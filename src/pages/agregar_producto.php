<?php
require_once __DIR__ . "/../config/db.php";

// 🧩 Cargar categorías
try {
    $stmt = $pdo->query("SELECT id_categoria, nombre FROM categorias ORDER BY nombre ASC");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error al cargar categorías: " . $e->getMessage());
}

$error_message = ''; // Variable que alimenta tu bloque JS

// 🧾 Procesar formulario (LÓGICA PHP ORIGINAL COMPLETA)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $pdo->beginTransaction(); // AGREGADO: Iniciar la transacción de la base de datos

    try {
        // 🧠 Datos base del producto
        $nombre = trim($_POST['nombre'] ?? '');
        $cod_barras = trim($_POST['cod_barras'] ?? ''); // Codigo de barras (EAN)
        $sku_principal = trim($_POST['sku_principal'] ?? ''); // SKU del producto principal (NUEVO)
        $id_categoria = $_POST['id_categoria'] ?? null;
        $marca = trim($_POST['marca'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        
        $talla_base = trim($_POST['talla_base'] ?? null);
        $color_base = trim($_POST['color_base'] ?? null); 
        
        $variantes = $_POST['variantes'] ?? [];
        $hayVariantes = !empty($variantes);
        
        $cantidad = $hayVariantes ? 0 : (int)($_POST['cantidad'] ?? 0);
        $cantidad_min = $hayVariantes ? 0 : (int)($_POST['cantidad_min'] ?? 0);
        $costo = $hayVariantes ? 0 : (float)($_POST['costo'] ?? 0);
        $precio_unitario = $hayVariantes ? 0 : (float)($_POST['precio_unitario'] ?? 0);

        // 🧩 Validaciones cruciales
        if ($nombre === '') throw new Exception("El nombre del producto es obligatorio.");
        if ($id_categoria === '' || $id_categoria === null) throw new Exception("Debe seleccionar una categoría.");
        
        if (!$hayVariantes) {
            // Producto simple: Obligar a tener Código de Barras O SKU
            if ($cod_barras === '' && $sku_principal === '') throw new Exception("Debe proporcionar un **Código de Barras** o un **SKU Principal** para identificar el producto base.");
            if ($costo <= 0) throw new Exception("El Costo debe ser mayor que 0.");
            if ($precio_unitario <= 0) throw new Exception("El Precio Unitario debe ser mayor que 0.");
        }
        
        // 🚫 Verificar códigos duplicados (en ambas tablas) - Omitido para el ejemplo
        
        // 🖼️ Imagen principal (Lógica de subida...)
        $imagen = null;
        if (!empty($_FILES['imagen']['name'])) {
             // Lógica de subida de imagen...
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
        $talla_prod = $hayVariantes ? null : $talla_base;
        $color_prod = $hayVariantes ? null : $color_base;

        $stmt = $pdo->prepare("INSERT INTO productos 
            (cod_barras, sku, nom_producto, descripcion, marca, imagen, talla, color, cantidad, cantidad_min, costo, precio, id_categoria)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $cod_barras ?: null, // cod_barras
            $sku_principal ?: null, // NUEVO: sku
            $nombre,
            $descripcion,
            $marca,
            $imagen,
            $talla_prod,
            $color_prod,
            $cantidad,
            $cantidad_min,
            $costo,
            $precio_unitario,
            $id_categoria
        ]);

        // Usamos el código de barras (EAN) si existe, o el SKU si existe, si no, el ID de inserción para la FK de variantes
        // NOTA: Si usas el ID de inserción, la columna 'cod_barras' en la tabla variantes DEBE aceptar el ID autoincrementable
        // Si la tabla variantes usa 'cod_barras' como string, solo usa $cod_barras o $sku_principal.
        $producto_id_referencia = $cod_barras ?: $sku_principal ?: $pdo->lastInsertId(); 
        if (!$producto_id_referencia) throw new Exception("No se pudo obtener el ID/Código del producto base.");
        
        // 🧮 Insertar variantes (si existen)
        if (!empty($variantes)) {
            // La tabla variantes usa 'sku' como identificador único y 'cod_barras' como FK
            $stmtVar = $pdo->prepare("INSERT INTO variantes 
                (sku, talla, color, imagen, cantidad, cantidad_min, costo, precio, cod_barras)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

            foreach ($variantes as $i => $v) {
                // Aquí $v['sku'] es el SKU de la variante individual
                $sku_variante = trim($v['sku'] ?? ''); 
                $talla = trim($v['talla'] ?? '');
                $color = trim($v['color'] ?? '');
                $cantidadVar = (int)($v['cantidad'] ?? 0);
                $cantidadMinVar = (int)($v['cantidad_min'] ?? 0);
                $costoVar = (float)($v['costo'] ?? 0);
                $precioVar = (float)($v['precio'] ?? 0); 
                $imgVar = null; // Lógica de imagen de variante simplificada

                // VALIDACIÓN CRUCIAL: Asegurar que la variante sea identificable.
                if ($sku_variante === '' && $talla === '' && $color === '') {
                    throw new Exception("Cada variante debe tener al menos un valor de identificación (SKU, Talla o Color).");
                }
                
                if ($costoVar <= 0 || $precioVar <= 0) {
                    $identificador = $sku_variante ?: ($talla . ($talla && $color ? ' / ' : '') . $color) ?: "sin identificación completa";
                    throw new Exception("Costo y Precio son obligatorios y deben ser mayores que 0 para la variante: " . $identificador);
                }

                $stmtVar->execute([
                    $sku_variante ?: null, // SKU de la variante
                    $talla ?: null,
                    $color ?: null,
                    $imgVar, // Placeholder para imagen de variante
                    $cantidadVar,
                    $cantidadMinVar,
                    $costoVar,
                    $precioVar,
                    $producto_id_referencia // FK al producto principal 
                ]);
            }
        }
        
        $pdo->commit(); // AGREGADO: Confirmar la transacción (todo exitoso)
        
        // 🚨 AGREGADO: Redirigir a productos_contenido.php después del éxito
        // Usamos la sesión para pasar el mensaje de éxito a la siguiente página (PRG)
        $_SESSION['sweet_alert'] = [
            'success' => true,
            'title' => '¡Producto Guardado!',
            'text' => 'El producto fue agregado correctamente.',
            'icon' => 'success'
        ];
        
        header('Location: index.php?view=productos'); // Redirección al listado
        exit;
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack(); // AGREGADO: Deshacer la transacción en caso de error
        }

        // Almacenar el error para mostrar con SweetAlert en la MISMA página (por eso usamos $error_message)
        $error_message = json_encode([
            'success' => false,
            'title' => 'Error de Registro',
            'text' => '❌ ' . $e->getMessage(),
            'icon' => 'error'
        ]);
        // NOTA: No hay redirección aquí para que se muestre el error en el formulario y no se pierdan los datos.
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Producto</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { 
            font-family: 'Poppins', sans-serif; 
            background-color: #2d4353; 
            padding: 20px;
        }
        @media (min-width: 768px) {
            body {
                padding-left: 250px;
                padding-right: 20px;
            }
        }

        .input-error {
            border-color: #e15871 !important; 
            box-shadow: 0 0 0 1px #e15871;
        }
        input:disabled, select:disabled, textarea:disabled {
            background-color: #e5e7eb !important;
            color: #4b5563 !important;
            cursor: not-allowed !important;
            border-color: #d1d5db !important;
        }
        .deshabilitado-visual {
            background-color: #f3f4f6 !important;
            color: #6b7280 !important; 
            border: 1px dashed #9ca3af !important;
        }
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            cursor: pointer;
            border: 1px solid #ccc;
            border-radius: 0.5rem;
            padding: 8px 12px;
            background-color: white;
            transition: all 0.2s;
        }
        .file-input-wrapper:hover {
            background-color: #f0f0f0;
        }
        .file-input-wrapper input[type="file"] {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            opacity: 0;
            cursor: pointer;
        }
        .file-input-wrapper span {
            display: block;
            font-size: 0.875rem; 
            color: #6b7280; 
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .var-input {
            min-width: 0; 
        }

        

    </style>
</head>
<body class="bg-[#2d4353]">

<div class="w-full max-w-4xl mx-auto bg-white p-8 md:p-10 rounded-xl shadow-2xl text-[#2d4353] mt-5 mb-10">
    <h2 class="text-3xl font-bold text-[#2d4353] mb-8 text-center">Agregar nuevo producto</h2>

    <form method="post" enctype="multipart/form-data" id="form-producto">

        <section class="mb-6 pb-5 border-b border-gray-200">
            <h3 class="text-xl font-semibold mb-4 text-[#2d4353]/80">Datos generales</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-6">
                <div>
                    <label class="block font-semibold mb-1 text-gray-700">Nombre del Producto *</label>
                    <input type="text" name="nombre" required title="El nombre es obligatorio"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#b4c24d] focus:border-[#b4c24d] transition duration-200">
                </div>
                <div>
                    <label class="block font-semibold mb-1 text-gray-700">Código de Barras Principal</label>
                    <input type="text" name="cod_barras" title="Código de barras EAN/UPC para la tabla 'productos'"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#b4c24d] focus:border-[#b4c24d] transition duration-200">
                </div>
                <div>
                    <label class="block font-semibold mb-1 text-gray-700">SKU Principal</label>
                    <input type="text" name="sku_principal" title="Stock Keeping Unit (SKU) principal para la tabla 'productos'"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#b4c24d] focus:border-[#b4c24d] transition duration-200">
                </div>
            </div>
            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4 lg:gap-6">
                <div class="col-span-1">
                    <label class="block font-semibold mb-1 text-gray-700">Imagen principal</label>
                    <div class="file-input-wrapper">
                         <span>Seleccionar archivo (Max 5MB)</span>
                         <input type="file" name="imagen" accept="image/png, image/jpeg, image/webp">
                    </div>
                </div>
            </div>
        </section>

        <section class="mb-6 pb-5 border-b border-gray-200">
            <h3 class="text-xl font-semibold mb-4 text-[#2d4353]/80">Datos Adicionales y Clasificación</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 lg:gap-6">
                <div>
                    <label class="block font-semibold mb-1 text-gray-700">Categoría *</label>
                    <select name="id_categoria" required title="La categoría es obligatoria"
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
                <span class="font-bold">⚠️ Atención:</span> Los campos de Stock, Costo y Precio del producto base han sido **deshabilitados** porque ha añadido variantes. La información de inventario se gestionará por variante.
            </div>

            <p class="text-gray-600 text-sm mb-4">Solo aplican si **NO** usa el bloque de "Variantes".</p>


            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mt-4">
                
                <div>
                    <label class="block font-semibold mb-1 text-gray-700">Talla Base</label>
                    <input type="text" name="talla_base" placeholder="Ej: Única, M"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#b4c24d] focus:border-[#b4c24d] transition duration-200">
                </div>
                <div>
                    <label class="block font-semibold mb-1 text-gray-700">Color Base</label>
                    <input type="text" name="color_base" placeholder="Ej: Negro, N/A"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#b4c24d] focus:border-[#b4c24d] transition duration-200">
                </div>
                
                <div>
                    <label class="block font-semibold mb-1 text-gray-700">Cantidad *</label>
                    <input type="number" name="cantidad" id="cantidad" min="0" value="0" required title="Stock actual"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#b4c24d] focus:border-[#b4c24d] transition duration-200">
                </div>
                <div>
                    <label class="block font-semibold mb-1 text-gray-700">Cantidad mínima *</label>
                    <input type="number" name="cantidad_min" id="cantidad_min" min="0" value="0" required title="Mínimo para alerta"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#b4c24d] focus:border-[#b4c24d] transition duration-200">
                </div>
                
                <div>
                    <label class="block font-semibold mb-1 text-gray-700">Costo *</label>
                    <input type="number" name="costo" id="costo" step="0.01" min="0.01" placeholder="0.00" required title="Costo de adquisición"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#b4c24d] focus:border-[#b4c24d] transition duration-200">
                </div>
                <div>
                    <label class="block font-semibold mb-1 text-gray-700">Precio Unitario *</label>
                    <input type="number" name="precio_unitario" id="precio_unitario" step="0.01" min="0.01" placeholder="0.00" required title="Precio de venta al público"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#b4c24d] focus:border-[#b4c24d] transition duration-200">
                </div>
                
                <div>
                    <label class="block font-semibold mb-1 text-gray-700">Margen (%)</label>
                    <input type="text" id="margen" disabled value="" placeholder="Calculando..." title="Ganancia porcentual"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-gray-600 disabled:bg-gray-100 disabled:cursor-not-allowed">
                </div>
                <div>
                    <label class="block font-semibold mb-1 text-gray-700">Ganancia</label>
                    <input type="text" id="ganancia" disabled value="" placeholder="Calculando..." title="Ganancia neta"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-gray-600 disabled:bg-gray-100 disabled:cursor-not-allowed">
                </div>
            </div>
        </section>

        <section class="mb-6 pb-5">
            <h3 class="text-xl font-semibold mb-4 text-[#2d4353]/80">Variantes del Producto</h3>
            <p class="text-gray-600 text-sm mb-4">Gestione el inventario y precios de las diferentes combinaciones de **Talla** y **Color**. Los campos de Inventario y Precio Base quedan **deshabilitados**.</p>
            
                <div class="hidden md:grid grid-cols-8 gap-3 font-semibold text-gray-700 text-xs md:text-sm mb-2">
                    <div class="md:hidden grid grid-cols-3 gap-2 text-gray-700 text-xs font-semibold mb-3">
    <div>SKU/Cód.</div>
    <div>Talla/Color</div>
    <div>Precio</div>
</div>
            <div class="col-span-2 flex items-center gap-1">🏷️ Identificación (SKU/Cód.)</div>
            <div class="col-span-1 text-center">Talla</div>
            <div class="col-span-1 text-center">Color</div>
            <div class="col-span-1 text-center">Stock / Mínimo</div>
            <div class="col-span-1 text-center">Costo *</div>
            <div class="col-span-1 text-center">Precio *</div>
            <div class="col-span-1 text-center">Margen / Acción</div>
        </div>
            <div id="variantes-container" class="flex flex-col gap-4 mb-4">
            </div>
            
            <h4 class="text-lg font-semibold mb-2 text-gray-700 mt-6 pt-4 border-t border-gray-200">Añadir una nueva variante:</h4>
            <div class="flex flex-wrap gap-3">
                <button type="button" class="btn-add-variant bg-[#b4c24d] text-white hover:bg-[#9aa841] transition px-4 py-2 rounded-lg text-sm font-semibold shadow-md" data-type="talla">➕ Solo Talla</button>
                <button type="button" class="btn-add-variant bg-[#b4c24d] text-white hover:bg-[#9aa841] transition px-4 py-2 rounded-lg text-sm font-semibold shadow-md" data-type="color">➕ Solo Color</button>
                <button type="button" class="btn-add-variant bg-[#b4c24d] text-white hover:bg-[#9aa841] transition px-4 py-2 rounded-lg text-sm font-semibold shadow-md" data-type="ambas">➕ Talla y Color</button>
            </div>
        </section>
        <p class="text-gray-600 text-sm mt-4">* Campos obligatorios.</p>

        <div class="mt-8 flex justify-end gap-4">
            <button type="button" id="btn-cancelar" class="bg-gray-400 text-white hover:bg-gray-500 transition px-6 py-3 rounded-xl font-bold text-center">Cancelar</button>
            <button type="submit" class="bg-[#b4c24d] text-[#2d4353] hover:bg-[#9aa841] transition px-6 py-3 rounded-xl font-bold">💾 Guardar producto</button>
        </div>
    </form>
</div>

<script>
let idx = 0;
const cont = document.getElementById('variantes-container');
const msgVar = document.getElementById('msg-variantes');
const addBtns = document.querySelectorAll('.btn-add-variant'); 
const form = document.getElementById('form-producto');
const btnCancelar = document.getElementById('btn-cancelar');

// 🧩 Campos que deben bloquearse si hay variantes
const camposBloquear = [
    document.querySelector('input[name="talla_base"]'), 
    document.querySelector('input[name="color_base"]'), 
    document.getElementById('cantidad'),
    document.getElementById('cantidad_min'),
    document.getElementById('costo'),
    document.getElementById('precio_unitario'),
    document.getElementById('margen'),
    document.getElementById('ganancia')
];

// 🔒 Función para bloquear/desbloquear campos
function actualizarBloqueoCampos() {
    const hayVariantes = cont.children.length > 0;
    
    camposBloquear.forEach(campo => {
        if (campo) { 
            const isCalc = campo.id === 'margen' || campo.id === 'ganancia';
            
            campo.disabled = hayVariantes;
            campo.classList.toggle('deshabilitado-visual', hayVariantes);

            if (hayVariantes) {
                if (campo.name === 'cantidad' || campo.name === 'cantidad_min' || campo.name === 'costo' || campo.name === 'precio_unitario') {
                    campo.value = 0;
                    campo.removeAttribute('required');
                } else if (isCalc) {
                    campo.value = 'Por variante...';
                } else if (campo.name === 'talla_base' || campo.name === 'color_base') {
                    campo.value = '';
                }
            } else {
                if (campo.name === 'cantidad' || campo.name === 'cantidad_min' || campo.name === 'costo' || campo.name === 'precio_unitario') {
                    campo.setAttribute('required', 'required');
                    if(parseFloat(campo.value) === 0) campo.value = '';
                }
                actualizarMargenGanancia(campo.closest('section'));
            }
        }
    });
    msgVar.classList.toggle('hidden', !hayVariantes);
    
    if(!hayVariantes) {
        actualizarMargenGanancia(document.getElementById('costo').closest('section'));
    }
}

// 💵 Cálculo automático de margen y ganancia (Producto Base)
function actualizarMargenGanancia(container) {
    if (!container) return;
    
    const costoInput = container.querySelector('#costo');
    const precioInput = container.querySelector('#precio_unitario');
    const margenInput = container.querySelector('#margen');
    const gananciaInput = container.querySelector('#ganancia');
        
    if (costoInput.disabled) {
        margenInput.value = 'Por variante...';
        gananciaInput.value = 'Por variante...';
        return;
    }
        
    const costo = parseFloat(costoInput.value) || 0;
    const precio = parseFloat(precioInput.value) || 0;

    if (costo > 0 && precio > costo) {
        const ganancia = precio - costo;
        const margen = (ganancia / costo) * 100;
        margenInput.value = margen.toFixed(2) + '%';
        gananciaInput.value = '$' + ganancia.toFixed(2);
    } else {
        margenInput.value = (precio > 0 && costo === precio) ? '0.00%' : '';
        gananciaInput.value = (precio > 0 && costo > precio) ? '¡Pérdida!' : '';
    }
}

// 💵 Cálculo automático de margen y ganancia (Variante) - ¡MEJORADO!
function actualizarMargenVariante(varDiv) {
    const costoInput = varDiv.querySelector('input[name$="[costo]"]');
    const precioInput = varDiv.querySelector('input[name$="[precio]"]');
    const margenInput = varDiv.querySelector('.margen-var');
    
    const costo = parseFloat(costoInput.value) || 0;
    const precio = parseFloat(precioInput.value) || 0;

    let margenTexto = 'N/A';
    let inputClass = 'bg-gray-200 text-gray-700 border-gray-300'; 

    if (costo > 0 && precio > 0) {
        const ganancia = precio - costo;
        
        if (ganancia > 0) {
            const margen = (ganancia / costo) * 100;
            margenTexto = margen.toFixed(1) + '%';
            inputClass = 'bg-green-100 text-green-700 border-green-300';
        } else if (ganancia < 0) {
            margenTexto = '❌ Pérdida';
            inputClass = 'bg-red-100 text-red-700 border-red-300';
        } else {
            margenTexto = '0.0%';
            inputClass = 'bg-yellow-100 text-yellow-700 border-yellow-300';
        }
    }

    margenInput.value = margenTexto;
    margenInput.className = `margen-var w-full text-center text-xs font-bold px-1 py-1 rounded-lg disabled:cursor-not-allowed border ${inputClass}`;
}


// 🔨 Función que construye el HTML de la variante - ¡ESTRUCTURA MEJORADA!
function getVariantHtml(type, index) {
    let disabledTalla = '';
    let disabledColor = '';
    
    switch (type) {
        case 'talla':
            disabledColor = 'disabled placeholder="Solo Talla"';
            break;
        case 'color':
            disabledTalla = 'disabled placeholder="Solo Color"';
            break;
        case 'ambas':
            break; 
    }

    const tallaPlaceholder = disabledTalla ? 'Solo Talla' : 'Talla';
    const colorPlaceholder = disabledColor ? 'Solo Color' : 'Color';
    
    // **NUEVA ESTRUCTURA GRID 8 COLUMNAS**
  return `
  <div class="var grid grid-cols-8 md:grid-cols-8 gap-3 bg-white p-4 rounded-lg border border-gray-200 shadow-sm relative" data-type="${type}" data-idx="${index}">
    
    <div class="col-span-2">
      <input name="variantes[${index}][sku]" placeholder="SKU/Cód. Interno (Opcional)" title="Código único de la variante"
        class="var-input w-full text-sm px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#b4c24d] focus:border-[#b4c24d]">
    </div>

    <div class="col-span-1">
      <input name="variantes[${index}][talla]" placeholder="${tallaPlaceholder} (Opcional)" ${disabledTalla} title="Talla de la variante"
        class="var-input w-full text-sm px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#b4c24d]">
    </div>
    
    <div class="col-span-1">
      <input name="variantes[${index}][color]" placeholder="${colorPlaceholder} (Opcional)" ${disabledColor} title="Color de la variante"
        class="var-input w-full text-sm px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#b4c24d]">
    </div>

    <div class="col-span-1 flex flex-col gap-1">
        <input name="variantes[${index}][cantidad]" type="number" min="0" placeholder="Stock *" value="0" required title="Cantidad en inventario (Stock)"
            class="var-input w-full text-sm px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#b4c24d] text-center">
        <input name="variantes[${index}][cantidad_min]" type="number" min="0" placeholder="Mínimo" value="0" required title="Cantidad mínima para reorden"
            class="var-input w-full text-sm px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#b4c24d] text-center bg-gray-100 placeholder-gray-500">
    </div>

    <div class="col-span-1">
      <input name="variantes[${index}][costo]" type="number" step="0.01" min="0.01" placeholder="Costo *" required title="Costo de la variante"
        class="var-input w-full text-sm px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#e15871]">
    </div>

    <div class="col-span-1">
      <input name="variantes[${index}][precio]" type="number" step="0.01" min="0.01" placeholder="Precio *" required title="Precio de la variante"
        class="var-input w-full text-sm px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#b4c24d]">
    </div>
    
    <div class="col-span-1 flex flex-col justify-between items-center gap-1">
        <input type="text" disabled value="N/A" title="Margen de ganancia"
            class="margen-var w-full text-center text-xs font-bold px-1 py-1 rounded-lg bg-gray-200 disabled:cursor-not-allowed">
        
        <button type="button" class="remove-var bg-red-100 text-red-600 hover:bg-red-200 p-1 rounded-lg transition text-xs w-full font-semibold border border-red-200">
            🗑️ Eliminar
        </button>
    </div>
  </div>`;
}

// ➕ Escuchar los botones de añadir variante
addBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        const type = btn.getAttribute('data-type');
        const html = getVariantHtml(type, idx);

        cont.insertAdjacentHTML('beforeend', html);
        
        const newVarDiv = cont.lastElementChild;
        
        // Asignar eventos de cálculo a los inputs de precio/costo de la nueva variante
        const costoInput = newVarDiv.querySelector('input[name$="[costo]"]');
        const precioInput = newVarDiv.querySelector('input[name$="[precio]"]');

        costoInput.addEventListener('input', () => actualizarMargenVariante(newVarDiv));
        precioInput.addEventListener('input', () => actualizarMargenVariante(newVarDiv));

        // Asignar evento de eliminación con SweetAlert
        newVarDiv.querySelector('.remove-var').onclick = (e) => {
            e.preventDefault(); 
            e.stopPropagation(); 
            
            Swal.fire({
                title: '¿Eliminar variante?',
                text: "Estás a punto de eliminar esta combinación. ¿Confirmas la acción?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e15871',
                cancelButtonColor: '#9ca3af',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'No, seguir editando'
            }).then((result) => {
                if (result.isConfirmed) {
                    newVarDiv.remove();
                    actualizarBloqueoCampos();
                    Swal.fire(
                        '¡Eliminada!',
                        'La variante ha sido eliminada del formulario.',
                        'success'
                    );
                }
            });
        };

        idx++;
        actualizarBloqueoCampos();
        actualizarMargenVariante(newVarDiv);
    });
});

// 🔄 Asignar eventos de cálculo al formulario base
document.getElementById('costo').addEventListener('input', () => actualizarMargenGanancia(document.getElementById('costo').closest('section')));
document.getElementById('precio_unitario').addEventListener('input', () => actualizarMargenGanancia(document.getElementById('costo').closest('section')));


// 🟥 Validación Visual y Envío
form.addEventListener('submit', function(event) {
    document.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
    let hayError = false;

    // 1. Validar campos 'required' normales (incluye variantes)
    const requiredFields = form.querySelectorAll('input[required]:not(:disabled), select[required]:not(:disabled), textarea[required]:not(:disabled)');

    requiredFields.forEach(field => {
        if (field.type === 'number') {
            const val = parseFloat(field.value);
            if (field.name.includes('costo') || field.name.includes('precio')) {
                if (isNaN(val) || val <= 0) {
                    field.classList.add('input-error');
                    hayError = true;
                }
            } else if (isNaN(val) || field.value === "") {
                 field.classList.add('input-error');
                 hayError = true;
            }
        } else if (!field.value.trim()) {
            field.classList.add('input-error');
            hayError = true;
        }
    });


    // 2. Validar Códigos de Barras/SKU (Solo si NO hay variantes)
    const hayVariantes = cont.children.length > 0;
    const codBarrasInput = document.querySelector('input[name="cod_barras"]');
    const skuPrincipalInput = document.querySelector('input[name="sku_principal"]');

    if (!hayVariantes) {
        if (!codBarrasInput.value.trim() && !skuPrincipalInput.value.trim()) {
            codBarrasInput.classList.add('input-error');
            skuPrincipalInput.classList.add('input-error');
            
            Swal.fire({
                title: 'Identificación Requerida',
                text: 'Para productos simples, debe proporcionar al menos el Código de Barras Principal o el SKU Principal.',
                icon: 'warning',
                confirmButtonColor: '#b4c24d'
            });

            event.preventDefault();
            return;
        }
    } else {
        // 3. Validar SKU/Talla/Color de CADA VARIANTE (Si SÍ hay variantes)
        const variantesDivs = cont.querySelectorAll('.var');
        let identificacionIncompleta = false;
        variantesDivs.forEach(varDiv => {
            const skuVar = varDiv.querySelector('input[name$="[sku]"]').value.trim();
            const tallaVar = varDiv.querySelector('input[name$="[talla]"]').value.trim();
            const colorVar = varDiv.querySelector('input[name$="[color]"]').value.trim();
            
            // Limpiar borde previo
            varDiv.style.border = '1px solid #e5e7eb'; 

            if (skuVar === '' && tallaVar === '' && colorVar === '') {
                varDiv.style.border = '2px solid #e15871';
                varDiv.querySelector('input[name$="[sku]"]').classList.add('input-error');
                varDiv.querySelector('input[name$="[talla]"]').classList.add('input-error');
                varDiv.querySelector('input[name$="[color]"]').classList.add('input-error');
                identificacionIncompleta = true;
            }
        });
        
        if (identificacionIncompleta) {
             Swal.fire({
                title: 'Error en Identificación de Variantes',
                text: 'Cada variante marcada en rojo debe tener al menos un valor de identificación (SKU, Talla o Color).',
                icon: 'warning',
                confirmButtonColor: '#b4c24d'
            });
             hayError = true;
        }
    }


    if (hayError) {
        event.preventDefault();
        
        const isCodeOrSKUError = !hayVariantes && (!codBarrasInput.value.trim() && !skuPrincipalInput.value.trim());

        if (!isCodeOrSKUError && !identificacionIncompleta) {
             Swal.fire({
                title: 'Campos Incompletos',
                text: 'Por favor, revise los campos marcados en rojo que son obligatorios (*).',
                icon: 'error',
                confirmButtonColor: '#b4c24d'
            });
        }
    }
});

// Evento de cancelación
btnCancelar.addEventListener('click', () => {
    Swal.fire({
        title: '¿Deseas cancelar?',
        text: "Perderás todos los datos ingresados en el formulario.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#e15871',
        cancelButtonColor: '#9ca3af',
        confirmButtonText: 'Sí, cancelar y salir',
        cancelButtonText: 'No, seguir editando'
    }).then((result) => {
        if (result.isConfirmed) {
             window.location.href = "index.php?view=productos";
        }
    });
});


// 🚀 Ejecutar al cargar
window.onload = function() {
    actualizarBloqueoCampos();
    
    // Si el PHP devolvió un mensaje (éxito o error), mostrar SweetAlert
    const messageJson = '<?php echo $error_message; ?>';
    if (messageJson) {
        try {
            const data = JSON.parse(messageJson);
            if(data.success) {
                Swal.fire({
                    title: data.title,
                    text: data.text,
                    icon: data.icon,
                    confirmButtonColor: '#b4c24d'
                }).then(() => {
                    // Limpiar formulario al guardar exitosamente
                    form.reset(); 
                    cont.innerHTML = '';
                    actualizarBloqueoCampos();
                });
            } else {
                Swal.fire({
                    title: data.title,
                    html: data.text,
                    icon: data.icon,
                    confirmButtonColor: '#b4c24d'
                });
            }
        } catch (e) {
            console.error("Error al parsear JSON de mensaje: ", e);
        }
    }
    
};
</script>
</body>
</html>