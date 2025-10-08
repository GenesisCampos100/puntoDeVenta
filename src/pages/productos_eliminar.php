<?php
require_once __DIR__ . "/../config/db.php";

$id = $_GET['id'] ?? 0;
$type = $_GET['type'] ?? '';

if ($id > 0 && ($type === 'producto' || $type === 'variante')) {
    try {
        if ($type === 'producto') {
            // Eliminar variantes asociadas
            $stmt_variantes = $pdo->prepare("DELETE FROM variantes WHERE id_producto = :id");
            $stmt_variantes->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt_variantes->execute();

            // Eliminar producto principal
            $sql = "DELETE FROM productos WHERE id = :id";
        } else {
            $sql = "DELETE FROM variantes WHERE id = :id";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        // ✅ Mostrar animación antes de redirigir
        echo '
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Eliminando...</title>
            <style>
                body {
                    font-family: "Poppins", sans-serif;
                    background: #f9fafb;
                    display: flex;
                    flex-direction: column;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                    margin: 0;
                }
                .loader {
                    border: 6px solid #f3f3f3;
                    border-top: 6px solid #e63946;
                    border-radius: 50%;
                    width: 60px;
                    height: 60px;
                    animation: spin 1s linear infinite;
                }
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                h2 {
                    color: #333;
                    margin-top: 20px;
                    font-weight: 600;
                    font-size: 1.2rem;
                }
                .fade-out {
                    opacity: 0;
                    transition: opacity 0.5s ease;
                }
            </style>
        </head>
        <body>
            <div class="loader" id="loader"></div>
            <h2 id="message">Eliminando producto...</h2>

            <script>
                // Simular animación de carga
                setTimeout(() => {
                    document.getElementById("loader").style.display = "none";
                    document.getElementById("message").innerHTML = "✅ Producto eliminado correctamente";
                    
                    // Redirigir con pequeño retraso
                    setTimeout(() => {
                        window.location.href = "../index.php?view=productos&status=deleted";
                    }, 1200);
                }, 1500);
            </script>
        </body>
        </html>';
        exit();

    } catch (PDOException $e) {
        echo "<h2>Error al eliminar: " . htmlspecialchars($e->getMessage()) . "</h2>";
        exit();
    }
}

echo json_encode([
    'status' => 'error',
    'message' => 'Parámetros inválidos'
]);
exit();
?>
