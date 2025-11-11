<?php
// src/api/inventario_api.php
// API server-side para inventario (filtrar, ajustar stock, toggle activo, historial)
// Requiere: src/config/db.php (PDO $pdo)

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

function responder($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Sanity: $pdo debe venir de config/db.php
if (!isset($pdo) || !$pdo instanceof PDO) {
    responder(['success' => false, 'message' => 'Error de configuración: no hay conexión PDO.']);
}

$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {

        // --------------------------------------------------
        // FILTRAR (devuelve HTML para insertar en la tabla)
        // --------------------------------------------------
        case 'filtrar':
            $busqueda = trim($_GET['busqueda'] ?? '');
            $categoria = $_GET['categoria'] ?? '';
            $orden = $_GET['orden'] ?? 'nom_asc';
            $mostrar_inactivos = (($_GET['tab'] ?? 'activo') === 'descatalogado');

            // mapear ordenes a SQL
            $mapOrder = [
                'nom_asc' => 'p.nom_producto ASC',
                'nom_desc' => 'p.nom_producto DESC',
                'precio_asc' => 'p.precio ASC',
                'precio_desc' => 'p.precio DESC',
            ];
            $orderSQL = $mapOrder[$orden] ?? $mapOrder['nom_asc'];

            // construir WHERE dinámico
            $where = ['1=1'];
            $params = [];

            if ($busqueda !== '') {
                $where[] = "(p.nom_producto LIKE :q OR p.cod_barras LIKE :q OR p.sku LIKE :q)";
                $params[':q'] = "%{$busqueda}%";
            }

            if ($categoria !== '') {
                $where[] = "p.id_categoria = :cat";
                $params[':cat'] = $categoria;
            }


            
            // $mostrar_inactivos es TRUE si $_GET['tab'] === 'descatalogado'
            if ($mostrar_inactivos) {
                // Si la pestaña es "Descatalogados", SOLAMENTE mostrar los inactivos (0)
                $where[] = "p.is_active = 0"; // NOTA: Quitamos IFNULL porque se asume que is_active tiene valor
            } else {
                // Si la pestaña es "Activos" (u otra cosa), SOLAMENTE mostrar los activos (1)
                $where[] = "IFNULL(p.is_active, 1) = 1";
            }

            $whereSQL = implode(' AND ', $where);

            // consulta principal: productos
            $sql = "
                SELECT 
                    p.cod_barras AS id_producto,
                    p.cod_barras AS producto_cod_barras,
                    p.nom_producto AS producto_nombre,
                    p.imagen AS producto_imagen,
                    p.marca,
                    p.descripcion,
                    c.nombre AS categoria,
                    p.cantidad,
                    p.talla,
                    p.color,
                    p.cantidad_min,
                    p.costo,
                    p.precio AS precio_unitario,
                    p.id_categoria,
                    (SELECT COUNT(*) FROM variantes v2 WHERE v2.cod_barras = p.cod_barras) AS tiene_variante,
                    IFNULL(p.is_active,1) AS is_active
                FROM productos p
                LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
                WHERE {$whereSQL}
                ORDER BY {$orderSQL}
                LIMIT 1000
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // obtener variantes de los productos (por cod_barras)
            $cods = array_map(function($r){ return $r['id_producto']; }, $productos);
            $variantesPorProducto = [];
            if (!empty($cods)) {
                $in = implode(',', array_fill(0, count($cods), '?'));
                $sqlv = "
                    SELECT id_variante, color, talla, imagen, sku, cantidad, cantidad_min, costo, precio, cod_barras
                    FROM variantes
                    WHERE cod_barras IN ($in)
                    ORDER BY id_variante ASC
                ";
                $stmtv = $pdo->prepare($sqlv);
                $stmtv->execute($cods);
                $rowsV = $stmtv->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rowsV as $vv) {
                    $variantesPorProducto[$vv['cod_barras']][] = $vv;
                }
            }

            // Generar HTML (mismo markup que usa la página)
            ob_start();
            if (!empty($productos)):
                foreach ($productos as $producto):
                    $pid = htmlspecialchars($producto['id_producto']);
                    $nombre = htmlspecialchars($producto['producto_nombre']);
                    $sku = htmlspecialchars($producto['producto_cod_barras']);
                    $cantidad = (int)($producto['cantidad'] ?? 0);
                    $cantidad_min = (int)($producto['cantidad_min'] ?? 0);
                    $is_active = (int)($producto['is_active'] ?? 1);
                    $stockClass = ($cantidad <= $cantidad_min && $cantidad_min > 0) ? 'bg-red-100 text-red-600' : 'bg-green-50 text-green-800';
                    $imagen = !empty($producto['producto_imagen']) ? "uploads/".htmlspecialchars($producto['producto_imagen']) : "/../uploads/sin-imagen.png";
                    $jsonProducto = htmlspecialchars(json_encode($producto), ENT_QUOTES, 'UTF-8');
                    ?>
                    <tr class="producto-row <?php if(!$is_active) echo 'product-inactive'; ?>"
                        id="product-row-<?= $pid ?>"
                        data-name="<?= strtolower($nombre) ?>"
                        data-sku="<?= strtolower($sku) ?>"
                        data-category="<?= htmlspecialchars($producto['id_categoria']) ?>"
                        data-price="<?= htmlspecialchars($producto['precio_unitario']) ?>"
                        data-active="<?= $is_active ?>"
                        data-details="<?= $jsonProducto ?>">
                        <td class="px-4 py-4 flex items-center gap-4">
                            <img src="<?= $imagen ?>" class="w-12 h-14 object-cover rounded" alt="img">
                            <div>
                                <div class="font-semibold text-sm"><?= $nombre ?></div>
                                <div class="text-xs text-gray-500">SKU: <?= $sku ?></div>
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <span id="stock-<?= $pid ?>" data-min="<?= $cantidad_min ?>" class="px-3 py-1 rounded-full text-sm font-semibold <?= $stockClass ?>"><?= $cantidad ?> unid.</span>
                        </td>
                        <td class="px-4 py-4 hidden sm:table-cell"><?= htmlspecialchars($producto['categoria']) ?></td>
                        <td class="px-4 py-4 font-semibold">$<?= number_format($producto['precio_unitario'] ?? 0, 2) ?></td>
                        <td class="px-4 py-4 text-right">
                            <div class="inline-flex items-center gap-2 justify-end">
                                <button title="Ajustar stock" class="btn-ajuste inline-flex items-center gap-2 px-3 py-2 rounded-full bg-gray-100 hover:bg-gray-200"
                                        data-id="<?= $pid ?>" data-isvariante="<?= ($producto['tiene_variante'] > 0 ? 'true' : 'false') ?>">
                                    ⚙ Ajuste
                                </button>

                                <button title="Ver detalle" class="inline-flex items-center px-3 py-2 rounded-full bg-indigo-50 hover:bg-indigo-100 open-modal-btn"
                                        data-details='<?= $jsonProducto ?>'>
                                    👁 Ver
                                </button>

                                <button title="<?= $is_active ? 'Descatalogar' : 'Activar' ?>"
                                        class="toggle-active inline-flex items-center px-3 py-2 rounded-full <?= $is_active ? 'bg-red-50 hover:bg-red-100' : 'bg-green-50 hover:bg-green-100' ?>"
                                        data-id="<?= $pid ?>" data-type="producto" data-active="<?= $is_active ? 'true' : 'false' ?>">
                                    <?= $is_active ? '🗙' : '✔' ?>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php
                    // variantes (si las hay)
                    if (!empty($variantesPorProducto[$producto['id_producto']])):
                        foreach ($variantesPorProducto[$producto['id_producto']] as $var):
                            $vsku = htmlspecialchars($var['sku'] ?: $var['id_variante']);
                            $vcant = (int)($var['cantidad'] ?? 0);
                            $vcant_min = (int)($var['cantidad_min'] ?? 0);
                            $vstockClass = ($vcant <= $vcant_min && $vcant_min > 0) ? 'bg-red-100 text-red-600' : 'bg-green-50 text-green-800';
                            $jsonVar = htmlspecialchars(json_encode(array_merge($var, ['producto_nombre' => $producto['producto_nombre'], 'categoria' => $producto['categoria'], 'id_producto' => $producto['id_producto']])), ENT_QUOTES, 'UTF-8');
                            ?>
                            <tr class="variant-row bg-gray-50" id="variant-row-<?= $vsku ?>"
                                data-name="<?= htmlspecialchars(strtolower($producto['producto_nombre'] . ' ' . ($var['talla'] ?? '') . ' ' . ($var['color'] ?? ''))) ?>"
                                data-sku="<?= htmlspecialchars(strtolower($vsku)) ?>"
                                data-category="<?= htmlspecialchars($producto['id_categoria']) ?>"
                                data-price="<?= htmlspecialchars($var['precio']) ?>"
                                data-active="<?= $is_active ?>">
                                <td class="px-4 py-3 pl-20">
                                    <div class="text-sm font-semibold"><?= htmlspecialchars($producto['producto_nombre']) ?> — <span class="text-xs text-gray-500">SKU: <?= $vsku ?></span></div>
                                    <div class="text-xs text-gray-500">Talla: <?= htmlspecialchars($var['talla'] ?: '—') ?> | Color: <?= htmlspecialchars($var['color'] ?: '—') ?></div>
                                </td>
                                <td class="px-4 py-3">
                                    <span id="stock-<?= $vsku ?>" data-min="<?= $vcant_min ?>" class="px-3 py-1 rounded-full text-sm font-semibold <?= $vstockClass ?>"><?= $vcant ?> unid.</span>
                                </td>
                                <td class="px-4 py-3 hidden sm:table-cell"><?= htmlspecialchars($producto['categoria']) ?></td>
                                <td class="px-4 py-3">$<?= number_format($var['precio'] ?? 0, 2) ?></td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex gap-2 justify-end items-center">
                                        <button class="btn-ajuste inline-flex items-center px-3 py-2 rounded-full bg-gray-100 hover:bg-gray-200"
                                                data-id="<?= $vsku ?>" data-isvariante="true">
                                            ⚙ Ajuste
                                        </button>
                                        <button class="inline-flex items-center px-3 py-2 rounded-full bg-indigo-50 hover:bg-indigo-100 open-modal-btn"
                                                data-details='<?= $jsonVar ?>'>
                                            👁 Ver
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php
                        endforeach;
                    endif;
                endforeach;
            else:
                echo '<tr><td colspan="5" class="p-8 text-center text-gray-500">No hay productos con esos criterios.</td></tr>';
            endif;
            $html = ob_get_clean();
            responder(['success' => true, 'html' => $html]);
            break;

        // --------------------------------------------------
        // AJUSTAR STOCK (POST)
        // --------------------------------------------------
        case 'ajustar_stock':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                responder(['success' => false, 'message' => 'Método no permitido.']);
            }
            $cod_entidad = $_POST['cod_entidad'] ?? null;
            $cantidad = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 0;
            // flag puede venir como ajusteEsVariante o es_variante
            $esVar = filter_var($_POST['ajusteEsVariante'] ?? $_POST['es_variante'] ?? 'false', FILTER_VALIDATE_BOOLEAN);

            if (!$cod_entidad || $cantidad == 0) {
                responder(['success' => false, 'message' => 'Datos insuficientes o cantidad inválida.']);
            }

            try {
                $pdo->beginTransaction();

                // Registrar movimiento
                $tipo = $cantidad > 0 ? 'ENTRADA' : 'SALIDA';
                $abs = abs($cantidad);
                $motivo = $_POST['motivo'] ?? 'Ajuste manual';
                $referencia = $_POST['referencia'] ?? null;
                $idUsuario = $_SESSION['user_id'] ?? 1;

                $sqlMov = "INSERT INTO inventario_movimientos (cod_barras, tipo_movimiento, cantidad_impactada, motivo, referencia, id_usuario) VALUES (?, ?, ?, ?, ?, ?)";
                $stm = $pdo->prepare($sqlMov);
                $stm->execute([$cod_entidad, $tipo, $abs, $motivo, $referencia, $idUsuario]);

                // Actualizar tabla correspondiente
                if ($esVar) {
                    // asumimos que cod_entidad es sku para variantes
                    $sqlUpd = "UPDATE variantes SET cantidad = cantidad + ? WHERE sku = ?";
                    $op = ($cantidad > 0) ? $abs : -$abs;
                    // pero para seguridad, usamos cantidad = cantidad +/- abs
                    // Ejecutar con operador según signo:
                    if ($cantidad > 0) {
                        $sqlUpd = "UPDATE variantes SET cantidad = cantidad + ? WHERE sku = ?";
                        $pdo->prepare($sqlUpd)->execute([$abs, $cod_entidad]);
                    } else {
                        $sqlUpd = "UPDATE variantes SET cantidad = GREATEST(0, cantidad - ?) WHERE sku = ?";
                        $pdo->prepare($sqlUpd)->execute([$abs, $cod_entidad]);
                    }
                    // obtener nuevo stock
                    $stmt = $pdo->prepare("SELECT cantidad FROM variantes WHERE sku = ?");
                    $stmt->execute([$cod_entidad]);
                    $nuevo = (int)$stmt->fetchColumn();
                } else {
                    // productos por cod_barras
                    if ($cantidad > 0) {
                        $sqlUpd = "UPDATE productos SET cantidad = cantidad + ? WHERE cod_barras = ?";
                        $pdo->prepare($sqlUpd)->execute([$abs, $cod_entidad]);
                    } else {
                        $sqlUpd = "UPDATE productos SET cantidad = GREATEST(0, cantidad - ?) WHERE cod_barras = ?";
                        $pdo->prepare($sqlUpd)->execute([$abs, $cod_entidad]);
                    }
                    $stmt = $pdo->prepare("SELECT cantidad FROM productos WHERE cod_barras = ?");
                    $stmt->execute([$cod_entidad]);
                    $nuevo = (int)$stmt->fetchColumn();
                }

                $pdo->commit();

                responder(['success' => true, 'message' => 'Ajuste registrado con éxito.', 'nuevo_stock' => $nuevo]);
            } catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    // error_log("inventario_api::ajustar_stock => " . $e->getMessage()); // COMENTAR LÍNEA ORIGINAL
    
    // --- TEMPORAL: Muestra el mensaje de error real de la BD ---
    responder(['success' => false, 'message' => 'Error SQL: ' . $e->getMessage()]);
    // ------------------------------------------------------------
}
            break;

        // --------------------------------------------------
        // TOGGLE ACTIVO (POST)
        // --------------------------------------------------
        case 'toggle_activo':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                responder(['success' => false, 'message' => 'Método no permitido.']);
            }
            $id = $_POST['id'] ?? null;
            $status = isset($_POST['status']) ? (int)$_POST['status'] : null;
            if (!$id || ($status !== 0 && $status !== 1)) {
                responder(['success' => false, 'message' => 'Parámetros inválidos.']);
            }
            try {
                $stmt = $pdo->prepare("UPDATE productos SET is_active = :s WHERE cod_barras = :id");
                $stmt->execute([':s' => $status, ':id' => $id]);
                $msg = $status ? 'Producto activado.' : 'Producto descatalogado.';
                responder(['success' => true, 'message' => $msg]);
            } catch (PDOException $e) {
                error_log("inventario_api::toggle_activo => " . $e->getMessage());
                responder(['success' => false, 'message' => 'Error al cambiar estado.']);
            }
            break;

        // --------------------------------------------------
        // FETCH HISTORIAL (GET)
        // --------------------------------------------------
        case 'fetch_historial':
            $id = $_GET['id'] ?? null;
            $type = $_GET['type'] ?? 'producto';
            if (!$id) responder(['success' => false, 'message' => 'Falta ID.']);

            // Intentamos devolver movimientos y datos básicos del producto
            try {
                $stmtMov = $pdo->prepare("SELECT tipo_movimiento, cantidad_impactada AS cantidad, motivo, referencia, fecha_movimiento FROM inventario_movimientos WHERE cod_barras = :id ORDER BY fecha_movimiento DESC LIMIT 50");
                $stmtMov->execute([':id' => $id]);
                $hist = $stmtMov->fetchAll(PDO::FETCH_ASSOC);

                // info producto (si existe)
                if ($type === 'variante') {
                    $stmtP = $pdo->prepare("SELECT * FROM variantes WHERE sku = :id LIMIT 1");
                } else {
                    $stmtP = $pdo->prepare("SELECT * FROM productos WHERE cod_barras = :id LIMIT 1");
                }
                $stmtP->execute([':id' => $id]);
                $info = $stmtP->fetch(PDO::FETCH_ASSOC);

                responder(['success' => true, 'historial' => $hist, 'data' => $info]);
            } catch (PDOException $e) {
                error_log("inventario_api::fetch_historial => " . $e->getMessage());
                responder(['success' => false, 'message' => 'Error al obtener historial.']);
            }
            break;

        default:
            responder(['success' => false, 'message' => 'Acción no reconocida.']);
    }
} catch (Exception $e) {
    error_log("inventario_api::global => " . $e->getMessage());
    responder(['success' => false, 'message' => 'Error interno del servidor.']);
}
