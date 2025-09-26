<?php
// test_session.php - Archivo temporal para verificar sesiones
session_start();

echo "<h2>Información de Sesión</h2>";
echo "<strong>user_id:</strong> " . ($_SESSION['user_id'] ?? 'No definido') . "<br>";
echo "<strong>user_rol:</strong> " . ($_SESSION['user_rol'] ?? 'No definido') . "<br>";
echo "<strong>user_nombre:</strong> " . ($_SESSION['user_nombre'] ?? 'No definido') . "<br>";
echo "<strong>user_email:</strong> " . ($_SESSION['user_email'] ?? 'No definido') . "<br>";

echo "<h3>Todas las variables de sesión:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Verificar si es admin
if (isset($_SESSION['user_id']) && $_SESSION['user_rol'] === 'admin') {
    echo "<p style='color: green;'>✓ Acceso de administrador correcto</p>";
    echo "<a href='views/admin/edit_resource.php?id=1'>Probar edit_resource.php</a><br>";
    echo "<a href='views/admin/manage_resources.php'>Probar manage_resources.php</a>";
} else {
    echo "<p style='color: red;'>✗ Sin acceso de administrador</p>";
    echo "<a href='index.php'>Ir a login</a>";
}
?>
