<?php
require_once __DIR__ . "/../config/db.php";
?>

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<?php
// ----------------------------------------------------
// 1. Cargar la Variante específica
// ----------------------------------------------------
$id_variante = $_GET['id'] ?? null;
if (!$id_variante) {
    // Redirigir si no hay ID de variante
    echo "<script>window.location='index.php?view=productos';</script>";
    exit;
}

$stmtVar = $pdo->prepare("SELECT * FROM variantes WHERE id_variante = ?");
$stmtVar->execute([$id_variante]);
$variante = $stmtVar->fetch(PDO::FETCH_ASSOC);

if (!$variante) {
    echo "<script>alert('Variante no encontrada'); window.location='index.php?view=productos';</script>";
    exit;
}

// ----------------------------------------------------
// 2. Obtener Producto Padre (para contexto y botón Volver)
// ----------------------------------------------------
// Se asume que variantes.cod_barras contiene el cod_barras del producto padre
$stmtProd = $pdo->prepare("SELECT nom_producto, cod_barras FROM productos WHERE cod_barras = ?");
$stmtProd->execute([$variante['cod_barras']]);
$producto_padre = $stmtProd->fetch(PDO::FETCH_ASSOC);

$producto_padre_nombre = $producto_padre['nom_producto'] ?? 'Producto Padre Desconocido';
$producto_padre_cod_barras = $producto_padre['cod_barras'] ?? 'productos'; // ID para el enlace de regreso

// ----------------------------------------------------
// 3. Procesar Actualización de la Variante
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Los campos bloqueados (cantidad, cantidad_min, costo) se reciben del POST
        // pero se asume que no fueron modificados por el usuario.
        $sku = trim($_POST['sku'] ?? '');
        $talla = trim($_POST['talla'] ?? '');
        $color = trim($_POST['color'] ?? '');
        
        // RECUPERAR VALORES (BLOQUEADOS EN EL FORMULARIO, PERO AÚN SE ENVÍAN)
        $cantidad = (int)($_POST['cantidad'] ?? 0);
        $cantidad_min = (int)($_POST['cantidad_min'] ?? 0);
        $costo = (float)($_POST['costo'] ?? 0);
        
        // PRECIO VENTA (EDITABLE)
        $precio = (float)($_POST['precio'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // 🖼️ Imagen de la Variante
        $imagen = $variante['imagen'];
        
        // Carpeta de carga de imágenes de variantes (dentro de la carpeta uploads base)
        $carpetaUploads = __DIR__ . "/../uploads/variantes/"; 
        if (!is_dir($carpetaUploads)) mkdir($carpetaUploads, 0777, true);

        if (!empty($_FILES['imagen']['name'])) {
            // Generar nombre de archivo único
            $nombreArchivo = uniqid("var_") . "_" . basename($_FILES['imagen']['name']);
            $rutaDestino = $carpetaUploads . $nombreArchivo;
            
            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)) {
                // Opcional: Eliminar imagen anterior si existe
                if ($variante['imagen'] && file_exists($carpetaUploads . $variante['imagen'])) {
                    @unlink($carpetaUploads . $variante['imagen']); 
                }
                $imagen = $nombreArchivo;
            } else {
                 throw new Exception("Error al mover el archivo de imagen de la variante.");
            }
        }

        // 💾 Actualizar variante
        $stmt = $pdo->prepare("UPDATE variantes SET 
            sku=?, talla=?, color=?, imagen=?, cantidad=?, cantidad_min=?, costo=?, precio=?, is_active=?
            WHERE id_variante=?");

        $stmt->execute([
            $sku ?: null,
            $talla ?: null,
            $color ?: null,
            $imagen ?: null, // Permitir NULL si no hay imagen
            $cantidad,
            $cantidad_min,
            $costo,
            $precio, // Precio de venta es editable
            $is_active,
            $id_variante
        ]);
        
        // 🔄 SweetAlert que redirige a la vista principal de productos
        echo "<script>
            Swal.fire({
                title: '¡Actualizado!',
                text: 'La variante ha sido actualizada correctamente.',
                icon: 'success',
                confirmButtonColor: '#b4c24d',
                confirmButtonText: 'Aceptar',
                allowOutsideClick: false
            }).then(() => {
                window.location='index.php?view=productos'; 
            });
        </script>";
        exit;

    } catch (Exception $e) {
        // Mostrar error con SweetAlert
        echo "<script>
            Swal.fire({
                title: 'Error',
                text: '" . addslashes($e->getMessage()) . "',
                icon: 'error',
                confirmButtonColor: '#e15871'
            });
        </script>";
        // Recargar los datos de la variante para que el formulario refleje el estado actual
        $stmtVar->execute([$id_variante]);
        $variante = $stmtVar->fetch(PDO::FETCH_ASSOC);
    }
}
?>

<style>
    :root {
        --primary: #b4c24d;
        --primary-dark: #9fb03d;
        --secondary: #2d4353;
        --accent: #e15871;
    }
    
    body { font-family: 'Poppins', sans-serif; }

    .animate-fadeIn { animation: fadeIn 0.4s ease-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    .input-field {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .input-field:focus {
        box-shadow: 0 0 0 4px rgba(180, 194, 77, 0.1);
        border-color: var(--primary);
    }
    
    /* Readonly styling */
    .input-readonly {
        background-color: #f3f4f6;
        color: #6b7280;
        cursor: not-allowed;
        border-color: #e5e7eb;
    }
</style>

<div class="max-w-4xl mx-auto p-4 md:p-8 pb-32">
    
    <div class="mb-8 flex items-center justify-between animate-fadeIn">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Editar Variante</h1>
            <p class="text-gray-600">Modificando variante del producto: <span class="font-semibold text-[#2d4353]"><?= htmlspecialchars($producto_padre_nombre) ?></span></p>
        </div>
        <button onclick="window.location.href='index.php?view=editar_producto&id=<?= htmlspecialchars($producto_padre_cod_barras) ?>'" class="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-xl font-semibold hover:bg-gray-200 transition-all flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Volver al Producto
        </button>
    </div>

    <form method="post" enctype="multipart/form-data" class="animate-fadeIn" style="animation-delay: 0.1s;">
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#b4c24d]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Imagen de la Variante
                    </h3>
                    
                    <div class="relative group aspect-square bg-gray-100 rounded-xl overflow-hidden mb-4 border-2 border-dashed border-gray-300 hover:border-[#b4c24d] transition-colors">
                        <img id="preview-img" src="<?= $variante['imagen'] ? 'uploads/variantes/'.$variante['imagen'] : 'public/img/sin-imagen.png' ?>" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                            <span class="text-white font-semibold">Cambiar Imagen</span>
                        </div>
                        <input type="file" name="imagen" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer" onchange="previewImage(this)">
                    </div>
                    <p class="text-xs text-gray-500 text-center">Click para subir una nueva imagen (JPG, PNG)</p>
                </div>

                <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#b4c24d]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Estado
                    </h3>
                    <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 cursor-pointer hover:bg-gray-50 transition-colors">
                        <input type="checkbox" name="is_active" class="w-5 h-5 text-[#b4c24d] rounded focus:ring-[#b4c24d]" <?= ($variante['is_active'] ?? 1) ? 'checked' : '' ?>>
                        <span class="font-medium text-gray-700">Variante Activa</span>
                    </label>
                </div>
            </div>

            <div class="lg:col-span-2 space-y-6">
                
                <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 mb-6 flex items-center gap-2 border-b pb-2">
                        <svg class="w-5 h-5 text-[#b4c24d]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                        Identificación
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-bold text-gray-500 mb-2 flex items-center gap-1">
                                ID Variante
                                <span class="text-xs font-normal bg-gray-200 px-2 py-0.5 rounded text-gray-600">Bloqueado</span>
                            </label>
                            <input type="text" value="<?= htmlspecialchars($variante['id_variante']) ?>" readonly
                                   class="input-readonly w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-500 mb-2 flex items-center gap-1">
                                Código de Barras Padre
                                <span class="text-xs font-normal bg-gray-200 px-2 py-0.5 rounded text-gray-600">Bloqueado</span>
                            </label>
                            <input type="text" value="<?= htmlspecialchars($variante['cod_barras']) ?>" readonly
                                   class="input-readonly w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">SKU</label>
                            <input type="text" name="sku" value="<?= htmlspecialchars($variante['sku'] ?? '') ?>"
                                   class="input-field w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none text-gray-900 placeholder-gray-400">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Talla</label>
                            <input type="text" name="talla" value="<?= htmlspecialchars($variante['talla'] ?? '') ?>"
                                   class="input-field w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Color</label>
                            <input type="text" name="color" value="<?= htmlspecialchars($variante['color'] ?? '') ?>"
                                   class="input-field w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none">
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 mb-6 flex items-center gap-2 border-b pb-2">
                        <svg class="w-5 h-5 text-[#b4c24d]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Inventario y Precios
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <div>
                            <label class="block text-sm font-bold text-gray-500 mb-2 flex items-center gap-1">
                                Stock Actual
                                <span class="text-xs font-normal bg-gray-200 px-2 py-0.5 rounded text-gray-600">Bloqueado</span>
                            </label>
                            <input type="number" name="cantidad" value="<?= htmlspecialchars($variante['cantidad']) ?>" required **readonly**
                                   class="input-readonly w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-500 mb-2 flex items-center gap-1">
                                Stock Mínimo
                                <span class="text-xs font-normal bg-gray-200 px-2 py-0.5 rounded text-gray-600">Bloqueado</span>
                            </label>
                            <input type="number" name="cantidad_min" value="<?= htmlspecialchars($variante['cantidad_min']) ?>" required **readonly**
                                   class="input-readonly w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-500 mb-2 flex items-center gap-1">
                                Costo ($)
                                <span class="text-xs font-normal bg-gray-200 px-2 py-0.5 rounded text-gray-600">Bloqueado</span>
                            </label>
                            <input type="number" step="0.01" name="costo" value="<?= htmlspecialchars($variante['costo']) ?>" required **readonly**
                                   class="input-readonly w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Precio Venta ($)</label>
                            <input type="number" step="0.01" name="precio" value="<?= htmlspecialchars($variante['precio']) ?>" required
                                   class="input-field w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none font-bold text-gray-900">
                        </div>

                    </div>
                </div>

                <div class="flex items-center gap-4 pt-4">
                    <button type="button" onclick="window.location.href='index.php?view=productos'" class="flex-1 px-6 py-4 bg-gray-300 text-gray-700 rounded-xl font-bold hover:bg-gray-200 transition-all">
                        Cancelar y Volver a Productos
                    </button>
                    <button type="submit" class="flex-1 px-6 py-4 text-white rounded-xl font-bold shadow-lg hover:shadow-xl hover:scale-[1.02] transition-all" style="background: linear-gradient(135deg, #2d4353 0%, #1e2d38 100%);">
                        Guardar Cambios
                    </button>
                </div>

            </div>
        </div>
    </form>
</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview-img').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>