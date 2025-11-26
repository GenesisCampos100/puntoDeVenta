<?php
require_once __DIR__ . "/../config/db.php";

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
    echo json_encode($sql->fetchAll(PDO::FETCH_ASSOC));
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
    button svg { pointer-events: none; }

    /* Hover elegante para filas */
    .row-hover:hover {
        background-color: rgba(0, 121, 255, 0.08);
        cursor: pointer;
    }

    /* Scroll estilizado */
    #search-results::-webkit-scrollbar {
        width: 8px;
    }
    #search-results::-webkit-scrollbar-thumb {
        background: #cfcfcf;
        border-radius: 20px;
    }
</style>

</head>
<body class="bg-gray-100 font-sans">

<!-- =======================
          LAYOUT GENERAL
=========================== -->
<div class="flex h-screen">

    <!-- =======================
            SECCIÓN IZQUIERDA
    ============================ -->
    <div class="w-3/4 p-6">

        <!-- 🔵 BÚSQUEDA -->
        <div class="mb-4">
            <label class="text-sm font-semibold text-gray-700">Código del Producto:</label>

            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none"
                    stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M21 21l-4.35-4.35m1.6-5.15a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" />
                </svg>

                <input 
                    id="search-input" 
                    type="text"
                    placeholder="Ingresa o busca un producto..."
                    class="w-full border bg-white border-gray-300 rounded-lg pl-10 pr-4 py-3 text-lg shadow-sm focus:outline-none focus:border-blue-600"
                >
            </div>
        </div>

        <!-- 🟦 RESULTADOS DE BÚSQUEDA -->
        <div id="search-results" class="hidden border border-gray-300 rounded-lg bg-white shadow max-h-64 overflow-y-auto">
            <table class="w-full text-sm">
                <thead class="bg-blue-600 text-white">
                    <tr>
                        <th class="py-2 px-3 text-left">Código</th>
                        <th class="py-2 px-3 text-left">Descripción</th>
                        <th class="py-2 px-3 text-center">Precio</th>
                        <th class="py-2 px-3 text-center">Depto</th>
                        <th class="py-2 px-3 text-center">Stock</th>
                    </tr>
                </thead>

                <tbody id="search-body">
                    <!-- FILAS DINÁMICAS -->
                </tbody>
            </table>
        </div>

        <!-- 🧾 CARRITO / TICKET -->
        <div class="mt-8">
            <h2 class="text-xl font-bold mb-3 text-gray-700">Ticket 1</h2>

            <table class="w-full text-sm bg-white shadow rounded-lg overflow-hidden">
                <thead class="bg-gray-200 border-b border-gray-300">
    <tr class="text-gray-700">
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
<tbody id="cart-rows"></tbody>
            </table>
        </div>

    </div>

    <!-- =======================
             PANEL DERECHO
    ============================ -->
    <div class="w-1/4 bg-white border-l shadow-xl p-6 flex flex-col">

    <div id="vista-producto" class="w-full mb-4 hidden">
    <div class="bg-white shadow rounded-lg p-3">
        <img id="vista-producto-img" class="w-full h-40 object-cover rounded-md">
        <div id="vista-producto-nombre" class="mt-2 font-semibold text-gray-700"></div>
        <div id="vista-producto-detalles" class="text-sm text-gray-500"></div>
    </div>
</div>


        <h2 class="text-xl font-bold text-gray-700 mb-6">Opciones</h2>

        <!-- CLIENTE -->
        <button id="cliente-btn" 
            class="w-full py-3 mb-3 bg-blue-600 text-white text-lg font-semibold rounded-lg shadow hover:bg-blue-700 transition">
            Cliente
        </button>

        <!-- DESCUENTO -->
        <button id="descuento-btn" 
            class="w-full py-3 mb-3 bg-yellow-500 text-white text-lg font-semibold rounded-lg shadow hover:bg-yellow-600 transition">
            Descuento
        </button>

        <!-- PAGO -->
        <button id="pago-btn" 
            class="w-full py-3 mb-6 bg-green-600 text-white text-lg font-semibold rounded-lg shadow hover:bg-green-700 transition">
            Pagar
        </button>

        <!-- 🧮 TOTALES -->
        <div class="mt-auto border-t pt-6">
            <div class="flex justify-between text-lg font-semibold text-gray-700 mb-2">
                <span>Subtotal:</span>
                <span id="subtotal-text">$0.00</span>
            </div>

            <div class="flex justify-between text-lg font-semibold text-gray-700 mb-2">
                <span>Descuento:</span>
                <span id="descuento-text">$0.00</span>
            </div>

            <div class="flex justify-between text-4xl font-extrabold text-blue-600 mt-4">
                <span>Total:</span>
                <span id="total-text">$0.00</span>
            </div>
        </div>

    </div>

</div>

<!-- SCRIPTS -->
<script src="../src/scripts/cart.js"></script>
<script src="../src/scripts/modal.js"></script>



</body>
</html>
