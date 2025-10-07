<?php
require_once __DIR__ . "/../config/db.php";

// 1. Obtener los IDs necesarios de la URL
$variante_id = $_GET['id'] ?? null;
$producto_id = $_GET['prod_id'] ?? null;

if (!$variante_id || !$producto_id) die("ID de variante o producto principal no especificado.");

// 2. Obtener los datos de la variante
$stmt = $pdo->prepare("SELECT * FROM variantes WHERE id = ? AND id_producto = ?");
$stmt->execute([$variante_id, $producto_id]);
$variante = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$variante) die("Variante no encontrada.");

// 3. Procesar la actualización (si el formulario se envió)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Recoger los datos de la variante
        $cod_barras = trim($_POST['cod_barras'] ?? '');
        $talla = trim($_POST['talla'] ?? '');
        $color = trim($_POST['color'] ?? '');
        $cantidad = (int)($_POST['cantidad'] ?? 0);
        $cantidad_min = (int)($_POST['cantidad_min'] ?? 0);
        $costo = (float)($_POST['costo'] ?? 0);
        $precio_unitario = (float)($_POST['precio_unitario'] ?? 0);

        // Lógica de manejo de imagen (similar a tu otro archivo)
        $imagen = $variante['imagen']; // Valor actual
        if (!empty($_FILES['imagen']['name'])) {
            $carpetaUploads = __DIR__ . "/../uploads/";
            // ... (Lógica de subir el archivo y definir $imagen) ...
        }

        // 💾 Actualizar la variante en la DB
        $stmt = $pdo->prepare("UPDATE variantes SET 
            cod_barras=?, talla=?, color=?, cantidad=?, cantidad_min=?, costo=?, precio_unitario=?, imagen=? 
            WHERE id=? AND id_producto=?");

        $stmt->execute([
            $cod_barras, $talla, $color, $cantidad, $cantidad_min, $costo, $precio_unitario, $imagen, 
            $variante_id, $producto_id
        ]);

        // 🧮 Recalcular la cantidad total del producto principal
        $stmtTotal = $pdo->prepare("SELECT SUM(cantidad) AS total FROM variantes WHERE id_producto = ?");
        $stmtTotal->execute([$producto_id]);
        $totalCantidad = $stmtTotal->fetchColumn();
        
        $pdo->prepare("UPDATE productos SET cantidad=? WHERE id=?")->execute([$totalCantidad, $producto_id]);

        echo "<script>alert('✅ Variante actualizada correctamente'); window.location='index.php?view=productos';</script>";
        exit;

    } catch (Exception $e) {
        die("❌ Error al actualizar: " . $e->getMessage());
    }
}
?>

<div class="variante-form">
    <h2>Editar Variante</h2>
    <form method="post" enctype="multipart/form-data">
        <label>Talla</label>
        <input type="text" name="talla" value="<?= htmlspecialchars($variante['talla']) ?>">
        
        <label>Color</label>
        <input type="text" name="color" value="<?= htmlspecialchars($variante['color']) ?>">
        
        <div class="botones">
            <button type="submit" class="btn-principal">💾 Guardar variante</button>
            <a href="index.php?view=productos" class="btn-cancelar">Cancelar</a>
        </div>
    </form>
</div>

<style>
:root {
  --azul: #2563eb;
  --verde: #22c55e;
  --rojo: #ef4444;
  --gris-claro: #f9fafb;
  --borde: #e5e7eb;
  --texto: #374151;
}

body {
  background: #f3f4f6;
   font-family: 'Poppins', sans-serif; 
}

.producto-form {
  width: 90%;
  max-width: 900px;
  margin: 40px auto;
  background: white;
  padding: 35px 40px;
  border-radius: 16px;
  box-shadow: 0 6px 20px rgba(0,0,0,0.08);
}

h2 {
  text-align: center;
  margin-bottom: 30px;
  color: var(--texto);
  font-weight: 600;
}

section {
  margin-bottom: 25px;
  padding-bottom: 20px;
  border-bottom: 1px solid var(--borde);
}

h3 {
  color: var(--azul);
  font-size: 1.1rem;
  margin-bottom: 12px;
}

.grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 18px;
}

.full { grid-column: 1 / -1; }

label {
  display: block;
  font-weight: 600;
  margin-bottom: 5px;
  color: var(--texto);
}

input, select, textarea {
  width: 100%;
  padding: 8px 10px;
  border: 1px solid var(--borde);
  border-radius: 8px;
  transition: 0.2s;
}

input:focus, select:focus, textarea:focus {
  border-color: var(--azul);
  outline: none;
  box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
}

.variantes {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-top: 10px;
}

.var {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  background: var(--gris-claro);
  padding: 12px;
  border: 1px solid var(--borde);
  border-radius: 10px;
}

.var input {
  flex: 1;
  min-width: 100px;
}

.remove {
  background: var(--rojo);
  color: white;
  border: none;
  border-radius: 8px;
  padding: 8px 10px;
  cursor: pointer;
  transition: 0.2s;
}
.remove:hover { background: #dc2626; }

.btn-principal, .btn-secundario, .btn-cancelar {
  border: none;
  border-radius: 10px;
  padding: 10px 18px;
  cursor: pointer;
  font-weight: 600;
  transition: 0.2s;
}

.btn-principal {
  background: var(--verde);
  color: white;
}

.btn-secundario {
  background: var(--azul);
  color: white;
  margin-top: 10px;
}

.btn-cancelar {
  background: var(--rojo);
  color: white;
  text-decoration: none;
}

.botones {
  margin-top: 25px;
  display: flex;
  justify-content: center;
  gap: 15px;
}

.info {
  font-size: 0.9em;
  color: #6b7280;
}

.disabled {
  opacity: 0.6;
}

.msg-variantes {
  margin-top: 12px;
  background: #fef2f2;
  border: 1px solid #fecaca;
  padding: 10px 14px;
  border-radius: 8px;
  color: #b91c1c;
  font-size: 0.9em;
  display: flex;
  align-items: center;
  gap: 8px;
}
.hidden { display: none; }

/* 🌫️ Estilo visual para campos bloqueados */
input:disabled, select:disabled, textarea:disabled {
  background-color: #e5e7eb !important;
  color: #9ca3af !important;
  cursor: not-allowed;
  opacity: 0.8;
}
</style>
