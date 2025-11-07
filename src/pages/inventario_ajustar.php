<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];
$http_code = 200;

$base_path = dirname(__DIR__);
require_once $base_path . "/config/db.php";

try {
    if (!isset($pdo)) $pdo = getPdoConnection();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido.');
    }

    $tipo_movimiento = $_POST['tipo_movimiento'] ?? null;
    $cantidad_impactada = filter_var($_POST['cantidad'] ?? 0, FILTER_VALIDATE_INT);
    $motivo = $_POST['motivo'] ?? null;
    $referencia = $_POST['referencia'] ?? null;
    $cod_entidad_padre = $_POST['cod_entidad'] ?? null;
    $sku_variante = $_POST['sku_variante'] ?? null;
    $es_variante_str = $_POST['es_variante'] ?? 'false';

    $tabla_stock = 'productos';
    $cod_entidad = $cod_entidad_padre;
    $columna_condicion = 'cod_barras';

    if ($es_variante_str === 'true' && $sku_variante) {
        $tabla_stock = 'variantes';
        $cod_entidad = $sku_variante;
    }

    if (!$tipo_movimiento || $cantidad_impactada <= 0 || !$motivo || !$cod_entidad) {
        throw new Exception("Datos incompletos.");
    }

    $pdo->beginTransaction();

    // Obtener stock actual
    $stmt_check = $pdo->prepare("
        SELECT cantidad
        FROM $tabla_stock
        WHERE $columna_condicion = :cod
    ");
    $stmt_check->execute([':cod' => $cod_entidad]);
    $cur = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$cur) throw new Exception("Entidad no encontrada.");
    
    $factor = ($tipo_movimiento === 'ENTRADA') ? 1 : -1;
    $nuevo_stock = $cur['cantidad'] + ($factor * $cantidad_impactada);

    if ($nuevo_stock < 0) throw new Exception("Stock insuficiente.");

    // ✅ ACTUALIZAR STOCK
    $stmt_update = $pdo->prepare("
        UPDATE $tabla_stock
        SET cantidad = :nuevo
        WHERE $columna_condicion = :cod
    ");
    $stmt_update->execute([
        ':nuevo' => $nuevo_stock,
        ':cod' => $cod_entidad
    ]);

    // ✅ INSERTAR EN HISTORIAL
    $stmt_hist = $pdo->prepare("
        INSERT INTO inventario_historial 
        (cod_barras, tipo_movimiento, cantidad, motivo, referencia, fecha_movimiento)
        VALUES (:cod, :tipo, :cant, :motivo, :ref, NOW())
    ");
    $stmt_hist->execute([
        ':cod' => $cod_entidad,
        ':tipo' => $tipo_movimiento,
        ':cant' => $cantidad_impactada,
        ':motivo' => $motivo,
        ':ref' => $referencia
    ]);

    $pdo->commit();

    $response['success'] = true;
    $response['nuevo_stock'] = $nuevo_stock;
    $response['message'] = "Stock actualizado correctamente.";

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $http_code = 500;
    $response['message'] = $e->getMessage();
}

http_response_code($http_code);
echo json_encode($response);

?>