<?php
session_start();

// Si no hay login, mándalo al login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: pages/login.php");
    exit;
}

// Detectar qué vista quiere cargar el usuario (?view=ventas, ?view=caja, etc.)
$view = $_GET['view'] ?? 'nueva_venta'; // por defecto carga "caja"

// Definir rutas válidas
$views = [
    'nueva venta' => __DIR__ . "/pages/nueva_venta.php",
    'caja' => __DIR__ . "/pages/caja_contenido.php",
    'ventas' => __DIR__ . "/pages/ventas_contenido.php",
    'clientes' => __DIR__ . "/pages/clientes_contenido.php",
    'empleados' => __DIR__ . "/pages/empleados_contenido.php",
    'productos' => __DIR__ . "/pages/productos_contenido.php",
    'proveedores' => __DIR__ . "/pages/proveedores_contenido.php",
    'reportes' => __DIR__ . "/pages/reportes_contenido.php",
    'agregar_producto' => __DIR__ . "/pages/agregar_producto.php",
    'agregar_empleado' => __DIR__ . "/pages/agregar_empleado.php",
];   

// Si la vista no existe, mostrar error 404
if (!array_key_exists($view, $views)) {
    $contenido = __DIR__ . "/pages/404.php"; // crea un archivo sencillo
} else {
    $contenido = $views[$view];
}

// Incluir el layout (el que ya tienes)
include __DIR__ . "/layout.php";

// El archivo contenido contendrá el contenido específico de la página seleccionada
?>
