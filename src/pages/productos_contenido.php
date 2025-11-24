<?php
// src/pages/productos_contenido.php
// Versión Premium Refactorizada - Estilo React/Moderno
// Mantiene TODA la lógica de backend intacta.

require_once __DIR__ . "/../config/db.php";

// -----------------------
// LÓGICA PHP (INTACTA)
// -----------------------

// 1. Mapeo para prevenir errores de SQL Injection y columna inválida
$mapOrder = [
    'nom_asc'    => 'p.nom_producto ASC',
    'nom_desc'   => 'p.nom_producto DESC',
    'precio_asc' => 'p.precio ASC',
    'precio_desc' => 'p.precio DESC',
    'p.nom_producto ASC' => 'p.nom_producto ASC'
];

$orden_get = $_GET['orden'] ?? 'nom_asc';
$orden_sql = $mapOrder[$orden_get] ?? $mapOrder['nom_asc'];
$busqueda = $_GET['busqueda'] ?? '';
$categoria = $_GET['categoria'] ?? '';
$orden = $_GET['orden'] ?? 'p.nom_producto ASC';
$vista_actual = $_GET['view'] ?? 'productos_contenido';

// Consulta Inicial
$sql = "SELECT 
            p.cod_barras,
            p.cod_barras AS id_producto,
            p.nom_producto,
            p.imagen,
            p.marca,
            p.descripcion,
            c.nombre AS categoria,
            p.cantidad,
            p.color,
            p.sku,
            p.cantidad_min,
            p.costo,
            p.precio,
            p.id_categoria,
            (SELECT COUNT(*) FROM variantes v2 WHERE v2.cod_barras = p.cod_barras) AS tiene_variante,
            IFNULL(p.is_active,1) AS is_active
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

// Variantes (pre-carga)
$variantesStmt = $pdo->query("
    SELECT 
        v.cod_barras AS id_producto,
        v.id_variante,
        v.sku,
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

// Categorías
$categorias = $pdo->query("SELECT * FROM categorias")->fetchAll(PDO::FETCH_ASSOC);
$totalProductos = count($productos);
$stockBajo = 0;
foreach($productos as $p) {
    if($p['cantidad'] <= $p['cantidad_min']) $stockBajo++;
}
?>

<!-- Dependencias -->
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://unpkg.com/lucide@0.257.0/dist/lucide.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
    body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
    .animate-fadeIn { animation: fadeIn 0.4s ease-out; }
    .animate-slideUp { animation: slideUp 0.5s cubic-bezier(0.4, 0, 0.2, 1); }
    .delay-100 { animation-delay: 0.1s; animation-fill-mode: both; }
    .delay-200 { animation-delay: 0.2s; animation-fill-mode: both; }
    
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

    .search-input:focus { box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); border-color: #3b82f6; }
    .has-value { border-color: #3b82f6; background: #eff6ff; }
    
    /* Custom Scrollbar */
    ::-webkit-scrollbar { width: 8px; height: 8px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>

<div class="max-w-7xl mx-auto p-4 md:p-8 pb-24">

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-6 animate-slideUp">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Inventario</h1>
            <p class="text-gray-500 mt-1">Administra tu catálogo y existencias</p>
        </div>
        
        <div class="flex gap-4">
            <!-- Stat Card: Total -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4 min-w-[160px]">
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <i data-lucide="package" class="w-6 h-6"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Total</p>
                    <p class="text-2xl font-bold text-gray-900" id="totalProductos"><?= $totalProductos ?></p>
                </div>
            </div>
            
            <!-- Stat Card: Stock Bajo -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4 min-w-[160px]">
                <div class="w-12 h-12 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center">
                    <i data-lucide="alert-triangle" class="w-6 h-6"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Stock Bajo</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stockBajo ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Toolbar Section -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 mb-6 animate-slideUp delay-100">
        <div class="flex flex-col lg:flex-row gap-5 items-center justify-between">
            
            <!-- Search & Tabs -->
            <div class="flex flex-col md:flex-row gap-4 w-full lg:w-2/3 items-center">
                <!-- Search Bar -->
                <div class="relative w-full md:w-1/2 group">
                    <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 group-focus-within:text-blue-500 transition-colors"></i>
                    <input id="busqueda" type="text" placeholder="Buscar por nombre, código o SKU..." 
                           value="<?= htmlspecialchars($busqueda) ?>"
                           class="search-input w-full pl-12 pr-10 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white outline-none transition-all duration-200 font-medium text-gray-700 placeholder-gray-400">
                    <button id="clear-search" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-500 hidden transition-colors p-1 rounded-full hover:bg-red-50">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                <!-- Tabs -->
                <div class="flex bg-gray-100 p-1.5 rounded-xl w-full md:w-auto">
                    <button data-status="activo" class="tab-btn active flex-1 md:flex-none px-6 py-2 rounded-lg text-sm font-semibold transition-all shadow-sm bg-white text-gray-900">
                        Activos
                    </button>
                    <button data-status="descatalogado" class="tab-btn flex-1 md:flex-none px-6 py-2 rounded-lg text-sm font-semibold text-gray-500 hover:text-gray-900 transition-all">
                        Descatalogados
                    </button>
                </div>
            </div>

            <!-- Filters & Actions -->
            <div class="flex flex-wrap md:flex-nowrap gap-3 w-full lg:w-auto justify-end">
                <select id="categoria" class="px-4 py-3 rounded-xl border border-gray-200 bg-white text-sm font-medium focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 outline-none cursor-pointer hover:bg-gray-50 transition-all">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= htmlspecialchars($cat['id_categoria']) ?>" <?= ($categoria == $cat['id_categoria']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select id="orden" class="px-4 py-3 rounded-xl border border-gray-200 bg-white text-sm font-medium focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 outline-none cursor-pointer hover:bg-gray-50 transition-all hidden md:block">
                    <option value="nom_asc" <?= ($orden == 'nom_asc') ? 'selected' : '' ?>>Nombre (A-Z)</option>
                    <option value="nom_desc" <?= ($orden == 'nom_desc') ? 'selected' : '' ?>>Nombre (Z-A)</option>
                    <option value="precio_asc" <?= ($orden == 'precio_asc') ? 'selected' : '' ?>>Precio: Menor</option>
                    <option value="precio_desc" <?= ($orden == 'precio_desc') ? 'selected' : '' ?>>Precio: Mayor</option>
                </select>

                <button id="btnAgregarProducto" onclick="window.location.href='index.php?view=agregar_producto'" class="px-6 py-3 rounded-xl bg-gray-900 hover:bg-gray-800 text-white font-semibold shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all flex items-center gap-2 whitespace-nowrap">
                    <i data-lucide="plus" class="w-5 h-5"></i>
                    <span class="hidden sm:inline">Nuevo Producto</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Table Container -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden animate-slideUp delay-200">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Producto</th>
                        <th class="px-4 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Stock</th>
                        <th class="px-4 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider hidden sm:table-cell">Categoría</th>
                        <th class="px-4 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Precio</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tabla-productos" class="divide-y divide-gray-100">
                    <?php 
                        // Renderizado Inicial PHP
                        if (!empty($productos)) {
                            foreach ($productos as $producto) {
                                // Ajuste de claves para compatibilidad con partial
                                $producto['nombre_categoria'] = $producto['categoria'];
                                
                                include __DIR__ . '/../api/product_row.partial.php';
                                
                                if (($producto['tiene_variante'] > 0) && isset($variantesPorProducto[$producto['id_producto']])) {
                                    echo '<tr id="variants-' . $producto['id_producto'] . '" class="hidden transition-all duration-300 ease-in-out bg-gray-50/50">';
                                    echo '<td colspan="5" class="p-0 border-t-0">';
                                    echo '<div class="px-4 py-3 bg-gray-50 border-y border-gray-100 shadow-inner">';
                                    echo '<table class="w-full">';
                                    echo '<tbody class="divide-y divide-gray-200/50">';
                                    foreach ($variantesPorProducto[$producto['id_producto']] as $var) {
                                        $var['producto_nombre'] = $producto['nom_producto'];
                                        $var['categoria'] = $producto['categoria'];
                                        $var['id_producto'] = $producto['id_producto'];
                                        include __DIR__ . '/../api/variant_row.partial.php';
                                    }
                                    echo '</tbody></table></div></td></tr>';
                                }
                            }
                        } else {
                            echo '<tr><td colspan="5" class="px-6 py-16 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                            <i data-lucide="search-x" class="w-8 h-8 text-gray-400"></i>
                                        </div>
                                        <h3 class="text-lg font-semibold text-gray-900 mb-1">No se encontraron productos</h3>
                                        <p class="text-sm text-gray-500">Intenta ajustar los filtros o tu búsqueda</p>
                                    </div>
                                  </td></tr>';
                        }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal Detalles (Premium) -->
<div id="modalDetalle" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm p-4 transition-opacity duration-300">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden transform transition-all scale-100 animate-fadeIn">
        
        <!-- Modal Header Image -->
        <div class="relative h-64 bg-gray-100 group">
            <img id="modal-img" src="" alt="Producto" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
            
            <button onclick="cerrarModal()" class="absolute top-4 right-4 bg-black/20 hover:bg-black/40 text-white rounded-full p-2 backdrop-blur-md transition-all border border-white/10">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
            
            <div class="absolute bottom-0 left-0 right-0 p-6 text-white">
                <span id="modal-categoria" class="inline-block px-2 py-1 bg-white/20 backdrop-blur-md rounded-md text-xs font-bold uppercase tracking-wider mb-2 border border-white/10"></span>
                <h3 id="modal-nombre" class="text-2xl font-bold leading-tight shadow-sm"></h3>
            </div>
        </div>
        
        <div class="p-6">
            <!-- Price Section -->
            <div class="flex justify-between items-end mb-8 border-b border-gray-100 pb-6">
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wider font-bold mb-1">Precio de Venta</p>
                    <p class="text-4xl font-bold text-gray-900 tracking-tight">$<span id="modal-precio"></span></p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-400 uppercase tracking-wider font-bold mb-1">Costo</p>
                    <p class="text-lg font-medium text-gray-500">$<span id="modal-costo"></span></p>
                </div>
            </div>
            
            <!-- Info Grid -->
            <div class="grid grid-cols-2 gap-4 mb-8">
                <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 hover:border-gray-200 transition-colors">
                    <div class="flex items-center gap-2 mb-2">
                        <i data-lucide="barcode" class="w-4 h-4 text-blue-500"></i>
                        <p class="text-xs text-gray-500 uppercase font-bold">Código / SKU</p>
                    </div>
                    <p id="modal-codigo" class="font-mono text-gray-900 font-bold text-sm truncate"></p>
                </div>
                <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 hover:border-gray-200 transition-colors">
                    <div class="flex items-center gap-2 mb-2">
                        <i data-lucide="box" class="w-4 h-4 text-orange-500"></i>
                        <p class="text-xs text-gray-500 uppercase font-bold">Existencias</p>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span id="modal-stock" class="text-xl font-bold text-gray-900"></span>
                        <span class="text-xs text-gray-400 font-medium">Min: <span id="modal-stock-min"></span></span>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-3">
                <a id="modal-btn-editar" href="#" class="flex-1 bg-gray-900 hover:bg-gray-800 text-white py-3.5 rounded-xl font-bold text-center transition shadow-lg hover:shadow-xl hover:-translate-y-0.5 flex items-center justify-center gap-2">
                    <i data-lucide="edit-2" class="w-4 h-4"></i> Editar
                </a>
                <button id="modal-btn-eliminar" onclick="confirmarEliminar(this)" class="flex-1 bg-white border-2 border-red-100 text-red-500 hover:bg-red-50 hover:border-red-200 py-3.5 rounded-xl font-bold transition flex items-center justify-center gap-2">
                    <i data-lucide="trash-2" class="w-4 h-4"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Confirmación Eliminación -->
<div id="confirmModal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-black/60 backdrop-blur-sm transition-opacity duration-300">
    <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-sm w-full mx-4 transform transition-all scale-100 animate-fadeIn text-center">
        <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4 text-red-500 ring-8 ring-red-50/50">
            <i data-lucide="alert-circle" class="w-8 h-8"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">¿Estás seguro?</h3>
        <p id="confirmMessage" class="text-gray-500 mb-8 leading-relaxed">Esta acción eliminará el producto permanentemente y no se puede deshacer.</p>
        <div class="flex gap-3">
            <button id="cancelBtn" class="flex-1 px-4 py-3 bg-gray-100 text-gray-700 rounded-xl font-bold hover:bg-gray-200 transition">Cancelar</button>
            <button id="confirmBtn" class="flex-1 px-4 py-3 bg-red-500 text-white rounded-xl font-bold hover:bg-red-600 shadow-lg hover:shadow-red-500/30 transition">Eliminar</button>
        </div>
    </div>
</div>

<script>
    // Configuración Global para JS
    // Usamos ruta relativa a la raíz para evitar problemas de CORS/Protocolo (HTTP vs HTTPS)
    const BASE_URL = "/puntoDeVenta/src/api/inventario_api.php";
    console.log("API URL Configurada:", BASE_URL);
</script>
<script src="js/productos.js?v=<?= time() ?>"></script>