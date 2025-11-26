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
        SELECT 
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
            p.nom_producto LIKE :texto
            OR p.cod_barras LIKE :texto
            OR p.sku LIKE :texto
        LIMIT 15
    ");

    $sql->execute([
        ":texto" => "%$texto%"
    ]);

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
    /* Reset y layout fijo */
    html, body {
        height: 100%;
        overflow: hidden !important;
        background: #f3f6fb;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    /* Paleta corporativa (opción B) */
    :root{
        --primary: #0f2a44;    /* azul oscuro */
        --primary-600: #0b2237;
        --accent: #ff557f;     /* magenta suave */
        --muted: #6b7280;
        --border: #e6eef7;
    }

    /* Scrollbars internas */
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
    ::-webkit-scrollbar-thumb:hover { background: #a0aec0; }

    /* Botones inmutables (evitar que el svg capture eventos) */
    button svg { pointer-events: none; }

    /* Hover para filas */
    .row-hover:hover {
        background-color: rgba(15, 42, 68, 0.06);
        transition: background-color 0.12s ease;
        cursor: pointer;
    }

    /* Estilos para el encabezado de tablas */
    .thead-primary {
        background: linear-gradient(90deg, rgba(15,42,68,1) 0%, rgba(11,34,55,1) 100%);
        color: white;
    }

    /* Sombras POS profesionales */
    .card-shadow { box-shadow: 0 6px 18px -8px rgba(15,42,68,0.12); }

    /* Clases utilitarias extras */
    .text-primary { color: var(--primary); }
    .bg-primary { background-color: var(--primary); }
    .bg-accent { background-color: var(--accent); }
</style>
</head>

<body class="h-screen overflow-hidden font-sans antialiased">

<div class="flex h-full">

    <!-- ================================
                PANEL IZQUIERDO
    ================================== -->
    <div class="w-3/4 p-6 flex flex-col overflow-hidden">

        <!-- Top: buscador y acciones rápidas -->
        <div class="flex items-start justify-between gap-4">
            <div class="flex-1">
                <label class="text-sm font-semibold text-gray-700">Código del Producto</label>

                <div class="relative mt-2">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none"
                        stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M21 21l-4.35-4.35m1.6-5.15a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" />
                    </svg>

                    <input 
                        id="search-input" 
                        type="text"
                        placeholder="Ingresa código, nombre o SKU..."
                        class="w-full border bg-white border-gray-200 rounded-xl pl-12 pr-4 py-3 text-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-[rgba(15,42,68,0.12)] focus:border-primary transition"
                    >
                </div>
            </div>

            
        </div>

        <!-- 🟦 RESULTADOS (SCROLL INTERNO) -->
        <div id="search-results" 
             class="hidden border border-gray-200 rounded-xl bg-white shadow-lg mt-4 overflow-y-auto card-shadow"
             style="max-height:260px;">
             
            <table class="w-full text-sm">
                <thead class="thead-primary text-left">
                    <tr>
                        <th class="py-3 px-4 text-left text-xs font-semibold uppercase tracking-wider">Código</th>
                        <th class="py-3 px-4 text-left text-xs font-semibold uppercase tracking-wider">Descripción</th>
                        <th class="py-3 px-4 text-left text-xs font-semibold uppercase tracking-wider">Talla / Color</th>
                        <th class="py-3 px-4 text-center text-xs font-semibold uppercase tracking-wider">Precio</th>
                        <th class="py-3 px-4 text-center text-xs font-semibold uppercase tracking-wider">Depto</th>
                        <th class="py-3 px-4 text-center text-xs font-semibold uppercase tracking-wider">Stock</th>
                    </tr>
                </thead>
                <tbody id="search-body" class="text-gray-700"></tbody>
            </table>
        </div>

        <!-- 🧾 CARRITO / TICKET -->
        <div class="mt-6 flex-1 flex flex-col overflow-hidden">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-primary">Ticket 1</h2>
                <div class="text-sm text-muted">Caja: <span class="font-semibold">Principal</span></div>
            </div>

            <div class="bg-white rounded-xl overflow-hidden flex-1 flex flex-col border border-gray-200 card-shadow">
                <div class="p-4 border-b border-gray-100">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-gray-700 text-sm">
                                <th class="py-2 px-3 text-left">Código</th>
                                <th class="py-2 px-3 text-left">Nombre</th>
                                <th class="py-2 px-3 text-left">Talla/Color</th>
                                <th class="py-2 px-3 text-center">Precio</th>
                                <th class="py-2 px-3 text-center">Cant.</th>
                                <th class="py-2 px-3 text-center">Total</th>
                                <th class="py-2 px-3 text-center">Desc.</th>
                                <th class="py-2 px-3 text-center">Eliminar</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                <!-- 🟧 CONTENEDOR CON SCROLL -->
                <div class="overflow-y-auto" style="max-height:340px;">
                    <table class="w-full text-sm">
                        <tbody id="cart-rows" class="divide-y divide-gray-100 bg-white"></tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- ================================
                PANEL DERECHO
    ================================== -->
    <div class="w-1/4 bg-white border-l border-gray-100 shadow-lg p-6 flex flex-col rounded-l-xl card-shadow">

    <!-- CLIENTE SELECCIONADO -->
    <div id="selected-client" class="mb-4 p-3 bg-gray-100 rounded-xl shadow-sm flex justify-between items-center">
        <div>
            <p class="text-gray-700 font-semibold">Cliente:</p>
            <p id="client-name" class="text-lg text-primary">Público General</p>
            <p id="client-phone" class="text-sm text-gray-500"></p>
        </div>
        <button id="remove-client" class="text-gray-400 hover:text-red-500 text-xl font-bold ml-4" title="Eliminar cliente">&times;</button>
    </div>

        <h2 class="text-xl font-bold text-primary mb-4">Opciones</h2>

        <!-- IMAGEN DEL PRODUCTO SELECCIONADO -->
        <div id="preview-producto" class="w-full mb-6 hidden">
            <div class="bg-white rounded-xl border border-gray-200 p-3">
                <img id="preview-img"
                     src=""
                     alt="Producto seleccionado"
                     class="w-full h-56 object-contain rounded-lg border shadow-sm bg-white">
            </div>
        </div>

        <!-- CLIENTE -->
        <button id="client-btn" 
            class="w-full py-3 mb-3 bg-[var(--primary)] text-white text-lg font-semibold rounded-xl shadow hover:bg-[var(--primary-600)] transition">
            Cliente
        </button>

        <!-- DESCUENTO -->
        <button id="discount-btn" 
            class="w-full py-3 mb-3 bg-[var(--accent)] text-white text-lg font-semibold rounded-xl shadow hover:brightness-95 transition">
            Descuento
        </button>

        <!-- PAGO -->
        <button id="pay-btn" 
            class="w-full py-3 mb-6 bg-green-600 text-white text-lg font-semibold rounded-xl shadow hover:bg-green-700 transition">
            Pagar
        </button>

        <!-- DIVISOR -->
        <div class="border-t border-gray-100 pt-6 mt-auto">
            <div class="flex justify-between text-lg font-semibold text-gray-700 mb-2">
                <span>Subtotal:</span>
                <span id="subtotal" class="text-right">$0.00</span>
            </div>

            <div class="flex justify-between text-lg font-semibold text-gray-700 mb-2">
                <span>Descuento:</span>
                <span id="discount" class="text-right text-red-500">$0.00</span>
            </div>

            <div class="flex justify-between text-4xl font-extrabold text-primary mt-4 tracking-tight">
                <span>Total:</span>
                <span id="total">$0.00</span>
            </div>
        </div>

    </div>

</div>

<!-- SCRIPTS -->
<script src="../src/scripts/cart.js"></script>
<script src="../src/scripts/modal.js"></script>

<?php require_once __DIR__ . "/../scripts/modales_venta.php"; ?>

</body>
</html>
