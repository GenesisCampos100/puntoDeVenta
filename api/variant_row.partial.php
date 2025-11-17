<?php
// src/api/variant_row.partial.php
// Renderiza una sola fila de variante asociada al producto actual

$vcant = (int)($var['cantidad'] ?? 0);
$vcant_min = (int)($var['cantidad_min'] ?? 0);
$vstockClass = ($vcant_min > 0 && $vcant <= $vcant_min)
    ? 'bg-red-100 text-red-700 font-bold border border-red-300'
    : 'bg-green-100 text-green-700 font-bold';
$jsonVar = htmlspecialchars(json_encode($var), ENT_QUOTES, 'UTF-8');
?>
<tr class="bg-gray-100 text-sm" data-variante-id="<?= htmlspecialchars($var['id_variante'] ?? '') ?>">
    <td class="pl-8 text-sm font-medium text-gray-800"><?= htmlspecialchars($var['nombre_variante']) ?></td>
    <td class="text-sm text-gray-600"><?= htmlspecialchars($var['sku']) ?></td>

    <!-- Stock + Acciones en la misma celda para mantener compatibilidad con el layout -->
    <td colspan="2" class="text-center">
        <div class="flex items-center justify-center gap-3">
            <!-- Badge de stock -->
            <span id="stock-<?= htmlspecialchars($var['id_variante'] ?? $var['sku']) ?>" data-min="<?= $vcant_min ?>" class="inline-block px-3 py-1 rounded-full text-xs <?= $vstockClass ?>">
                <?= $vcant ?>
            </span>

            <!-- Acciones (ajustar / ver) -->
            <div class="flex items-center gap-2">
                <button class="btn-ajuste inline-flex items-center justify-center w-8 h-8 rounded-lg bg-green-50 text-green-600 hover:bg-green-100 transition shadow-sm"
                        onclick="openMovimientoModal('<?= htmlspecialchars($var['id_variante'] ?? $var['sku']) ?>','variante','<?= addslashes($var['nombre_variante'] ?? '') ?>', false)"
                        title="Ajustar stock">
                    <i data-lucide="settings" class="h-4 w-4"></i>
                </button>

                <button class="open-modal-btn inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition shadow-sm"
                        data-details='<?= $jsonVar ?>'
                        title="Ver detalle">
                    <i data-lucide="eye" class="h-4 w-4"></i>
                </button>
            </div>
        </div>
    </td>
</tr>
