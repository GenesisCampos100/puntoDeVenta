<?php
$permisos = [
    "super_admin" => ["ventas", "productos", "proveedores", "caja", "reportes", "clientes", "empleados"],
    "gerente" => ["ventas", "productos", "proveedores", "caja", "reportes", "clientes"],
    "cajero" => ["ventas", "productos", "proveedores", "caja", "reportes", "clientes"]
];

// Ejemplo de validación
function tienePermiso($modulo) {
    global $permisos;
    $rol = $_SESSION['rol'] ?? null;
    return $rol && in_array($modulo, $permisos[$rol]);
}
