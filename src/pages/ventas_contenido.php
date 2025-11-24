<?php
require_once __DIR__ . '/../config/db.php';

// Consulta de ventas (ya no usamos tipo_pago ni pago_total directamente)
$sql = "
    SELECT 
        v.id_venta,
        v.fecha,
        v.total AS pago_total,
        CONCAT(e.nombre, ' ', e.apellido_paterno, ' ', e.apellido_materno) AS nombre_empleado
    FROM ventas v
    LEFT JOIN empleados e ON v.id_empleado = e.id_empleado
    ORDER BY v.fecha DESC
";
$stmt = $pdo->query($sql);
$ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= __('sales_made_title') ?></title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">

<h1 class="text-2xl font-bold mb-6"><?= __('sales_made_header') ?></h1>

<div class="overflow-x-auto">
<table class="w-full text-left border-collapse bg-white shadow rounded-lg">
    <thead class="bg-gray-200">
        <tr>
            <th class="px-4 py-2 border"><?= __('sale_id_col') ?></th>
            <th class="px-4 py-2 border"><?= __('employee_col') ?></th>
            <th class="px-4 py-2 border"><?= __('date_col') ?></th>
            <th class="px-4 py-2 border"><?= __('total_col') ?></th>
            <th class="px-4 py-2 border"><?= __('actions_col') ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($ventas as $v): ?>
        <tr class="border-b hover:bg-gray-50">
            <td class="px-4 py-2"><?= $v['id_venta'] ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($v['nombre_empleado']) ?></td>
            <td class="px-4 py-2"><?= $v['fecha'] ?></td>
            <td class="px-4 py-2">$<?= number_format($v['pago_total'],2) ?></td>
            <td class="px-4 py-2 space-x-2">
                <!-- Ver Detalle -->
                <button class="ver-detalle-btn px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600"
                        data-id="<?= $v['id_venta'] ?>">
                    <?= __('view_details_btn') ?>
                </button>

                <!-- Eliminar -->
                <form style="display:inline;" method="POST" action="scripts/eliminar_venta.php">
                    <input type="hidden" name="id_venta" value="<?= $v['id_venta'] ?>">
                    <button type="button" class="delete-sale-btn px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600"
                        data-id="<?= $v['id_venta'] ?>">
                        <?= __('delete_btn') ?>
                    </button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

<!-- Modal detalle de venta -->
<div id="venta-modal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-lg p-6 w-96">
        <h2 class="text-xl font-semibold mb-4 text-gray-800 text-center"><?= __('sale_details_title') ?></h2>
        <div id="venta-detalles" class="space-y-2 max-h-80 overflow-y-auto"></div>
        <div class="flex justify-end mt-4">
            <button id="close-venta-modal" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300"><?= __('close_btn') ?></button>
        </div>
    </div>
</div>

<script src="../src/scripts/modal.js"></script>
</body>
</html>