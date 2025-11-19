<?php
session_start();
require __DIR__ . '/../config/db.php';

// Helper function to send JSON response and exit
function send_json_response($success, $message, $redirect = null) {
    header('Content-Type: application/json');
    $response = ['success' => $success, 'message' => $message];
    if ($redirect) {
        $response['redirect'] = $redirect;
    }
    echo json_encode($response);
    exit;
}

$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || !empty($_POST['ajax']);

// --- Rate Limiting ---
// Preparamos las variables para limitar los intentos de registro.
$ip = $_SERVER['REMOTE_ADDR'];
$timeFrame = 3600; // 1 hora en segundos (60 * 60)
$maxAttempts = 5;  // Máximo 5 intentos por hora desde la misma IP

// Contamos los registros de la tabla `password_resets` como un indicador de actividad.
// Nota: Idealmente, esto debería usar una tabla dedicada para el log de registros.
$stmt = $pdo->prepare("SELECT created_at FROM password_resets WHERE ip_address = :ip AND created_at > DATE_SUB(NOW(), INTERVAL :time_frame SECOND)");
$stmt->execute(['ip' => $ip, 'time_frame' => $timeFrame]);
$ipTimes = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (count($ipTimes) >= $maxAttempts) {
    $error_message = 'Demasiados intentos de registro desde tu IP. Intenta de nuevo más tarde.';
    if ($is_ajax) {
        send_json_response(false, $error_message);
    }
    $_SESSION['registro_error'] = $error_message;
    header('Location: ../pages/registrate.php');
    exit;
}
// ... (código de rate limiting) ...

// --- Recolección de datos del formulario ---
$nombre_completo = trim($_POST['nombre_completo'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$password2 = $_POST['password2'] ?? '';
$csrf = $_POST['csrf_token'] ?? '';


// Basic validation
if (!$nombre_completo || !$email || !$password || !$password2) {
    $error_message = 'Todos los campos son requeridos.';
    if ($is_ajax) {
        send_json_response(false, $error_message);
    }
    $_SESSION['old'] = ['nombre_completo' => $nombre_completo, 'email' => $email];
    $_SESSION['registro_error'] = $error_message;
    header('Location: ../pages/registrate.php');
    exit;
}

// CSRF check (si lo estás usando)
if (!empty($_SESSION['csrf_token']) && !hash_equals($_SESSION['csrf_token'], $csrf)) {
    $error_message = 'Token inválido. Por favor recarga la página e intenta de nuevo.';
    if ($is_ajax) {
        send_json_response(false, $error_message);
    }
    $_SESSION['registro_error'] = $error_message;
    header('Location: ../pages/registrate.php');
    exit;
}

// Validaciones de nombre, email, contraseña...
if (mb_strlen($nombre_completo) < 2) {
    $error_message = 'El nombre completo debe tener al menos 2 caracteres.';
    if ($is_ajax) { send_json_response(false, $error_message); }
    // ... (código de sesión y redirección) ...
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error_message = 'Correo inválido.';
    if ($is_ajax) { send_json_response(false, $error_message); }
    // ... (código de sesión y redirección) ...
    exit;
}

if ($password !== $password2) {
    $error_message = 'Las contraseñas no coinciden.';
    if ($is_ajax) { send_json_response(false, $error_message); }
    // ... (código de sesión y redirección) ...
    exit;
}

if (strlen($password) < 8) {
    $error_message = 'La contraseña debe tener al menos 8 caracteres.';
    if ($is_ajax) { send_json_response(false, $error_message); }
    // ... (código de sesión y redirección) ...
    exit;
}


try {
    // Check duplicate email
    $stmt = $pdo->prepare('SELECT id_usuario FROM usuarios WHERE correo = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $error_message = 'Ya existe una cuenta con ese correo.';
        if ($is_ajax) { send_json_response(false, $error_message); }
        $_SESSION['old'] = ['nombre_completo' => $nombre_completo, 'email' => $email];
        $_SESSION['registro_error'] = $error_message;
        header('Location: ../pages/registrate.php');
        exit;
    }

    // --- Inserción de usuario y perfil ---
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Dividir el nombre completo para la tabla de perfiles
    $parts = explode(' ', $nombre_completo, 2);
    $nombre = $parts[0];
    $apellido = $parts[1] ?? ''; // El resto será el apellido

    $pdo->beginTransaction();

    // 1. Insertar en la tabla `usuarios`
    $stmt = $pdo->prepare('INSERT INTO usuarios (correo, contrasena) VALUES (?, ?)');
    $stmt->execute([$email, $hashed_password]);
    $user_id = $pdo->lastInsertId();

    // 2. Insertar en la tabla `user_profiles`
    $stmt = $pdo->prepare('INSERT INTO user_profiles (user_id, nombre, apellido, nombre_completo) VALUES (?, ?, ?, ?)');
    $stmt->execute([$user_id, $nombre, $apellido, $nombre_completo]);

    $pdo->commit();
    
    // ... (código de envío de email) ...

    // Success:
    $success_message = 'Registro completado. Revisa tu correo para verificar tu cuenta.';
    if ($is_ajax) {
        send_json_response(true, $success_message, '../pages/login.php');
    }
    
    $_SESSION['registro_success'] = $success_message;
    unset($_SESSION['csrf_token']);
    unset($_SESSION['old']);
    header('Location: ../pages/login.php');
    exit;

} catch (PDOException $e) {
    error_log('Register error: ' . $e->getMessage());
    $error_message = 'Ocurrió un error al procesar su registro. Intente más tarde.';
    if ($is_ajax) {
        send_json_response(false, $error_message);
    }
    $_SESSION['old'] = ['nombre_completo' => $nombre_completo, 'email' => $email];
    $_SESSION['registro_error'] = $error_message;
    header('Location: ../pages/registrate.php');
    exit;
}