<?php
require_once __DIR__ . '/../config/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<p class='text-red-500 font-semibold p-4'>ID de venta inválido.</p>";
    exit;
}

$id = intval($_GET['id']);

// Obtener venta
$stmtVenta = $pdo->prepare("
    SELECT v.*, 
           c.nombre AS cliente_nombre, 
           c.apellido_paterno, 
           c.apellido_materno
    FROM ventas v
    LEFT JOIN clientes c ON v.id_cliente = c.id_cliente
    WHERE v.id_venta = :id
    LIMIT 1
");
$stmtVenta->execute(['id' => $id]);
$venta = $stmtVenta->fetch(PDO::FETCH_ASSOC);

if (!$venta) {
    echo "<p class='text-red-500 font-semibold p-4'>Venta no encontrada.</p>";
    exit;
}

// Obtener detalles
$stmtDet = $pdo->prepare("
    SELECT dv.*, p.nom_producto
    FROM detalle_ventas dv
    LEFT JOIN productos p ON dv.cod_barras = p.cod_barras
    WHERE dv.id_venta = :id
");
$stmtDet->execute(['id' => $id]);
$detalles = $stmtDet->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- HEADER -->
<div class="modal-header bg-gray-900 text-white">
    <h5 class="modal-title text-lg font-semibold">
        Venta #<?= htmlspecialchars($venta['id_venta']) ?>
    </h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>

<!-- BODY -->
<div class="modal-body animate-fade-in">

    <div class="bg-gray-100 rounded-xl p-4 shadow-inner mb-4">
        <p class="text-sm mb-1">
            <span class="font-semibold text-gray-700">Cliente:</span>
            <?= htmlspecialchars($venta['cliente_nombre'] . " " . $venta['apellido_paterno'] . " " . $venta['apellido_materno']) ?>
        </p>

        <p class="text-sm mb-1">
            <span class="font-semibold text-gray-700">Fecha:</span>
            <?= htmlspecialchars($venta['fecha']) ?>
        </p>

        <p class="text-sm">
            <span class="font-semibold text-gray-700">Total venta:</span>
            <span class="text-green-600 font-bold">$<?= number_format($venta['total'], 2) ?></span>
        </p>
    </div>

    <h6 class="text-gray-800 font-bold mb-3 text-sm">Productos</h6>

    <?php if (count($detalles) === 0): ?>
        <p class="text-gray-500 italic">No hay productos registrados en esta venta.</p>
    <?php else: ?>
        <div class="overflow-x-auto rounded-lg shadow border border-gray-200">
            <table class="min-w-full bg-white text-sm">
                <thead class="bg-gray-900 text-white text-left">
                    <tr>
                        <th class="py-2 px-3">Producto</th>
                        <th class="py-2 px-3">Cantidad</th>
                        <th class="py-2 px-3">P. Unitario</th>
                        <th class="py-2 px-3">Subtotal</th>
                        <th class="py-2 px-3">Descuento</th>
                        <th class="py-2 px-3">Total</th>
                    </tr>
                </thead>
                <tbody>

                    <?php foreach ($detalles as $d): ?>

                        <?php
                        // Subtotal por producto
                        $subtotal = $d['precio_unitario'] * $d['cantidad'];

                        // Proporción del subtotal general
                        $proporcion = $subtotal > 0 && $venta['subtotal'] > 0 
                            ? $subtotal / $venta['subtotal'] 
                            : 0;

                        // Descuento proporcional
                        $descuentoProducto = $venta['descuento_general'] * $proporcion;

                        // Porcentaje del descuento
                        $porcentaje = $subtotal > 0 
                            ? ($descuentoProducto / $subtotal) * 100 
                            : 0;

                        // Total final
                        $totalFinal = $subtotal - $descuentoProducto;
                        ?>

                        <tr class="border-t border-gray-200 hover:bg-gray-50 transition">
                            <td class="py-2 px-3"><?= htmlspecialchars($d['nom_producto']) ?></td>

                            <td class="py-2 px-3"><?= htmlspecialchars($d['cantidad']) ?></td>

                            <td class="py-2 px-3">
                                $<?= number_format($d['precio_unitario'], 2) ?>
                            </td>

                            <td class="py-2 px-3 font-medium text-gray-700">
                                $<?= number_format($subtotal, 2) ?>
                            </td>

                            <td class="py-2 px-3 text-red-600 font-semibold">
                                <?= number_format($porcentaje, 1) ?>%
                                <div class="text-xs text-gray-500">
                                    (-$<?= number_format($descuentoProducto, 2) ?>)
                                </div>
                            </td>

                            <td class="py-2 px-3 font-bold text-gray-900">
                                $<?= number_format($totalFinal, 2) ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>


<!-- ANIMACIÓN -->
<style>
    .animate-fade-in {
        animation: fadeIn 0.25s ease-out;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(4px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
