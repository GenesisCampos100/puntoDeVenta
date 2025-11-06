<?php
require_once __DIR__ . '/../config/db.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

// Este endpoint verifica la contraseña del usuario autenticado en sesión (quien autoriza la acción).
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$password = $_POST['password'] ?? null;

if (!$password) {
    echo json_encode(['error' => 'Parámetros incompletos']);
    exit;
}

// Verificar que exista una sesión y obtener el id del usuario autenticado
if (empty($_SESSION['usuario_id'])) {
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

$id_usuario = $_SESSION['usuario_id'];

try {
    $stmt = $pdo->prepare("SELECT contrasena FROM usuarios WHERE id_usuario = :id_usuario");
    $stmt->execute(['id_usuario' => $id_usuario]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['error' => 'Usuario no encontrado en sesión']);
        exit;
    }

    $hash = $row['contrasena'];
    if (password_verify($password, $hash)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Contraseña incorrecta']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error en BD: ' . $e->getMessage()]);
}
