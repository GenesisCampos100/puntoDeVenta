<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Layout</title>
</head>
<body>
    
</body>
</html>


<?php
session_start();

// Si no hay login, mandamos al login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// Incluimos el archivo donde ya tienes los permisos definidos
require_once "config/permisos.php";

$rol = $_SESSION['rol'];

// Seguridad extra: si el rol no existe en permisos, lo mandamos fuera
if (!isset($permisos[$rol])) {
    header("Location: login.php");
    exit;
}
?>

<!-- Menú lateral -->
<nav class="sidebar">
    <ul>
        <?php foreach ($permisos[$rol] as $modulo): ?>
            <li>
                <a href="<?= $modulo ?>.php"><?= ucfirst($modulo) ?></a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
