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

// 1. Intentar leer JSON del body
$input = file_get_contents('php://input');
$data = json_decode($input, true);
$ids = $data['ids'] ?? null;

// 2. Si no hay JSON, intentar leer POST normal
if ($ids === null) {
    $ids = $_POST['ids'] ?? $_GET['ids'] ?? null;
}

// 3. Normalizar a array de strings
$finalIds = [];

if (is_array($ids)) {
    // Caso: ids[] = ["A001", "B002"]
    $finalIds = array_map('trim', $ids);
} elseif (is_string($ids)) {
    // Caso: ids = "A001,B002"
    $finalIds = array_map('trim', explode(',', $ids));
}

// 4. Filtrar solo IDs no vacíos
$finalIds = array_values(array_filter($finalIds, fn($v) => $v !== ''));

// 5. Validar que haya al menos un ID
if (empty($finalIds)) {
    error_log("Intento de eliminación múltiple fallido: IDs inválidos o vacíos. Input raw: " . substr($input, 0, 100));
    echo json_encode(['success' => false, 'error' => 'IDs inválidos']);
    exit;
}

try {
    // Iniciar transacción
    $pdo->beginTransaction();

    // Prepare statements
    $stmtUser = $pdo->prepare("DELETE FROM usuarios WHERE id_empleado = ?");
    $stmtEmp = $pdo->prepare("DELETE FROM empleados WHERE id_empleado = ?");
    
    $deletedCount = 0;
    foreach ($finalIds as $id) {
        $stmtUser->execute([$id]); // Eliminar usuario primero
        if ($stmtEmp->execute([$id])) {
            $deletedCount++;
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true, 
        'message' => "$deletedCount empleado(s) eliminado(s) correctamente",
        'count' => $deletedCount
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Error al eliminar múltiples empleados: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error interno al procesar la eliminación múltiple']);
}
exit;
?>
