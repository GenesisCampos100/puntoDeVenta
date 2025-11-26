<?php
// src/pages/eliminar_producto.php
// Endpoint para eliminar un producto

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Obtener el ID del producto
$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? null;

if (empty($id)) {
    echo json_encode(['success' => false, 'error' => 'ID de producto no proporcionado']);
    exit;
}

try {
    // Iniciar transacción
    $pdo->beginTransaction();
    
    // Verificar si el producto existe
    $stmt = $pdo->prepare("SELECT nom_producto FROM productos WHERE cod_barras = ?");
    $stmt->execute([$id]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$producto) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Producto no encontrado']);
        exit;
    }
    
    // Eliminar primero las variantes asociadas (si existen)
    $stmt = $pdo->prepare("DELETE FROM variantes WHERE cod_barras = ?");
    $stmt->execute([$id]);
    
    // Eliminar el producto
    $stmt = $pdo->prepare("DELETE FROM productos WHERE cod_barras = ?");
    $stmt->execute([$id]);
    
    // Confirmar transacción
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Producto eliminado correctamente',
        'nombre' => $producto['nom_producto']
    ]);
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'error' => 'Error al eliminar el producto: ' . $e->getMessage()
    ]);
}
?>
