<?php
    require_once __DIR__ . '/../config/db.php';

    $id_venta = $_GET['id_venta'] ?? 0;

    if (!$id_venta) {
        die("ID de venta no proporcionado.");
    }

    // 🔹 Obtener los datos de la venta
    $stmtVenta = $pdo->prepare("
        SELECT v.*, 
            e.id_empleado AS num_empleado,
            e.nombre AS nom_empleado,
            e.apellido_paterno AS ap_empleado,
            c.nombre AS nom_cliente,
            c.apellido_paterno AS ap_cliente
        FROM ventas v
        LEFT JOIN empleados e ON e.id_empleado = v.id_empleado
        LEFT JOIN clientes c ON c.id_cliente = v.id_cliente
        WHERE v.id_venta = ?
    ");
    $stmtVenta->execute([$id_venta]);
    $venta = $stmtVenta->fetch(PDO::FETCH_ASSOC);

    if (!$venta) {
        die("Venta no encontrada.");
    }

    // 🔹 Obtener los detalles de la venta
    $stmtDetalles = $pdo->prepare("
        SELECT dv.*, 
            p.nom_producto,
            COALESCE(vr.talla, p.talla) AS talla,
            COALESCE(vr.color, p.color) AS color
        FROM detalle_ventas dv
        LEFT JOIN productos p ON p.cod_barras = dv.cod_barras
        LEFT JOIN variantes vr ON vr.id_variante = dv.id_variante
        WHERE dv.id_venta = ?
    ");
    $stmtDetalles->execute([$id_venta]);
    $detalles = $stmtDetalles->fetchAll(PDO::FETCH_ASSOC);

    // 🔹 Determinar cliente
    $cliente = "Público en general";
    if (!empty($venta['id_cliente'])) {
        $cliente = $venta['nom_cliente'] . ' ' . $venta['ap_cliente'];
    }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket de Venta</title>
    <style>
        body {
            width: 80mm;
            font-family: monospace;
            font-size: 12px;
            color: #000;
        }

        .centrado { 
            text-align: center; 
        }

        .ticket { 
            width: 100%; 
        }

        hr { 
            border: 1px dashed #000; 
        }

        .totales { 
            text-align: right; 
        }

        table { 
            width: 100%; 
            border-collapse: collapse; 
        }

        td, th { 
            padding: 2px; 
            text-align: left; 
        }
    </style>
</head>
<body onload="window.print();">
    <div class="ticket">
        <div class="centrao">
            <h2 class="centrado">PRISMA</h2>
            <p>
                Ticket #<?= $venta['id_venta'] ?><br>
                Fecha: <?= date('d/m/Y H:i', strtotime($venta['fecha'])) ?><br>
                Nº Empleado: <?= $venta['num_empleado'] ?><br>
                Cajero: <?= htmlspecialchars($venta['nom_empleado'] . ' ' . $venta['ap_empleado']) ?><br>
                Cliente: <?= htmlspecialchars($cliente) ?><br>
                Tipo de pago: <?= htmlspecialchars($venta['tipo_pago']) ?>
            </p>
        </div>

        <hr>
        <table>
            <tr>
                <th>Cant</th>
                <th>Producto</th>
                <th>Precio</th>
                <th>Subt</th>
            </tr>
            <?php 
            $totalDescuento = 0;
            foreach ($detalles as $d): 
                $desc = floatval($d['descuento'] ?? 0);
                $subtotal = ($d['cantidad'] * $d['precio_unitario']) - $desc;
                $totalDescuento += $desc;
            ?>
            <tr>
                <td><?= $d['cantidad'] ?></td>
                <td>
                    <?= htmlspecialchars($d['nom_producto']) ?><br>
                    <small><?= $d['talla'] ?> / <?= $d['color'] ?></small>
                </td>
                <td>$<?= number_format($d['precio_unitario'], 2) ?></td>
                <td>$<?= number_format($subtotal, 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>

        <hr>
        <p class="totales">Descuento productos: $<?= number_format($totalDescuento, 2) ?></p>
        <p class="totales">Descuento general: $<?= number_format($venta['descuento_general'], 2) ?></p>
        <p class="totales"><strong>Total a pagar: $<?= number_format($venta['pago_total'], 2) ?></strong></p>

        <hr>
        <div class="centrado">
            <p>¡Gracias por su compra!</p>
        </div>
    </div>
</body>
</html>