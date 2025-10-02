<?php
$permisos = [
    "super_admin" => ["nueva venta", "ventas", "productos", "proveedores", "caja", "reportes", "clientes", "empleados"],
    "gerente" => ["nueva venta","ventas", "productos", "proveedores", "caja", "reportes", "clientes"],
    "cajero" => ["nueva venta", "ventas", "productos", "proveedores", "caja", "reportes", "clientes"]
];

// Ejemplo de validación
function tienePermiso($modulo) {
    global $permisos;
    $rol = $_SESSION['rol'] ?? null;
    return $rol && in_array($modulo, $permisos[$rol]);
}
