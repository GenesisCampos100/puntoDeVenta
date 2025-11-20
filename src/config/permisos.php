<?php
$permisos = [
    "super_admin" => ["nueva venta", "ventas", "productos", "proveedores", "caja", "reportes", "clientes", "empleados"],
    // Compatibilidad con nombres de rol distintos en la BD
    "Admin" => ["nueva venta", "ventas", "productos", "proveedores", "caja", "reportes", "clientes", "empleados"],
    "Gerente" => ["nueva venta","ventas", "productos", "proveedores", "caja", "reportes", "clientes"],
    "Cajero" => ["nueva venta", "ventas", "productos", "proveedores", "caja", "reportes", "clientes"],
    "gerente" => ["nueva venta","ventas", "productos", "proveedores", "caja", "reportes", "clientes"],
    "cajero" => ["nueva venta", "ventas", "productos", "proveedores", "caja", "reportes", "clientes"]
];

// Ejemplo de validación
function tienePermiso($modulo) {
    global $permisos;
    $rol = $_SESSION['rol'] ?? null;
    return $rol && in_array($modulo, $permisos[$rol]);
}
