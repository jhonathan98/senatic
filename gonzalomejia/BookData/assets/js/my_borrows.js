// BookData - My Borrows JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // Confirmation dialogs for book actions
    const returnForms = document.querySelectorAll('form[name="return_book"]');
    returnForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (!confirm('¿Estás seguro de que quieres devolver este libro?')) {
                e.preventDefault();
            }
        });
    });

    const renewForms = document.querySelectorAll('form[name="renew_book"]');
    renewForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (!confirm('¿Deseas renovar este libro por 14 días más?')) {
                e.preventDefault();
            }
        });
    });

    // Add loading state to buttons
    const actionButtons = document.querySelectorAll('.btn-success-custom, .btn-warning-custom');
    actionButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Procesando...';
            this.disabled = true;
            
            // Re-enable button after 3 seconds if form doesn't submit
            setTimeout(() => {
                this.innerHTML = originalText;
                this.disabled = false;
            }, 3000);
        });
    });

    // Filter functionality for history
    const filterButtons = document.querySelectorAll('[data-filter]');
    const bookCards = document.querySelectorAll('.book-card');
    
    filterButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const filter = this.getAttribute('data-filter');
            
            // Remove active class from all buttons
            filterButtons.forEach(btn => btn.classList.remove('active'));
            // Add active class to clicked button
            this.classList.add('active');
            
            // Filter cards
            bookCards.forEach(function(card) {
                if (filter === 'all' || card.classList.contains(filter)) {
                    card.style.display = 'block';
                    card.style.animation = 'fadeIn 0.3s ease';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    // Tooltip initialization
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Search functionality
    const searchInput = document.getElementById('borrowSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            bookCards.forEach(function(card) {
                const title = card.querySelector('.card-title').textContent.toLowerCase();
                const author = card.querySelector('.text-muted').textContent.toLowerCase();
                
                if (title.includes(searchTerm) || author.includes(searchTerm)) {
                    card.style.display = 'block';
                    card.style.animation = 'fadeIn 0.3s ease';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }

    // Statistics counter animation
    const statNumbers = document.querySelectorAll('.stats-number');
    const animateCounter = (element, target) => {
        let current = 0;
        const increment = target / 30;
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            element.textContent = Math.floor(current);
        }, 50);
    };

    // Intersection Observer for counter animation
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const target = parseInt(entry.target.textContent);
                animateCounter(entry.target, target);
                observer.unobserve(entry.target);
            }
        });
    });

    statNumbers.forEach(stat => {
        observer.observe(stat);
    });

    // Add smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });

    // Dark mode toggle (if implemented)
    const darkModeToggle = document.getElementById('darkModeToggle');
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
        });

        // Load dark mode preference
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
        }
    }
});

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    @keyframes slideIn {
        from { transform: translateX(-100%); }
        to { transform: translateX(0); }
    }
    
    .book-card {
        animation: fadeIn 0.5s ease forwards;
    }
    
    .stats-card {
        animation: slideIn 0.6s ease forwards;
    }
    
    .book-card:hover {
        animation: none;
    }
`;
document.head.appendChild(style);
