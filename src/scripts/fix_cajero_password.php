<?php
require_once __DIR__ . '/../config/db.php';

$correo = 'cajero@prisma.com';
$nuevaPassword = 'cajero123';
$hash = password_hash($nuevaPassword, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("UPDATE usuarios SET contrasena = :pass WHERE correo = :correo");
    $stmt->execute([':pass' => $hash, ':correo' => $correo]);
    
    if ($stmt->rowCount() > 0) {
        echo "<h1>Éxito</h1>";
        echo "Contraseña de <b>$correo</b> actualizada a: <b>$nuevaPassword</b>";
    } else {
        echo "<h1>Aviso</h1>";
        echo "No se encontró el usuario $correo o la contraseña ya era esa.";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
