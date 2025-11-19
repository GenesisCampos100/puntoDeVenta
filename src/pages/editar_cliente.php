<?php
// require_once __DIR__ . "/../config/db.php"; // Ya incluido

$id_cliente = $_GET['id'] ?? null;
$mensaje = "";
$cliente = [];

// 1. Procesar el formulario POST (Actualización)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_cliente'])) {
    // ... (Lógica de actualización aquí) ...
    $id = $conexion->real_escape_string($_POST['id_cliente']);
    $nombre = $conexion->real_escape_string($_POST['nombre']);
    // ... (restantes variables)

    $sql = "UPDATE clientes SET 
            nombre='$nombre', 
            apellido_paterno='{$_POST['apellido_paterno']}', 
            apellido_materno='{$_POST['apellido_materno']}', 
            celular='{$_POST['celular']}', 
            correo='{$_POST['correo']}', 
            calle='{$_POST['calle']}', 
            num_ext='{$_POST['num_ext']}', 
            num_int='{$_POST['num_int']}', 
            colonia='{$_POST['colonia']}', 
            cp='{$_POST['cp']}', 
            estado='{$_POST['estado']}'
            WHERE id_cliente='$id'";

    if ($conexion->query($sql) === TRUE) {
        header("Location: index.php?view=clientes_contenido"); 
        exit();
    } else {
        $mensaje = '<div class="p-3 bg-rose-100 text-rose-500 rounded-lg">Error al actualizar: ' . $conexion->error . '</div>';
    }
}

// 2. Obtener datos del cliente (Carga inicial del formulario)
if ($id_cliente) {
    $sql_select = "SELECT * FROM clientes WHERE id_cliente = '{$conexion->real_escape_string($id_cliente)}'";
    $resultado = $conexion->query($sql_select);

    if ($resultado->num_rows == 1) {
        $cliente = $resultado->fetch_assoc();
    } else {
        // Redirige si el cliente no existe
        header("Location: index.php?view=clientes_contenido"); 
        exit();
    }
} else {
    // Redirige si no hay ID
    header("Location: index.php?view=clientes_contenido"); 
    exit();
}
?>

<div class="animate-fade-in max-w-3xl mx-auto">
    <h2 class="text-3xl font-bold text-secondary mb-6 text-center">✏️ Editar Cliente: <?php echo htmlspecialchars($cliente['nombre']); ?></h2>
    
    <?php echo $mensaje; ?>

    <div class="bg-white shadow-2xl rounded-xl p-8">
        <form method="POST" action="index.php?view=editar_cliente">
            <input type="hidden" name="id_cliente" value="<?php echo htmlspecialchars($cliente['id_cliente']); ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="col-span-1 md:col-span-3">
                    <h3 class="text-lg font-semibold border-b pb-2 mb-4 text-primary">Información Personal</h3>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nombre</label>
                    <input type="text" name="nombre" value="<?php echo htmlspecialchars($cliente['nombre']); ?>" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary transition duration-150 p-2 border">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Apellido Paterno</label>
                    <input type="text" name="apellido_paterno" value="<?php echo htmlspecialchars($cliente['apellido_paterno']); ?>" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary transition duration-150 p-2 border">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Apellido Materno</label>
                    <input type="text" name="apellido_materno" value="<?php echo htmlspecialchars($cliente['apellido_materno']); ?>" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary transition duration-150 p-2 border">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="col-span-1 md:col-span-2">
                    <h3 class="text-lg font-semibold border-b pb-2 mb-4 text-primary">Contacto y Dirección</h3>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Teléfono Celular</label>
                    <input type="tel" name="celular" value="<?php echo htmlspecialchars($cliente['celular']); ?>" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary transition duration-150 p-2 border">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Correo Electrónico</label>
                    <input type="email" name="correo" value="<?php echo htmlspecialchars($cliente['correo']); ?>" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary transition duration-150 p-2 border">
                </div>
                
                <div class="col-span-1 md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Calle</label>
                    <input type="text" name="calle" value="<?php echo htmlspecialchars($cliente['calle']); ?>" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary transition duration-150 p-2 border">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Núm. Exterior</label>
                    <input type="text" name="num_ext" value="<?php echo htmlspecialchars($cliente['num_ext']); ?>" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary transition duration-150 p-2 border">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Núm. Interior</label>
                    <input type="text" name="num_int" value="<?php echo htmlspecialchars($cliente['num_int']); ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary transition duration-150 p-2 border">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Colonia</label>
                    <input type="text" name="colonia" value="<?php echo htmlspecialchars($cliente['colonia']); ?>" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary transition duration-150 p-2 border">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">CP</label>
                    <input type="text" name="cp" value="<?php echo htmlspecialchars($cliente['cp']); ?>" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary transition duration-150 p-2 border">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Estado</label>
                    <input type="text" name="estado" value="<?php echo htmlspecialchars($cliente['estado']); ?>" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary transition duration-150 p-2 border">
                </div>
            </div>

            <div class="pt-5 border-t border-gray-200 flex justify-end space-x-4">
                <a href="index.php?view=clientes_contenido" 
                   class="px-5 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition duration-150">
                    Cancelar
                </a>
                <button type="submit" 
                        class="px-5 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-lime-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transform hover:scale-[1.02] transition duration-300">
                    Actualizar Cliente
                </button>
            </div>
        </form>
    </div>
</div>