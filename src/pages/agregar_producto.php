<?php
require_once __DIR__ . "/../config/db.php";

// -----------------------------
// Helpers
// -----------------------------
function sanitize_sku_component($str) {
    $s = mb_strtoupper(trim($str));
    $s = preg_replace('/\s+/', '-', $s); // spaces -> hyphen
    $s = preg_replace('/[^A-Z0-9\-]/u', '', $s); // remove non-alnum/hyphen
    return $s;
}

// -----------------------------
// Cargar categorías
// -----------------------------
try {
    $stmt = $pdo->query("SELECT id_categoria, nombre FROM categorias ORDER BY nombre ASC");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error al cargar categorías: " . $e->getMessage());
}

$error_message = ''; // cadena JSON para frontend

// -----------------------------
// Procesar formulario
// -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->beginTransaction();
    try {
        // Datos base del producto
        $nombre = trim($_POST['nombre'] ?? '');
        $cod_barras = trim($_POST['cod_barras'] ?? '');
        $sku_principal = trim($_POST['sku_principal'] ?? '');
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

        // Validaciones base
        if ($nombre === '') throw new Exception("El nombre del producto es obligatorio.");
        if ($id_categoria === '' || $id_categoria === null) throw new Exception("Debe seleccionar una categoría.");

        if (!$hayVariantes) {
            if ($cod_barras === '' && $sku_principal === '') throw new Exception("Debe proporcionar un Código de Barras o un SKU Principal para identificar el producto base.");
            if ($costo <= 0) throw new Exception("El Costo debe ser mayor que 0 cuando no hay variantes.");
            if ($precio_unitario <= 0) throw new Exception("El Precio Unitario debe ser mayor que 0 cuando no hay variantes.");
            if ($cantidad < 0) throw new Exception("La cantidad debe ser 0 o mayor.");
        }

        // Imagen principal (opcional)
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
                throw new Exception("Error al guardar la imagen principal.");
            }
            $imagen = $nombreArchivo;
        }

        // Insert producto base
        $talla_prod = $hayVariantes ? null : $talla_base;
        $color_prod = $hayVariantes ? null : $color_base;

        $stmt = $pdo->prepare("INSERT INTO productos 
            (cod_barras, sku, nom_producto, descripcion, marca, imagen, talla, color, cantidad, cantidad_min, costo, precio, id_categoria)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $cod_barras ?: null,
            $sku_principal ?: null,
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

        $producto_id_referencia = $cod_barras ?: $sku_principal ?: $pdo->lastInsertId();
        if (!$producto_id_referencia) throw new Exception("No se pudo obtener el ID/Código del producto base.");

        // Validaciones e inserción de variantes
        if (!empty($variantes)) {
            $stmtVar = $pdo->prepare("INSERT INTO variantes 
                (sku, talla, color, imagen, cantidad, cantidad_min, costo, precio, cod_barras)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            for ($i = 0; $i < count($variantes); $i++) {
                $v = $variantes[$i];
                $sku_variante = trim($v['sku'] ?? '');
                $talla = trim($v['talla'] ?? '');
                $color = trim($v['color'] ?? '');
                $cantidadVar = (int)($v['cantidad'] ?? 0);
                $cantidadMinVar = (int)($v['cantidad_min'] ?? 0);
                $costoVar = (float)($v['costo'] ?? 0);
                $precioVar = (float)($v['precio'] ?? 0);

                // Imagen variante (si se subió)
                $imgVar = null;
                if (isset($_FILES['variantes_imagenes']) && isset($_FILES['variantes_imagenes']['name'][$i]) && $_FILES['variantes_imagenes']['name'][$i]) {
                    $ext = strtolower(pathinfo($_FILES['variantes_imagenes']['name'][$i], PATHINFO_EXTENSION));
                    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                        throw new Exception("Formato de imagen no válido para la variante #".($i+1));
                    }
                    $carpetaV = __DIR__ . "/../uploads/variants/";
                    if (!is_dir($carpetaV)) mkdir($carpetaV, 0777, true);
                    $nombreArchivoV = uniqid("var_") . "." . $ext;
                    $rutaV = $carpetaV . $nombreArchivoV;
                    if (!move_uploaded_file($_FILES['variantes_imagenes']['tmp_name'][$i], $rutaV)) {
                        throw new Exception("Error al guardar la imagen de la variante #".($i+1));
                    }
                    $imgVar = $nombreArchivoV;
                }

                // Validaciones por variante
                if ($sku_variante === '' && $talla === '' && $color === '') {
                    throw new Exception("Cada variante debe tener al menos un valor de identificación (SKU, Talla o Color). (variante #".($i+1).")");
                }
                if ($costoVar <= 0 || $precioVar <= 0) {
                    $identificador = $sku_variante ?: ($talla . ($talla && $color ? ' / ' : '') . $color) ?: "sin identificación completa";
                    throw new Exception("Costo y Precio son obligatorios y deben ser mayores que 0 para la variante: " . $identificador);
                }
                if ($cantidadVar < 0) throw new Exception("La cantidad no puede ser negativa en la variante: " . ($sku_variante ?: $i+1));

                // Generar SKU en servidor si falta (NOMBRE-TALLA-COLOR)
                if ($sku_variante === '') {
                    $parts = [];
                    if ($nombre) $parts[] = sanitize_sku_component($nombre);
                    if ($talla) $parts[] = sanitize_sku_component($talla);
                    if ($color) $parts[] = sanitize_sku_component($color);
                    $sku_variante = implode('-', $parts);
                    if (!$sku_variante) $sku_variante = 'VAR-' . uniqid();
                }

                $stmtVar->execute([
                    $sku_variante,
                    $talla ?: null,
                    $color ?: null,
                    $imgVar,
                    $cantidadVar,
                    $cantidadMinVar,
                    $costoVar,
                    $precioVar,
                    $producto_id_referencia
                ]);
            }
        }

        $pdo->commit();

        $_SESSION['sweet_alert'] = [
            'success' => true,
            'title' => '¡Producto Guardado!',
            'text' => 'El producto fue agregado correctamente.',
            'icon' => 'success'
        ];

        header('Location: index.php?view=productos');
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();

        $error_message = json_encode([
            'success' => false,
            'title' => 'Error de Registro',
            'text' => '❌ ' . $e->getMessage(),
            'icon' => 'error'
        ]);
    }
}
?>
 
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Agregar Producto | POS System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#f7f9ee',
                            100: '#eef3d9',
                            500: '#b4c24d', // Color principal para botones y focus
                            600: '#9aa841'
                        },
                        darkbg: '#2d4353'
                    },
                    borderRadius: { 'xl-2': '14px' }
                }
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body{ font-family: 'Poppins', sans-serif; background-color: #f7f7f7; } 
        .card { border-radius: 14px; box-shadow: 0 4px 12px rgba(0,0,0,0.06); } 
        .file-thumb { width:100%; height:100%; object-fit:cover; border-radius:inherit; }
        .disabled-visual { opacity: 0.5; pointer-events: none; background-color: #f7f7f7 !important; }
        .input-error { border-color: #ef4444 !important; box-shadow: 0 0 0 1px rgba(239,68,68,0.15); }
        .badge-disabled { 
            font-size: 10px; 
            background: #e5e7eb; 
            color:#4b5563; 
            padding:0.2rem .6rem; 
            border-radius:999px; 
            white-space: nowrap; /* Evita que el badge se rompa */
        }

        /* Estilos uniformes y elegantes para inputs */
        .form-input {
            padding: 0.5rem 0.75rem;
            border: 1px solid #e5e7eb; /* gray-200 */
            border-radius: 0.75rem; /* rounded-xl */
            transition: all 0.2s;
            color: #374151; /* gray-700 */
        }
        .form-input:focus {
            border-color: #b4c24d; /* brand-500 */
            box-shadow: 0 0 0 3px rgba(180,194,77,0.3);
            outline: none;
        }
        .form-input-disabled {
            background-color: #f3f4f6; /* gray-100 */
            border-color: #e5e7eb; /* gray-200 */
            color: #9ca3af; /* gray-400 */
            cursor: not-allowed;
        }
        .form-input-plain { /* Para el textarea de descripción */
            border: none !important;
            padding: 1rem;
        }

        /* AJUSTE CLAVE: Mejor espaciado en los inputs de la tabla de variantes */
        #table-variants .input-variant-small {
            padding: 0.25rem 0.5rem; /* Ajuste del padding */
            border-radius: 0.5rem; /* Un poco menos redondeado */
            box-shadow: none; /* Eliminar sombra duplicada */
        }
        #table-variants td {
            padding-top: 0.75rem; /* Aumento del espaciado */
            padding-bottom: 0.75rem; /* Aumento del espaciado */
            vertical-align: top; /* Alinear arriba para inputs apilados */
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50 text-gray-700">

    <div class="max-w-6xl mx-auto px-4 py-12 lg:py-9">
        
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Agregar Nuevo Producto</h1>
                <p class="text-sm text-gray-500 mt-1">Añade la información básica, detalles, precios y variantes de tu producto.</p>
                <p class="text-xs text-red-500 mt-2">* Campos obligatorios</p>
            </div>
            <a href="index.php?view=productos" 
                class="inline-flex items-center gap-2 px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-xl hover:bg-gray-100 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                Volver
            </a>
        </div>

        <form id="form-producto" method="post" enctype="multipart/form-data"
              class="grid grid-cols-1 lg:grid-cols-3 gap-8" novalidate>
            
            <main class="lg:col-span-2 space-y-6">

                <section class="bg-white card p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-6">Información General</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Producto *</label>
                            <input name="nombre" id="nombre_producto" required
                                    value="<?= isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : '' ?>"
                                    class="form-input w-full">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Categoría *</label>
                            <select name="id_categoria" id="select_categoria" required
                                    class="form-input w-full">
                                <option value="">Seleccione</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?= $cat['id_categoria'] ?>"
                                        <?= (isset($_POST['id_categoria']) && $_POST['id_categoria']==$cat['id_categoria']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Marca</label>
                            <input name="marca"
                                    value="<?= htmlspecialchars($_POST['marca'] ?? '') ?>"
                                    class="form-input w-full">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">SKU Principal</label>
                            <input name="sku_principal" id="sku_principal"
                                    value="<?= htmlspecialchars($_POST['sku_principal'] ?? '') ?>"
                                    class="form-input w-full">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Código de Barras</label>
                            <input name="cod_barras" id="cod_barras"
                                    value="<?= htmlspecialchars($_POST['cod_barras'] ?? '') ?>"
                                    class="form-input w-full">
                        </div>
                    </div>

                    <div class="mt-5">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                        <div class="border border-gray-300 rounded-xl overflow-hidden">
                            <textarea name="descripcion" rows="4"
                                    class="w-full form-input-plain focus:ring-0 resize-none rounded-xl"
                                    placeholder="Detalles del producto, materiales, cuidados, etc."><?= htmlspecialchars($_POST['descripcion'] ?? '') ?></textarea>
                        </div>
                    </div>
                </section>

                <section class="bg-white card p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-semibold text-gray-800">Inventario y Precios Base</h2>
                        <div class="text-xs text-gray-400">Campos para producto **simple**</div>
                    </div>

                    <div id="msg-variantes" class="hidden mt-3 p-3 bg-rose-50 border border-rose-200 text-rose-700 rounded-lg text-sm flex items-center gap-2">
                        <span>&#9888;</span> Hay variantes definidas. Los campos de inventario y precio base se han **deshabilitado**.
                    </div>

                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Talla Base</label>
                            <input id="talla_base" name="talla_base" value="<?= isset($_POST['talla_base']) ? htmlspecialchars($_POST['talla_base']) : '' ?>" class="form-input w-full">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Color Base</label>
                            <input id="color_base" name="color_base" value="<?= isset($_POST['color_base']) ? htmlspecialchars($_POST['color_base']) : '' ?>" class="form-input w-full">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cantidad *</label>
                            <input id="cantidad" name="cantidad" type="number" min="0" value="<?= isset($_POST['cantidad']) ? intval($_POST['cantidad']) : 0 ?>" class="form-input w-full">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Stock Mínimo *</label>
                            <input id="cantidad_min" name="cantidad_min" type="number" min="0" value="<?= isset($_POST['cantidad_min']) ? intval($_POST['cantidad_min']) : 0 ?>" class="form-input w-full">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Costo ($) *</label>
                            <input id="costo" name="costo" type="number" step="0.01" min="0.01" value="<?= isset($_POST['costo']) ? htmlspecialchars($_POST['costo']) : '' ?>" class="form-input w-full">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Precio Unitario ($) *</label>
                            <input id="precio_unitario" name="precio_unitario" type="number" step="0.01" min="0.01" value="<?= isset($_POST['precio_unitario']) ? htmlspecialchars($_POST['precio_unitario']) : '' ?>" class="form-input w-full">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Margen</label>
                            <input id="margen" disabled class="form-input-disabled w-full">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ganancia</label>
                            <input id="ganancia" disabled class="form-input-disabled w-full">
                        </div>
                    </div>
                </section>

                <section class="bg-white card p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Variantes del Producto</h2>
                    <p class="text-sm text-gray-500 mb-4">Añade combinaciones de Talla y Color, o usa los botones rápidos.</p>

                    <div class="mb-5 flex flex-wrap items-center gap-3">
                        <button type="button" data-type="talla" class="btn-add-variant px-4 py-2 rounded-xl bg-brand-500 text-white hover:bg-brand-600 transition text-sm font-medium flex items-center gap-1"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" /></svg> Solo Talla</button>
                        <button type="button" data-type="color" class="btn-add-variant px-4 py-2 rounded-xl bg-brand-500 text-white hover:bg-brand-600 transition text-sm font-medium flex items-center gap-1"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" /></svg> Solo Color</button>
                        <button type="button" data-type="ambas" class="btn-add-variant px-4 py-2 rounded-xl bg-brand-500 text-white hover:bg-brand-600 transition text-sm font-medium flex items-center gap-1"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" /></svg> Talla y Color</button>
                        <button type="button" id="btn-add-row" class="px-4 py-2 rounded-xl border border-gray-300 bg-white hover:bg-gray-100 transition text-sm font-medium flex items-center gap-1"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" /></svg> Añadir fila rápida</button>
                    </div>

                    <div class="overflow-x-auto border border-gray-200 rounded-xl">
                        <table id="table-variants" class="w-full table-fixed text-sm">
                            <thead class="text-gray-600 bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="p-3 w-10"><input id="select-all" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500"></th>
                                    <th class="p-3 text-left w-36">SKU / Identif.</th>
                                    <th class="p-3 text-left w-24">Talla</th>
                                    <th class="p-3 text-left w-28">Color</th>
                                    <th class="p-3 text-left w-24">Stock</th>
                                    <th class="p-3 text-left w-24">Costo</th>
                                    <th class="p-3 text-left w-24">Precio</th>
                                    <th class="p-3 text-left w-24">Margen</th>
                                    <th class="p-3 text-left w-28">Imagen</th>
                                    <th class="p-3 text-left w-16">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="variants-body" class="divide-y divide-gray-100"></tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex items-center gap-3">
                        <button type="button" id="btn-delete-selected-2" class="px-4 py-2 rounded-xl border border-rose-300 text-rose-600 bg-rose-50 hover:bg-rose-100 transition hidden text-sm font-medium flex items-center gap-1"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg> Eliminar seleccionadas</button>
                    </div>
                </section>

                <div class="mt-6 p-4 bg-white card sticky bottom-0 z-10 flex justify-end gap-3 border-t border-gray-100">
                    <button type="button" id="btn-cancel" class="px-5 py-2 rounded-xl border border-gray-300 hover:bg-gray-100 transition font-medium">Cancelar</button>
                    <button type="submit" class="px-6 py-2 rounded-xl bg-brand-500 text-white hover:bg-brand-600 transition font-medium flex items-center gap-1"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg> Guardar Producto</button>
                </div>
            </main>

            <aside class="space-y-6 sticky top-12 self-start">
                
                <div class="bg-white card p-6">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Imagen Principal</h3>
                    
                    <label id="file-main-label" class="flex flex-col items-center gap-4 cursor-pointer p-4 border border-dashed border-gray-300 rounded-xl hover:bg-gray-50 transition">
                        <div id="preview-main" class="w-24 h-24 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-gray-400"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-2-6h-2M4 7h16v10a3 3 0 01-3 3H7a3 3 0 01-3-3V7z" /></svg>
                        </div>
                        <span class="text-sm font-medium text-brand-600">
                            Clic para subir imagen
                        </span>
                        <input id="file-main-input" name="imagen"
                                type="file" class="hidden" accept="image/png,image/jpeg,image/webp">
                    </label>
                    <p class="text-xs text-gray-400 mt-2 text-center">
                        Formatos: JPG, PNG, WEBP.
                    </p>
                </div>

                <div class="bg-white card p-6">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Acciones de Variante</h3>
                    <button type="button" id="btn-delete-selected"
                            class="hidden w-full text-sm px-4 py-2 rounded-xl border border-rose-300 text-rose-600 bg-rose-50 hover:bg-rose-100 transition font-medium flex items-center justify-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg> Eliminar seleccionadas
                    </button>
                    <button type="button" id="btn-delete-all"
                            class="w-full text-sm px-4 py-2 mt-3 rounded-xl border border-gray-300 hover:bg-gray-100 transition font-medium flex items-center justify-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg> Eliminar todas las variantes
                    </button>
                    <p class="text-xs text-gray-400 mt-3 text-center">
                        *Solo afecta las filas en esta vista
                    </p>
                </div>
            </aside>

        </form>
    </div>

<script>
/* ---------------------------
    Utilidades & estado
    --------------------------- */
const form = document.getElementById('form-producto');
const nombreInput = document.getElementById('nombre_producto');
const skuPrincipalInput = document.getElementById('sku_principal');
const fileMainInput = document.getElementById('file-main-input');
const fileMainLabel = document.getElementById('file-main-label');
const previewMain = document.getElementById('preview-main');

const variantsBody = document.getElementById('variants-body');
const selectAll = document.getElementById('select-all');
const btnDeleteSelected = document.getElementById('btn-delete-selected');
const btnDeleteAll = document.getElementById('btn-delete-all');
const btnDeleteSelected2 = document.getElementById('btn-delete-selected-2');
const btnAddRow = document.getElementById('btn-add-row');
const msgVariantes = document.getElementById('msg-variantes');
const btnCancel = document.getElementById('btn-cancel');

const cantidadInput = document.getElementById('cantidad');
const cantidadMinInput = document.getElementById('cantidad_min');
const costoInput = document.getElementById('costo');
const precioInput = document.getElementById('precio_unitario');
const margenInput = document.getElementById('margen');
const gananciaInput = document.getElementById('ganancia');


let variantIdx = 0;

/* Helper: escape HTML */
function hEsc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function sanitizeSKU(str) { 
    let s = String(str || '').toUpperCase().trim();
    s = s.replace(/\s+/g, '-'); // spaces -> hyphen
    s = s.replace(/[^A-Z0-9\-]/g, ''); // remove non-alnum/hyphen
    return s;
}

/* ---------------------------
    Preview imagen principal
    --------------------------- */
fileMainInput.addEventListener('change', function(e){
    const f = this.files[0];
    if(!f) {
        previewMain.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-2-6h-2M4 7h16v10a3 3 0 01-3 3H7a3 3 0 01-3-3V7z" /></svg>`;
        fileMainLabel.querySelector('span').textContent = 'Clic para subir imagen';
        return;
    }
    fileMainLabel.querySelector('span').textContent = f.name.length > 25 ? f.name.substring(0, 22) + '...' : f.name;
    const reader = new FileReader();
    reader.onload = function(ev){
        previewMain.innerHTML = `<img src="${ev.target.result}" alt="preview" class="file-thumb">`;
    }
    reader.readAsDataURL(f);
});

/* ---------------------------
    Cálculo de Margen Base
    --------------------------- */
function calcBaseMargin(){
    const c = parseFloat(costoInput.value) || 0;
    const p = parseFloat(precioInput.value) || 0;
    if(c>0 && p>0){
        const g = p - c;
        const m = (g / c) * 100;
        margenInput.value = m.toFixed(1) + '%';
        gananciaInput.value = '$' + g.toFixed(2);
        margenInput.classList.remove('form-input-disabled');
        gananciaInput.classList.remove('form-input-disabled');
    } else {
        margenInput.value = 'N/A';
        gananciaInput.value = 'N/A';
        margenInput.classList.add('form-input-disabled');
        gananciaInput.classList.add('form-input-disabled');
    }
}
costoInput.addEventListener('input', calcBaseMargin);
precioInput.addEventListener('input', calcBaseMargin);
calcBaseMargin(); // Cálculo inicial

/* ---------------------------
    Añadir fila variante
    --------------------------- */
function addVariantRow(type = 'ambas', data = {}) {
    const idx = variantIdx++;
    const sku = hEsc(data.sku || '');
    const talla = hEsc(data.talla || '');
    const color = hEsc(data.color || '');
    const cantidad = (data.cantidad !== undefined) ? data.cantidad : 0;
    const cantidad_min = (data.cantidad_min !== undefined) ? data.cantidad_min : 0;
    const costo = (data.costo !== undefined) ? data.costo : '';
    const precio = (data.precio !== undefined) ? data.precio : '';

    const tallaDisabled = (type === 'color') ? 'disabled' : '';
    const colorDisabled = (type === 'talla') ? 'disabled' : '';

    const tr = document.createElement('tr');
    tr.className = 'bg-white hover:bg-gray-50 transition';
    tr.dataset.idx = idx;
    tr.innerHTML = `
        <td class="p-3 text-center"><input type="checkbox" class="variant-select h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500"></td>
        <td class="p-3">
            <input name="variantes[${idx}][sku]" value="${sku}" class="w-full form-input input-variant-small" placeholder="SKU (opcional)">
        </td>
        <td class="p-3">
            <div class="flex flex-col gap-1 items-start">
                <input name="variantes[${idx}][talla]" value="${talla}" ${tallaDisabled} class="w-20 form-input input-variant-small ${tallaDisabled ? 'form-input-disabled' : ''}" placeholder="Talla">
                ${type==='talla' ? '<span class="badge-disabled mt-1">SOLO TALLA</span>' : ''}
            </div>
        </td>
        <td class="p-3">
            <div class="flex flex-col gap-1 items-start">
                <input name="variantes[${idx}][color]" value="${color}" ${colorDisabled} class="w-24 form-input input-variant-small ${colorDisabled ? 'form-input-disabled' : ''}" placeholder="Color">
                ${type==='color' ? '<span class="badge-disabled mt-1">SOLO COLOR</span>' : ''}
            </div>
        </td>
        <td class="p-3">
            <div class="flex flex-col gap-1">
                <input name="variantes[${idx}][cantidad]" type="number" min="0" value="${cantidad}" class="w-16 form-input input-variant-small text-center" placeholder="Stock">
                <input name="variantes[${idx}][cantidad_min]" type="number" min="0" value="${cantidad_min}" class="w-16 form-input input-variant-small text-center" placeholder="Min">
            </div>
        </td>
        <td class="p-3"><input name="variantes[${idx}][costo]" type="number" step="0.01" min="0.01" value="${costo}" class="w-full form-input input-variant-small" placeholder="0.00"></td>
        <td class="p-3"><input name="variantes[${idx}][precio]" type="number" step="0.01" min="0.01" value="${precio}" class="w-full form-input input-variant-small" placeholder="0.00"></td>
        <td class="p-3"><input disabled class="variant-margin w-full form-input-disabled input-variant-small" value="N/A"></td>
        <td class="p-3">
            <div class="flex items-center gap-2">
                <label class="inline-flex items-center px-2 py-1 border border-gray-300 rounded-lg cursor-pointer bg-white hover:bg-gray-50 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500 mr-1" viewBox="0 0 20 20" fill="currentColor"><path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7z" /><path fill-rule="evenodd" d="M6 5v11a2 2 0 002 2h6a2 2 0 002-2V5H6zm10-4H4a2 2 0 00-2 2v14a2 2 0 002 2h12a2 2 0 002-2V3a2 2 0 00-2-2z" clip-rule="evenodd" /></svg>
                    <span class="text-xs text-gray-600">Subir</span>
                    <input type="file" name="variantes_imagenes[]" accept="image/png,image/jpeg,image/webp" class="hidden variant-file">
                </label>
                <div class="variant-thumb w-8 h-8 bg-gray-100 rounded-md overflow-hidden border border-gray-200 flex-shrink-0"></div>
            </div>
        </td>
        <td class="p-3">
            <button type="button" class="btn-delete-row px-2 py-1 rounded-lg text-sm bg-rose-50 text-rose-600 border border-rose-200 hover:bg-rose-100 transition flex items-center gap-1"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
        </td>
    `;
    variantsBody.appendChild(tr);

    // Attach events
    const costoInputVar = tr.querySelector(`input[name="variantes[${idx}][costo]"]`);
    const precioInputVar = tr.querySelector(`input[name="variantes[${idx}][precio]"]`);
    const skuInput = tr.querySelector(`input[name="variantes[${idx}][sku]"]`);
    const tallaInput = tr.querySelector(`input[name="variantes[${idx}][talla]"]`);
    const colorInput = tr.querySelector(`input[name="variantes[${idx}][color]"]`);
    const fileInput = tr.querySelector('.variant-file');
    const thumb = tr.querySelector('.variant-thumb');
    const selectCheckbox = tr.querySelector('.variant-select');
    const btnDelete = tr.querySelector('.btn-delete-row');
    const marginInput = tr.querySelector('.variant-margin');

    // File preview
    fileInput.addEventListener('change', function(e){
        const f = this.files[0];
        if(!f) { thumb.innerHTML = ''; return; }
        const reader = new FileReader();
        reader.onload = function(ev){
            thumb.innerHTML = `<img src="${ev.target.result}" class="w-full h-full object-cover">`;
        }
        reader.readAsDataURL(f);
    });

    // Margin calc
    function calcMargin(){
        const c = parseFloat(costoInputVar.value) || 0;
        const p = parseFloat(precioInputVar.value) || 0;
        if(c>0 && p>0){
            const g = p - c;
            const m = (g / c) * 100;
            marginInput.value = m.toFixed(1) + '%';
            marginInput.classList.remove('form-input-disabled');
            marginInput.classList.remove('bg-gray-50');
        } else {
            marginInput.value = 'N/A';
            marginInput.classList.add('form-input-disabled');
            marginInput.classList.add('bg-gray-50');
        }
    }
    costoInputVar.addEventListener('input', calcMargin);
    precioInputVar.addEventListener('input', calcMargin);

    // Auto-generate SKU when talla/color change if sku empty
    function tryGenerateSKU(){
        if(skuInput.value.trim() !== '') return;
        const name = nombreInput.value.trim();
        const t = tallaInput && !tallaInput.disabled ? tallaInput.value.trim() : '';
        const c = colorInput && !colorInput.disabled ? colorInput.value.trim() : '';
        
        let parts = [];
        if(name) parts.push(sanitizeSKU(name));
        if(t) parts.push(sanitizeSKU(t));
        if(c) parts.push(sanitizeSKU(c));
        
        let sku = parts.join('-');
        if(!sku) sku = 'VAR-MANUAL-' + Date.now().toString().slice(-6);
        skuInput.value = sku;
    }
    if(tallaInput) tallaInput.addEventListener('input', tryGenerateSKU);
    if(colorInput) colorInput.addEventListener('input', tryGenerateSKU);
    nombreInput.addEventListener('input', tryGenerateSKU);
    skuPrincipalInput.addEventListener('input', tryGenerateSKU);


    // Delete single row
    btnDelete.addEventListener('click', function(){
        Swal.fire({
            title: 'Eliminar variante',
            text: '¿Confirmas eliminar esta variante del formulario?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e15871',
            cancelButtonColor: '#9ca3af',
            confirmButtonText: 'Sí, eliminar'
        }).then(res => {
            if(res.isConfirmed){
                tr.remove();
                updateVariantUIState();
                Swal.fire('Eliminada', 'La variante se ha eliminado del formulario.', 'success');
            }
        });
    });

    // When checkbox toggled, update UI
    selectCheckbox.addEventListener('change', updateVariantUIState);

    // Ensure margin initial calc
    calcMargin();

    updateVariantUIState();
}

/* ---------------------------
    Controladores de Eventos Globales
    --------------------------- */

// Add variant buttons
document.querySelectorAll('.btn-add-variant').forEach(btn => {
    btn.addEventListener('click', function(){
        addVariantRow(this.dataset.type);
    });
});

// Add quick row button
btnAddRow.addEventListener('click', () => addVariantRow('ambas'));

// Delete selected rows
[btnDeleteSelected, btnDeleteSelected2].forEach(btn => {
    btn.addEventListener('click', function(){
        const selected = variantsBody.querySelectorAll('.variant-select:checked');
        if(selected.length === 0) return;

        Swal.fire({
            title: `Eliminar ${selected.length} variantes`,
            text: `¿Estás seguro de eliminar las ${selected.length} variantes seleccionadas?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e15871',
            cancelButtonColor: '#9ca3af',
            confirmButtonText: 'Sí, eliminar'
        }).then(res => {
            if(res.isConfirmed){
                selected.forEach(chk => chk.closest('tr').remove());
                updateVariantUIState();
                Swal.fire('Eliminadas', `Se han eliminado ${selected.length} variantes del formulario.`, 'success');
            }
        });
    });
});

// Delete ALL rows
btnDeleteAll.addEventListener('click', function(){
    if (variantsBody.children.length === 0) {
        Swal.fire({
            title: 'No hay variantes',
            text: 'La tabla de variantes está vacía. No hay nada que eliminar.',
            icon: 'info',
            confirmButtonColor: '#b4c24d'
        });
        return;
    }

    Swal.fire({
        title: `Eliminar todas las variantes`,
        text: `¿Estás seguro de eliminar TODAS las variantes (${variantsBody.children.length}) del formulario?`,
        icon: 'error',
        showCancelButton: true,
        confirmButtonColor: '#e15871',
        cancelButtonColor: '#9ca3af',
        confirmButtonText: 'Sí, eliminar todas'
    }).then(res => {
        if(res.isConfirmed){
            variantsBody.innerHTML = '';
            updateVariantUIState();
            Swal.fire('Éxito', 'Todas las variantes han sido eliminadas.', 'success');
        }
    });
});

// Select All functionality
selectAll.addEventListener('change', function(){
    const checked = this.checked;
    variantsBody.querySelectorAll('.variant-select').forEach(chk => {
        chk.checked = checked;
    });
    updateVariantUIState();
});

// Cancel button
btnCancel.addEventListener('click', function(){
    Swal.fire({
        title: 'Cancelar Registro',
        text: '¿Deseas salir? Los datos no guardados se perderán.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#aaa',
        confirmButtonText: 'Sí, salir'
    }).then(res => {
        if(res.isConfirmed){
            window.location.href = 'index.php?view=productos';
        }
    });
});


/* ---------------------------
    Estado UI: mostrar/ocultar botones y deshabilitar campos base
    --------------------------- */
function updateVariantUIState(){
    const rows = variantsBody.querySelectorAll('tr');
    const any = rows.length > 0;
    msgVariantes.classList.toggle('hidden', !any);

    // show delete selected if any checked
    const selected = variantsBody.querySelectorAll('.variant-select:checked').length;
    btnDeleteSelected.classList.toggle('hidden', !(selected>0));
    btnDeleteSelected2.classList.toggle('hidden', !(selected>0));
    
    // Uncheck selectAll if not all selected
    const total = rows.length;
    selectAll.checked = (total > 0 && selected === total);
    
    // disable base fields if any variant
    const disableBase = any;
    [cantidadInput, cantidadMinInput, costoInput, precioInput, document.getElementById('talla_base'), document.getElementById('color_base')].forEach(el=>{
        el.disabled = disableBase;
        el.classList.toggle('form-input-disabled', disableBase);
        el.classList.toggle('form-input', !disableBase);
    });
    
    // Update base margin state
    margenInput.classList.toggle('form-input-disabled', disableBase);
    gananciaInput.classList.toggle('form-input-disabled', disableBase);

    // Re-check base margin calculation if it's enabled
    if(!disableBase) calcBaseMargin();

    // Re-enable/disable select all based on variants existing
    selectAll.disabled = !any;
}

/* ---------------------------
    Re-popular initial variants if POST had data (PHP Reintegrado)
    --------------------------- */
const initialVariants = <?= json_encode($_POST['variantes'] ?? []) ?>;
if(Array.isArray(initialVariants) && initialVariants.length > 0){
    initialVariants.forEach(v => {
        let t = 'ambas';
        if(v.talla && !v.color) t = 'talla';
        if(v.color && !v.talla) t = 'color';
        addVariantRow(t, v);
    });
}

// Initial state update on page load
document.addEventListener('DOMContentLoaded', () => {
    updateVariantUIState();
    calcBaseMargin();
});

/* ---------------------------
    Submit: client-side validations strengthened
    --------------------------- */
form.addEventListener('submit', function(e){
    // remove previous input-error classes
    document.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));

    let hasError = false;

    // required fields always: nombre, categoria
    if(!nombreInput.value.trim()){
        nombreInput.classList.add('input-error');
        hasError = true;
    }
    if(!document.getElementById('select_categoria').value){
        document.getElementById('select_categoria').classList.add('input-error');
        hasError = true;
    }

    const hasVariants = variantsBody.querySelectorAll('tr').length > 0;

    // if no variants => validate base inventory & price
    if(!hasVariants){
        // cantidad, cantidad_min, costo, precio_unitario
        if(cantidadInput.value === '' || isNaN(parseFloat(cantidadInput.value)) || parseInt(cantidadInput.value) < 0){
            cantidadInput.classList.add('input-error'); hasError = true;
        }
        if(cantidadMinInput.value === '' || isNaN(parseFloat(cantidadMinInput.value)) || parseInt(cantidadMinInput.value) < 0){
            cantidadMinInput.classList.add('input-error'); hasError = true;
        }
        if(costoInput.value === '' || isNaN(parseFloat(costoInput.value)) || parseFloat(costoInput.value) <= 0){
            costoInput.classList.add('input-error'); hasError = true;
        }
        if(precioInput.value === '' || isNaN(parseFloat(precioInput.value)) || parseFloat(precioInput.value) <= 0){
            precioInput.classList.add('input-error'); hasError = true;
        }
        // also ensure cod_barras or sku_principal present
        const codBarras = document.getElementById('cod_barras').value.trim();
        const skuPr = skuPrincipalInput.value.trim();
        if(!codBarras && !skuPr){
            document.getElementById('cod_barras').classList.add('input-error');
            skuPrincipalInput.classList.add('input-error');
            Swal.fire({ title:'Identificación requerida', text:'Para productos simples debes proporcionar Código de Barras o SKU Principal.', icon:'warning', confirmButtonColor:'#b4c24d' });
            e.preventDefault(); return;
        }
    } else {
        // validate each variant row
        const rows = variantsBody.querySelectorAll('tr');
        let badVariant = false;
        rows.forEach(tr => {
            const sku = tr.querySelector(`input[name^="variantes"][name$="[sku]"]`).value.trim();
            const talla = tr.querySelector(`input[name^="variantes"][name$="[talla]"]`) ? tr.querySelector(`input[name^="variantes"][name$="[talla]"]`).value.trim() : '';
            const color = tr.querySelector(`input[name^="variantes"][name$="[color]"]`) ? tr.querySelector(`input[name^="variantes"][name$="[color]"]`).value.trim() : '';
            const c = tr.querySelector(`input[name^="variantes"][name$="[costo]"]`).value;
            const p = tr.querySelector(`input[name^="variantes"][name$="[precio]"]`).value;
            const q = tr.querySelector(`input[name^="variantes"][name$="[cantidad]"]`).value;

            // identification
            const tallaInput = tr.querySelector(`input[name^="variantes"][name$="[talla]"]`);
            const colorInput = tr.querySelector(`input[name^="variantes"][name$="[color]"]`);

            let needsTalla = tallaInput && !tallaInput.disabled;
            let needsColor = colorInput && !colorInput.disabled;

            let rowError = false;

            // Validate that required variant fields (talla/color based on row type) are not empty
            if (needsTalla && talla === '') {
                 tallaInput.classList.add('input-error');
                 rowError = true;
            } else if (tallaInput) {
                tallaInput.classList.remove('input-error');
            }

            if (needsColor && color === '') {
                colorInput.classList.add('input-error');
                rowError = true;
            } else if (colorInput) {
                colorInput.classList.remove('input-error');
            }
            
            // At least one identifier (SKU, Talla, or Color) must be present
            if(sku === '' && talla === '' && color === ''){
                if (tallaInput) tallaInput.classList.add('input-error');
                if (colorInput) colorInput.classList.add('input-error');
                tr.querySelector(`input[name^="variantes"][name$="[sku]"]`).classList.add('input-error');
                rowError = true;
            }

            // numeric validations
            if(c === '' || isNaN(parseFloat(c)) || parseFloat(c) <= 0){
                tr.querySelector(`input[name^="variantes"][name$="[costo]"]`).classList.add('input-error');
                rowError = true;
            }
            if(p === '' || isNaN(parseFloat(p)) || parseFloat(p) <= 0){
                tr.querySelector(`input[name^="variantes"][name$="[precio]"]`).classList.add('input-error');
                rowError = true;
            }
            if(q === '' || isNaN(parseInt(q)) || parseInt(q) < 0){
                tr.querySelector(`input[name^="variantes"][name$="[cantidad]"]`).classList.add('input-error');
                rowError = true;
            }
            
            if (rowError) {
                tr.classList.add('bg-rose-50');
                badVariant = true;
            } else {
                tr.classList.remove('bg-rose-50');
            }
        });
        if(badVariant){
            Swal.fire({ title:'Error en variantes', text:'Revisa las variantes: cada una debe tener la identificación requerida (SKU, Talla, o Color), y costo/precio/cantidad válidos. Las filas con errores están resaltadas.', icon:'warning', confirmButtonColor:'#b4c24d' });
            e.preventDefault(); return;
        }
    }

    if(hasError){
        Swal.fire({ title:'Campos incompletos', text:'Por favor completa los campos obligatorios en rojo.', icon:'error', confirmButtonColor:'#b4c24d' });
        e.preventDefault();
        return;
    }

    // Fill empty SKUs for variants before submit (NOMBRE-TALLA-COLOR)
    if(hasVariants){
        const rows = variantsBody.querySelectorAll('tr');
        rows.forEach(tr => {
            const skuInput = tr.querySelector(`input[name^="variantes"][name$="[sku]"]`);
            if(skuInput.value.trim() === '') {
                // If SKU is empty, force a generated one before submitting
                const name = nombreInput.value.trim();
                const tallaInput = tr.querySelector(`input[name^="variantes"][name$="[talla]"]`);
                const colorInput = tr.querySelector(`input[name^="variantes"][name$="[color]"]`);
                
                const t = tallaInput && !tallaInput.disabled ? tallaInput.value.trim() : '';
                const c = colorInput && !colorInput.disabled ? colorInput.value.trim() : '';
                
                let parts = [];
                if(name) parts.push(sanitizeSKU(name));
                if(t) parts.push(sanitizeSKU(t));
                if(c) parts.push(sanitizeSKU(c));
                
                let sku = parts.join('-');
                if(!sku) sku = 'VAR-MANUAL-' + Date.now().toString().slice(-6);
                skuInput.value = sku;
            }
        });
    }
    
    // Si llegamos aquí, el formulario puede enviarse
    // e.preventDefault(); // Comentar o borrar para permitir el envío real
});

</script>
</body>
</html>