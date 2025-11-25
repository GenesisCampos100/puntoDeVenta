<?php 
    require_once __DIR__ . "/../config/db.php";

    $id_venta = $_GET['id_venta'] ?? 0;

    if (!$id_venta) {
        die("ID de venta no proporcionado.");
    }

    //Obtener detalles de la venta, empleado y cliente
    $stmtVenta = $pdo->prepare("
        SELECT 
            v.*,
            e.nombre AS emp_nombre,
            e.apellido_paterno AS emp_ap,
            c.nombre AS cli_nombre,
            c.apellido_paterno AS cli_ap
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

    // Obtener detalles de los productos vendidos
    $stmtDetalles = $pdo->prepare("
        SELECT
            dv.*,
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

    // Obtener el tipo de pago
    $stmtPago = $pdo->prepare("
        SELECT 
            metodo,
            monto,
            referencia
        FROM pagos_venta
        WHERE id_venta = ?
    ");

    $stmtPago->execute([$id_venta]);
    $pagos = $stmtPago->fetchAll(PDO::FETCH_ASSOC);

    $tipoPago = $pagos ?: [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket</title>
    <style>
        body {
            width: 80mm;
            font-family: monospace;
            font-size: 12px;
        }

        .center {
            text-align: center;
        }

        hr {
            border: 1px dashed #000;
        }

        table { 
            width: 100%; 
            border-collapse: collapse; 
        }

        td, th { 
            padding: 2px; 
        }
    
        .totales { 
            text-align: right; 
        }
    
        /*.desc-prod { 
            font-size: 10px; color: #555; 
        }*/

        /* Layout sin tablas para pagos (flex, más compacto) */
        .pagos {
            width: 100%;
              margin-top: 2px; /* pequeño espacio respecto al texto anterior */
        }
        
        .pago {
            display: flex;
            justify-content: space-between;
            gap: 4px;
            align-items: flex-start;
            padding: 0 0 1px 0; /* un poco de espacio entre filas */
        }

        .pago .metodo {
            text-align: left;
            flex: 1 1 60%;
            word-break: break-word;
        }

        .pago .monto {
            text-align: right;
            flex: 0 0 40%;
            min-width: 50px;
        }

        .pago .ref { 
            display: block; 
            font-size: 9px; 
            margin-top: 1px; 
        }
                
        /* Información superior (ticket) ligeramente separada */
        .ticket-info { 
            margin: 0 0 3px 0;
            line-height: 1.08; 
        }
        
        /* Centrar columnas Cant (1), Productos (2), Precio (3), Descuento (4) y Subt (5) */
        table th:nth-child(1), table td:nth-child(1),
        table th:nth-child(2), table td:nth-child(2),
        table th:nth-child(3), table td:nth-child(3),
        table th:nth-child(4), table td:nth-child(4),
        table th:nth-child(5), table td:nth-child(5) {
            text-align: center;
        }
    </style>
</head>
<body>
    <h1 class = "center">PRISMA</h1>

    <p class = "center">
        Km 20.5 de la Carretera Manzanillo-Cihuatlán,
        Colonia El Naranjo, Código Postal 28868,
        Manzanillo, Colima, México.
    </p>

    <p>
        Ticket #<?= $venta['id_venta'] ?><br>
        Fecha: <?= date('d/m/Y H:i', strtotime($venta['fecha'])) ?><br>
        Cajero: <?= $venta['emp_nombre'] . ' ' . $venta['emp_ap'] ?><br>
        <?php if (!empty($venta['id_cliente'])): ?>
            Cliente: <?= htmlspecialchars($venta['cli_nombre'] . ' ' . $venta['cli_ap']) ?><br>
        <?php endif; ?>
    </p>

    <hr>

    <table>
        <tr>
            <th>Cant</th>
            <th>Productos</th>
            <th>Precio</th>
            <th>Descuento</th>
            <th>Subt</th>
        </tr>

        <?php
            $totalDescuentoProductos = 0;
            $calculatedSubtotal = 0; // subtotal calculado a partir de los productos

                foreach ($detalles as $d):
                    $descProd = floatval($d['descuento'] ?? 0);
                    $subtotal = ($d['cantidad'] * $d['precio_unitario']) - $descProd;
                    $totalDescuentoProductos += $descProd;
                    $calculatedSubtotal += $subtotal;
        ?>

        <tr>
            <td><?= $d['cantidad'] ?></td>
            <td>
                <?= htmlspecialchars($d['nom_producto']) ?><br>
                <small><?= $d['talla'] ?> / <?= $d['color'] ?></small>
            </td>
            <td>$<?= number_format($d['precio_unitario'],2) ?></td>
            <td>
                <?php if ($descProd > 0): ?>
                    <div class="desc-prod">$<?= number_format($descProd,2) ?></div>
                <?php endif; ?>
            </td>
            <td>$<?= number_format($subtotal,2) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <hr>

    <p class="totales">Subtotal: $<?= number_format($calculatedSubtotal ?? 0, 2) ?></p>
    <p class="totales">Descuento general: $<?= number_format($venta['descuento_general'], 2) ?></p>
    <p class="totales"><strong>Total a pagar: $<?= number_format($venta['total'], 2) ?></strong></p>

    <hr>

    <p class="ticket-info">
        Método de pago: 
        <?php if (empty($tipoPago)): ?>
            DESCONOCIDO
        <?php else: ?>
            <div class="pagos">
                <?php foreach ($tipoPago as $p): ?>
                    <div class="pago">
                        <div class="metodo"><?= htmlspecialchars($p['metodo']) ?></div>
                        <div class="monto">
                            <?= '$' . number_format($p['monto'], 2) ?>
                            <?php if (!empty($p['referencia'])): ?>
                                <span class="ref">Ref: <?= htmlspecialchars(substr($p['referencia'] ?? '', -4)) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </p>

    <div class = "center">
        <p>"ESTE TICKET NO ES COMPROBANTE FISCAL"</p>
        <p>
            Para solicitar factura, por favor envíe su información fiscal al correo:<br>
            <strong>prisma_pos@outlook.com</strong>
        </p>
        <p>Su solicitud será atendida dentro de las proximas 24 horas hábiles.</p>
        <p>Cuenta con 30 días naturales para solicitar su factura.</p>
    </div>

    <hr>

    <p class="center">¡Gracias por su compra!</p>
</body>
</html>