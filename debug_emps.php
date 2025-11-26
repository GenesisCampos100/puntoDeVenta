<?php
header('Content-Type: text/plain');
require_once __DIR__ . '/src/config/db.php';

try {
    $stmt = $pdo->query("SELECT id_empleado, nombre, apellido_paterno FROM empleados");
    $emps = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($emps as $emp) {
        echo $emp['id_empleado'] . " | " . $emp['nombre'] . " " . $emp['apellido_paterno'] . "\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
