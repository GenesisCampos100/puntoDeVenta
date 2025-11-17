<?php
// =======================================================
// Archivo: api/ProductoModelo.php
// Modelo de datos para la gestión de productos e inventario.
// =======================================================

class ProductoModelo {
    private PDO $pdo;

    // Columnas válidas para ordenar (evita inyección SQL)
    private array $allowedOrderColumns = [
        'nom_asc' => 'p.nom_producto ASC',
        'nom_desc' => 'p.nom_producto DESC',
        'precio_asc' => 'p.precio ASC',
        'precio_desc' => 'p.precio DESC',
    ];

    /**
     * Constructor.
     * @param PDO $pdo_instance Instancia de la conexión PDO.
     */
    public function __construct(PDO $pdo_instance) {
        $this->pdo = $pdo_instance;
        // Establecer el modo de error de excepciones es una buena práctica
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    // =======================================================
    // ⚙️ FUNCIONES INTERNAS (HELPERS)
    // =======================================================

    /**
     * Obtiene el 'cod_barras' del producto principal.
     * Es crucial para registrar movimientos de variantes bajo el ID de su producto padre.
     */
    private function _getCodBarrasPrincipal(string $cod_entidad, bool $esVar): string {
        if (!$esVar) {
            return $cod_entidad; // Si es producto, su cod_barras es su ID principal
        }
        
        // Si es variante, buscamos el cod_barras (ID del producto padre) usando SKU o id_variante
        $stmtCB = $this->pdo->prepare("SELECT cod_barras FROM variantes WHERE sku = ? OR id_variante = ? LIMIT 1");
        $stmtCB->execute([$cod_entidad, $cod_entidad]);
        // Devolvemos el cod_barras encontrado, o el propio ID de la variante como fallback (aunque debería ser imposible)
        return $stmtCB->fetchColumn() ?: $cod_entidad;
    }

    // =======================================================
    // 🔹 1. Obtener categorías
    // =======================================================
    public function getCategorias(): array {
        // Asumiendo que 'categorias' tiene 'nombre' y no 'nombre_categoria' como se usó en el API
        // Si tu tabla usa 'nombre_categoria', simplemente cambia la consulta.
        $sql = "SELECT id_categoria, nombre FROM categorias ORDER BY nombre ASC"; 
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =======================================================
    // 🔹 2. Obtener productos filtrados (SERVER SIDE)
    // =======================================================
    public function getProductosFiltrados(array $params): array {
        $busqueda = trim($params['busqueda'] ?? '');
        $categoria = $params['categoria'] ?? '';
        $orden = $params['orden'] ?? 'nom_asc';
        // Los valores booleanos deben venir de forma limpia desde el controlador
        $mostrar_inactivos = $params['mostrar_inactivos'] ?? false; 

        $where = [];
        $values = [];

        // Filtros dinámicos
        if ($busqueda !== '') {
            // ✅ CORRECCIÓN: Se agrega p.sku a la búsqueda, usando marcadores de posición posicionales (?)
            $where[] = "(p.nom_producto LIKE ? OR p.cod_barras LIKE ? OR p.sku LIKE ?)";
            $values[] = "%$busqueda%";
            $values[] = "%$busqueda%";
            $values[] = "%$busqueda%";
        }

        if ($categoria !== '') {
            $where[] = "p.id_categoria = ?";
            $values[] = $categoria;
        }

        if (!$mostrar_inactivos) {
            // El API original usaba IFNULL(p.is_active, 1) = 1. Si is_active es NULL, lo trata como ACTIVO.
            $where[] = "IFNULL(p.is_active, 1) = 1"; 
        } else {
            // Mostrar solo inactivos (para la pestaña 'descatalogados')
            $where[] = "p.is_active = 0"; 
        }

        $whereSQL = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $orderSQL = $this->allowedOrderColumns[$orden] ?? $this->allowedOrderColumns['nom_asc'];

        // Consulta principal
        $sql = "
            SELECT 
                p.cod_barras AS id_producto,
                p.cod_barras,
                p.sku,
                p.nom_producto AS producto_nombre,
                p.imagen AS producto_imagen,
                p.marca,
                p.descripcion,
                p.talla,
                p.color,
                p.precio AS precio_unitario,
                p.costo,
                p.cantidad,
                p.cantidad_min,
                p.id_categoria,
                c.nombre AS categoria,
                IFNULL(p.is_active, 1) AS is_active,
                -- Se usa cod_barras para la subconsulta, asumiendo que esa es la relación
                (SELECT COUNT(*) FROM variantes v WHERE v.cod_barras = p.cod_barras) AS tiene_variante
            FROM productos p
            LEFT JOIN categorias c ON c.id_categoria = p.id_categoria
            $whereSQL
            ORDER BY $orderSQL
            LIMIT 1000
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Variantes asociadas (se usa cod_barras, coherente con el modelo de negocio)
        $cods = array_column($productos, 'cod_barras');
        $variantes = $this->getVariantesParaProductos($cods);

        return [
            'success' => true,
            'productos' => $productos,
            'variantes' => $variantes
        ];
    }

    // =======================================================
    // 🔹 3. Obtener variantes por producto (por cod_barras padre)
    // =======================================================
    public function getVariantesParaProductos(array $codBarrasProductos): array {
        if (empty($codBarrasProductos)) return [];

        // Asegura que el IN statement es seguro y eficiente
        $in = implode(',', array_fill(0, count($codBarrasProductos), '?'));
        $sql = "
            SELECT 
                v.id_variante,
                v.cod_barras,
                v.sku,
                v.color, -- ✅ Añadido
                v.talla, -- ✅ Añadido
                v.imagen, -- ✅ Añadido
                v.cantidad,
                v.cantidad_min, -- ✅ Añadido
                v.costo, -- ✅ Añadido
                v.precio
            FROM variantes v
            WHERE v.cod_barras IN ($in)
            ORDER BY v.id_variante ASC -- Ordenar por ID o alguna característica lógica
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($codBarrasProductos);
        $variantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $agrupadas = [];
        // Agrupar por el cod_barras del producto padre (la clave de unión)
        foreach ($variantes as $v) {
            $agrupadas[$v['cod_barras']][] = $v;
        }
        return $agrupadas;
    }

    // =======================================================
    // 🔹 4. Registrar ajuste de stock
    // =======================================================
    public function registrarAjusteStock(array $datos): array {
        try {
            // 1. Obtener y validar datos
            $codEntidad = $datos['cod_entidad'] ?? null;
            $cantidad = (int)($datos['cantidad'] ?? 0);
            $motivo = $datos['motivo'] ?? 'Ajuste manual';
            $referencia = $datos['referencia'] ?? null;
            $esVariante = filter_var($datos['ajusteEsVariante'] ?? 'false', FILTER_VALIDATE_BOOLEAN);

            $idUsuario = $_SESSION['user_id'] ?? $_SESSION['id_usuario'] ?? 1;

            if (!$codEntidad || $cantidad == 0) {
                return ['success' => false, 'message' => 'Datos insuficientes o cantidad inválida.'];
            }

            $tipoMovimiento = ($cantidad > 0) ? 'ENTRADA' : 'SALIDA';
            $cantidadAbs = abs($cantidad);
            
            // 2. Iniciar transacción
            $this->pdo->beginTransaction();

            // 3. ✅ CORRECCIÓN CRÍTICA: Obtener el ID principal para el historial
            $cod_barras_historial = $this->_getCodBarrasPrincipal($codEntidad, $esVariante);

            // 4. Registrar movimiento (usando el ID principal)
            $sqlMov = "
                INSERT INTO inventario_movimientos 
                (cod_barras, tipo_movimiento, cantidad_impactada, motivo, referencia, id_usuario) -- Se asume 'cod_barras' (plural)
                VALUES (?, ?, ?, ?, ?, ?)
            ";
            $stmtMov = $this->pdo->prepare($sqlMov);
            $stmtMov->execute([$cod_barras_historial, $tipoMovimiento, $cantidadAbs, $motivo, $referencia, $idUsuario]);

            // 5. Actualizar stock
            $tabla = $esVariante ? 'variantes' : 'productos';
            // Se usa SKU para variantes y cod_barras para productos (coherente con la entrada de datos)
            $columnaCod = $esVariante ? 'sku' : 'cod_barras'; 

            if ($tipoMovimiento === 'ENTRADA') {
                $sqlStock = "UPDATE $tabla SET cantidad = cantidad + ? WHERE $columnaCod = ?";
            } else {
                // ✅ CORRECCIÓN: Prevenir stock negativo usando GREATEST(0, ...)
                $sqlStock = "UPDATE $tabla SET cantidad = GREATEST(0, cantidad - ?) WHERE $columnaCod = ?";
            }
            
            $stmtStock = $this->pdo->prepare($sqlStock);
            $stmtStock->execute([$cantidadAbs, $codEntidad]);

            // 6. Finalizar transacción
            $this->pdo->commit();

            // 7. Obtener nuevo stock (usa el ID original del ajuste, no el ID principal)
            $nuevoStock = $this->_getNuevoStock($codEntidad, $esVariante);

            return ['success' => true, 'message' => 'Stock actualizado correctamente.', 'nuevo_stock' => $nuevoStock];

        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            error_log("Error en registrarAjusteStock: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al registrar ajuste.', 'error_detail' => $e->getMessage()];
        }
    }

    // =======================================================
    // 🔹 5. Obtener stock actualizado (PRIVADO)
    // =======================================================
    private function _getNuevoStock(string $codEntidad, bool $esVariante): int {
        $tabla = $esVariante ? 'variantes' : 'productos';
        // Usa SKU para variante, cod_barras para producto
        $columnaCod = $esVariante ? 'sku' : 'cod_barras'; 

        $stmt = $this->pdo->prepare("SELECT cantidad FROM $tabla WHERE $columnaCod = ? LIMIT 1");
        $stmt->execute([$codEntidad]);
        return (int)$stmt->fetchColumn();
    }

    // =======================================================
    // 🔹 6. Activar / Desactivar producto
    // =======================================================
    public function toggleProductoActivo(string $id, int $status): array {
        try {
            $sql = "UPDATE productos SET is_active = :status WHERE cod_barras = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':status' => $status, ':id' => $id]);

            $msg = $status ? 'Producto activado correctamente.' : 'Producto descatalogado.';
            return ['success' => true, 'message' => $msg];
        } catch (PDOException $e) {
            error_log("Error toggleProductoActivo: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al cambiar el estado del producto.'];
        }
    }

    // =======================================================
    // 🔹 7. Obtener historial de movimientos
    // =======================================================
    public function getHistorial(string $id_entidad, string $type): array {
        try {
            // ✅ CORRECCIÓN CRÍTICA: Obtener el ID principal para la consulta de historial
            $esVar = ($type === 'variante');
            $cod_barras_historial = $this->_getCodBarrasPrincipal($id_entidad, $esVar);

            $sql = "
                SELECT 
                    tipo_movimiento, 
                    cantidad_impactada AS cantidad,
                    motivo,
                    referencia,
                    fecha_movimiento
                FROM inventario_movimientos
                WHERE cod_barras = :id -- Usamos el cod_barras principal
                ORDER BY fecha_movimiento DESC
                LIMIT 50
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $cod_barras_historial]);
            $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'historial' => $historial];
        } catch (PDOException $e) {
            error_log("Error getHistorial: " . $e->getMessage());
            return ['success' => false, 'message' => 'No se pudo obtener el historial.', 'error_detail' => $e->getMessage()];
        }
    }
}