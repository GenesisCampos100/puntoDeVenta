<?php
    session_start();
    include(__DIR__ . '/../config/db.php'); // conexión PDO

    if($_SERVER["REQUEST_METHOD"] === "POST") {
        $usuario = trim($_POST['usuario'] ?? '');
        $contra = $_POST['password'] ?? '';

        if (empty($usuario) || empty($contra)) {
            $_SESSION['error'] = 'Por favor, completa todos los campos.';
            header("Location: ../pages/login.php");
            exit;
        }
        
        try {
            $stmt = $pdo->prepare(
                "SELECT u.id_usuario AS id,
                        CONCAT(e.nombre, ' ', e.apellido_paterno, ' ', e.apellido_materno) AS nombre_completo,
                        u.contrasena AS contra,
                        u.correo AS correo,
                        r.nombre_rol AS rol
                FROM usuarios u
                INNER JOIN empleados e ON u.id_empleado = e.id_empleado
                LEFT JOIN roles r ON e.id_rol = r.id_rol
                WHERE u.correo = :usuario LIMIT 1"
            );
            $stmt->execute(['usuario' => $usuario]);
            $user = $stmt->fetch();

            var_dump($user);
            var_dump($user['contra']);
            var_dump(password_verify($contra, $user['contra']));
            exit;
            
            if ($user && password_verify($contra, $user['contra'])) {
                session_regenerate_id(true); // Prevención de fijación de sesión

                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['rol'] = $user['rol'];
                $_SESSION['nombre_completo'] = $user['nombre_completo'];
                $_SESSION['correo'] = $user['correo'];

                header("Location: ../index.php?view=nueva_venta");
                exit;
            } else {
                $_SESSION['error'] = 'Usuario o contraseña incorrectos';
                header("Location: ../pages/login.php");
                exit;
            }

        } catch (PDOException $e) {
            error_log("Error en login: " . $e->getMessage());
            $_SESSION['error'] = 'Ocurrió un error. Inténtalo más tarde.';
            header("Location: ../pages/login.php");
            exit;
        }

    } else {
        // Redirigir si no es POST
        header("Location: ../pages/login.php");
        exit;
    }
?>