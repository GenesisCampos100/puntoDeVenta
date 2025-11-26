<?php
require_once __DIR__ . '/../config/db.php';

// Función para generar ID aleatorio
function generarId($prefix) {
    return $prefix . rand(1000, 9999);
}

// Datos de los usuarios a crear
$usuarios = [
    [
        'rol_id' => 1, // super_admin
        'nombre' => 'Admin Nuevo',
        'correo' => 'admin_nuevo@prisma.com',
        'pass' => 'admin123',
        'prefix' => 'ADM'
    ],
    [
        'rol_id' => 2, // gerente
        'nombre' => 'Gerente Nuevo',
        'correo' => 'gerente_nuevo@prisma.com',
        'pass' => 'gerente123',
        'prefix' => 'GER'
    ],
    [
        'rol_id' => 3, // cajero
        'nombre' => 'Cajero Nuevo',
        'correo' => 'cajero_nuevo@prisma.com',
        'pass' => 'cajero123',
        'prefix' => 'CAJ'
    ]
];

echo "<h1>Creando Usuarios...</h1>";

foreach ($usuarios as $u) {
    try {
        // 1. Crear Empleado
        $idEmpleado = generarId($u['prefix']);
        
        // Verificar si existe el ID (simple check)
        $check = $pdo->prepare("SELECT id_empleado FROM empleados WHERE id_empleado = ?");
        $check->execute([$idEmpleado]);
        if ($check->rowCount() > 0) {
            $idEmpleado = generarId($u['prefix']) . 'X'; // Reintento simple
        }

        $sqlEmp = "INSERT INTO empleados (id_empleado, nombre, apellido_paterno, id_rol, estatus, fecha) 
                   VALUES (?, ?, 'Test', ?, 1, NOW())";
        $stmtEmp = $pdo->prepare($sqlEmp);
        $stmtEmp->execute([$idEmpleado, $u['nombre'], $u['rol_id']]);

        // 2. Crear Usuario
        $hash = password_hash($u['pass'], PASSWORD_DEFAULT);
        
        // Eliminar usuario si ya existe el correo para evitar duplicados
        $delUser = $pdo->prepare("DELETE FROM usuarios WHERE correo = ?");
        $delUser->execute([$u['correo']]);

        $sqlUser = "INSERT INTO usuarios (correo, contrasena, id_empleado) VALUES (?, ?, ?)";
        $stmtUser = $pdo->prepare($sqlUser);
        $stmtUser->execute([$u['correo'], $hash, $idEmpleado]);

        echo "<div style='color: green; margin-bottom: 10px;'>";
        echo "✅ Creado: <b>{$u['nombre']}</b><br>";
        echo "📧 Correo: {$u['correo']}<br>";
        echo "🔑 Pass: {$u['pass']}<br>";
        echo "🆔 ID Empleado: $idEmpleado";
        echo "</div>";

    } catch (Exception $e) {
        echo "<div style='color: red;'>❌ Error creando {$u['nombre']}: " . $e->getMessage() . "</div>";
    }
}
?>
