<?php
session_start();
require_once __DIR__ . "/../config/db.php";
header('Content-Type: application/json; charset=utf-8');

$response = ['status' => 'error', 'message' => 'Ocurrió un error inesperado.'];

// ===========================
// VALIDAR DATOS DEL CARRITO
// ===========================
if (empty($_POST['cart_data'])) {
    echo json_encode(['status' => 'error', 'message' => 'No se recibieron datos del carrito.']);
    exit;
}

$cart = json_decode($_POST['cart_data'], true);
if (!$cart || !is_array($cart) || count($cart) === 0) {
    echo json_encode(['status' => 'error', 'message' => 'El carrito está vacío o tiene formato incorrecto.']);
    exit;
}

// ===========================
// DATOS DEL FORMULARIO
// ===========================
$tipo_pago = $_POST['tipo_pago'] ?? 'EFECTIVO';
$id_cliente = !empty($_POST['id_cliente']) ? $_POST['id_cliente'] : null;

// Obtener id_empleado desde sesión
$id_usuario = $_SESSION['usuario_id'] ?? null;
$id_empleado = null;

if ($id_usuario) {
    $stmtEmp = $pdo->prepare("SELECT id_empleado FROM usuarios WHERE id_usuario = ?");
    $stmtEmp->execute([$id_usuario]);
    $id_empleado = $stmtEmp->fetchColumn();
}

try {
    $pdo->beginTransaction();

    // 1️⃣ Calcular total
    $total = 0;
    foreach ($cart as $item) {
        $precio = floatval($item['price'] ?? 0);
        $cantidad = intval($item['quantity'] ?? 1);
        $total += $precio * $cantidad;
    }

    // 2️⃣ Insertar venta
    $stmtVenta = $pdo->prepare("
        INSERT INTO ventas (fecha, tipo_pago, pago_total, id_cliente, id_empleado)
        VALUES (NOW(), ?, ?, ?, ?)
    ");
    $stmtVenta->execute([$tipo_pago, $total, $id_cliente, $id_empleado]);
    $id_venta = $pdo->lastInsertId();

    // 3️⃣ Preparar consultas
    $stmtDetalle = $pdo->prepare("
        INSERT INTO detalle_ventas (cantidad, precio_unitario, id_venta, cod_barras, id_variante)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmtUpdateVariante = $pdo->prepare("
        UPDATE variantes SET cantidad = GREATEST(cantidad - ?, 0)
        WHERE id_variante = ?
    ");
    $stmtUpdateProducto = $pdo->prepare("
        UPDATE productos SET cantidad = GREATEST(cantidad - ?, 0)
        WHERE cod_barras = ?
    ");

    // 4️⃣ Insertar cada detalle
    foreach ($cart as $item) {
        $cantidad = intval($item['quantity'] ?? 1);
        $precio_unitario = floatval($item['price'] ?? 0);
        $cod_barras = $item['cod_barras'] ?? $item['code'] ?? null;
        $id_variante = $item['id_variante'] ?? null;

        // Buscar id_variante si no viene
        if (!$id_variante && !empty($item['variants']) && !empty($item['size']) && !empty($item['color'])) {
            foreach ($item['variants'] as $v) {
                $matchTalla = ($v['talla'] ?? $v['size'] ?? null);
                $matchColor = ($v['color'] ?? null);

                if ($matchTalla === $item['size'] && $matchColor === $item['color']) {
                    $id_variante = $v['id'] ?? $v['id_variante'] ?? null;
                    $cod_barras = $v['cod_barras'] ?? $cod_barras;
                    break;
                }
            }
        }

        // Insertar detalle
        $stmtDetalle->execute([$cantidad, $precio_unitario, $id_venta, $cod_barras, $id_variante]);

        // Actualizar stock
        if (!empty($id_variante)) {
            $stmtUpdateVariante->execute([$cantidad, $id_variante]);
        } elseif (!empty($cod_barras)) {
            $stmtUpdateProducto->execute([$cantidad, $cod_barras]);
        }
    }

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Venta registrada correctamente.',
        'total' => number_format($total, 2),
        'id_venta' => $id_venta
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log('Error al procesar la venta: ' . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al procesar la venta. Intente nuevamente.'
    ]);
}
?>
