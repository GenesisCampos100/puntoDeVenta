<?php
// Script idempotente para asegurar columnas en user_profiles y migrar datos
// Ejecutar desde CLI: php migrate_user_profiles.php
// O por navegador (temporal) accediendo a /src/scripts/migrate_user_profiles.php

require __DIR__ . '/../config/db.php';

try {
    echo "Iniciando migración de user_profiles...\n";

    // 1) Asegurar que la tabla exista con las columnas necesarias
    $createProfiles = "CREATE TABLE IF NOT EXISTS user_profiles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        nombre_completo VARCHAR(255) DEFAULT NULL,
        nombre VARCHAR(150) DEFAULT NULL,
        apellido VARCHAR(150) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_user (user_id),
        FOREIGN KEY (user_id) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $pdo->exec($createProfiles);
    echo "Tabla user_profiles creada/confirmada.\n";

    // 2) Detectar columnas faltantes y añadirlas
    $needed = [
        'nombre_completo' => "VARCHAR(255) DEFAULT NULL",
        'nombre' => "VARCHAR(150) DEFAULT NULL",
        'apellido' => "VARCHAR(150) DEFAULT NULL",
    ];

    $missing = [];
    $sth = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_profiles'");
    $sth->execute();
    $cols = $sth->fetchAll(PDO::FETCH_COLUMN, 0);
    foreach ($needed as $col => $def) {
        if (!in_array($col, $cols)) {
            $missing[$col] = $def;
        }
    }

    if ($missing) {
        foreach ($missing as $col => $def) {
            $sql = "ALTER TABLE user_profiles ADD COLUMN $col $def";
            $pdo->exec($sql);
            echo "Columna $col añadida.\n";
        }
    } else {
        echo "No hay columnas faltantes.\n";
    }

    // 3) Migrar datos existentes: si nombre_completo vacío y nombre tiene el full name, copiar
    $pdo->beginTransaction();

    // a) Llenar nombre_completo con el valor actual de nombre cuando esté vacío
    $update1 = $pdo->prepare("UPDATE user_profiles SET nombre_completo = nombre WHERE (nombre_completo IS NULL OR nombre_completo = '') AND (nombre IS NOT NULL AND nombre != '')");
    $update1->execute();
    echo "Copiados valores de 'nombre' a 'nombre_completo' donde era necesario. Filas afectadas: " . $update1->rowCount() . "\n";

    // b) Para cada fila con nombre_completo no vacío, aplicar split y poblar nombre / apellido si están vacíos
    $sel = $pdo->query("SELECT id, user_id, nombre_completo, nombre, apellido FROM user_profiles");
    $rows = $sel->fetchAll(PDO::FETCH_ASSOC);
    $updated = 0;
    foreach ($rows as $r) {
        $id = $r['id'];
        $full = trim($r['nombre_completo'] ?? '');
        if ($full === '') continue;

        $first = $r['nombre'];
        $last = $r['apellido'];

        // Only set if empty
        if (empty($first) || empty($last)) {
            $parts = preg_split('/\s+/', $full);
            $f = $parts[0] ?? '';
            $l = '';
            if (count($parts) > 1) $l = implode(' ', array_slice($parts, 1));

            $upd = $pdo->prepare("UPDATE user_profiles SET nombre = ?, apellido = ? WHERE id = ?");
            $upd->execute([$f, $l, $id]);
            $updated += $upd->rowCount();
        }
    }

    $pdo->commit();
    echo "Split realizado y nombre/apellido poblados cuando estaban vacíos. Filas actualizadas: $updated\n";

    echo "Migración completada con éxito.\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "Error durante la migración: " . $e->getMessage() . "\n";
    exit(1);
}

// Nota: ejecutar este script una sola vez en entorno local/backup de BD; es idempotente y seguro para re-ejecuciones.

?>
