<?php

function json_response($data = [], $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// -------------------------------------------------------------------
try {
    if (!file_exists(__DIR__ . '/../config/db.php')) {
        throw new Exception("Archivo de configuración db.php no encontrado.");
    }
    require_once __DIR__ . '/../config/db.php';
    
    if (!isset($pdo) || !$pdo instanceof PDO) {
        throw new Exception("La variable \$pdo no está configurada correctamente.");
    }
} catch (Exception $e) {
    json_response(['success' => false, 'message' => 'Error de conexión DB: ' . $e->getMessage()], 500);
}

// 3. Procesamiento de Solicitud
// -------------------------------------------------------------------
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$data = array_merge($_GET, $_POST, $input);
$action = $data['action'] ?? null;

if (!$action) {
    json_response(['success' => false, 'message' => 'No se especificó ninguna acción.'], 400);
}

// 4. Lógica de Negocio
// -------------------------------------------------------------------
try {
    switch ($action) {
        
        // CASO 1: FILTRAR PRODUCTOS (Retorna HTML para la tabla)
        case 'filtrar':
            $busqueda = trim($data['busqueda'] ?? '');
            $categoria = $data['categoria'] ?? '';
            $orden = $data['orden'] ?? 'nom_asc';
            $tab = $data['tab'] ?? 'activo';
            
            // Mapeo de orden
            $mapOrder = [
                'nom_asc' => 'p.nom_producto ASC',
                'nom_desc' => 'p.nom_producto DESC',
                'precio_asc' => 'p.precio ASC',
                'precio_desc' => 'p.precio DESC'
            ];
            $orderBy = $mapOrder[$orden] ?? 'p.nom_producto ASC';
            
            // Filtros WHERE
            $where = ["1=1"];
            $params = [];
            
            if ($busqueda) {
                $where[] = "(p.nom_producto LIKE :q OR p.cod_barras LIKE :q OR p.sku LIKE :q)";
                $params[':q'] = "%$busqueda%";
            }
            
            if ($categoria) {
                $where[] = "p.id_categoria = :cat";
                $params[':cat'] = $categoria;
            }
            
            if ($tab === 'descatalogado') {
                $where[] = "p.is_active = 0";
            } else {
                $where[] = "IFNULL(p.is_active, 1) = 1";
            }
            
            $whereSQL = implode(' AND ', $where);
            
            // Consulta Principal
            $sql = "SELECT p.*, c.nombre as nombre_categoria, 
                    (SELECT COUNT(*) FROM variantes v WHERE v.cod_barras = p.cod_barras) as tiene_variante
                    FROM productos p 
                    LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
                    WHERE $whereSQL 
                    ORDER BY $orderBy LIMIT 500";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Cargar Variantes si hay productos
            $variantesMap = [];
            if (!empty($productos)) {
                $codigos = array_column($productos, 'cod_barras');
                $inQuery = implode(',', array_fill(0, count($codigos), '?'));
                $sqlVar = "SELECT * FROM variantes WHERE cod_barras IN ($inQuery)";
                $stmtVar = $pdo->prepare($sqlVar);
                $stmtVar->execute($codigos);
                $todasVariantes = $stmtVar->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($todasVariantes as $v) {
                    $variantesMap[$v['cod_barras']][] = $v;
                }
            }
            
            // Renderizar HTML (Buffer)
            ob_start();
            if (empty($productos)) {
                echo '<tr><td colspan="5" class="text-center py-8 text-gray-500">No se encontraron productos.</td></tr>';
            } else {
                foreach ($productos as $producto) {
                    // Preparar datos para partials
                    // IMPORTANTE: Los partials esperan variables específicas, las definimos aquí
                    // OJO: Si los partials usan $producto['clave'], ya lo tenemos.
                    
                    // Renderizar Fila Padre
                    require __DIR__ . '/product_row.partial.php';
                    
                    // Renderizar Variantes
                    if (!empty($variantesMap[$producto['cod_barras']])) {
                        $variantes = $variantesMap[$producto['cod_barras']];
                        echo '<tr id="variants-' . $producto['cod_barras'] . '" class="hidden bg-gray-50"><td colspan="5" class="p-0"><table class="w-full">';
                        foreach ($variantes as $var) {
                            // Enriquecer datos de variante
                            $var['producto_nombre'] = $producto['nom_producto'];
                            require __DIR__ . '/variant_row.partial.php';
                        }
                        echo '</table></td></tr>';
                    }
                }
            }
            $html = ob_get_clean();
            
            json_response(['success' => true, 'html' => $html, 'total' => count($productos)]);
            break;

        // CASO 2: AJUSTAR STOCK
        case 'ajustar_stock':
            $id = $data['cod_entidad'] ?? null;
            $cantidad = intval($data['cantidad'] ?? 0);
            $esVariante = filter_var($data['ajusteEsVariante'] ?? false, FILTER_VALIDATE_BOOLEAN);
            
            if (!$id || $cantidad === 0) {
                json_response(['success' => false, 'message' => 'Datos inválidos.'], 400);
            }
            
            $pdo->beginTransaction();
            
            if ($esVariante) {
                // Actualizar Variante
                // Validar ID (puede ser SKU o ID numérico)
                $col = is_numeric($id) ? 'id_variante' : 'sku';
                $sql = "UPDATE variantes SET cantidad = GREATEST(0, cantidad + :cant) WHERE $col = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':cant' => $cantidad, ':id' => $id]);
                
                // Obtener nuevo stock
                $stmt = $pdo->prepare("SELECT cantidad FROM variantes WHERE $col = :id");
                $stmt->execute([':id' => $id]);
                $nuevoStock = $stmt->fetchColumn();
            } else {
                // Actualizar Producto
                $sql = "UPDATE productos SET cantidad = GREATEST(0, cantidad + :cant) WHERE cod_barras = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':cant' => $cantidad, ':id' => $id]);
                
                // Obtener nuevo stock
                $stmt = $pdo->prepare("SELECT cantidad FROM productos WHERE cod_barras = :id");
                $stmt->execute([':id' => $id]);
                $nuevoStock = $stmt->fetchColumn();
            }
            
            // Registrar Movimiento (Simplificado)
            // Aquí podrías agregar la lógica de historial si la tabla existe
            
            $pdo->commit();
            json_response(['success' => true, 'nuevo_stock' => $nuevoStock, 'message' => 'Stock actualizado correctamente.']);
            break;

        // CASO 3: TOGGLE ACTIVO
        case 'toggle_activo':
            $id = $data['id'] ?? null;
            $status = intval($data['status'] ?? 1);
            
            if (!$id) json_response(['success' => false, 'message' => 'Falta ID.'], 400);
            
            $sql = "UPDATE productos SET is_active = :s WHERE cod_barras = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':s' => $status, ':id' => $id]);
            
            json_response(['success' => true, 'message' => 'Estado actualizado.']);
            break;

        default:
            json_response(['success' => false, 'message' => "Acción '$action' no reconocida."], 400);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("API ERROR: " . $e->getMessage());
    json_response(['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()], 500);
}