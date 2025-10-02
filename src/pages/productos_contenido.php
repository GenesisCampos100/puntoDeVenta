<?php
require_once __DIR__ . "/../config/db.php";

// Consulta
$sql = "SELECT p.cod_barras, p.nombre, p.imagen, p.cantidad, c.nombre AS categoria, p.precio_unitario
        FROM productos p
        INNER JOIN categorias c ON p.id_categoria = c.id_categoria";

$stmt = $pdo->query($sql);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Productos</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .productos-container {
            width: 90%;
            margin: 20px auto;
        }
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
            border-bottom: 1px solid #ddd;
        }
        td img {
            width: 60px;
            height: 80px;
            object-fit: cover;
            border-radius: 6px;
        }
        .stock {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 14px;
            font-weight: bold;
            color: white;
        }
        .stock-bajo { background-color: red; }
        .stock-medio { background-color: orange; }
        .stock-alto { background-color: green; }
        .precio {
            color: #e63946;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="productos-container">
        <h2 style="text-align:center; color:#e63946;">PRODUCTOS</h2>
        
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
