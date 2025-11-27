<?php
// src/api/caja_controller.php
require_once __DIR__ . '/../config/db.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// Verificar autenticación básica
if (!isset($_SESSION['id_empleado'])) {
    echo json_encode(['status' => 'error', 'message' => 'No hay sesión activa']);
    exit;
}

$action = $_POST['action'] ?? '';
$id_empleado = $_SESSION['id_empleado'];

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
    $motivo = $_POST['motivo'] ?? '';
    $metodo = 'EFECTIVO'; // Por defecto, movimientos manuales suelen ser efectivo, pero podría ser parametrizable si se requiere.
                          // El prompt dice "ingreso / retiro -> Inserción en caja_movimientos".
                          // Asumiremos EFECTIVO para caja chica, salvo que se especifique lo contrario.
                          // Revisando tabla: metodo enum('EFECTIVO','TARJETA').
                          // Generalmente retiros/ingresos manuales son de efectivo.

    if ($monto <= 0) {
        throw new Exception("El monto debe ser mayor a 0");
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

    // Filtro de fecha para las consultas
    $fechaFilter = $ultimoCorte ? "WHERE fecha_pago > '$ultimoCorte'" : "";
    $fechaFilterMov = $ultimoCorte ? "WHERE fecha_movimiento > '$ultimoCorte'" : "";

    // 2. Sumar Pagos de Ventas (Ingresos por venta)
    // EFECTIVO
    $sqlVentasEf = "SELECT SUM(monto) as total FROM pagos_venta $fechaFilter AND metodo = 'EFECTIVO'";
    if (!$ultimoCorte) $sqlVentasEf = "SELECT SUM(monto) as total FROM pagos_venta WHERE metodo = 'EFECTIVO'"; // Si no hay corte, todo
    
    // Ajuste: La lógica de $fechaFilter arriba estaba un poco simplificada. Hagámoslo bien con params o string directo si es seguro (aquí viene de DB, es seguro).
    // Mejor usemos lógica condicional clara.
    
    $ventasEfectivo = getSum($pdo, "pagos_venta", "monto", "metodo = 'EFECTIVO'", "fecha_pago", $ultimoCorte);
    $ventasTarjeta = getSum($pdo, "pagos_venta", "monto", "metodo = 'TARJETA'", "fecha_pago", $ultimoCorte);

    // 3. Sumar Movimientos Manuales (Caja Chica)
    // Nota: caja_movimientos.monto ya tiene signo negativo para retiros.
    $movsEfectivo = getSum($pdo, "caja_movimientos", "monto", "metodo = 'EFECTIVO'", "fecha_movimiento", $ultimoCorte);
    $movsTarjeta = getSum($pdo, "caja_movimientos", "monto", "metodo = 'TARJETA'", "fecha_movimiento", $ultimoCorte);

    // 4. Totales Esperados
    // Se asume un fondo inicial? El prompt no menciona tabla de fondos iniciales, solo "ingresos/retiros".
    // Asumiremos que el "saldo" es la suma de todo lo ocurrido desde el corte.
    
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
    $sql = "SELECT SUM($sumCol) as total FROM $table WHERE $whereStatic";
    if ($dateVal) {
        $sql .= " AND $dateCol > ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$dateVal]);
    } else {
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
