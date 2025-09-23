<?php
session_start();
include 'db.php'; // archivo con la conexión PDO

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
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['rol'] = $user['rol'];

        echo json_encode([
            "status" => "success",
            "rol" => $user['rol']
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Usuario o contraseña incorrectos"
        ]);
    }
}
