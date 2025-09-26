    <footer class="footer mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5><i class="bi bi-newspaper"></i> Periódico Digital</h5>
                    <p class="mb-0">Colegio Gonzalo Mejía</p>
                    <p>Mantente informado con las últimas noticias de nuestra comunidad educativa.</p>
                </div>
                <div class="col-md-4">
                    <h5>Enlaces Útiles</h5>
                    <ul class="list-unstyled">
                        <li><a href="../index.php" class="text-light">Inicio</a></li>
                        <li><a href="noticias_todas.php" class="text-light">Todas las Noticias</a></li>
                        <li><a href="calendario.php" class="text-light">Calendario de Eventos</a></li>
                        <li><a href="enviar_opinion.php" class="text-light">Enviar Opinión</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contacto</h5>
                    <p><i class="bi bi-geo-alt"></i> Dirección del Colegio</p>
                    <p><i class="bi bi-telephone"></i> +57 (xxx) xxx-xxxx</p>
                    <p><i class="bi bi-envelope"></i> info@colegiogonzalomejia.edu.co</p>
                    <div class="mt-3">
                        <a href="#" class="text-light me-3"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-light me-3"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="text-light me-3"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="text-light"><i class="bi bi-youtube"></i></a>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> Colegio Gonzalo Mejía. Todos los derechos reservados.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <small>Desarrollado por estudiantes del colegio</small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Scripts personalizados -->
    <script>
        // Funcionalidad para reacciones y guardado
        function toggleReaction(noticiaId, tipo) {
            // Aquí iría la lógica AJAX para manejar reacciones
            alert('Funcionalidad de reacciones - Noticia: ' + noticiaId + ', Tipo: ' + tipo);
        }
        
        function saveArticle(noticiaId) {
            // Aquí iría la lógica AJAX para guardar artículo
            alert('Artículo guardado - ID: ' + noticiaId);
        }
        
        function shareArticle(noticiaId) {
            // Funcionalidad básica de compartir
            if (navigator.share) {
                navigator.share({
                    title: 'Artículo del Periódico Digital',
                    url: window.location.href
                });
            } else {
                // Fallback para navegadores que no soportan Web Share API
                alert('Compartir artículo - ID: ' + noticiaId);
            }
        }
    </script>
</body>
</html>