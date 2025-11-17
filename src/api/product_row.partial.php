<?php
// src/api/product_row.partial.php
// Render: Fila de un producto PRINCIPAL (con variantes o sin variantes)

$id         = htmlspecialchars($producto['cod_barras']);
$nombre     = htmlspecialchars($producto['nom_producto']);
$codigo     = htmlspecialchars($producto['cod_barras']);
$categoria  = htmlspecialchars($producto['nombre_categoria']);
$cantidad   = (int)$producto['cantidad'];
$estado     = (int)$producto['is_active'];
$precio     = number_format($producto['precio'], 2, '.', ',');
?>

<tr class="border-b hover:bg-gray-50 transition" data-tipo="producto" data-id="<?= $id ?>">
    
    <!-- Nombre -->
    <td class="px-4 py-2 text-sm font-medium text-gray-800">
        <?= $nombre ?>
    </td>

    <!-- Stock  -->
    <td class="px-4 py-2 text-sm text-gray-600">
        <?= $cantidad ?>
    </td>

    <!-- Categoría -->
    <td class="px-4 py-2 text-sm text-gray-600">
        <?= $categoria ?>
    </td>

    <!-- Precio  -->
    <td class="px-4 py-2 text-sm text-center">
        <?= $precio ?>
    </td>

    <!-- Acciones -->
    <td class="px-4 py-2">
        <div class="flex items-center justify-center gap-3">

            <!-- Ver detalles -->
            <button 
                class="btn-detalle w-9 h-9 flex items-center justify-center rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition shadow-sm"
                data-id="<?= $id ?>"
                data-tipo="producto"
                data-nombre="<?= $nombre ?>"
                title="Ver detalles"
            >
                <i data-lucide="eye" class="w-5 h-5"></i>
            </button>

            <!-- Ajustar stock -->
            <button 
                class="btn-ajuste w-9 h-9 flex items-center justify-center rounded-lg bg-green-50 text-green-600 hover:bg-green-100 transition shadow-sm"
                data-id="<?= $id ?>"
                data-tipo="producto"
                data-nombre="<?= $nombre ?>"
                title="Ajustar stock"
            >
                <i data-lucide="settings" class="w-5 h-5"></i>
            </button>

            <!-- Activar / Desactivar -->
            <button 
                class="btn-toggle w-9 h-9 flex items-center justify-center rounded-lg bg-gray-50 hover:bg-gray-100 transition shadow-sm
                <?= $estado ? 'text-red-600' : 'text-green-600' ?>"
                data-id="<?= $id ?>"
                data-tipo="producto"
                data-estado="<?= $estado ?>"
                data-nombre="<?= $nombre ?>"
                title="<?= $estado ? 'Desactivar producto' : 'Activar producto' ?>"
            >
                <?php if ($estado): ?>
                    <i data-lucide="x" class="w-5 h-5"></i>
                <?php else: ?>
                    <i data-lucide="check" class="w-5 h-5"></i>
                <?php endif; ?>
            </button>

        </div>
    </td>
</tr>
