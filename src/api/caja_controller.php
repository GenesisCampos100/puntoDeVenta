<?php
// src/api/caja_controller.php
require_once __DIR__ . '/../config/db.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// Verificar autenticación básica
if (!isset($_SESSION['usuario_id']) && !isset($_SESSION['id_empleado'])) {
    echo json_encode(['status' => 'error', 'message' => 'No hay sesión activa']);
    exit;
}

$action = $_POST['action'] ?? '';
$id_empleado = $_SESSION['id_empleado'] ?? null;

// Si no hay id_empleado en sesión, buscarlo por usuario_id
if (!$id_empleado && isset($_SESSION['usuario_id'])) {
    $stmt = $pdo->prepare("SELECT id_empleado FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $id_empleado = $stmt->fetchColumn();
}

// Si aún no hay id_empleado (ej. usuario sin empleado asignado), manejar error o usar null
if (!$id_empleado) {
    // Opcional: permitir continuar si la acción no requiere id_empleado estricto, 
    // pero para movimientos de caja generalmente se requiere.
    // Por ahora, si es null, algunas DB podrían fallar si la columna es NOT NULL.
    // Asumiremos que se requiere.
    // Sin embargo, para fetch_totales no es estrictamente necesario el ID del empleado actual,
    // pero para registrar movimientos sí.
    if ($action !== 'fetch_totales') {
         echo json_encode(['status' => 'error', 'message' => 'Usuario no vinculado a un empleado']);
         exit;
    }
}

try {
    switch ($action) {
        case 'ingreso':
        case 'retiro':
            handleMovimiento($pdo, $action, $id_empleado);
            break;

        case 'fetch_totales':
            handleFetchTotales($pdo);
            break;

        case 'corte_caja':
            handleCorteCaja($pdo, $id_empleado);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

/**
 * Maneja Ingresos y Retiros Manuales
 */
function handleMovimiento($pdo, $type, $id_empleado) {
    $monto = floatval($_POST['monto'] ?? 0);
    $motivo = trim($_POST['motivo'] ?? '');
    $metodo = $_POST['metodo'] ?? 'EFECTIVO'; // Usar el método seleccionado en el formulario

    if ($monto <= 0) {
        throw new Exception("El monto debe ser mayor a 0");
    }

    if (empty($motivo)) {
        throw new Exception("El motivo es obligatorio");
    }

    // Si es retiro, convertir a negativo
    $montoFinal = ($type === 'retiro') ? ($monto * -1) : $monto;

    $stmt = $pdo->prepare("INSERT INTO caja_movimientos (monto, metodo, motivo, id_empleado, fecha_movimiento) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$montoFinal, $metodo, $motivo, $id_empleado]);

    echo json_encode(['status' => 'success', 'message' => 'Movimiento registrado correctamente']);
}

/**
 * Calcula los totales esperados desde el último corte
 */
function handleFetchTotales($pdo) {
    // 1. Obtener fecha del último corte
    $stmt = $pdo->query("SELECT MAX(fecha_corte) as ultimo_corte FROM cortes_caja");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $ultimoCorte = $row['ultimo_corte'];

    // 2. Sumar Pagos de Ventas (Ingresos por venta)
    $ventasEfectivo = getSum($pdo, "pagos_venta", "monto", "metodo = 'EFECTIVO'", "fecha_pago", $ultimoCorte);
    $ventasTarjeta = getSum($pdo, "pagos_venta", "monto", "metodo = 'TARJETA'", "fecha_pago", $ultimoCorte);

    // 3. Sumar Movimientos Manuales (Caja Chica)
    // Nota: caja_movimientos.monto ya tiene signo negativo para retiros.
    $movsEfectivo = getSum($pdo, "caja_movimientos", "monto", "metodo = 'EFECTIVO'", "fecha_movimiento", $ultimoCorte);
    $movsTarjeta = getSum($pdo, "caja_movimientos", "monto", "metodo = 'TARJETA'", "fecha_movimiento", $ultimoCorte);

    // 4. Totales Esperados
    $totalEfectivo = $ventasEfectivo + $movsEfectivo;
    $totalTarjeta = $ventasTarjeta + $movsTarjeta;

    echo json_encode([
        'status' => 'success',
        'efectivo_esperado' => round($totalEfectivo, 2),
        'tarjeta_esperado' => round($totalTarjeta, 2),
        'ultima_fecha_corte' => $ultimoCorte
    ]);
}

/**
 * Helper para sumar columnas con filtro de fecha opcional
 */
function getSum($pdo, $table, $sumCol, $whereStatic, $dateCol, $dateVal) {
    if ($dateVal) {
        // Si hay fecha de corte, filtrar registros posteriores a esa fecha
        $sql = "SELECT COALESCE(SUM($sumCol), 0) as total FROM $table WHERE $whereStatic AND $dateCol > ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$dateVal]);
    } else {
        // Si no hay fecha de corte, sumar todos los registros
        $sql = "SELECT COALESCE(SUM($sumCol), 0) as total FROM $table WHERE $whereStatic";
        $stmt = $pdo->query($sql);
    }
    
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    return floatval($res['total'] ?? 0);
}

/**
 * Registra el Corte de Caja
 */
function handleCorteCaja($pdo, $id_empleado) {
    $efectivo_esperado = floatval($_POST['efectivo_esperado'] ?? 0);
    $tarjeta_esperado = floatval($_POST['tarjeta_esperado'] ?? 0);
    $efectivo_contado = floatval($_POST['efectivo_contado'] ?? 0);
    $tarjeta_contado = floatval($_POST['tarjeta_contado'] ?? 0);
    $comentarios = $_POST['comentarios'] ?? '';

    // Cálculo de diferencia
    // Diferencia = (Lo que tengo) - (Lo que debería tener)
    $total_contado = $efectivo_contado + $tarjeta_contado;
    $total_esperado = $efectivo_esperado + $tarjeta_esperado;
    $diferencia = $total_contado - $total_esperado;

    $sql = "INSERT INTO cortes_caja (
                id_empleado, 
                efectivo_esperado, 
                tarjeta_esperado, 
                efectivo_contado, 
                tarjeta_contado, 
                diferencia, 
                comentarios, 
                fecha_corte
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $id_empleado,
        $efectivo_esperado,
        $tarjeta_esperado,
        $efectivo_contado,
        $tarjeta_contado,
        $diferencia,
        $comentarios
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Corte de caja registrado',
        'diferencia' => round($diferencia, 2)
    ]);
}
