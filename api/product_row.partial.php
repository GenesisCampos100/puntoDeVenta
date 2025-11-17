<?php
// src/api/product_row.partial.php
// Renderiza una sola fila de producto (se llama dentro del foreach principal en inventario_api.php)
?>
<tr class="border-b hover:bg-gray-50 transition" data-id="<?= htmlspecialchars($producto['id_producto']) ?>">
    <td class="px-4 py-2 text-sm font-medium text-gray-700"><?= htmlspecialchars($producto['nom_producto']) ?></td>
    <td class="px-4 py-2 text-sm text-gray-600"><?= htmlspecialchars($producto['cod_barras']) ?></td>
    <td class="px-4 py-2 text-sm"><?= htmlspecialchars($producto['nombre_categoria']) ?></td>
    <td class="px-4 py-2 text-sm text-center"><?= (int)$producto['cantidad'] ?></td>
    
    <!-- Columna de Acciones - ALINEADA AL CENTRO -->
    <td class="px-4 py-2 text-center">
        <div class="flex justify-center items-center gap-3">
            <!-- Botón Ver Detalles -->
            <button class="btn-detalle inline-flex items-center justify-center w-9 h-9 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition shadow-sm" 
                    data-id="<?= $producto['id_producto'] ?>" 
                    title="Ver detalles">
                <i data-lucide="eye" class="h-5 w-5"></i>
            </button>
            
            <!-- Botón Ajustar Stock -->
            <button class="btn-ajuste inline-flex items-center justify-center w-9 h-9 rounded-lg bg-green-50 text-green-600 hover:bg-green-100 transition shadow-sm" 
                    data-id="<?= $producto['id_producto'] ?>" 
                    title="Ajustar stock">
                <i data-lucide="settings" class="h-5 w-5"></i>
            </button>
            
            <!-- Botón Activar/Desactivar -->
            <button class="btn-toggle inline-flex items-center justify-center w-9 h-9 rounded-lg bg-gray-50 text-red-600 hover:bg-red-100 transition shadow-sm" 
                    data-id="<?= $producto['id_producto'] ?>" 
                    data-estado="<?= $producto['is_active'] ?>" 
                    title="<?= $producto['is_active'] ? 'Desactivar producto' : 'Activar producto' ?>">
                <?php if (!empty($producto['is_active'])): ?>
                    <i data-lucide="x" class="h-5 w-5"></i>
                <?php else: ?>
                    <i data-lucide="check" class="h-5 w-5"></i>
                <?php endif; ?>
            </button>
        </div>
    </td>
</tr>
