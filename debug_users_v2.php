<?php
require_once __DIR__ . '/src/config/db.php';

try {
    $stmt = $pdo->query("SELECT id_usuario, correo, contrasena FROM usuarios");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<html><body>";
    echo "<h1>Lista de Usuarios</h1>";
    echo "<ul>";
    foreach ($users as $user) {
        $isHash = password_get_info($user['contrasena'])['algo'] != 0 ? 'YES' : 'NO';
        echo "<li>";
        echo "ID: " . $user['id_usuario'] . " | ";
        echo "Email: " . $user['correo'] . " | ";
        echo "Pass: " . substr($user['contrasena'], 0, 10) . "... | ";
        echo "IsHash: " . $isHash;
        echo "</li>";
    }
    echo "</ul>";
    echo "</body></html>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
