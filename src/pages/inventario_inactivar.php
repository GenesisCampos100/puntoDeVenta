<?php
require_once __DIR__ . "/../config/db.php";
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido");
    }

    $id_producto = $_POST['id'] ?? null;
    $status = isset($_POST['status']) ? (int)$_POST['status'] : null;

    if (!$id_producto || !in_array($status, [0,1])) {
        throw new Exception("Parámetros inválidos");
    }

    $pdo->beginTransaction();

    // ✅ Actualizar producto
    $stmt = $pdo->prepare("
        UPDATE productos 
        SET is_active = :estado 
        WHERE cod_barras = :id
    ");
    $stmt->execute([
        ':estado' => $status,
        ':id' => $id_producto
    ]);

    // ✅ Actualizar variantes relacionadas
    $stmtVar = $pdo->prepare("
        UPDATE variantes
        SET is_active = :estado
        WHERE cod_barras = :id
    ");
    $stmtVar->execute([
        ':estado' => $status,
        ':id' => $id_producto
    ]);

    // ✅ Registrar historial
    $stmtHist = $pdo->prepare("
        INSERT INTO inventario_historial
        (cod_barras, tipo_movimiento, cantidad, motivo, referencia, fecha_movimiento)
        VALUES
        (:id, :tipo, 0, :motivo, 'ADMIN', NOW())
    ");

    $stmtHist->execute([
        ':id' => $id_producto,
        ':tipo' => ($status == 1 ? 'ACTIVADO' : 'DESACTIVADO'),
        ':motivo' => 'Cambio de estado del producto'
    ]);

    $pdo->commit();

    $response['success'] = true;
    $response['status'] = $status;
    $response['message'] = $status == 1 
                            ? "Producto activado correctamente"
                            : "Producto desactivado correctamente";

} catch (Exception $e) {
    if (!empty($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
