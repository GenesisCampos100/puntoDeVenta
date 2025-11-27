<?php
require_once __DIR__ . '/../config/translation.php';
require_once __DIR__ . '/../config/db.php';
header("Content-Type: application/json; charset=utf-8");

// Habilitar reporte de errores para depuración (desactivar en producción)
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido");
    }

    if (!isset($_POST['cod_barras']) || empty($_POST['cod_barras'])) {
        throw new Exception("Código de barras no recibido");
    }

    $cod_barras = $_POST['cod_barras'];

    // Iniciar transacción para asegurar integridad
    $pdo->beginTransaction();

    // 1. Verificar si el producto existe
    $stmtCheck = $pdo->prepare("SELECT cod_barras FROM productos WHERE cod_barras = ?");
    $stmtCheck->execute([$cod_barras]);
    if ($stmtCheck->rowCount() === 0) {
        throw new Exception("El producto no existe");
    }

    // 2. Eliminar variantes asociadas (HARD DELETE)
    // Primero eliminamos las variantes para no violar restricciones de FK si las hubiera
    $stmtDeleteVariantes = $pdo->prepare("DELETE FROM variantes WHERE cod_barras = ?");
    $stmtDeleteVariantes->execute([$cod_barras]);

    // 3. Eliminar el producto principal (HARD DELETE)
    $stmtDeleteProducto = $pdo->prepare("DELETE FROM productos WHERE cod_barras = ?");
    $stmtDeleteProducto->execute([$cod_barras]);

    // Confirmar transacción
    $pdo->commit();

    echo json_encode([
        "success" => true,
        "message" => "Producto eliminado permanentemente"
    ]);

} catch (Exception $e) {
    // Revertir cambios si algo falla
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Verificar si es error de llave foránea (integridad referencial)
    $msg = $e->getMessage();
    if (strpos($msg, 'Integrity constraint violation') !== false) {
        $msg = "No se puede eliminar el producto porque tiene registros relacionados (ventas, compras, etc.).";
    }

    echo json_encode([
        "success" => false,
        "message" => $msg
    ]);
}
?>
