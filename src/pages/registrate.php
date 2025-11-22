<?php
session_start();

// Bloquear el acceso público a esta página
$_SESSION['error'] = 'El registro de nuevos usuarios está deshabilitado.';
header('Location: login.php');
exit;

// El resto del código original nunca se ejecutará.
?>