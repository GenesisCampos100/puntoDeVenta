<?php
require_once __DIR__ . '/../config/translation.php';
require_once __DIR__ . "/../config/db.php";

// 📦 Cargar categorías
$stmt = $pdo->query("SELECT id_categoria, nombre FROM categorias ORDER BY nombre ASC");
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 🧾 Obtener producto principal
$id = $_GET['id'] ?? null;
if (!$id) {
    echo "<script>window.location='index.php?view=productos';</script>";
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM productos WHERE cod_barras = ?");
$stmt->execute([$id]);
$producto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$producto) {
    echo "<script>alert('Producto no encontrado'); window.location='index.php?view=productos';</script>";
    exit;
}

// 🧩 Obtener variantes (si las hay)
$stmtVar = $pdo->prepare("SELECT * FROM variantes WHERE cod_barras = ?");
$stmtVar->execute([$producto['cod_barras']]);
$variantes = $stmtVar->fetchAll(PDO::FETCH_ASSOC);

?>

<!-- Dependencias (CARGADAS PRIMERO) -->
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<?php
// 🧾 Actualizar producto
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $nombre = trim($_POST['nombre']);
    $marca = trim($_POST['marca']);
    $descripcion = trim($_POST['descripcion']);
    $color_base = trim($_POST['color_base']);
    $precio_unitario = (float)$_POST['precio_unitario'];
    $id_categoria = $_POST['id_categoria']; // Permitido editar
    $is_active = isset($_POST['is_active']) ? 1 : 0;

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

    // 💾 Actualizar producto base (SOLO CAMPOS PERMITIDOS)
    // NO actualizamos: cod_barras, cantidad, cantidad_min, costo, tipo_costo
    $stmt = $pdo->prepare("UPDATE productos SET 
      nom_producto=?, marca=?, descripcion=?, color=?, imagen=?, 
      precio=?, id_categoria=?, is_active=?
      WHERE cod_barras=?");

    $stmt->execute([
      $nombre, $marca, $descripcion, $color_base, $imagen, 
      $precio_unitario, $id_categoria, $is_active, $producto['cod_barras']
    ]);

    echo "<script>
        Swal.fire({
            title: '¡Actualizado!',
            text: 'El producto ha sido actualizado correctamente',
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
      echo "<script>
          Swal.fire({
              title: 'Error',
              text: '" . addslashes($e->getMessage()) . "',
              icon: 'error',
              confirmButtonColor: '#e15871'
          });
      </script>";
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

<div class="max-w-5xl mx-auto p-4 md:p-8 pb-32">
    
    <!-- Header -->
    <div class="mb-8 flex items-center justify-between animate-fadeIn">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Editar Producto</h1>
            <p class="text-gray-600">Modifica los detalles del producto. Los campos de inventario están protegidos.</p>
        </div>
        <button onclick="window.location.href='index.php?view=productos'" class="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-xl font-semibold hover:bg-gray-200 transition-all flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Volver
        </button>
    </div>

    <form method="post" enctype="multipart/form-data" class="animate-fadeIn" style="animation-delay: 0.1s;">
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Columna Izquierda: Imagen y Estado -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Tarjeta Imagen -->
                <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#b4c24d]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Imagen del Producto
                    </h3>
                    
                    <div class="relative group aspect-square bg-gray-100 rounded-xl overflow-hidden mb-4 border-2 border-dashed border-gray-300 hover:border-[#b4c24d] transition-colors">
                        <img id="preview-img" src="<?= $producto['imagen'] ? 'uploads/'.$producto['imagen'] : 'public/img/sin-imagen.png' ?>" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                            <span class="text-white font-semibold">Cambiar Imagen</span>
                        </div>
                        <input type="file" name="imagen" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer" onchange="previewImage(this)">
                    </div>
                    <p class="text-xs text-gray-500 text-center">Click para subir una nueva imagen (JPG, PNG)</p>
                </div>

                <!-- Tarjeta Estado -->
                <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#b4c24d]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Estado
                    </h3>
                    <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 cursor-pointer hover:bg-gray-50 transition-colors">
                        <input type="checkbox" name="is_active" class="w-5 h-5 text-[#b4c24d] rounded focus:ring-[#b4c24d]" <?= ($producto['is_active'] ?? 1) ? 'checked' : '' ?>>
                        <span class="font-medium text-gray-700">Producto Activo</span>
                    </label>
                </div>
            </div>

            <!-- Columna Derecha: Formulario -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Información General -->
                <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 mb-6 flex items-center gap-2 border-b pb-2">
                        <svg class="w-5 h-5 text-[#b4c24d]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Información General
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Nombre (Editable) -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Nombre del Producto</label>
                            <input type="text" name="nombre" value="<?= htmlspecialchars($producto['nom_producto']) ?>" required
                                   class="input-field w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none text-gray-900 placeholder-gray-400">
                        </div>

                        <!-- Código de Barras (Readonly) -->
                        <div>
                            <label class="block text-sm font-bold text-gray-500 mb-2 flex items-center gap-1">
                                Código de Barras
                                <span class="text-xs font-normal bg-gray-200 px-2 py-0.5 rounded text-gray-600">Bloqueado</span>
                            </label>
                            <input type="text" value="<?= htmlspecialchars($producto['cod_barras']) ?>" readonly
                                   class="input-readonly w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none">
                        </div>

                        <!-- Categoría (Editable) -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Categoría</label>
                            <select name="id_categoria" required class="input-field w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none bg-white">
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?= $cat['id_categoria'] ?>" <?= $cat['id_categoria'] == $producto['id_categoria'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Marca (Editable) -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Marca</label>
                            <input type="text" name="marca" value="<?= htmlspecialchars($producto['marca']) ?>"
                                   class="input-field w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none">
                        </div>

                        <!-- Color (Editable) -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Color</label>
                            <input type="text" name="color_base" value="<?= htmlspecialchars($producto['color']) ?>"
                                   class="input-field w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none">
                        </div>

                        <!-- Descripción (Editable) -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Descripción</label>
                            <textarea name="descripcion" rows="3" class="input-field w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none"><?= htmlspecialchars($producto['descripcion']) ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Inventario y Precios -->
                <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 mb-6 flex items-center gap-2 border-b pb-2">
                        <svg class="w-5 h-5 text-[#b4c24d]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Inventario y Precios
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <!-- Stock Actual (Readonly) -->
                        <div>
                            <label class="block text-sm font-bold text-gray-500 mb-2 flex items-center gap-1">
                                Stock Actual
                                <span class="text-xs font-normal bg-gray-200 px-2 py-0.5 rounded text-gray-600">Bloqueado</span>
                            </label>
                            <input type="number" value="<?= htmlspecialchars($producto['cantidad']) ?>" readonly
                                   class="input-readonly w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none">
                        </div>

                        <!-- Stock Mínimo (Readonly) -->
                        <div>
                            <label class="block text-sm font-bold text-gray-500 mb-2 flex items-center gap-1">
                                Stock Mínimo
                                <span class="text-xs font-normal bg-gray-200 px-2 py-0.5 rounded text-gray-600">Bloqueado</span>
                            </label>
                            <input type="number" value="<?= htmlspecialchars($producto['cantidad_min']) ?>" readonly
                                   class="input-readonly w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none">
                        </div>

                        <!-- Costo (Readonly) -->
                        <div>
                            <label class="block text-sm font-bold text-gray-500 mb-2 flex items-center gap-1">
                                Costo ($)
                                <span class="text-xs font-normal bg-gray-200 px-2 py-0.5 rounded text-gray-600">Bloqueado</span>
                            </label>
                            <input type="number" value="<?= htmlspecialchars($producto['costo']) ?>" readonly
                                   class="input-readonly w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none">
                        </div>

                        <!-- Precio Venta (Editable) -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Precio Venta ($)</label>
                            <input type="number" step="0.01" name="precio_unitario" value="<?= htmlspecialchars($producto['precio']) ?>" required
                                   class="input-field w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none font-bold text-gray-900">
                        </div>

                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="flex items-center gap-4 pt-4">
                    <button type="button" onclick="window.location.href='index.php?view=productos'" class="flex-1 px-6 py-4 bg-gray-300 text-gray-700 rounded-xl font-bold hover:bg-gray-200 transition-all">
                        Cancelar
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
