<?php
require_once __DIR__ . "/../config/db.php";

// búsqueda cliente (ajax)
if (isset($_GET['buscar_cliente'])) {
    $texto = $_GET['buscar_cliente'];
    $sql = $db->prepare("
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

// traer productos con variantes
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

// agrupar productos con variantes
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
            'talla_default' => $row['producto_talla'] ?: 'Única',
            'color_default' => $row['producto_color'] ?: 'Sin color',
            'stock' => $row['producto_cantidad'] ?: 0, // STOCK PRODUCTO
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
            'cantidad' => $row['variante_cantidad'] ?: 0, // STOCK VARIANTE
        ];
    }
}

// categorías
$categorias = $pdo->query("SELECT * FROM categorias")->fetchAll(PDO::FETCH_ASSOC);

function normalizeCategory($name) {
    return strtolower(trim(preg_replace('/\s+/', '', $name)));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Nueva Venta</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">

<!-- CATEGORÍAS -->
<div class="flex flex-wrap justify-start gap-2 mb-8 px-6 py-4">
  <button data-category="all" class="category-btn px-6 py-2 rounded-full text-white font-medium" style="background-color:#ec3678; font-size:.9rem">Todos</button>
  <?php foreach($categorias as $cat): ?>
    <button data-category="<?= normalizeCategory($cat['nombre']) ?>" class="category-btn px-6 py-2 rounded-full text-white font-medium" style="background-color:#ec3678; font-size:.9rem">
      <?= htmlspecialchars($cat['nombre']) ?>
    </button>
  <?php endforeach; ?>
</div>

<!-- GRID PRODUCTOS -->
<div class="px-6">
  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6" id="productos-grid">
    <?php foreach($productos as $prod): 
      $imagen = !empty($prod['imagen']) ? $prod['imagen'] : 'sin-imagen.png';
      $precio = $prod['precio'] ?: 0;
      $variants_json = htmlspecialchars(json_encode($prod['variantes'], JSON_UNESCAPED_UNICODE), ENT_QUOTES);
    ?>
      <article class="producto bg-white shadow rounded-lg p-4 text-center w-60"
               data-code="<?= htmlspecialchars($prod['producto_cod_barras']) ?>"
               data-name="<?= htmlspecialchars($prod['nombre']) ?>"
               data-img="../src/uploads/<?= htmlspecialchars($imagen) ?>"
               data-price="<?= htmlspecialchars($precio) ?>"
               data-category="<?= htmlspecialchars($prod['categoria']) ?>"
               data-stock="<?= $prod['stock'] ?>"
               data-variants='<?= $variants_json ?>'>
        
        <img src="../src/uploads/<?= htmlspecialchars($imagen) ?>" alt="<?= htmlspecialchars($prod['nombre']) ?>" class="w-full h-40 object-cover rounded product-image">
        
        <h3 class="mt-2 font-semibold"><?= htmlspecialchars($prod['nombre']) ?></h3>
        <p class="text-gray-500 text-sm"><?= htmlspecialchars($prod['categoria']) ?></p>
        <p class="text-lg font-bold mt-1 price">$<?= number_format($precio, 2) ?></p>

        <!-- STOCK MOSTRADO -->
        <p class="text-sm mt-1 text-green-600 font-semibold stock-text">
            Stock: <?= count($prod['variantes']) > 0 ? 'Según variante' : $prod['stock'] ?>
        </p>

        <select class="variant-size border rounded-lg px-2 py-1 text-sm mt-2 w-full">
          <?php 
            $sizes = array_unique(array_filter(array_map(fn($v)=>$v['talla'] ?? null, $prod['variantes'])));
            if (empty($sizes)) $sizes = [$prod['talla_default']];
            foreach ($sizes as $size): ?>
              <option value="<?= htmlspecialchars($size) ?>"><?= htmlspecialchars($size) ?></option>
          <?php endforeach; ?>
        </select>

        <select class="variant-color border rounded-lg px-2 py-1 text-sm mt-2 w-full">
          <?php 
            $colors = array_unique(array_filter(array_map(fn($v)=>$v['color'] ?? null, $prod['variantes'])));
            if (empty($colors)) $colors = [$prod['color_default']];
            foreach ($colors as $color): ?>
              <option value="<?= htmlspecialchars($color) ?>"><?= htmlspecialchars($color) ?></option>
          <?php endforeach; ?>
        </select>

        <button class="add-to-cart mt-3 bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded w-full">Agregar</button>
      </article>
    <?php endforeach; ?>
  </div>
</div>

</div>

<!-- CARRITO LATERAL -->
<aside id="cart" class="fixed top-0 right-0 w-80 h-full bg-white shadow-lg flex flex-col p-4 z-50">
  <div class="flex justify-between items-center mb-4">
    <h2 class="text-lg font-bold">Orden</h2>
    <div class="flex gap-2">
      <button id="client-btn" class="bg-blue-300 p-2 text-white rounded-full">👤</button>
      <button id="discount-btn" class="bg-yellow-300 p-2 text-white rounded-full">%</button>
      <button id="clear-cart" class="bg-red-100 p-2 rounded-full">🗑</button>
    </div>
  </div>

  <div id="cliente_info" class="hidden mb-4 bg-blue-50 p-3 rounded-lg">
    <p class="text-sm font-semibold">Cliente seleccionado:</p>
    <p id="cliente_nombre" class="text-gray-700">No hay cliente seleccionado</p>
    <div class="flex gap-2 mt-2">
      <button id="cambiarCliente" class="px-3 py-1 bg-blue-600 text-white rounded-lg text-sm">Cambiar</button>
      <button id="eliminarCliente" class="px-3 py-1 bg-red-600 text-white rounded-lg text-sm">Eliminar</button>
    </div>
    <input type="hidden" id="cliente_id" value="">
  </div>

  <div id="cart-items" class="flex-1 overflow-y-auto space-y-4"></div>

  <form id="checkout-form" class="mt-4">
    <input type="hidden" name="id_cliente" id="id_cliente">
    <div class="border-t pt-4 mt-4">
      <div class="flex justify-between text-sm"><span>Subtotal:</span><span id="subtotal">$0.00</span></div>
      <div class="flex justify-between text-sm text-red-500"><span>Descuento:</span><span id="discount">$0.00</span></div>
      <div class="flex justify-between font-bold text-lg mt-2"><span>Total:</span><span id="total">$0.00</span></div>
      <button type="button" id="pay-btn" class="w-full bg-lime-500 hover:bg-lime-600 text-white font-semibold py-2 rounded mt-4">Realizar Pago</button>
    </div>
  </form>
</aside>

<!-- MODAL CLIENTE -->
<div id="modalClientes" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
  <div class="bg-white w-full max-w-3xl rounded-lg shadow-lg p-6">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-xl font-bold">Seleccionar cliente</h2>
      <button id="cerrar-modal-cliente" class="text-gray-500 text-2xl">&times;</button>
    </div>
    <input type="text" id="buscarCliente" class="w-full border px-3 py-2 rounded mb-3" placeholder="Buscar cliente...">
    <div class="overflow-y-auto max-h-96">
      <table class="w-full text-left border">
        <thead class="bg-gray-100">
          <tr><th class="p-2 border">ID</th><th class="p-2 border">Cliente</th><th class="p-2 border">Teléfono</th><th class="p-2 border">Acción</th></tr>
        </thead>
        <tbody id="tablaClientes">
          <?php
            $sql = "SELECT * FROM clientes ORDER BY nombre ASC";
            $stmt = $pdo->query($sql);
            while ($cli = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $full = htmlspecialchars($cli['nombre'].' '.$cli['apellido_paterno'].' '.$cli['apellido_materno']);
                echo "<tr class='border-b'>
                  <td class='p-2 border'>{$cli['id_cliente']}</td>
                  <td class='p-2 border'>{$full}</td>
                  <td class='p-2 border'>".htmlspecialchars($cli['celular'])."</td>
                  <td class='p-2 border'>
                    <button class='bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded seleccionarCliente' data-id='{$cli['id_cliente']}' data-nombre='{$full}'>Seleccionar</button>
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
<div id="payment-modal" class="fixed inset-0 bg-black bg-opacity-40 hidden items-center justify-center z-50">
  <div class="bg-white rounded-2xl shadow-lg p-6 w-96">
    <h2 class="text-xl font-semibold mb-4 text-gray-800 text-center">Método de Pago</h2>

    <form id="payment-form">

      <!-- Datos que necesita procesar_venta.php -->
      <input type="hidden" name="cart_data" id="cart-data-input">
      <input type="hidden" id="cliente-input" name="id_cliente" value="">
      <input type="hidden" name="descuento_general" id="descuento-general-input">
      <input type="hidden" name="tipo_descuento_general" id="descuento-general-type">
      <input type="hidden" name="subtotal" id="subtotal-input">
      <input type="hidden" name="total" id="total-input">

      <!-- MÉTODO DE PAGO -->
      <div class="space-y-3 mb-6">
        <!-- EFECTIVO -->
        <label class="flex items-center gap-3 border rounded-lg p-3 cursor-pointer hover:bg-gray-50">
          <input type="radio" name="metodo" value="EFECTIVO" checked class="payment-method">
          <span>Efectivo 💵</span>
        </label>

        <!-- TARJETA -->
        <label class="flex items-center gap-3 border rounded-lg p-3 cursor-pointer hover:bg-gray-50">
          <input type="radio" name="metodo" value="TARJETA" class="payment-method">
          <span>Tarjeta 💳</span>
        </label>

        <!-- MIXTO -->
        <label class="flex items-center gap-3 border rounded-lg p-3 cursor-pointer hover:bg-gray-50">
          <input type="radio" name="metodo" value="MIXTO" class="payment-method">
          <span>Pago Mixto 💵💳</span>
        </label>
      </div>

      <!-- CAMPOS DE EFECTIVO -->
      <div id="efectivo-section" class="mb-4">
        <label class="block text-sm mb-1">Monto recibido (efectivo):</label>
        <input type="number" step="0.01" id="monto-efectivo" name="monto_efectivo"
               class="w-full border rounded-lg p-2" placeholder="0.00">
      </div>

      <!-- CAMPOS DE TARJETA -->
      <div id="tarjeta-section" class="mb-4 hidden">
        <label class="block text-sm mb-1">Monto pagado con tarjeta:</label>
        <input type="number" step="0.01" id="monto-tarjeta" name="monto_tarjeta"
               class="w-full border rounded-lg p-2" placeholder="0.00">

        <label class="block text-sm mt-2 mb-1">Referencia / Folio:</label>
        <input type="text" id="referencia-tarjeta" name="referencia_tarjeta"
               class="w-full border rounded-lg p-2" placeholder="Ingrese referencia">
      </div>

      <!-- CAMPOS MIXTOS -->
      <div id="mixto-section" class="mb-4 hidden">
        <label class="block text-sm mb-1">Efectivo:</label>
        <input type="number" step="0.01" id="mixto-efectivo" name="mixto_efectivo"
               class="w-full border rounded-lg p-2 mb-2" placeholder="0.00">

        <label class="block text-sm mb-1">Tarjeta:</label>
        <input type="number" step="0.01" id="mixto-tarjeta" name="mixto_tarjeta"
               class="w-full border rounded-lg p-2 mb-2" placeholder="0.00">

        <label class="block text-sm mb-1">Referencia tarjeta:</label>
        <input type="text" id="mixto-referencia" name="mixto_referencia"
               class="w-full border rounded-lg p-2" placeholder="Folio, referencia, etc.">
      </div>

      <!-- BOTONES -->
      <div class="flex justify-end gap-3 mt-6">
        <button type="button" id="cancel-payment" class="px-4 py-2 bg-gray-200 rounded-lg">Cancelar</button>
        <button type="submit" id="confirm-payment" class="px-4 py-2 bg-lime-600 text-white rounded-lg">Confirmar</button>
      </div>

    </form>

  </div>
</div>


<!-- MODALES DE DESCUENTO (sin cambios mayores) -->
<div id="discount-modal" class="hidden fixed inset-0 bg-black/40 items-center justify-center z-50">
  <div class="bg-white rounded-2xl shadow-xl p-6 w-80">
    <h2 class="text-lg font-bold mb-3">Descuento General</h2>
    <div class="flex gap-2 mb-3">
      <select id="discount-type" class="border rounded-lg p-2 w-1/3 text-center"><option value="percent">%</option><option value="amount">$</option></select>
      <input type="number" id="discount-input" class="border rounded-lg p-2 w-2/3" placeholder="Valor">
    </div>
    <div class="flex justify-end gap-2">
      <button id="close-discount" class="bg-gray-200 rounded-lg px-3 py-1">Cancelar</button>
      <button id="apply-discount" class="bg-lime-500 text-white rounded-lg px-3 py-1">Aplicar</button>
    </div>
  </div>
</div>

<div id="product-discount-modal" class="hidden fixed inset-0 bg-black/40 items-center justify-center z-50">
  <div class="bg-white rounded-2xl shadow-xl p-6 w-80">
    <h2 class="text-lg font-bold mb-3">Descuento del Producto</h2>
    <div class="flex gap-2 mb-3">
      <select id="product-discount-type" class="border rounded-lg p-2 w-1/3 text-center"><option value="percent">%</option><option value="amount">$</option></select>
      <input type="number" id="product-discount-input" class="border rounded-lg p-2 w-2/3" placeholder="Valor">
    </div>
    <div class="flex justify-end gap-2">
      <button id="product-discount-close" class="bg-gray-200 rounded-lg px-3 py-1">Cancelar</button>
      <button id="product-discount-apply" class="bg-lime-500 text-white rounded-lg px-3 py-1">Aplicar</button>
    </div>
  </div>
</div>

<script src="../src/scripts/cart.js"></script>
<script src="../src/scripts/modal.js"></script>

<script>
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

        const variante = variants.find(v =>
            v.talla === talla && v.color === color
        );

        stockText.textContent = variante
            ? `Stock: ${variante.cantidad}`
            : 'Stock: 0';
    }

    if (variants.length) {
        sizeSelect.addEventListener('change', updateStock);
        colorSelect.addEventListener('change', updateStock);
        updateStock();
    }
});
</script>

</body>
</html>
