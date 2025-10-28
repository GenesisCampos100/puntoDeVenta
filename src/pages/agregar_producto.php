<?php
require_once __DIR__ . "/../config/db.php";

// 🧩 Cargar categorías
try {
    $stmt = $pdo->query("SELECT id_categoria, nombre FROM categorias ORDER BY nombre ASC");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error al cargar categorías: " . $e->getMessage());
}

// 🧾 Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 🧠 Datos base del producto
        $nombre = trim($_POST['nombre'] ?? '');
        $cod_barras = trim($_POST['cod_barras'] ?? '');
        $id_categoria = $_POST['id_categoria'] ?? null;
        $marca = trim($_POST['marca'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $cantidad = (int)($_POST['cantidad'] ?? 0);
        $cantidad_min = (int)($_POST['cantidad_min'] ?? 0);
        $costo = (float)($_POST['costo'] ?? 0);
        $precio_unitario = (float)($_POST['precio_unitario'] ?? 0);
        $variantes = $_POST['variantes'] ?? [];

        // 🧩 Validaciones básicas
        if ($nombre === '') throw new Exception("El nombre del producto es obligatorio.");
        if ($id_categoria === '' || $id_categoria === null) throw new Exception("Debe seleccionar una categoría.");
        if ($costo <= 0) throw new Exception("El costo debe ser mayor que 0.");
        if ($precio_unitario <= 0) throw new Exception("El precio unitario debe ser mayor que 0.");
        if ($cantidad < 0) throw new Exception("La cantidad no puede ser negativa.");
        if ($cantidad_min < 0) throw new Exception("La cantidad mínima no puede ser negativa.");

        // 🚫 Verificar código de barras duplicado
        if ($cod_barras !== '') {
            $check = $pdo->prepare("SELECT cod_barras FROM productos WHERE cod_barras = ?");
            $check->execute([$cod_barras]);
            if ($check->fetch()) throw new Exception("Ya existe un producto con ese código de barras.");
        }

        // 🖼️ Imagen principal
        $imagen = null;
        if (!empty($_FILES['imagen']['name'])) {
            $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                throw new Exception("Formato de imagen no válido (solo JPG, PNG o WEBP).");
            }

            $carpeta = __DIR__ . "/../uploads/";
            if (!is_dir($carpeta)) mkdir($carpeta, 0777, true);
            $nombreArchivo = uniqid("prod_") . "." . $ext;
            $ruta = $carpeta . $nombreArchivo;
            if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta)) {
                throw new Exception("Error al guardar la imagen.");
            }
            $imagen = $nombreArchivo;
        }

        // 💾 Insertar producto base
        $stmt = $pdo->prepare("INSERT INTO productos 
            (cod_barras, nom_producto, descripcion, marca, imagen, talla, cantidad, cantidad_min, costo, precio, id_categoria)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        // Si no hay variantes, se guarda como "Unitalla" por defecto
        $talla_base = empty($variantes) ? 'Unitalla' : null;

        $stmt->execute([
            $cod_barras ?: null,
            $nombre,
            $descripcion,
            $marca,
            $imagen,
            $talla_base,
            $cantidad,
            $cantidad_min,
            $costo,
            $precio_unitario,
            $id_categoria
        ]);

        // 🧮 Insertar variantes (si existen)
        if (!empty($variantes)) {
            $stmtVar = $pdo->prepare("INSERT INTO variantes 
                (cod_barras, talla, color, imagen, cantidad, cantidad_min, costo, precio, cod_barras_producto)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

            foreach ($variantes as $i => $v) {
                $codVar = trim($v['cod_barras'] ?? '') ?: "VAR-" . uniqid();
                $talla = trim($v['talla'] ?? '');
                $color = trim($v['color'] ?? '');
                $cantidadVar = (int)($v['cantidad'] ?? 0);
                $cantidadMinVar = (int)($v['cantidad_min'] ?? 0);
                $costoVar = (float)($v['costo'] ?? 0);
                $precioVar = (float)($v['precio_unitario'] ?? 0);

                // Imagen de variante
                $imgVar = null;
                if (!empty($_FILES['variantes']['name'][$i]['imagen'])) {
                    $extVar = strtolower(pathinfo($_FILES['variantes']['name'][$i]['imagen'], PATHINFO_EXTENSION));
                    if (in_array($extVar, ['jpg', 'jpeg', 'png', 'webp'])) {
                        $nombreArchivo = uniqid("var_") . "." . $extVar;
                        $rutaVar = $carpeta . $nombreArchivo;
                        $tmp = $_FILES['variantes']['tmp_name'][$i]['imagen'];
                        if (is_uploaded_file($tmp) && move_uploaded_file($tmp, $rutaVar)) {
                            $imgVar = $nombreArchivo;
                        }
                    }
                }

                $stmtVar->execute([
                    $codVar,
                    $talla ?: null,
                    $color ?: null,
                    $imgVar,
                    $cantidadVar,
                    $cantidadMinVar,
                    $costoVar,
                    $precioVar,
                    $cod_barras
                ]);
            }
        } else {
            // Si no hay variantes, crear una por defecto
            $stmtDef = $pdo->prepare("INSERT INTO variantes 
                (cod_barras, talla, color, cantidad, cantidad_min, costo, precio, cod_barras_producto)
                VALUES (?, 'Unitalla', NULL, ?, ?, ?, ?, ?)");
            $stmtDef->execute([
                $cod_barras ?: "AUTO-" . uniqid(),
                $cantidad,
                $cantidad_min,
                $costo,
                $precio_unitario,
                $cod_barras
            ]);
        }

        echo "<script>alert('✅ Producto agregado correctamente'); window.location='index.php?view=productos';</script>";
        exit;
    } catch (Exception $e) {
        echo "<script>alert('❌ Error: " . addslashes($e->getMessage()) . "');</script>";
    }
}
?>

<!-- 🧾 FORMULARIO -->
<div class="producto-form">
  <h2>Agregar nuevo producto</h2>

  <form method="post" enctype="multipart/form-data">

    <section>
      <h3>Datos generales</h3>
      <div class="grid">
        <div>
          <label>Nombre *</label>
          <input type="text" name="nombre" required>
        </div>
        <div>
          <label>Código de barras *</label>
          <input type="text" name="cod_barras" required>
        </div>
        <div>
          <label>Imagen principal</label>
          <input type="file" name="imagen" accept="image/png, image/jpeg, image/webp">
        </div>
      </div>
    </section>

    <section>
      <h3>Datos adicionales</h3>
      <div class="grid">
        <div>
          <label>Categoría *</label>
          <select name="id_categoria" required>
            <option value="">Seleccione</option>
            <?php foreach ($categorias as $cat): ?>
              <option value="<?= htmlspecialchars($cat['id_categoria']) ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
            <?php endforeach; ?>
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

    <section>
      <h3>Inventario y precios</h3>
      <div class="grid">
        <div>
          <label>Cantidad *</label>
          <input type="number" name="cantidad" min="0" value="0" required>
        </div>
        <div>
          <label>Cantidad mínima *</label>
          <input type="number" name="cantidad_min" min="0" value="0" required>
        </div>
        <div>
          <label>Costo *</label>
          <input type="number" name="costo" step="0.01" min="0.01" required>
        </div>
        <div>
          <label>Precio unitario *</label>
          <input type="number" name="precio_unitario" step="0.01" min="0.01" required>
        </div>
      </div>
    </section>

    <section>
      <h3>Variantes (opcional)</h3>
      <p>Agregue colores o tallas específicas solo si aplica.</p>
      <div id="variantes-container"></div>
      <button type="button" id="add-variant" class="btn-secundario">+ Agregar variante</button>
    </section>

    <div class="botones">
      <button type="submit" class="btn-principal">💾 Guardar</button>
      <a href="index.php?view=productos" class="btn-cancelar">Cancelar</a>
    </div>
  </form>
</div>


<script>
let idx = 0;
const cont = document.getElementById('variantes-container');
const msgVar = document.getElementById('msg-variantes');
const addBtn = document.getElementById('add-variant');

// 🧩 Campos que deben bloquearse si hay variantes
const camposBloquear = [
  document.querySelector('input[name="color_base"]'),
  document.querySelector('input[name="cantidad"]'),
  document.querySelector('input[name="cantidad_min"]'),
  document.getElementById('costo'),
  document.getElementById('tipo_costo'),
  document.getElementById('precio_unitario'),
  document.getElementById('margen'),
  document.getElementById('ganancia')
];

// 🔒 Función para bloquear/desbloquear campos
function actualizarBloqueoCampos() {
  const hayVariantes = cont.children.length > 0;
  camposBloquear.forEach(campo => {
    campo.disabled = hayVariantes;
  });
  msgVar.classList.toggle('hidden', !hayVariantes);
}

// ➕ Agregar variante
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
  cont.querySelectorAll('.remove').forEach(btn => btn.onclick = e => {
    e.target.closest('.var').remove();
    actualizarBloqueoCampos();
  });
  idx++;
  actualizarBloqueoCampos();
});


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

h1 {
  text-align: center;
  margin-bottom: 80px;
  color: var(--texto);
  font-weight: 800;
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