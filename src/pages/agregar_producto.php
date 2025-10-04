<?php
require_once __DIR__ . "/../config/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nombre = $_POST['nombre'];
        $codigo_barras = $_POST['codigo_barras'];
        $categoria_id = $_POST['categoria_id'] ?? null;
        $marca = $_POST['marca'] ?? null;
        $descripcion = $_POST['descripcion'] ?? null;
        $cantidad = $_POST['cantidad'] ?? 0;
        $cantidad_min = $_POST['cantidad_min'] ?? 0;
        $costo = $_POST['costo'] ?? 0;
        $tipo_costo = $_POST['tipo_costo'] ?? 'bruto';
        $precio_venta = $_POST['precio_venta'] ?? 0;

        // Imagen
        $imagen = null;
        if (!empty($_FILES['imagen']['name'])) {
            $nombreArchivo = uniqid() . "_" . basename($_FILES['imagen']['name']);
            $rutaDestino = __DIR__ . "/../uploads/" . $nombreArchivo;
            move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino);
            $imagen = $nombreArchivo;
        }

        $stmt = $pdo->prepare("INSERT INTO productos 
            (nombre, codigo_barras, categoria_id, marca, descripcion, cantidad, cantidad_min, costo, tipo_costo, precio_venta, imagen)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $codigo_barras, $categoria_id, $marca, $descripcion, $cantidad, $cantidad_min, $costo, $tipo_costo, $precio_venta, $imagen]);

        $producto_id = $pdo->lastInsertId();

        if (!empty($_POST['variantes'])) {
            $stmtVar = $pdo->prepare("INSERT INTO producto_variantes 
                (producto_id, talla, color, cantidad, costo, precio_unitario)
                VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($_POST['variantes'] as $v) {
                $stmtVar->execute([
                    $producto_id,
                    $v['talla'],
                    $v['color'],
                    $v['cantidad'],
                    $v['costo'],
                    $v['precio_unitario']
                ]);
            }
        }

        echo "<script>alert('✅ Producto agregado correctamente'); window.location='index.php?view=productos';</script>";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>

<div class="producto-form">
  <h2>Agregar nuevo producto</h2>

  <form method="post" enctype="multipart/form-data">

    <!-- 🧾 DATOS GENERALES -->
    <section>
      <h3>🧾 Datos generales</h3>
      <div class="grid">
        <div>
          <label>Nombre del producto</label>
          <input type="text" name="nombre" required>
        </div>
        <div>
          <label>Código de barras</label>
          <input type="text" name="codigo_barras" required>
        </div>
        <div>
          <label>Imagen</label>
          <input type="file" name="imagen" accept="image/*">
        </div>
      </div>
    </section>

    <!-- 🧩 VARIANTES -->
    <section>
      <h3>🧩 Variantes</h3>
      <p class="info">Si el producto tiene variantes (por ejemplo talla o color), agrégalas aquí. Los datos de inventario y precios se desactivarán automáticamente.</p>

      <div id="variantes-container" class="variantes"></div>
      <button type="button" id="add-variant" class="btn-secundario">+ Agregar variante</button>

      <div id="msg-variantes" class="msg-variantes hidden">
        ⚠️ Este producto tiene variantes. Los campos de inventario y precios generales están deshabilitados.
      </div>
    </section>

    <!-- ⚙️ DATOS ADICIONALES -->
    <section>
      <h3>⚙️ Datos adicionales</h3>
      <div class="grid">
        <div>
          <label>Categoría</label>
          <select name="categoria_id">
            <option value="">Seleccione una categoría</option>
          </select>
        </div>
        <div>
          <label>Marca</label>
          <input type="text" name="marca">
        </div>
        <div class="full">
          <label>Descripción</label>
          <textarea name="descripcion" rows="3"></textarea>
        </div>
      </div>
    </section>

    <!-- 📦 EXISTENCIAS -->
    <section id="inventario-section">
      <h3>📦 Inventario</h3>
      <div class="grid">
        <div>
          <label>Cantidad</label>
          <input type="number" name="cantidad" min="0" value="0">
        </div>
        <div>
          <label>Cantidad mínima</label>
          <input type="number" name="cantidad_min" min="0" value="0">
        </div>
      </div>
    </section>

    <!-- 💰 COSTO -->
    <section id="compra-section">
      <h3>💰 Costo</h3>
      <div class="grid">
        <div>
          <label>Costo</label>
          <input type="number" name="costo" step="0.01" value="0">
        </div>
        <div>
          <label>Tipo de costo</label>
          <select name="tipo_costo">
            <option value="bruto">Bruto</option>
            <option value="neto">Neto</option>
          </select>
        </div>
      </div>
    </section>

    <!-- 💸 PRECIO VENTA -->
    <section id="venta-section">
      <h3>💸 Precio de venta</h3>
      <div class="grid">
        <div>
          <label>Precio de venta</label>
          <input type="number" name="precio_venta" step="0.01" value="0">
        </div>
      </div>
    </section>

    <!-- BOTONES -->
    <div class="botones">
      <button type="submit" class="btn-principal">💾 Guardar producto</button>
      <a href="index.php?view=productos" class="btn-cancelar">Cancelar</a>
    </div>
  </form>
</div>

<script>
let idx = 0;
const cont = document.getElementById('variantes-container');
const inventario = document.getElementById('inventario-section');
const compra = document.getElementById('compra-section');
const venta = document.getElementById('venta-section');
const msg = document.getElementById('msg-variantes');

function toggleSecciones() {
  const hayVariantes = cont.children.length > 0;
  [inventario, compra, venta].forEach(sec => {
    sec.querySelectorAll('input, select').forEach(el => el.disabled = hayVariantes);
    sec.classList.toggle('disabled', hayVariantes);
  });
  msg.classList.toggle('hidden', !hayVariantes);
}

document.getElementById('add-variant').addEventListener('click', () => {
  const html = `
    <div class="var">
      <input name="variantes[${idx}][talla]" placeholder="Talla">
      <input name="variantes[${idx}][color]" placeholder="Color">
      <input name="variantes[${idx}][cantidad]" type="number" placeholder="Cantidad" min="0">
      <input name="variantes[${idx}][costo]" type="number" step="0.01" placeholder="Costo">
      <input name="variantes[${idx}][precio_unitario]" type="number" step="0.01" placeholder="Precio unitario">
      <button type="button" class="remove">🗑️</button>
    </div>`;
  cont.insertAdjacentHTML('beforeend', html);
  idx++;
  cont.querySelectorAll('.remove').forEach(btn => {
    btn.onclick = e => { e.target.closest('.var').remove(); toggleSecciones(); };
  });
  toggleSecciones();
});
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
  font-family: 'Inter', sans-serif;
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
</style>
