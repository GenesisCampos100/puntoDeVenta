<?php
require_once __DIR__ . "/../config/db.php";

$busqueda   = $_GET['busqueda'] ?? '';
$categoria  = $_GET['categoria'] ?? '';
$orden      = $_GET['orden'] ?? 'p.nombre ASC';

// Consulta
$sql = "SELECT 
            p.id AS id_producto,
            p.cod_barras AS producto_cod_barras,
            p.nombre AS producto_nombre,
            p.imagen AS producto_imagen,
            p.marca,
            c.nombre AS categoria,
            v.id AS id_variante,
            v.cod_barras AS variante_cod_barras,
            v.talla,
            v.color,
            v.imagen AS imagen_variante,
            v.cantidad,
            v.cantidad_min,
            v.precio_unitario,
            v.margen,
            v.ganancia
        FROM productos p
        LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
        LEFT JOIN variantes v ON v.id_producto = p.id
        WHERE 1=1";

// Filtro búsqueda
if (!empty($busqueda)) {
    $sql .= " AND (p.nombre LIKE :busqueda OR p.cod_barras LIKE :busqueda OR v.cod_barras LIKE :busqueda)";
}

// Filtro categoría
if (!empty($categoria)) {
    $sql .= " AND c.id_categoria = :categoria";
}

// Orden
$sql .= " ORDER BY $orden";

$stmt = $pdo->prepare($sql);

// Vincular parámetros
if (!empty($busqueda)) {
    $stmt->bindValue(':busqueda', "%$busqueda%");
}
if (!empty($categoria)) {
    $stmt->bindValue(':categoria', $categoria, PDO::PARAM_INT);
}

$stmt->execute();
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traer categorías para el filtro
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
}

h2 {
  text-align: center;
  color: #e63946;
  margin-top: 30px;
  letter-spacing: 1px;
}

/* Barra de herramientas */
.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin: 25px auto;
  width: 90%;
  flex-wrap: wrap;
  gap: 10px;
}

.toolbar form {
  display: flex;
  gap: 10px;
  align-items: center;
  flex-wrap: wrap;
}

.toolbar input[type="text"],
.toolbar select {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
}

.toolbar button {
  background: #1f2937;
  color: white;
  padding: 8px 14px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
}

.toolbar button:hover {
  background: #374151;
}

.btn-agregar {
  padding: 10px 16px;
  background: #e63946;
  color: white;
  border-radius: 6px;
  text-decoration: none;
  font-weight: bold;
  transition: background 0.2s;
}

.btn-agregar:hover {
  background: #c92a35;
}

/* Tabla */
.productos-container {
  width: 95%;
  margin: 0 auto 40px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  overflow: hidden;
}

table {
  width: 100%;
  border-collapse: collapse;
}

thead {
  background: #1f2937;
  color: white;
}

th, td {
  padding: 14px;
  text-align: left;
  border-bottom: 1px solid #eee;
}

th:first-child, td:first-child {
  text-align: center;
  width: 40px;
}

tr:hover {
  background: #f3f4f6;
}

/* Imagen y nombre */
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

/* Stock */
.stock {
  display: inline-block;
  padding: 6px 14px;
  border-radius: 15px;
  font-size: 13px;
  font-weight: 600;
  color: white;
}
.stock-bajo  { background-color: #ef4444; }
.stock-medio { background-color: #f59e0b; }
.stock-alto  { background-color: #10b981; }

/* Precio */
.precio {
  color: #e63946;
  font-weight: bold;
  font-size: 15px;
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
      <option value="v.precio_unitario ASC"  <?= $orden=="v.precio_unitario ASC" ? "selected" : "" ?>>Precio (menor a mayor)</option>
      <option value="v.precio_unitario DESC" <?= $orden=="v.precio_unitario DESC" ? "selected" : "" ?>>Precio (mayor a menor)</option>
      <option value="v.cantidad ASC"  <?= $orden=="v.cantidad ASC" ? "selected" : "" ?>>Stock (menor a mayor)</option>
      <option value="v.cantidad DESC" <?= $orden=="v.cantidad DESC" ? "selected" : "" ?>>Stock (mayor a menor)</option>
    </select>
    <button type="submit">Aplicar</button>
  </form>

<a href="index.php?view=agregar_producto" class="btn-agregar">➕ Agregar Producto</a>
</div>

<div class="productos-container">
  <table>
    <thead>
      <tr>
        <th><input type="checkbox"></th>
        <th>Producto</th>
        <th>Stock</th>
        <th>Categoría</th>
        <th>Precio</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($productos)): ?>
        <?php foreach ($productos as $producto): ?>
          <?php
            $cantidad = (int)$producto['cantidad'];
            $cantidad_min = (int)$producto['cantidad_min'];
            if ($cantidad <= $cantidad_min) {
                $stockClass = 'stock-bajo';
            } elseif ($cantidad <= $cantidad_min + 10) {
                $stockClass = 'stock-medio';
            } else {
                $stockClass = 'stock-alto';
            }

               $imagen = '../uploads/sin-imagen.png';

              if (!empty($producto['imagen_variante'])) {
                  $imagen = '../uploads/' . htmlspecialchars($producto['imagen_variante']);
              } elseif (!empty($producto['producto_imagen'])) {
                  $imagen = '../uploads/' . htmlspecialchars($producto['producto_imagen']);
              }
          ?>
          <tr>
            <td><input type="checkbox"></td>
            <td>
            <div class="producto-info">
            <img src="<?= htmlspecialchars($imagen) ?>" alt="Imagen del producto">
            <div>
              <strong><?= htmlspecialchars($producto['producto_nombre']) ?></strong><br>
              <small>Talla: <?= htmlspecialchars($producto['talla']) ?> | Color: <?= htmlspecialchars($producto['color']) ?></small>
            </div>
          </div>
            </td>
            <td><span class="stock <?= $stockClass ?>"><?= $cantidad ?> unidades</span></td>
            <td><?= htmlspecialchars($producto['categoria']) ?></td>
            <td class="precio">$<?= number_format($producto['precio_unitario'], 2) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="5" style="text-align:center;">No hay productos registrados.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

</body>
</html>