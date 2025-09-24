<?php
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    header("Location: dashboard.php?section=help");
    exit;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-question-circle text-primary"></i> Centro de Ayuda</h2>
    <small class="text-muted">Guía de uso del sistema StudentGPS</small>
</div>

<div class="row">
    <div class="col-lg-3 mb-4">
        <!-- Navegación de ayuda -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-list-ul"></i> Temas de Ayuda</h6>
            </div>
            <div class="list-group list-group-flush">
                <a href="#getting-started" class="list-group-item list-group-item-action" data-bs-toggle="collapse" data-bs-target="#getting-started">
                    <i class="bi bi-play-circle me-2"></i> Primeros Pasos
                </a>
                <a href="#roles" class="list-group-item list-group-item-action" data-bs-toggle="collapse" data-bs-target="#roles">
                    <i class="bi bi-people me-2"></i> Roles y Permisos
                </a>
                <a href="#attendance" class="list-group-item list-group-item-action" data-bs-toggle="collapse" data-bs-target="#attendance">
                    <i class="bi bi-calendar-check me-2"></i> Control de Asistencia
                </a>
                <a href="#gps" class="list-group-item list-group-item-action" data-bs-toggle="collapse" data-bs-target="#gps">
                    <i class="bi bi-geo-alt me-2"></i> Sistema GPS
                </a>
                <a href="#reports" class="list-group-item list-group-item-action" data-bs-toggle="collapse" data-bs-target="#reports">
                    <i class="bi bi-graph-up me-2"></i> Reportes
                </a>
                <a href="#faq" class="list-group-item list-group-item-action" data-bs-toggle="collapse" data-bs-target="#faq">
                    <i class="bi bi-patch-question me-2"></i> Preguntas Frecuentes
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-lg-9">
        <!-- Contenido de ayuda -->
        <div class="accordion" id="helpAccordion">
            
            <!-- Primeros Pasos -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingGettingStarted">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#getting-started" aria-expanded="true">
                        <i class="bi bi-play-circle me-2"></i> Primeros Pasos
                    </button>
                </h2>
                <div id="getting-started" class="accordion-collapse collapse show" data-bs-parent="#helpAccordion">
                    <div class="accordion-body">
                        <h5>Bienvenido a StudentGPS</h5>
                        <p>StudentGPS es un sistema integral para el monitoreo y seguimiento de estudiantes. Aquí te mostramos cómo comenzar:</p>
                        
                        <h6>1. Primer Acceso</h6>
                        <ul>
                            <li>Ingresa con tu usuario y contraseña proporcionados</li>
                            <li>Cambia tu contraseña desde <strong>Mi Perfil</strong></li>
                            <li>Completa tu información personal</li>
                        </ul>
                        
                        <h6>2. Navegación</h6>
                        <ul>
                            <li>Usa el menú lateral para acceder a las diferentes secciones</li>
                            <li>El <strong>Panel</strong> muestra un resumen de tu actividad</li>
                            <li>Cada función tiene su propio icono descriptivo</li>
                        </ul>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-lightbulb"></i>
                            <strong>Consejo:</strong> Explora todas las secciones disponibles según tu rol para familiarizarte con el sistema.
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Roles y Permisos -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingRoles">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#roles">
                        <i class="bi bi-people me-2"></i> Roles y Permisos
                    </button>
                </h2>
                <div id="roles" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                    <div class="accordion-body">
                        <h5>Tipos de Usuario</h5>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="card border-primary">
                                    <div class="card-header bg-primary text-white">
                                        <i class="bi bi-gear-fill"></i> Administrador
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-unstyled">
                                            <li><i class="bi bi-check-circle text-success"></i> Registrar profesores</li>
                                            <li><i class="bi bi-check-circle text-success"></i> Asignar estudiantes</li>
                                            <li><i class="bi bi-check-circle text-success"></i> Ver todos los reportes</li>
                                            <li><i class="bi bi-check-circle text-success"></i> Acceso completo</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <div class="card border-success">
                                    <div class="card-header bg-success text-white">
                                        <i class="bi bi-mortarboard-fill"></i> Profesor
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-unstyled">
                                            <li><i class="bi bi-check-circle text-success"></i> Registrar asistencia</li>
                                            <li><i class="bi bi-check-circle text-success"></i> Ver estudiantes asignados</li>
                                            <li><i class="bi bi-check-circle text-success"></i> Generar reportes</li>
                                            <li><i class="bi bi-check-circle text-success"></i> Consultar ubicaciones</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <div class="card border-info">
                                    <div class="card-header bg-info text-white">
                                        <i class="bi bi-person-hearts"></i> Acudiente
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-unstyled">
                                            <li><i class="bi bi-check-circle text-success"></i> Registrar hijos</li>
                                            <li><i class="bi bi-check-circle text-success"></i> Ver ubicación GPS</li>
                                            <li><i class="bi bi-check-circle text-success"></i> Consultar asistencia</li>
                                            <li><i class="bi bi-check-circle text-success"></i> Actualizar perfil</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Control de Asistencia -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingAttendance">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#attendance">
                        <i class="bi bi-calendar-check me-2"></i> Control de Asistencia
                    </button>
                </h2>
                <div id="attendance" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                    <div class="accordion-body">
                        <h5>Gestión de Asistencia</h5>
                        
                        <h6>Para Profesores:</h6>
                        <ol>
                            <li>Ve a <strong>Registrar Asistencia</strong></li>
                            <li>Selecciona la fecha (por defecto es hoy)</li>
                            <li>Marca el estado de cada estudiante:
                                <ul>
                                    <li><span class="badge bg-success">Asistió</span> - Estudiante presente</li>
                                    <li><span class="badge bg-danger">No asistió</span> - Falta injustificada</li>
                                    <li><span class="badge bg-warning">Tarde</span> - Llegó tarde</li>
                                    <li><span class="badge bg-info">Excusado</span> - Falta justificada</li>
                                </ul>
                            </li>
                            <li>Agrega notas si es necesario</li>
                            <li>Guarda los cambios</li>
                        </ol>
                        
                        <h6>Consultar Asistencia:</h6>
                        <ul>
                            <li>Usa <strong>Consultar Asistencia</strong> para ver registros por fecha</li>
                            <li>Filtra por grado (solo administradores)</li>
                            <li>Exporta los datos si es necesario</li>
                        </ul>
                        
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Importante:</strong> La asistencia debe registrarse diariamente para mantener un control preciso.
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sistema GPS -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingGPS">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#gps">
                        <i class="bi bi-geo-alt me-2"></i> Sistema GPS
                    </button>
                </h2>
                <div id="gps" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                    <div class="accordion-body">
                        <h5>Monitoreo de Ubicación</h5>
                        
                        <h6>¿Cómo funciona?</h6>
                        <p>El sistema GPS permite rastrear la ubicación de los estudiantes en tiempo real a través de dispositivos móviles.</p>
                        
                        <h6>Para ver ubicaciones:</h6>
                        <ol>
                            <li>Ve a <strong>Ubicación en Tiempo Real</strong></li>
                            <li>Selecciona el estudiante (si tienes varios)</li>
                            <li>El mapa mostrará la última ubicación conocida</li>
                            <li>La información incluye coordenadas y timestamp</li>
                        </ol>
                        
                        <h6>Estados del GPS:</h6>
                        <ul>
                            <li><span class="text-success"><i class="bi bi-circle-fill"></i></span> Ubicación reciente (menos de 5 min)</li>
                            <li><span class="text-warning"><i class="bi bi-circle-fill"></i></span> Ubicación antigua (5-30 min)</li>
                            <li><span class="text-danger"><i class="bi bi-circle-fill"></i></span> Sin ubicación (más de 30 min)</li>
                        </ul>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-phone"></i>
                            <strong>Nota:</strong> Para que funcione el GPS, el estudiante debe tener un dispositivo móvil con la aplicación instalada y permisos de ubicación activados.
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Reportes -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingReports">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#reports">
                        <i class="bi bi-graph-up me-2"></i> Reportes
                    </button>
                </h2>
                <div id="reports" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                    <div class="accordion-body">
                        <h5>Generación de Reportes</h5>
                        
                        <h6>Tipos de Reporte:</h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">Resumen</h6>
                                        <p class="card-text">Estadísticas diarias de asistencia agrupadas por fecha.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">Detallado</h6>
                                        <p class="card-text">Lista completa de todos los registros de asistencia.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">Por Estudiante</h6>
                                        <p class="card-text">Estadísticas individuales con porcentaje de asistencia.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <h6>Generar un Reporte:</h6>
                        <ol>
                            <li>Ve a <strong>Reportes</strong></li>
                            <li>Selecciona el rango de fechas</li>
                            <li>Elige el tipo de reporte</li>
                            <li>Filtra por grado (opcional)</li>
                            <li>Haz clic en <strong>Generar</strong></li>
                            <li>Usa <strong>Exportar</strong> para descargar en CSV</li>
                        </ol>
                    </div>
                </div>
            </div>
            
            <!-- FAQ -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingFAQ">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq">
                        <i class="bi bi-patch-question me-2"></i> Preguntas Frecuentes
                    </button>
                </h2>
                <div id="faq" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                    <div class="accordion-body">
                        <h5>Preguntas Frecuentes</h5>
                        
                        <div class="accordion" id="faqAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                        ¿Cómo cambio mi contraseña?
                                    </button>
                                </h2>
                                <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Ve a <strong>Mi Perfil</strong>, luego a la sección "Cambiar Contraseña". Necesitarás tu contraseña actual y la nueva contraseña (mínimo 6 caracteres).
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                        ¿Por qué no veo la ubicación de mi hijo?
                                    </button>
                                </h2>
                                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Puede ser porque: 1) El dispositivo no tiene GPS activado, 2) No hay conexión a internet, 3) La aplicación no está instalada o 4) Los permisos de ubicación están desactivados.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                        ¿Puedo corregir una asistencia ya registrada?
                                    </button>
                                </h2>
                                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Sí, los profesores pueden volver a registrar la asistencia del mismo día y el sistema actualizará automáticamente el registro anterior.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                        ¿Cómo registro un nuevo estudiante?
                                    </button>
                                </h2>
                                <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Los acudientes pueden ir a <strong>Registrar Estudiante</strong> y completar el formulario. Los administradores también pueden hacerlo desde su panel.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                        ¿Qué hacer si olvido mi contraseña?
                                    </button>
                                </h2>
                                <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Contacta al administrador del sistema. Él puede generar una nueva contraseña temporal para tu usuario.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Información de contacto -->
        <div class="card mt-4">
            <div class="card-body text-center">
                <h5><i class="bi bi-headset"></i> ¿Necesitas más ayuda?</h5>
                <p class="text-muted">Si no encuentras la respuesta a tu pregunta, contacta al soporte técnico.</p>
                
                <div class="row">
                    <div class="col-md-4">
                        <i class="bi bi-envelope-fill text-primary" style="font-size: 2rem;"></i>
                        <p class="mt-2 mb-0"><strong>Email</strong></p>
                        <small class="text-muted">soporte@studentgps.edu.co</small>
                    </div>
                    <div class="col-md-4">
                        <i class="bi bi-telephone-fill text-success" style="font-size: 2rem;"></i>
                        <p class="mt-2 mb-0"><strong>Teléfono</strong></p>
                        <small class="text-muted">300 123 4567</small>
                    </div>
                    <div class="col-md-4">
                        <i class="bi bi-clock-fill text-warning" style="font-size: 2rem;"></i>
                        <p class="mt-2 mb-0"><strong>Horario</strong></p>
                        <small class="text-muted">Lun-Vie 8:00-17:00</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
