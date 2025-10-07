
<?php
require_once __DIR__ . "/../config/db.php";

// 🧾 Obtener datos de la variante y su producto
$id = $_GET['id'] ?? null;
$prod_id = $_GET['prod_id'] ?? null;

if (!$id || !$prod_id) {
    die("Error: Faltan parámetros (id o prod_id).");
}

// Cargar la variante
$stmt = $pdo->prepare("SELECT * FROM variantes WHERE id = ?");
$stmt->execute([$id]);
$variante = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$variante) {
    die("Error: Variante no encontrada.");
}

// Cargar nombre del producto
$stmtProd = $pdo->prepare("SELECT nombre FROM productos WHERE id = ?");
$stmtProd->execute([$prod_id]);
$producto = $stmtProd->fetch(PDO::FETCH_ASSOC);
$nombre_producto = $producto['nombre'] ?? 'Producto desconocido';

// 🧾 Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $cod_barras = trim($_POST['cod_barras'] ?? '');
        $talla = trim($_POST['talla'] ?? '');
        $color = trim($_POST['color'] ?? '');
        $cantidad = (int)($_POST['cantidad'] ?? 0);
        $cantidad_min = (int)($_POST['cantidad_min'] ?? 0);
        $costo = (float)($_POST['costo'] ?? 0);
        $precio_unitario = (float)($_POST['precio_unitario'] ?? 0);

        if ($talla === '' && $color === '') {
            throw new Exception("Debe indicar al menos una talla o un color.");
        }

        // 📸 Imagen nueva (si se cambia)
        $imagen = $variante['imagen'];
        if (!empty($_FILES['imagen']['name'])) {
            $carpetaUploads = __DIR__ . "/../uploads/";
            if (!is_dir($carpetaUploads)) mkdir($carpetaUploads, 0777, true);
            $nombreArchivo = uniqid("var_") . "_" . basename($_FILES['imagen']['name']);
            $rutaDestino = $carpetaUploads . $nombreArchivo;

            if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)) {
                throw new Exception("Error al subir la imagen. Verifica permisos.");
            }

            // 🧹 Opcional: eliminar la imagen anterior si existía
            if (!empty($variante['imagen']) && file_exists($carpetaUploads . $variante['imagen'])) {
                @unlink($carpetaUploads . $variante['imagen']);
            }

            $imagen = $nombreArchivo;
        }

        // 💾 Actualizar variante
        $stmt = $pdo->prepare("UPDATE variantes SET 
            cod_barras = ?, talla = ?, color = ?, imagen = ?, cantidad = ?, cantidad_min = ?, costo = ?, precio_unitario = ?
            WHERE id = ?");
        $stmt->execute([$cod_barras, $talla, $color, $imagen, $cantidad, $cantidad_min, $costo, $precio_unitario, $id]);

        // 🔄 Recalcular stock total del producto
        $stmtSum = $pdo->prepare("SELECT SUM(cantidad) AS total FROM variantes WHERE id_producto = ?");
        $stmtSum->execute([$prod_id]);
        $total = $stmtSum->fetchColumn() ?: 0;
        $pdo->prepare("UPDATE productos SET cantidad = ? WHERE id = ?")->execute([$total, $prod_id]);

        echo "<script>alert('✅ Variante actualizada correctamente'); window.location='index.php?view=productos';</script>";
        exit;

    } catch (Exception $e) {
        echo "<script>alert('❌ Error: " . addslashes($e->getMessage()) . "');</script>";
    }
}
?>

<!-- 🧾 FORMULARIO -->
<div class="producto-form">
  <h2>Editar variante del producto: <span style="color:#3b82f6;"><?= htmlspecialchars($nombre_producto) ?></span></h2>

  <form method="post" enctype="multipart/form-data">
    <section>
      <h3>🧩 Datos de la variante</h3>
      <div class="grid">
        <div>
          <label>Código de barras</label>
          <input type="text" name="cod_barras" value="<?= htmlspecialchars($variante['cod_barras']) ?>">
        </div>
        <div>
          <label>Talla</label>
          <input type="text" name="talla" value="<?= htmlspecialchars($variante['talla']) ?>">
        </div>
        <div>
          <label>Color</label>
          <input type="text" name="color" value="<?= htmlspecialchars($variante['color']) ?>">
        </div>
        <div>
          <label>Imagen actual</label><br>
          <?php if (!empty($variante['imagen']) && file_exists(__DIR__ . '/../uploads/' . $variante['imagen'])): ?>
              <img src="../uploads/<?= htmlspecialchars($variante['imagen']) ?>" alt="Imagen" style="max-width:100px;border-radius:8px;">
          <?php else: ?>
              <p>Sin imagen</p>
          <?php endif; ?>
        </div>
        <div>
          <label>Cambiar imagen</label>
          <input type="file" name="imagen" accept="image/*">
        </div>
      </div>
    </section>

    <section>
      <h3>📦 Inventario</h3>
      <div class="grid">
        <div>
          <label>Cantidad</label>
          <input type="number" name="cantidad" min="0" value="<?= htmlspecialchars($variante['cantidad']) ?>">
        </div>
        <div>
          <label>Cantidad mínima</label>
          <input type="number" name="cantidad_min" min="0" value="<?= htmlspecialchars($variante['cantidad_min']) ?>">
        </div>
      </div>
    </section>

    <section>
      <h3>💰 Costo y precio</h3>
      <div class="grid">
        <div>
          <label>Costo</label>
          <input type="number" name="costo" id="costo" step="0.01" value="<?= htmlspecialchars($variante['costo']) ?>">
        </div>
      </div>
    </section>

    <section>
      <h3>💵 Precio de venta</h3>
      <div class="grid">
        <div>
          <label>Precio unitario</label>
          <input type="number" name="precio_unitario" id="precio_unitario" step="0.01" value="<?= htmlspecialchars($variante['precio_unitario']) ?>">
        </div>
        <div>
          <label>Margen (%)</label>
          <input type="text" id="margen" readonly>
        </div>
        <div>
          <label>Ganancia ($)</label>
          <input type="text" id="ganancia" readonly>
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
// 💵 Cálculo automático de margen y ganancia
function actualizarMargenGanancia() {
  const costo = parseFloat(document.getElementById('costo').value) || 0;
  const precio = parseFloat(document.getElementById('precio_unitario').value) || 0;
  const margenInput = document.getElementById('margen');
  const gananciaInput = document.getElementById('ganancia');

  if (costo > 0 && precio > 0) {
    const ganancia = precio - costo;
    const margen = (ganancia / costo) * 100;
    margenInput.value = margen.toFixed(2) + '%';
    gananciaInput.value = '$' + ganancia.toFixed(2);
  } else {
    margenInput.value = '';
    gananciaInput.value = '';
  }
}

document.getElementById('costo').addEventListener('input', actualizarMargenGanancia);
document.getElementById('precio_unitario').addEventListener('input', actualizarMargenGanancia);
window.addEventListener('load', actualizarMargenGanancia);
</script>

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
