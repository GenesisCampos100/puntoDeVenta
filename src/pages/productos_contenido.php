<?php 
require_once __DIR__ . "/../config/db.php";

$busqueda = $_GET['busqueda'] ?? '';
$categoria = $_GET['categoria'] ?? '';
$orden = $_GET['orden'] ?? 'p.nom_producto ASC'; 
$vista_actual = $_GET['view'] ?? 'productos_contenido'; 

// --- COMIENZA EL CÓDIGO PHP DE CONSULTA (SIN CAMBIOS) ---

$sql = "SELECT 
            p.cod_barras AS id_producto,
            p.cod_barras AS producto_cod_barras,
            p.nom_producto AS producto_nombre,
            p.imagen AS producto_imagen,
            p.marca,
            p.descripcion,
            c.nombre AS categoria,
            p.cantidad,
            p.color,
            p.cantidad_min,
            p.costo,
            p.precio AS precio_unitario,
            p.id_categoria,
            (SELECT COUNT(*) FROM variantes v2 WHERE v2.cod_barras = p.cod_barras) AS tiene_variante
        FROM productos p
        LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
        WHERE 1=1"; 

if (!empty($busqueda)) $sql .= " AND (p.nom_producto LIKE :busqueda OR p.cod_barras LIKE :busqueda)";
if (!empty($categoria)) $sql .= " AND p.id_categoria = :categoria";

$sql .= " ORDER BY $orden";

$stmt = $pdo->prepare($sql);
$params = [];
if (!empty($busqueda)) $params[':busqueda'] = "%$busqueda%";
if (!empty($categoria)) $params[':categoria'] = $categoria; 
$stmt->execute($params); 
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$variantesStmt = $pdo->query("
    SELECT 
        v.cod_barras AS id_producto,
        v.id_variante AS id,
        v.sku AS cod_barras,
        v.talla,
        v.color,
        v.cantidad,
        v.cantidad_min,
        v.precio,                      
        v.costo,
        v.imagen
    FROM variantes v
");
$variantesRaw = $variantesStmt->fetchAll(PDO::FETCH_ASSOC);

$variantesPorProducto = [];
foreach ($variantesRaw as $v) {
    $variantesPorProducto[$v['id_producto']][] = $v; 
}

$categorias = $pdo->query("SELECT * FROM categorias")->fetchAll(PDO::FETCH_ASSOC);

// --- TERMINA EL CÓDIGO PHP DE CONSULTA ---

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { 
            font-family: 'Poppins', sans-serif; 
            /* Eliminamos el padding fijo del body */
        }

        /* --- Estilos responsive específicos de la tabla --- */
        /* Oculta la cabecera en móvil y convierte las filas en bloques */
        @media screen and (max-width: 1023px) { 
            .productos-container table {
                border-spacing: 0 !important; /* Elimina el espaciado entre filas en móvil */
            }
            .productos-container thead {
                display: none; /* Oculta la cabecera de la tabla */
            }
            .product-row, .fila-variantes {
                display: block; /* La fila se convierte en un bloque */
                margin-bottom: 20px; /* Espaciado entre productos/bloques */
                padding-bottom: 5px; /* Pequeño ajuste visual */
            }
            .product-row td {
                display: block; /* Las celdas se convierten en bloques para apilar */
                text-align: right !important;
                padding: 10px 15px; /* Ajusta el padding de celdas */
            }
            .product-row td:first-child {
                display: none; /* Oculta el checkbox de selección en móvil */
            }
            .product-row td:nth-child(2) {
                text-align: left !important; /* Mantiene la info de producto a la izquierda */
            }
            .product-row td:nth-child(2):before {
                content: "Producto"; 
                float: left; 
                font-weight: 600; 
                color: #555;
            }
            .product-row td:nth-child(3):before {
                content: "Stock";
                float: left;
                font-weight: 600;
                color: #555;
            }
            .product-row td:nth-child(4):before {
                content: "Categoría";
                float: left;
                font-weight: 600;
                color: #555;
            }
            .product-row td:nth-child(5):before {
                content: "Precio";
                float: left;
                font-weight: 600;
                color: #555;
            }
            .product-row td:nth-child(6) {
                text-align: left !important;
                padding-top: 15px;
            }

            /* Estilos para las variantes apiladas en móvil */
            .fila-variantes .w-full {
                display: block;
            }
            .fila-variantes thead {
                display: none;
            }
            .fila-variantes tbody tr {
                display: block;
                border-bottom: 1px dashed #ddd;
            }
            .fila-variantes tbody tr:last-child {
                border-bottom: none;
            }
            .fila-variantes tbody td {
                display: block;
                text-align: right !important;
                padding: 8px 15px;
            }
            .fila-variantes tbody td:nth-child(2) {
                text-align: left !important;
                font-weight: 600;
                padding-left: 55px;
            }
            .fila-variantes tbody td:nth-child(2) img {
                margin-left: -40px;
            }
            .fila-variantes tbody td:nth-child(3):before {
                content: "Stock:";
                float: left;
                font-weight: 400;
                color: #555;
            }
            .fila-variantes tbody td:nth-child(4):before {
                content: "Precio:";
                float: left;
                font-weight: 400;
                color: #555;
            }
            .fila-variantes tbody td:nth-child(1),
            .fila-variantes tbody td:nth-child(5) {
                display: none; /* Oculta columnas de checkbox y acciones */
            }
        }
        
        /* Clases necesarias para la manipulación JS de los selectores que Tailwind no maneja directamente */
        .select-custom-dropdown {
            padding: 10px 20px;
            border: 1px solid #2d4353;
            background-color: #ffffff;
            border-radius: 10px;
            cursor: pointer;
            appearance: none;
            color: #2d4353;
            position: absolute; 
            min-width: 220px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            z-index: 20;
            display: none;
            top: calc(100% + 10px); 
            left: 0;
            right: auto;
            text-align: left;
            font-size: 0.9em;
        }
    </style>
</head>
<body class="bg-[#2d4353] pt-5 text-[#eeeeee] lg:ml-64">

<div class="bg-white p-4 rounded-xl shadow-xl flex flex-col sm:flex-row sm:justify-between items-start sm:items-center sticky top-5 z-50 mx-2 sm:mx-5 font-poppins gap-3">
    <form method="GET" id="toolbar-form" action="index.php" class="flex flex-col sm:flex-row sm:flex-wrap gap-3 items-start sm:items-center w-full sm:w-auto"> 
        <input type="hidden" name="view" value="productos"> 

        <div class="relative w-full sm:w-auto">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 cursor-pointer text-[#2d4353]" onclick="document.getElementById('toolbar-form').submit()">🔍</span> 
            <input type="text" id="busqueda-input" name="busqueda" placeholder="Buscar producto..." 
                    value="<?= htmlspecialchars($busqueda) ?>" 
                    onkeydown="if(event.key === 'Enter') document.getElementById('toolbar-form').submit();"
                    class="pl-11 pr-11 py-2.5 border border-[#eeeeee] bg-white rounded-full w-full sm:w-72 text-[#2d4353] focus:border-[#b4c24d] focus:ring-2 focus:ring-[#b4c24d]/40 transition outline-none">
            <span class="absolute right-4 top-1/2 -translate-y-1/2 cursor-pointer text-[#2d4353] text-sm" onclick="document.getElementById('busqueda-input').value=''; document.getElementById('toolbar-form').submit();">✖</span>
        </div>

        <div class="flex gap-3 w-full sm:w-auto">
            <div class="relative flex-1">
                <button type="button" class="px-5 py-2 rounded-full cursor-pointer font-semibold flex items-center gap-2 transition bg-[#2d4353] text-white hover:bg-[#3b5a70] transform hover:-translate-y-px w-full justify-center" onclick="toggleSelect(event, 'categoria-select')">
                    <span class="text-lg">⚙</span> Filtrar
                </button>
                <select name="categoria" id="categoria-select" onchange="document.getElementById('toolbar-form').submit()" class="select-custom-dropdown">
                    <option value="">-- Todas las categorías --</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= $cat['id_categoria'] ?>" <?= ($categoria == $cat['id_categoria']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="relative flex-1">
                <button type="button" class="px-5 py-2 rounded-full cursor-pointer font-semibold flex items-center gap-2 transition bg-[#2d4353] text-white hover:bg-[#3b5a70] transform hover:-translate-y-px w-full justify-center" onclick="toggleSelect(event, 'orden-select')">
                    <span class="text-xl">⇅</span> Ordenar
                </button>
                <select name="orden" id="orden-select" onchange="document.getElementById('toolbar-form').submit()" class="select-custom-dropdown">
                    <option value="p.nom_producto ASC"    <?= $orden=="p.nom_producto ASC" ? "selected" : "" ?>>Nombre (A-Z)</option>
                    <option value="p.nom_producto DESC" <?= $orden=="p.nom_producto DESC" ? "selected" : "" ?>>Nombre (Z-A)</option>
                    <option value="p.precio ASC"    <?= $orden=="p.precio ASC" ? "selected" : "" ?>>Precio ↑</option>
                    <option value="p.precio DESC"  <?= $orden=="p.precio DESC" ? "selected" : "" ?>>Precio ↓</option>
                </select>
            </div>
        </div>
    </form>
    
    <a href="index.php?view=agregar_producto" class="px-5 py-2 rounded-full cursor-pointer font-semibold flex items-center gap-2 transition bg-[#b4c24d] text-[#2d4353] hover:bg-[#9aa841] transform hover:-translate-y-px w-full sm:w-auto justify-center">
        <span class="text-lg">➕</span> Agregar producto
    </a>
</div>

<div class="productos-container px-2 sm:px-5 mt-5">
    <table class="w-full border-separate" style="border-spacing: 0 15px;">
        <thead class="hidden lg:table-header-group"> 
            <tr>
                <th class="bg-[#2d4353] p-4 text-left font-semibold text-white border-b-2 border-[#b4c24d] text-sm" style="width: 4.5%; border-top-left-radius: 10px;"></th> 
                <th class="bg-[#2d4353] p-4 text-left font-semibold text-white border-b-2 border-[#b4c24d] text-sm" style="width: 45%;">Producto</th>
                <th class="bg-[#2d4353] p-4 text-left font-semibold text-white border-b-2 border-[#b4c24d] text-sm" style="width: 15%;">Stock</th>
                <th class="bg-[#2d4353] p-4 text-left font-semibold text-white border-b-2 border-[#b4c24d] text-sm" style="width: 15%;">Categoría</th>
                <th class="bg-[#2d4353] p-4 text-left font-semibold text-white border-b-2 border-[#b4c24d] text-sm" style="width: 15%;">Precio</th>
                <th class="bg-[#2d4353] p-4 text-left font-semibold text-white border-b-2 border-[#b4c24d] text-sm" style="width: 5%; border-top-right-radius: 10px;"></th> 
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($productos)): ?>
                <?php $isFirstRow = true; ?>
                <?php foreach ($productos as $producto): 
                    // Asegurando la robustez con el operador ?? (asumiendo PHP 7.0+)
                    $cantidad = (int)($producto['cantidad'] ?? 0);
                    $cantidad_min = (int)($producto['cantidad_min'] ?? 0);
                    $es_bajo_stock = $cantidad <= $cantidad_min;
                    $stockClass = $es_bajo_stock ? 'bg-[#fbe2e7] text-[#e15871]' : 'bg-[#f0f4db] text-[#b4c24d]';
                    $imagen = !empty($producto['producto_imagen']) ? "uploads/{$producto['producto_imagen']}" : "../uploads/sin-imagen.png";
                ?>
                <tr class="product-row bg-white shadow-md transition hover:shadow-xl hover:transform hover:-translate-y-0.5 rounded-xl" id="product-row-<?= htmlspecialchars($producto['id_producto']) ?>">
                    <td class="p-4 text-[#2d4353] rounded-l-xl hidden lg:table-cell"><input type="checkbox"></td>
                    
                    <td class="p-4 text-[#2d4353]">
                        <div class="flex items-center gap-4">
                            <img src="<?= htmlspecialchars($imagen) ?>" alt="Producto" class="w-16 h-20 object-cover rounded-md border border-[#eeeeee]">
                            <div>
                                <strong class="text-[#2d4353] font-semibold"><?= htmlspecialchars($producto['producto_nombre']) ?></strong>
                                <small class="block text-gray-500 text-xs mt-1 lg:hidden">Cód: <?= htmlspecialchars($producto['producto_cod_barras']) ?></small>
                                <small class="block text-gray-500 text-xs mt-1 lg:hidden">Color: <?= htmlspecialchars($producto['color'] ?: 'N/A') ?></small>
                            </div>
                        </div>
                    </td>
                    
                    <td class="p-4 text-[#2d4353]"><span class="px-3 py-1 rounded-full text-sm font-semibold <?= $stockClass ?>"><?= $cantidad ?> unidades</span></td>
                    
                    <td class="p-4 text-[#2d4353] hidden sm:table-cell"><?= htmlspecialchars($producto['categoria']) ?></td>
                    
                    <td class="p-4 text-[#2d4353] font-bold text-lg">$<?= number_format($producto['precio_unitario'], 2) ?></td>
                    
                    <td class="p-4 text-[#2d4353] text-center rounded-r-xl lg:text-right">
                        <?php if ($producto['tiene_variante'] > 0): ?>
                            <button class="w-9 h-9 rounded-full bg-[#e15871] text-white text-xl leading-none transition hover:bg-[#c64a61] hover:scale-105" id="btn-toggle-<?= htmlspecialchars($producto['id_producto']) ?>" 
                                 onclick="toggleVariantes('<?= htmlspecialchars($producto['id_producto']) ?>')">+</button>
                        <?php else: ?>
                            <button class="w-9 h-9 rounded-full bg-[#e15871] text-white text-xl leading-none transition hover:bg-[#c64a61] hover:scale-105" onclick='openCustomModal(<?= json_encode($producto) ?>, "producto")'>+</button>
                        <?php endif; ?>
                    </td>
                </tr>

                <?php if ($isFirstRow): ?>
                    <tr class="h-4 bg-transparent hidden lg:table-row"><td colspan="6"></td></tr>
                    <?php $isFirstRow = false; ?>
                <?php endif; ?>

                <?php if ($producto['tiene_variante'] > 0 && !empty($variantesPorProducto[$producto['id_producto']])): ?>
                <tr class="fila-variantes bg-[#f7f7f7] rounded-xl" style="display:none;" id="variantes-<?= htmlspecialchars($producto['id_producto']) ?>">
                    <td colspan="6" class="p-0">
                        <table class="w-full border-collapse">
                            <thead class="hidden lg:table-header-group"> 
                                <tr>
                                    <th class="bg-[#eeeeee] p-2.5 text-left font-semibold text-[#2d4353] text-xs" style="width: 4.5%;"></th>
                                    <th class="bg-[#eeeeee] p-2.5 text-left font-semibold text-[#2d4353] text-xs">Talla/Color</th>
                                    <th class="bg-[#eeeeee] p-2.5 text-left font-semibold text-[#2d4353] text-xs">Stock</th>
                                    <th class="bg-[#eeeeee] p-2.5 text-left font-semibold text-[#2d4353] text-xs">Precio</th>
                                    <th class="bg-[#eeeeee] p-2.5 text-left font-semibold text-[#2d4353] text-xs" style="width: 5%;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($variantesPorProducto[$producto['id_producto']] as $var): 
                                    $var['categoria'] = $producto['categoria'] ?? 'N/A';
                                    $var['producto_nombre'] = ($producto['producto_nombre'] ?? 'Producto') . " (Var. " . ($var['talla'] ?? '—') . "/" . ($var['color'] ?? '—') . ")";
                                    $cantidad_var = (int)($var['cantidad'] ?? 0);
                                    $es_bajo_stock_var = $cantidad_var <= (int)($var['cantidad_min'] ?? 0);
                                    $stockClass_var = $es_bajo_stock_var ? 'bg-[#fbe2e7] text-[#e15871]' : 'bg-[#f0f4db] text-[#b4c24d]';
                                    $precio_var = $var['precio'] ?? 0;
                                ?>
                                <tr class="bg-[#f7f7f7]">
                                    <td class="p-3 text-center hidden lg:table-cell" style="width: 4.5%;"></td>
                                    <td class="p-3">
                                        <div class="flex items-center gap-3 text-[#444444]">
                                            <img src="<?= !empty($var['imagen']) ? 'uploads/'.$var['imagen'] : '../uploads/sin-imagen.png' ?>" class="w-10 h-14 object-cover rounded-sm">
                                            <div>
                                                <strong class="text-[#2d4353]">Talla: <?= htmlspecialchars($var['talla'] ?: '—') ?></strong><br>
                                                <small class="text-xs">Color: <?= htmlspecialchars($var['color'] ?: '—') ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-3"><span class="px-3 py-1 rounded-full text-xs font-semibold <?= $stockClass_var ?>"><?= (int)$var['cantidad'] ?></span></td>
                                    <td class="p-3 font-semibold text-[#2d4353]">$<?= number_format($precio_var, 2) ?></td>
                                    <td class="p-3 text-center lg:text-right" style="width: 5%;">
                                        <button class="w-8 h-8 rounded-full bg-[#e15871] text-white text-lg leading-none transition hover:bg-[#c64a61] hover:scale-105" onclick='openCustomModal(<?= json_encode($var) ?>, "variante")'>+</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <?php endif; ?>

                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" class="text-center p-8 bg-white rounded-xl text-[#2d4353] mx-2 sm:mx-0">No hay productos registrados.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="modal" class="hidden fixed inset-0 flex items-center justify-center z-[1000] bg-[#2d4353]/80 p-4">
    <div class="bg-white p-6 sm:p-8 rounded-xl w-full max-w-2xl relative shadow-2xl text-[#2d4353]">
        <button class="absolute top-2 right-3 sm:top-4 sm:right-5 text-[#2d4353] text-3xl font-bold cursor-pointer" onclick="cerrarModal()">✖</button> 
        
        <div class="flex flex-col sm:flex-row gap-4 sm:gap-6 border-b border-[#eeeeee] pb-4 sm:pb-6 mb-4 sm:mb-6">
            <div class="w-32 h-44 sm:w-36 sm:h-52 bg-[#f7f7f7] rounded-lg overflow-hidden flex-shrink-0 border-2 border-[#eeeeee] mx-auto sm:mx-0">
                <img id="modal-img" src="" alt="Producto" class="w-full h-full object-cover">
            </div>

            <div class="flex-grow text-center sm:text-left">
                <h3 class="text-xl sm:text-2xl font-bold mb-1 sm:mb-2 text-[#2d4353]" id="modal-nombre"></h3>
                <div class="text-gray-600 text-sm mb-1 sm:mb-2">Categoría <span id="modal-categoria"></span></div>
                <div class="text-gray-500 text-xs sm:text-sm mb-3 sm:mb-5">Código de barras: <span id="modal-codigo"></span></div>
                
                <div class="bg-[#f0f4db] p-3 sm:p-4 rounded-lg border-l-4 border-[#b4c24d] text-left">
                    <span class="text-sm text-gray-600 block">Precio de Venta</span>
                    <span class="text-3xl sm:text-4xl font-bold text-[#2d4353]">$<span id="modal-precio"></span></span>
                    <small class="text-xs text-gray-500 block">IVA %16 incluido</small>
                </div>
            </div>
        </div>
        
        <div class="flex flex-col sm:flex-row gap-3 sm:gap-5 mb-6">
             <div class="flex-1 p-4 rounded-lg bg-[#f7f7f7] border border-[#eeeeee]">
                 <span class="text-sm text-gray-600 block">Costo unitario</span>
                 <span class="text-xl sm:text-2xl font-bold text-[#2d4353]">$<span id="modal-costo"></span></span>
                 <small class="text-xs text-gray-500 block">Precio sin margen</small>
             </div>
             
             <div class="flex-1 p-4 rounded-lg bg-[#f7f7f7] border border-[#eeeeee]">
                 <span class="text-sm text-gray-600 block">Existencias</span>
                 <span class="text-xl sm:text-2xl font-bold text-[#2d4353]"><span id="modal-stock"></span> unidades</span>
                 <small class="text-xs text-gray-500 block">Mínimo de stock: <span id="modal-stock-min"></span></small>
             </div>
        </div>
        
        <div class="flex flex-col sm:flex-row justify-end gap-3">
            <button 
                id="modal-btn-eliminar" 
                class="px-5 py-3 rounded-lg font-semibold cursor-pointer text-white transition bg-[#e15871] hover:bg-[#c64a61] w-full sm:w-auto" 
                data-id="" 
                data-type="" 
                onclick="confirmarEliminar(this)">
                🗑️ Eliminar
            </button>
            <a id="modal-btn-editar" class="px-5 py-3 rounded-lg font-semibold cursor-pointer transition bg-[#b4c24d] text-[#2d4353] hover:bg-[#9aa841] text-center w-full sm:w-auto">✏️ Editar</a> 
        </div>
        
    </div>
</div>

<div id="confirmModal" class="hidden fixed inset-0 flex items-center justify-center z-[9999] bg-[#2d4353]/80 p-4">
      <div class="bg-white p-6 rounded-2xl shadow-xl text-center max-w-sm w-full text-[#2d4353]">
        <h3 class="text-lg font-semibold mb-4 text-[#2d4353]">Confirmar eliminación</h3>
        <p class="text-gray-600 mb-6" id="confirmMessage"></p>
        <div class="flex justify-center gap-4">
          <button id="cancelBtn" class="px-4 py-2 rounded-lg bg-[#eeeeee] text-[#2d4353] hover:bg-[#dddddd] transition w-full">Cancelar</button>
          <button id="confirmBtn" class="px-4 py-2 rounded-lg bg-[#e15871] text-white hover:bg-[#c64a61] transition w-full">Eliminar</button>
        </div>
      </div>
    </div>


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script><script>

function toggleSelect(event, selectId) {
    event.stopPropagation();
    const select = document.getElementById(selectId);
    const button = event.currentTarget;
    
    document.querySelectorAll('.toolbar form select').forEach(s => {
        if (s.id !== selectId) {
            s.style.display = 'none';
        }
    });

    const isHidden = select.style.display === 'none' || select.style.display === '';
    select.style.display = isHidden ? 'block' : 'none';

    if (isHidden) {
        // Listener para cerrar al hacer clic fuera
        const closeSelect = (e) => {
            const clickedInsideSelect = select.contains(e.target);
            const clickedButton = e.target === button;
            if (!clickedInsideSelect && !clickedButton) {
                select.style.display = 'none';
                document.removeEventListener('click', closeSelect);
            }
        };
        setTimeout(() => document.addEventListener('click', closeSelect), 50);
    }
}


function openCustomModal(data, type) {
    const isVariant = type === 'variante';
    
    const productName = data.producto_nombre || data.nom_producto || 'Sin nombre'; 
    document.getElementById('modal-nombre').textContent = isVariant 
        ? `${productName}` 
        : productName;
        
    document.getElementById('modal-categoria').textContent = data.categoria || 'Sin categoría';
    document.getElementById('modal-codigo').textContent = data.cod_barras || data.producto_cod_barras || 'N/A';

    const imageKey = isVariant ? 'imagen' : 'producto_imagen';
    document.getElementById('modal-img').src = data[imageKey] ? "uploads/" + data[imageKey] : "../uploads/sin-imagen.png";

    const precioKey = isVariant ? 'precio' : 'precio_unitario'; 
    const precio = (typeof data[precioKey] !== 'undefined' && data[precioKey] !== null) ? parseFloat(data[precioKey]).toFixed(2) : '—';
    const costo = (typeof data.costo !== 'undefined' && data.costo !== null) ? parseFloat(data.costo).toFixed(2) : '—';
    const stock = (typeof data.cantidad !== 'undefined') ? data.cantidad : '—';
    const stockMin = (typeof data.cantidad_min !== 'undefined') ? data.cantidad_min : '—';

    document.getElementById('modal-precio').textContent = precio;
    document.getElementById('modal-costo').textContent = costo;
    document.getElementById('modal-stock').textContent = stock;
    document.getElementById('modal-stock-min').textContent = stockMin; 
    
    const btnEliminar = document.getElementById('modal-btn-eliminar');
    const btnEditar = document.getElementById('modal-btn-editar');
    let id;
    
    if (isVariant) {
        id = data.id; 
        btnEditar.href = `index.php?view=editar_variante&id=${id}&prod_cod_barras=${data.id_producto}`; 
    } else {
        id = data.id_producto; 
        btnEditar.href = `index.php?view=editar_producto&id=${id}`; 
    } 
    btnEliminar.setAttribute('data-id', id);
    btnEliminar.setAttribute('data-type', type);

    document.getElementById('modal').style.display = 'flex';
}

let deleteId = null;
let deleteType = null;

function confirmarEliminar(element) {
  deleteId = element.getAttribute('data-id');
  deleteType = element.getAttribute('data-type');

  if (!deleteId || !deleteType) {
    console.error("Falta el ID o el tipo de producto/variante.");
    return;
  }

  const confirmModal = document.getElementById("confirmModal");
  confirmModal.classList.remove("hidden");
  confirmModal.style.display = 'flex'; 
  
  document.getElementById("confirmMessage").textContent =
    `¿Estás seguro de que quieres eliminar? Esta acción no se puede deshacer.`;
}

document.getElementById("cancelBtn").addEventListener("click", () => {
  const confirmModal = document.getElementById("confirmModal");
  confirmModal.classList.add("hidden");
  confirmModal.style.display = 'none'; 
});

document.getElementById("confirmBtn").addEventListener("click", () => {
  if (deleteId && deleteType) {
    window.location.href = `pages/productos_eliminar.php?type=${deleteType}&id=${deleteId}`;
  }
});


function toggleVariantes(productId) {
    const filaId = 'variantes-' + productId;
    const filaVar = document.getElementById(filaId);
    const btnToggle = document.getElementById('btn-toggle-' + productId);

    if (!filaVar || !btnToggle) {
        return;
    }
    
    const isHidden = filaVar.style.display === 'none' || filaVar.style.display === '';
    // Usamos 'table-row' para pantallas grandes y 'block' para pantallas pequeñas
    filaVar.style.display = isHidden ? (window.innerWidth >= 1024 ? 'table-row' : 'block') : 'none';
    
    btnToggle.textContent = isHidden ? '−' : '+'; 

    if (isHidden) {
        filaVar.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

// Escucha el cambio de tamaño para recalcular la visualización de la fila de variantes
window.addEventListener('resize', () => {
    document.querySelectorAll('.fila-variantes').forEach(fila => {
        const isVisible = fila.style.display !== 'none' && fila.style.display !== '';
        if (isVisible) {
            // Mantiene la visualización correcta según el tamaño de la pantalla
            fila.style.display = window.innerWidth >= 1024 ? 'table-row' : 'block';
        }
    });
});

function cerrarModal() {
    document.getElementById('modal').style.display = 'none';
}

// CAMBIO CLAVE 2: Bloque para mostrar SweetAlert desde la sesión (Flash Message)
window.onload = function() {
    // El PHP imprime el JSON de la sesión si existe
    const alertJson = '<?php 
        if (isset($_SESSION["sweet_alert"])) {
            echo json_encode($_SESSION["sweet_alert"]);
            // Una vez impreso, lo eliminamos de la sesión para que no se muestre de nuevo
            unset($_SESSION["sweet_alert"]); 
        } else {
            echo ""; // Si no existe, no imprimimos nada
        }
    ?>';

    if (alertJson) {
        try {
            const data = JSON.parse(alertJson);
            let confirmColor = '#b4c24d'; // Color de éxito
            if (!data.success) {
                confirmColor = '#e15871'; // Color de error
            }

            Swal.fire({
                title: data.title,
                html: data.text,
                icon: data.icon,
                confirmButtonColor: confirmColor
            });

        } catch (e) {
            console.error("Error al parsear JSON de SweetAlert en productos_contenido: ", e);
        }
    }
};

</script>


</body>
</html>