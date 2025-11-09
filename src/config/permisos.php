<?php
$permisos = [
    "Admin" => ["nueva venta", "ventas", "productos", "proveedores", "caja", "reportes", "clientes", "empleados"],
    "Gerente" => ["nueva venta","ventas", "productos", "proveedores", "caja", "reportes", "clientes"],
    "Cajero" => ["nueva venta", "ventas", "productos", "proveedores", "caja", "reportes", "clientes"]
];

// Ejemplo de validación
function tienePermiso($modulo) {
    global $permisos;
    $rol = $_SESSION['rol'] ?? null;
    return $rol && in_array($modulo, $permisos[$rol]);
}
