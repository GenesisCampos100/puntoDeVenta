<?php
session_start();
include(__DIR__ . '/../config/db.php'); // conexión PDO

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST['usuario'];
    $password = $_POST['password'];

    // Buscar usuario
    $stmt = $pdo->prepare("SELECT u.id, u.password, r.nombre as rol 
                           FROM usuarios u
                           INNER JOIN roles r ON u.rol_id = r.id
                           WHERE u.nombre_usuario = :usuario OR u.correo = :usuario");
    $stmt->execute(['usuario' => $usuario]);
    $user = $stmt->fetch();

    if ($user && md5($password) === $user['password']) {
        // Guardar sesión
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['rol'] = $user['rol'];

        // ✅ Ahora redirige al index con la vista de caja
        header("Location: ../index.php?view=nueva venta");
        exit;
    } else {
        // En caso de error, volver al login con mensaje
        $_SESSION['error'] = "Usuario o contraseña incorrectos";
        header("Location: ../pages/login.php");
        exit;
    }
}
