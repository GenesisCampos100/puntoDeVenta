<?php
session_start();
include(__DIR__ . '/../config/db.php'); // conexión PDO

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Buscar usuario con su imagen
    $stmt = $pdo->prepare("SELECT 
                u.id_usuario AS id,
                e.id_empleado AS id_empleado,
                CONCAT(e.nombre, ' ', e.apellido_paterno, ' ', e.apellido_materno) AS nombre_completo,
                u.contrasena AS password,
                u.correo AS correo,
                u.imagen AS imagen,
                r.nombre_rol AS rol
            FROM usuarios u
            INNER JOIN empleados e ON u.id_empleado = e.id_empleado
            INNER JOIN roles r ON e.id_rol = r.id_rol
            WHERE u.correo = :correo
        ");
    $stmt->execute(['correo' => $usuario]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $isAjax = isset($_POST['ajax']) && $_POST['ajax'] === '1';
    $isXRequestedWith = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    $wantsJson = $isAjax || $isXRequestedWith || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

    if ($user && password_verify($password, $user['password'])) {
        // Guardar variables de sesión
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['empleado_id'] = $user['id_empleado'] ?? null;
        $_SESSION['rol'] = $user['rol'];
        $_SESSION['nombre_completo'] = $user['nombre_completo'];
        $_SESSION['correo'] = $user['correo'];
        $_SESSION['foto_perfil'] = $user['imagen'] ?? '../public/img/1.png';

        if ($wantsJson) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'redirect' => '../index.php?view=nueva_venta'
            ]);
            exit;
        } else {
            header("Location: ../index.php?view=nueva_venta");
            exit;
        }
    } else {
        if ($wantsJson) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Usuario o contraseña incorrectos'
            ]);
            exit;
        } else {
            $_SESSION['error'] = "Usuario o contraseña incorrectos";
            header("Location: ../pages/login.php");
            exit;
        }
    }
}
?>
