<?php
require_once __DIR__ . '/../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);
$id_venta = $data['id_venta'] ?? null;

if (!$id_venta) {
    echo json_encode(['success' => false, 'message' => 'ID de venta no proporcionado']);
    exit;
}

try {
    // Iniciamos transacción
    $pdo->beginTransaction();

    // Eliminamos los detalles primero
    $stmt_detalle = $pdo->prepare("DELETE FROM detalle_ventas WHERE id_venta = ?");
    $stmt_detalle->execute([$id_venta]);

    // Eliminamos la venta
    $stmt_venta = $pdo->prepare("DELETE FROM ventas WHERE id_venta = ?");
    $stmt_venta->execute([$id_venta]);

    $pdo->commit();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
