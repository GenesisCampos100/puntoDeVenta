<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$id_venta = $_GET['id_venta'] ?? 0;

if (!$id_venta) {
    echo json_encode(['success' => false, 'message' => 'ID de venta no proporcionado']);
    exit;
}

try {
    // Obtener información de la venta
    $stmtVenta = $pdo->prepare("
        SELECT 
            v.*,
            CONCAT(e.nombre, ' ', e.apellido_paterno, ' ', e.apellido_materno) AS empleado,
            CONCAT(c.nombre, ' ', c.apellido_paterno, ' ', c.apellido_materno) AS cliente
        FROM ventas v
        LEFT JOIN empleados e ON e.id_empleado = v.id_empleado
        LEFT JOIN clientes c ON c.id_cliente = v.id_cliente
        WHERE v.id_venta = ?
    ");
    $stmtVenta->execute([$id_venta]);
    $venta = $stmtVenta->fetch(PDO::FETCH_ASSOC);

    if (!$venta) {
        echo json_encode(['success' => false, 'message' => 'Venta no encontrada']);
        exit;
    }

    // Obtener productos
    $stmtProductos = $pdo->prepare("
        SELECT
            dv.*,
            p.nom_producto AS nombre,
            COALESCE(vr.talla, p.talla) AS talla,
            COALESCE(vr.color, p.color) AS color,
            (dv.cantidad * dv.precio_unitario - COALESCE(dv.descuento, 0)) AS subtotal
        FROM detalle_ventas dv
        LEFT JOIN productos p ON p.cod_barras = dv.cod_barras
        LEFT JOIN variantes vr ON vr.id_variante = dv.id_variante
        WHERE dv.id_venta = ?
    ");
    $stmtProductos->execute([$id_venta]);
    $productos = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);

    // Obtener métodos de pago
    $stmtPagos = $pdo->prepare("
        SELECT metodo, monto, referencia
        FROM pagos_venta
        WHERE id_venta = ?
    ");
    $stmtPagos->execute([$id_venta]);
    $pagos = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'venta' => $venta,
        'productos' => $productos,
        'pagos' => $pagos
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
