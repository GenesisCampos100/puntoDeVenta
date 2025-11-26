<?php
/**
 * Script de verificación del sistema
 * Verifica que todos los componentes necesarios estén funcionando
 */

// Estilos básicos para el reporte
echo '<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    h1 { color: #2d4353; border-bottom: 3px solid #b4c24d; padding-bottom: 10px; }
    h2 { color: #2d4353; margin-top: 30px; }
    .ok { color: #28a745; font-weight: bold; }
    .error { color: #dc3545; font-weight: bold; }
    .warning { color: #ffc107; font-weight: bold; }
    .info { background: #e7f3ff; padding: 15px; border-left: 4px solid #2196F3; margin: 10px 0; }
    table { width: 100%; border-collapse: collapse; margin: 15px 0; }
    th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background: #2d4353; color: white; }
    tr:hover { background: #f5f5f5; }
    .btn { display: inline-block; padding: 10px 20px; background: #b4c24d; color: #2d4353; text-decoration: none; border-radius: 5px; font-weight: bold; margin-top: 20px; }
    .btn:hover { background: #a0b040; }
</style>';

echo '<div class="container">';
echo '<h1>🔍 Verificación del Sistema - PrismaMK2C</h1>';

// Verificar versión de PHP
echo '<h2>1. PHP</h2>';
echo '<table>';
echo '<tr><th>Componente</th><th>Estado</th><th>Valor</th></tr>';
echo '<tr><td>Versión de PHP</td><td class="ok">✓ OK</td><td>' . phpversion() . '</td></tr>';
echo '<tr><td>PHP >= 7.4</td><td class="' . (version_compare(phpversion(), '7.4.0', '>=') ? 'ok">✓ OK' : 'error">✗ ERROR') . '</td><td>' . (version_compare(phpversion(), '7.4.0', '>=') ? 'Compatible' : 'Actualizar PHP') . '</td></tr>';
echo '</table>';

// Verificar extensiones de PHP
echo '<h2>2. Extensiones de PHP</h2>';
echo '<table>';
echo '<tr><th>Extensión</th><th>Estado</th></tr>';

$required_extensions = ['pdo', 'pdo_mysql', 'mysqli', 'curl', 'json', 'mbstring', 'openssl'];
foreach ($required_extensions as $ext) {
    $loaded = extension_loaded($ext);
    echo '<tr><td>' . $ext . '</td><td class="' . ($loaded ? 'ok">✓ Instalada' : 'error">✗ No instalada') . '</td></tr>';
}
echo '</table>';

// Verificar archivos críticos
echo '<h2>3. Archivos del Sistema</h2>';
echo '<table>';
echo '<tr><th>Archivo</th><th>Estado</th></tr>';

$critical_files = [
    '.env' => '.env',
    'src/config/db.php' => 'src/config/db.php',
    'src/config/translation.php' => 'src/config/translation.php',
    'src/pages/login.php' => 'src/pages/login.php',
    'src/index.php' => 'src/index.php',
];

foreach ($critical_files as $label => $file) {
    $exists = file_exists(__DIR__ . '/' . $file);
    echo '<tr><td>' . $label . '</td><td class="' . ($exists ? 'ok">✓ Existe' : 'error">✗ No encontrado') . '</td></tr>';
}
echo '</table>';

// Verificar permisos de escritura
echo '<h2>4. Permisos de Escritura</h2>';
echo '<table>';
echo '<tr><th>Directorio</th><th>Estado</th></tr>';

$writable_dirs = [
    'src/logs' => 'src/logs',
    'src/uploads' => 'src/uploads',
    'public/uploads' => 'public/uploads',
    'src/config' => 'src/config',
];

foreach ($writable_dirs as $label => $dir) {
    $full_path = __DIR__ . '/' . $dir;
    if (file_exists($full_path)) {
        $writable = is_writable($full_path);
        echo '<tr><td>' . $label . '</td><td class="' . ($writable ? 'ok">✓ Escribible' : 'warning">⚠ No escribible') . '</td></tr>';
    } else {
        echo '<tr><td>' . $label . '</td><td class="error">✗ No existe</td></tr>';
    }
}
echo '</table>';

// Verificar módulos de Apache
echo '<h2>5. Servidor Web</h2>';
echo '<table>';
echo '<tr><th>Componente</th><th>Estado</th></tr>';
echo '<tr><td>Servidor</td><td class="ok">✓</td></tr>';
echo '<tr><td>Software</td><td>' . $_SERVER['SERVER_SOFTWARE'] . '</td></tr>';

if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    $mod_rewrite = in_array('mod_rewrite', $modules);
    echo '<tr><td>mod_rewrite</td><td class="' . ($mod_rewrite ? 'ok">✓ Habilitado' : 'warning">⚠ No detectado') . '</td></tr>';
} else {
    echo '<tr><td>mod_rewrite</td><td class="warning">⚠ No se puede verificar</td></tr>';
}

echo '</table>';

// Verificar base de datos
echo '<h2>6. Base de Datos</h2>';

try {
    // Intentar cargar configuración
    if (file_exists(__DIR__ . '/src/config/db.php')) {
        require_once __DIR__ . '/src/config/db.php';
        echo '<div class="info"><strong>✓ Archivo de configuración encontrado</strong><br>';
        echo 'Host: ' . ($host ?? 'No definido') . '<br>';
        echo 'Base de datos: ' . ($db_name ?? 'No definido') . '<br>';
        echo 'Usuario: ' . ($user ?? 'No definido') . '</div>';
        
        // Intentar conectar
        if (isset($conn) && $conn instanceof PDO) {
            echo '<div class="info ok">✓ <strong>Conexión a base de datos exitosa</strong></div>';
        }
    } else {
        echo '<div class="error">✗ Archivo de configuración de base de datos no encontrado</div>';
    }
} catch (Exception $e) {
    echo '<div class="error">✗ Error de conexión: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

// Rutas de acceso
echo '<h2>7. Rutas de Acceso</h2>';
echo '<div class="info">';
echo '<strong>URL Base del Proyecto:</strong> http://' . $_SERVER['HTTP_HOST'] . '/PrismaMK2C/<br>';
echo '<strong>Login:</strong> <a href="http://' . $_SERVER['HTTP_HOST'] . '/PrismaMK2C/" target="_blank">http://' . $_SERVER['HTTP_HOST'] . '/PrismaMK2C/</a><br>';
echo '<strong>Login Directo:</strong> <a href="http://' . $_SERVER['HTTP_HOST'] . '/PrismaMK2C/src/pages/login.php" target="_blank">http://' . $_SERVER['HTTP_HOST'] . '/PrismaMK2C/src/pages/login.php</a><br>';
echo '</div>';

// Información del sistema
echo '<h2>8. Información del Sistema</h2>';
echo '<table>';
echo '<tr><td>Sistema Operativo</td><td>' . PHP_OS . '</td></tr>';
echo '<tr><td>Directorio del Proyecto</td><td>' . __DIR__ . '</td></tr>';
echo '<tr><td>Timezone</td><td>' . date_default_timezone_get() . '</td></tr>';
echo '<tr><td>Fecha/Hora</td><td>' . date('Y-m-d H:i:s') . '</td></tr>';
echo '</table>';

echo '<a href="src/pages/login.php" class="btn">🚀 Ir al Login</a>';
echo '</div>';
?>
