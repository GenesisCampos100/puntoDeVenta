<?php
session_start();
require __DIR__ . '/../config/db.php';

$token = $_GET['token'] ?? '';
if (!$token) {
    $_SESSION['error'] = 'Token inválido.';
    header('Location: ../pages/login.php');
    exit;
}

$tokenHash = hash('sha256', $token);
try {
    $stmt = $pdo->prepare('SELECT uv.id, uv.user_id, uv.expires_at, u.correo FROM user_verifications uv JOIN usuarios u ON uv.user_id = u.id_usuario WHERE uv.token = :token LIMIT 1');
    $stmt->execute(['token' => $tokenHash]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        $_SESSION['error'] = 'Token inválido o ya usado.';
        header('Location: ../pages/login.php');
        exit;
    }
    if (strtotime($row['expires_at']) < time()) {
        // token expirado
        // eliminar token
        $del = $pdo->prepare('DELETE FROM user_verifications WHERE id = ?');
        $del->execute([$row['id']]);
        $_SESSION['error'] = 'El token ha expirado. Solicita uno nuevo.';
        header('Location: ../pages/login.php');
        exit;
    }

    // Marcar usuario como verificado
    $upd = $pdo->prepare('UPDATE usuarios SET verificado = 1 WHERE id_usuario = ?');
    $upd->execute([$row['user_id']]);

    // Eliminar token usado
    $del = $pdo->prepare('DELETE FROM user_verifications WHERE id = ?');
    $del->execute([$row['id']]);

    $_SESSION['registro_success'] = 'Cuenta verificada correctamente. Ya puedes iniciar sesión.';
    header('Location: ../pages/login.php');
    exit;
} catch (PDOException $e) {
    error_log('Verify error: ' . $e->getMessage());
    $_SESSION['error'] = 'Ocurrió un error procesando la verificación.';
    header('Location: ../pages/login.php');
    exit;
}
