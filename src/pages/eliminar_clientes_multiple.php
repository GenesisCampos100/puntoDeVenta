<?php
require_once __DIR__ . '/../config/translation.php';
require_once __DIR__ . "/../config/db.php";

// Limpiar cualquier salida previa
while (ob_get_level()) ob_end_clean();

// Configurar cabecera JSON
header('Content-Type: application/json; charset=utf-8');

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Obtener datos del cuerpo (JSON)
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$ids = $data['ids'] ?? [];

// Validar IDs
if (empty($ids) || !is_array($ids)) {
    echo json_encode(['success' => false, 'error' => 'No se seleccionaron clientes']);
    exit;
}

// Filtrar IDs válidos
$ids = array_filter($ids, function($id) {
    return is_numeric($id) && $id > 0;
});

if (empty($ids)) {
    echo json_encode(['success' => false, 'error' => 'IDs inválidos']);
    exit;
}

try {
    // Iniciar transacción
    $pdo->beginTransaction();

    // Preparar statement
    $stmt = $pdo->prepare("DELETE FROM clientes WHERE id_cliente = ?");
    
    $deletedCount = 0;
    foreach ($ids as $id) {
        if ($stmt->execute([$id])) {
            $deletedCount++;
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true, 
        'message' => "$deletedCount cliente(s) eliminado(s) correctamente",
        'count' => $deletedCount
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    
    // Log del error real
    error_log("Error al eliminar múltiples clientes: " . $e->getMessage());
    
    echo json_encode(['success' => false, 'error' => 'Error interno al procesar la eliminación múltiple']);
}
exit;
?>
