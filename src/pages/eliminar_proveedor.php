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
        echo json_encode(['success' => false, 'error' => 'ID de proveedor inválido']);
        exit;
    }

    // No forzar cast a int para soportar VARCHAR
    $id = trim($id);

    try {
        // Verificar si existe el proveedor
        $stmtCheck = $pdo->prepare("SELECT id_proveedor FROM proveedores WHERE id_proveedor = ?");
        $stmtCheck->execute([$id]);
        if (!$stmtCheck->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Proveedor no encontrado']);
            exit;
        }

        // Iniciar transacción
        $pdo->beginTransaction();

        // Eliminar proveedor
        $stmtProv = $pdo->prepare("DELETE FROM proveedores WHERE id_proveedor = ?");
        if ($stmtProv->execute([$id])) {
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Proveedor eliminado correctamente']);
        } else {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'error' => 'No se pudo eliminar el proveedor']);
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("Error al eliminar proveedor $id: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Error interno al procesar la solicitud']);
    }
?>