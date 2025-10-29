<?php
require_once __DIR__ . "/../config/db.php";

// 🧩 Cargar categorías
try {
    $stmt = $pdo->query("SELECT id_categoria, nombre FROM categorias ORDER BY nombre ASC");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Aquí deberías manejar el error de forma más elegante
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
        $color_base = trim($_POST['color'] ?? null); // Campo de color base
        
        // Si hay variantes, la información numérica de stock/precio del producto principal se ignora o se establece en 0
        $hayVariantes = !empty($_POST['variantes']);
        
        $cantidad = $hayVariantes ? 0 : (int)($_POST['cantidad'] ?? 0);
        $cantidad_min = $hayVariantes ? 0 : (int)($_POST['cantidad_min'] ?? 0);
        $costo = $hayVariantes ? 0 : (float)($_POST['costo'] ?? 0);
        $precio_unitario = $hayVariantes ? 0 : (float)($_POST['precio_unitario'] ?? 0);
        $variantes = $_POST['variantes'] ?? [];

        // 🧩 Validaciones básicas (se relajan si hay variantes)
        if ($nombre === '') throw new Exception("El nombre del producto es obligatorio.");
        if ($id_categoria === '' || $id_categoria === null) throw new Exception("Debe seleccionar una categoría.");
        
        if (!$hayVariantes) {
            if ($costo <= 0) throw new Exception("El costo debe ser mayor que 0.");
            if ($precio_unitario <= 0) throw new Exception("El precio unitario debe ser mayor que 0.");
        }
        
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
            (cod_barras, nom_producto, descripcion, marca, imagen, color, cantidad, cantidad_min, costo, precio, id_categoria)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        // Si no hay variantes, se usa la talla/color base. Si hay, se dejan en NULL o el valor 0 de la base.
        $color_prod = empty($variantes) ? $color_base : null;

        $stmt->execute([
            $cod_barras ?: null,
            $nombre,
            $descripcion,
            $marca,
            $imagen,
            $color_prod, // Usamos el color base solo si no hay variantes
            $cantidad,
            $cantidad_min,
            $costo,
            $precio_unitario,
            $id_categoria
        ]);

        // 🧮 Insertar variantes (si existen)
        if (!empty($variantes)) {
            $stmtVar = $pdo->prepare("INSERT INTO variantes 
                (sku, talla, color, imagen, cantidad, cantidad_min, costo, precio, cod_barras)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $lastCodBarras = $cod_barras ?: $pdo->lastInsertId(); // Usamos el ID de inserción o el cod_barras manual
            if (!$lastCodBarras) throw new Exception("No se pudo obtener el ID del producto base.");
            
            foreach ($variantes as $i => $v) {
                // Usamos el campo sku de la variante para el código de barras si no está vacío, sino un ID único
                $sku = trim($v['sku'] ?? '') ?: "VAR-" . uniqid(); 
                $talla = trim($v['talla'] ?? '');
                $color = trim($v['color'] ?? '');
                $cantidadVar = (int)($v['cantidad'] ?? 0);
                $cantidadMinVar = (int)($v['cantidad_min'] ?? 0);
                $costoVar = (float)($v['costo'] ?? 0);
                $precioVar = (float)($v['precio'] ?? 0); // Corregido: el input se llama 'precio'
                
                if ($costoVar <= 0 || $precioVar <= 0) throw new Exception("Costo y Precio son obligatorios para la variante " . $sku);

                // Imagen de variante (manejo especial de $_FILES para arrays)
                $imgVar = null;
                if (!empty($_FILES['variantes']['name'][$i]['imagen'])) {
                    $extVar = strtolower(pathinfo($_FILES['variantes']['name'][$i]['imagen'], PATHINFO_EXTENSION));
                    if (in_array($extVar, ['jpg', 'jpeg', 'png', 'webp'])) {
                        $nombreArchivo = uniqid("var_") . "." . $extVar;
                        $rutaVar = $carpeta . $nombreArchivo;
                        // Aquí el manejo del array de archivos subidos es complejo, se asume que tu entorno lo está aplanando correctamente
                        $tmp = $_FILES['variantes']['tmp_name'][$i]['imagen'] ?? null;
                        if ($tmp && is_uploaded_file($tmp) && move_uploaded_file($tmp, $rutaVar)) {
                            $imgVar = $nombreArchivo;
                        }
                    }
                }

                $stmtVar->execute([
                    $sku, // SKU o cod_barras de la variante
                    $talla ?: null,
                    $color ?: null,
                    $imgVar,
                    $cantidadVar,
                    $cantidadMinVar,
                    $costoVar,
                    $precioVar,
                    $cod_barras // FK al producto principal
                ]);
            }
        } else {
            // Si no hay variantes, crear una por defecto
            $lastCodBarras = $cod_barras ?: $pdo->lastInsertId();
            if (!$lastCodBarras) throw new Exception("No se pudo obtener el ID del producto base para la variante.");

            $stmtDef = $pdo->prepare("INSERT INTO variantes 
                (sku, talla, color, cantidad, cantidad_min, costo, precio, cod_barras)
                VALUES (?, 'Unitalla', ?, ?, ?, ?, ?, ?)");
            $stmtDef->execute([
                $cod_barras ?: "AUTO-" . uniqid(),
                $color_base,
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
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Producto</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Estilos base para compatibilidad con la vista de productos */
        body { 
            font-family: 'Poppins', sans-serif; 
            background-color: #2d4353; /* AZUL OSCURO de la paleta para el fondo principal */
            padding-left: 250px; /* Espacio para el sidebar fijo */
            padding-top: 20px;
        }
        /* Estilo para inputs de archivo (file) que no tienen el mismo padding visual */
        input[type="file"] {
            padding-top: 5px; /* Ajuste visual */
        }
        /* Estilo para los campos bloqueados */
        input:disabled, select:disabled, textarea:disabled {
            background-color: #e5e7eb !important;
            color: #9ca3af !important;
            cursor: not-allowed;
            opacity: 0.8;
        }
    </style>
</head>
<body class="bg-[#2d4353]">

<div class="w-full max-w-4xl mx-auto bg-white p-8 md:p-10 rounded-xl shadow-2xl text-[#2d4353] mt-5 mb-10">
    <h2 class="text-3xl font-bold text-[#2d4353] mb-8 text-center">Agregar nuevo producto</h2>

    <form method="post" enctype="multipart/form-data">

        <section class="mb-6 pb-5 border-b border-gray-200">
            <h3 class="text-xl font-semibold mb-4 text-[#2d4353]/80">Datos generales</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-6">
                <div>
                    <label class="block font-semibold mb-1 text-gray-700">Nombre *</label>
                    <input type="text" name="nombre" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#b4c24d] focus:border-[#b4c24d] transition duration-200">
                </div>
                <div>
                    <label class="block font-semibold mb-1 text-gray-700">Código de barras</label>
                    <input type="text" name="cod_barras"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#b4c24d] focus:border-[#b4c24d] transition duration-200">
                </div>
                <div>
                    <label class="block font-semibold mb-1 text-gray-700">Imagen principal</label>
                    <input type="file" name="imagen" accept="image/png, image/jpeg, image/webp"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#b4c24d] focus:border-[#b4c24d] transition duration-200">
                </div>
            </div>
        </section>

        <section class="mb-6 pb-5 border-b border-gray-200">
            <h3 class="text-xl font-semibold mb-4 text-[#2d4353]/80">Datos adicionales</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 lg:gap-6">
                <div>
                    <label class="block font-semibold mb-1 text-gray-700">Categoría *</label>
                    <select name="id_categoria" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#b4c24d] focus:border-[#b4c24d] transition duration-200">
                        <option value="">Seleccione</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['id_categoria']) ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block font-semibold mb-1 text-gray-700">Marca</label>
                    <input type="text" name="marca"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#b4c24d] focus:border-[#b4c24d] transition duration-200">
                </div>
                <div class="col-span-1 md:col-span-2">
                    <label class="block font-semibold mb-1 text-gray-700">Descripción</label>
                    <textarea name="descripcion" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#b4c24d] focus:border-[#b4c24d] transition duration-200"></textarea>
                </div>
            </div>
        </section>

        <section class="mb-6 pb-5 border-b border-gray-200">
            <h3 class="text-xl font-semibold mb-4 text-[#2d4353]/80">Inventario y precios base</h3>
            
            <div id="msg-variantes" class="hidden mt-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm flex items-center gap-2">
                <span class="font-bold">⚠️ Atención:</span> Los campos de Stock y Precio del producto base han sido **deshabilitados** porque ha añadido variantes. La información de inventario se gestionará por variante.
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mt-4">
                
                <div>
                    <label class="block font-semibold mb-1 text-gray-700">Color Base</label>
                    <input type="text" name="color" placeholder="Ej: Único, Negro, N/A"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#b4c24d] focus:border-[#b4c24d] transition duration-200">
                </div>
                
                <div>
                    <label class="block font-semibold mb-1 text-gray-700">Cantidad *</label>
                    <input type="number" name="cantidad" min="0" value="0" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#b4c24d] focus:border-[#b4c24d] transition duration-200">
                </div>
                <div>
                    <label class="block font-semibold mb-1 text-gray-700">Cantidad mínima *</label>
                    <input type="number" name="cantidad_min" min="0" value="0" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#b4c24d] focus:border-[#b4c24d] transition duration-200">
                </div>
                
                <div>
                    <label class="block font-semibold mb-1 text-gray-700">Costo *</label>
                    <input type="number" name="costo" id="costo" step="0.01" min="0.01" placeholder="0.00" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#b4c24d] focus:border-[#b4c24d] transition duration-200">
                </div>
                <div>
                    <label class="block font-semibold mb-1 text-gray-700">Precio unitario *</label>
                    <input type="number" name="precio_unitario" id="precio_unitario" step="0.01" min="0.01" placeholder="0.00" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#b4c24d] focus:border-[#b4c24d] transition duration-200">
                </div>
                
                <div>
                    <label class="block font-semibold mb-1 text-gray-700">Margen (%)</label>
                    <input type="text" id="margen" disabled value="" placeholder="Calculando..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-gray-600 disabled:bg-gray-100 disabled:cursor-not-allowed">
                </div>
                <div>
                    <label class="block font-semibold mb-1 text-gray-700">Ganancia</label>
                    <input type="text" id="ganancia" disabled value="" placeholder="Calculando..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-gray-600 disabled:bg-gray-100 disabled:cursor-not-allowed">
                </div>
                <input type="hidden" id="tipo_costo" value="manual">
            </div>
        </section>

        <section class="mb-6 pb-5">
            <h3 class="text-xl font-semibold mb-4 text-[#2d4353]/80">Variantes (opcional)</h3>
            <p class="text-gray-600 text-sm mb-4">Agregue colores, tallas o modelos específicos. Si agrega variantes, los campos de Stock y Precio Base **se deshabilitarán** y la información de inventario se tomará de aquí.</p>
            
            <div id="variantes-container" class="flex flex-col gap-3">
                </div>
            
            <button type="button" id="add-variant" class="bg-[#e15871] text-white hover:bg-[#c64a61] transition px-4 py-2 rounded-lg text-sm font-semibold mt-4">+ Agregar variante</button>
        </section>

        <div class="mt-8 flex justify-end gap-4">
            <a href="index.php?view=productos" class="bg-gray-400 text-white hover:bg-gray-500 transition px-6 py-3 rounded-xl font-bold text-center">Cancelar</a>
            <button type="submit" class="bg-[#b4c24d] text-[#2d4353] hover:bg-[#9aa841] transition px-6 py-3 rounded-xl font-bold">💾 Guardar producto</button>
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
  document.querySelector('input[name="color"]'), // El campo de color base
  document.querySelector('input[name="cantidad"]'),
  document.querySelector('input[name="cantidad_min"]'),
  document.getElementById('costo'),
  document.getElementById('precio_unitario'),
  document.getElementById('margen'),
  document.getElementById('ganancia')
];

// 🔒 Función para bloquear/desbloquear campos
function actualizarBloqueoCampos() {
    const hayVariantes = cont.children.length > 0;
    camposBloquear.forEach(campo => {
        if (campo) { // Comprobación de existencia
            campo.disabled = hayVariantes;
            if (hayVariantes) {
                // Limpiar valores del producto principal si se agregan variantes
                if (campo.name === 'cantidad' || campo.name === 'cantidad_min' || campo.name === 'costo' || campo.name === 'precio_unitario') {
                    campo.value = 0;
                } else if (campo.id === 'margen' || campo.id === 'ganancia') {
                    campo.value = 'Deshabilitado';
                } else if (campo.name === 'color') {
                    campo.value = '';
                }
            } else {
                // Restaurar el cálculo si no hay variantes
                actualizarMargenGanancia();
            }
        }
    });
    msgVar.classList.toggle('hidden', !hayVariantes);
}

// ➕ Agregar variante
addBtn.addEventListener('click', () => {
    // Hemos corregido los nombres de los inputs de variante para usar 'sku' y 'precio' como en el PHP
  const html = `
  <div class="var flex flex-wrap items-center gap-3 bg-[#f7f7f7] p-3 rounded-lg border border-[#eeeeee]">
    <input name="variantes[${idx}][sku]" placeholder="SKU/Cod. Barras" 
        class="flex-1 min-w-[100px] text-sm px-3 py-1 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#b4c24d]">
    <input name="variantes[${idx}][talla]" placeholder="Talla" required
        class="flex-1 min-w-[100px] text-sm px-3 py-1 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#b4c24d]">
    <input name="variantes[${idx}][color]" placeholder="Color" required
        class="flex-1 min-w-[100px] text-sm px-3 py-1 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#b4c24d]">
    <input name="variantes[${idx}][cantidad]" type="number" min="0" placeholder="Stock" value="0" required
        class="flex-1 min-w-[100px] text-sm px-3 py-1 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#b4c24d]">
    <input name="variantes[${idx}][cantidad_min]" type="number" min="0" placeholder="Mínimo" value="0" required
        class="flex-1 min-w-[100px] text-sm px-3 py-1 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#b4c24d]">
    <input name="variantes[${idx}][costo]" type="number" step="0.01" min="0.01" placeholder="Costo" required
        class="flex-1 min-w-[100px] text-sm px-3 py-1 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#b4c24d]">
    <input name="variantes[${idx}][precio]" type="number" step="0.01" min="0.01" placeholder="Precio" required
        class="flex-1 min-w-[100px] text-sm px-3 py-1 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#b4c24d]">
    <input type="file" name="variantes[${idx}][imagen]" accept="image/*"
        class="flex-1 min-w-[100px] text-xs px-3 py-1">
    <button type="button" class="remove bg-[#e15871] text-white hover:bg-[#c64a61] p-2 rounded-lg transition text-sm">🗑️</button>
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
  const costoInput = document.getElementById('costo');
  const precioInput = document.getElementById('precio_unitario');
  const margenInput = document.getElementById('margen');
  const gananciaInput = document.getElementById('ganancia');
    
    // Si los campos están deshabilitados (porque hay variantes), salir
    if (costoInput.disabled) return;
    
  const costo = parseFloat(costoInput.value) || 0;
  const precio = parseFloat(precioInput.value) || 0;


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

// Escuchar cambios para el cálculo
document.getElementById('costo').addEventListener('input', actualizarMargenGanancia);
document.getElementById('precio_unitario').addEventListener('input', actualizarMargenGanancia);

// Inicializar el estado de bloqueo al cargar la página
document.addEventListener('DOMContentLoaded', actualizarMargenGanancia); // Muestra cálculo si no hay variantes
</script>

</body>
</html>