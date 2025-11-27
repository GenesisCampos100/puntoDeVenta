<?php
require_once __DIR__ . '/../config/db.php';

$proveedor_id = $_GET['proveedor'] ?? '';
$orden_get    = $_GET['orden'] ?? 'nom_asc';

$mapOrder = [
    'nom_asc'    => 'p.nom_producto ASC',
    'nom_desc'   => 'p.nom_producto DESC',
    'precio_asc' => 'p.precio ASC',
    'precio_desc'=> 'p.precio DESC'
];

// Normalizar y validar el parámetro de orden para evitar inyección SQL
$orden_get_trim = trim((string)$orden_get);
$orden_sql = $mapOrder['nom_asc'];
if (isset($mapOrder[$orden_get_trim])) {
    $orden_sql = $mapOrder[$orden_get_trim];
} else {
    if (in_array($orden_get_trim, $mapOrder, true)) {
        $orden_sql = $orden_get_trim;
    } else {
        $low = strtolower($orden_get_trim);
        if (strpos($low, 'precio') !== false) {
            $orden_sql = (strpos($low, 'desc') !== false) ? $mapOrder['precio_desc'] : $mapOrder['precio_asc'];
        } elseif (strpos($low, 'nom') !== false || strpos($low, 'producto') !== false || strpos($low, 'nombre') !== false) {
            $orden_sql = (strpos($low, 'desc') !== false) ? $mapOrder['nom_desc'] : $mapOrder['nom_asc'];
        }
    }
}

if (empty($proveedor_id)) {
    echo "<p style='padding:20px;font-family:sans-serif;'>ID de proveedor no proporcionado.</p>";
    exit;
}

// Obtener datos del proveedor
$stmtP = $pdo->prepare("SELECT id_proveedor, empresa, nombre, apellido_paterno, apellido_materno FROM proveedores WHERE id_proveedor = ? LIMIT 1");
$stmtP->execute([$proveedor_id]);
$prov = $stmtP->fetch(PDO::FETCH_ASSOC);

if (!$prov) {
    echo "<p style='padding:20px;font-family:sans-serif;'>Proveedor no encontrado.</p>";
    exit;
}

$proveedor_nombre = trim(($prov['nombre'] ?? '') . ' ' . ($prov['apellido_paterno'] ?? '') . ' ' . ($prov['apellido_materno'] ?? ''));
if (empty($proveedor_nombre)) $proveedor_nombre = $prov['empresa'] ?? 'Proveedor';

// Obtener productos del proveedor
$sql = "
    SELECT
        p.cod_barras AS id_producto,
        p.nom_producto,
        p.imagen,
        p.marca,
        p.descripcion,
        c.nombre AS categoria,
        p.cantidad,
        p.precio
    FROM productos p
    LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
    WHERE p.id_proveedor = :proveedor
    ORDER BY $orden_sql
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':proveedor' => $proveedor_id]);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    /* ==========================================
       CATÁLOGO PROVEEDOR - DISEÑO MODERNO
       ========================================== */
    
    .catalogo-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem;
        font-family: 'Montserrat', 'Open Sans', sans-serif;
    }

    /* Header del catálogo - Sin fondo, solo tipografía elegante */
    .catalogo-header {
        background: transparent;
        padding: 2rem 0;
        margin-top: 1rem;
        margin-bottom: 2rem;
        border-bottom: 2px solid #e2e8f0;
    }

    .catalogo-header h1 {
        font-size: 2.5rem;
        font-weight: 700;
        margin: 0 0 0.5rem 0;
        background: linear-gradient(135deg, #2d3748 0%, #4a5568 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        letter-spacing: -0.02em;
    }

    .catalogo-header p {
        font-size: 1rem;
        font-weight: 500;
        color: #718096;
        margin: 0;
    }

    /* Barra de controles */
    .catalogo-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .catalogo-select {
        padding: 0.75rem 1rem;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 0.95rem;
        font-weight: 500;
        background: white;
        cursor: pointer;
        transition: all 0.3s ease;
        outline: none;
    }

    .catalogo-select:hover {
        border-color: #4a5568;
    }

    .catalogo-select:focus {
        border-color: #4a5568;
        box-shadow: 0 0 0 3px rgba(74, 85, 104, 0.1);
    }

    .btn-volver {
        padding: 0.75rem 1.5rem;
        background: #f7fafc;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-weight: 600;
        color: #2d3748;
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-volver:hover {
        background: white;
        border-color: #cbd5e0;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    /* Tarjeta de la tabla */
    .catalogo-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }

    /* Tabla moderna */
    .catalogo-table {
        width: 100%;
        border-collapse: collapse;
    }

    .catalogo-table thead {
        background: linear-gradient(to right, #f7fafc, #edf2f7);
    }

    .catalogo-table th {
        padding: 1rem 1.5rem;
        text-align: left;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #4a5568;
        border-bottom: 2px solid #e2e8f0;
    }

    .catalogo-table th.text-center {
        text-align: center;
    }

    .catalogo-table th.text-right {
        text-align: right;
    }

    .catalogo-table tbody tr {
        border-bottom: 1px solid #f1f5f9;
        transition: all 0.2s ease;
    }

    .catalogo-table tbody tr:hover {
        background: linear-gradient(to right, #f8fafc, #f1f5f9);
        transform: scale(1.01);
    }

    .catalogo-table td {
        padding: 1.25rem 1.5rem;
        color: #2d3748;
    }

    /* Celda de producto con imagen */
    .producto-cell {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .producto-img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 10px;
        border: 2px solid #e2e8f0;
    }

    .producto-placeholder {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        color: #cbd5e0;
        border: 2px solid #e2e8f0;
        font-weight: 600;
    }

    .producto-info {
        flex: 1;
    }

    .producto-nombre {
        font-weight: 600;
        font-size: 1rem;
        color: #1a202c;
        margin: 0 0 0.25rem 0;
    }

    .producto-marca {
        font-size: 0.85rem;
        color: #718096;
    }

    /* Badge de categoría */
    .categoria-badge {
        display: inline-block;
        padding: 0.4rem 0.9rem;
        background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);
        color: white;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }

    /* Stock badge */
    .stock-badge {
        display: inline-block;
        padding: 0.4rem 0.9rem;
        background: #48bb78;
        color: white;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 700;
    }

    .stock-badge.low {
        background: #f56565;
    }

    .stock-badge.medium {
        background: #ed8936;
    }

    /* Precio destacado */
    .precio-cell {
        font-size: 1.25rem;
        font-weight: 700;
        color: #2d3748;
        text-align: right;
    }

    /* Estado vacío */
    .empty-state {
        padding: 4rem 2rem;
        text-align: center;
        color: #718096;
    }

    .empty-state svg {
        width: 80px;
        height: 80px;
        margin: 0 auto 1rem;
        opacity: 0.3;
    }

    .empty-state h3 {
        font-size: 1.25rem;
        font-weight: 600;
        margin: 0 0 0.5rem 0;
        color: #4a5568;
    }

    .empty-state p {
        font-size: 0.95rem;
        margin: 0;
    }

    /* Modo oscuro */
    body.dark-mode .catalogo-card {
        background: #2d3748;
    }

    body.dark-mode .catalogo-header h1 {
        background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e0 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    body.dark-mode .catalogo-header p {
        color: #cbd5e0;
    }

    body.dark-mode .catalogo-header {
        border-bottom-color: #4a5568;
    }

    body.dark-mode .catalogo-table th {
        background: #1a202c;
        color: #e2e8f0;
        border-bottom-color: #4a5568;
    }

    body.dark-mode .catalogo-table tbody tr {
        border-bottom-color: #4a5568;
    }

    body.dark-mode .catalogo-table tbody tr:hover {
        background: #374151;
    }

    body.dark-mode .catalogo-table td {
        color: #e2e8f0;
    }

    body.dark-mode .producto-nombre {
        color: #f7fafc;
    }

    body.dark-mode .producto-marca {
        color: #cbd5e0;
    }

    body.dark-mode .producto-placeholder {
        background: #374151;
        border-color: #4a5568;
    }

    body.dark-mode .catalogo-select {
        background: #2d3748;
        border-color: #4a5568;
        color: #e2e8f0;
    }

    body.dark-mode .btn-volver {
        background: #2d3748;
        border-color: #4a5568;
        color: #e2e8f0;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .catalogo-container {
            padding: 1rem;
        }

        .catalogo-header {
            padding: 1.5rem 0;
        }

        .catalogo-header h1 {
            font-size: 1.75rem;
        }

        .catalogo-controls {
            flex-direction: column;
            align-items: stretch;
        }

        .catalogo-table th,
        .catalogo-table td {
            padding: 0.75rem;
            font-size: 0.85rem;
        }

        .producto-img,
        .producto-placeholder {
            width: 50px;
            height: 50px;
        }

        .precio-cell {
            font-size: 1rem;
        }
    }
</style>

    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
<div class="catalogo-container">
    
    <!-- Header del catálogo -->
    <div class="catalogo-header">
        <h1>Catálogo de <?= htmlspecialchars($proveedor_nombre) ?></h1>
        <p>Empresa: <?= htmlspecialchars($prov['empresa'] ?? '-') ?></p>
    </div>

    <!-- Controles -->
    <div class="catalogo-controls">
        <form method="GET" action="<?= htmlspecialchars($_SERVER['SCRIPT_NAME'] . '?view=catalogo_proveedor') ?>">
            <input type="hidden" name="view" value="catalogo_proveedor">
            <input type="hidden" name="proveedor" value="<?= htmlspecialchars($proveedor_id) ?>">
            <select name="orden" onchange="this.form.submit()" class="catalogo-select">
                <option value="nom_asc"    <?= ($orden_get=='nom_asc')    ? 'selected' : '' ?>>Nombre A-Z</option>
                <option value="nom_desc"   <?= ($orden_get=='nom_desc')   ? 'selected' : '' ?>>Nombre Z-A</option>
                <option value="precio_asc" <?= ($orden_get=='precio_asc') ? 'selected' : '' ?>>Precio: Menor</option>
                <option value="precio_desc"<?= ($orden_get=='precio_desc')? 'selected' : '' ?>>Precio: Mayor</option>
            </select>
        </form>

        <a href="index.php?view=proveedores" class="btn-volver">
            ← Volver a proveedores
        </a>
    </div>

    <!-- Tabla de productos -->
    <div class="catalogo-card">
        <table class="catalogo-table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th class="text-center">Categoría</th>
                    <th class="text-center">Stock</th>
                    <th class="text-right">Precio</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($productos)): ?>
                    <?php foreach ($productos as $p): ?>
                        <tr>
                            <td>
                                <div class="producto-cell">
                                    <?php if (!empty($p['imagen'])): ?>
                                        <img src="<?= htmlspecialchars($p['imagen']) ?>" alt="" class="producto-img">
                                    <?php else: ?>
                                        <div class="producto-placeholder">IMG</div>
                                    <?php endif; ?>
                                    <div class="producto-info">
                                        <div class="producto-nombre"><?= htmlspecialchars($p['nom_producto']) ?></div>
                                        <?php if (!empty($p['marca'])): ?>
                                            <div class="producto-marca">Marca: <?= htmlspecialchars($p['marca']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="categoria-badge"><?= htmlspecialchars($p['categoria'] ?? 'Sin categoría') ?></span>
                            </td>
                            <td class="text-center">
                                <?php 
                                $stock = (int)$p['cantidad'];
                                $stockClass = $stock > 20 ? '' : ($stock > 5 ? 'medium' : 'low');
                                ?>
                                <span class="stock-badge <?= $stockClass ?>"><?= $stock ?></span>
                            </td>
                            <td class="precio-cell">
                                $<?= number_format($p['precio'], 2) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">
                            <div class="empty-state">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                                <h3>No hay productos disponibles</h3>
                                <p>Este proveedor aún no tiene productos registrados en el catálogo.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>