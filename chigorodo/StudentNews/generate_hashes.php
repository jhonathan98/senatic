<!DOCTYPE html>
<html>
<head>
    <title>Generar Hashes de Contraseñas</title>
</head>
<body>
    <h2>Hashes de Contraseñas para Student News</h2>
    
    <?php
    $passwords = [
        'admin123' => 'admin - Administrador',
        'redactor123' => 'maria_garcia - Usuario Regular/Redactor',
        'escritor123' => 'carlos_escritor - Redactor',
        'lector123' => 'ana_sofia - Lector'
    ];

    echo "<table border='1' style='border-collapse: collapse; padding: 10px;'>";
    echo "<tr><th>Usuario</th><th>Contraseña</th><th>Hash</th></tr>";
    
    foreach ($passwords as $password => $user) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        echo "<tr>";
        echo "<td>$user</td>";
        echo "<td><strong>$password</strong></td>";
        echo "<td style='font-family: monospace; font-size: 10px;'>$hash</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>Para copiar al SQL:</h3>";
    echo "<pre>";
    $hashes = [
        'admin123' => password_hash('admin123', PASSWORD_DEFAULT),
        'redactor123' => password_hash('redactor123', PASSWORD_DEFAULT),
        'escritor123' => password_hash('escritor123', PASSWORD_DEFAULT),
        'lector123' => password_hash('lector123', PASSWORD_DEFAULT)
    ];
    
    echo "-- Hash para admin123: " . $hashes['admin123'] . "\n";
    echo "-- Hash para redactor123: " . $hashes['redactor123'] . "\n";
    echo "-- Hash para escritor123: " . $hashes['escritor123'] . "\n";
    echo "-- Hash para lector123: " . $hashes['lector123'] . "\n";
    echo "</pre>";
    ?>
</body>
</html>
