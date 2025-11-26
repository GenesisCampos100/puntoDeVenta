<?php
// src/pages/editar_producto_modal.php
// Endpoint para actualización segura de productos desde el modal (sin tocar variantes)

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

try {
    // Obtener datos del POST
    $id = $_POST['id_producto'] ?? null;
    
    if (!$id) {
        throw new Exception("ID de producto no proporcionado");
    }

    // Validar datos obligatorios
    $nombre = trim($_POST['nombre'] ?? '');
    if (empty($nombre)) {
        throw new Exception("El nombre del producto es obligatorio");
    }

    // Recoger otros datos
    $marca = trim($_POST['marca'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $color = trim($_POST['color'] ?? ''); // Color base
    $costo = floatval($_POST['costo'] ?? 0);
    $precio = floatval($_POST['precio_unitario'] ?? 0);
    
    // Iniciar transacción
    $pdo->beginTransaction();

    // 1. Verificar si hay nueva imagen
    $imagenSQL = "";
    $params = [
        $nombre, 
        $marca, 
        $descripcion, 
        $color, 
        $costo, 
        $precio
    ];

    if (!empty($_FILES['imagen']['name'])) {
        $carpetaUploads = __DIR__ . "/../uploads/";
        if (!is_dir($carpetaUploads)) mkdir($carpetaUploads, 0777, true);
        
        $nombreArchivo = uniqid("img_") . "_" . basename($_FILES['imagen']['name']);
        $rutaDestino = $carpetaUploads . $nombreArchivo;
        
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)) {
            $imagenSQL = ", imagen=?";
            $params[] = $nombreArchivo;
        }
    }

    // Agregar ID al final de los parámetros
    $params[] = $id;

    // 2. Actualizar producto (SOLO campos permitidos, NO stock, NO código, NO categoría)
    $sql = "UPDATE productos SET 
            nom_producto=?, 
            marca=?, 
            descripcion=?, 
            color=?, 
            costo=?, 
            precio=? 
            $imagenSQL 
            WHERE cod_barras=?"; // Usamos cod_barras como ID principal según la estructura vista

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    if ($stmt->rowCount() === 0) {
        // Verificar si el producto existe (puede que no se haya modificado nada)
        $check = $pdo->prepare("SELECT cod_barras FROM productos WHERE cod_barras = ?");
        $check->execute([$id]);
        if (!$check->fetch()) {
            throw new Exception("Producto no encontrado");
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true, 
        'message' => 'Producto actualizado correctamente'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}
?>
