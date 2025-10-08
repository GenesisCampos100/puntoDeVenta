<?php
// 1. AJUSTE DE RUTA DE CONEXIÓN:
// Desde src/pages/, se sube un nivel (../) para llegar a src/
// y otro nivel (../../) para llegar a la raíz donde está config/.
require_once __DIR__ . "/../config/db.php"; 

// 2. OBTENER Y SANEAR DATOS
$id = $_GET['id'] ?? 0;
$type = $_GET['type'] ?? '';

// 3. VALIDACIÓN Y PROCESAMIENTO
if ($id > 0 && ($type === 'producto' || $type === 'variante')) {
    
    try {
        // Determinar qué tabla usar
        $sql = ($type === 'producto') 
            ? "DELETE FROM productos WHERE id = :id" 
            : "DELETE FROM variantes WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $msg = ($type === 'producto') ? "Producto eliminado exitosamente." : "Variante eliminada exitosamente.";
        
        // 4. REDIRECCIÓN: Volver al index.php (subir un nivel a src/index.php)
        header("Location: ../index.php?view=productos&msg=" . urlencode($msg));
        exit();

    } catch (PDOException $e) {
        $error_msg = "Error al eliminar en la BD. Intente de nuevo.";
        header("Location: ../index.php?view=productos&msg=" . urlencode($error_msg));
        exit();
    }
}

// Redirección por defecto si la acción no es válida
header("Location: ../index.php?view=productos");
exit();
?>