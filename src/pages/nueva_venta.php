<?php
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../config/translation.php";

// Búsqueda de cliente (AJAX)
if (isset($_GET['buscar_cliente'])) {
    $texto = $_GET['buscar_cliente'];
    $sql = $pdo->prepare("
        SELECT id_cliente, 
               CONCAT(nombre, ' ', apellido_paterno, ' ', apellido_materno) AS nombre_completo,
               celular
        FROM clientes
        WHERE nombre LIKE ? 
           OR apellido_paterno LIKE ?
           OR apellido_materno LIKE ?
        LIMIT 20
    ");
    $like = "%$texto%";
    $sql->execute([$like, $like, $like]);
    $clientes = $sql->fetchAll(PDO::FETCH_ASSOC);
    // Aplicar traducción a nombres de clientes
    foreach ($clientes as &$c) {
        $c['nombre_completo'] = tr_content($c['nombre_completo']);
    }
    echo json_encode($clientes);
    exit;
}

// =========================
//      BÚSQUEDA PRODUCTO
// =========================
if (isset($_GET['buscar_producto'])) {
    $texto = trim($_GET['buscar_producto']);

    $sql = $pdo->prepare("
        (SELECT 
            p.cod_barras,
            p.nom_producto,
            p.descripcion,
            p.marca,
            p.imagen,
            p.talla,
            p.color,
            p.sku,
            p.cantidad,
            p.precio,
            c.nombre AS categoria
        FROM productos p
        LEFT JOIN categorias c ON c.id_categoria = p.id_categoria
        WHERE 
            (p.nom_producto LIKE ?
            OR p.cod_barras LIKE ?
            OR p.sku LIKE ?)
            AND p.is_active = 1
        LIMIT 15)
        
        UNION
        
        (SELECT 
            p.cod_barras,
            p.nom_producto,
            p.descripcion,
            p.marca,
            v.imagen,
            v.talla,
            v.color,
            v.sku,
            v.cantidad,
            v.precio,
            c.nombre AS categoria
        FROM variantes v
        JOIN productos p ON v.cod_barras = p.cod_barras
        LEFT JOIN categorias c ON c.id_categoria = p.id_categoria
        WHERE 
            (v.sku LIKE ?
            OR v.cod_barras LIKE ?)
            AND v.is_active = 1
        LIMIT 15)
    ");

    $like = "%$texto%";
    $sql->execute([$like, $like, $like, $like, $like]);

    $resultados = $sql->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($resultados);
    exit;
}




// Traer productos con variantes
$sql = "SELECT 
            p.cod_barras AS producto_cod_barras,
            p.nom_producto AS producto_nombre,
            p.descripcion,
            p.imagen AS producto_imagen,
            p.talla AS producto_talla,
            p.color AS producto_color,
            p.precio AS producto_precio,
            p.cantidad AS producto_cantidad,
            c.nombre AS categoria,
            v.id_variante AS id_variante,
            v.cod_barras AS variante_cod_barras,
            v.talla AS variante_talla,
            v.color AS variante_color,
            v.imagen AS variante_imagen,
            v.precio AS variante_precio,
            v.cantidad AS variante_cantidad
        FROM productos p
        LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
        LEFT JOIN variantes v ON v.cod_barras = p.cod_barras
        ORDER BY p.nom_producto ASC";

$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupar productos con variantes
// Traer productos con variantes
$sql = "SELECT 
            p.cod_barras AS producto_cod_barras,
            p.nom_producto AS producto_nombre,
            p.descripcion,
            p.imagen AS producto_imagen,
            p.talla AS producto_talla,
            p.color AS producto_color,
            p.precio AS producto_precio,
            p.cantidad AS producto_cantidad,
            c.nombre AS categoria,
            v.id_variante AS id_variante,
            v.cod_barras AS variante_cod_barras,
            v.talla AS variante_talla,
            v.color AS variante_color,
            v.imagen AS variante_imagen,
            v.precio AS variante_precio,
            v.cantidad AS variante_cantidad
        FROM productos p
        LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
        LEFT JOIN variantes v ON v.cod_barras = p.cod_barras
        ORDER BY p.nom_producto ASC";

$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupar productos con variantes
$productos = [];
foreach ($rows as $row) {
    $codigo = $row['producto_cod_barras'];
    if (!isset($productos[$codigo])) {
        $productos[$codigo] = [
            'producto_cod_barras' => $codigo,
            'nombre' => $row['producto_nombre'],
            'descripcion' => $row['descripcion'],
            'imagen' => $row['producto_imagen'],
            'precio' => $row['producto_precio'] ?: 0,
            'categoria' => $row['categoria'] ?? 'Sin categoría',
            'variantes' => [],
            'talla_default' => $row['producto_talla'] ?: 'N/A',
            'color_default' => $row['producto_color'] ?: 'Sin color',
            'stock' => $row['producto_cantidad'] ?: 0,
        ];
    }
    if ($row['id_variante'] !== null) {
        $productos[$codigo]['variantes'][] = [
            'id' => (int)$row['id_variante'],
            'cod_barras' => $row['variante_cod_barras'],
            'talla' => $row['variante_talla'],
            'color' => $row['variante_color'],
            'precio' => $row['variante_precio'],
            'imagen' => $row['variante_imagen'],
            'cantidad' => $row['variante_cantidad'] ?: 0,
        ];
    }
}


// Categorías
$categorias = $pdo->query("SELECT * FROM categorias")->fetchAll(PDO::FETCH_ASSOC);

function normalizeCategory($name) {
    return strtolower(trim(preg_replace('/\s+/', '', $name)));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Punto de Venta - Caja</title>

<!-- Tailwind -->
<script src="https://cdn.tailwindcss.com"></script>

<!-- jQuery -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    :root {
        --primary: #1e293b;      /* Slate 800 */
        --primary-dark: #0f172a; /* Slate 900 */
        --accent: #6366f1;       /* Indigo 500 */
        --accent-hover: #4f46e5; /* Indigo 600 */
        --bg-main: #f8fafc;      /* Slate 50 */
        --surface: #ffffff;
        --border: #e2e8f0;       /* Slate 200 */
        --text-main: #334155;    /* Slate 700 */
        --text-muted: #64748b;   /* Slate 500 */
        --success: #10b981;      /* Emerald 500 */
        --danger: #ef4444;       /* Red 500 */
    }

    /* Custom Scrollbar */
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 99px; }
    ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

    .card-shadow {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 
                    0 2px 4px -1px rgba(0, 0, 0, 0.03);
    }
    
    .glass-effect {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(4px);
    }

    .btn-transition {
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .btn-transition:active {
        transform: scale(0.98);
    }

    .row-hover:hover {
        background-color: #f1f5f9; /* Slate 100 */
    }

    input:focus {
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        border-color: var(--accent);
    }

    .apply-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

</style>

<div class="flex w-full gap-4 pb-4 overflow-hidden" style="height: calc(100vh - 9rem);">

    <!-- PANEL IZQUIERDO (Buscador y Carrito) -->
    <div class="flex-1 flex flex-col overflow-hidden gap-4">

        <!-- Top: Buscador -->
        <div class="flex gap-3 shrink-0">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input id="quick-search" type="text" placeholder="Buscar producto por código, nombre o SKU..." 
                    class="w-full pl-12 pr-4 py-4 bg-white border border-slate-200 rounded-2xl text-lg shadow-sm focus:outline-none transition-all placeholder-slate-400 font-medium">
            </div>
            <button id="open-product-modal" class="bg-[var(--primary)] hover:bg-[var(--primary-dark)] text-white px-8 py-3 rounded-2xl font-semibold shadow-lg btn-transition flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                Catálogo
            </button>
        </div>

        <!-- Resultados búsqueda (Flotante) -->
        <div id="search-results" class="hidden absolute top-28 left-6 right-[28%] z-50 bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden max-h-[400px] animate-fade-in-down">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-500 font-semibold uppercase text-xs tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="py-3 px-4">Código</th>
                        <th class="py-3 px-4">Producto</th>
                        <th class="py-3 px-4">Variante</th>
                        <th class="py-3 px-4 text-right">Precio</th>
                        <th class="py-3 px-4 text-center">Stock</th>
                    </tr>
                </thead>
                <tbody id="search-body" class="divide-y divide-slate-50"></tbody>
            </table>
        </div>

        <!-- Ticket / Carrito -->
        <div class="flex-1 bg-white rounded-3xl shadow-sm border border-slate-200 flex flex-col overflow-hidden card-shadow">
            <!-- Header Ticket -->
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <div class="flex items-center gap-3">
                    <div class="bg-indigo-100 p-2 rounded-lg text-indigo-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-800">Ticket de Venta</h2>
                        <p class="text-xs text-slate-500 font-medium">Folio: #NEW</p>
                    </div>
                </div>
                <div class="text-sm font-medium text-slate-500 bg-white px-3 py-1 rounded-full border border-slate-200 shadow-sm">
                    Caja Principal
                </div>
            </div>

            <!-- Tabla Header -->
            <div class="bg-slate-50 border-b border-slate-100 px-2">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-slate-500 text-xs uppercase tracking-wider font-semibold">
                            <th class="py-3 px-4 text-left w-[15%]">Código</th>
                            <th class="py-3 px-4 text-left w-[30%]">Descripción</th>
                            <th class="py-3 px-4 text-center w-[15%]">Precio</th>
                            <th class="py-3 px-4 text-center w-[15%]">Cant.</th>
                            <th class="py-3 px-4 text-right w-[15%]">Total</th>
                            <th class="py-3 px-4 text-center w-[10%]"></th>
                        </tr>
                    </thead>
                </table>
            </div>

            <!-- Filas Carrito -->
            <div class="flex-1 overflow-y-auto custom-scrollbar p-2">
                <table class="w-full text-sm border-separate border-spacing-y-1">
                    <tbody id="cart-rows"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- PANEL DERECHO (Totales y Acciones) -->
    <div class="w-[340px] flex flex-col gap-4 shrink-0">

        <!-- Tarjeta Cliente -->
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200 card-shadow relative overflow-hidden group shrink-0">
            <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:opacity-20 transition-opacity">
                <svg class="w-20 h-20 text-indigo-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            </div>
            
            <div class="relative z-10">
                <label class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1 block">Cliente Asignado</label>
                <div class="flex justify-between items-start">
                    <div class="truncate pr-2">
                        <h3 id="client-name" class="text-lg font-bold text-slate-800 leading-tight truncate">Público General</h3>
                        <p id="client-phone" class="text-xs text-slate-500 mt-0.5 font-medium h-4"></p>
                    </div>
                    <button id="remove-client" class="text-slate-300 hover:text-red-500 transition-colors p-1 rounded-full hover:bg-red-50 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <button id="client-btn" class="mt-3 w-full py-2 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 rounded-lg text-sm font-bold transition-colors flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Cambiar Cliente
                </button>
            </div>
        </div>

        

        <!-- Panel de Totales -->
        <div class="flex-1 bg-slate-900 rounded-2xl p-5 text-white shadow-2xl flex flex-col justify-between relative overflow-hidden min-h-0">
            <!-- Decorative background -->
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-indigo-500 rounded-full blur-3xl opacity-20"></div>
            <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-emerald-500 rounded-full blur-3xl opacity-20"></div>

            <div class="relative z-10 space-y-3 overflow-y-auto custom-scrollbar pr-1">
                <div class="flex justify-between items-center text-slate-300">
                    <span class="text-base font-medium">Subtotal</span>
                    <span id="subtotal" class="text-lg font-semibold tracking-wide">$0.00</span>
                </div>
                
                <div class="flex justify-between items-center text-rose-300">
                    <span class="flex items-center gap-2 text-sm font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                        Descuento
                    </span>
                    <span id="discount" class="text-base font-semibold">-$0.00</span>
                </div>

                <div class="h-px bg-slate-700 my-3"></div>

                <div class="flex justify-between items-end">
                    <span class="text-slate-400 font-medium mb-1 text-sm">Total a Pagar</span>
                    <span id="total" class="text-4xl font-bold tracking-tight text-white">$0.00</span>
                </div>
            </div>

            <div class="relative z-10 mt-4 space-y-3 shrink-0">
                <button id="discount-btn" class="w-full py-3 bg-slate-800 hover:bg-slate-700 text-indigo-300 rounded-xl font-semibold transition-colors flex items-center justify-center gap-2 border border-slate-700 text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path></svg>
                    Aplicar Descuento
                </button>

                <button id="pay-btn" class="w-full py-3.5 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-white rounded-xl font-bold text-lg shadow-lg shadow-emerald-900/30 btn-transition flex items-center justify-center gap-2">
                    <span>Cobrar</span>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </button>
            </div>
        </div>

    </div>

</div>

<?php require_once __DIR__ . "/../scripts/modales_venta.php"; ?>
<!-- SCRIPTS -->
<script src="../src/scripts/cart.js"></script>
<script src="../src/scripts/modal.js"></script>
