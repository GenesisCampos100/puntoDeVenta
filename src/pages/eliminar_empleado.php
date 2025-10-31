<?php 
    require_once __DIR__ . "/../config/db.php";

    try {
        $empleado_id = $_GET['id'] ?? null;

        if ($empleado_id === false || $empleado_id <= 0) {
            // ID inválido: mostramos mensaje y detenemos ejecución
            echo "ID de empleado inválido.";
            exit;
        }

        // --- OPCIONAL: Verificar que el empleado exista antes de eliminar ---
        $stmt_check = $pdo->prepare("SELECT id_empleado FROM empleados WHERE id_empleado = ?");
        $stmt_check->execute([$empleado_id]);
        if ($stmt_check->rowCount() === 0) {
            echo "No se encontró el empleado con ese ID.";
            exit;
        }

        // Iniciar transacción
        $pdo->beginTransaction();

        // Eliminar de usuarios
        $stmt1 = $pdo->prepare("DELETE FROM usuarios WHERE id_empleado = ?");
        $stmt1->execute([$empleado_id]);

        // Eliminar de empleados
        $stmt2 = $pdo->prepare("DELETE FROM empleados WHERE id_empleado = ?");
        $stmt2->execute([$empleado_id]);

        // Confirmar cambios
        $pdo->commit();

        // Redirigir a la lista de empleados
        header("Location: index.php?view=empleados");
        exit;
    } catch (Exception $e) {
        // Revertir cambios si hubo error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        echo "Error al eliminar empleado. Detalles registrados en el log.";
        error_log("Error al eliminar empleado ID $empleado_id: " . $e->getMessage());
    }
?>