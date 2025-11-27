<?php
require_once __DIR__ . "/../config/db.php";


if($_SERVER['REQUEST_METHOD']==='POST'){

    $cart = json_decode($_POST['cart_data'], true);
    if(!$cart) die("Carrito inválido");

    /* ========== TOTAL DE LA VENTA ========== */
    $total = 0;
    foreach ($cart as $item) {
        $total += $item['price'] * $item['quantity'];
    }

    /* ========== PROCESAR PAGOS ========== */
    $pagos = [];
    $metodo = $_POST["metodo"];

    if ($metodo === "efectivo") {
        $pagos[] = [
            "metodo" => "EFECTIVO",
            "monto" => floatval($_POST["monto_efectivo"]),
            "referencia" => null
        ];

    } elseif ($metodo === "tarjeta") {
        $pagos[] = [
            "metodo" => "TARJETA",
            "monto" => $total,
            "referencia" => $_POST["referencia_tarjeta"]
        ];

    } elseif ($metodo === "mixto") {

        if (floatval($_POST["mixto_efectivo"]) > 0) {
            $pagos[] = [
                "metodo" => "EFECTIVO",
                "monto" => floatval($_POST["mixto_efectivo"]),
                "referencia" => null
            ];
        }

        if (floatval($_POST["mixto_tarjeta"]) > 0) {
            $pagos[] = [
                "metodo" => "TARJETA",
                "monto" => floatval($_POST["mixto_tarjeta"]),
                "referencia" => $_POST["mixto_referencia"]
            ];
        }
    }

    try {
        $pdo->beginTransaction();

        /* =====================
           INSERTAR VENTA
        ====================== */
        $stmt = $pdo->prepare("
            INSERT INTO VENTAS (fecha, pago_total, tipo_pago, id_empleado, id_cliente)
            VALUES (NOW(), ?, ?, NULL, ?)
        ");

        $tipoPagoBD = strtoupper($metodo); // EFECTIVO / TARJETA / MIXTO
        $stmt->execute([$total, $tipoPagoBD, $_POST["id_cliente"] ?? null]);
        $id_venta = $pdo->lastInsertId();

        /* =====================
           INSERTAR DETALLE
        ====================== */
        $stmt_detalle = $pdo->prepare("
            INSERT INTO DETALLE_VENTAS (id_venta, cod_barras, cantidad, precio_unitario, talla, color, descuento)
            VALUES (:id_venta, :cod_barras, :cantidad, :precio_unitario, :talla, :color, :descuento)
        ");

        foreach($cart as $item){
            $stmt_detalle->execute([
                ':id_venta'=>$id_venta,
                ':cod_barras'=>$item['name'],
                ':cantidad'=>$item['quantity'],
                ':precio_unitario'=>$item['price'],
                ':talla'=>$item['size'],
                ':color'=>$item['color'],
                ':descuento'=>0
            ]);
        }

        /* =====================
           INSERTAR PAGOS
        ====================== */
        $stmt_pago = $pdo->prepare("
            INSERT INTO PAGOS (metodo, monto, referencia, fecha_pago, id_venta)
            VALUES (?, ?, ?, NOW(), ?)
        ");

        foreach ($pagos as $p) {
            $stmt_pago->execute([
                $p["metodo"],
                $p["monto"],
                $p["referencia"],
                $id_venta
            ]);
        }

        $pdo->commit();
        echo json_encode(["success" => true, "id_venta" => $id_venta]);

    } catch(PDOException $e){
        $pdo->rollBack();
        die("Error al procesar la venta: ".$e->getMessage());
    }
}

?>