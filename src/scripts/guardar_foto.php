<?php
session_start();
ini_set('display_errors', 0); // Oculta warnings/notices
error_reporting(0);

header('Content-Type: application/json');

require_once __DIR__ . "../../config/db.php"; // Conexión correcta a la DB

$response = ['success' => false];

try {
    if (!isset($_SESSION['usuario_id'])) {
        throw new Exception('Sesión no iniciada.');
    }

    if (!isset($_FILES['foto'])) {
        throw new Exception('No se envió ninguna imagen.');
    }

    $usuarioId = $_SESSION['usuario_id'];
    $foto = $_FILES['foto'];

    // Carpeta donde se guardarán las fotos
    $carpetaDestino = __DIR__ . "/../../public/uploads/fotos_perfil/";
    if (!file_exists($carpetaDestino)) {
        if (!mkdir($carpetaDestino, 0777, true)) {
            throw new Exception('No se pudo crear la carpeta de destino.');
        }
    }

    // Crear nombre único
    $nombreArchivo = "user_" . $usuarioId . "_" . time() . ".jpg";
    $rutaServidor = $carpetaDestino . $nombreArchivo;
    $rutaWeb = "../public/uploads/fotos_perfil/" . $nombreArchivo; // ruta para el frontend

    if (!move_uploaded_file($foto['tmp_name'], $rutaServidor)) {
        throw new Exception('Error al mover el archivo al servidor.');
    }

    // Guardar ruta en la base de datos
    $sql = "UPDATE usuarios SET imagen = :imagen WHERE id_usuario = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':imagen' => $rutaWeb,
        ':id' => $usuarioId
    ]);

    // Actualizar sesión
    $_SESSION['foto_perfil'] = $rutaWeb;

    $response['success'] = true;
    $response['newPhoto'] = $rutaWeb;

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
exit;
