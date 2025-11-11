<?php
// =======================================================
// Archivo: api/ProductoModelo.php
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

    // Constructor
    public function __construct(PDO $pdo_instance) {
        $this->pdo = $pdo_instance;
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    // =======================================================
    // 🔹 1. Obtener categorías
    // =======================================================
    public function getCategorias(): array {
        $sql = "SELECT id_categoria, nombre_categoria FROM categorias ORDER BY nombre_categoria ASC";
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
        $mostrar_inactivos = $params['mostrar_inactivos'] ?? false;

        $where = [];
        $values = [];

        // Filtros dinámicos
        if ($busqueda !== '') {
            $where[] = "(p.nom_producto LIKE ? OR p.cod_barras LIKE ?)";
            $values[] = "%$busqueda%";
            $values[] = "%$busqueda%";
        }

        if ($categoria !== '') {
            $where[] = "p.id_categoria = ?";
            $values[] = $categoria;
        }

        if (!$mostrar_inactivos) {
            $where[] = "p.is_active = 1";
        }

        $whereSQL = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $orderSQL = $this->allowedOrderColumns[$orden] ?? $this->allowedOrderColumns['nom_asc'];

        // Consulta principal
        $sql = "
            SELECT 
                p.id_producto,
                p.cod_barras,
                p.nom_producto,
                p.precio,
                p.cantidad,
                p.is_active,
                c.nombre_categoria,
                (SELECT COUNT(*) FROM variantes v WHERE v.id_producto = p.id_producto) AS tiene_variante
            FROM productos p
            LEFT JOIN categorias c ON c.id_categoria = p.id_categoria
            $whereSQL
            ORDER BY $orderSQL
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Variantes asociadas
        $variantes = $this->getVariantesParaProductos(array_column($productos, 'id_producto'));

        return [
            'success' => true,
            'productos' => $productos,
            'variantes' => $variantes
        ];
    }

    // =======================================================
    // 🔹 3. Obtener variantes por producto
    // =======================================================
    public function getVariantesParaProductos(array $idsProductos): array {
        if (empty($idsProductos)) return [];

        $in = implode(',', array_fill(0, count($idsProductos), '?'));
        $sql = "
            SELECT 
                v.id_variante,
                v.id_producto,
                v.sku,
                v.descripcion,
                v.precio,
                v.cantidad
            FROM variantes v
            WHERE v.id_producto IN ($in)
            ORDER BY v.descripcion ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($idsProductos);
        $variantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $agrupadas = [];
        foreach ($variantes as $v) {
            $agrupadas[$v['id_producto']][] = $v;
        }
        return $agrupadas;
    }

    // =======================================================
    // 🔹 4. Registrar ajuste de stock
    // =======================================================
    public function registrarAjusteStock(array $datos): array {
        try {
            $codEntidad = $datos['cod_entidad'] ?? null;
            $cantidad = (int)($datos['cantidad'] ?? 0);
            $motivo = $datos['motivo'] ?? 'Ajuste manual';
            $referencia = $datos['referencia'] ?? null;
            $esVariante = filter_var($datos['ajusteEsVariante'] ?? 'false', FILTER_VALIDATE_BOOLEAN);

            $idUsuario = $_SESSION['user_id'] ?? 1;

            if (!$codEntidad || $cantidad == 0) {
                return ['success' => false, 'message' => 'Datos insuficientes o cantidad inválida.'];
            }

            $tipoMovimiento = ($cantidad > 0) ? 'ENTRADA' : 'SALIDA';
            $cantidadAbs = abs($cantidad);
            $operador = ($cantidad > 0) ? '+' : '-';

            $this->pdo->beginTransaction();

            // Registro movimiento
            $sqlMov = "
                INSERT INTO inventario_movimientos 
                (cod_barra, tipo_movimiento, cantidad_impactada, motivo, referencia, id_usuario)
                VALUES (?, ?, ?, ?, ?, ?)
            ";
            $stmtMov = $this->pdo->prepare($sqlMov);
            $stmtMov->execute([$codEntidad, $tipoMovimiento, $cantidadAbs, $motivo, $referencia, $idUsuario]);

            // Actualizar stock
            $tabla = $esVariante ? 'variantes' : 'productos';
            $columnaCod = $esVariante ? 'sku' : 'cod_barras';
            $sqlStock = "UPDATE $tabla SET cantidad = cantidad $operador ? WHERE $columnaCod = ?";
            $stmtStock = $this->pdo->prepare($sqlStock);
            $stmtStock->execute([$cantidadAbs, $codEntidad]);

            $this->pdo->commit();

            $nuevoStock = $this->getNuevoStock($codEntidad, $esVariante);

            return ['success' => true, 'message' => 'Stock actualizado correctamente.', 'nuevo_stock' => $nuevoStock];

        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            error_log("Error en registrarAjusteStock: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al registrar ajuste.'];
        }
    }

    // =======================================================
    // 🔹 5. Obtener stock actualizado
    // =======================================================
    private function getNuevoStock(string $codEntidad, bool $esVariante): int {
        $tabla = $esVariante ? 'variantes' : 'productos';
        $columnaCod = $esVariante ? 'sku' : 'cod_barras';

        $stmt = $this->pdo->prepare("SELECT cantidad FROM $tabla WHERE $columnaCod = ?");
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
    public function getHistorial(string $id, string $type): array {
        $sql = "
            SELECT 
                tipo_movimiento, 
                cantidad_impactada AS cantidad,
                motivo,
                referencia,
                fecha_movimiento
            FROM inventario_movimientos
            WHERE cod_barra = :id
            ORDER BY fecha_movimiento DESC
            LIMIT 50
        ";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['success' => true, 'historial' => $historial];
        } catch (PDOException $e) {
            error_log("Error getHistorial: " . $e->getMessage());
            return ['success' => false, 'message' => 'No se pudo obtener el historial.'];
        }
    }
}
