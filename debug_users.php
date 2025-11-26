<?php
header('Content-Type: text/plain');
require_once __DIR__ . '/src/config/db.php';

try {
    $stmt = $pdo->query("SELECT id_usuario, correo, contrasena FROM usuarios");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $user) {
        $isHash = password_get_info($user['contrasena'])['algo'] != 0 ? 'YES' : 'NO';
        echo "ID: " . $user['id_usuario'] . "\n";
        echo "Email: " . $user['correo'] . "\n";
        echo "Pass: " . substr($user['contrasena'], 0, 10) . "...\n";
        echo "IsHash: " . $isHash . "\n";
        echo "-------------------\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
