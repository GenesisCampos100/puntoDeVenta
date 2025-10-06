<?php 
require_once __DIR__ . "/../config/db.php";

$busqueda   = $_GET['busqueda'] ?? '';
$categoria  = $_GET['categoria'] ?? '';
$orden      = $_GET['orden'] ?? 'p.nombre ASC';

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
            p.cantidad_min,
            p.costo,
            p.precio_unitario,
            (SELECT COUNT(*) FROM variantes v2 WHERE v2.id_producto = p.id) AS tiene_variante
        FROM productos p
        LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
        WHERE 1=1";

if (!empty($busqueda)) $sql .= " AND (p.nombre LIKE :busqueda OR p.cod_barras LIKE :busqueda)";
if (!empty($categoria)) $sql .= " AND c.id_categoria = :categoria";
$sql .= " ORDER BY $orden";

$stmt = $pdo->prepare($sql);
if (!empty($busqueda)) $stmt->bindValue(':busqueda', "%$busqueda%");
if (!empty($categoria)) $stmt->bindValue(':categoria', $categoria, PDO::PARAM_INT);
$stmt->execute();
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ================================
// 2️⃣ Consulta de variantes
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

// ================================
// 3️⃣ Categorías para filtro
// ================================
$categorias = $pdo->query("SELECT * FROM categorias")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Productos</title>
<style>
body {
  font-family: 'Poppins', sans-serif;
  background: #f9fafb;
  margin: 0;
  padding: 0;
}
h2 {
  text-align: center;
  color: #e63946;
  margin-top: 30px;
  letter-spacing: 1px;
}
.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin: 25px auto;
  width: 90%;
  flex-wrap: wrap;
  gap: 10px;
}
.toolbar input, .toolbar select {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
}
.toolbar button {
  background: #1f2937;
  color: white;
  padding: 8px 14px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}
.btn-agregar {
  background: #e63946;
  color: white;
  padding: 10px 16px;
  border-radius: 6px;
  text-decoration: none;
  font-weight: bold;
}
.productos-container {
  width: 95%;
  margin: 0 auto 40px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  overflow: hidden;
}
table { width: 100%; border-collapse: collapse; }
thead { background: #1f2937; color: white; }
th, td { padding: 14px; text-align: left; border-bottom: 1px solid #eee; }
tr:hover { background: #f3f4f6; }
.producto-info {
  display: flex;
  align-items: center;
  gap: 10px;
}
.producto-info img {
  width: 60px;
  height: 80px;
  object-fit: cover;
  border-radius: 6px;
  background: #f1f1f1;
}
.stock { padding: 6px 14px; border-radius: 15px; color: white; font-size: 13px; font-weight: 600; }
.stock-bajo { background-color: #ef4444; }
.stock-medio { background-color: #f59e0b; }
.stock-alto { background-color: #10b981; }
.precio { color: #e63946; font-weight: bold; font-size: 15px; }
.btn-mas {
  background: #e63946;
  color: white;
  border: none;
  padding: 6px 10px;
  border-radius: 6px;
  cursor: pointer;
}
.fila-variantes table {
  border-radius: 8px;
  background: #fff;
  margin-top: 5px;
  font-size: 14px;
}
.fila-variantes th {
  background: #f3f4f6;
  color: #333;
}
.fila-variantes img {
  width: 50px;
  height: 60px;
  border-radius: 6px;
  object-fit: cover;
}

/* Modal */
.modal {
  position: fixed; top: 0; left: 0; width: 100%; height: 100%;
  background: rgba(0,0,0,0.4);
  display: none; justify-content: center; align-items: center;
  z-index: 1000;
}
.modal-content {
  background: white;
  border-radius: 16px;
  padding: 25px;
  width: 420px;
  text-align: center;
  position: relative;
  animation: aparecer 0.3s ease;
}
@keyframes aparecer { from { transform: translateY(-20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
.modal-content img {
  width: 150px; height: 180px; object-fit: cover;
  border-radius: 10px; margin-bottom: 15px;
}
.modal-content h3 { color: #e63946; margin-bottom: 10px; }
.modal-content p { margin: 5px 0; color: #333; font-size: 15px; }
.modal-buttons { margin-top: 15px; display: flex; justify-content: center; gap: 15px; }
.cerrar {
  position: absolute; top: 10px; left: 10px; border: none; background: none; font-size: 22px; color: #e63946; cursor: pointer;
}
</style>
</head>
<body>

<h2>PRODUCTOS</h2>

<div class="toolbar">
  <form method="GET">
    <input type="text" name="busqueda" placeholder="Buscar producto..." value="<?= htmlspecialchars($busqueda) ?>">
    <select name="categoria">
      <option value="">-- Todas las categorías --</option>
      <?php foreach ($categorias as $cat): ?>
        <option value="<?= $cat['id_categoria'] ?>" <?= ($categoria == $cat['id_categoria']) ? 'selected' : '' ?>>
          <?= htmlspecialchars($cat['nombre']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <select name="orden">
      <option value="p.nombre ASC"  <?= $orden=="p.nombre ASC" ? "selected" : "" ?>>Nombre (A-Z)</option>
      <option value="p.nombre DESC" <?= $orden=="p.nombre DESC" ? "selected" : "" ?>>Nombre (Z-A)</option>
      <option value="p.precio_unitario ASC"  <?= $orden=="p.precio_unitario ASC" ? "selected" : "" ?>>Precio ↑</option>
      <option value="p.precio_unitario DESC" <?= $orden=="p.precio_unitario DESC" ? "selected" : "" ?>>Precio ↓</option>
    </select>
    <button type="submit">Aplicar</button>
  </form>
  <a href="index.php?view=agregar_producto" class="btn-agregar">➕ Agregar Producto</a>
</div>

<div class="productos-container">
  <table>
    <thead>
      <tr>
        <th></th><th>Producto</th><th>Stock</th><th>Categoría</th><th>Precio</th><th>Acción</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($productos)): ?>
        <?php foreach ($productos as $producto): 
          $cantidad = (int)$producto['cantidad'];
          $cantidad_min = (int)$producto['cantidad_min'];
          if ($cantidad <= $cantidad_min) $stockClass = 'stock-bajo';
          elseif ($cantidad <= $cantidad_min + 10) $stockClass = 'stock-medio';
          else $stockClass = 'stock-alto';
          $imagen = !empty($producto['producto_imagen']) ? "uploads/{$producto['producto_imagen']}" : "../uploads/sin-imagen.png";
        ?>
        <tr>
          <td><input type="checkbox"></td>
          <td>
            <div class="producto-info">
              <img src="<?= htmlspecialchars($imagen) ?>" alt="Producto">
              <div><strong><?= htmlspecialchars($producto['producto_nombre']) ?></strong><br>
              <small><?= htmlspecialchars($producto['marca'] ?: 'Sin marca') ?></small></div>
            </div>
          </td>
          <td><span class="stock <?= $stockClass ?>"><?= $cantidad ?></span></td>
          <td><?= htmlspecialchars($producto['categoria']) ?></td>
          <td class="precio">$<?= number_format($producto['precio_unitario'], 2) ?></td>
          <td><button class="btn-mas" onclick='mostrarDetalle(<?= json_encode($producto) ?>)'>➕ Más</button></td>
        </tr>

       <!-- Mostrar tabla de variantes -->
<?php if ($producto['tiene_variante'] > 0 && !empty($variantesPorProducto[$producto['id_producto']])): ?>
<tr class="fila-variantes" style="display:none;" id="variantes-<?= $producto['id_producto'] ?>">
  <td colspan="6">
    <table style="width:100%;">
      <thead>
        <tr><th>Variante</th><th>Stock</th><th>Precio Venta</th><th>Detalles</th></tr>
      </thead>
      <tbody>
        <?php foreach ($variantesPorProducto[$producto['id_producto']] as $var): ?>
        <tr>
          <td>
            <div class="producto-info">
              <img src="<?= !empty($var['imagen']) ? 'uploads/'.$var['imagen'] : '../uploads/sin-imagen.png' ?>">
              <div>
                <strong><?= htmlspecialchars($var['talla'] ?: '—') ?></strong><br>
                <small><?= htmlspecialchars($var['color'] ?: '—') ?></small>
              </div>
            </div>
          </td>
         <?php
  // Heredamos la categoría del producto padre
  $var['categoria'] = $producto['categoria'];
?>
<td><span class="stock"><?= (int)$var['cantidad'] ?></span></td>
<td class="precio">$<?= number_format($var['precio_unitario'], 2) ?></td>
<td>
  <button class="btn-mas" onclick='mostrarVariante(<?= json_encode($var) ?>)'>➕ Más</button>
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
        <tr><td colspan="6" style="text-align:center;">No hay productos registrados.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Modal -->
<div id="modal" class="modal">
  <div class="modal-content">
    <button class="cerrar" onclick="cerrarModal()">←</button>
    <img id="modal-img" src="" alt="Producto">
    <h3 id="modal-nombre"></h3>
    <p><strong>Categoría:</strong> <span id="modal-categoria"></span></p>
    <p><strong>Código de barras:</strong> <span id="modal-codigo"></span></p>
    <p><strong>Precio venta:</strong> $<span id="modal-precio"></span></p>
    <p><strong>Precio compra:</strong> $<span id="modal-costo"></span></p>
    <p><strong>Existencias:</strong> <span id="modal-stock"></span></p>
  </div>
</div>

<script>
/**
 * Muestra detalle o despliega variantes según corresponda.
 * - si tiene variantes: despliega/oculta la fila <tr id="variantes-<id>">
 * - si no tiene variantes: abre el modal con los datos del producto
 */
function mostrarDetalle(data) {
  // sanity checks: data puede venir como string para tiene_variante; convertir a número
  const tiene = data.tiene_variante ? parseInt(data.tiene_variante, 10) : 0;
  // si hay variantes intentamos mostrar la fila correspondiente
  if (tiene > 0) {
    const filaId = 'variantes-' + data.id_producto;
    const filaVar = document.getElementById(filaId);

    if (!filaVar) {
      // fila no encontrada -> mostrar modal como fallback (evita silencio total)
      console.warn('Fila de variantes no encontrada para', filaId, '. Abriendo modal como fallback.');
      mostrarModalProducto(data);
      return;
    }

    // alternar visibilidad (soporta estado inicial '' o 'none')
    const isHidden = (filaVar.style.display === 'none' || filaVar.style.display === '');
    filaVar.style.display = isHidden ? 'table-row' : 'none';

    // opcional: scrollear suavemente cuando se abre
    if (isHidden) {
      filaVar.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

  } else {
    // no tiene variantes -> mostrar modal del producto
    mostrarModalProducto(data);
  }
}

/** abre el modal con los datos de producto (nombre, imagen, precio, costo, stock, etc.) */
function mostrarModalProducto(data) {
  // asegúrate de usar las mismas claves que genera PHP en json_encode($producto)
  document.getElementById('modal-img').src = data.producto_imagen ? "uploads/" + data.producto_imagen : "uploads/sin-imagen.png";
  document.getElementById('modal-nombre').textContent = data.producto_nombre || 'Sin nombre';
  document.getElementById('modal-categoria').textContent = data.categoria || 'Sin categoría';
  document.getElementById('modal-codigo').textContent = data.producto_cod_barras || 'N/A';
  document.getElementById('modal-precio').textContent = (typeof data.precio_unitario !== 'undefined' && data.precio_unitario !== null) ? parseFloat(data.precio_unitario).toFixed(2) : '—';
  document.getElementById('modal-costo').textContent = (typeof data.costo !== 'undefined' && data.costo !== null) ? parseFloat(data.costo).toFixed(2) : '—';
  document.getElementById('modal-stock').textContent = (typeof data.cantidad !== 'undefined') ? data.cantidad : '—';
  document.getElementById('modal').style.display = 'flex';
}

/** abre el modal con datos de una variante (misma info que producto) */
function mostrarVariante(v) {
  document.getElementById('modal-img').src = v.imagen ? "uploads/" + v.imagen : "uploads/sin-imagen.png";
  document.getElementById('modal-nombre').textContent = `Variante ${v.talla || '—'} (${v.color || '—'})`;
  document.getElementById('modal-categoria').textContent = v.categoria || 'Sin categoría';
  document.getElementById('modal-codigo').textContent = v.cod_barras || 'N/A';
  document.getElementById('modal-precio').textContent = (typeof v.precio_unitario !== 'undefined' && v.precio_unitario !== null) ? parseFloat(v.precio_unitario).toFixed(2) : '—';
  document.getElementById('modal-costo').textContent = (typeof v.costo !== 'undefined' && v.costo !== null) ? parseFloat(v.costo).toFixed(2) : '—';
  document.getElementById('modal-stock').textContent = (typeof v.cantidad !== 'undefined') ? v.cantidad : '—';
  document.getElementById('modal').style.display = 'flex';
}

function cerrarModal() {
  document.getElementById('modal').style.display = 'none';
}
</script>


</body>
</html>
