<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Asegúrate de tener Composer y PHPMailer instalado (vendor/autoload.php)
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

// Validar email
$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
if (!$email) {
    $_SESSION['error'] = 'Correo inválido';
    header('Location: ../pages/recuperar_contrasena.html');
    exit;
}

// ===== Rate limiting simple por IP: máximo X intentos en WINDOW segundos =====
$maxAttempts = 5;
$windowSeconds = 3600; // 1 hora
$logsDir = __DIR__ . '/../logs';
if (!is_dir($logsDir)) @mkdir($logsDir, 0755, true);
$attemptsFile = $logsDir . '/reset_attempts.json';

function get_client_ip() {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($parts[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

$ip = get_client_ip();
$attempts = [];
if (file_exists($attemptsFile)) {
    $raw = @file_get_contents($attemptsFile);
    $attempts = $raw ? json_decode($raw, true) ?? [] : [];
}
$now = time();
$ipTimes = isset($attempts[$ip]) ? array_filter($attempts[$ip], function($t) use ($now, $windowSeconds) { return ($now - $t) <= $windowSeconds; }) : [];
if (count($ipTimes) >= $maxAttempts) {
    $_SESSION['error'] = 'Demasiados intentos de recuperación desde tu IP. Intenta de nuevo más tarde.';
    header('Location: ../pages/recuperar_contrasena.html');
    exit;
}
// anotar intento
$ipTimes[] = $now;
$attempts[$ip] = array_values($ipTimes);
@file_put_contents($attemptsFile, json_encode($attempts), LOCK_EX);


// Buscar usuario en la estructura real (usuarios.id_usuario y empleados)
$stmt = $pdo->prepare('SELECT u.id_usuario, u.correo, u.id_empleado, CONCAT(e.nombre, " ", e.apellido_paterno) as nombre_empleado
    FROM usuarios u
    LEFT JOIN empleados e ON u.id_empleado = e.id_empleado
    WHERE u.correo = :email
    LIMIT 1');
$stmt->execute(['email' => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // No revelar si el correo existe por seguridad
    $_SESSION['info'] = 'Si existe una cuenta asociada, recibirás un email con instrucciones.';
    header('Location: ../pages/login.php');
    exit;
}

// Si el usuario está vinculado a un empleado, no permitir recuperación por este flujo
if (!empty($user['id_empleado'])) {
    // No revelar detalles: indicar mensaje genérico y salir
    error_log('Password reset requested for empleado-linked account: ' . $user['correo']);
    $_SESSION['info'] = 'Si existe una cuenta asociada, recibirás un email con instrucciones.';
    header('Location: ../pages/login.php');
    exit;
}

// Generar token (enviamos token claro por email, guardamos hash en DB)
$token = bin2hex(random_bytes(32));
$tokenHash = hash('sha256', $token);
$expires_at = date('Y-m-d H:i:s', time() + 3600); // 1 hora

// Guardar token (usar user_id = id_usuario)
$insert = $pdo->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)');
$insert->execute(['user_id' => $user['id_usuario'], 'token' => $tokenHash, 'expires_at' => $expires_at]);

// Enviar email con PHPMailer (con debug a archivo para diagnóstico)
$mail = new PHPMailer(true);
try {
    // Leer configuración de mail desde src/config/mail.php
    $mailConfigPath = __DIR__ . '/../config/mail.php';
    $mailConfig = file_exists($mailConfigPath) ? require $mailConfigPath : [];

    // Logs dir
    $logsDir = __DIR__ . '/../logs';
    if (!is_dir($logsDir)) @mkdir($logsDir, 0755, true);
    $mailDebugFile = $logsDir . '/mail_debug.log';

    // Configurar PHPMailer según config (si no existe, quedan placeholders)
    $mail->isSMTP();
    $mail->CharSet = 'UTF-8';
    $mail->Host = $mailConfig['host'] ?? 'smtp-mail.outlook.com';
    $mail->SMTPAuth = true;
    $mail->Username = $mailConfig['username'] ?? '';
    $mail->Password = $mailConfig['password'] ?? '';
    $mail->SMTPSecure = (isset($mailConfig['secure']) && $mailConfig['secure'] === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $mailConfig['port'] ?? 587;

    // Debug: registrar salida SMTP en archivo
    $mail->SMTPDebug = 2; // 0 = off, 1 = client, 2 = client+server
    $mail->Debugoutput = function($str, $level) use ($mailDebugFile) {
        $line = date('[Y-m-d H:i:s]') . " [level:$level] " . trim($str) . PHP_EOL;
        @file_put_contents($mailDebugFile, $line, FILE_APPEND | LOCK_EX);
    };

    // Opciones de SSL (por si hay certificados auto-firmados en entorno local)
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ],
    ];

    $fromEmail = $mailConfig['from_email'] ?? ($mailConfig['username'] ?? 'no-reply@example.com');
    $fromName = $mailConfig['from_name'] ?? 'Soporte';
    $mail->setFrom($fromEmail, $fromName);

    $displayName = !empty($user['nombre_empleado']) ? $user['nombre_empleado'] : $user['correo'];
    $mail->addAddress($email, $displayName);

    // Construir enlace absoluto dinámicamente (reemplazar /src/scripts con /src/pages)
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $basePath = dirname($_SERVER['REQUEST_URI']); // ej. /PrismaMK2C/src/scripts
    $pagesPath = str_replace('/src/scripts', '/src/pages', $basePath);
    $resetLink = $protocol . '://' . $host . $pagesPath . '/confirmar_contra.html?token=' . urlencode($token);

    $mail->isHTML(true);
    $mail->Subject = 'Recuperación de contraseña';
    $mail->Body = "<p>Hola {$displayName},</p>"
        . "<p>Recibimos una solicitud para restablecer tu contraseña. Haz clic en el siguiente enlace (válido 1 hora):</p>"
        . "<p><a href=\"{$resetLink}\">Restablecer contraseña</a></p>"
        . "<p>Si no solicitaste esto, ignora este correo.</p>";

    // Intentar envío
    $mail->send();
    // Registrar resultado
    @file_put_contents($mailDebugFile, date('[Y-m-d H:i:s]') . " Mail sent to {$email}\n", FILE_APPEND | LOCK_EX);
} catch (Exception $e) {
    // registrar error y continuar (no exponer detalles al usuario)
    $err = 'Mail error: ' . ($mail->ErrorInfo ?? $e->getMessage());
    error_log($err);
    @file_put_contents($mailDebugFile, date('[Y-m-d H:i:s]') . " ERROR: " . $err . PHP_EOL, FILE_APPEND | LOCK_EX);
}

$_SESSION['info'] = 'Si existe una cuenta asociada, recibirás un email con instrucciones.';
header('Location: ../pages/recuperar_contrasena.html');
exit;
