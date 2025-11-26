<?php
require_once __DIR__ . '/../config/db.php';

echo "<h1>Reparación Masiva de Roles</h1>";

$fixes = [
    'admin@prisma.com' => 1,   // super_admin
    'gerente@prisma.com' => 2, // gerente
    'cajero@prisma.com' => 3   // cajero
];

foreach ($fixes as $correo => $id_rol) {
    // Buscar empleado del usuario
    $stmt = $pdo->prepare("SELECT id_empleado FROM usuarios WHERE correo = ?");
    $stmt->execute([$correo]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['id_empleado']) {
        // Actualizar rol del empleado
        $update = $pdo->prepare("UPDATE empleados SET id_rol = ? WHERE id_empleado = ?");
        $update->execute([$id_rol, $user['id_empleado']]);
        echo "✅ Usuario $correo actualizado a rol ID $id_rol.<br>";
    } else {
        echo "⚠️ Usuario $correo no encontrado o sin empleado asignado.<br>";
    }
}
?>
