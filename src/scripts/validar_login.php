<?php
session_start();
include(__DIR__ . '/../config/db.php'); // conexión PDO

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST['usuario'];
    $password = $_POST['password'];

    // Buscar usuario y su empleado
    $stmt = $pdo->prepare("
        SELECT 
            u.id_usuario AS id,
            u.id_empleado AS id_empleado,
            CONCAT(e.nombre, ' ', e.apellido_paterno, ' ', e.apellido_materno) AS nombre_completo,
            u.contrasena AS password,
            u.correo AS correo,
            r.nombre_rol AS rol
        FROM usuarios u
        INNER JOIN empleados e ON u.id_empleado = e.id_empleado
        INNER JOIN roles r ON e.id_rol = r.id_rol
        WHERE u.correo = :correo
    ");
    $stmt->execute(['correo' => $usuario]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // ✅ Guardamos también el id_empleado en sesión
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['empleado_id'] = $user['id_empleado']; // <-- importante
        $_SESSION['rol'] = $user['rol'];
        $_SESSION['nombre_completo'] = $user['nombre_completo'];
        $_SESSION['correo'] = $user['correo'];

        header("Location: ../index.php?view=nueva_venta");
        exit;
    } else {
        $_SESSION['error'] = "Usuario o contraseña incorrectos";
        header("Location: ../pages/login.php");
        exit;
    }
}
?>
