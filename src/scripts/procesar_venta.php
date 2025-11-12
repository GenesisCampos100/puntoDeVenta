<?php
session_start();
require_once __DIR__ . "/../config/db.php";

if (!isset($_POST['cart_data'])) {
    die("No se recibieron datos del carrito.");
}

$cart = json_decode($_POST['cart_data'], true);
if (!$cart || count($cart) === 0) {
    die("El carrito está vacío.");
}

$tipo_pago = $_POST['tipo_pago'] ?? 'EFECTIVO';
$id_cliente = null;

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

    // Calcular total
    $total = 0;
    foreach ($cart as $item) {
        $total += floatval($item['price'] ?? 0) * intval($item['quantity'] ?? 1);
    }

    // Insertar venta
    $stmt = $pdo->prepare("
        INSERT INTO ventas (fecha, tipo_pago, pago_total, id_cliente, id_empleado) 
        VALUES (NOW(), ?, ?, ?, ?)
    ");
    $stmt->execute([$tipo_pago, $total, $id_cliente, $id_empleado]);
    $id_venta = $pdo->lastInsertId();

    // Insertar detalle ventas y actualizar inventario
    $stmt_detalle = $pdo->prepare("
    INSERT INTO detalle_ventas (cantidad, precio_unitario, id_venta, cod_barras, id_variante)
    VALUES (?, ?, ?, ?, ?)
");

foreach ($cart as $item) {
    $cantidad = intval($item['quantity'] ?? 1);
    $precio_unitario = floatval($item['price'] ?? 0);
    $cod_barras = $item['cod_barras'] ?? $item['code'] ?? $item['barcode'] ?? null;
    $id_variante = $item['id_variante'] ?? $item['variant_id'] ?? null;


    // 🧠 Si el carrito contiene variantes (por talla/color), buscar el id_variante correspondiente
     if (!$id_variante && !empty($item['variants']) && !empty($item['size']) && !empty($item['color'])) {
        foreach ($item['variants'] as $v) {
            if (($v['talla'] ?? $v['size'] ?? null) === $item['size']
             && ($v['color'] ?? null) === $item['color']) {
                $id_variante = $v['id'] ?? $v['id_variante'] ?? null;
                $cod_barras = $v['cod_barras'] ?? $cod_barras;
                break;
            }
        }
    }

    // 🔹 Registrar detalle
    $stmt_detalle->execute([$cantidad, $precio_unitario, $id_venta, $cod_barras, $id_variante]);

    // 🔁 Actualizar inventario según exista variante o no
    if (!empty($id_variante)) {
        $stmt_update = $pdo->prepare("
            UPDATE variantes 
            SET cantidad = GREATEST(cantidad - ?, 0)
            WHERE id_variante = ?
        ");
        $stmt_update->execute([$cantidad, $id_variante]);
    } elseif (!empty($cod_barras)) {
        $stmt_update = $pdo->prepare("
            UPDATE productos 
            SET cantidad = GREATEST(cantidad - ?, 0)
            WHERE cod_barras = ?
        ");
        $stmt_update->execute([$cantidad, $cod_barras]);
    }
}




    $pdo->commit();

    echo "<script>
        localStorage.removeItem('cart');
        alert('✅ Venta registrada con éxito. Total: $" . number_format($total, 2) . "');
        window.location.href = '../index.php?view=ventas';
    </script>";

} catch (Exception $e) {
    $pdo->rollBack();
    die('Error al procesar la venta: ' . $e->getMessage());
}
?>