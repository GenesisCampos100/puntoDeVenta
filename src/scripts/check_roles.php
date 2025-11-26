<?php
require_once __DIR__ . '/../config/db.php';

echo "<h2>Roles Disponibles:</h2>";
$stmt = $pdo->query("SELECT * FROM roles");
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>" . print_r($roles, true) . "</pre>";

echo "<h2>Columnas de Empleados:</h2>";
$stmt = $pdo->query("DESCRIBE empleados");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>" . print_r($cols, true) . "</pre>";
?>
