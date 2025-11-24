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
  button svg {
  pointer-events: none;
}
</style>

</head>
<body class="bg-gray-50 font-sans">
<!-- Contenedor principal -->
<div class="main-content pr-96">
  <!-- BÚSQUEDA -->
  <div class="px-6 py-6">
    <div class="relative max-w-xl mx-auto">
      <input type="text" id="search-products" placeholder="Buscar productos por nombre, categoría o código..."
        class="w-full pl-12 pr-4 py-3 rounded-full border-2 border-gray-200 focus:outline-none focus:border-green-500 shadow-sm transition">
      <svg class="absolute left-4 top-1/2 transform -translate-y-1/2 w-6 h-6 text-gray-400" fill="none"
        stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
      </svg>
    </div>
  </div>

  <!-- CATEGORÍAS -->
  <div class="px-6 flex flex-wrap gap-2 mb-6">
    <button data-category="all"
      class="category-btn bg-pink-500 text-white px-6 py-2 rounded-full font-semibold shadow-md">Todos</button>
    <?php foreach($categorias as $cat): ?>
    <button data-category="<?= normalizeCategory($cat['nombre']) ?>"
      class="category-btn bg-pink-500 text-white px-6 py-2 rounded-full font-semibold shadow-md">
      <?= htmlspecialchars($cat['nombre']) ?>
    </button>
    <?php endforeach; ?>
  </div>

<!-- PRODUCTOS -->
<div class="px-6">
  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6" id="productos-grid">
    <?php foreach($productos as $prod):
      $imagen = !empty($prod['imagen']) ? $prod['imagen'] : 'sin-imagen.png';
      $precio = $prod['precio'] ?: 0;
      $variants = $prod['variantes'] ?? [];
      $variants_json = htmlspecialchars(json_encode($variants, JSON_UNESCAPED_UNICODE), ENT_QUOTES);

      // tallas únicas
      $sizes = array_unique(array_map(fn($v)=> $v['talla'] ?? $prod['talla_default'], $variants));
      if (empty($sizes)) $sizes = [$prod['talla_default']];

      // colores de la primera talla
      $firstSize = $sizes[0];
      $colors = array_unique(array_map(fn($v)=> $v['color'], array_filter($variants, fn($v)=>($v['talla'] ?? $prod['talla_default'])==$firstSize)));
      if (empty($colors)) $colors = [$prod['color_default']];
    ?>
    <article class="producto bg-white rounded-xl shadow-md p-4 flex flex-col items-center hover:shadow-lg transition"
        data-code="<?= htmlspecialchars($prod['producto_cod_barras'] ?? '') ?>"
        data-name="<?= htmlspecialchars($prod['nombre'] ?? '') ?>"
        data-img="../src/uploads/<?= htmlspecialchars($imagen) ?>"
        data-price="<?= htmlspecialchars($precio) ?>"
        data-category="<?= htmlspecialchars($prod['categoria'] ?? '') ?>"
        data-stock="<?= $prod['stock'] ?? 0 ?>"
        data-variants='<?= $variants_json ?>'>

      <img src="../src/uploads/<?= htmlspecialchars($imagen) ?>" class="w-28 h-28 rounded-full object-cover -mt-10 shadow-sm transition-transform hover:scale-105">
      <h2 class="text-sm"><?= htmlspecialchars($prod['producto_cod_barras'] ?? '') ?></h2>
      <h3 class="mt-4 font-semibold text-gray-800 text-lg text-center"><?= htmlspecialchars($prod['nombre'] ?? '') ?></h3>
      <p class="text-sm text-gray-500"><?= htmlspecialchars($prod['categoria'] ?? '') ?></p>
      <p class="mt-1 font-bold text-green-500">$<?= number_format($precio,2) ?></p>
      <p class="stock-text text-sm mt-1 font-medium text-gray-600">Stock: <?= count($variants)>0 ? 'Según variante' : ($prod['stock'] ?? 0) ?></p>

      <!-- Select Talla -->
      <select class="variant-size mt-2 w-full border-gray-200 rounded-lg p-2">
        <?php foreach($sizes as $size): ?>
          <option value="<?= htmlspecialchars($size) ?>"><?= htmlspecialchars($size) ?></option>
        <?php endforeach; ?>
      </select>

      <!-- Select Color dependiente -->
      <select class="variant-color mt-2 w-full border-gray-200 rounded-lg p-2">
        <?php foreach($colors as $color): ?>
          <option value="<?= htmlspecialchars($color) ?>"><?= htmlspecialchars($color) ?></option>
        <?php endforeach; ?>
      </select>

      <button type="button" class="add-to-cart mt-3 w-full bg-pink-500 text-white rounded-lg py-2 font-semibold hover:bg-pink-600 transition">
        Agregar
      </button>
    </article>
    <?php endforeach; ?>
  </div>
</div>


          </div>


<aside id="cart" class="fixed top-0 right-0 w-96 h-full bg-white flex flex-col p-5 z-50 shadow-xl animate-slide-right">
  <!-- Header -->
  <div class="flex justify-between items-center mb-5 border-b pb-3">
    <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
      🛒 Mi Carrito
    </h2>
    <div class="flex gap-2">
      <button id="client-btn" class="p-2.5 text-white rounded-full transition-all hover:scale-110 shadow" style="background: #4ADE80;" title="Seleccionar Cliente">👤</button>
      <button id="discount-btn" class="p-2.5 text-white rounded-full transition-all hover:scale-110 shadow" style="background: #22D3EE;" title="Descuento General">%</button>
      <button id="clear-cart" class="p-2.5 bg-red-100 text-red-600 rounded-full transition-all hover:bg-red-200 shadow" title="Limpiar Carrito">🗑</button>
    </div>
  </div>


<!-- Cliente compacto con íconos -->
<div id="cliente_info" class="hidden mb-2 p-2 rounded-xl bg-gray-50 border border-green-200 flex items-center justify-between">
  <!-- Nombre cliente -->
  <p id="cliente_nombre" class="text-lg font-medium text-gray-800 truncate max-w-[70%]">No hay cliente seleccionado</p>

  <!-- Botones como íconos -->
  <div class="flex gap-2">
    <!-- Cambiar cliente (icono switch) -->
    <button id="cambiarCliente" class="p-2 bg-green-400 text-white rounded-full hover:bg-green-500 transition" title="Cambiar Cliente">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="pointer-events: none;">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v6h6M20 20v-6h-6M4 10a8 8 0 0116 0 8 8 0 01-16 0z" />
      </svg>
    </button>

    <!-- Eliminar cliente -->
    <button id="eliminarCliente" class="p-2 bg-pink-600 text-white rounded-full hover:bg-pink-700 transition" title="Eliminar Cliente">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="pointer-events: none;">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
      </svg>
    </button>
  </div>

  <input type="hidden" id="cliente_id" value="">
</div>





  <!-- Items -->
  <div id="cart-items" class="flex-1 overflow-y-auto space-y-3 mb-4 scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100">
  </div>

  <!-- Totales -->
  <form id="checkout-form" class="mt-auto">
    <input type="hidden" name="id_cliente" id="id_cliente">
    <div class="border-t-2 pt-4 space-y-2">
      <div class="flex justify-between text-base">
        <span class="font-medium text-gray-600">Subtotal:</span>
        <span id="subtotal" class="font-semibold text-gray-800">$0.00</span>
      </div>
      <div class="flex justify-between text-base">
        <span class="font-medium text-red-600">Descuento:</span>
        <span id="discount" class="font-semibold text-red-600">$0.00</span>
      </div>
      <div class="flex justify-between font-bold text-2xl mt-2" style="color: #123163ff;">
        <span>Total:</span>
        <span id="total">$0.00</span>
      </div>
      <button type="button" id="pay-btn" class="w-full mt-4 py-4 text-white font-bold rounded-xl hover:from-blue-600 hover:to-blue-800 transition-shadow shadow-md hover:shadow-xl" style="background-color: #0A2342;">
      Procesar Venta
      </button>
    </div>
  </form>
</aside>


<!-- MODAL CLIENTES -->
<div id="modalClientes" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white w-full max-w-4xl rounded-2xl shadow-2xl p-6 m-4 animate-slide">
        <div class="flex justify-between items-center mb-5">
            <h2 class="text-2xl font-bold" style="color: var(--secondary);">Seleccionar Cliente</h2>
            <button id="cerrar-modal-cliente" class="text-gray-400 hover:text-gray-600 text-3xl font-bold">&times;</button>
        </div>
        <input type="text" id="buscarCliente" class="w-full border-2 px-4 py-3 rounded-xl mb-4 focus:border-primary focus:outline-none" placeholder="Buscar cliente por nombre...">
        <div class="overflow-y-auto max-h-96">
            <table class="w-full text-left border-collapse">
                <thead class="sticky top-0" style="background: var(--bg-gray);">
                    <tr>
                        <th class="p-3 border-b-2 font-semibold">ID</th>
                        <th class="p-3 border-b-2 font-semibold">Cliente</th>
                        <th class="p-3 border-b-2 font-semibold">Teléfono</th>
                        <th class="p-3 border-b-2 font-semibold">Acción</th>
                    </tr>
                </thead>
                <tbody id="tablaClientes">
                    <?php
                        $sql = "SELECT * FROM clientes ORDER BY nombre ASC";
                        $stmt = $pdo->query($sql);
                        while ($cli = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $full = htmlspecialchars($cli['nombre'].' '.$cli['apellido_paterno'].' '.$cli['apellido_materno']);
                            echo "<tr class='border-b hover:bg-gray-50 transition-colors'>
                                <td class='p-3'>{$cli['id_cliente']}</td>
                                <td class='p-3 font-medium'>{$full}</td>
                                <td class='p-3'>".htmlspecialchars($cli['celular'])."</td>
                                <td class='p-3'>
                                    <button class='seleccionarCliente px-4 py-2 text-white rounded-lg font-medium transition-all hover:shadow-md' style='background: var(--primary);' data-id='{$cli['id_cliente']}' data-nombre='{$full}'>Seleccionar</button>
                                </td>
                            </tr>";
                        }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL PAGO -->
<div id="payment-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md animate-slide">
        <h2 class="text-2xl font-bold mb-6 text-center" style="color: var(--secondary);">Método de Pago</h2>
        
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
                    <span class="text-lg font-medium">💵 Efectivo</span>
                </label>
                
                <label class="flex items-center gap-3 border-2 rounded-xl p-4 cursor-pointer hover:bg-gray-50 transition-all">
                    <input type="radio" name="metodo" value="TARJETA" class="payment-method w-5 h-5">
                    <span class="text-lg font-medium">💳 Tarjeta</span>
                </label>
                
                <label class="flex items-center gap-3 border-2 rounded-xl p-4 cursor-pointer hover:bg-gray-50 transition-all">
                    <input type="radio" name="metodo" value="MIXTO" class="payment-method w-5 h-5">
                    <span class="text-lg font-medium">💵💳 Pago Mixto</span>
                </label>
            </div>
            
            <div id="efectivo-section" class="mb-4">
                <label class="block text-sm font-semibold mb-2">Monto recibido (efectivo):</label>
                <input type="number" step="0.01" id="monto-efectivo" name="monto_efectivo" class="w-full border-2 rounded-xl p-3 focus:border-primary focus:outline-none" placeholder="0.00">
            </div>
            
            <div id="tarjeta-section" class="mb-4 hidden">
                <label class="block text-sm font-semibold mb-2">Monto pagado con tarjeta:</label>
                <input type="number" step="0.01" id="monto-tarjeta" name="monto_tarjeta" class="w-full border-2 rounded-xl p-3 mb-3 focus:border-primary focus:outline-none" placeholder="0.00">
                
                <label class="block text-sm font-semibold mb-2">Referencia / Folio:</label>
                <input type="text" id="referencia-tarjeta" name="referencia_tarjeta" class="w-full border-2 rounded-xl p-3 focus:border-primary focus:outline-none" placeholder="Ingrese referencia">
            </div>
            
            <div id="mixto-section" class="mb-4 hidden">
                <label class="block text-sm font-semibold mb-2">Efectivo:</label>
                <input type="number" step="0.01" id="mixto-efectivo" name="mixto_efectivo" class="w-full border-2 rounded-xl p-3 mb-3 focus:border-primary focus:outline-none" placeholder="0.00">
                
                <label class="block text-sm font-semibold mb-2">Tarjeta:</label>
                <input type="number" step="0.01" id="mixto-tarjeta" name="mixto_tarjeta" class="w-full border-2 rounded-xl p-3 mb-3 focus:border-primary focus:outline-none" placeholder="0.00">
                
                <label class="block text-sm font-semibold mb-2">Referencia tarjeta:</label>
                <input type="text" id="mixto-referencia" name="mixto_referencia" class="w-full border-2 rounded-xl p-3 focus:border-primary focus:outline-none" placeholder="Folio, referencia, etc.">
            </div>
            
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" id="cancel-payment" class="px-6 py-3 bg-gray-200 rounded-xl font-semibold hover:bg-gray-300 transition-all">Cancelar</button>
                <button type="submit" id="confirm-payment" class="px-6 py-3 text-white rounded-xl font-semibold transition-all hover:shadow-xl" style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);">Confirmar</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL DESCUENTO GENERAL -->
<div id="discount-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl p-8 w-96 animate-slide">
        <h2 class="text-xl font-bold mb-5" style="color: var(--secondary);">Descuento General</h2>
        <div class="flex gap-3 mb-5">
            <select id="discount-type" class="border-2 rounded-xl p-3 w-1/3 text-center font-semibold focus:border-primary focus:outline-none">
                <option value="percent">%</option>
                <option value="amount">$</option>
            </select>
            <input type="number" id="discount-input" class="border-2 rounded-xl p-3 w-2/3 focus:border-primary focus:outline-none" placeholder="Valor">
        </div>
        <div class="flex justify-end gap-3">
            <button id="close-discount" class="px-5 py-2.5 bg-gray-200 rounded-xl font-semibold hover:bg-gray-300 transition-all">Cancelar</button>
            <button id="apply-discount" class="px-5 py-2.5 text-white rounded-xl font-semibold transition-all hover:shadow-lg" style="background: var(--primary);">Aplicar</button>
        </div>
    </div>
</div>

<!-- MODAL DESCUENTO POR PRODUCTO -->
<div id="product-discount-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl p-8 w-96 animate-slide">
        <h2 class="text-xl font-bold mb-5" style="color: var(--secondary);">Descuento del Producto</h2>
        <div class="flex gap-3 mb-5">
            <select id="product-discount-type" class="border-2 rounded-xl p-3 w-1/3 text-center font-semibold focus:border-primary focus:outline-none">
                <option value="percent">%</option>
                <option value="amount">$</option>
            </select>
            <input type="number" id="product-discount-input" class="border-2 rounded-xl p-3 w-2/3 focus:border-primary focus:outline-none" placeholder="Valor">
        </div>
        <div class="flex justify-end gap-3">
            <button id="product-discount-close" class="px-5 py-2.5 bg-gray-200 rounded-xl font-semibold hover:bg-gray-300 transition-all">Cancelar</button>
            <button id="product-discount-apply" class="px-5 py-2.5 text-white rounded-xl font-semibold transition-all hover:shadow-lg" style="background: var(--primary);">Aplicar</button>
        </div>
    </div>
</div>

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

<!-- SCRIPTS -->
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

document.querySelectorAll('.producto').forEach(card => {
    const variants = JSON.parse(card.dataset.variants);
    const sizeSelect = card.querySelector('.variant-size');
    const colorSelect = card.querySelector('.variant-color');
    const stockText = card.querySelector('.stock-text');

    function updateColors() {
        const selectedSize = sizeSelect.value;
        // Filtrar colores disponibles para la talla seleccionada
        const availableColors = variants
            .filter(v => v.talla === selectedSize)
            .map(v => v.color);

        const uniqueColors = [...new Set(availableColors)];
        colorSelect.innerHTML = '';
        uniqueColors.forEach(c => {
            const option = document.createElement('option');
            option.value = c;
            option.textContent = c;
            colorSelect.appendChild(option);
        });

        // Después de actualizar colores, actualizar stock
        updateStock();
    }

    function updateStock() {
        const talla = sizeSelect.value;
        const color = colorSelect.value;
        const variante = variants.find(v => v.talla === talla && v.color === color);
        stockText.textContent = variante ? `Stock: ${variante.cantidad}` : 'Stock: 0';
    }

    if (variants.length) {
        sizeSelect.addEventListener('change', updateColors);
        colorSelect.addEventListener('change', updateStock);
        updateColors(); // inicializar select de colores según la talla inicial
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
</script>

</body>
</html>