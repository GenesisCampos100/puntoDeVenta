<?php
session_start();
echo "<h1>Diagnóstico de Sesión</h1>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

require_once __DIR__ . '/config/permisos.php';
echo "<h2>Permisos Configurados:</h2>";
echo "<pre>" . print_r(array_keys($permisos), true) . "</pre>";

if (isset($_SESSION['rol'])) {
    $rol = $_SESSION['rol'];
    echo "<h2>Verificación de Rol:</h2>";
    echo "Rol en sesión: '<b>" . htmlspecialchars($rol) . "</b>'<br>";
    if (isset($permisos[$rol])) {
        echo "<span style='color:green'>✅ El rol existe en permisos.php</span>";
    } else {
        echo "<span style='color:red'>❌ El rol NO existe en permisos.php (Causa del bucle)</span>";
        echo "<br>Hash del rol: " . md5($rol); // Para ver si hay caracteres invisibles
    }
} else {
    echo "<span style='color:red'>❌ No hay rol en la sesión.</span>";
}
?>
