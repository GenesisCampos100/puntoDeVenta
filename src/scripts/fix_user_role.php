<?php
require_once __DIR__ . '/../config/db.php';

$correo = 'admin_nuevo@prisma.com';

echo "<h1>Diagnóstico y Reparación de Rol</h1>";

try {
    // 1. Buscar usuario y empleado
    $sql = "SELECT u.id_usuario, u.id_empleado, e.id_rol, r.nombre_rol 
            FROM usuarios u
            LEFT JOIN empleados e ON u.id_empleado = e.id_empleado
            LEFT JOIN roles r ON e.id_rol = r.id_rol
            WHERE u.correo = :correo";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':correo' => $correo]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        die("Usuario $correo no encontrado.");
    }

    echo "<b>Estado Actual:</b><br>";
    echo "ID Usuario: " . $data['id_usuario'] . "<br>";
    echo "ID Empleado: " . ($data['id_empleado'] ?? 'NULL') . "<br>";
    echo "ID Rol: " . ($data['id_rol'] ?? 'NULL') . "<br>";
    echo "Nombre Rol: " . ($data['nombre_rol'] ?? 'NULL') . "<br><br>";

    // 2. Reparar si es necesario
    if ($data['nombre_rol'] !== 'super_admin') {
        echo "⚠️ El rol no es 'super_admin'. Intentando reparar...<br>";

        if ($data['id_empleado']) {
            // Asegurar que el empleado tenga rol 1 (super_admin)
            $update = $pdo->prepare("UPDATE empleados SET id_rol = 1 WHERE id_empleado = ?");
            $update->execute([$data['id_empleado']]);
            echo "✅ Rol del empleado actualizado a 1 (super_admin).<br>";
        } else {
            echo "❌ El usuario no tiene empleado asignado. No se puede asignar rol.<br>";
        }
    } else {
        echo "✅ El usuario ya tiene el rol correcto (super_admin).<br>";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
