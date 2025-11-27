<?php
    require_once __DIR__ . '/../config/db.php';

    header('Content-Type: application/json; charset=utf-8');

    if (!isset($_GET['id_rol'])) {
        echo json_encode(['error' => 'id_rol required']);
        exit;
    }

    $id_rol = (int)$_GET['id_rol'];

    // Obtener el nombre del rol para generar el prefijo
    $prefijo = 'EMP';
    try {
        $stmtRol = $pdo->prepare("SELECT nombre_rol FROM roles WHERE id_rol = :id_rol");
        $stmtRol->execute(['id_rol' => $id_rol]);
        $rolData = $stmtRol->fetch(PDO::FETCH_ASSOC);
        
        if ($rolData && !empty($rolData['nombre_rol'])) {
            // Usar las primeras 3 letras del nombre del rol en mayúsculas
            $prefijo = strtoupper(substr($rolData['nombre_rol'], 0, 3));
        }
    } catch (Exception $e) {
        // Si hay error, usar EMP por defecto
        $prefijo = 'EMP';
    }

    try {
        $sql = "SELECT id_empleado FROM empleados WHERE id_empleado LIKE :prefijo ORDER BY id_empleado DESC LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['prefijo' => $prefijo . '%']);
        $lastEmp = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($lastEmp) {
            // Extraer el número del último empleado y sumar 1
            $lastNum = (int) substr($lastEmp['id_empleado'], strlen($prefijo));
            $next = $prefijo . str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
        } else {
            // Si no hay empleados con este prefijo, empezar en 0001
            $next = $prefijo . '0001';
        }
        echo json_encode(['next' => $next]);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
?>