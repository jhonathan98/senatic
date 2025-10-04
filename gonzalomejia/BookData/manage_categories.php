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
            case 'add_category':
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                
                // Check if category already exists
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name = ?");
                $stmt->execute([$name]);
                if ($stmt->fetchColumn() > 0) {
                    $message = 'La categoría ya existe.';
                    $message_type = 'danger';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
                    if ($stmt->execute([$name, $description])) {
                        $message = 'Categoría agregada exitosamente.';
                        $message_type = 'success';
                    } else {
                        $message = 'Error al agregar la categoría.';
                        $message_type = 'danger';
                    }
                }
                break;
                
            case 'edit_category':
                $category_id = $_POST['category_id'];
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                
                // Check if another category with same name exists
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name = ? AND id != ?");
                $stmt->execute([$name, $category_id]);
                if ($stmt->fetchColumn() > 0) {
                    $message = 'Ya existe otra categoría con ese nombre.';
                    $message_type = 'danger';
                } else {
                    $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
                    if ($stmt->execute([$name, $description, $category_id])) {
                        $message = 'Categoría actualizada exitosamente.';
                        $message_type = 'success';
                    } else {
                        $message = 'Error al actualizar la categoría.';
                        $message_type = 'danger';
                    }
                }
                break;
                
            case 'delete_category':
                $category_id = $_POST['category_id'];
                
                // Check if category has books assigned
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE category = (SELECT name FROM categories WHERE id = ?)");
                $stmt->execute([$category_id]);
                $books_count = $stmt->fetchColumn();
                
                if ($books_count > 0) {
                    $message = "No se puede eliminar la categoría porque tiene {$books_count} libros asignados.";
                    $message_type = 'danger';
                } else {
                    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                    if ($stmt->execute([$category_id])) {
                        $message = 'Categoría eliminada exitosamente.';
                        $message_type = 'success';
                    } else {
                        $message = 'Error al eliminar la categoría.';
                        $message_type = 'danger';
                    }
                }
                break;
                
            case 'merge_categories':
                $source_id = $_POST['source_category'];
                $target_id = $_POST['target_category'];
                
                if ($source_id === $target_id) {
                    $message = 'No puedes fusionar una categoría consigo misma.';
                    $message_type = 'danger';
                } else {
                    $pdo->beginTransaction();
                    try {
                        // Get category names
                        $stmt = $pdo->prepare("SELECT name FROM categories WHERE id IN (?, ?)");
                        $stmt->execute([$source_id, $target_id]);
                        $categories = $stmt->fetchAll();
                        
                        if (count($categories) !== 2) {
                            throw new Exception('Categorías no válidas');
                        }
                        
                        $target_name = '';
                        foreach ($categories as $cat) {
                            if ($cat['name']) {
                                $target_name = $cat['name'];
                                break;
                            }
                        }
                        
                        // Update books to use target category
                        $stmt = $pdo->prepare("
                            UPDATE books 
                            SET category = (SELECT name FROM categories WHERE id = ?) 
                            WHERE category = (SELECT name FROM categories WHERE id = ?)
                        ");
                        $stmt->execute([$target_id, $source_id]);
                        
                        // Delete source category
                        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                        $stmt->execute([$source_id]);
                        
                        $pdo->commit();
                        $message = 'Categorías fusionadas exitosamente.';
                        $message_type = 'success';
                        
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $message = 'Error al fusionar categorías: ' . $e->getMessage();
                        $message_type = 'danger';
                    }
                }
                break;
        }
    }
}

// Get categories with book counts
$stmt = $pdo->prepare("
    SELECT c.*, 
           COUNT(b.id) as book_count,
           COUNT(CASE WHEN b.availability = 'available' THEN 1 END) as available_books
    FROM categories c
    LEFT JOIN books b ON c.name = b.category
    GROUP BY c.id, c.name, c.description, c.created_at, c.updated_at
    ORDER BY c.name ASC
");
$stmt->execute();
$categories = $stmt->fetchAll();

// Get statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_categories,
        COUNT(CASE WHEN EXISTS(SELECT 1 FROM books WHERE books.category = categories.name) THEN 1 END) as categories_with_books,
        AVG((SELECT COUNT(*) FROM books WHERE books.category = categories.name)) as avg_books_per_category
    FROM categories
");
$stmt->execute();
$stats = $stmt->fetch();

$page_title = 'Gestión de Categorías';
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
        <div class="container-fluid">
            <div class="manage-container">
                <!-- Page Header -->
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-tags me-3"></i>Gestión de Categorías
                    </h1>
                    <p class="text-muted">Administra las categorías de libros del sistema</p>
                </div>

                <!-- Alert Messages -->
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row stats-row">
                    <div class="col-xl-4 col-md-6">
                        <div class="stat-card total">
                            <i class="fas fa-tags" style="font-size: 2rem; opacity: 0.8; margin-bottom: 10px;"></i>
                            <div class="stat-value"><?php echo $stats['total_categories']; ?></div>
                            <div class="stat-label">Total Categorías</div>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <div class="stat-card active">
                            <i class="fas fa-book" style="font-size: 2rem; opacity: 0.8; margin-bottom: 10px;"></i>
                            <div class="stat-value"><?php echo $stats['categories_with_books']; ?></div>
                            <div class="stat-label">Con Libros</div>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <div class="stat-card info">
                            <i class="fas fa-chart-bar" style="font-size: 2rem; opacity: 0.8; margin-bottom: 10px;"></i>
                            <div class="stat-value"><?php echo round($stats['avg_books_per_category'] ?? 0, 1); ?></div>
                            <div class="stat-label">Promedio Libros/Categoría</div>
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
                            <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                <i class="fas fa-plus me-2"></i>Nueva Categoría
                            </button>
                            <button class="btn btn-warning me-2" data-bs-toggle="modal" data-bs-target="#mergeCategoriesModal">
                                <i class="fas fa-code-branch me-2"></i>Fusionar Categorías
                            </button>
                            <button class="btn btn-info me-2" onclick="exportCategories()">
                                <i class="fas fa-download me-2"></i>Exportar Lista
                            </button>
                            <button class="btn btn-secondary" onclick="generateCategoryReport()">
                                <i class="fas fa-chart-pie me-2"></i>Reporte de Uso
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Search and Filters -->
                <div class="filters-section">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" placeholder="Buscar categorías..." id="searchCategories">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="filterStatus">
                                <option value="">Todas las categorías</option>
                                <option value="with_books">Con libros</option>
                                <option value="empty">Sin libros</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="sortBy">
                                <option value="name">Por nombre</option>
                                <option value="books_count">Por cantidad de libros</option>
                                <option value="created">Por fecha de creación</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-outline-secondary w-100" onclick="clearFilters()">
                                <i class="fas fa-times me-2"></i>Limpiar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Categories Grid -->
                <div class="content-card">
                    <h4 class="section-title">
                        <i class="fas fa-list me-2"></i>Lista de Categorías
                    </h4>
                    
                    <div class="row" id="categoriesGrid">
                        <?php foreach ($categories as $category): ?>
                            <div class="col-md-6 col-lg-4 mb-4 category-item" 
                                 data-name="<?php echo strtolower($category['name']); ?>"
                                 data-status="<?php echo $category['book_count'] > 0 ? 'with_books' : 'empty'; ?>"
                                 data-books="<?php echo $category['book_count']; ?>"
                                 data-created="<?php echo $category['created_at']; ?>">
                                
                                <div class="category-card">
                                    <div class="category-header">
                                        <div class="category-icon">
                                            <i class="fas fa-tag"></i>
                                        </div>
                                        <div class="category-info">
                                            <h5 class="category-name"><?php echo htmlspecialchars($category['name']); ?></h5>
                                            <div class="category-stats">
                                                <span class="badge bg-primary"><?php echo $category['book_count']; ?> libros</span>
                                                <span class="badge bg-success"><?php echo $category['available_books']; ?> disponibles</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="category-body">
                                        <?php if ($category['description']): ?>
                                            <p class="category-description"><?php echo htmlspecialchars($category['description']); ?></p>
                                        <?php else: ?>
                                            <p class="category-description text-muted">Sin descripción</p>
                                        <?php endif; ?>
                                        
                                        <div class="category-meta">
                                            <small class="text-muted">
                                                <i class="fas fa-calendar me-1"></i>
                                                Creada: <?php echo date('d/m/Y', strtotime($category['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                    
                                    <div class="category-actions">
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="viewCategoryBooks(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>')" 
                                                title="Ver libros">
                                            <i class="fas fa-book"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning" 
                                                onclick="editCategory(<?php echo $category['id']; ?>)" 
                                                title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($category['book_count'] == 0): ?>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>')" 
                                                    title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-secondary" 
                                                    disabled 
                                                    title="No se puede eliminar (tiene libros)">
                                                <i class="fas fa-lock"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (empty($categories)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay categorías creadas</h5>
                            <p class="text-muted">Crea tu primera categoría para organizar los libros</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                <i class="fas fa-plus me-2"></i>Crear Primera Categoría
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Agregar Nueva Categoría
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_category">
                        
                        <div class="mb-3">
                            <label for="category_name" class="form-label">Nombre de la Categoría *</label>
                            <input type="text" class="form-control" name="name" id="category_name" required maxlength="50">
                            <div class="form-text">Máximo 50 caracteres</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="category_description" class="form-label">Descripción (Opcional)</label>
                            <textarea class="form-control" name="description" id="category_description" rows="3" maxlength="255"></textarea>
                            <div class="form-text">Máximo 255 caracteres</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Crear Categoría
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Editar Categoría
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_category">
                        <input type="hidden" name="category_id" id="edit_category_id">
                        
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Nombre de la Categoría *</label>
                            <input type="text" class="form-control" name="name" id="edit_name" required maxlength="50">
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Descripción (Opcional)</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="3" maxlength="255"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save me-2"></i>Actualizar Categoría
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Merge Categories Modal -->
    <div class="modal fade" id="mergeCategoriesModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-code-branch me-2"></i>Fusionar Categorías
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="merge_categories">
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Esta acción fusionará dos categorías moviendo todos los libros de una a otra y eliminando la categoría origen.
                        </div>
                        
                        <div class="mb-3">
                            <label for="source_category" class="form-label">Categoría Origen (será eliminada) *</label>
                            <select class="form-select" name="source_category" id="source_category" required>
                                <option value="">Seleccionar categoría origen</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?> (<?php echo $category['book_count']; ?> libros)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="target_category" class="form-label">Categoría Destino (recibirá los libros) *</label>
                            <select class="form-select" name="target_category" id="target_category" required>
                                <option value="">Seleccionar categoría destino</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?> (<?php echo $category['book_count']; ?> libros)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>¡Atención!</strong> Esta acción no se puede deshacer.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-code-branch me-2"></i>Fusionar Categorías
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Setup filters
        function setupFilters() {
            const searchInput = document.getElementById('searchCategories');
            const statusFilter = document.getElementById('filterStatus');
            const sortBy = document.getElementById('sortBy');

            [searchInput, statusFilter, sortBy].forEach(element => {
                if (element) {
                    element.addEventListener('input', filterCategories);
                    element.addEventListener('change', filterCategories);
                }
            });
        }

        // Filter categories
        function filterCategories() {
            const searchTerm = document.getElementById('searchCategories')?.value.toLowerCase() || '';
            const statusFilter = document.getElementById('filterStatus')?.value || '';
            const sortBy = document.getElementById('sortBy')?.value || 'name';
            
            let items = Array.from(document.querySelectorAll('.category-item'));

            // Filter items
            items.forEach(item => {
                const name = item.dataset.name || '';
                const status = item.dataset.status || '';

                const matchesSearch = name.includes(searchTerm);
                const matchesStatus = !statusFilter || status === statusFilter;

                if (matchesSearch && matchesStatus) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });

            // Sort visible items
            const visibleItems = items.filter(item => item.style.display !== 'none');
            sortItems(visibleItems, sortBy);
        }

        // Sort items
        function sortItems(items, sortBy) {
            const grid = document.getElementById('categoriesGrid');
            
            items.sort((a, b) => {
                switch (sortBy) {
                    case 'books_count':
                        return parseInt(b.dataset.books) - parseInt(a.dataset.books);
                    case 'created':
                        return new Date(b.dataset.created) - new Date(a.dataset.created);
                    case 'name':
                    default:
                        return a.dataset.name.localeCompare(b.dataset.name);
                }
            });

            // Reorder items in DOM
            items.forEach(item => {
                grid.appendChild(item);
            });
        }

        // Clear filters
        function clearFilters() {
            document.getElementById('searchCategories').value = '';
            document.getElementById('filterStatus').value = '';
            document.getElementById('sortBy').value = 'name';
            filterCategories();
        }

        // View category books
        function viewCategoryBooks(categoryId, categoryName) {
            window.location.href = `manage_books.php?category=${encodeURIComponent(categoryName)}`;
        }

        // Edit category
        function editCategory(categoryId) {
            // This would typically load category data via AJAX
            // For now, we'll create a simple implementation
            fetch(`get_category_data.php?id=${categoryId}`)
                .then(response => response.json())
                .then(category => {
                    document.getElementById('edit_category_id').value = categoryId;
                    document.getElementById('edit_name').value = category.name;
                    document.getElementById('edit_description').value = category.description || '';
                    new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
                })
                .catch(error => {
                    console.error('Error loading category data:', error);
                    // Fallback: show modal without pre-filled data
                    document.getElementById('edit_category_id').value = categoryId;
                    new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
                });
        }

        // Delete category
        function deleteCategory(categoryId, categoryName) {
            if (confirm(`¿ELIMINAR PERMANENTEMENTE la categoría "${categoryName}"?\n\nEsta acción no se puede deshacer.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_category">
                    <input type="hidden" name="category_id" value="${categoryId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Export categories
        function exportCategories() {
            if (confirm('¿Exportar lista de categorías a Excel?')) {
                window.location.href = 'export_categories.php';
            }
        }

        // Generate category report
        function generateCategoryReport() {
            window.location.href = 'reports.php#categories';
        }

        // Animate counters
        function animateCounters() {
            const counters = document.querySelectorAll('.stat-value');
            counters.forEach(counter => {
                const target = parseFloat(counter.textContent);
                let current = 0;
                const increment = target / 30;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        current = target;
                        clearInterval(timer);
                    }
                    if (target % 1 === 0) {
                        counter.textContent = Math.floor(current);
                    } else {
                        counter.textContent = current.toFixed(1);
                    }
                }, 50);
            });
        }

        // Validate merge form
        function initializeMergeModal() {
            const modal = document.getElementById('mergeCategoriesModal');
            if (modal) {
                modal.addEventListener('shown.bs.modal', function() {
                    const sourceSelect = document.getElementById('source_category');
                    const targetSelect = document.getElementById('target_category');
                    
                    if (sourceSelect && targetSelect) {
                        sourceSelect.addEventListener('change', function() {
                            const sourceValue = this.value;
                            const targetOptions = targetSelect.querySelectorAll('option');
                            
                            targetOptions.forEach(option => {
                                if (option.value === sourceValue) {
                                    option.disabled = true;
                                    option.style.color = '#ccc';
                                } else {
                                    option.disabled = false;
                                    option.style.color = '';
                                }
                            });
                            
                            if (targetSelect.value === sourceValue) {
                                targetSelect.value = '';
                            }
                        });
                    }
                });
            }
        }

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Initialize modals and animations
            initializeMergeModal();
            animateCounters();
        });
    </script>
</div>
</body>
</html>
