<?php
// --- INCLUSIÓN DE DB.PHP MÁS ROBUSTA ---
$db_path = __DIR__ . "/../config/db.php"; 

if (!file_exists($db_path)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Error Fatal: Archivo de conexión (db.php) no encontrado.']);
    exit;
}
require_once $db_path;

header('Content-Type: application/json');
$response = [];

if (!isset($pdo) || !($pdo instanceof PDO)) {
    $response = ['error' => 'Error Fatal: La conexión a la base de datos ($pdo) no está disponible.'];
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $response = ['error' => 'Método no permitido'];
    echo json_encode($response);
    exit;
}

$cod_entidad = $_GET['cod'] ?? null; 
$type = $_GET['type'] ?? null;      

if (!$cod_entidad) {
    $response = ['error' => 'Código de entidad (SKU/Cód. de Barras) requerido.'];
    echo json_encode($response);
    exit;
}

try {
    $sql = "
        SELECT 
            tipo_movimiento,
            cantidad_impactada,
            motivo,
            DATE_FORMAT(fecha_movimiento, '%d/%m/%Y %H:%i:%s') AS fecha_movimiento,
            referencia
        FROM inventario_movimientos
        WHERE cod_barras = :cod_entidad
        ORDER BY fecha_movimiento DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':cod_entidad' => $cod_entidad]);
    $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($movimientos);

} catch (PDOException $e) {
    $response = ['error' => 'Error al consultar la base de datos: ' . $e->getMessage()];
    echo json_encode($response);
}
?>