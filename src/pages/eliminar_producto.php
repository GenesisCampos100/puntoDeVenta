<?php
require_once __DIR__ . '/../config/db.php';
header("Content-Type: application/json; charset=utf-8");

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_POST['cod_barras']) || empty($_POST['cod_barras'])) {
    echo json_encode([
        "success" => false,
        "message" => "cod_barras no recibido"
    ]);
    exit;
}

$cod_barras = intval($_POST['cod_barras']);

$sql = "DELETE FROM productos WHERE cod_barras = ?";
$stmt = $conexion->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar la consulta"
    ]);
    exit;
}

$stmt->bind_param("i", $cod_barras);

$ok = $stmt->execute();

echo json_encode([
    "success" => $ok,
    "message" => $ok ? "Producto eliminado" : "Error al eliminar"
]);

$stmt->close();
$conexion->close();
