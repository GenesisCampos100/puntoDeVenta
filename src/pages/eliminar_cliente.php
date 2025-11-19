<?php
require_once __DIR__ . "/../config/db.php"; 

$id_cliente = $_GET['id'] ?? null;

if ($id_cliente) {
    $id = $conexion->real_escape_string($id_cliente);

    $sql = "DELETE FROM clientes WHERE id_cliente = '$id'";

    if ($conexion->query($sql) === TRUE) {
        // Redirige al listado principal con un mensaje de éxito (si es implementado en clientes_contenido)
        header("Location: index.php?view=clientes_contenido&status=deleted"); 
        exit();
    } else {
        // En un POS real, aquí manejarías el error (p. ej., si el cliente tiene ventas asociadas)
        echo "<div class='p-8 bg-rose-100 text-rose-500 rounded-lg max-w-lg mx-auto mt-10'>Error al eliminar el cliente: " . $conexion->error . "</div>";
        echo '<div class="text-center mt-4"><a href="index.php?view=clientes_contenido" class="px-4 py-2 bg-gray-200 rounded-md">Volver</a></div>';
    }
} else {
    header("Location: index.php?view=clientes_contenido"); 
    exit();
}

$conexion->close();
?>