<?php

require_once __DIR__ . "/../config/db.php";

$sql = "SELECT v.id_venta, v.fecha, v.tipo_pago, v.pago_total,
               CONCAT(e.nombre, ' ', e.apellido_paterno, ' ', e.apellido_materno) AS nombre_empleado
        FROM ventas v
        LEFT JOIN empleados e ON v.id_empleado = e.id_empleado
        ORDER BY v.fecha DESC";

$stmt = $pdo->query($sql);
$ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ventas Realizadas</title>
<script src="https://cdn.tailwindcss.com"></script>

</head>
<body class="bg-gray-100 p-6">

<h1 class="text-2xl font-bold mb-6">Ventas Realizadas</h1>

<div class="overflow-x-auto">
<table class="w-full text-left border-collapse">
    <thead>
        <tr>
            <th>ID Venta</th>
            <th>Empleado</th>
            <th>Fecha</th>
            <th>Total</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($ventas as $v): ?>
        <tr>
            <td><?= $v['id_venta'] ?></td>
            <td><?= htmlspecialchars($v['nombre_empleado']) ?></td>
            <td><?= $v['fecha'] ?></td>
            <td>$<?= number_format($v['pago_total'],2) ?></td>
            <td>
            <button onclick="window.verDetalleVenta(<?= $v['id_venta'] ?>)" 
        class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">
    Ver Detalle
</button>



<form style="display:inline;" method="POST" action="scripts/eliminar_venta.php">
    <input type="hidden" name="id_venta" value="<?= $v['id_venta'] ?>">
    <button type="button" class="delete-sale-btn px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600"
        data-id="<?= $v['id_venta'] ?>">
        Eliminar
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
        <h2 class="text-xl font-semibold mb-4 text-gray-800 text-center">Detalle de Venta</h2>
        <div id="venta-detalles" class="space-y-2 max-h-80 overflow-y-auto"></div>
        <div class="flex justify-end mt-4">
            <button id="close-venta-modal" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Cerrar</button>
        </div>
    </div>
</div>



<script>
document.querySelectorAll('.delete-sale-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const idVenta = btn.dataset.id;
        if (!idVenta) {
            alert("❌ No se pudo obtener el ID de la venta.");
            return;
        }
        if (!confirm("¿Seguro que deseas eliminar esta venta?")) return;

        console.log("ID a eliminar:", idVenta);

        fetch('scripts/eliminar_venta.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id_venta: idVenta})
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert("✅ Venta eliminada correctamente");
                location.reload();
            } else {
                alert("❌ Error: " + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert("❌ Error al eliminar la venta");
        });
    });
});

</script>

<script src="../src/scripts/modal.js"></script>
</body>
</html>
