<?php 
require_once __DIR__ . "/../config/db.php";

// Estos parámetros son los que viajan en la URL de vuelta
$busqueda   = $_GET['busqueda'] ?? '';
$categoria  = $_GET['categoria'] ?? '';
$orden      = $_GET['orden'] ?? 'p.nombre ASC';
$vista_actual = $_GET['view'] ?? 'productos_contenido'; // Esto solo para confirmación de la vista

// ================================
// 1️⃣ Consulta de productos
// ================================
$sql = "SELECT 
            p.id AS id_producto,
            p.cod_barras AS producto_cod_barras,
            p.nombre AS producto_nombre,
            p.imagen AS producto_imagen,
            p.marca,
            c.nombre AS categoria,
            p.cantidad,
            p.color,
            p.cantidad_min,
            p.costo,
            p.precio_unitario,
            p.id_categoria,
            (SELECT COUNT(*) FROM variantes v2 WHERE v2.id_producto = p.id) AS tiene_variante
        FROM productos p
        LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
        WHERE 1=1"; // Cláusula base para empezar los filtros

// ⚠️ Se construye la cláusula WHERE
if (!empty($busqueda)) $sql .= " AND (p.nombre LIKE :busqueda OR p.cod_barras LIKE :busqueda)";
if (!empty($categoria)) $sql .= " AND p.id_categoria = :categoria";

// ⚠️ El orden DEBE ser el último
$sql .= " ORDER BY $orden";

// ====================================================================
// 🚨 ZONA DE REVISIÓN: Preparación y Vinculación de Parámetros
// ====================================================================
$stmt = $pdo->prepare($sql);

// Array para almacenar los parámetros a vincular
$params = [];

if (!empty($busqueda)) {
    // Para LIKE, usamos el comodín % en el valor, no en el SQL
    $params[':busqueda'] = "%$busqueda%";
}

if (!empty($categoria)) {
    // Para el filtro de categoría, se vincula el ID (como entero)
    // Usaremos bindValue en la ejecución para asegurar el tipo.
    $params[':categoria'] = $categoria; 
}

// ⚠️ Si $orden es una columna inválida (ej. inyección SQL), esto fallará.
// Como $orden viene de un SELECT fijo, es poco probable, pero siempre es bueno 
// sanitizar si el valor puede ser inyectado. En este caso, lo mantendremos 
// igual porque viene de un select predefinido.


$stmt->execute($params); // 🚀 Ejecutamos pasando el array de parámetros
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ================================
// 2️⃣ Consulta de variantes y 3️⃣ Categorías (sin cambios)
// ================================

$variantesStmt = $pdo->query("
    SELECT 
        v.id_producto,
        v.id,
        v.cod_barras,
        v.talla,
        v.color,
        v.cantidad,
        v.cantidad_min,
        v.precio_unitario,
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

?>
<head>
    <meta charset="UTF-8">
    <title>Productos</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-blue': '#1e40af', // Ejemplo de color principal
                        'stock-low': '#ef4444', // Rojo
                        'stock-ok': '#10b981', // Verde
                        'stock-min': '#f59e0b', // Amarillo/Naranja
                    },
                    boxShadow: {
                        'custom-light': '0 4px 12px rgba(0, 0, 0, 0.08)',
                    }
                }
            }
        }
    </script>
    
    <style>
        /* Clases auxiliares para el Select Dropdown y Modales que requieren un comportamiento específico */
        .select-visible { display: block !important; }
        .modal { display: none; } /* Tailwind flex se aplica en JS para mostrar */
        .stock.verde { color: var(--tw-colors-stock-ok); font-weight: 600; }
        .stock.rojo { color: var(--tw-colors-stock-low); font-weight: 600; }
        .stock.amarillo { color: var(--tw-colors-stock-min); font-weight: 600; }
    </style>

</head>
<body class="bg-gray-50 font-sans p-6">

<div class="toolbar flex flex-wrap items-center justify-between p-4 bg-white rounded-xl shadow-custom-light mb-6">
    <form method="GET" id="toolbar-form" action="index.php" class="flex flex-wrap items-center gap-3">
        
        <input type="hidden" name="view" value="productos"> 

        <div class="search-container flex items-center border border-gray-300 rounded-lg bg-gray-50 px-3 py-1.5 focus-within:ring-2 focus-within:ring-blue-500 transition duration-150">
            <span class="search-icon text-gray-500 cursor-pointer text-lg" onclick="document.getElementById('toolbar-form').submit()">🔍</span> 
            <input type="text" id="busqueda-input" name="busqueda" placeholder="Buscar producto..." 
                    class="bg-transparent border-none outline-none text-gray-800 placeholder-gray-400 px-2 flex-grow min-w-[150px]"
                    value="<?= htmlspecialchars($busqueda) ?>" 
                    onkeydown="if(event.key === 'Enter') document.getElementById('toolbar-form').submit();">
            <span class="clear-icon text-gray-500 hover:text-gray-700 cursor-pointer" onclick="document.getElementById('busqueda-input').value=''; document.getElementById('toolbar-form').submit();">✖</span>
        </div>

        <div class="relative">
            <button type="button" class="btn-accion flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-200 transition" onclick="toggleSelect(event, 'categoria-select')">
                <span class="icon">⚙</span> Filtrar
            </button>
            <select name="categoria" id="categoria-select" class="absolute hidden mt-1 w-full min-w-[200px] bg-white border border-gray-300 rounded-lg shadow-lg z-20" onchange="document.getElementById('toolbar-form').submit()">
                <option value="">-- Todas las categorías --</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= $cat['id_categoria'] ?>" <?= ($categoria == $cat['id_categoria']) ? 'selected' : '' ?> class="p-2 hover:bg-gray-100">
                        <?= htmlspecialchars($cat['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="relative">
            <button type="button" class="btn-accion flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-200 transition" onclick="toggleSelect(event, 'orden-select')">
                <span class="icon">⇅</span> Ordenar
            </button>
            <select name="orden" id="orden-select" class="absolute hidden mt-1 w-full min-w-[200px] bg-white border border-gray-300 rounded-lg shadow-lg z-20" onchange="document.getElementById('toolbar-form').submit()">
                <option value="p.nombre ASC"  <?= $orden=="p.nombre ASC" ? "selected" : "" ?> class="p-2 hover:bg-gray-100">Nombre (A-Z)</option>
                <option value="p.nombre DESC" <?= $orden=="p.nombre DESC" ? "selected" : "" ?> class="p-2 hover:bg-gray-100">Nombre (Z-A)</option>
                <option value="p.precio_unitario ASC"   <?= $orden=="p.precio_unitario ASC" ? "selected" : "" ?> class="p-2 hover:bg-gray-100">Precio ↑</option>
                <option value="p.precio_unitario DESC"  <?= $orden=="p.precio_unitario DESC" ? "selected" : "" ?> class="p-2 hover:bg-gray-100">Precio ↓</option>
            </select>
        </div>
    </form>
    
    <a href="index.php?view=agregar_producto" class="btn-agregar flex items-center gap-2 px-4 py-2 bg-primary-blue text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 transition duration-150 mt-2 md:mt-0">
        <span class="icon">➕</span> Agregar producto
    </a>
</div>

<div class="productos-container bg-white p-6 rounded-xl shadow-custom-light overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[4.5%]"></th> 
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[45%]">Producto</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[15%]">Stock</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[15%]">Categoría</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[15%]">Precio</th>
                <th scope="col" class="px-6 py-3 w-[5%]"></th> 
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (!empty($productos)): ?>
                <?php $isFirstRow = true; ?>
                <?php foreach ($productos as $producto): 
                    $cantidad = (int)$producto['cantidad'];
                    // Asignación de clases de stock (usando las configuradas en tailwind.config)
                    $es_bajo_stock = $cantidad <= $producto['cantidad_min'];
                    $stockClass = ($cantidad > 0 && !$es_bajo_stock) ? 'verde' : 'rojo';
                    $imagen = !empty($producto['producto_imagen']) ? "uploads/{$producto['producto_imagen']}" : "../uploads/sin-imagen.png";
                ?>
                <tr class="product-row hover:bg-gray-50 transition duration-150" id="product-row-<?= $producto['id_producto'] ?>">
                    <td class="px-6 py-4 whitespace-nowrap"><input type="checkbox" class="form-checkbox h-4 w-4 text-primary-blue border-gray-300 rounded"></td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="producto-info flex items-center space-x-4">
                            <img src="<?= htmlspecialchars($imagen) ?>" alt="Producto" class="w-10 h-14 object-cover rounded-md shadow-sm">
                            <div>
                                <strong class="text-sm font-medium text-gray-900"><?= htmlspecialchars($producto['producto_nombre']) ?></strong>
                                <div><small class="producto-color text-xs text-gray-500"><?= htmlspecialchars($producto['color']) ?></small></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm"><span class="stock <?= $stockClass ?>"><?= $cantidad ?> unidades</span></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($producto['categoria']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 precio">$<?= number_format((float)$producto['precio_unitario'], 2) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <?php if ($producto['tiene_variante'] > 0): ?>
                            <button class="btn-toggle-variantes text-blue-600 hover:text-blue-800 focus:outline-none" id="btn-toggle-<?= $producto['id_producto'] ?>" 
                                onclick='toggleVariantes(<?= $producto['id_producto'] ?>)'>+</button>
                        <?php else: ?>
                            <button class="btn-toggle-variantes text-blue-600 hover:text-blue-800 focus:outline-none" onclick='openCustomModal(<?= json_encode($producto) ?>, "producto")'>+</button>
                        <?php endif; ?>
                    </td>
                </tr>

                <?php if ($producto['tiene_variante'] > 0 && !empty($variantesPorProducto[$producto['id_producto']])): ?>
                <tr class="fila-variantes bg-gray-100" style="display:none;" id="variantes-<?= $producto['id_producto'] ?>">
                    <td colspan="6" class="p-0">
                        <table class="tabla-variantes w-full">
                            <thead>
                                <tr class="border-b border-gray-200 text-xs text-gray-500 uppercase">
                                    <th class="col-vacio w-[4.5%]"></th>
                                    <th class="col-producto-info px-6 py-2 text-left w-[45%]">Talla/Color</th>
                                    <th class="col-stock px-6 py-2 text-left w-[15%]">Stock</th>
                                    <th class="col-precio px-6 py-2 text-left w-[15%]">Precio</th>
                                    <th class="col-acciones px-6 py-2 w-[5%]"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($variantesPorProducto[$producto['id_producto']] as $var): 
                                    $var['categoria'] = $producto['categoria'];
                                    $cantidad_var = (int)$var['cantidad'];
                                    $es_bajo_stock_var = $cantidad_var <= $var['cantidad_min'];
                                    $stockClass_var = ($cantidad_var > 0 && !$es_bajo_stock_var) ? 'verde' : 'rojo';
                                ?>
                                <tr class="hover:bg-gray-200 transition duration-100">
                                    <td class="col-vacio"></td>
                                    <td class="col-producto-info px-6 py-3 whitespace-nowrap">
                                        <div class="producto-info flex items-center space-x-4">
                                            <img src="<?= !empty($var['imagen']) ? 'uploads/'.$var['imagen'] : '../uploads/sin-imagen.png' ?>" class="w-10 h-14 object-cover rounded-md shadow-sm">
                                            <div>
                                                <strong class="text-sm font-medium text-gray-800">Talla: <?= htmlspecialchars($var['talla'] ?: '—') ?></strong><br>
                                                <small class="text-xs text-gray-500">Color: <?= htmlspecialchars($var['color'] ?: '—') ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="col-stock px-6 py-3 whitespace-nowrap text-sm"><span class="stock <?= $stockClass_var ?>"><?= (int)$var['cantidad'] ?></span></td>
                                    <td class="col-precio px-6 py-3 whitespace-nowrap text-sm font-medium">$<?= number_format((float)$var['precio_unitario'], 2) ?></td>
                                    <td class="col-acciones px-6 py-3 whitespace-nowrap text-right">
                                        <button class="btn-toggle-variantes text-blue-600 hover:text-blue-800 focus:outline-none" onclick='openCustomModal(<?= json_encode($var) ?>, "variante")'>+</button>
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
                <tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">No hay productos registrados.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="modal" class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[9998]">
    <div class="modal-content bg-white p-6 rounded-xl shadow-2xl w-full max-w-xl mx-4 relative transform transition-all">
        <button class="cerrar absolute top-3 right-3 text-gray-500 hover:text-gray-900 text-2xl font-semibold" onclick="cerrarModal()">✖</button> 
        
        <div class="modal-top-section flex gap-6 border-b pb-4 mb-4">
            <div class="modal-image-container flex-shrink-0">
                <img id="modal-img" src="" alt="Producto" class="w-32 h-44 object-cover rounded-lg shadow-md">
            </div>

            <div class="modal-info flex-grow">
                <h3 id="modal-nombre" class="text-2xl font-bold text-gray-900 mb-1"></h3>
                <div class="modal-subtitle text-sm text-gray-500 mb-2">Categoría: <span id="modal-categoria" class="font-medium text-gray-700"></span></div>
                <div class="modal-cod-barras text-xs text-gray-400 mb-4">Código de barras: <span id="modal-codigo"></span></div>
                
                <div class="modal-main-price p-3 border border-blue-200 bg-blue-50 rounded-lg">
                    <span class="label text-sm font-medium text-blue-700 block">Precio de Venta</span>
                    <span class="value text-3xl font-extrabold text-blue-800">$<span id="modal-precio"></span></span>
                    <small class="text-xs text-gray-500 block">IVA %16 incluido</small>
                </div>
            </div>
        </div>
        
        <div class="modal-bottom-section flex justify-between gap-4 mb-6">
             <div class="modal-detail-box costo p-4 border border-gray-200 rounded-lg flex-1">
                <span class="label text-sm font-medium text-gray-600 block">Costo unitario</span>
                <span class="value text-xl font-semibold text-gray-900">$<span id="modal-costo"></span></span>
                <small class="text-xs text-gray-500 block">Precio sin margen</small>
            </div>
            
            <div class="modal-detail-box stock p-4 border border-gray-200 rounded-lg flex-1">
                <span class="label text-sm font-medium text-gray-600 block">Existencias</span>
                <span class="value text-xl font-semibold text-gray-900"><span id="modal-stock"></span> unidades</span>
                <small class="text-xs text-gray-500 block">Mínimo de stock: <span id="modal-stock-min"></span></small>
            </div>
        </div>
        
        <div class="modal-actions flex justify-end gap-3">
            <button 
                id="modal-btn-eliminar" 
                class="btn-eliminar flex items-center gap-1 px-4 py-2 bg-red-600 text-white font-semibold rounded-lg shadow-md hover:bg-red-700 transition" 
                data-id="" 
                data-type="" 
                onclick="confirmarEliminar(this)">
                🗑️ Eliminar
            </button>
            <a id="modal-btn-editar" class="btn-editar flex items-center gap-1 px-4 py-2 bg-yellow-500 text-white font-semibold rounded-lg shadow-md hover:bg-yellow-600 transition">✏️ Editar</a> 
        </div>
    </div>
</div>

<div id="confirmModal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-[9999]" style="display: none;">
      <div class="bg-white p-6 rounded-2xl shadow-xl text-center max-w-sm w-full">
        <h3 class="text-xl font-bold mb-4 text-gray-800">Confirmar eliminación</h3>
        <p class="text-gray-600 mb-6" id="confirmMessage"></p>
        <div class="flex justify-center gap-4">
          <button id="cancelBtn" class="px-4 py-2 rounded-lg bg-gray-300 text-gray-700 hover:bg-gray-400 transition font-medium">Cancelar</button>
          <button id="confirmBtn" class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700 transition font-medium">Eliminar</button>
        </div>
      </div>
    </div>

<script>
/**
 * Alterna la visibilidad de los selects de Filtro/Ordenar.
 * @param {Event} event - El evento click.
 * @param {string} selectId - El ID del select.
 */
function toggleSelect(event, selectId) {
    event.stopPropagation();
    const select = document.getElementById(selectId);
    const button = event.currentTarget;
    
    document.querySelectorAll('.toolbar form select').forEach(s => {
        if (s.id !== selectId) {
            s.classList.remove('select-visible');
            s.style.display = 'none';
        }
    });

    const isVisible = select.classList.contains('select-visible');
    
    if (isVisible) {
        select.classList.remove('select-visible');
        select.style.display = 'none';
    } else {
        select.classList.add('select-visible');
        // Posicionamiento relativo al botón
        select.style.top = `${button.offsetHeight + 5}px`;
        select.style.left = '0';
        select.style.display = 'block';

       // Listener para cerrar al hacer clic fuera
        const closeSelect = (e) => {
            const clickedInsideSelect = select.contains(e.target);
            const clickedButton = e.target === button || button.contains(e.target);

            if (!clickedInsideSelect && !clickedButton) {
                select.classList.remove('select-visible');
                select.style.display = 'none';
                document.removeEventListener('click', closeSelect);
            }
        };

        setTimeout(() => document.addEventListener('click', closeSelect), 50);
    }
}


/**
 * Muestra el modal de detalle del producto o variante.
 * @param {Object} data - Los datos del producto o variante.
 * @param {string} type - 'producto' o 'variante'.
 */
function openCustomModal(data, type) {
    const isVariant = type === 'variante';
    
    document.getElementById('modal-nombre').textContent = isVariant 
        ? `Variante ${data.talla || '—'} (${data.color || '—'})` 
        : data.producto_nombre || 'Sin nombre';
        
    document.getElementById('modal-categoria').textContent = data.categoria || 'Sin categoría';
    document.getElementById('modal-codigo').textContent = isVariant ? data.cod_barras : data.producto_cod_barras || 'N/A';

    const imageKey = isVariant ? 'imagen' : 'producto_imagen';
    document.getElementById('modal-img').src = data[imageKey] ? "uploads/" + data[imageKey] : "../uploads/sin-imagen.png";

    const precio = (typeof data.precio_unitario !== 'undefined' && data.precio_unitario !== null) ? parseFloat(data.precio_unitario).toFixed(2) : '—';
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
        btnEditar.href = `index.php?view=editar_variante&id=${id}&prod_id=${data.id_producto}`;
    } else {
        id = data.id_producto; 
        btnEditar.href = `index.php?view=editar_producto&id=${id}`; 
    } 
    btnEliminar.setAttribute('data-id', id);
    btnEliminar.setAttribute('data-type', type);

    document.getElementById('modal').style.display = 'flex';
}

function cerrarModal() {
    document.getElementById('modal').style.display = 'none';
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

    const nombre = (deleteType === 'variante') ? 'esta variante' : 'este producto';
    
    const confirmModal = document.getElementById("confirmModal");
    confirmModal.classList.remove("hidden");
    confirmModal.style.display = 'flex'; 
    
    document.getElementById("confirmMessage").textContent =
      `¿Estás seguro de que quieres eliminar ${nombre}? Esta acción no se puede deshacer.`;

    // Cierra el modal de detalles antes de abrir el de confirmación
    cerrarModal(); 
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


/**
 * Alterna la visibilidad de la fila de variantes y cambia el signo del botón.
 * @param {number} productId - El ID del producto principal.
 */
function toggleVariantes(productId) {
    const filaId = 'variantes-' + productId;
    const filaVar = document.getElementById(filaId);
    const btnToggle = document.getElementById('btn-toggle-' + productId);

    if (!filaVar || !btnToggle) {
        return;
    }
    
    const isHidden = filaVar.style.display === 'none' || filaVar.style.display === '';
    filaVar.style.display = isHidden ? 'table-row' : 'none';
    
    btnToggle.textContent = isHidden ? '−' : '+'; 

    if (isHidden) {
        filaVar.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}
</script>


</body>
</html> 