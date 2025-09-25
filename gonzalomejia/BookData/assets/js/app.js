// BookData - JavaScript Principal

// Utility functions
const BookData = {
    // Initialize common functionality
    init: function() {
        this.setupTooltips();
        this.setupAlerts();
        this.setupFormValidation();
        this.setupAnimations();
    },

    // Setup Bootstrap tooltips
    setupTooltips: function() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    },

    // Auto-hide alerts after 5 seconds
    setupAlerts: function() {
        const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(alert => {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });
    },

    // Form validation enhancements
    setupFormValidation: function() {
        const forms = document.querySelectorAll('.needs-validation');
        forms.forEach(form => {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });
    },

    // Setup entrance animations
    setupAnimations: function() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        const animatedElements = document.querySelectorAll('.animate-on-scroll');
        animatedElements.forEach(el => observer.observe(el));
    },

    // Confirm dialog
    confirm: function(message, callback) {
        if (confirm(message)) {
            callback();
        }
    },

    // Show loading state
    showLoading: function(element) {
        const originalText = element.innerHTML;
        element.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Cargando...';
        element.disabled = true;
        
        return function() {
            element.innerHTML = originalText;
            element.disabled = false;
        };
    },

    // Format date for display
    formatDate: function(dateString) {
        const options = { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        };
        return new Date(dateString).toLocaleDateString('es-ES', options);
    },

    // Validate email format
    isValidEmail: function(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },

    // Debounce function for search
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};

// Book-specific functions
const BookFunctions = {
    // Filter books by category and search
    filterBooks: function(category = 'all', searchText = '') {
        const bookCards = document.querySelectorAll('.book-card');
        
        bookCards.forEach(card => {
            const cardCategory = card.dataset.category || '';
            const cardTitle = card.querySelector('.card-title')?.textContent.toLowerCase() || '';
            const cardAuthor = card.querySelector('.book-info')?.textContent.toLowerCase() || '';
            
            const matchesCategory = category === 'all' || cardCategory === category;
            const matchesSearch = searchText === '' || 
                                cardTitle.includes(searchText.toLowerCase()) ||
                                cardAuthor.includes(searchText.toLowerCase());

            if (matchesCategory && matchesSearch) {
                card.style.display = 'block';
                card.classList.add('fade-in');
            } else {
                card.style.display = 'none';
                card.classList.remove('fade-in');
            }
        });
    },

    // Handle book card hover effects
    setupCardHoverEffects: function() {
        const bookCards = document.querySelectorAll('.book-card');
        
        bookCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px)';
                this.style.transition = 'transform 0.3s ease, box-shadow 0.3s ease';
                this.style.boxShadow = '0 12px 30px rgba(0,0,0,0.2)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 4px 10px rgba(0,0,0,0.1)';
            });
        });
    },

    // Animate book cards entrance
    animateBookCards: function() {
        const bookCards = document.querySelectorAll('.book-card');
        
        bookCards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            
            setTimeout(() => {
                card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }
};

// Admin functions
const AdminFunctions = {
    // Delete confirmation
    confirmDelete: function(type, id, name) {
        const message = `¿Estás seguro de que quieres eliminar ${type} "${name}"? Esta acción no se puede deshacer.`;
        return BookData.confirm(message, () => {
            window.location.href = `delete_${type}.php?id=${id}`;
        });
    },

    // Toggle user status
    toggleUserStatus: function(userId, currentStatus) {
        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
        const message = `¿Cambiar el estado del usuario a ${newStatus}?`;
        
        BookData.confirm(message, () => {
            window.location.href = `toggle_user_status.php?id=${userId}&status=${newStatus}`;
        });
    },

    // Setup statistics animations
    animateStatistics: function() {
        const statValues = document.querySelectorAll('.stat-value');
        
        statValues.forEach(stat => {
            const finalValue = parseInt(stat.textContent);
            let currentValue = 0;
            const increment = finalValue / 20;
            const timer = setInterval(() => {
                currentValue += increment;
                if (currentValue >= finalValue) {
                    currentValue = finalValue;
                    clearInterval(timer);
                }
                stat.textContent = Math.floor(currentValue);
            }, 50);
        });
    }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    BookData.init();
    
    // Initialize book-specific functions if on book pages
    if (document.querySelector('.book-card')) {
        BookFunctions.setupCardHoverEffects();
        BookFunctions.animateBookCards();
    }
    
    // Initialize admin functions if on admin pages
    if (document.querySelector('.stat-card')) {
        AdminFunctions.animateStatistics();
    }
});

// Export to global scope
window.BookData = BookData;
window.BookFunctions = BookFunctions;
window.AdminFunctions = AdminFunctions;
