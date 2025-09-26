<?php
// test_make_reservation.php - Archivo de prueba para verificar funcionalidad
session_start();

// Simular sesión de usuario para prueba
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_nombre'] = 'Usuario de Prueba';
    $_SESSION['user_rol'] = 'usuario';
    $_SESSION['user_email'] = 'test@test.com';
}

echo "<h2>Prueba de Funcionalidad - make_reservation.php</h2>";

try {
    // Probar inclusión de controladores
    require_once 'controllers/ResourceController.php';
    require_once 'controllers/ReservationController.php';
    require_once 'models/Department.php';
    
    echo "<p style='color: green;'>✓ Controladores cargados correctamente</p>";
    
    // Probar instanciación
    $resourceController = new ResourceController();
    $reservationController = new ReservationController();
    $department = new Department();
    
    echo "<p style='color: green;'>✓ Objetos instanciados correctamente</p>";
    
    // Probar métodos
    $resources = $resourceController->getActive();
    echo "<p style='color: green;'>✓ getActive() funciona</p>";
    
    $resource_types = $resourceController->getResourceTypes();
    echo "<p style='color: green;'>✓ getResourceTypes() funciona</p>";
    
    $departments = $department->read();
    echo "<p style='color: green;'>✓ Department->read() funciona</p>";
    
    echo "<h3>Todo funciona correctamente. La página debe cargar sin problemas.</h3>";
    
    echo "<a href='views/user/make_reservation.php' class='btn btn-primary'>Probar make_reservation.php</a>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
