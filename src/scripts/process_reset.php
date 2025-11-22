<?php
session_start();

// Limpiar mensajes de sesión antiguos para evitar confusiones
unset($_SESSION['info']);
unset($_SESSION['error']);

require_once __DIR__ . '/../config/db.php';

// --- Función para enviar respuestas JSON y terminar el script ---
function send_json_response($success, $message, $redirect = null) {
    header('Content-Type: application/json');
    $response = ['success' => $success, 'message' => $message];
    if ($redirect) {
        $response['redirect'] = $redirect;
    }
    echo json_encode($response);
    exit;
}

// --- Detectar si es una petición AJAX ---
$is_ajax = isset($_POST['ajax']) && $_POST['ajax'] === '1';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    if ($is_ajax) {
        send_json_response(false, 'Método no permitido.');
    }
    exit('Method not allowed');
}

$token = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['password2'] ?? '';;

// --- Validaciones ---
if (empty($token) || empty($password) || empty($confirm_password)) {
    if ($is_ajax) {
        send_json_response(false, 'Todos los campos son obligatorios.');
    }
    $_SESSION['error'] = 'Todos los campos son obligatorios.';
    header('Location: ../pages/confirmar_contra.html?token=' . urlencode($token));
    exit;
}

if ($password !== $confirm_password) {
    if ($is_ajax) {
        send_json_response(false, 'Las contraseñas no coinciden.');
    }
    $_SESSION['error'] = 'Las contraseñas no coinciden.';
    header('Location: ../pages/confirmar_contra.html?token=' . urlencode($token));
    exit;
}

if (strlen($password) < 8) {
    if ($is_ajax) {
        send_json_response(false, 'La contraseña debe tener al menos 8 caracteres.');
    }
    $_SESSION['error'] = 'La contraseña debe tener al menos 8 caracteres.';
    header('Location: ../pages/confirmar_contra.html?token=' . urlencode($token));
    exit;
}

// --- Procesar el token ---
$tokenHash = hash('sha256', $token);

$stmt = $pdo->prepare('SELECT * FROM password_resets WHERE token = :token AND expires_at > NOW()');
$stmt->execute(['token' => $tokenHash]);
$reset_request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reset_request) {
    if ($is_ajax) {
        send_json_response(false, 'El token es inválido o ha expirado. Por favor, solicita un nuevo enlace de recuperación.');
    }
    $_SESSION['error'] = 'Token inválido o expirado.';
    header('Location: ../pages/recuperar_contrasena.html');
    exit;
}

// --- Actualizar la contraseña del usuario ---
$user_id = $reset_request['user_id'];
$newPasswordHash = password_hash($password, PASSWORD_DEFAULT);

$update = $pdo->prepare('UPDATE usuarios SET contrasena = :password WHERE id_usuario = :user_id');
$update->execute(['password' => $newPasswordHash, 'user_id' => $user_id]);

// --- Invalidar el token usado ---
$delete = $pdo->prepare('DELETE FROM password_resets WHERE user_id = :user_id');
$delete->execute(['user_id' => $user_id]);

// --- Enviar respuesta final ---
if ($is_ajax) {
    send_json_response(true, '¡Contraseña actualizada con éxito!', '../pages/login.php');
}

$_SESSION['success'] = 'Contraseña actualizada con éxito. Ya puedes iniciar sesión.';
header('Location: ../pages/login.php');
exit;
