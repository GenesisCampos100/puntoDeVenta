<?php
// src/api/inventario_api.php
// API server-side refactorizado para inventario (filtrar, ajustar stock, toggle activo, historial)
// Desarrollado con enfoque en robustez, atomicidad (transacciones) y seguridad.

// 1. Configuración de Errores (CRÍTICO: Evita que las advertencias rompan la respuesta JSON)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Asegura que los errores no se muestren en la salida
ini_set('log_errors', 1);
// Cambia a una ruta de log segura y existente (ejemplo: dentro de una carpeta 'log' en src)
ini_set('error_log', __DIR__ . '/../log/php-error.log'); 

// 2. Inclusiones Críticas (Solo config/db.php)
require_once __DIR__ . '/../config/db.php';
// ATENCIÓN: Eliminamos los 'require_once' de los archivos product_row.partial.php y variant_row.partial.php.
// Estos solo se deben incluir DENTRO de la función render_productos_html.

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

function render_productos_html(array $productos, array $variantes): string {
    $html = '';
    $variantes_map = [];

    // Mapear variantes para acceso rápido
    foreach ($variantes as $v) {
        $variantes_map[$v['cod_barras']][] = $v;
    }

    foreach ($productos as $producto) {
        // Renderizar el producto padre
        ob_start(); // Inicia la captura del buffer
        // Necesitas que la plantilla use las claves del array de $producto (nom_producto, cod_barras, etc.)
        // Las claves de la consulta en inventario_api.php son correctas.
        
        // La plantilla (product_row.partial.php) espera 'nombre_categoria',
        // pero la consulta del API devuelve 'categoria'. Asegúrate de que coincidan.
        $producto['nombre_categoria'] = $producto['categoria']; 
        
        // La plantilla product_row.partial.php debe usar $producto['cod_barras'] como ID
        require __DIR__ . '/product_row.partial.php'; 
        
        $html .= ob_get_clean(); // Captura y limpia el buffer

        // Si tiene variantes, renderizarlas
        if (isset($variantes_map[$producto['cod_barras']])) {
            $variantes_del_producto = $variantes_map[$producto['cod_barras']];
            $html_variantes = '';
            
            foreach ($variantes_del_producto as $var) {
                ob_start();
                $var['nombre_variante'] = $var['nom_variante']; // Ajuste de nombre de variable si es necesario
                require __DIR__ . '/variant_row.partial.php';
                $html_variantes .= ob_get_clean();
            }

            // Crear la fila contenedora de variantes
            $html .= '<tr id="variants-' . $producto['cod_barras'] . '" class="hidden transition-all duration-300 ease-in-out">';
            $html .= '<td colspan="5" class="p-0 border-t-0">';
            $html .= '<table class="min-w-full divide-y divide-gray-200">';
            $html .= '<tbody>';
            $html .= $html_variantes;
            $html .= '</tbody>';
            $html .= '</table>';
            $html .= '</td>';
            $html .= '</tr>';
        }
    }
    return $html;
}


// ----------------------------------------------------
// FUNCIONES CORE DE BACKEND
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
    // Buscar código de barras real del producto
    // Solo buscar por cod_barras (ID único), no por nom_producto
    $stmt = $pdo->prepare("SELECT cod_barras FROM productos WHERE cod_barras = ? LIMIT 1");
    $stmt->execute([$cod_entidad]);
    return $stmt->fetchColumn() ?: $cod_entidad;
}
    // Es variante: buscar el cod_barras del producto padre
    $stmt = $pdo->prepare("SELECT cod_barras FROM variantes WHERE sku = ? OR id_variante = ? LIMIT 1");
    $stmt->execute([$cod_entidad, $cod_entidad]);
    $cod_barras = $stmt->fetchColumn();
    if ($cod_barras) {
        return $cod_barras;
    } else {
        // Si no se encuentra, devolver el mismo ID (fallback)
        return $cod_entidad;
    }
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
// INICIALIZACIÓN (Solución Universal para GET/POST/JSON)
// ----------------------------------------------------

if (!isset($pdo) || !$pdo instanceof PDO) {
    responder(['success' => false, 'message' => 'Error de configuración: no hay conexión PDO.']);
}

$request_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Paso 1: Leer el cuerpo de la petición (JSON, raw data)
    $input = file_get_contents('php://input');
    $json_data = json_decode($input, true);
    
    // Paso 2: Usar $_POST y mezclar con JSON (si existe)
    // Esto cubre POST estándar (formularios) y POST JSON (AJAX)
    $request_data = $_POST;
    if (is_array($json_data)) {
        // ✅ CORRECCIÓN CLAVE: Sobrescribimos o añadimos los datos decodificados
        $request_data = array_merge($request_data, $json_data);
    }
} else {
    // Si es GET (fetch_historial o filtrar), usamos $_GET
    $request_data = $_GET;
}

// Corregido: eliminamos el error de sintaxis al final
$action = $request_data['action'] ?? null;

// ----------------------------------------------------
// ✅ BLOQUE DE DEPURACIÓN CRÍTICO (TEMPORAL)
// ----------------------------------------------------
if ($action === 'fetch_historial' || $action === 'ajustar_stock' || $action === 'toggle_activo') {
    
    // Si $request_data['id'] no está, esto disparó el error "Falta ID.".
    if (!isset($request_data['id'])) { 
        
        // Obtener el cuerpo RAW de la petición (necesario si es JSON)
        $raw_input = file_get_contents('php://input');
        
        // Responder con toda la información disponible
        responder([
            'success' => false,
            'message' => '🛑 DEPURACIÓN: Falla al obtener ID. Analiza el objeto "debug_data".',
            'debug_data' => [
                'Metodo_HTTP' => $_SERVER['REQUEST_METHOD'],
                'Superglobal_GET' => $_GET,
                'Superglobal_POST' => $_POST,
                'Raw_Input_Body' => $raw_input,
                'JSON_Decodificado' => json_decode($raw_input, true),
                'Request_Data_Final_Antes_del_Fallo' => $request_data,
            ],
            'nota' => 'El ID que se está buscando es: ' . ($request_data['id'] ?? 'NULL/UNDEFINED')
        ]);
        // La función responder(..) termina la ejecución aquí.
    }
}
// ----------------------------------------------------
// CONTINÚA CON EL BLOQUE try { switch ($action) { ...

// ----------------------------------------------------
// ENRUTAMIENTO DE ACCIONES (SWITCH)
// ----------------------------------------------------

try {
    switch ($action) {

        // --------------------------------------------------
        // FILTRAR (devuelve HTML para insertar en la tabla)
        // --------------------------------------------------
case 'filtrar':
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
            p.*,
            c.nombre AS categoria,
            (SELECT COUNT(*) FROM variantes v2 WHERE v2.cod_barras = p.cod_barras) AS tiene_variante
        FROM productos p
        LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
        WHERE {$whereSQL}
        ORDER BY {$orderSQL}
        LIMIT 1000
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // obtener variantes por producto
    $lista_codigos = array_column($productos, 'cod_barras');
    $variantes = [];

    if (!empty($lista_codigos)) {
        $in = implode(',', array_fill(0, count($lista_codigos), '?'));
        $sqlv = "
            SELECT * FROM variantes
            WHERE cod_barras IN ($in)
            ORDER BY id_variante ASC
        ";
        $stmtv = $pdo->prepare($sqlv);
        $stmtv->execute($lista_codigos);
        $variantes = $stmtv->fetchAll(PDO::FETCH_ASSOC);
    }

    $html_tabla = render_productos_html($productos, $variantes); // Llama a la nueva función

    responder([
        'success' => true,
        // DEVUELVE EL HTML GENERADO
        'html' => $html_tabla, 
        // Opcional: para depuración (pero el cliente no los usa)
        // 'productos' => $productos, 
        // 'variantes' => $variantes 
    ]);
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