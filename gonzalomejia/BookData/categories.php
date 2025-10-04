<?php
require_once 'includes/config.php';
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// Get categories with book counts (only categories that have books)
$stmt = $pdo->prepare("
    SELECT c.*, 
           COUNT(b.id) as book_count,
           COUNT(CASE WHEN b.available_quantity > 0 THEN 1 END) as available_books
    FROM categories c
    INNER JOIN books b ON c.name = b.category
    GROUP BY c.id, c.name, c.description, c.created_at, c.updated_at
    HAVING book_count > 0
    ORDER BY c.name ASC
");
$stmt->execute();
$categories = $stmt->fetchAll();

// Get featured books from each category (max 3 per category)
$featured_books = [];
foreach ($categories as $category) {
    $stmt = $pdo->prepare("
        SELECT * FROM books 
        WHERE category = ? AND available_quantity > 0 
        ORDER BY RAND() 
        LIMIT 3
    ");
    $stmt->execute([$category['name']]);
    $featured_books[$category['name']] = $stmt->fetchAll();
}

$page_title = 'Explorar por Categorías';
?>

<?php include 'includes/header.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            <div class="manage-container">
                <!-- Page Header -->
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-tags me-3"></i>Explorar por Categorías
                    </h1>
                    <p class="text-muted">Descubre libros organizados por temas y géneros</p>
                </div>

                <!-- Search Bar -->
                <div class="content-card mb-4">
                    <div class="row">
                        <div class="col-md-8 mx-auto">
                            <div class="input-group input-group-lg">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" placeholder="Buscar en todas las categorías..." id="globalSearch">
                                <button class="btn btn-primary" type="button" onclick="performGlobalSearch()">
                                    <i class="fas fa-search me-2"></i>Buscar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Categories Grid -->
                <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $index => $category): ?>
                        <div class="content-card mb-4 category-section" 
                             data-category="<?php echo strtolower($category['name']); ?>">
                            
                            <!-- Category Header -->
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div class="category-header-info">
                                    <h3 class="section-title mb-1">
                                        <i class="fas fa-tag me-2"></i>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </h3>
                                    <p class="text-muted mb-0">
                                        <?php echo htmlspecialchars($category['description'] ?: 'Explora nuestra colección de ' . $category['name']); ?>
                                    </p>
                                    <small class="text-muted">
                                        <i class="fas fa-book me-1"></i>
                                        <?php echo $category['book_count']; ?> libros • 
                                        <i class="fas fa-check-circle me-1 text-success"></i>
                                        <?php echo $category['available_books']; ?> disponibles
                                    </small>
                                </div>
                                <div>
                                    <a href="dashboard.php?category=<?php echo urlencode($category['name']); ?>" 
                                       class="btn btn-outline-primary">
                                        <i class="fas fa-eye me-2"></i>Ver Todos
                                    </a>
                                </div>
                            </div>

                            <!-- Featured Books -->
                            <?php if (!empty($featured_books[$category['name']])): ?>
                                <div class="row">
                                    <?php foreach ($featured_books[$category['name']] as $book): ?>
                                        <div class="col-md-4 mb-3">
                                            <div class="book-card-horizontal">
                                                <div class="book-cover-small">
                                                    <img src="<?php echo $book['cover_image']; ?>" 
                                                         alt="<?php echo htmlspecialchars($book['title']); ?>"
                                                         onclick="viewBook(<?php echo $book['id']; ?>)"
                                                         style="cursor: pointer;">
                                                </div>
                                                <div class="book-info-small">
                                                    <h6 class="book-title-small" onclick="viewBook(<?php echo $book['id']; ?>)" style="cursor: pointer;">
                                                        <?php echo htmlspecialchars($book['title']); ?>
                                                    </h6>
                                                    <p class="book-author-small">
                                                        <i class="fas fa-user me-1"></i>
                                                        <?php echo htmlspecialchars($book['author']); ?>
                                                    </p>
                                                    <div class="book-meta-small">
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-check me-1"></i>
                                                            <?php echo $book['available_quantity']; ?> disponibles
                                                        </span>
                                                        <?php if ($book['publication_year']): ?>
                                                            <small class="text-muted ms-2">
                                                                <i class="fas fa-calendar me-1"></i>
                                                                <?php echo $book['publication_year']; ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="content-card text-center py-5">
                        <i class="fas fa-tags fa-4x text-muted mb-4"></i>
                        <h4 class="text-muted">No hay categorías disponibles</h4>
                        <p class="text-muted">Aún no se han creado categorías con libros.</p>
                        <a href="dashboard.php" class="btn btn-primary">
                            <i class="fas fa-books me-2"></i>Ver Todos los Libros
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Popular Categories Quick Access -->
                <?php if (!empty($categories)): ?>
                    <div class="content-card">
                        <h4 class="section-title mb-4">
                            <i class="fas fa-fire me-2"></i>Acceso Rápido a Categorías
                        </h4>
                        <div class="row">
                            <?php foreach (array_slice($categories, 0, 6) as $category): ?>
                                <div class="col-md-2 col-sm-4 col-6 mb-3">
                                    <a href="dashboard.php?category=<?php echo urlencode($category['name']); ?>" 
                                       class="category-quick-link">
                                        <div class="text-center">
                                            <div class="category-icon-large mb-2">
                                                <i class="fas fa-tag"></i>
                                            </div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($category['name']); ?></h6>
                                            <small class="text-muted"><?php echo $category['book_count']; ?> libros</small>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <style>
        /* Estilos específicos para vista de categorías de usuario */
        .category-section {
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s ease;
        }

        .category-section:hover {
            border-left-color: #667eea;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .book-card-horizontal {
            display: flex;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            transition: all 0.3s ease;
            height: 100%;
        }

        .book-card-horizontal:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }

        .book-cover-small {
            width: 60px;
            height: 80px;
            margin-right: 1rem;
            border-radius: 5px;
            overflow: hidden;
            flex-shrink: 0;
        }

        .book-cover-small img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .book-cover-small img:hover {
            transform: scale(1.05);
        }

        .book-info-small {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .book-title-small {
            font-size: 0.95rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .book-title-small:hover {
            color: var(--primary-color);
        }

        .book-author-small {
            font-size: 0.8rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }

        .book-meta-small {
            margin-top: auto;
        }

        .category-quick-link {
            display: block;
            padding: 1.5rem 1rem;
            background: white;
            border-radius: 12px;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
            border: 2px solid #e9ecef;
        }

        .category-quick-link:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(59, 113, 231, 0.3);
            border-color: var(--primary-color);
        }

        .category-icon-large {
            width: 50px;
            height: 50px;
            margin: 0 auto;
            background: linear-gradient(135deg, var(--primary-color), #667eea);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .category-quick-link:hover .category-icon-large {
            background: white;
            color: var(--primary-color);
        }

        .global-search-enhanced {
            background: linear-gradient(135deg, #f8f9fa, white);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        @media (max-width: 768px) {
            .book-card-horizontal {
                flex-direction: column;
                text-align: center;
            }

            .book-cover-small {
                width: 80px;
                height: 100px;
                margin: 0 auto 1rem auto;
            }

            .category-header-info h3 {
                font-size: 1.3rem;
            }
        }
    </style>

    <script>
        // View book details
        function viewBook(bookId) {
            window.location.href = `book_detail.php?id=${bookId}`;
        }

        // Global search functionality
        function performGlobalSearch() {
            const searchTerm = document.getElementById('globalSearch').value.trim();
            if (searchTerm) {
                window.location.href = `dashboard.php?search=${encodeURIComponent(searchTerm)}`;
            }
        }

        // Enter key search
        document.getElementById('globalSearch').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performGlobalSearch();
            }
        });

        // Smooth scrolling for category navigation
        function scrollToCategory(categoryName) {
            const element = document.querySelector(`[data-category="${categoryName.toLowerCase()}"]`);
            if (element) {
                element.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        // Initialize page with animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate sections on scroll
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            });

            document.querySelectorAll('.category-section').forEach(section => {
                section.style.opacity = '0';
                section.style.transform = 'translateY(30px)';
                section.style.transition = 'all 0.6s ease';
                observer.observe(section);
            });
        });
    </script>

<?php include 'includes/footer.php'; ?>
