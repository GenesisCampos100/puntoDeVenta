<?php
// src/api/variant_row.partial.php
// Render: Fila de VARIANTE de un producto

$idVar   = htmlspecialchars($var['id_variante']);
$nombre  = htmlspecialchars($var['nombre_variante']);
$sku     = htmlspecialchars($var['sku']);
$cant    = (int)($var['cantidad'] ?? 0);
$cantMin = (int)($var['cantidad_min'] ?? 0);

// Badge según estado de stock
$stockClass = ($cantMin > 0 && $cant <= $cantMin)
    ? 'bg-red-100 text-red-700 font-bold border border-red-300'
    : 'bg-green-100 text-green-700 font-bold';

// Pasamos datos completos en JSON para detalle
$json = htmlspecialchars(json_encode($var), ENT_QUOTES, 'UTF-8');
?>

<tr class="bg-gray-100 text-sm border-b" data-tipo="variante" data-id="<?= $idVar ?>">

    <!-- Nombre variante -->
    <td class="pl-8 pr-4 py-2 font-medium text-gray-800">
        <?= $nombre ?>
    </td>

    <!-- SKU -->
    <td class="px-4 py-2 text-gray-600">
        <?= $sku ?>
    </td>

    <!-- Stock + Acciones en la misma celda -->
    <td colspan="2" class="px-4 py-2 text-center">
        <div class="flex items-center justify-center gap-4">

            <!-- Badge de stock -->
            <span 
                id="stock-var-<?= $idVar ?>"
                class="px-3 py-1 rounded-full text-xs <?= $stockClass ?>"
                data-min="<?= $cantMin ?>"
            >
                <?= $cant ?>
            </span>

            <!-- Acciones -->
            <div class="flex items-center gap-3">

                <!-- Ajustar stock -->
                <button 
                    class="btn-ajuste w-8 h-8 flex items-center justify-center rounded-lg bg-green-50 text-green-600 hover:bg-green-100 transition shadow-sm"
                    data-id="<?= $idVar ?>"
                    data-tipo="variante"
                    data-nombre="<?= $nombre ?>"
                    title="Ajustar stock"
                >
                    <i data-lucide="settings" class="w-4 h-4"></i>
                </button>

                <!-- Ver detalle -->
                <button 
                    class="btn-detalle w-8 h-8 flex items-center justify-center rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition shadow-sm"
                    data-id="<?= $idVar ?>"
                    data-tipo="variante"
                    data-nombre="<?= $nombre ?>"
                    data-details='<?= $json ?>'
                    title="Ver detalle"
                >
                    <i data-lucide="eye" class="w-4 h-4"></i>
                </button>

            </div>
        </div>
    </td>
</tr>
