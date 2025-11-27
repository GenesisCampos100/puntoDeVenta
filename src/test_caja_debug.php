<?php
// Script de prueba para verificar que las ventas se están consultando correctamente
require_once __DIR__ . '/config/db.php';

echo "<h2>Diagnóstico de Caja - Verificación de Ventas</h2>";

// 1. Verificar último corte
$stmt = $pdo->query("SELECT MAX(fecha_corte) as ultimo_corte FROM cortes_caja");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$ultimoCorte = $row['ultimo_corte'];

echo "<p><strong>Último corte:</strong> " . ($ultimoCorte ?? 'Sin cortes previos') . "</p>";

// 2. Consultar ventas en efectivo
if ($ultimoCorte) {
    $sql = "SELECT COALESCE(SUM(monto), 0) as total FROM pagos_venta WHERE metodo = 'EFECTIVO' AND fecha_pago > ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ultimoCorte]);
} else {
    $sql = "SELECT COALESCE(SUM(monto), 0) as total FROM pagos_venta WHERE metodo = 'EFECTIVO'";
    $stmt = $pdo->query($sql);
}
$ventasEfectivo = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

echo "<p><strong>Ventas en efectivo:</strong> $" . number_format($ventasEfectivo, 2) . "</p>";

// 3. Consultar ventas con tarjeta
if ($ultimoCorte) {
    $sql = "SELECT COALESCE(SUM(monto), 0) as total FROM pagos_venta WHERE metodo = 'TARJETA' AND fecha_pago > ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ultimoCorte]);
} else {
    $sql = "SELECT COALESCE(SUM(monto), 0) as total FROM pagos_venta WHERE metodo = 'TARJETA'";
    $stmt = $pdo->query($sql);
}
$ventasTarjeta = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

echo "<p><strong>Ventas con tarjeta:</strong> $" . number_format($ventasTarjeta, 2) . "</p>";

// 4. Mostrar últimas 5 ventas
echo "<h3>Últimas 5 ventas registradas:</h3>";
$stmt = $pdo->query("SELECT id_pago, monto, metodo, fecha_pago FROM pagos_venta ORDER BY fecha_pago DESC LIMIT 5");
$ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($ventas)) {
    echo "<p>No hay ventas registradas.</p>";
} else {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Monto</th><th>Método</th><th>Fecha</th></tr>";
    foreach ($ventas as $v) {
        echo "<tr>";
        echo "<td>" . $v['id_pago'] . "</td>";
        echo "<td>$" . number_format($v['monto'], 2) . "</td>";
        echo "<td>" . $v['metodo'] . "</td>";
        echo "<td>" . $v['fecha_pago'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 5. Verificar movimientos de caja
echo "<h3>Movimientos de caja registrados:</h3>";
if ($ultimoCorte) {
    $stmt = $pdo->prepare("SELECT * FROM caja_movimientos WHERE fecha_movimiento > ? ORDER BY fecha_movimiento DESC LIMIT 5");
    $stmt->execute([$ultimoCorte]);
} else {
    $stmt = $pdo->query("SELECT * FROM caja_movimientos ORDER BY fecha_movimiento DESC LIMIT 5");
}
$movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($movimientos)) {
    echo "<p>No hay movimientos de caja registrados.</p>";
} else {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Monto</th><th>Método</th><th>Motivo</th><th>Fecha</th></tr>";
    foreach ($movimientos as $m) {
        echo "<tr>";
        echo "<td>" . $m['id_movimiento'] . "</td>";
        echo "<td>$" . number_format($m['monto'], 2) . "</td>";
        echo "<td>" . $m['metodo'] . "</td>";
        echo "<td>" . htmlspecialchars($m['motivo']) . "</td>";
        echo "<td>" . $m['fecha_movimiento'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>
