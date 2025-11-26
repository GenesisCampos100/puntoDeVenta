<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

try {
    // Obtener usuarios con datos crudos
    $stmt = $pdo->query("SELECT id_usuario, correo, contrasena, id_empleado FROM usuarios");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $analysis = [];

    foreach ($users as $u) {
        $pass = $u['contrasena'];
        $len = strlen($pass);
        $isHash = (substr($pass, 0, 4) === '$2y$');
        
        // Verificar si coincide con 'cajero123'
        $matchHash = password_verify('cajero123', $pass);
        $matchPlain = ($pass === 'cajero123');
        $matchPlainTrim = (trim($pass) === 'cajero123');

        $analysis[] = [
            'id' => $u['id_usuario'],
            'correo' => $u['correo'],
            'pass_len' => $len,
            'is_hash_format' => $isHash,
            'match_cajero123_hash' => $matchHash,
            'match_cajero123_plain' => $matchPlain,
            'match_cajero123_trim' => $matchPlainTrim,
            'pass_preview' => substr($pass, 0, 10) . '...'
        ];
    }

    echo json_encode(['success' => true, 'data' => $analysis], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
