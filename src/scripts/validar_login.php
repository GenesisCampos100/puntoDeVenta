<?php
session_start();
include(__DIR__ . '/../config/db.php'); // conexión PDO

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // NOTE: debug logging removed for production. Keep minimal server logs if needed.

    // Buscar usuario en la tabla real: usuarios (id_usuario, correo, contrasena)
    $stmt = $pdo->prepare(
        "SELECT u.id_usuario, u.correo, u.contrasena, u.id_empleado, e.id_rol, r.nombre_rol as rol
         FROM usuarios u
         LEFT JOIN empleados e ON u.id_empleado = e.id_empleado
         LEFT JOIN roles r ON e.id_rol = r.id_rol
         WHERE u.correo = :usuario OR u.id_usuario = :usuario OR u.id_empleado = :usuario"
    );
    $stmt->execute(['usuario' => $usuario]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // (no debug write)

    if ($user && !empty($user['contrasena']) && password_verify($password, $user['contrasena'])) {
        
        // --- NUEVA VALIDACIÓN: Solo permitir acceso a empleados ---
        if (empty($user['id_empleado'])) {
            $error_message = 'Acceso denegado. Solo para empleados.';
            if (!empty($_POST['ajax'])) {
                header('Content-Type: application/json');
                http_response_code(403); // Forbidden
                echo json_encode(['success' => false, 'message' => $error_message]);
                exit;
            }
            $_SESSION['error'] = $error_message;
            header("Location: ../pages/login.php");
            exit;
        }

        // Guardar sesión
        $_SESSION['usuario_id'] = $user['id_usuario'];
        $_SESSION['rol'] = isset($user['rol']) ? $user['rol'] : null;
        $_SESSION['correo'] = $user['correo'];

        // Regenerar id de sesión para prevenir fijación de sesión
        session_regenerate_id(true);

    // (no debug write)

        // Asegurarse de que la sesión se escriba antes de redirigir
        session_write_close();

        // Si la petición es AJAX, devolver JSON. Si no, redirigir como antes.
        if (!empty($_POST['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'redirect' => '/PrismaMK2C/src/index.php?view=nueva%20venta'
            ]);
            exit;
        }

        // Redirección normal para fallback si JS falla
        header("Location: /PrismaMK2C/src/index.php?view=nueva%20venta");
        exit;
    } else {
        // Si la petición es AJAX, devolver JSON de error.
        if (!empty($_POST['ajax'])) {
            header('Content-Type: application/json');
            http_response_code(401); // Unauthorized
            echo json_encode(['success' => false, 'message' => 'Usuario o contraseña incorrectos']);
            exit;
        }

        // En caso de error, volver al login con mensaje
    // Buscar usuario con su imagen
    $stmt = $pdo->prepare("SELECT 
                u.id_usuario AS id,
                CONCAT(e.nombre, ' ', e.apellido_paterno, ' ', e.apellido_materno) AS nombre_completo,
                u.contrasena AS password,
                u.correo AS correo,
                u.imagen AS imagen,  -- ✅ agregamos la columna imagen
                r.nombre_rol AS rol
            FROM usuarios u
            INNER JOIN empleados e ON u.id_empleado = e.id_empleado
            INNER JOIN roles r ON e.id_rol = r.id_rol
            WHERE u.correo = :correo
        ");
    $stmt->execute(['correo' => $usuario]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // ✅ Guardar variables de sesión
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['rol'] = $user['rol'];
        $_SESSION['nombre_completo'] = $user['nombre_completo'];
        $_SESSION['correo'] = $user['correo'];
        $_SESSION['foto_perfil'] = $user['imagen'] ?? '../public/img/1.png'; // ✅ foto guardada o default

        header("Location: ../index.php?view=nueva_venta");
        exit;
    } else {
        $_SESSION['error'] = "Usuario o contraseña incorrectos";
        header("Location: ../pages/login.php");
        exit;
    }
}