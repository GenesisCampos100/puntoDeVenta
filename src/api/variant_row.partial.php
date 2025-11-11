<?php
// src/api/variant_row.partial.php
// Renderiza una sola fila de variante asociada al producto actual
?>
<tr class="bg-gray-100 text-sm" data-variante-id="<?= htmlspecialchars($var['id_variante'] ?? '') ?>">
    <td class="pl-8"><?= htmlspecialchars($var['nombre_variante']) ?></td>
    <td><?= htmlspecialchars($var['sku']) ?></td>
    <td colspan="2" class="text-center"><?= (int)$var['cantidad'] ?></td>
</tr>
