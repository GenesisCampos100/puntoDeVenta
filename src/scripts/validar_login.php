<?php
// Iniciar buffer de salida para evitar que warnings/notices rompan el JSON
ob_start();

session_start();
require_once __DIR__ . '/../config/db.php';

// Función helper para respuesta JSON segura
function send_json($success, $message = '', $redirect = '') {
    // Limpiar cualquier salida previa (errores PHP, espacios, etc.)
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'redirect' => $redirect
    ]);
    exit;
}

try {
    // Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido");
    }

    // Verificar conexión PDO
    if (!isset($pdo)) {
        throw new Exception("Error de conexión a la base de datos");
    }

    // Obtener datos
    $correo = trim($_POST['correo'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validar campos vacíos
    if (empty($correo) || empty($password)) {
        send_json(false, "Por favor complete todos los campos");
    }

    // Consulta SQL segura con PDO
    // Usamos LEFT JOIN para permitir login incluso si faltan datos de empleado/rol
    $sql = "SELECT 
                u.id_usuario,
                u.contrasena,
                u.correo,
                u.imagen,
                e.nombre,
                e.apellido_paterno,
                e.apellido_materno,
                r.nombre_rol
            FROM usuarios u
            LEFT JOIN empleados e ON u.id_empleado = e.id_empleado
            LEFT JOIN roles r ON e.id_rol = r.id_rol
            WHERE u.correo = :correo
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':correo' => $correo]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificar contraseña
    $passwordValido = false;
    if ($user) {
        if (password_verify($password, $user['contrasena'])) {
            $passwordValido = true;
        } elseif ($password === trim($user['contrasena'])) {
            // Fallback: Permitir contraseña en texto plano (para usuarios de prueba)
            $passwordValido = true;
        }
    }

    if ($user && $passwordValido) {
        
        // Regenerar ID de sesión por seguridad
        session_regenerate_id(true);

        // Construir nombre completo (manejar nulos)
        $nombre = $user['nombre'] ?? 'Usuario';
        $paterno = $user['apellido_paterno'] ?? '';
        $materno = $user['apellido_materno'] ?? '';
        $nombreCompleto = trim("$nombre $paterno $materno");

        // Guardar en sesión
        $_SESSION['usuario_id'] = $user['id_usuario'];
        $_SESSION['correo'] = $user['correo'];
        $_SESSION['nombre_completo'] = $nombreCompleto ?: $user['correo'];
        $_SESSION['rol'] = $user['nombre_rol'] ?? 'empleado'; // Rol por defecto
        $_SESSION['foto_perfil'] = $user['imagen'] ?? '../public/img/1.png';

        // Respuesta exitosa
        send_json(true, "Inicio de sesión exitoso", "../index.php?view=nueva_venta");

    } else {
        send_json(false, "Correo o contraseña incorrectos");
    }

} catch (Exception $e) {
    // Log del error real en el servidor (opcional)
    error_log("Login Error: " . $e->getMessage());
    
    // Respuesta genérica al usuario
    send_json(false, "Error en el servidor: " . $e->getMessage());
}

// Finalizar buffer (por si acaso no se llamó a send_json)
ob_end_flush();
?>
