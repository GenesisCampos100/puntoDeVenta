<?php
require_once __DIR__ . "/../config/db.php";

$busqueda   = $_GET['busqueda'] ?? '';
$categoria  = $_GET['categoria'] ?? '';
$orden      = $_GET['orden'] ?? 'nombre ASC';

// Consulta
$sql = "SELECT p.cod_barras, p.nombre, p.imagen, p.cantidad, c.nombre AS categoria, p.precio_unitario
        FROM productos p
        INNER JOIN categorias c ON p.id_categoria = c.id_categoria
        WHERE 1=1";

// Filtro búsqueda
if (!empty($busqueda)) {
    $sql .= " AND (p.nombre LIKE :busqueda OR p.cod_barras LIKE :busqueda)";
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
    font-family: Arial, sans-serif; 
    background: #f9fafb;
  }

  .productos-container {
    width: 95%;
    margin: 20px auto;
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  }

  /* --- Barra de herramientas --- */
  .toolbar {
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    margin-bottom: 20px;
  }

  .toolbar form {
    display: flex; 
    gap: 10px; 
    align-items: center;
  }

  .toolbar input[type="text"],
  .toolbar select {
    padding: 8px 12px;
    border: 1px solid #ccc;
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

  .toolbar a {
    padding: 8px 16px; 
    background: #e63946; 
    color: white; 
    border-radius: 6px; 
    text-decoration: none;
    font-weight: bold;
    transition: background 0.2s;
  }

  .toolbar a:hover {
    background: #c92a35;
  }

  /* --- Tabla --- */
  table {
    width: 100%;
    border-collapse: collapse;
    border-radius: 10px;
    overflow: hidden;
  }

  thead {
    background: #1f2937;
    color: white;
  }

  th, td {
    padding: 12px;
    text-align: center;
    border-bottom: 1px solid #eee;
  }

  tr:hover {
    background: #f3f4f6;
  }

  td img {
    width: 60px;
    height: 80px;
    object-fit: cover;
    border-radius: 6px;
    margin-right: 8px;
    vertical-align: middle;
  }

  /* --- Etiquetas de stock --- */
  .stock {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 15px;
    font-size: 13px;
    font-weight: bold;
    color: white;
  }
  .stock-bajo  { background-color: #ef4444; } /* rojo */
  .stock-medio { background-color: #f59e0b; } /* naranja */
  .stock-alto  { background-color: #10b981; } /* verde */

  /* --- Precio --- */
  .precio {
    color: #e63946;
    font-weight: bold;
    font-size: 15px;
  }
</style>

</head>
<body>
    <div>
     <h2 style="text-align:center; color:#e63946;">PRODUCTOS</h2>
</div>
    <!-- Barra de herramientas -->
<div class="toolbar" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
  <!-- Formulario búsqueda/filtro -->
  <form method="GET" style="display:flex; gap:10px;">
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
      <option value="nombre ASC"  <?= $orden=="nombre ASC" ? "selected" : "" ?>>Nombre (A-Z)</option>
      <option value="nombre DESC" <?= $orden=="nombre DESC" ? "selected" : "" ?>>Nombre (Z-A)</option>
      <option value="precio_unitario ASC"  <?= $orden=="precio_unitario ASC" ? "selected" : "" ?>>Precio (menor a mayor)</option>
      <option value="precio_unitario DESC" <?= $orden=="precio_unitario DESC" ? "selected" : "" ?>>Precio (mayor a menor)</option>
      <option value="cantidad ASC"  <?= $orden=="cantidad ASC" ? "selected" : "" ?>>Stock (menor a mayor)</option>
      <option value="cantidad DESC" <?= $orden=="cantidad DESC" ? "selected" : "" ?>>Stock (mayor a menor)</option>
    </select>

    <button type="submit">Aplicar</button>
  </form>

  <!-- Botón agregar producto -->
  <a href="agregar_producto.php" style="padding:8px 15px; background:#e3342f; color:white; border-radius:5px; text-decoration:none;">
    ➕ Agregar Producto
  </a>
</div>

    <div class="productos-container">
        <table>
  <thead>
    <tr>
      <th></th>
      <th>Producto</th>
      <th>Stock</th>
      <th>Categoría</th>
      <th>Precio</th>
    </tr>
  </thead>
  <tbody>
    <?php if (!empty($productos)): ?>
        <?php foreach ($productos as $producto): ?>
          <tr>
            <td><input type="checkbox" value="<?= $producto['cod_barras'] ?>"></td>
            <td>
              <img src="<?= $producto['imagen'] ?>" alt="<?= $producto['nombre'] ?>" width="50">
              <?= htmlspecialchars($producto['nombre']) ?>
            </td>
            <td><span class="badge"><?= $producto['cantidad'] ?> unidades</span></td>
            <td><?= htmlspecialchars($producto['categoria']) ?></td>
            <td>$<?= number_format($producto['precio_unitario'], 2) ?></td>
          </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="5">No hay productos registrados.</td></tr>
    <?php endif; ?>
  </tbody>
</table>
    </div>
</body>
</html>
