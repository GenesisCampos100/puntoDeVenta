<?php
require_once __DIR__ . "/../config/db.php";

if($_SERVER['REQUEST_METHOD']==='POST'){
    $cart = json_decode($_POST['cart_data'], true);
    $payments = json_decode($_POST['payments_data'], true);

    if(!$cart || !$payments) die("Datos inválidos");

    $total = 0;
    foreach($cart as $item) $total += $item['price']*$item['quantity'];

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO VENTAS(fecha,pago_total,tipo_pago,id_empleado,id_cliente) VALUES(NOW(),?,?,NULL,NULL)");
        $stmt->execute([ $total, json_encode($payments) ]);
        $id_venta = $pdo->lastInsertId();

        $stmt_detalle = $pdo->prepare("INSERT INTO DETALLE_VENTAS(id_venta,cod_barras,cantidad,precio_unitario,talla,color,descuento)
            VALUES(:id_venta,:cod_barras,:cantidad,:precio_unitario,:talla,:color,:descuento)");

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

        $pdo->commit();
        echo "<script>alert('Venta registrada con éxito'); window.location='nueva_venta.php';</script>";

    } catch(PDOException $e){
        $pdo->rollBack();
        die("Error al procesar la venta: ".$e->getMessage());
    }
}
?>
