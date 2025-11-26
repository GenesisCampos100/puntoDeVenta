<?php
require_once __DIR__ . '/../config/db.php';

// Revert role names to original values (super_admin, gerente, cajero)
try {
    $pdo->beginTransaction();
    // id_rol 1 -> super_admin
    $stmt = $pdo->prepare("UPDATE roles SET nombre_rol = 'super_admin' WHERE id_rol = 1");
    $stmt->execute();
    // id_rol 2 -> gerente
    $stmt = $pdo->prepare("UPDATE roles SET nombre_rol = 'gerente' WHERE id_rol = 2");
    $stmt->execute();
    // id_rol 3 -> cajero
    $stmt = $pdo->prepare("UPDATE roles SET nombre_rol = 'cajero' WHERE id_rol = 3");
    $stmt->execute();
    $pdo->commit();
    echo "<h2>Roles revertidos correctamente</h2>";
    echo "<p>Ahora los nombres son: super_admin, gerente, cajero.</p>";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "<h2>Error al revertir roles</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
