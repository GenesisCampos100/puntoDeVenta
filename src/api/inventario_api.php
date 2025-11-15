<?php
// src/api/inventario_api.php
// API server-side refactorizado para inventario (filtrar, ajustar stock, toggle activo, historial)
// Desarrollado con enfoque en robustez, atomicidad (transacciones) y seguridad.

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) session_start();

/**
 * Función central para responder en formato JSON y terminar la ejecución.
 * @param array $data Los datos a codificar en JSON.
 */
function responder(array $data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ----------------------------------------------------
// 🎯 FUNCIONES CORE DE BACKEND
// ----------------------------------------------------

/**
 * Determina el cod_barras principal (del producto padre) de una entidad.
 * Esto es clave para el registro unificado en inventario_movimientos.
 * @param PDO $pdo Conexión de base de datos.
 * @param string $cod_entidad SKU/ID de la entidad (producto o variante).
 * @param bool $esVar Indica si la entidad es una variante.
 * @return string El cod_barras principal o el $cod_entidad si no es variante.
 */
function obtener_cod_barras_principal(PDO $pdo, string $cod_entidad, bool $esVar): string {
    if (!$esVar) {
        return $cod_entidad;
    }
    // Si es variante, buscamos el cod_barras padre
    $stmtCB = $pdo->prepare("SELECT cod_barras FROM variantes WHERE sku = ? OR id_variante = ? LIMIT 1");
    $stmtCB->execute([$cod_entidad, $cod_entidad]);
    return $stmtCB->fetchColumn() ?: $cod_entidad; // Fallback al propio ID si no se encuentra
}

/**
 * Actualiza el stock en la tabla productos o variantes.
 * @param PDO $pdo Conexión de base de datos.
 * @param string $cod_entidad SKU/ID de la entidad a actualizar.
 * @param int $cantidad Cantidad absoluta a sumar/restar.
 * @param bool $esVar Es variante o producto.
 * @param int $signo 1 para ENTRADA (+), -1 para SALIDA (-).
 * @return int El nuevo stock de la entidad.
 */
function actualizar_stock(PDO $pdo, string $cod_entidad, int $cantidad, bool $esVar, int $signo): int {
    $abs = abs($cantidad);
    $nuevo_stock = 0;

    if ($esVar) {
        $campo_id = is_numeric($cod_entidad) ? 'id_variante' : 'sku';
        $condicion = "{$campo_id} = ?";
        
        // Consulta base (asegura que el stock nunca sea negativo)
        if ($signo > 0) {
            $sqlUpd = "UPDATE variantes SET cantidad = cantidad + ? WHERE {$condicion}";
        } else {
            $sqlUpd = "UPDATE variantes SET cantidad = GREATEST(0, cantidad - ?) WHERE {$condicion}";
        }
        
        $pdo->prepare($sqlUpd)->execute([$abs, $cod_entidad]);
        
        // Obtener nuevo stock
        $stmt = $pdo->prepare("SELECT cantidad FROM variantes WHERE {$condicion} LIMIT 1");
        $stmt->execute([$cod_entidad]);
        $nuevo_stock = (int)$stmt->fetchColumn();

    } else {
        // Producto (por cod_barras)
        if ($signo > 0) {
            $sqlUpd = "UPDATE productos SET cantidad = cantidad + ? WHERE cod_barras = ?";
        } else {
            $sqlUpd = "UPDATE productos SET cantidad = GREATEST(0, cantidad - ?) WHERE cod_barras = ?";
        }
        
        $pdo->prepare($sqlUpd)->execute([$abs, $cod_entidad]);
        
        // Obtener nuevo stock
        $stmt = $pdo->prepare("SELECT cantidad FROM productos WHERE cod_barras = ? LIMIT 1");
        $stmt->execute([$cod_entidad]);
        $nuevo_stock = (int)$stmt->fetchColumn();
    }

    return $nuevo_stock;
}

// ----------------------------------------------------
// 🚧 INICIALIZACIÓN
// ----------------------------------------------------

if (!isset($pdo) || !$pdo instanceof PDO) {
    responder(['success' => false, 'message' => 'Error de configuración: no hay conexión PDO.']);
}

// Para aceptar datos JSON en peticiones POST
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Combinar GET/POST/JSON para obtener la acción y los parámetros
$request_data = array_merge($_GET, $_POST, $data ?: []); 
$action = $request_data['action'] ?? '';

// ----------------------------------------------------
// ⚙️ ENRUTAMIENTO DE ACCIONES (SWITCH)
// ----------------------------------------------------

try {
    switch ($action) {

        // --------------------------------------------------
        // FILTRAR (devuelve HTML para insertar en la tabla)
        // --------------------------------------------------
        case 'filtrar':
            // Lógica de filtrado y generación de HTML (mantenida del código original y funcional)
            // ... (Bloque de código 'filtrar' que genera el HTML de productos y variantes)
            $busqueda = trim($request_data['busqueda'] ?? '');
            $categoria = $request_data['categoria'] ?? '';
            $orden = $request_data['orden'] ?? 'nom_asc';
            $mostrar_inactivos = (($request_data['tab'] ?? 'activo') === 'descatalogado');

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

            if ($mostrar_inactivos) {
                $where[] = "p.is_active = 0";
            } else {
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
                // Usamos un IN seguro con marcadores de posición
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

            // Generar HTML
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
                        <td class="px-4 py-4 align-top text-right">
        <div class="inline-flex items-center gap-1 justify-end">
        
        <?php if ($producto['tiene_variante'] <= 0): ?>
            <button title="Editar / Ajustar Stock" 
                class="btn-ajuste p-2 rounded-full text-gray-500 hover:bg-primary-100 hover:text-indigo-600 transition duration-150"
                onclick="openMovimientoModal('<?= $pid ?>','producto','<?= addslashes($nombre) ?>', <?= $producto['tiene_variante'] > 0 ? 'true' : 'false' ?>)">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18.37 2.63a2.12 2.12 0 0 1 3 3L11.75 16.25l-4.5 1.75 1.75-4.5Z"/><path d="M15 20h4a2 2 0 0 0 2-2v-1"/><path d="M3.5 6.5 6 9"/><path d="m5 16 2.5 2.5"/></svg>
            </button>
        <?php endif; ?>
        <button title="Ver detalle" 
            class="p-2 rounded-full text-indigo-600 hover:bg-indigo-100 open-modal-btn transition duration-150" 
            data-details='<?= $jsonProducto ?>'>
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M9 11h6"/><path d="M9 15h6"/></svg>
        </button>

        <button title="<?= $is_active ? 'Descatalogar' : 'Activar' ?>" 
            class="toggle-active p-2 rounded-full <?= $is_active ? 'text-red-500 hover:bg-red-100' : 'text-green-600 hover:bg-green-100' ?> transition duration-150"
            data-id="<?= $pid ?>" data-type="producto" data-active="<?= $is_active ? 'true' : 'false' ?>">
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="<?= $is_active ? 'M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M10 11v6M14 11v6' : 'M22 11.08V12a10 10 0 1 1-5.93-9.14M9 11l3 3L22 4' ?>" /></svg>
        </button>
    </div>
</td>
                    </tr>
                    <?php
                    // variantes (si las hay)
                    if (!empty($variantesPorProducto[$producto['id_producto']])):
                        foreach ($variantesPorProducto[$producto['id_producto']] as $var):
                            // Se utiliza el id_variante/sku como ID para acciones en frontend
                            $vId = htmlspecialchars($var['sku'] ?: $var['id_variante']);
                            $vsku = htmlspecialchars($var['sku'] ?: $var['id_variante']);
                            $vcant = (int)($var['cantidad'] ?? 0);
                            $vcant_min = (int)($var['cantidad_min'] ?? 0);
                            $vstockClass = ($vcant <= $vcant_min && $vcant_min > 0) ? 'bg-red-100 text-red-600' : 'bg-green-50 text-green-800';
                            $jsonVar = htmlspecialchars(json_encode(array_merge($var, ['producto_nombre' => $producto['producto_nombre'], 'categoria' => $producto['categoria'], 'id_producto' => $producto['id_producto']])), ENT_QUOTES, 'UTF-8');
                            ?>
                            <tr class="variant-row bg-gray-50" id="variant-row-<?= $vId ?>"
                                data-name="<?= htmlspecialchars(strtolower($producto['producto_nombre'] . ' ' . ($var['talla'] ?? '') . ' ' . ($var['color'] ?? ''))) ?>"
                                data-sku="<?= htmlspecialchars(strtolower($vsku)) ?>"
                                data-category="<?= htmlspecialchars($producto['id_categoria']) ?>"
                                data-price="<?= htmlspecialchars($var['precio']) ?>"
                                data-active="<?= $is_active ?>">
                                <td class="px-4 py-3 pl-20">
                                    <div class="text-sm font-semibold"><?= htmlspecialchars($producto['producto_nombre']) ?> — <span class="text-xs text-gray-500">SKU/ID: <?= $vId ?></span></div>
                                    <div class="text-xs text-gray-500">Talla: <?= htmlspecialchars($var['talla'] ?: '—') ?> | Color: <?= htmlspecialchars($var['color'] ?: '—') ?></div>
                                </td>
                                <td class="px-4 py-3">
                                    <span id="stock-<?= $vId ?>" data-min="<?= $vcant_min ?>" class="px-3 py-1 rounded-full text-sm font-semibold <?= $vstockClass ?>"><?= $vcant ?> unid.</span>
                                </td>
                                <td class="px-4 py-3 hidden sm:table-cell"><?= htmlspecialchars($producto['categoria']) ?></td>
                                <td class="px-4 py-3">$<?= number_format($var['precio'] ?? 0, 2) ?></td>
                                
                                <td class="px-4 py-3 align-top text-right">
                                    <div class="inline-flex items-center gap-1 justify-end">
                                        <button title="Ajustar stock variante" class="btn-ajuste p-2 rounded-full text-gray-500 hover:bg-primary-100 hover:text-indigo-600 transition duration-150"
                                            onclick="openMovimientoModal('<?= $vId ?>','variante','<?= addslashes($producto['producto_nombre'] . ' - ' . ($var['talla'] ?? '')) ?>', true)">
                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18.37 2.63a2.12 2.12 0 0 1 3 3L11.75 16.25l-4.5 1.75 1.75-4.5Z"/><path d="M15 20h4a2 2 0 0 0 2-2v-1"/><path d="M3.5 6.5 6 9"/><path d="m5 16 2.5 2.5"/></svg>
                                        </button>

                                        <button title="Ver detalle variante" class="p-2 rounded-full text-indigo-600 hover:bg-indigo-100 open-modal-btn transition duration-150"
                                            data-details='<?= $jsonVar ?>'>
                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M9 11h6"/><path d="M9 15h6"/></svg>
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
            // Sanitización y validación de datos
            $cod_entidad = $request_data['cod_entidad'] ?? null; 
            $cantidad_raw = isset($request_data['cantidad']) ? (int)$request_data['cantidad'] : 0;
            $esVar = filter_var($request_data['ajusteEsVariante'] ?? $request_data['es_variante'] ?? 'false', FILTER_VALIDATE_BOOLEAN);

            if (!$cod_entidad || $cantidad_raw == 0) {
                responder(['success' => false, 'message' => 'Datos insuficientes (ID de entidad o Cantidad inválida).']);
            }
            
            // Determinar tipo de movimiento y signo
            $tipo = $cantidad_raw > 0 ? 'ENTRADA' : 'SALIDA';
            $signo = $cantidad_raw > 0 ? 1 : -1;
            $abs_cantidad = abs($cantidad_raw);
            
            $motivo = $request_data['motivo'] ?? 'Ajuste manual';
            $referencia = $request_data['referencia'] ?? null;
            $idUsuario = $_SESSION['user_id'] ?? $_SESSION['id_usuario'] ?? 1; // ID de usuario fallback

            try {
                // 1. Iniciar Transacción (para atomicidad)
                $pdo->beginTransaction();

                // 2. Obtener el Cod_Barras principal para el registro de movimiento
                $cod_barras_final = obtener_cod_barras_principal($pdo, $cod_entidad, $esVar);

                // 3. Registrar el movimiento en la tabla de historial
                $sqlMov = "INSERT INTO inventario_movimientos (cod_barras, tipo_movimiento, cantidad_impactada, motivo, referencia, id_usuario) 
                           VALUES (?, ?, ?, ?, ?, ?)";
                $stm = $pdo->prepare($sqlMov);
                $stm->execute([$cod_barras_final, $tipo, $abs_cantidad, $motivo, $referencia, $idUsuario]);

                // 4. Actualizar el stock de la entidad (Producto o Variante)
                $nuevo_stock = actualizar_stock($pdo, $cod_entidad, $abs_cantidad, $esVar, $signo);

                // 5. Finalizar la Transacción
                $pdo->commit();

                responder(['success' => true, 'message' => 'Ajuste registrado con éxito.', 'nuevo_stock' => $nuevo_stock]);
            } catch (PDOException $e) {
                // Si algo falla, deshacer todo y registrar el error
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log("inventario_api::ajustar_stock => " . $e->getMessage());
                // Devolvemos el error de forma segura (sin exponer detalles internos sensibles si es posible)
                responder(['success' => false, 'message' => 'Error de ajuste de stock (Error de Transacción).', 'error_detail' => $e->getMessage()]);
            }
            break;

        // --------------------------------------------------
        // TOGGLE ACTIVO (POST)
        // --------------------------------------------------
        case 'toggle_activo':
            // Mantenido con mejora en el uso de $request_data
            $id = $request_data['id'] ?? null;
            $status = isset($request_data['status']) ? (int)$request_data['status'] : null;

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
        // FETCH HISTORIAL (GET) - Solución del problema de variantes
        // --------------------------------------------------
        case 'fetch_historial':
            $id_entidad = $request_data['id'] ?? null;
            $type = $request_data['type'] ?? 'producto';
            
            if (!$id_entidad) responder(['success' => false, 'message' => 'Falta ID.']);

            try {
                // 1. CORRECCIÓN CLAVE: Obtener el cod_barras principal para consultar movimientos
                $esVar = ($type === 'variante');
                $id_para_movimientos = obtener_cod_barras_principal($pdo, $id_entidad, $esVar);
                
                // 2. Consulta de Historial (Ahora usa el cod_barras principal)
                $stmtMov = $pdo->prepare("SELECT tipo_movimiento, cantidad_impactada AS cantidad, motivo, referencia, fecha_movimiento, id_usuario 
                                          FROM inventario_movimientos 
                                          WHERE cod_barras = :id 
                                          ORDER BY fecha_movimiento DESC LIMIT 50");
                $stmtMov->execute([':id' => $id_para_movimientos]); 
                $hist = $stmtMov->fetchAll(PDO::FETCH_ASSOC);

                // 3. Consulta de la información de la entidad (usa el ID original)
                if ($esVar) {
                    $stmtP = $pdo->prepare("SELECT * FROM variantes WHERE sku = :id OR id_variante = :id LIMIT 1");
                } else {
                    $stmtP = $pdo->prepare("SELECT * FROM productos WHERE cod_barras = :id LIMIT 1");
                }
                $stmtP->execute([':id' => $id_entidad]);
                $info = $stmtP->fetch(PDO::FETCH_ASSOC);

                responder(['success' => true, 'historial' => $hist, 'data' => $info]);
            } catch (PDOException $e) {
                error_log("inventario_api::fetch_historial => " . $e->getMessage());
                responder(['success' => false, 'message' => 'Error al obtener historial.', 'error_detail' => $e->getMessage()]);
            }
            break;

        default:
            responder(['success' => false, 'message' => 'Acción no reconocida.']);
    }
} catch (Exception $e) {
    error_log("inventario_api::global => " . $e->getMessage());
    responder(['success' => false, 'message' => 'Error interno del servidor.']);
}