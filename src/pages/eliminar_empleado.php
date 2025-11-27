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
$id = $data['id'] ?? null;

// 2. Si no hay JSON, intentar leer POST normal
if ($id === null) {
    $id = $_POST['id'] ?? $_GET['id'] ?? null;
}

// 3. Validar ID (permitir strings no vacíos)
if (!$id || trim($id) === '') {
    echo json_encode(['success' => false, 'error' => 'ID de empleado inválido']);
    exit;
}

// No forzar cast a int para soportar VARCHAR
$id = trim($id);

try {
    // Verificar si existe el empleado
    $stmtCheck = $pdo->prepare("SELECT id_empleado FROM empleados WHERE id_empleado = ?");
    $stmtCheck->execute([$id]);
    if (!$stmtCheck->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Empleado no encontrado']);
        exit;
    }

    // Iniciar transacción
    $pdo->beginTransaction();

    // Eliminar usuario asociado (si existe)
    $stmtUser = $pdo->prepare("DELETE FROM usuarios WHERE id_empleado = ?");
    $stmtUser->execute([$id]);

    // Eliminar empleado
    $stmtEmp = $pdo->prepare("DELETE FROM empleados WHERE id_empleado = ?");
    if ($stmtEmp->execute([$id])) {
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Empleado eliminado correctamente']);
    } else {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'No se pudo eliminar el empleado']);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Error al eliminar empleado $id: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error interno al procesar la solicitud']);
}
exit;
?>
