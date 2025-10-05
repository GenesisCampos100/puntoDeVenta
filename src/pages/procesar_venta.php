<?php
require_once "conexion.php";
session_start();

header('Content-Type: application/json');

try {
    // Verificar si hay datos POST válidos
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Método no permitido.");
    }

    $cliente = trim($_POST['cliente'] ?? '');
    $productos = json_decode($_POST['productos'] ?? '[]', true);
    $pagos = json_decode($_POST['pagos'] ?? '[]', true);
    $subtotal = floatval($_POST['subtotal'] ?? 0);
    $descuento = floatval($_POST['descuento'] ?? 0);
    $total = floatval($_POST['total'] ?? 0);

    if (empty($productos)) throw new Exception("No hay productos en la venta.");
    if (empty($pagos)) throw new Exception("Debe seleccionarse al menos un método de pago.");

    // Validar montos
    $sumaPagos = array_sum(array_column($pagos, 'monto'));
    if (abs($sumaPagos - $total) > 0.05) {
        throw new Exception("El total de los pagos no coincide con el total de la venta.");
    }

    // Iniciar transacción
    $conexion->begin_transaction();

    // Insertar venta
    $stmtVenta = $conexion->prepare("
        INSERT INTO ventas (cliente, fecha, subtotal, descuento, total)
        VALUES (?, NOW(), ?, ?, ?)
    ");
    $stmtVenta->bind_param("sddd", $cliente, $subtotal, $descuento, $total);
    $stmtVenta->execute();

    $venta_id = $conexion->insert_id;

    // Insertar detalle de venta
    $stmtDetalle = $conexion->prepare("
        INSERT INTO detalle_ventas (id_venta, codigo_barras, nombre_producto, cantidad, precio_unitario, descuento, talla, color)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($productos as $p) {
        $codigo = $p['cod_barras'] ?? '';
        $nombre = $p['name'] ?? '';
        $cantidad = intval($p['quantity'] ?? 0);
        $precio = floatval($p['price'] ?? 0);
        $desc = floatval($p['descuento'] ?? 0);
        $talla = $p['size'] ?? '';
        $color = $p['color'] ?? '';

        if ($cantidad <= 0 || $precio <= 0) {
            throw new Exception("Cantidad o precio inválido en un producto.");
        }

        $stmtDetalle->bind_param("issiddss", $venta_id, $codigo, $nombre, $cantidad, $precio, $desc, $talla, $color);
        $stmtDetalle->execute();
    }

    // Insertar métodos de pago
    $stmtPago = $conexion->prepare("
        INSERT INTO pagos (id_venta, metodo, monto)
        VALUES (?, ?, ?)
    ");

    foreach ($pagos as $pago) {
        $metodo = $pago['metodo'];
        $monto = floatval($pago['monto']);
        if ($monto <= 0) throw new Exception("Monto inválido en un método de pago.");
        $stmtPago->bind_param("isd", $venta_id, $metodo, $monto);
        $stmtPago->execute();
    }

    // Confirmar todo
    $conexion->commit();

    echo json_encode([
        "status" => "success",
        "message" => "Venta procesada correctamente.",
        "venta_id" => $venta_id
    ]);

} catch (Exception $e) {
    if ($conexion->connect_errno === 0) {
        $conexion->rollback();
    }

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>
