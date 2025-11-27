<?php
session_start();
require_once __DIR__ . "/../config/db.php";
header('Content-Type: application/json; charset=utf-8');

$response = ['status' => 'error', 'message' => 'Ocurrió un error inesperado.'];

if (empty($_POST['cart_data'])) {
    echo json_encode(['status' => 'error', 'message' => 'No se recibieron datos del carrito.']);
    exit;
}

$cart = json_decode($_POST['cart_data'], true);
if (!$cart || !is_array($cart) || count($cart) === 0) {
    echo json_encode(['status' => 'error', 'message' => 'El carrito está vacío o tiene formato incorrecto.']);
    exit;
}

// Datos de cliente
$id_cliente = !empty($_POST['id_cliente']) ? $_POST['id_cliente'] : null;

// Descuento general
$descuento_general = floatval($_POST['descuento_general'] ?? 0);
$tipo_desc_general = $_POST['tipo_descuento_general'] ?? 'percent';

// Usuario / empleado
$id_usuario = $_SESSION['usuario_id'] ?? null;
$id_empleado = null;
if ($id_usuario) {
    $stmtEmp = $pdo->prepare("SELECT id_empleado FROM usuarios WHERE id_usuario = ?");
    $stmtEmp->execute([$id_usuario]);
    $id_empleado = $stmtEmp->fetchColumn();
}

try {
    $pdo->beginTransaction();

    // Calcular subtotal y descuentos individuales
    $subtotal = 0;
    $descuentos_individuales_total = 0;

    foreach ($cart as $item) {
        $precio = floatval($item['price'] ?? 0);
        $cantidad = intval($item['quantity'] ?? 1);
        $subtotal += $precio * $cantidad;

        if (!empty($item['discount'])) {
            if ($item['discount']['type'] === 'percent') {
                $descuentos_individuales_total += ($precio * $cantidad) * ($item['discount']['value'] / 100);
            } else {
                $descuentos_individuales_total += floatval($item['discount']['value']);
            }
        }
    }

    // Descuento general
    $descuento_general_final = ($tipo_desc_general === 'percent') 
        ? ($subtotal - $descuentos_individuales_total) * ($descuento_general / 100)
        : $descuento_general;

    $total = $subtotal - $descuentos_individuales_total - $descuento_general_final;

    // Insertar venta (sin tipo_pago)
    $stmtVenta = $pdo->prepare("
        INSERT INTO ventas (fecha, subtotal, descuento_general, total, id_cliente, id_empleado)
        VALUES (NOW(), ?, ?, ?, ?, ?)
    ");
    $stmtVenta->execute([$subtotal, $descuento_general_final, $total, $id_cliente, $id_empleado]);
    $id_venta = $pdo->lastInsertId();

    // Preparar detalle
    $stmtDetalle = $pdo->prepare("
        INSERT INTO detalle_ventas (cantidad, precio_unitario, descuento, id_venta, cod_barras, id_variante)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmtUpdateVariante = $pdo->prepare("
        UPDATE variantes SET cantidad = GREATEST(cantidad - ?, 0) WHERE id_variante = ?
    ");
    $stmtUpdateProducto = $pdo->prepare("
        UPDATE productos SET cantidad = GREATEST(cantidad - ?, 0) WHERE cod_barras = ?
    ");

    foreach ($cart as $item) {
        $cantidad = intval($item['quantity'] ?? 1);
        $precio_unitario = floatval($item['price'] ?? 0);
        $cod_barras = $item['cod_barras'] ?? $item['code'] ?? null;
        $id_variante = $item['id_variante'] ?? null;

        $descuento_item = 0;
        if (!empty($item['discount'])) {
            if ($item['discount']['type'] === 'percent') {
                $descuento_item = ($precio_unitario * $cantidad) * ($item['discount']['value'] / 100);
            } else {
                $descuento_item = floatval($item['discount']['value']);
            }
        }

        // Insertar detalle
        $stmtDetalle->execute([$cantidad, $precio_unitario, $descuento_item, $id_venta, $cod_barras, $id_variante]);

        // Actualizar stock
        if (!empty($id_variante)) {
            $stmtUpdateVariante->execute([$cantidad, $id_variante]);
        } elseif (!empty($cod_barras)) {
            $stmtUpdateProducto->execute([$cantidad, $cod_barras]);
        }
    }

    // Insertar pagos
    // Insertar pagos
    $pagos = [];
    $metodo = $_POST['metodo'] ?? 'efectivo'; // Default a efectivo si no viene

    if ($metodo === 'efectivo') {
        $monto = floatval($_POST['monto_efectivo'] ?? $total);
        // Si el monto enviado es 0 o vacío, asumimos el total (aunque el frontend debería validar)
        if ($monto <= 0) $monto = $total;
        
        $pagos[] = [
            'metodo' => 'EFECTIVO',
            'monto' => $monto,
            'referencia' => null
        ];
    } elseif ($metodo === 'tarjeta') {
        $pagos[] = [
            'metodo' => 'TARJETA',
            'monto' => $total,
            'referencia' => $_POST['referencia_tarjeta'] ?? ''
        ];
    } elseif ($metodo === 'mixto') {
        $efectivo = floatval($_POST['mixto_efectivo'] ?? 0);
        $tarjeta = floatval($_POST['mixto_tarjeta'] ?? 0);
        
        // Validar que la suma coincida con el total (opcional, pero recomendado)
        // Por ahora confiamos en la validación del frontend, pero aseguramos que se guarden
        
        if ($efectivo > 0) {
            $pagos[] = [
                'metodo' => 'EFECTIVO',
                'monto' => $efectivo,
                'referencia' => null
            ];
        }
        
        if ($tarjeta > 0) {
            $pagos[] = [
                'metodo' => 'TARJETA',
                'monto' => $tarjeta,
                'referencia' => $_POST['mixto_referencia'] ?? ''
            ];
        }
        
        // Si por alguna razón ambos son 0 (error frontend), guardar como efectivo el total
        if (empty($pagos)) {
             $pagos[] = [
                'metodo' => 'EFECTIVO',
                'monto' => $total,
                'referencia' => 'Error mixto'
            ];
        }
    } else {
        // Fallback por si llega algo raro
        $pagos[] = [
            'metodo' => 'EFECTIVO',
            'monto' => $total,
            'referencia' => ''
        ];
    }

    $stmtPago = $pdo->prepare("
        INSERT INTO pagos_venta (metodo, monto, referencia, id_venta)
        VALUES (?, ?, ?, ?)
    ");
    foreach ($pagos as $p) {
        $monto = floatval($p['monto'] ?? 0);
        if ($monto <= 0) continue;
        $stmtPago->execute([$p['metodo'], $monto, $p['referencia'] ?? '', $id_venta]);
    }

    $pdo->commit();

    // Devolver JSON para que el frontend (fetch) lo procese correctamente
    echo json_encode([
        'success' => true,
        'message' => 'Venta registrada correctamente.',
        'id_venta' => $id_venta,
        'subtotal' => number_format($subtotal, 2),
        'descuento_general' => number_format($descuento_general_final, 2),
        'total' => number_format($total, 2)
    ]);
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'line' => $e->getLine(),
        'file' => $e->getFile()
    ]);
}
?>
