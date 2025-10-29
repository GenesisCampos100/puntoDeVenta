<?php
session_start();

// Limpiar sesión
$_SESSION = [];

// Borrar cookie de sesión
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'], $params['secure'], $params['httponly']
    );
}

// Destruir sesión en servidor
session_destroy();

// Evitar cache en cliente
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

// Redirigir al login
header('Location: ../pages/login.php');
exit;
