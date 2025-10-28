<?php
    require_once __DIR__ . '/../config/db.php';

    header('Content-Type: application/json; charset=utf-8');

    if (!isset($_GET['id_rol'])) {
        echo json_encode(['error' => 'id_rol required']);
        exit;
    }

    $id_rol = (int)$_GET['id_rol'];

    // Determinar prefijo según rol (coincide con la lógica en agregar_empleado.php)
    switch ($id_rol) {
        case 1: $prefijo = 'A'; break; // Admin
        case 2: $prefijo = 'G'; break; // Gerente
        case 3: $prefijo = 'C'; break; // Cajero
        default: $prefijo = 'X'; break;
    }

    try {
        $sql = "SELECT id_empleado FROM empleados WHERE id_empleado LIKE :prefijo ORDER BY id_empleado DESC LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['prefijo' => $prefijo . '%']);
        $ultimo = $stmt->fetchColumn();

        $numero = $ultimo ? ((int)substr($ultimo, 1)) + 1 : 1;
        $next = $prefijo . str_pad($numero, 4, '0', STR_PAD_LEFT);

        echo json_encode(['next' => $next]);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
?>