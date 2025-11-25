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
<html lang="<?= $_SESSION['lang'] ?? 'es' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Punto de Venta - Caja</title>
    
    <!-- Poppins Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    },
                },
            },
        }
    </script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    
    <style>
        :root {
            --primary: #b4c24d;
            --primary-dark: #9fb03d;
            --secondary: #2d4353;
            --accent: #e15871;
            --bg-gray: #eeeeee;
            --font: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        body {
            font-family: var(--font);
            background: linear-gradient(135deg, #f9fafb 0%, var(--bg-gray) 100%);
            min-height: 100vh;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(100%); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .animate-fade { animation: fadeIn 0.5s ease-out; }
        .animate-slide { animation: slideIn 0.5s ease-out; }
        .animate-slide-right { animation: slideInRight 0.3s ease-out; }
        
        .producto {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .producto:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
        }
        
        .category-btn {
            transition: all 0.25s ease;
        }
        
        .category-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(225, 88, 113, 0.3);
        }
        
        .category-btn.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        }
        
        #cart {
            box-shadow: -4px 0 24px rgba(0, 0, 0, 0.1);
        }
        
        .search-container {
            position: relative;
            max-width: 600px;
            margin: 0 auto 2rem;
        }
        
        .search-input {
            width: 100%;
            padding: 1rem 1.5rem 1rem 3.5rem;
            font-size: 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 50px;
            transition: all 0.3s ease;
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 4px 16px rgba(180, 194, 77, 0.2);
        }
        
        .search-icon {
            position: absolute;
            left: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }
    </style>
</head>
<body>

<!-- HEADER CON BÚSQUEDA -->
<div class="px-6 pt-6 pb-4 animate-fade">
    <div class="search-container">
        <svg class="search-icon w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input type="text" id="search-products" class="search-input" placeholder="<?= __('search_products_placeholder') ?>">
    </div>
</div>

<!-- CATEGORÍAS -->
<div class="flex flex-wrap justify-start gap-2 mb-6 px-6 animate-slide">
    <button data-category="all" class="category-btn active px-6 py-2.5 rounded-full text-white font-semibold text-sm shadow-md" style="background-color:#e15871;">
        <?= __('all_categories') ?>
    </button>
    <?php foreach($categorias as $cat): ?>
        <button data-category="<?= normalizeCategory($cat['nombre']) ?>" class="category-btn px-6 py-2.5 rounded-full text-white font-semibold text-sm shadow-md" style="background-color:#e15871;">
            <?= htmlspecialchars(tr_category($cat['nombre'])) ?>
        </button>
    <?php endforeach; ?>
</div>
<!-- GRID PRODUCTOS -->
<div class="px-6 pb-24">
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6" id="productos-grid">
        <?php foreach($productos as $prod): 
            $imagen = !empty($prod['imagen']) ? $prod['imagen'] : 'sin-imagen.png';
            $precio = $prod['precio'] ?: 0;
            $variants_json = htmlspecialchars(json_encode($prod['variantes'], JSON_UNESCAPED_UNICODE), ENT_QUOTES);
        ?>
            <article class="producto bg-white shadow-lg rounded-2xl p-4 text-center animate-slide"
                     data-code="<?= htmlspecialchars($prod['producto_cod_barras']) ?>"
                     data-name="<?= htmlspecialchars($prod['nombre']) ?>"
                     data-img="../src/uploads/<?= htmlspecialchars($imagen) ?>"
                     data-price="<?= htmlspecialchars($precio) ?>"
                     data-category="<?= htmlspecialchars($prod['categoria']) ?>"
                     data-stock="<?= $prod['stock'] ?>"
                     data-variants='<?= $variants_json ?>'>
                
                <div class="relative overflow-hidden rounded-xl mb-3">
                    <img src="../src/uploads/<?= htmlspecialchars($imagen) ?>" 
                         alt="<?= htmlspecialchars($prod['nombre']) ?>" 
                         class="w-full h-48 object-cover rounded-xl product-image">
                </div>
                
                <h3 class="font-semibold text-gray-800 text-base mb-1"><?= tr_content(htmlspecialchars($prod['nombre'])) ?></h3>
                <p class="text-gray-500 text-sm mb-2"><?= htmlspecialchars(tr_category($prod['categoria'])) ?></p>
                <p class="text-xl font-bold price mb-2" style="color: var(--primary);">$<?= number_format($precio, 2) ?></p>
                
                <!-- STOCK -->
                <p class="text-sm mb-3 font-semibold stock-text" style="color: #10b981;">
                    Stock: <?= count($prod['variantes']) > 0 ? __('depends_on_variant') : $prod['stock'] ?>
                </p>
                
                <!-- VARIANTES -->
                <select class="variant-size border-2 rounded-lg px-3 py-2 text-sm mb-2 w-full focus:border-primary focus:outline-none">
                    <?php 
                        $sizes = array_unique(array_filter(array_map(fn($v)=>$v['talla'] ?? null, $prod['variantes'])));
                        if (empty($sizes)) $sizes = [$prod['talla_default']];
                        foreach ($sizes as $size): ?>
                        <option value="<?= htmlspecialchars($size) ?>"><?= htmlspecialchars($size) ?></option>
                    <?php endforeach; ?>
                </select>
                
                <select class="variant-color border-2 rounded-lg px-3 py-2 text-sm mb-3 w-full focus:border-primary focus:outline-none">
                    <?php 
                        $colors = array_unique(array_filter(array_map(fn($v)=>$v['color'] ?? null, $prod['variantes'])));
                        if (empty($colors)) $colors = [$prod['color_default']];
                        foreach ($colors as $color): ?>
                        <option value="<?= htmlspecialchars($color) ?>"><?= tr_content(htmlspecialchars($color)) ?></option>
                    <?php endforeach; ?>
                </select>
                
                <button class="add-to-cart w-full font-semibold py-3 rounded-xl text-white transition-all hover:shadow-lg" style="background: linear-gradient(135deg, var(--secondary) 0%, #1e3244 100%);">
                    <?= __('add_to_cart_button') ?>
                </button>
            </article>
        <?php endforeach; ?>
    </div>
<!-- Eliminado grid duplicado para evitar doble render y traducciones inconsistentes -->

<!-- CARRITO LATERAL -->
<aside id="cart" class="fixed top-[81px] right-0 w-80 h-[calc(100%-81px)] bg-white shadow-lg flex flex-col p-4 z-50">   
    <div class="flex justify-between items-center mb-5">
        <h2 class="text-2xl font-bold" style="color: var(--secondary);"><?= __('my_cart') ?></h2>
        <div class="flex gap-2">
            <button id="client-btn" class="p-2.5 text-white rounded-full transition-all hover:scale-110" style="background: var(--secondary);" title="Seleccionar Cliente">👤</button>
            <button id="discount-btn" class="p-2.5 text-white rounded-full transition-all hover:scale-110" style="background: var(--primary);" title="Descuento General">%</button>
            <button id="clear-cart" class="p-2.5 bg-red-100 text-red-600 rounded-full transition-all hover:bg-red-200" title="Limpiar Carrito">🗑</button>
        </div>
    </div>
    
    <div id="cliente_info" class="hidden mb-4 p-4 rounded-xl" style="background: #e0f2fe;">
        <p class="text-sm font-semibold text-gray-700"><?= __('selected_customer_label') ?></p>
        <p id="cliente_nombre" class="text-gray-800 font-medium"><?= __('no_customer_selected') ?></p>
        <div class="flex gap-2 mt-3">
            <button id="cambiarCliente" class="px-4 py-2 text-white rounded-lg text-sm font-medium transition-all hover:shadow-md" style="background: var(--secondary);"><?= __('change_button') ?></button>
            <button id="eliminarCliente" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium transition-all hover:bg-red-700"><?= __('delete_button') ?></button>
        </div>
        <input type="hidden" id="cliente_id" value="">
    </div>
    
    <div id="cart-items" class="flex-1 overflow-y-auto space-y-3 mb-4"></div>
    
    <form id="checkout-form" class="mt-auto">
        <input type="hidden" name="id_cliente" id="id_cliente">
        <div class="border-t-2 pt-4">
            <div class="flex justify-between text-base mb-2">
                <span class="font-medium text-gray-600"><?= __('subtotal_label') ?></span>
                <span id="subtotal" class="font-semibold text-gray-800">$0.00</span>
            </div>
            <div class="flex justify-between text-base mb-3">
                <span class="font-medium text-red-600"><?= __('discount_label') ?></span>
                <span id="discount" class="font-semibold text-red-600">$0.00</span>
            </div>
            <div class="flex justify-between font-bold text-2xl mb-4" style="color: var(--primary);">
                <span><?= __('total_label') ?></span>
                <span id="total">$0.00</span>
            </div>
            <button type="button" id="pay-btn" class="w-full font-bold py-4 rounded-xl text-white transition-all hover:shadow-xl text-lg" style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);">
                <?= __('process_sale_button') ?>
            </button>
        </div>
    </form>
</aside>

<!-- Carrito de Compras -->
<!-- MODAL CLIENTES -->
<div id="modalClientes" class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg">
        <h2 class="text-xl font-bold mb-4"><?= __('modal_select_customer_title') ?></h2>
        <input type="text" id="busquedaCliente" class="w-full p-2 border rounded mb-4" placeholder="<?= __('search_customer_placeholder_new_sale') ?>">
        <div id="listaClientes" class="max-h-64 overflow-y-auto">
            <!-- Los clientes se cargarán aquí -->
        </div>
        <div class="flex justify-end mt-4">
            <button id="cerrarModalClientes" class="bg-gray-300 text-gray-800 px-4 py-2 rounded mr-2"><?= __('cancel') ?></button>
        </div>
    </div>
</div>

<!-- MODAL PAGO -->
<div id="payment-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md animate-slide">
        <h2 class="text-2xl font-bold mb-6 text-center" style="color: var(--secondary);"><?= __('payment_method_title') ?></h2>
        
        <form id="payment-form">
            <input type="hidden" name="cart_data" id="cart-data-input">
            <input type="hidden" id="cliente-input" name="id_cliente" value="">
            <input type="hidden" name="descuento_general" id="descuento-general-input">
            <input type="hidden" name="tipo_descuento_general" id="descuento-general-type">
            <input type="hidden" name="subtotal" id="subtotal-input">
            <input type="hidden" name="total" id="total-input">
            
            <div class="space-y-3 mb-6">
                <label class="flex items-center gap-3 border-2 rounded-xl p-4 cursor-pointer hover:bg-gray-50 transition-all">
                    <input type="radio" name="metodo" value="EFECTIVO" checked class="payment-method w-5 h-5">
                    <span class="text-lg font-medium"><?= __('cash_payment_label') ?></span>
                </label>
                
                <label class="flex items-center gap-3 border-2 rounded-xl p-4 cursor-pointer hover:bg-gray-50 transition-all">
                    <input type="radio" name="metodo" value="TARJETA" class="payment-method w-5 h-5">
                    <span class="text-lg font-medium"><?= __('card_payment_label') ?></span>
                </label>
                
                <label class="flex items-center gap-3 border-2 rounded-xl p-4 cursor-pointer hover:bg-gray-50 transition-all">
                    <input type="radio" name="metodo" value="MIXTO" class="payment-method w-5 h-5">
                    <span class="text-lg font-medium"><?= __('mixed_payment_label') ?></span>
                </label>
            </div>
            
            <div id="efectivo-section" class="mb-4">
                <label class="block text-sm font-semibold mb-2"><?= __('cash_received_label') ?></label>
                <input type="number" step="0.01" id="monto-efectivo" name="monto_efectivo" class="w-full border-2 rounded-xl p-3 focus:border-primary focus:outline-none" placeholder="0.00">
            </div>
            
            <div id="tarjeta-section" class="mb-4 hidden">
                <label class="block text-sm font-semibold mb-2"><?= __('card_paid_label') ?></label>
                <input type="number" step="0.01" id="monto-tarjeta" name="monto_tarjeta" class="w-full border-2 rounded-xl p-3 mb-3 focus:border-primary focus:outline-none" placeholder="0.00">
                
                <label class="block text-sm font-semibold mb-2"><?= __('reference_label') ?></label>
                <input type="text" id="referencia-tarjeta" name="referencia_tarjeta" class="w-full border-2 rounded-xl p-3 focus:border-primary focus:outline-none" placeholder="Ingrese referencia">
            </div>
            
            <div id="mixto-section" class="mb-4 hidden">
                <label class="block text-sm font-semibold mb-2"><?= __('cash_label') ?></label>
                <input type="number" step="0.01" id="mixto-efectivo" name="mixto_efectivo" class="w-full border-2 rounded-xl p-3 mb-3 focus:border-primary focus:outline-none" placeholder="0.00">
                
                <label class="block text-sm font-semibold mb-2"><?= __('card_label') ?></label>
                <input type="number" step="0.01" id="mixto-tarjeta" name="mixto_tarjeta" class="w-full border-2 rounded-xl p-3 mb-3 focus:border-primary focus:outline-none" placeholder="0.00">
                
                <label class="block text-sm font-semibold mb-2"><?= __('card_reference_label') ?></label>
                <input type="text" id="mixto-referencia" name="mixto_referencia" class="w-full border-2 rounded-xl p-3 focus:border-primary focus:outline-none" placeholder="Folio, referencia, etc.">
            </div>
            
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" id="cancel-payment" class="px-6 py-3 bg-gray-200 rounded-xl font-semibold hover:bg-gray-300 transition-all"><?= __('cancel_button') ?></button>
                <button type="submit" id="confirm-payment" class="px-6 py-3 text-white rounded-xl font-semibold transition-all hover:shadow-xl" style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);"><?= __('confirm_button') ?></button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL DESCUENTO GENERAL -->
<div id="discount-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl p-8 w-96 animate-slide">
        <h2 class="text-xl font-bold mb-5" style="color: var(--secondary);"><?= __('general_discount_title') ?></h2>
        <div class="flex gap-3 mb-5">
            <select id="discount-type" class="border-2 rounded-xl p-3 w-1/3 text-center font-semibold focus:border-primary focus:outline-none">
                <option value="percent">%</option>
                <option value="amount">$</option>
            </select>
            <input type="number" id="discount-input" class="border-2 rounded-xl p-3 w-2/3 focus:border-primary focus:outline-none" placeholder="Valor">
        </div>
        <div class="flex justify-end gap-3">
            <button id="close-discount" class="px-5 py-2.5 bg-gray-200 rounded-xl font-semibold hover:bg-gray-300 transition-all"><?= __('cancel_button') ?></button>
            <button id="apply-discount" class="px-5 py-2.5 text-white rounded-xl font-semibold transition-all hover:shadow-lg" style="background: var(--primary);"><?= __('apply_button') ?></button>
        </div>
    </div>
</div>

<!-- MODAL DESCUENTO POR PRODUCTO -->
<div id="product-discount-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl p-8 w-96 animate-slide">
        <h2 class="text-xl font-bold mb-5" style="color: var(--secondary);"><?= __('product_discount_title') ?></h2>
        <div class="flex gap-3 mb-5">
            <select id="product-discount-type" class="border-2 rounded-xl p-3 w-1/3 text-center font-semibold focus:border-primary focus:outline-none">
                <option value="percent">%</option>
                <option value="amount">$</option>
            </select>
            <input type="number" id="product-discount-input" class="border-2 rounded-xl p-3 w-2/3 focus:border-primary focus:outline-none" placeholder="Valor">
        </div>
        <div class="flex justify-end gap-3">
            <button id="product-discount-close" class="px-5 py-2.5 bg-gray-200 rounded-xl font-semibold hover:bg-gray-300 transition-all"><?= __('cancel_button') ?></button>
            <button id="product-discount-apply" class="px-5 py-2.5 text-white rounded-xl font-semibold transition-all hover:shadow-lg" style="background: var(--primary);"><?= __('apply_button') ?></button>
        </div>
    </div>
</div>

<script>
    const translations = {
        empty_cart_message: "<?= __('empty_cart_message') ?>",
        add_products_message: "<?= __('add_products_message') ?>",
        discount_button: "<?= __('discount_button') ?>",
        remove_button: "<?= __('remove_button') ?>",
        sale_registered_message: "<?= __('sale_registered_message') ?>",
        sale_error_message: "<?= __('sale_error_message') ?>",
        process_sale_error_message: "<?= __('process_sale_error_message') ?>"
    };
</script>
<!-- MODAL TICKET -->
<div id="ticket-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-start justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl p-6 w-auto max-w-[95%] md:max-w-md animate-slide overflow-hidden mt-12">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold" style="color: var(--secondary);">Ticket de Venta</h2>
            <button id="close-ticket-modal" class="text-gray-400 hover:text-gray-600 text-3xl font-bold">&times;</button>
        </div>

        <div class="max-h-[60vh] mb-4 flex items-start justify-center">
            <div class="overflow-auto pr-2" style="max-height:60vh; width: fit-content;">
                <div class="border p-1 bg-gray-50" style="width:85mm;max-width:100%;">
                    <iframe id="ticket-iframe" src="" frameborder="0" style="width:100%;height:60vh;background:white;display:block;margin:0"></iframe>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <button id="cancel-ticket" class="px-5 py-2.5 bg-gray-200 rounded-xl font-semibold hover:bg-gray-300 transition-all">Cancelar</button>
            <button id="print-ticket" class="px-5 py-2.5 text-white rounded-xl font-semibold transition-all hover:shadow-lg" style="background: var(--primary);">Imprimir</button>
        </div>
    </div>
</div>

<script src="../src/scripts/cart.js"></script>
<script src="../src/scripts/modal.js"></script>

<script>
// BÚSQUEDA EN TIEMPO REAL
document.getElementById('search-products').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const products = document.querySelectorAll('.producto');
    
    products.forEach(product => {
        const name = product.dataset.name.toLowerCase();
        const category = product.dataset.category.toLowerCase();
        const code = product.dataset.code.toLowerCase();
        
        if (name.includes(searchTerm) || category.includes(searchTerm) || code.includes(searchTerm)) {
            product.style.display = '';
        } else {
            product.style.display = 'none';
        }
    });
});

// FILTRO POR CATEGORÍA
document.querySelectorAll('.category-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        const category = this.dataset.category;
        const products = document.querySelectorAll('.producto');
        
        products.forEach(product => {
            if (category === 'all') {
                product.style.display = '';
            } else {
                const productCategory = product.dataset.category.toLowerCase().replace(/\s+/g, '');
                if (productCategory === category) {
                    product.style.display = '';
                } else {
                    product.style.display = 'none';
                }
            }
        });
    });
});

// ACTUALIZAR STOCK SEGÚN VARIANTE
document.querySelectorAll('.producto').forEach(card => {
    const variants = JSON.parse(card.dataset.variants);
    const sizeSelect = card.querySelector('.variant-size');
    const colorSelect = card.querySelector('.variant-color');
    const stockText = card.querySelector('.stock-text');
    
    function updateStock() {
        if (!variants.length) return;
        
        const talla = sizeSelect.value;
        const color = colorSelect.value;
        
        const variante = variants.find(v => v.talla === talla && v.color === color);
        
        stockText.textContent = variante ? `Stock: ${variante.cantidad}` : 'Stock: 0';
    }
    
    if (variants.length) {
        sizeSelect.addEventListener('change', updateStock);
        colorSelect.addEventListener('change', updateStock);
        updateStock();
    }
});

// BÚSQUEDA DE CLIENTES
$('#buscarCliente').on('input', function() {
    const searchTerm = $(this).val().toLowerCase();
    $('#tablaClientes tr').each(function() {
        const rowText = $(this).text().toLowerCase();
        $(this).toggle(rowText.includes(searchTerm));
    });
});

// SELECCIONAR CLIENTE
$(document).on('click', '.seleccionarCliente', function() {
    const id = $(this).data('id');
    const nombre = $(this).data('nombre');
    window.guardarCliente(id, nombre);
    $('#modalClientes').addClass('hidden');
});

// ABRIR/CERRAR MODAL CLIENTES
$('#client-btn').click(() => $('#modalClientes').removeClass('hidden').addClass('flex'));
$('#cerrar-modal-cliente').click(() => $('#modalClientes').addClass('hidden').removeClass('flex'));

$(document).ready(function() {
        // --- MANEJO DEL MODAL DE CLIENTES ---

        // Abrir modal para cambiar/seleccionar cliente
        $('#cambiar_cliente_btn').on('click', function() {
            $('#modalClientes').removeClass('hidden');
            cargarClientes();
        });

        // Cerrar modal
        $('#cerrarModalClientes').on('click', function() {
            $('#modalClientes').addClass('hidden');
        });

        // Búsqueda de clientes en tiempo real
        $('#busquedaCliente').on('keyup', function() {
            cargarClientes($(this).val());
        });

        // Función para cargar clientes vía AJAX
        function cargarClientes(terminoBusqueda = '') {
            $.ajax({
                url: '../scripts/buscar_clientes.php',
                type: 'GET',
                data: {
                    term: terminoBusqueda
                },
                dataType: 'json',
                success: function(clientes) {
                    let html = '<table class="w-full text-left"><thead><tr><th class="p-2"><?= __('customer_col') ?></th><th class="p-2"><?= __('phone_col') ?></th><th class="p-2"><?= __('actions_col') ?></th></tr></thead><tbody>';
                    if (clientes.length > 0) {
                        clientes.forEach(function(cliente) {
                            html += `<tr>
                                <td class="p-2">${cliente.nombre}</td>
                                <td class="p-2">${cliente.telefono}</td>
                                <td class="p-2"><button class="seleccionar-cliente-btn bg-blue-500 text-white px-3 py-1 rounded" data-id="${cliente.id_cliente}" data-nombre="${cliente.nombre}"><?= __('select_button') ?></button></td>
                            </tr>`;
                        });
                    } else {
                        html += `<tr><td colspan="3" class="text-center p-4"><?= __('no_customers_found') ?></td></tr>`;
                    }
                    html += '</tbody></table>';
                    $('#listaClientes').html(html);
                },
                error: function() {
                    $('#listaClientes').html('<p>Error al cargar los clientes.</p>');
                }
            });
        }

        // Seleccionar un cliente del modal
        $(document).on('click', '.seleccionar-cliente-btn', function() {
            const id = $(this).data('id');
            const nombre = $(this).data('nombre');
            
            // Guardar en sesión y actualizar vista
            $.ajax({
                url: '../scripts/guardar_cliente.php',
                type: 'POST',
                data: {
                    id_cliente: id
                },
                success: function() {
                    $('#nombre_cliente_seleccionado').text(nombre);
                    $('#modalClientes').addClass('hidden');
                },
                error: function() {
                    alert('Error al seleccionar el cliente.');
                }
            });
        });
    });
</script>

</body>
</html>