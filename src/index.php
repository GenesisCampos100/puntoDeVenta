<?php
// 🚀 Iniciar salida antes de cualquier texto
ob_start();
session_start();

// Evitar caching de páginas protegidas para que el botón atrás requiera re-login
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

// Si no hay login, mándalo al login
if (!isset($_SESSION['usuario_id'])) {
    // ⚠️ Asegurar que no haya espacios o salida antes del header
    header("Location: pages/login.php");
    exit;
}

// Detectar vista
$view = $_GET['view'] ?? 'nueva_venta';

// Detectar si es una petición AJAX para JSON
$action = $_GET['action'] ?? null;

// 🟢 Si viene AJAX para Clientes (get, search, delete), NO cargar layout
if ($view === 'clientes' && in_array($action, ['getCliente', 'search', 'delete', 'deleteMultiple'])) {
    include __DIR__ . "/pages/clientes_contenido.php";
    exit; // 🚀 IMPORTANTE
}

// 🟢 Si viene AJAX como "getEmpleado", NO cargar layout
if ($view === 'empleados' && $action === 'getEmpleado') {
    include __DIR__ . "/pages/empleados_contenido.php";
    exit;
}

// 🟢 Lógica de eliminación (sin layout)
if ($view === 'eliminar_empleado' || $view === 'eliminar_empleados_multiple' || $view === 'eliminar_cliente' || $view === 'eliminar_clientes_multiple') {
    include __DIR__ . "/pages/" . $view . ".php";
    exit;
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
    'eliminar_empleados_multiple' => __DIR__ . "/pages/eliminar_empleados_multiple.php",
];

// 🔐 Si la vista no existe, mostrar 404
$contenido = array_key_exists($view, $views)
    ? $views[$view]
    : __DIR__ . "/pages/404.php";
    
// Si es una petición AJAX (XMLHttpRequest) y viene por POST, incluir
// directamente la vista para que los endpoints que devuelven JSON
// no sean envueltos por el layout (evita HTML antes del JSON)
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) {
    if (file_exists($contenido)) {
        include $contenido;
        exit;
    }
}

// ✅ Incluir el layout (NO debe imprimir antes del header)
include __DIR__ . "/layout.php";

ob_end_flush();
?>
