<?php
/**
 * FOOTER.PHP - Pie de página común para Student News
 */

// Obtener año actual para copyright
$anio_actual = date('Y');
?>

<footer class="bg-dark text-light py-4 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <h5 class="fw-bold mb-3">STUDENT NEWS</h5>
                <p class="text-muted">
                    Periódico digital estudiantil donde compartimos noticias, eventos y actividades de nuestra comunidad educativa.
                </p>
            </div>
            
            <div class="col-md-4">
                <h6 class="fw-bold mb-3">Enlaces Rápidos</h6>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <a href="../../index.php" class="text-decoration-none text-muted">
                            <i class="bi bi-house"></i> Inicio
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="../publicas/participa.php" class="text-decoration-none text-muted">
                            <i class="bi bi-lightbulb"></i> Participa
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="../publicas/contacto.php" class="text-decoration-none text-muted">
                            <i class="bi bi-envelope"></i> Contacto
                        </a>
                        </li>
                        <li style="margin-bottom: 0.5rem;">
                            <a href="<?php echo $base_url ?? '../'; ?>vistas/publicas/galeria.php" style="color: #bdc3c7; text-decoration: none;">Galería</a>
                        </li>
                        <li style="margin-bottom: 0.5rem;">
                            <a href="<?php echo $base_url ?? '../'; ?>vistas/publicas/calendario.php" style="color: #bdc3c7; text-decoration: none;">Eventos</a>
                        </li>
                    </ul>
                </div>
                
                <div class="col">
                    <h6 style="color: white; margin-bottom: 1rem;">Participa</h6>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 0.5rem;">
                            <a href="<?php echo $base_url ?? '../'; ?>vistas/publicas/participa.php" style="color: #bdc3c7; text-decoration: none;">Enviar Ideas</a>
                        </li>
                        <li style="margin-bottom: 0.5rem;">
                            <a href="<?php echo $base_url ?? '../'; ?>vistas/publicas/contacto.php" style="color: #bdc3c7; text-decoration: none;">Contacto</a>
                        </li>
                        <?php if (!isset($_SESSION['usuario_id'])): ?>
                        <li style="margin-bottom: 0.5rem;">
                            <a href="<?php echo $base_url ?? '../'; ?>vistas/publicas/registro.php" style="color: #bdc3c7; text-decoration: none;">Registrarse</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="col">
                    <h6 style="color: white; margin-bottom: 1rem;">Síguenos</h6>
                    <div style="display: flex; gap: 1rem;">
                        <a href="#" style="color: #bdc3c7; font-size: 1.5rem; text-decoration: none;" title="Facebook">📘</a>
                        <a href="#" style="color: #bdc3c7; font-size: 1.5rem; text-decoration: none;" title="Instagram">📷</a>
                        <a href="#" style="color: #bdc3c7; font-size: 1.5rem; text-decoration: none;" title="Twitter">🐦</a>
                        <a href="#" style="color: #bdc3c7; font-size: 1.5rem; text-decoration: none;" title="YouTube">📺</a>
                    </div>
                    
                    <div style="margin-top: 1rem;">
                        <p style="color: #bdc3c7; font-size: 0.9rem; margin: 0;">
                            📧 contacto@estudiantes.edu<br>
                            📞 (123) 456-7890
                        </p>
                    </div>
                </div>
            </div>
            
            <hr style="border: none; border-top: 1px solid #34495e; margin: 2rem 0 1rem 0;">
            
            <div class="d-flex justify-content-between align-items-center" style="flex-wrap: wrap;">
                <p style="color: #bdc3c7; margin: 0; font-size: 0.9rem;">
                    &copy; <?php echo $anio_actual; ?> <?php echo $sitio_nombre; ?>. Todos los derechos reservados.
                </p>
                
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <a href="#" style="color: #bdc3c7; text-decoration: none; font-size: 0.9rem;">Política de Privacidad</a>
                    <a href="#" style="color: #bdc3c7; text-decoration: none; font-size: 0.9rem;">Términos de Uso</a>
                    <a href="#" style="color: #bdc3c7; text-decoration: none; font-size: 0.9rem;">Ayuda</a>
                </div>
            </div>
            
            <!-- Información adicional -->
            <div style="text-align: center; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #34495e;">
                <p style="color: #95a5a6; font-size: 0.8rem; margin: 0;">
                    Desarrollado con ❤️ por estudiantes para estudiantes
                    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                        | <a href="<?php echo $base_url ?? '../'; ?>vistas/privadas/admin/configuraciones.php" style="color: #95a5a6;">Panel Admin</a>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </footer>

    <!-- Botón volver arriba -->
    <button id="btnVolverArriba" onclick="volverArriba()" 
            style="display: none; position: fixed; bottom: 20px; right: 20px; z-index: 999; background: var(--primary-color); color: white; border: none; border-radius: 50%; width: 50px; height: 50px; cursor: pointer; box-shadow: 0 2px 10px rgba(0,0,0,0.3); transition: opacity 0.3s;">
        ⬆️
    </button>

    <!-- JavaScript -->
    <script src="<?php echo $base_url ?? '../'; ?>assets/js/scripts.js"></script>
    
    <?php if (isset($js_adicional)): ?>
        <?php foreach ($js_adicional as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <script>
        // Mostrar/ocultar botón "volver arriba"
        window.onscroll = function() {
            const btn = document.getElementById('btnVolverArriba');
            if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
                btn.style.display = 'block';
            } else {
                btn.style.display = 'none';
            }
        };

        // Función volver arriba
        function volverArriba() {
            document.body.scrollTop = 0; // Safari
            document.documentElement.scrollTop = 0; // Chrome, Firefox, IE y Opera
        }

        // Animación suave para enlaces internos
        document.addEventListener('DOMContentLoaded', function() {
            const enlaces = document.querySelectorAll('a[href^="#"]');
            
            enlaces.forEach(enlace => {
                enlace.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const targetId = this.getAttribute('href').substring(1);
                    const targetElement = document.getElementById(targetId);
                    
                    if (targetElement) {
                        targetElement.scrollIntoView({
                            behavior: 'smooth'
                        });
                    }
                });
            });
        });

        // Configuración global para AJAX
        window.addEventListener('load', function() {
            // Configurar CSRF token para todas las peticiones AJAX
            const csrfToken = '<?php echo generarCSRFToken(); ?>';
            
            // Interceptar formularios AJAX
            document.addEventListener('submit', function(e) {
                const form = e.target;
                if (form.classList.contains('ajax-form')) {
                    e.preventDefault();
                    // Lógica para formularios AJAX aquí
                }
            });
        });
    </script>

    <!-- Código adicional de seguimiento/analytics si es necesario -->
    <?php if (obtenerConfiguracion('google_analytics_id')): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo obtenerConfiguracion('google_analytics_id'); ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?php echo obtenerConfiguracion('google_analytics_id'); ?>');
    </script>
    <?php endif; ?>

</body>
</html>
