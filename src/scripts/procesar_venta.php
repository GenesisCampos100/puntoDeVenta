<?php
require_once __DIR__ . "/../config/db.php";
session_start();

if (!isset($_POST['cart_data'])) {
    die("No se recibieron datos del carrito.");
}

$cart = json_decode($_POST['cart_data'], true);
if (!$cart || count($cart) === 0) {
    die("El carrito está vacío.");
}

$tipo_pago = $_POST['tipo_pago'] ?? 'EFECTIVO';
$id_cliente = null;
$id_empleado = $_SESSION['usuario_id'] ?? 'EMP-DEFAULT';

try {
    $conn->beginTransaction();

    // Calcular total
    $total = 0;
    foreach ($cart as $item) {
        $precio = floatval($item['price'] ?? 0);
        $cantidad = intval($item['quantity'] ?? 1);
        $total += $precio * $cantidad;
    }

    // Insertar venta
    $stmt = $conn->prepare("INSERT INTO ventas (fecha, tipo_pago, pago_total, id_cliente, id_empleado)
                            VALUES (NOW(), ?, ?, ?, ?)");
    $stmt->execute([$tipo_pago, $total, $id_cliente, $id_empleado]);
    $id_venta = $conn->lastInsertId();

    // Insertar detalles y actualizar inventario
    $stmt_detalle = $conn->prepare("INSERT INTO detalle_ventas 
        (cantidad, precio_unitario, id_venta, cod_barras, id_variante)
        VALUES (?, ?, ?, ?, ?)");

    foreach ($cart as $item) {
        $cantidad = intval($item['quantity'] ?? 1);
        $precio_unitario = floatval($item['price'] ?? 0);
        $cod_barras = $item['barcode'] ?? null;
        $id_variante = $item['variant_id'] ?? null;

        $stmt_detalle->execute([$cantidad, $precio_unitario, $id_venta, $cod_barras, $id_variante]);

        // 🔽 Descontar inventario
        if ($id_variante) {
            $stmt_update = $conn->prepare("UPDATE variantes SET cantidad = GREATEST(cantidad - ?, 0) WHERE id = ?");
            $stmt_update->execute([$cantidad, $id_variante]);
        } elseif ($cod_barras) {
            $stmt_update = $conn->prepare("UPDATE productos SET cantidad = GREATEST(cantidad - ?, 0) WHERE cod_barras = ?");
            $stmt_update->execute([$cantidad, $cod_barras]);
        }
    }

    $conn->commit();

    echo "
    <script>
      localStorage.removeItem('cart');
      alert('✅ Venta registrada con éxito. Total: $" . number_format($total, 2) . "');
      window.location.href = 'nueva_venta.php';
    </script>";
    
} catch (Exception $e) {
    $conn->rollBack();
    die('Error al procesar la venta: ' . $e->getMessage());
}
?>
