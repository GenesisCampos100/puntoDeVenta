<?php
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

// Obtener datos del cuerpo (JSON) o POST normal
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$id = $data['id'] ?? $_POST['id'] ?? null;

// Validar ID
if (!$id || !is_numeric($id) || $id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de cliente inválido']);
    exit;
}

$id = (int)$id;

try {
    // Verificar si existe el cliente
    $stmtCheck = $pdo->prepare("SELECT id_cliente FROM clientes WHERE id_cliente = ?");
    $stmtCheck->execute([$id]);
    if (!$stmtCheck->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Cliente no encontrado']);
        exit;
    }

    // Ejecutar eliminación
    $stmt = $pdo->prepare("DELETE FROM clientes WHERE id_cliente = ?");
    if ($stmt->execute([$id])) {
        echo json_encode(['success' => true, 'message' => 'Cliente eliminado correctamente']);
    } else {
        echo json_encode(['success' => false, 'error' => 'No se pudo eliminar el cliente']);
    }

} catch (Exception $e) {
    // Log del error real para el admin
    error_log("Error al eliminar cliente $id: " . $e->getMessage());
    // Mensaje genérico para el usuario
    echo json_encode(['success' => false, 'error' => 'Error interno al procesar la solicitud']);
}
exit;
?>