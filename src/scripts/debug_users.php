<?php
require_once __DIR__ . '/../config/db.php';

echo "<h1>Diagnóstico de Usuarios</h1>";

try {
    $stmt = $pdo->query("SELECT id_usuario, correo, contrasena FROM usuarios");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Correo</th><th>Contraseña (Hash o Texto)</th><th>Prueba 'cajero123'</th></tr>";

    foreach ($usuarios as $u) {
        $pass = $u['contrasena'];
        $isHash = password_get_info($pass)['algo'] != 0;
        $matchHash = password_verify('cajero123', $pass) ? 'SÍ (Hash)' : 'NO';
        $matchPlain = ($pass === 'cajero123') ? 'SÍ (Plano)' : 'NO';
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($u['id_usuario']) . "</td>";
        echo "<td>" . htmlspecialchars($u['correo']) . "</td>";
        echo "<td>" . htmlspecialchars(substr($pass, 0, 15)) . "... (" . ($isHash ? 'Hash' : 'Texto') . ")</td>";
        echo "<td>Hash: $matchHash | Plano: $matchPlain</td>";
        echo "</tr>";
    }
    echo "</table>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
