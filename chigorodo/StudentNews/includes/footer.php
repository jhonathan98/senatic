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
                <h5 class="fw-bold mb-3">
                    <i class="bi bi-newspaper"></i> STUDENT NEWS
                </h5>
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
                </ul>
            </div>
            
            <div class="col-md-4">
                <h6 class="fw-bold mb-3">Contacto</h6>
                <ul class="list-unstyled text-muted">
                    <li class="mb-2">
                        <i class="bi bi-envelope-fill"></i> contacto@studentnews.com
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-geo-alt-fill"></i> Institución Educativa
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-clock-fill"></i> Lun - Vie: 7:00 AM - 5:00 PM
                    </li>
                </ul>
            </div>
        </div>
        
        <hr class="my-4 border-secondary">
        
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="mb-0 text-muted">
                    © <?= $anio_actual ?> Student News. Todos los derechos reservados.
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <small class="text-muted">
                    Hecho con <i class="bi bi-heart-fill text-danger"></i> por estudiantes para estudiantes
                </small>
            </div>
        </div>
    </div>
</footer>
