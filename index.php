<?php
/**
 * Punto de entrada principal del proyecto
 * Redirige automáticamente al login
 */

// Iniciar sesión para verificar si el usuario ya está logueado
session_start();

// Si el usuario ya tiene sesión activa, redirigir al dashboard
if (isset($_SESSION['usuario_id'])) {
    header('Location: src/index.php?view=nueva_venta');
    exit;
}

// Si no hay sesión, redirigir al login
header('Location: src/pages/login.php');
exit;
