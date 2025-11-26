<?php
require_once __DIR__ . '/../config/db.php';

$correos = ['admin_nuevo@prisma.com', 'admin@prisma.com', 'gerente@prisma.com', 'cajero@prisma.com'];

echo "<h1>Diagnóstico de Login</h1>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Correo</th><th>ID Usuario</th><th>ID Empleado</th><th>ID Rol</th><th>Nombre Rol (Raw)</th><th>Nombre Rol (Trimmed)</th><th>¿Es NULL?</th></tr>";

foreach ($correos as $correo) {
    $sql = "SELECT 
                u.id_usuario,
                u.id_empleado,
                e.id_rol,
                r.nombre_rol
            FROM usuarios u
            LEFT JOIN empleados e ON u.id_empleado = e.id_empleado
            LEFT JOIN roles r ON e.id_rol = r.id_rol
            WHERE u.correo = :correo";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':correo' => $correo]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "<tr>";
    echo "<td>$correo</td>";
    if ($user) {
        echo "<td>" . $user['id_usuario'] . "</td>";
        echo "<td>" . ($user['id_empleado'] ?? 'NULL') . "</td>";
        echo "<td>" . ($user['id_rol'] ?? 'NULL') . "</td>";
        echo "<td>'" . ($user['nombre_rol'] ?? 'NULL') . "'</td>";
        echo "<td>'" . trim($user['nombre_rol'] ?? '') . "'</td>";
        echo "<td>" . (is_null($user['nombre_rol']) ? 'SÍ' : 'NO') . "</td>";
    } else {
        echo "<td colspan='6'>Usuario no encontrado</td>";
    }
    echo "</tr>";
}
echo "</table>";
?>
