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
        $cod_barras = trim($_POST['cod_barras'] ?? ''); // Codigo de barras del PRODUCTO PRINCIPAL
        $id_categoria = $_POST['id_categoria'] ?? null;
        $marca = trim($_POST['marca'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        
        // El color y talla base del producto principal solo se usarán si NO hay variantes.
        $talla_base = trim($_POST['talla_base'] ?? null);
        $color_base = trim($_POST['color_base'] ?? null); 
        
        // Si hay variantes, la información numérica de stock/precio del producto principal se ignora.
        $variantes = $_POST['variantes'] ?? [];
        $hayVariantes = !empty($variantes);
        
        $cantidad = $hayVariantes ? 0 : (int)($_POST['cantidad'] ?? 0);
        $cantidad_min = $hayVariantes ? 0 : (int)($_POST['cantidad_min'] ?? 0);
        $costo = $hayVariantes ? 0 : (float)($_POST['costo'] ?? 0);
        $precio_unitario = $hayVariantes ? 0 : (float)($_POST['precio_unitario'] ?? 0);

        // 🧩 Validaciones básicas
        if ($nombre === '') throw new Exception("El nombre del producto es obligatorio.");
        if ($id_categoria === '' || $id_categoria === null) throw new Exception("Debe seleccionar una categoría.");
        
        if (!$hayVariantes) {
            // Validaciones si es un producto simple (sin variantes)
            if ($costo <= 0) throw new Exception("El Costo debe ser mayor que 0.");
            if ($precio_unitario <= 0) throw new Exception("El Precio Unitario debe ser mayor que 0.");
        }
        
        // 🚫 Verificar código de barras duplicado para el producto principal (solo si se proporciona)
        if ($cod_barras !== '') {
            $check = $pdo->prepare("SELECT cod_barras FROM productos WHERE cod_barras = ?");
            $check->execute([$cod_barras]);
            if ($check->fetch()) throw new Exception("Ya existe un producto con ese Código de Barras principal.");
        }

        // 🖼️ Imagen principal
        $imagen = null;
        if (!empty($_FILES['imagen']['name'])) {
            // ... (Lógica de subida de imagen principal)
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
        // Se usan valores de talla/color base solo si NO hay variantes.
        $talla_prod = $hayVariantes ? null : $talla_base;
        $color_prod = $hayVariantes ? null : $color_base;

        $stmt = $pdo->prepare("INSERT INTO productos 
            (cod_barras, nom_producto, descripcion, marca, imagen, talla, color, cantidad, cantidad_min, costo, precio, id_categoria)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $cod_barras ?: null,
            $nombre,
            $descripcion,
            $marca,
            $imagen,
            $talla_prod, // NULL si hay variantes
            $color_prod, // NULL si hay variantes
            $cantidad,
            $cantidad_min,
            $costo,
            $precio_unitario,
            $id_categoria
        ]);

        $producto_cod_barras = $cod_barras ?: $pdo->lastInsertId();
        if (!$producto_cod_barras) throw new Exception("No se pudo obtener el ID del producto base.");
        
        // 🧮 Insertar variantes (si existen)
        if (!empty($variantes)) {
            $stmtVar = $pdo->prepare("INSERT INTO variantes 
                (sku, talla, color, imagen, cantidad, cantidad_min, costo, precio, cod_barras)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

            // Recorrer las variantes enviadas
            foreach ($variantes as $i => $v) {
                $sku = trim($v['sku'] ?? '');
                $talla = trim($v['talla'] ?? '');
                $color = trim($v['color'] ?? '');
                $cantidadVar = (int)($v['cantidad'] ?? 0);
                $cantidadMinVar = (int)($v['cantidad_min'] ?? 0);
                $costoVar = (float)($v['costo'] ?? 0);
                $precioVar = (float)($v['precio'] ?? 0); 

                // VALIDACIÓN CRUCIAL: Asegurar que la variante sea identificable.
                if ($sku === '' && $talla === '' && $color === '') {
                    throw new Exception("Cada variante debe tener al menos un valor de identificación (SKU, Talla o Color).");
                }
                
                if ($costoVar <= 0 || $precioVar <= 0) {
                     // Identificador para el mensaje de error
                    $identificador = $sku ?: ($talla . ($talla && $color ? ' / ' : '') . $color) ?: "sin identificación completa";
                    throw new Exception("Costo y Precio son obligatorios y deben ser mayores que 0 para la variante: " . $identificador);
                }

                // Manejo de imagen de variante
                $imgVar = null;
                if (!empty($_FILES['variantes']['name'][$i]['imagen'])) {
                    // La lógica de manejo de archivos subidos como array de arrays es compleja y depende del PHP.
                    // Se asume que $v['imagen'] no está vacío si el archivo existe.
                    $extVar = strtolower(pathinfo($_FILES['variantes']['name'][$i]['imagen'], PATHINFO_EXTENSION));
                    if (in_array($extVar, ['jpg', 'jpeg', 'png', 'webp'])) {
                        $nombreArchivo = uniqid("var_") . "." . $extVar;
                        $rutaVar = $carpeta . $nombreArchivo;
                        
                        // Necesitas el archivo temporal correcto
                        $tmp = $_FILES['variantes']['tmp_name'][$i]['imagen'] ?? null;
                        
                        // Si el archivo temporal existe y es un archivo subido válido, moverlo
                        if ($tmp && is_uploaded_file($tmp) && move_uploaded_file($tmp, $rutaVar)) {
                            $imgVar = $nombreArchivo;
                        }
                    }
                }

                $stmtVar->execute([
                    $sku ?: null, // Se permite NULL si solo se usa talla/color
                    $talla ?: null,
                    $color ?: null,
                    $imgVar,
                    $cantidadVar,
                    $cantidadMinVar,
                    $costoVar,
                    $precioVar,
                    $producto_cod_barras // FK al producto principal
                ]);
            }
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
            background-color: #2d4353; 
            padding-left: 250px; 
            padding-top: 20px;
        }
        input[type="file"] {
            padding-top: 5px; 
        }
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
                    <label class="block font-semibold mb-1 text-gray-700">Nombre del Producto *</label>
                    <input type="text" name="nombre" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#b4c24d] focus:border-[#b4c24d] transition duration-200">
                </div>
                <div>
                    <label class="block font-semibold mb-1 text-gray-700">Código de Barras Principal (Producto)</label>
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
            <h3 class="text-xl font-semibold mb-4 text-[#2d4353]/80">Datos Adicionales y Clasificación</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 lg:gap-6">
                <div>
                    <label class="block font-semibold mb-1 text-gray-700">Categoría (id_categoria) *</label>
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
            <h3 class="text-xl font-semibold mb-4 text-[#2d4353]/80">Inventario y Precios Base (Producto simple)</h3>
            
            <div id="msg-variantes" class="hidden mt-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm flex items-center gap-2">
                <span class="font-bold">⚠️ Atención:</span> Los campos de Stock y Precio del producto base han sido **deshabilitados** porque ha añadido variantes. La información de inventario se gestionará por variante.
            </div>

            <p class="text-gray-600 text-sm mb-4">Solo aplican si **NO** usa el bloque de "Variantes".</p>


            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mt-4">
                
                <div>
                    <label class="block font-semibold mb-1 text-gray-700">Talla Base (talla)</label>
                    <input type="text" name="talla_base" placeholder="Ej: Única, M"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#b4c24d] focus:border-[#b4c24d] transition duration-200">
                </div>
                <div>
                    <label class="block font-semibold mb-1 text-gray-700">Color Base (color)</label>
                    <input type="text" name="color_base" placeholder="Ej: Negro, N/A"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#b4c24d] focus:border-[#b4c24d] transition duration-200">
                </div>
                
                <div>
                    <label class="block font-semibold mb-1 text-gray-700">Cantidad (cantidad) *</label>
                    <input type="number" name="cantidad" min="0" value="0" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#b4c24d] focus:border-[#b4c24d] transition duration-200">
                </div>
                <div>
                    <label class="block font-semibold mb-1 text-gray-700">Cantidad mínima (cantidad_min) *</label>
                    <input type="number" name="cantidad_min" min="0" value="0" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#b4c24d] focus:border-[#b4c24d] transition duration-200">
                </div>
                
                <div>
                    <label class="block font-semibold mb-1 text-gray-700">Costo (costo) *</label>
                    <input type="number" name="costo" id="costo" step="0.01" min="0.01" placeholder="0.00" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#b4c24d] focus:border-[#b4c24d] transition duration-200">
                </div>
                <div>
                    <label class="block font-semibold mb-1 text-gray-700">Precio Unitario (precio) *</label>
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
            </div>
        </section>

        <section class="mb-6 pb-5">
    <h3 class="text-xl font-semibold mb-4 text-[#2d4353]/80">Variantes (Múltiples Tallas/Colores)</h3>
    <p class="text-gray-600 text-sm mb-4">Si agrega variantes, **los campos de Stock y Precio Base anteriores se deshabilitarán**. La información se gestiona por variante.</p>
    
    <div id="variantes-container" class="flex flex-col gap-3 mb-4">
        </div>
    
    <h4 class="text-lg font-semibold mb-2 text-gray-700">Añadir variante por:</h4>
    <div class="flex flex-wrap gap-3">
        <button type="button" class="btn-add-variant bg-[#b4c24d] text-white hover:bg-[#9aa841] transition px-4 py-2 rounded-lg text-sm font-semibold" data-type="talla">➕ Solo Talla</button>
        <button type="button" class="btn-add-variant bg-[#b4c24d] text-white hover:bg-[#9aa841] transition px-4 py-2 rounded-lg text-sm font-semibold" data-type="color">➕ Solo Color</button>
        <button type="button" class="btn-add-variant bg-[#b4c24d] text-white hover:bg-[#9aa841] transition px-4 py-2 rounded-lg text-sm font-semibold" data-type="ambas">➕ Talla y Color</button>
        <button type="button" class="btn-add-variant bg-[#e15871] text-white hover:bg-[#c64a61] transition px-4 py-2 rounded-lg text-sm font-semibold" data-type="generica">➕ Genérica (Solo SKU)</button>
    </div>
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
const addBtns = document.querySelectorAll('.btn-add-variant'); // Selecciona los nuevos botones

// 🧩 Campos que deben bloquearse si hay variantes
const camposBloquear = [
  document.querySelector('input[name="talla_base"]'), 
  document.querySelector('input[name="color_base"]'), 
  document.querySelector('input[name="cantidad"]'),
  document.querySelector('input[name="cantidad_min"]'),
  document.getElementById('costo'),
  document.getElementById('precio_unitario'),
  document.getElementById('margen'),
  document.getElementById('ganancia')
];

// 🔒 Función para bloquear/desbloquear campos (Misma lógica)
function actualizarBloqueoCampos() {
    const hayVariantes = cont.children.length > 0;
    camposBloquear.forEach(campo => {
        if (campo) { 
            campo.disabled = hayVariantes;
            if (hayVariantes) {
                if (campo.name === 'cantidad' || campo.name === 'cantidad_min' || campo.name === 'costo' || campo.name === 'precio_unitario') {
                    campo.value = 0;
                } else if (campo.id === 'margen' || campo.id === 'ganancia') {
                    campo.value = 'Deshabilitado';
                } else if (campo.name === 'talla_base' || campo.name === 'color_base') {
                    campo.value = '';
                }
            } else {
                actualizarMargenGanancia();
            }
        }
    });
    msgVar.classList.toggle('hidden', !hayVariantes);
}

// 🔨 Función que construye el HTML de la variante según el tipo
function getVariantHtml(type, index) {
    let nameFields = '';
    let requiredFields = 'SKU, Talla o Color';
    let disabledTalla = '';
    let disabledColor = '';
    
    // Determinar qué campos habilitar y cuáles deshabilitar visualmente
    switch (type) {
        case 'talla':
            disabledColor = 'disabled placeholder="Solo Talla"';
            requiredFields = 'Talla o SKU';
            break;
        case 'color':
            disabledTalla = 'disabled placeholder="Solo Color"';
            requiredFields = 'Color o SKU';
            break;
        case 'ambas':
            requiredFields = 'Talla, Color o SKU';
            break;
        case 'generica':
            disabledTalla = 'disabled placeholder="Solo SKU"';
            disabledColor = 'disabled placeholder="Solo SKU"';
            requiredFields = 'SKU';
            break;
    }

    // El placeholder ayuda al usuario a saber qué campo debe llenar si es opcional
    const tallaPlaceholder = disabledTalla ? 'Solo Talla' : 'Talla (talla)';
    const colorPlaceholder = disabledColor ? 'Solo Color' : 'Color (color)';
    
    // El atributo `required` se mantiene solo en los campos de precio/costo y cantidad
    nameFields = `
        <input name="variantes[${index}][sku]" placeholder="SKU/Cod. Barras (sku)" 
            class="flex-1 min-w-[120px] text-sm px-3 py-1 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#b4c24d]">
        <input name="variantes[${index}][talla]" placeholder="${tallaPlaceholder}" ${disabledTalla}
            class="flex-1 min-w-[80px] text-sm px-3 py-1 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#b4c24d]">
        <input name="variantes[${index}][color]" placeholder="${colorPlaceholder}" ${disabledColor}
            class="flex-1 min-w-[100px] text-sm px-3 py-1 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#b4c24d]">
    `;

  return `
  <div class="var flex flex-wrap items-center gap-3 bg-[#f7f7f7] p-3 rounded-lg border border-[#eeeeee]" data-type="${type}">
    ${nameFields}
    <input name="variantes[${index}][cantidad]" type="number" min="0" placeholder="Stock (cant)" value="0" required
        class="flex-1 min-w-[80px] text-sm px-3 py-1 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#b4c24d]">
    <input name="variantes[${index}][cantidad_min]" type="number" min="0" placeholder="Mínimo (cant_min)" value="0" required
        class="flex-1 min-w-[80px] text-sm px-3 py-1 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#b4c24d]">
    <input name="variantes[${index}][costo]" type="number" step="0.01" min="0.01" placeholder="Costo *" required
        class="flex-1 min-w-[100px] text-sm px-3 py-1 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#b4c24d]">
    <input name="variantes[${index}][precio]" type="number" step="0.01" min="0.01" placeholder="Precio *" required
        class="flex-1 min-w-[100px] text-sm px-3 py-1 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#b4c24d]">
    <input type="file" name="variantes[${index}][imagen]" accept="image/*"
        class="flex-1 min-w-[120px] text-xs px-3 py-1">
    <button type="button" class="remove bg-[#e15871] text-white hover:bg-[#c64a61] p-2 rounded-lg transition text-sm">🗑️</button>
  </div>`;
}

// ➕ Escuchar los nuevos botones
addBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        const type = btn.getAttribute('data-type');
        const html = getVariantHtml(type, idx);

        cont.insertAdjacentHTML('beforeend', html);

        // Volver a asignar el evento de eliminación
        cont.querySelectorAll('.remove').forEach(removeBtn => {
            removeBtn.onclick = (e) => {
                e.target.closest('.var').remove();
                actualizarBloqueoCampos();
            };
        });

        idx++;
        actualizarBloqueoCampos();
    });
});

// 💵 Cálculo automático de margen y ganancia (Misma lógica)
function actualizarMargenGanancia() {
  const costoInput = document.getElementById('costo');
  const precioInput = document.getElementById('precio_unitario');
  const margenInput = document.getElementById('margen');
  const gananciaInput = document.getElementById('ganancia');
    
    if (costoInput.disabled) {
        margenInput.value = 'Deshabilitado';
        gananciaInput.value = 'Deshabilitado';
        return;
    }
    
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
document.addEventListener('DOMContentLoaded', actualizarBloqueoCampos);
</script>
</body>
</html>