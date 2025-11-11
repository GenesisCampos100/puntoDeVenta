<?php
// src/api/product_row.partial.php
// Renderiza una sola fila de producto (se llama dentro del foreach principal en inventario_api.php)
?>
<tr class="border-b hover:bg-gray-50 transition" data-id="<?= htmlspecialchars($producto['id_producto']) ?>">
    <td class="px-4 py-2 text-sm font-medium text-gray-700"><?= htmlspecialchars($producto['nom_producto']) ?></td>
    <td class="px-4 py-2 text-sm text-gray-600"><?= htmlspecialchars($producto['cod_barras']) ?></td>
    <td class="px-4 py-2 text-sm"><?= htmlspecialchars($producto['nombre_categoria']) ?></td>
    <td class="px-4 py-2 text-sm text-center"><?= (int)$producto['cantidad'] ?></td>
    <td class="px-4 py-2 text-center flex justify-center gap-2">
        <!-- Botón Ver Detalles -->
        <button class="btn-detalle text-blue-500 hover:text-blue-700" data-id="<?= $producto['id_producto'] ?>" title="Ver detalles">
            <i data-lucide="eye"></i>
        </button>
        <!-- Botón Ajustar Stock -->
        <button class="btn-ajuste text-green-500 hover:text-green-700" data-id="<?= $producto['id_producto'] ?>" title="Ajustar stock">
            <i data-lucide="settings"></i>
        </button>
        <!-- Botón Activar/Desactivar -->
        <button class="btn-toggle text-red-500 hover:text-red-700" data-id="<?= $producto['id_producto'] ?>" data-estado="<?= $producto['is_active'] ?>" title="Desactivar producto">
            <i data-lucide="archive"></i>
        </button>
    </td>
</tr>
