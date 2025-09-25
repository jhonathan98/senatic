<?php
require_once 'includes/config.php';
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_book':
                $title = trim($_POST['title']);
                $author = trim($_POST['author']);
                $isbn = trim($_POST['isbn']);
                $category = $_POST['category'];
                $quantity = intval($_POST['quantity']);
                $description = trim($_POST['description']);
                
                // Handle image upload
                $cover_image = 'assets/images/book-placeholder.jpg';
                if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === 0) {
                    $upload_dir = 'assets/images/books/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_extension = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
                    $new_filename = uniqid() . '.' . $file_extension;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $upload_path)) {
                        $cover_image = $upload_path;
                    }
                }
                
                $stmt = $pdo->prepare("INSERT INTO books (title, author, isbn, category, quantity, available_quantity, description, cover_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$title, $author, $isbn, $category, $quantity, $quantity, $description, $cover_image])) {
                    $message = 'Libro agregado exitosamente.';
                    $message_type = 'success';
                } else {
                    $message = 'Error al agregar el libro.';
                    $message_type = 'danger';
                }
                break;
                
            case 'edit_book':
                $book_id = $_POST['book_id'];
                $title = trim($_POST['title']);
                $author = trim($_POST['author']);
                $isbn = trim($_POST['isbn']);
                $category = $_POST['category'];
                $quantity = intval($_POST['quantity']);
                $description = trim($_POST['description']);
                
                // Get current book data
                $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
                $stmt->execute([$book_id]);
                $current_book = $stmt->fetch();
                
                $cover_image = $current_book['cover_image'];
                
                // Handle image upload
                if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === 0) {
                    $upload_dir = 'assets/images/books/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_extension = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
                    $new_filename = uniqid() . '.' . $file_extension;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $upload_path)) {
                        // Delete old image if it's not the placeholder
                        if ($current_book['cover_image'] !== 'assets/images/book-placeholder.jpg' && file_exists($current_book['cover_image'])) {
                            unlink($current_book['cover_image']);
                        }
                        $cover_image = $upload_path;
                    }
                }
                
                // Calculate new available quantity
                $borrowed_count = $current_book['quantity'] - $current_book['available_quantity'];
                $new_available = max(0, $quantity - $borrowed_count);
                
                $stmt = $pdo->prepare("UPDATE books SET title = ?, author = ?, isbn = ?, category = ?, quantity = ?, available_quantity = ?, description = ?, cover_image = ? WHERE id = ?");
                if ($stmt->execute([$title, $author, $isbn, $category, $quantity, $new_available, $description, $cover_image, $book_id])) {
                    $message = 'Libro actualizado exitosamente.';
                    $message_type = 'success';
                } else {
                    $message = 'Error al actualizar el libro.';
                    $message_type = 'danger';
                }
                break;
                
            case 'delete_book':
                $book_id = $_POST['book_id'];
                
                // Check if book has active borrows
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM borrowed_books WHERE book_id = ? AND status IN ('active', 'overdue')");
                $stmt->execute([$book_id]);
                $active_borrows = $stmt->fetchColumn();
                
                if ($active_borrows > 0) {
                    $message = 'No se puede eliminar el libro porque tiene préstamos activos.';
                    $message_type = 'danger';
                } else {
                    // Get book data to delete image
                    $stmt = $pdo->prepare("SELECT cover_image FROM books WHERE id = ?");
                    $stmt->execute([$book_id]);
                    $book = $stmt->fetch();
                    
                    $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
                    if ($stmt->execute([$book_id])) {
                        // Delete image file if it's not the placeholder
                        if ($book && $book['cover_image'] !== 'assets/images/book-placeholder.jpg' && file_exists($book['cover_image'])) {
                            unlink($book['cover_image']);
                        }
                        $message = 'Libro eliminado exitosamente.';
                        $message_type = 'success';
                    } else {
                        $message = 'Error al eliminar el libro.';
                        $message_type = 'danger';
                    }
                }
                break;
                
            case 'bulk_import':
                // Handle bulk import functionality
                $message = 'Funcionalidad de importación masiva - En desarrollo.';
                $message_type = 'info';
                break;
        }
    }
}

// Get book statistics
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total,
    SUM(quantity) as total_copies,
    SUM(available_quantity) as available_copies,
    COUNT(DISTINCT category) as categories
FROM books");
$stmt->execute();
$stats = $stmt->fetch();

// Get categories for filter
$stmt = $pdo->prepare("SELECT DISTINCT category FROM books ORDER BY category");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Check if filtering by category
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';

// Get all books with borrowing information
$sql = "
    SELECT b.*, 
           COUNT(bb.id) as total_borrows,
           SUM(CASE WHEN bb.status = 'active' THEN 1 ELSE 0 END) as active_borrows,
           MAX(bb.borrow_date) as last_borrow_date
    FROM books b 
    LEFT JOIN borrowed_books bb ON b.id = bb.book_id";

$params = [];
if ($category_filter) {
    $sql .= " WHERE b.category = ?";
    $params[] = $category_filter;
}

$sql .= " GROUP BY b.id ORDER BY b.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

$page_title = 'Gestión de Libros';
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
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php require_once 'includes/header.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            <div class="manage-container">
                <!-- Page Header -->
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-books me-3"></i>Gestión de Libros
                    </h1>
                    <p class="text-muted">Administra todo el catálogo de libros de la biblioteca</p>
                </div>

                <!-- Category Filter Alert -->
                <?php if ($category_filter): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <i class="fas fa-filter me-2"></i>
                        Mostrando libros de la categoría: <strong><?php echo htmlspecialchars($category_filter); ?></strong>
                        <a href="manage_books.php" class="btn btn-sm btn-outline-info ms-3">
                            <i class="fas fa-times me-1"></i>Mostrar Todos
                        </a>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Alert Messages -->
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row stats-row">
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card total">
                            <i class="fas fa-book" style="font-size: 2rem; opacity: 0.8; margin-bottom: 10px;"></i>
                            <div class="stat-value"><?php echo $stats['total']; ?></div>
                            <div class="stat-label">Total Libros</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card copies">
                            <i class="fas fa-layer-group" style="font-size: 2rem; opacity: 0.8; margin-bottom: 10px;"></i>
                            <div class="stat-value"><?php echo $stats['total_copies']; ?></div>
                            <div class="stat-label">Total Copias</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card available">
                            <i class="fas fa-check-circle" style="font-size: 2rem; opacity: 0.8; margin-bottom: 10px;"></i>
                            <div class="stat-value"><?php echo $stats['available_copies']; ?></div>
                            <div class="stat-label">Copias Disponibles</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card categories">
                            <i class="fas fa-tags" style="font-size: 2rem; opacity: 0.8; margin-bottom: 10px;"></i>
                            <div class="stat-value"><?php echo $stats['categories']; ?></div>
                            <div class="stat-label">Categorías</div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="content-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="section-title mb-0">
                            <i class="fas fa-bolt me-2"></i>Acciones Rápidas
                        </h4>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#addBookModal">
                                <i class="fas fa-plus me-2"></i>Nuevo Libro
                            </button>
                            <button class="btn btn-info me-2" onclick="bulkImport()">
                                <i class="fas fa-upload me-2"></i>Importar Masiva
                            </button>
                            <button class="btn btn-warning me-2" onclick="exportBooks()">
                                <i class="fas fa-download me-2"></i>Exportar Catálogo
                            </button>
                            <button class="btn btn-secondary" onclick="generateReports()">
                                <i class="fas fa-chart-bar me-2"></i>Reportes
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="filters-section">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" placeholder="Buscar libros..." id="searchBooks">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" id="filterCategory">
                                <option value="">Todas las categorías</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" id="filterAvailability">
                                <option value="">Todas las disponibilidades</option>
                                <option value="available">Disponibles</option>
                                <option value="low">Stock bajo</option>
                                <option value="out">Agotados</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" id="sortBy">
                                <option value="newest">Más recientes</option>
                                <option value="title">Por título</option>
                                <option value="author">Por autor</option>
                                <option value="popular">Más populares</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-secondary" onclick="clearFilters()">
                                    <i class="fas fa-times me-2"></i>Limpiar
                                </button>
                                <div class="view-toggle">
                                    <button type="button" class="active" id="gridView" onclick="toggleView('grid')">
                                        <i class="fas fa-th"></i>
                                    </button>
                                    <button type="button" id="listView" onclick="toggleView('list')">
                                        <i class="fas fa-list"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Books Display -->
                <div class="content-card">
                    <h4 class="section-title">
                        <i class="fas fa-books me-2"></i>Catálogo de Libros
                    </h4>
                    
                    <!-- Grid View -->
                    <div id="booksGrid" class="book-grid">
                        <?php foreach ($books as $book): ?>
                            <div class="book-card" 
                                 data-title="<?php echo strtolower($book['title']); ?>"
                                 data-author="<?php echo strtolower($book['author']); ?>"
                                 data-category="<?php echo $book['category']; ?>"
                                 data-availability="<?php echo $book['available_quantity'] == 0 ? 'out' : ($book['available_quantity'] <= 2 ? 'low' : 'available'); ?>">
                                
                                <img src="<?php echo $book['cover_image']; ?>" 
                                     alt="<?php echo htmlspecialchars($book['title']); ?>" 
                                     class="book-cover">
                                
                                <div class="book-info">
                                    <div class="book-title"><?php echo htmlspecialchars($book['title']); ?></div>
                                    <div class="book-author">por <?php echo htmlspecialchars($book['author']); ?></div>
                                    <div class="book-category"><?php echo htmlspecialchars($book['category']); ?></div>
                                    
                                    <div class="book-stats">
                                        <span class="quantity-info <?php echo $book['available_quantity'] == 0 ? 'out' : ($book['available_quantity'] <= 2 ? 'low' : ''); ?>">
                                            <i class="fas fa-copy me-1"></i>
                                            <?php echo $book['available_quantity']; ?>/<?php echo $book['quantity']; ?> disponibles
                                        </span>
                                        <small class="text-muted">
                                            <i class="fas fa-hand-holding me-1"></i>
                                            <?php echo $book['total_borrows']; ?> préstamos
                                        </small>
                                    </div>
                                    
                                    <?php if ($book['isbn']): ?>
                                        <small class="text-muted d-block">
                                            <i class="fas fa-barcode me-1"></i>ISBN: <?php echo $book['isbn']; ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="book-actions">
                                    <button class="action-btn btn-view" onclick="viewBook(<?php echo $book['id']; ?>)" title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn btn-edit" onclick="editBook(<?php echo $book['id']; ?>)" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn btn-delete" 
                                            onclick="deleteBook(<?php echo $book['id']; ?>, '<?php echo htmlspecialchars($book['title']); ?>', <?php echo $book['active_borrows']; ?>)" 
                                            title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- List View (Hidden by default) -->
                    <div id="booksList" class="table-responsive" style="display: none;">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-book me-2"></i>Libro</th>
                                    <th><i class="fas fa-user me-2"></i>Autor</th>
                                    <th><i class="fas fa-tag me-2"></i>Categoría</th>
                                    <th><i class="fas fa-layer-group me-2"></i>Stock</th>
                                    <th><i class="fas fa-hand-holding me-2"></i>Préstamos</th>
                                    <th><i class="fas fa-cogs me-2"></i>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($books as $book): ?>
                                    <tr class="book-row"
                                        data-title="<?php echo strtolower($book['title']); ?>"
                                        data-author="<?php echo strtolower($book['author']); ?>"
                                        data-category="<?php echo $book['category']; ?>"
                                        data-availability="<?php echo $book['available_quantity'] == 0 ? 'out' : ($book['available_quantity'] <= 2 ? 'low' : 'available'); ?>">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo $book['cover_image']; ?>" 
                                                     alt="<?php echo htmlspecialchars($book['title']); ?>" 
                                                     style="width: 40px; height: 50px; object-fit: cover; border-radius: 5px; margin-right: 10px;">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($book['title']); ?></strong>
                                                    <?php if ($book['isbn']): ?>
                                                        <br><small class="text-muted">ISBN: <?php echo $book['isbn']; ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($book['author']); ?></td>
                                        <td>
                                            <span class="book-category"><?php echo htmlspecialchars($book['category']); ?></span>
                                        </td>
                                        <td>
                                            <span class="quantity-info <?php echo $book['available_quantity'] == 0 ? 'out' : ($book['available_quantity'] <= 2 ? 'low' : ''); ?>">
                                                <?php echo $book['available_quantity']; ?>/<?php echo $book['quantity']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo $book['total_borrows']; ?></span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-outline-primary" onclick="viewBook(<?php echo $book['id']; ?>)" title="Ver">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-warning" onclick="editBook(<?php echo $book['id']; ?>)" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="deleteBook(<?php echo $book['id']; ?>, '<?php echo htmlspecialchars($book['title']); ?>', <?php echo $book['active_borrows']; ?>)" 
                                                        title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Book Modal -->
    <div class="modal fade" id="addBookModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Agregar Nuevo Libro
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_book">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Título *</label>
                                    <input type="text" class="form-control" name="title" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="author" class="form-label">Autor *</label>
                                    <input type="text" class="form-control" name="author" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="isbn" class="form-label">ISBN</label>
                                    <input type="text" class="form-control" name="isbn">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="category" class="form-label">Categoría *</label>
                                    <select class="form-select" name="category" required>
                                        <option value="">Seleccionar categoría</option>
                                        <option value="Ficción">Ficción</option>
                                        <option value="No Ficción">No Ficción</option>
                                        <option value="Ciencia">Ciencia</option>
                                        <option value="Historia">Historia</option>
                                        <option value="Biografía">Biografía</option>
                                        <option value="Tecnología">Tecnología</option>
                                        <option value="Arte">Arte</option>
                                        <option value="Filosofía">Filosofía</option>
                                        <option value="Literatura">Literatura</option>
                                        <option value="Educación">Educación</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="quantity" class="form-label">Cantidad *</label>
                                    <input type="number" class="form-control" name="quantity" min="1" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="cover_image" class="form-label">Imagen de Portada</label>
                                    <input type="file" class="form-control" name="cover_image" accept="image/*">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Descripción</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Guardar Libro
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Book Modal -->
    <div class="modal fade" id="editBookModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Editar Libro
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_book">
                        <input type="hidden" name="book_id" id="edit_book_id">
                        <div id="edit_form_content">
                            <!-- Content will be loaded via JavaScript -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Actualizar Libro
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let currentView = 'grid';

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            setupFilters();
            animateCounters();
        });

        // Setup filters
        function setupFilters() {
            const searchInput = document.getElementById('searchBooks');
            const categoryFilter = document.getElementById('filterCategory');
            const availabilityFilter = document.getElementById('filterAvailability');
            const sortBy = document.getElementById('sortBy');

            [searchInput, categoryFilter, availabilityFilter, sortBy].forEach(element => {
                if (element) {
                    element.addEventListener('input', filterBooks);
                    element.addEventListener('change', filterBooks);
                }
            });
        }

        // Filter books
        function filterBooks() {
            const searchTerm = document.getElementById('searchBooks')?.value.toLowerCase() || '';
            const categoryFilter = document.getElementById('filterCategory')?.value || '';
            const availabilityFilter = document.getElementById('filterAvailability')?.value || '';
            
            const items = currentView === 'grid' ? 
                document.querySelectorAll('.book-card') : 
                document.querySelectorAll('.book-row');

            items.forEach(item => {
                const title = item.dataset.title || '';
                const author = item.dataset.author || '';
                const category = item.dataset.category || '';
                const availability = item.dataset.availability || '';

                const matchesSearch = title.includes(searchTerm) || author.includes(searchTerm);
                const matchesCategory = !categoryFilter || category === categoryFilter;
                const matchesAvailability = !availabilityFilter || availability === availabilityFilter;

                if (matchesSearch && matchesCategory && matchesAvailability) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // Toggle between grid and list view
        function toggleView(view) {
            currentView = view;
            const gridView = document.getElementById('booksGrid');
            const listView = document.getElementById('booksList');
            const gridBtn = document.getElementById('gridView');
            const listBtn = document.getElementById('listView');

            if (view === 'grid') {
                gridView.style.display = 'grid';
                listView.style.display = 'none';
                gridBtn.classList.add('active');
                listBtn.classList.remove('active');
            } else {
                gridView.style.display = 'none';
                listView.style.display = 'block';
                listBtn.classList.add('active');
                gridBtn.classList.remove('active');
            }

            filterBooks(); // Reapply filters
        }

        // Clear filters
        function clearFilters() {
            document.getElementById('searchBooks').value = '';
            document.getElementById('filterCategory').value = '';
            document.getElementById('filterAvailability').value = '';
            document.getElementById('sortBy').value = 'newest';
            filterBooks();
        }

        // View book details
        function viewBook(bookId) {
            window.location.href = `book_detail.php?id=${bookId}`;
        }

        // Edit book
        function editBook(bookId) {
            // Load book data via AJAX and populate edit form
            fetch(`get_book_data.php?id=${bookId}`)
                .then(response => response.json())
                .then(book => {
                    document.getElementById('edit_book_id').value = bookId;
                    document.getElementById('edit_form_content').innerHTML = `
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_title" class="form-label">Título *</label>
                                    <input type="text" class="form-control" name="title" value="${book.title}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_author" class="form-label">Autor *</label>
                                    <input type="text" class="form-control" name="author" value="${book.author}" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_isbn" class="form-label">ISBN</label>
                                    <input type="text" class="form-control" name="isbn" value="${book.isbn || ''}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_category" class="form-label">Categoría *</label>
                                    <select class="form-select" name="category" required>
                                        <option value="Ficción" ${book.category === 'Ficción' ? 'selected' : ''}>Ficción</option>
                                        <option value="No Ficción" ${book.category === 'No Ficción' ? 'selected' : ''}>No Ficción</option>
                                        <option value="Ciencia" ${book.category === 'Ciencia' ? 'selected' : ''}>Ciencia</option>
                                        <option value="Historia" ${book.category === 'Historia' ? 'selected' : ''}>Historia</option>
                                        <option value="Biografía" ${book.category === 'Biografía' ? 'selected' : ''}>Biografía</option>
                                        <option value="Tecnología" ${book.category === 'Tecnología' ? 'selected' : ''}>Tecnología</option>
                                        <option value="Arte" ${book.category === 'Arte' ? 'selected' : ''}>Arte</option>
                                        <option value="Filosofía" ${book.category === 'Filosofía' ? 'selected' : ''}>Filosofía</option>
                                        <option value="Literatura" ${book.category === 'Literatura' ? 'selected' : ''}>Literatura</option>
                                        <option value="Educación" ${book.category === 'Educación' ? 'selected' : ''}>Educación</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_quantity" class="form-label">Cantidad *</label>
                                    <input type="number" class="form-control" name="quantity" value="${book.quantity}" min="1" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_cover_image" class="form-label">Nueva Imagen de Portada</label>
                                    <input type="file" class="form-control" name="cover_image" accept="image/*">
                                    <small class="text-muted">Dejar vacío para mantener la imagen actual</small>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Descripción</label>
                            <textarea class="form-control" name="description" rows="3">${book.description || ''}</textarea>
                        </div>
                    `;
                    new bootstrap.Modal(document.getElementById('editBookModal')).show();
                })
                .catch(error => {
                    console.error('Error loading book data:', error);
                    alert('Error al cargar los datos del libro');
                });
        }

        // Delete book
        function deleteBook(bookId, bookTitle, activeBorrows) {
            if (activeBorrows > 0) {
                alert(`No se puede eliminar "${bookTitle}" porque tiene ${activeBorrows} préstamos activos.`);
                return;
            }
            
            if (confirm(`¿ELIMINAR PERMANENTEMENTE el libro "${bookTitle}"?\n\nEsta acción no se puede deshacer.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_book">
                    <input type="hidden" name="book_id" value="${bookId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Bulk import
        function bulkImport() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="action" value="bulk_import">';
            document.body.appendChild(form);
            form.submit();
        }

        // Export books
        function exportBooks() {
            if (confirm('¿Exportar el catálogo de libros a Excel?')) {
                window.location.href = 'export_books.php';
            }
        }

        // Generate reports
        function generateReports() {
            alert('Funcionalidad de reportes - En desarrollo');
        }

        // Animate counters
        function animateCounters() {
            const counters = document.querySelectorAll('.stat-value');
            counters.forEach(counter => {
                const target = parseInt(counter.textContent);
                let current = 0;
                const increment = target / 30;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        current = target;
                        clearInterval(timer);
                    }
                    counter.textContent = Math.floor(current);
                }, 50);
            });
        }
    </script>
</body>
</html>
