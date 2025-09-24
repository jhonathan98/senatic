<?php
echo "Generando hashes reales:\n\n";

$admin_hash = password_hash('admin123', PASSWORD_DEFAULT);
$redactor_hash = password_hash('redactor123', PASSWORD_DEFAULT);
$escritor_hash = password_hash('escritor123', PASSWORD_DEFAULT);
$lector_hash = password_hash('lector123', PASSWORD_DEFAULT);

echo "admin123: $admin_hash\n";
echo "redactor123: $redactor_hash\n";
echo "escritor123: $escritor_hash\n";
echo "lector123: $lector_hash\n";
?>
