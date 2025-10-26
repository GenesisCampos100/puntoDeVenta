<?php

session_start();

include(__DIR__ . '/../config/db.php'); // conexión PDO

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST['usuario'];
    $password = $_POST['password'];

    // Buscar usuario
    $stmt = $pdo->prepare("SELECT 
                u.id_usuario AS id,
                CONCAT(e.nombre, ' ', e.apellido_paterno, ' ', e.apellido_materno) AS nombre_completo,
                u.contrasena AS password,
                u.correo AS correo,
                r.nombre_rol AS rol
            FROM usuarios u
            INNER JOIN empleados e ON u.id_empleado = e.id_empleado
            INNER JOIN roles r ON e.id_rol = r.id_rol
            WHERE u.correo = :usuario
        ");
        $stmt->execute(['usuario' => $usuario]);
        $user = $stmt->fetch();

        if (password_verify($password, $user['password'])) {
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['rol'] = $user['rol'];
        $_SESSION['nombre_completo'] = $user['nombre_completo'];
        $_SESSION['correo'] = $user['correo'];
            
        header("Location: ../index.php?view=nueva_venta");
        exit;



    } else {
        // Redirigir si no es POST
        header("Location: ../pages/login.php");
        exit;
    }
}

