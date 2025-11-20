<?php
ob_start();
session_start();

// Evitar caching de páginas protegidas para que el botón atrás requiera re-login
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

// Si no hay login, mándalo al login
if (!isset($_SESSION['usuario_id'])) {
    header("Locati n: pages/login.php");
    exit;
}

// Detectar vista
$view = $_GET['view'] ?? 'nueva_venta';

// Detectar si es una petición AJAX para JSON
$action = $_GET['action'] ?? null;

// 🟢 Si viene AJAX como "getCliente", NO cargar layout
if ($view === 'clientes' && $action === 'getCliente') {
    include __DIR__ . "/pages/clientes_contenido.php";
    exit; // 🚀 IMPORTANTE
}

// Rutas válidas
$views = [
    'nueva_venta' => __DIR__ . "/pages/nueva_venta.php",
    'caja' => __DIR__ . "/pages/caja_contenido.php",
    'ventas' => __DIR__ . "/pages/ventas_contenido.php",
    'clientes' => __DIR__ . "/pages/clientes_contenido.php",
    'agregar_cliente' => __DIR__ . "/pages/agregar_cliente.php",
    'editar_cliente' => __DIR__ . "/pages/editar_cliente.php",
    'eliminar_cliente' => __DIR__ . "/pages/eliminar_cliente.php",
    'detalle_cliente' => __DIR__ . "/pages/detalle_cliente.php",
    'empleados' => __DIR__ . "/pages/empleados_contenido.php",
    'productos' => __DIR__ . "/pages/productos_contenido.php",
    'proveedores' => __DIR__ . "/pages/proveedores_contenido.php",
    'reportes' => __DIR__ . "/pages/reportes_contenido.php",
    'agregar_producto' => __DIR__ . "/pages/agregar_producto.php",
    'agregar_empleado' => __DIR__ . "/pages/agregar_empleado.php",
    'eliminar_empleado' => __DIR__ . "/pages/eliminar_empleado.php",
    'editar_empleado' => __DIR__ . "/pages/editar_empleado.php",
    'editar_producto' => __DIR__ . "/pages/editar_producto.php",
    'editar_variante' => __DIR__ . "/pages/editar_variante.php",
    

];

// Si no existe vista → 404
$contenido = $views[$view] ?? __DIR__ . "/pages/404.php";

// Cargar layout normal
include __DIR__ . "/layout.php";

ob_end_flush();
?>
