<?php
require_once 'includes/config.php';
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get date range from URL params
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // First day of current month
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'); // Today

// Get borrowing statistics for the period
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_borrows,
        COUNT(DISTINCT user_id) as unique_users,
        COUNT(DISTINCT book_id) as unique_books,
        SUM(CASE WHEN status = 'returned' THEN 1 ELSE 0 END) as returned_books,
        SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue_books,
        AVG(DATEDIFF(
            CASE WHEN return_date IS NOT NULL THEN return_date ELSE CURDATE() END, 
            borrow_date
        )) as avg_borrow_duration
    FROM borrowed_books 
    WHERE borrow_date BETWEEN ? AND ?
");
$stmt->execute([$start_date, $end_date]);
$period_stats = $stmt->fetch();

// Get most popular books
$stmt = $pdo->prepare("
    SELECT b.title, b.author, b.cover_image, COUNT(bb.id) as borrow_count
    FROM books b
    JOIN borrowed_books bb ON b.id = bb.book_id
    WHERE bb.borrow_date BETWEEN ? AND ?
    GROUP BY b.id, b.title, b.author, b.cover_image
    ORDER BY borrow_count DESC
    LIMIT 10
");
$stmt->execute([$start_date, $end_date]);
$popular_books = $stmt->fetchAll();

// Get most active users
$stmt = $pdo->prepare("
    SELECT u.full_name, u.email, COUNT(bb.id) as borrow_count,
           SUM(CASE WHEN bb.status = 'overdue' THEN 1 ELSE 0 END) as overdue_count
    FROM users u
    JOIN borrowed_books bb ON u.id = bb.user_id
    WHERE bb.borrow_date BETWEEN ? AND ?
    GROUP BY u.id, u.full_name, u.email
    ORDER BY borrow_count DESC
    LIMIT 10
");
$stmt->execute([$start_date, $end_date]);
$active_users = $stmt->fetchAll();

// Get category statistics
$stmt = $pdo->prepare("
    SELECT b.category, COUNT(bb.id) as borrow_count
    FROM books b
    JOIN borrowed_books bb ON b.id = bb.book_id
    WHERE bb.borrow_date BETWEEN ? AND ?
    GROUP BY b.category
    ORDER BY borrow_count DESC
");
$stmt->execute([$start_date, $end_date]);
$category_stats = $stmt->fetchAll();

// Get daily borrowing trend
$stmt = $pdo->prepare("
    SELECT DATE(borrow_date) as date, COUNT(*) as count
    FROM borrowed_books
    WHERE borrow_date BETWEEN ? AND ?
    GROUP BY DATE(borrow_date)
    ORDER BY date
");
$stmt->execute([$start_date, $end_date]);
$daily_trends = $stmt->fetchAll();

// Get overdue analysis
$stmt = $pdo->prepare("
    SELECT 
        u.full_name, u.email, b.title, bb.due_date,
        DATEDIFF(CURDATE(), bb.due_date) as days_overdue
    FROM borrowed_books bb
    JOIN users u ON bb.user_id = u.id
    JOIN books b ON bb.book_id = b.id
    WHERE bb.status = 'overdue'
    ORDER BY days_overdue DESC
    LIMIT 20
");
$stmt->execute();
$overdue_analysis = $stmt->fetchAll();

$page_title = 'Reportes y Estadísticas';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookData - <?php echo $page_title; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        .export-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .export-btn {
            padding: 8px 16px;
            border-radius: 20px;
            border: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .export-btn.excel {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .export-btn.pdf {
            background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
            color: white;
        }
        
        .export-btn.print {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
        }
        
        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <?php require_once 'includes/header.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            <div class="reports-container">
                <!-- Page Header -->
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-chart-line me-3"></i>Reportes y Estadísticas
                    </h1>
                    <p class="text-muted">Análisis detallado del rendimiento de la biblioteca</p>
                </div>

                <!-- Period Selector -->
                <div class="period-selector">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <form method="GET" class="d-flex align-items-center gap-3">
                                <div class="d-flex align-items-center gap-2">
                                    <label for="start_date" class="form-label mb-0">Desde:</label>
                                    <input type="date" class="form-control" name="start_date" id="start_date" value="<?php echo $start_date; ?>">
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <label for="end_date" class="form-label mb-0">Hasta:</label>
                                    <input type="date" class="form-control" name="end_date" id="end_date" value="<?php echo $end_date; ?>">
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Actualizar
                                </button>
                            </form>
                        </div>
                        <div class="col-md-4">
                            <div class="export-buttons justify-content-end">
                                <button class="export-btn excel" onclick="exportToExcel()">
                                    <i class="fas fa-file-excel me-2"></i>Excel
                                </button>
                                <button class="export-btn pdf" onclick="exportToPDF()">
                                    <i class="fas fa-file-pdf me-2"></i>PDF
                                </button>
                                <button class="export-btn print" onclick="printReport()">
                                    <i class="fas fa-print me-2"></i>Imprimir
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Key Statistics -->
                <div class="row stats-row">
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card">
                            <i class="fas fa-handshake stat-icon"></i>
                            <div class="stat-value"><?php echo number_format($period_stats['total_borrows'] ?? 0); ?></div>
                            <div class="stat-label">Total Préstamos</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card">
                            <i class="fas fa-users stat-icon"></i>
                            <div class="stat-value"><?php echo number_format($period_stats['unique_users'] ?? 0); ?></div>
                            <div class="stat-label">Usuarios Activos</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card">
                            <i class="fas fa-books stat-icon"></i>
                            <div class="stat-value"><?php echo number_format($period_stats['unique_books'] ?? 0); ?></div>
                            <div class="stat-label">Libros Prestados</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card">
                            <i class="fas fa-clock stat-icon"></i>
                            <div class="stat-value"><?php echo number_format($period_stats['avg_borrow_duration'] ?? 0, 1); ?></div>
                            <div class="stat-label">Días Promedio</div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Borrowing Trends Chart -->
                    <div class="col-lg-8">
                        <div class="content-card">
                            <h4 class="section-title">
                                <i class="fas fa-chart-line me-2"></i>Tendencia de Préstamos
                            </h4>
                            <div class="chart-container">
                                <canvas id="borrowingTrendChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Category Distribution -->
                    <div class="col-lg-4">
                        <div class="content-card">
                            <h4 class="section-title">
                                <i class="fas fa-chart-pie me-2"></i>Por Categorías
                            </h4>
                            <div class="chart-container">
                                <canvas id="categoryChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Most Popular Books -->
                    <div class="col-lg-6">
                        <div class="content-card">
                            <h4 class="section-title">
                                <i class="fas fa-trophy me-2"></i>Libros Más Populares
                            </h4>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Libro</th>
                                            <th>Préstamos</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($popular_books as $index => $book): ?>
                                            <tr>
                                                <td>
                                                    <span class="ranking-number <?php 
                                                        echo $index === 0 ? 'first' : 
                                                            ($index === 1 ? 'second' : 
                                                            ($index === 2 ? 'third' : 'other')); 
                                                    ?>">
                                                        <?php echo $index + 1; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="<?php echo $book['cover_image']; ?>" 
                                                             alt="<?php echo htmlspecialchars($book['title']); ?>" 
                                                             class="book-cover-small">
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($book['title']); ?></strong>
                                                            <br><small class="text-muted"><?php echo htmlspecialchars($book['author']); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary"><?php echo $book['borrow_count']; ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Most Active Users -->
                    <div class="col-lg-6">
                        <div class="content-card">
                            <h4 class="section-title">
                                <i class="fas fa-user-graduate me-2"></i>Usuarios Más Activos
                            </h4>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Usuario</th>
                                            <th>Préstamos</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($active_users as $index => $user): ?>
                                            <tr>
                                                <td>
                                                    <span class="ranking-number <?php 
                                                        echo $index === 0 ? 'first' : 
                                                            ($index === 1 ? 'second' : 
                                                            ($index === 2 ? 'third' : 'other')); 
                                                    ?>">
                                                        <?php echo $index + 1; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="user-avatar-small">
                                                            <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                                        </div>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                                            <br><small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-success"><?php echo $user['borrow_count']; ?></span>
                                                    <?php if ($user['overdue_count'] > 0): ?>
                                                        <br><small class="text-danger">
                                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                                            <?php echo $user['overdue_count']; ?> vencidos
                                                        </small>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Overdue Analysis -->
                <?php if (!empty($overdue_analysis)): ?>
                <div class="content-card">
                    <h4 class="section-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>Análisis de Préstamos Vencidos
                    </h4>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Libro</th>
                                    <th>Fecha Vencimiento</th>
                                    <th>Días Vencido</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($overdue_analysis as $overdue): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar-small">
                                                    <?php echo strtoupper(substr($overdue['full_name'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($overdue['full_name']); ?></strong>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($overdue['email']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($overdue['title']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($overdue['due_date'])); ?></td>
                                        <td>
                                            <span class="overdue-days <?php 
                                                echo $overdue['days_overdue'] > 30 ? 'overdue-critical' : 
                                                    ($overdue['days_overdue'] > 14 ? 'overdue-warning' : 'overdue-mild'); 
                                            ?>">
                                                <?php echo $overdue['days_overdue']; ?> días
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Initialize charts when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initializeBorrowingTrendChart();
            initializeCategoryChart();
        });

        // Borrowing Trend Chart
        function initializeBorrowingTrendChart() {
            const ctx = document.getElementById('borrowingTrendChart').getContext('2d');
            
            const chartData = <?php echo json_encode($daily_trends); ?>;
            const labels = chartData.map(item => {
                const date = new Date(item.date);
                return date.toLocaleDateString('es-ES', { month: 'short', day: 'numeric' });
            });
            const data = chartData.map(item => item.count);

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Préstamos por día',
                        data: data,
                        borderColor: 'rgb(102, 126, 234)',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: 'rgb(102, 126, 234)',
                        pointBorderColor: 'white',
                        pointBorderWidth: 2,
                        pointRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    elements: {
                        point: {
                            hoverRadius: 8
                        }
                    }
                }
            });
        }

        // Category Distribution Chart
        function initializeCategoryChart() {
            const ctx = document.getElementById('categoryChart').getContext('2d');
            
            const chartData = <?php echo json_encode($category_stats); ?>;
            const labels = chartData.map(item => item.category);
            const data = chartData.map(item => item.borrow_count);
            
            const colors = [
                'rgba(102, 126, 234, 0.8)',
                'rgba(118, 75, 162, 0.8)',
                'rgba(250, 112, 154, 0.8)',
                'rgba(254, 225, 64, 0.8)',
                'rgba(67, 233, 123, 0.8)',
                'rgba(79, 172, 254, 0.8)',
                'rgba(255, 107, 107, 0.8)',
                'rgba(255, 167, 38, 0.8)',
                'rgba(162, 155, 254, 0.8)',
                'rgba(86, 204, 242, 0.8)'
            ];

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: colors.slice(0, data.length),
                        borderWidth: 2,
                        borderColor: 'white',
                        hoverBorderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                usePointStyle: true,
                                font: {
                                    size: 12
                                }
                            }
                        }
                    },
                    cutout: '60%'
                }
            });
        }

        // Export functions
        function exportToExcel() {
            const params = new URLSearchParams(window.location.search);
            window.location.href = `export_reports.php?format=excel&${params.toString()}`;
        }

        function exportToPDF() {
            const params = new URLSearchParams(window.location.search);
            window.location.href = `export_reports.php?format=pdf&${params.toString()}`;
        }

        function printReport() {
            window.print();
        }
    </script>
</body>
</html>
