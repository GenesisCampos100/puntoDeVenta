<?php 
    require_once __DIR__ . "/../config/db.php";

    try {
        $empleado_id = $_GET['id'] ?? null;

        if ($empleado_id) {
            // Eliminar el empleado
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$empleado_id]);

            // Redirigir a la lista de empleados
            header("Location: index.php?view=empleados");
            exit;
        } else {
            echo "No se recibió el ID del empleado.";
        }
    } catch (Exception $e) {
        echo "Error al eliminar empleado: " . $e->getMessage();
    }
?>