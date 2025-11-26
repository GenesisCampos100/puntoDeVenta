<?php
require_once __DIR__ . '/src/config/db.php';

function getTableCount($pdo, $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        return $stmt->fetchColumn();
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

echo "Roles: " . getTableCount($pdo, 'roles') . "\n";
echo "Empleados: " . getTableCount($pdo, 'empleados') . "\n";
echo "Usuarios: " . getTableCount($pdo, 'usuarios') . "\n";
?>
