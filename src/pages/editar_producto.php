<?php
require_once __DIR__ . "/../config/db.php";

// 📦 Cargar categorías
$stmt = $pdo->query("SELECT id_categoria, nombre FROM categorias ORDER BY nombre ASC");
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 🧾 Obtener producto principal
$id = $_GET['id'] ?? null;
if (!$id) die("ID de producto no especificado.");

$stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
$stmt->execute([$id]);
$producto = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$producto) die("Producto no encontrado.");

// 🧩 Obtener variantes (si las hay)
$stmtVar = $pdo->prepare("SELECT * FROM variantes WHERE id_producto = ?");
$stmtVar->execute([$id]);
$variantes = $stmtVar->fetchAll(PDO::FETCH_ASSOC);

// 🧾 Actualizar producto
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $nombre = trim($_POST['nombre']);
    $cod_barras = trim($_POST['cod_barras']);
    $id_categoria = $_POST['id_categoria'];
    $marca = trim($_POST['marca']);
    $descripcion = trim($_POST['descripcion']);
    $color_base = trim($_POST['color_base']);
    $cantidad = (int)$_POST['cantidad'];
    $cantidad_min = (int)$_POST['cantidad_min'];
    $costo = (float)$_POST['costo'];
    $tipo_costo = $_POST['tipo_costo'];
    $precio_unitario = (float)$_POST['precio_unitario'];

    // 🖼️ Imagen principal
    $imagen = $producto['imagen'];
    if (!empty($_FILES['imagen']['name'])) {
      $carpetaUploads = __DIR__ . "/../uploads/";
      if (!is_dir($carpetaUploads)) mkdir($carpetaUploads, 0777, true);
      $nombreArchivo = uniqid("img_") . "_" . basename($_FILES['imagen']['name']);
      $rutaDestino = $carpetaUploads . $nombreArchivo;
      if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)) {
        $imagen = $nombreArchivo;
      }
    }

    // 💾 Actualizar producto base
    $stmt = $pdo->prepare("UPDATE productos SET 
      nombre=?, cod_barras=?, id_categoria=?, marca=?, descripcion=?, color=?, imagen=?, 
      cantidad=?, cantidad_min=?, costo=?, tipo_costo=?, precio_unitario=? 
      WHERE id=?");

    $stmt->execute([
      $nombre, $cod_barras ?: null, $id_categoria, $marca, $descripcion,
      $color_base, $imagen, $cantidad, $cantidad_min, $costo, $tipo_costo, $precio_unitario, $id
    ]);

    // 🧩 Actualizar variantes
    $pdo->prepare("DELETE FROM variantes WHERE id_producto = ?")->execute([$id]);
    if (!empty($_POST['variantes'])) {
      $stmtVar = $pdo->prepare("INSERT INTO variantes 
        (id_producto, cod_barras, talla, color, imagen, cantidad, cantidad_min, costo, precio_unitario) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

      $totalCantidad = 0;
      foreach ($_POST['variantes'] as $index => $v) {
        $codVar = trim($v['cod_barras'] ?? '');
        if ($codVar === '') $codVar = "VAR-{$id}-" . ($index + 1);

        // Imagen individual
        $imgVar = null;
        if (!empty($_FILES['variantes']['name'][$index]['imagen'])) {
          $carpetaUploads = __DIR__ . "/../uploads/";
          $nombreArchivo = uniqid("var_") . "_" . basename($_FILES['variantes']['name'][$index]['imagen']);
          $rutaDestino = $carpetaUploads . $nombreArchivo;
          $tmp = $_FILES['variantes']['tmp_name'][$index]['imagen'];
          if (is_uploaded_file($tmp) && move_uploaded_file($tmp, $rutaDestino)) {
            $imgVar = $nombreArchivo;
          }
        }

        $cantidadVar = (int)($v['cantidad'] ?? 0);
        $totalCantidad += $cantidadVar;

        $stmtVar->execute([
          $id,
          $codVar,
          $v['talla'] ?? '',
          $v['color'] ?? '',
          $imgVar,
          $cantidadVar,
          (int)($v['cantidad_min'] ?? 0),
          (float)($v['costo'] ?? 0),
          (float)($v['precio_unitario'] ?? 0)
        ]);
      }

      // 🧮 Actualizar cantidad global
      $pdo->prepare("UPDATE productos SET cantidad=? WHERE id=?")->execute([$totalCantidad, $id]);
    }

    echo "<script>alert('✅ Producto actualizado correctamente'); window.location='index.php?view=productos';</script>";
    exit;

  } catch (Exception $e) {
    echo "<script>alert('❌ Error: " . addslashes($e->getMessage()) . "');</script>";
  }
}
?>

<!-- 🧾 FORMULARIO -->
<div class="producto-form">
  <h2>Editar producto</h2>

  <form method="post" enctype="multipart/form-data">
    <section>
      <h3>🧾 Datos generales</h3>
      <div class="grid">
        <div>
          <label>Nombre</label>
          <input type="text" name="nombre" value="<?= htmlspecialchars($producto['nombre']) ?>" required>
        </div>
        <div>
          <label>Código de barras</label>
          <input type="text" name="cod_barras" value="<?= htmlspecialchars($producto['cod_barras']) ?>">
        </div>
        <div>
          <label>Imagen principal</label><br>
          <?php if ($producto['imagen']): ?>
            <img src="../uploads/<?= htmlspecialchars($producto['imagen']) ?>" width="100"><br>
          <?php endif; ?>
          <input type="file" name="imagen" accept="image/*">
        </div>
      </div>
    </section>

    <section>
      <h3>🧩 Variantes</h3>
      <div id="variantes-container" class="variantes">
        <?php foreach ($variantes as $i => $v): ?>
          <div class="var">
            <input name="variantes[<?= $i ?>][cod_barras]" value="<?= htmlspecialchars($v['cod_barras']) ?>" placeholder="Código de barras">
            <input name="variantes[<?= $i ?>][talla]" value="<?= htmlspecialchars($v['talla']) ?>" placeholder="Talla">
            <input name="variantes[<?= $i ?>][color]" value="<?= htmlspecialchars($v['color']) ?>" placeholder="Color">
            <input name="variantes[<?= $i ?>][cantidad]" type="number" value="<?= htmlspecialchars($v['cantidad']) ?>" placeholder="Cantidad">
            <input name="variantes[<?= $i ?>][cantidad_min]" type="number" value="<?= htmlspecialchars($v['cantidad_min']) ?>" placeholder="Mínimo">
            <input name="variantes[<?= $i ?>][costo]" type="number" step="0.01" value="<?= htmlspecialchars($v['costo']) ?>" placeholder="Costo">
            <input name="variantes[<?= $i ?>][precio_unitario]" type="number" step="0.01" value="<?= htmlspecialchars($v['precio_unitario']) ?>" placeholder="Precio">
            <input type="file" name="variantes[<?= $i ?>][imagen]" accept="image/*">
            <?php if ($v['imagen']): ?>
              <img src="../uploads/<?= htmlspecialchars($v['imagen']) ?>" width="60">
            <?php endif; ?>
            <button type="button" class="remove">🗑️</button>
          </div>
        <?php endforeach; ?>
      </div>
      <button type="button" id="add-variant" class="btn-secundario">+ Agregar variante</button>
    </section>

    <section>
      <h3>⚙️ Datos adicionales</h3>
      <div class="grid">
        <div>
          <label>Categoría</label>
          <select name="id_categoria" required>
            <?php foreach ($categorias as $cat): ?>
              <option value="<?= $cat['id_categoria'] ?>" <?= $cat['id_categoria'] == $producto['id_categoria'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['nombre']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>Marca</label>
          <input type="text" name="marca" value="<?= htmlspecialchars($producto['marca']) ?>">
        </div>
        <div class="full">
          <label>Descripción</label>
          <textarea name="descripcion"><?= htmlspecialchars($producto['descripcion']) ?></textarea>
        </div>
        <div class="full">
          <label>Color base</label>
          <input type="text" name="color_base" value="<?= htmlspecialchars($producto['color']) ?>">
        </div>
      </div>
    </section>

    <section>
      <h3>📦 Inventario</h3>
      <div class="grid">
        <div>
          <label>Cantidad</label>
          <input type="number" name="cantidad" value="<?= htmlspecialchars($producto['cantidad']) ?>">
        </div>
        <div>
          <label>Cantidad mínima</label>
          <input type="number" name="cantidad_min" value="<?= htmlspecialchars($producto['cantidad_min']) ?>">
        </div>
      </div>
    </section>

    <section>
      <h3>💰 Costo</h3>
      <div class="grid">
        <div>
          <label>Costo</label>
          <input type="number" name="costo" step="0.01" value="<?= htmlspecialchars($producto['costo']) ?>">
        </div>
        <div>
          <label>Tipo de costo</label>
          <select name="tipo_costo">
            <option value="bruto" <?= $producto['tipo_costo'] === 'bruto' ? 'selected' : '' ?>>Bruto</option>
            <option value="neto" <?= $producto['tipo_costo'] === 'neto' ? 'selected' : '' ?>>Neto</option>
          </select>
        </div>
      </div>
    </section>

    <section>
      <h3>💵 Precio de venta</h3>
      <div class="grid">
        <div>
          <label>Precio unitario</label>
          <input type="number" step="0.01" name="precio_unitario" value="<?= htmlspecialchars($producto['precio_unitario']) ?>">
        </div>
      </div>
    </section>

    <div class="botones">
      <button type="submit" class="btn-principal">💾 Guardar cambios</button>
      <a href="index.php?view=productos" class="btn-cancelar">Cancelar</a>
    </div>
  </form>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
  let idx = document.querySelectorAll('#variantes-container .var').length;
  const cont = document.getElementById('variantes-container');
  const addBtn = document.getElementById('add-variant');

  addBtn.addEventListener('click', () => {
    const html = `
      <div class="var">
        <input name="variantes[${idx}][cod_barras]" placeholder="Código de barras">
        <input name="variantes[${idx}][talla]" placeholder="Talla">
        <input name="variantes[${idx}][color]" placeholder="Color">
        <input name="variantes[${idx}][cantidad]" type="number" min="0" placeholder="Cantidad">
        <input name="variantes[${idx}][cantidad_min]" type="number" min="0" placeholder="Mínimo">
        <input name="variantes[${idx}][costo]" type="number" step="0.01" placeholder="Costo">
        <input name="variantes[${idx}][precio_unitario]" type="number" step="0.01" placeholder="Precio">
        <input type="file" name="variantes[${idx}][imagen]" accept="image/*">
        <button type="button" class="remove">🗑️</button>
      </div>`;
    cont.insertAdjacentHTML('beforeend', html);
    cont.querySelectorAll('.remove').forEach(btn => btn.onclick = e => e.target.closest('.var').remove());
    idx++;
  });

  cont.querySelectorAll('.remove').forEach(btn => btn.onclick = e => e.target.closest('.var').remove());
});
</script>

<link rel="stylesheet" href="agregar_producto.css">

<style>
:root {
  --azul: #2563eb;
  --verde: #22c55e;
  --rojo: #ef4444;
  --gris-claro: #f9fafb;
  --borde: #e5e7eb;
  --texto: #374151;
}

body {
  background: #f3f4f6;
   font-family: 'Poppins', sans-serif; 
}

.producto-form {
  width: 90%;
  max-width: 900px;
  margin: 40px auto;
  background: white;
  padding: 35px 40px;
  border-radius: 16px;
  box-shadow: 0 6px 20px rgba(0,0,0,0.08);
}

h2 {
  text-align: center;
  margin-bottom: 30px;
  color: var(--texto);
  font-weight: 600;
}

section {
  margin-bottom: 25px;
  padding-bottom: 20px;
  border-bottom: 1px solid var(--borde);
}

h3 {
  color: var(--azul);
  font-size: 1.1rem;
  margin-bottom: 12px;
}

.grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 18px;
}

.full { grid-column: 1 / -1; }

label {
  display: block;
  font-weight: 600;
  margin-bottom: 5px;
  color: var(--texto);
}

input, select, textarea {
  width: 100%;
  padding: 8px 10px;
  border: 1px solid var(--borde);
  border-radius: 8px;
  transition: 0.2s;
}

input:focus, select:focus, textarea:focus {
  border-color: var(--azul);
  outline: none;
  box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
}

.variantes {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-top: 10px;
}

.var {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  background: var(--gris-claro);
  padding: 12px;
  border: 1px solid var(--borde);
  border-radius: 10px;
}

.var input {
  flex: 1;
  min-width: 100px;
}

.remove {
  background: var(--rojo);
  color: white;
  border: none;
  border-radius: 8px;
  padding: 8px 10px;
  cursor: pointer;
  transition: 0.2s;
}
.remove:hover { background: #dc2626; }

.btn-principal, .btn-secundario, .btn-cancelar {
  border: none;
  border-radius: 10px;
  padding: 10px 18px;
  cursor: pointer;
  font-weight: 600;
  transition: 0.2s;
}

.btn-principal {
  background: var(--verde);
  color: white;
}

.btn-secundario {
  background: var(--azul);
  color: white;
  margin-top: 10px;
}

.btn-cancelar {
  background: var(--rojo);
  color: white;
  text-decoration: none;
}

.botones {
  margin-top: 25px;
  display: flex;
  justify-content: center;
  gap: 15px;
}

.info {
  font-size: 0.9em;
  color: #6b7280;
}

.disabled {
  opacity: 0.6;
}

.msg-variantes {
  margin-top: 12px;
  background: #fef2f2;
  border: 1px solid #fecaca;
  padding: 10px 14px;
  border-radius: 8px;
  color: #b91c1c;
  font-size: 0.9em;
  display: flex;
  align-items: center;
  gap: 8px;
}
.hidden { display: none; }

/* 🌫️ Estilo visual para campos bloqueados */
input:disabled, select:disabled, textarea:disabled {
  background-color: #e5e7eb !important;
  color: #9ca3af !important;
  cursor: not-allowed;
  opacity: 0.8;
}
</style>
