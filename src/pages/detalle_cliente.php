<?php
// pages/detalle_cliente.php
require_once __DIR__ . '/../config/db.php';


// -------------------------
// Mostrar errores (opcional, quitar en producción)
// ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);

// -------------------------
// Validar ID (GET)
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='p-6'><h2>ID de cliente inválido.</h2><a href='index.php?view=clientes'>Volver</a></div>";
    exit;
}

$id = intval($_GET['id']);

// -------------------------
// Obtener cliente
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id_cliente = :id LIMIT 1");
$stmt->execute(['id' => $id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    echo "<div class='p-6'><h2>Cliente no encontrado.</h2><a href='index.php?view=clientes'>Volver</a></div>";
    exit;
}

// -------------------------
// Obtener compras (ventas) + pagos agregados por venta
// Traemos total de la venta y suma de pagos (monto_pagado) y métodos concatenados
$stmt2 = $pdo->prepare("
    SELECT
      v.id_venta,
      v.fecha,
      v.subtotal,
      v.descuento_general,
      v.total,
      GROUP_CONCAT(DISTINCT p.metodo SEPARATOR ', ') AS metodo_pago,
      IFNULL(SUM(p.monto), 0) AS monto_pagado
    FROM ventas v
    LEFT JOIN pagos_venta p ON p.id_venta = v.id_venta
    WHERE v.id_cliente = :id
    GROUP BY v.id_venta, v.fecha, v.subtotal, v.descuento_general, v.total
    ORDER BY v.fecha DESC
");
$stmt2->execute(['id' => $id]);
$compras = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// -------------------------
// Total gastado (sumando total desde ventas)
$stmtSum = $pdo->prepare("SELECT IFNULL(SUM(total),0) as total FROM ventas WHERE id_cliente = :id");
$stmtSum->execute(['id' => $id]);
$totalGastado = (float)$stmtSum->fetchColumn();

// -------------------------
// Gasto por mes últimos 12 meses (SUM(total))
$stmtMonths = $pdo->prepare("
    SELECT DATE_FORMAT(fecha, '%Y-%m') as ym, DATE_FORMAT(fecha,'%b %Y') as label, IFNULL(SUM(total),0) as total
    FROM ventas
    WHERE id_cliente = :id
    GROUP BY ym
    ORDER BY ym ASC
    LIMIT 12
");
$stmtMonths->execute(['id' => $id]);
$monthsRaw = $stmtMonths->fetchAll(PDO::FETCH_ASSOC);

$chartLabels = [];
$chartData = [];
foreach ($monthsRaw as $m) {
    $chartLabels[] = $m['label'];
    $chartData[] = (float)$m['total'];
}

// Avatar iniciales
$iniciales = strtoupper((substr($cliente['nombre'] ?? '',0,1) . substr($cliente['apellido_paterno'] ?? '',0,1)) ?: 'CL');
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Detalle cliente — <?= htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellido_paterno']) ?></title>

<!-- Tailwind -->
<script src="https://cdn.tailwindcss.com"></script>

<!-- ApexCharts -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
:root{
  --verde: #b4c24d;
  --azul:  #2d4353;
  --rosa:  #e15871;
  --gris:  #eeeeee;
  --font: 'Poppins', sans-serif;
}
body { font-family: var(--font); background: linear-gradient(180deg,#f8fafc 0%, #f1f5f9 100%); color: #0f172a; }
.card { background: white; border-radius: 14px; padding: 1.5rem; box-shadow: 0 12px 30px rgba(2,6,23,0.06); }
.avatar { width:100px; height:100px; border-radius:9999px; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:32px; color:white; }
.btn-danger { background: var(--rosa); color: white; }
.btn-primary { background: var(--azul); color: white; }
.stat { border-radius:12px; padding:16px; background: linear-gradient(180deg, #ffffff, #fbfdff); box-shadow: 0 6px 18px rgba(45,67,83,0.06); }
.table th { text-align:left; font-weight:600; padding:12px 14px; color:#0f172a; background:#fafafa; border-bottom:1px solid #e6e6e6; }
.table td { padding:12px 14px; border-bottom:1px solid #f1f1f1; }
@media (min-width:1024px) {
  .layout-grid { display:grid; grid-template-columns: 400px 1fr; gap:24px; }
}

/* Estilos modo oscuro */
body.dark-mode main,
body.dark-mode .content {
    background-color: #121212 !important;
}
/* En modo oscuro: textos dentro de .stat en negro */
body.dark .stat {
  color: #000000; /* negro puro */
}

/* En modo oscuro: quitar los colores específicos en elementos hijos que pongan grises */
/* Por ejemplo: texto pequeño que puede tener color gris */
body.dark .stat .text-gray-500,
body.dark .stat .text-gray-400,
body.dark .stat .text-gray-600 {
  color: inherit; /* hereda el negro del contenedor .stat */
}

/* En modo oscuro: encabezado de tabla color gris oscuro */
body.dark .table th {
  color: #374151; /* gris oscuro */
}

body.dark .card {
  background: #1e293b; /* gris oscuro */
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.6);
  color: #cbd5e1;
}

body.dark .avatar {
  color: #e0e0e0;
}

body.dark .btn-danger {
  background: #be123c; /* rojo oscuro */
  color: #fef2f2;
}
/* BOTÓN volver EN MODO OSCURO */
body.dark-mode .btn-volver {
  background-color: transparent !important;
}

body.dark-mode .btn-volver:hover {
  background-color: transparent !important;
}

body.dark .btn-primary {
  background: #2563eb; /* azul oscuro */
  color: #dbeafe;
}

body.dark .stat {
  background: linear-gradient(180deg, #334155, #1e293b);
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.6);
  color: #cbd5e1;
}

body.dark .table th {
  color: #e2e8f0;
  background: #334155;
  border-bottom: 1px solid #475569;
}

body.dark .table td {
  border-bottom: 1px solid #475569;
  color: #cbd5e1;
}

body.dark a {
  color: #93c5fd; /* azul claro */
}

body.dark a:hover {
  color: #bfdbfe;
}

/* Modal modo oscuro */
body.dark #modalTW {
  background-color: rgba(15, 23, 42, 0.8);
}

body.dark #modalTWContent {
  background-color: #1e293b;
  color: #cbd5e1;
}

body.dark #modalTWContent button {
  background-color: #2563eb;
  color: white;
}

body.dark #modalTWContent button:hover {
  background-color: #1e40af;
}
body.dark-mode .btn-eliminar:hover {
    background-color: #dc2626 !important;
}

/* BOTÓN AGREGAR EN MODO OSCURO */
body.dark-mode .btn-eliminar {
  background-color: #dc2626 !important;
}
//* Modo oscuro: fondo oscuro para la tarjeta que contiene las stat */
body.dark-mode aside .card {
  background-color: #1e293b; /* azul/gris oscuro */
  box-shadow: 0 12px 30px rgba(0,0,0,0.6);
  color: #f9fafb; /* texto blanco */
}

/* Modo oscuro: las cajas stat con fondo un poco más claro y texto blanco */
body.dark-mode aside .stat {
  background: #334155; /* fondo gris oscuro */
  color: #f9fafb !important; /* texto blanco */
  box-shadow: 0 6px 18px rgba(0,0,0,0.6);
}

/* Forzar que textos con clase gris hereden el color blanco */
body.dark-mode aside .stat .text-gray-500,
body.dark-mode aside .stat .text-gray-400,
body.dark-mode aside .stat .text-gray-600 {
  color: inherit !important;
}
body.dark-mode aside p.text-gray-600,
body.dark-mode aside p.text-gray-500 {
  color: #cbd5e1 !important; /* gris claro */
}
/* Modo oscuro: fondo oscuro y texto claro en encabezado de la tabla compras */
body.dark-mode .card.mb-10 table thead tr th {
  background-color: #334155; /* fondo gris oscuro */
  color: #f9fafb;            /* texto blanco */
  border-bottom: 1px solid #475569;
}
/* Fondo semi-transparente del overlay: ya es negro con opacidad, está bien */

/* Contenedor principal del modal */
body.dark-mode #modalTWContent {
  background-color: #121212;  /* fondo azul/gris oscuro */
  color: #f9fafb;             /* texto blanco */
  box-shadow: 0 12px 30px rgba(0,0,0,0.6);
}

/* Encabezado */
body.dark-mode #modalTWContent > div.flex.justify-between {
  border-color: #475569;
}

body.dark-mode #modalTWContent h2 {
  color: #f9fafb;
}

/* Botón cerrar (cruz) */
body.dark-mode #modalTWContent button.text-gray-500 {
  color: #cbd5e1;
}

body.dark-mode #modalTWContent button.text-gray-500:hover {
  color: #e2e8f0;
}

/* Contenido con loader */
body.dark-mode #contenidoTW .animate-spin {
  border-top-color: #cbd5e1; /* hacer que el spinner sea claro */
}

/* Pie de modal (botón cerrar) */
body.dark-mode #modalTWContent > div.flex.justify-end {
  border-top-color: #475569;
}

body.dark-mode #modalTWContent > div.flex.justify-end button {
  background-color: #334155;
  color: #f9fafb;
  transition: background-color 0.3s;
}

body.dark-mode #modalTWContent > div.flex.justify-end button:hover {
  background-color: #475569;
}

</style>
</head>

<body class="p-6">
<div class="max-w-7xl mx-auto">

  <!-- HEADER -->
  <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 mb-4">
    <div>
      <h1 class="text-2xl font-bold">Detalle del cliente</h1>
      <p class="text-sm text-gray-600 mt-1">Información completa y resumen de compras.</p>
    </div>

    <div class="flex gap-2">
      <a href="index.php?view=clientes" class="px-4 py-2 rounded-lg border hover:bg-gray-50 flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
        Volver
      </a>

      <button id="btnEliminar" class="px-4 py-2 rounded-lg btn-danger hover:opacity-95 flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3" /></svg>
        Eliminar
      </button>
    </div>
  </div>

  <!-- LAYOUT -->
  <div class="layout-grid">

    <!-- LEFT: perfil + stats -->
    <aside>
      <div class="card mb-4">
        <div class="flex items-center gap-4">
          <div class="avatar" style="background: linear-gradient(135deg,#2d4353,#b4c24d);">
            <?= htmlspecialchars($iniciales) ?>
          </div>

          <div>
            <h2 class="text-lg font-bold"><?= htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellido_paterno'] . ' ' . $cliente['apellido_materno']) ?></h2>
            <p class="text-sm text-gray-600"><?= htmlspecialchars($cliente['correo'] ?: '-') ?></p>
            <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($cliente['celular'] ?: '-') ?></p>
          </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-3">
          <div class="stat flex items-center justify-between">
            <div>
              <div class="text-sm text-gray-500">Total gastado</div>
              <div class="text-2xl font-semibold">$<?= number_format($totalGastado,2) ?></div>
            </div>
            <div class="text-xs text-gray-400">MXN</div>
          </div>

          <div class="stat">
            <div class="text-sm text-gray-500">Dirección</div>
            <div class="mt-1 text-sm">
              <?= htmlspecialchars(trim(($cliente['calle'] ?? '') . ' ' . ($cliente['num_ext'] ?? '') . ' ' . ($cliente['num_int'] ?? ''))) ?><br>
              <?= htmlspecialchars(trim(($cliente['colonia'] ?? '') . ' ' . ($cliente['cp'] ?? '') . ' ' . ($cliente['estado'] ?? ''))) ?>
            </div>
          </div>
        </div>
      </div>
    </aside>

    <!-- RIGHT: main content -->
    <main>
      <!-- CHART + METRICS -->
      <div class="card mb-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold">Gasto por mes</h3>
          <div class="text-sm text-gray-500">Últimos meses</div>
        </div>

        <div id="chart" style="height: 340px;"></div>
      </div>

    <!-- COMPRAS TABLE -->
    <div class="card mb-10">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold">Compras</h3>
        <div class="text-sm text-gray-500"><?= count($compras) ?> registros</div>
    </div>

    <?php if (count($compras) === 0): ?>
        <p class="text-gray-600">Este cliente aún no tiene compras registradas.</p>
    <?php else: ?>

        <?php 
        // Ordenar por fecha ASC (más antigua primero)
        usort($compras, function($a, $b) {
            return strtotime($a['fecha']) - strtotime($b['fecha']);
        });

        $i = 1; // Enumeración
        ?>

        <div class="overflow-x-auto">
        <table class="table w-full">
            <thead>
            <tr>
                <th>#</th>
                <th>Fecha</th>
                <th>Método pago</th>
                <th>Total</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($compras as $c): ?>
                <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($c['fecha']))) ?></td>
                <td><?= htmlspecialchars($c['metodo_pago'] ?? '-') ?></td>
                <td>$<?= number_format($c['total'],2) ?></td>
                <td class="text-right">
                    <a href="#" 
                    class="btnVerVenta text-blue-600 hover:underline inline-flex items-center gap-2"
                    data-id="<?= $c['id_venta'] ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        Ver
                    </a>
                </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>

    <?php endif; ?>
    </div>




    </main>

  </div>
</div>

<!-- MODAL TAILWIND -->
<div id="modalTW" class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-[999] opacity-0 pointer-events-none transition-opacity duration-300">
    <div id="modalTWContent" class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl p-6 scale-90 transition-transform duration-300">
        
        <div class="flex justify-between items-center border-b pb-3">
            <h2 class="text-xl font-bold">Detalle de Venta</h2>
            <button onclick="cerrarModalTW()" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
        </div>

        <div id="contenidoTW" class="py-6">
            <div class="flex justify-center py-6">
                <div class="animate-spin h-10 w-10 border-t-4 border-gray-700 rounded-full"></div>
            </div>
        </div>

        <div class="flex justify-end border-t pt-3 mt-3">
            <button onclick="cerrarModalTW()" class="px-4 py-2 rounded-xl bg-gray-800 text-white hover:bg-gray-700 transition">
                Cerrar
            </button>
        </div>

    </div>
</div>



<script>
const chartLabels = <?= json_encode($chartLabels, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>;
const chartData = <?= json_encode($chartData, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>;

const options = {
  chart: {
    type: 'bar',
    height: 340,
    toolbar: { show: false },
    animations: { enabled: true, easing: 'easeout', speed: 600 }
  },
  series: [{ name: 'Gasto', data: chartData }],
  xaxis: { categories: chartLabels, labels: { rotate: -45 } },
  yaxis: { labels: { formatter: val => '$' + val.toLocaleString() } },
  tooltip: { y: { formatter: val => '$' + parseFloat(val).toFixed(2) } },
  colors: ['#2d4353'],
  grid: { borderColor: '#f1f5f9' },
  dataLabels: { enabled: false },
};

const chart = new ApexCharts(document.querySelector("#chart"), options);
chart.render();

// ----------------------
// Eliminar cliente
document.getElementById('btnEliminar').addEventListener('click', function(){
  Swal.fire({
    title: 'Eliminar cliente',
    html: `¿Seguro que deseas eliminar a <strong><?= htmlspecialchars(addslashes($cliente['nombre'])) ?></strong>?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    confirmButtonColor: 'var(--rosa)',
    cancelButtonText: 'Cancelar'
  }).then((res) => {
    if (!res.isConfirmed) return;

    fetch(window.location.href, {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: new URLSearchParams({ action: 'delete', id: '<?= $cliente['id_cliente'] ?>' })
    })
    .then(r => r.json())
    .then(json => {
      if (!json.success) {
        Swal.fire('Error', json.error || 'No se pudo eliminar', 'error');
        return;
      }
      Swal.fire({ title: 'Eliminado', text: 'Cliente eliminado correctamente', icon: 'success', confirmButtonColor: 'var(--verde)' })
        .then(()=> { window.location.href = json.redirect || 'index.php?view=clientes'; });
    })
    .catch(err => Swal.fire('Error', 'Error en el servidor: ' + err, 'error'));
  });
});


function abrirModalTW() {
    const modal = document.getElementById('modalTW');
    const content = document.getElementById('modalTWContent');

    modal.classList.remove("opacity-0", "pointer-events-none");
    setTimeout(() => {
        content.classList.remove("scale-90");
        content.classList.add("scale-100");
    }, 10);
}

function cerrarModalTW() {
    const modal = document.getElementById('modalTW');
    const content = document.getElementById('modalTWContent');

    content.classList.add("scale-90");
    content.classList.remove("scale-100");

    setTimeout(() => {
        modal.classList.add("opacity-0", "pointer-events-none");
    }, 200);
}

// EVENTO PARA VER DETALLE
document.querySelectorAll('.btnVerVenta').forEach(btn => {
    btn.addEventListener('click', function (e) {
        e.preventDefault();

        const idVenta = this.getAttribute('data-id');

        document.getElementById("contenidoTW").innerHTML = `
            <div class="flex justify-center py-6">
                <div class="animate-spin h-10 w-10 border-t-4 border-gray-700 rounded-full"></div>
            </div>
        `;

        // Cargar modal
        fetch('/puntoDeVenta/src/pages/detalle_venta_modal.php?id=' + idVenta)
            .then(res => res.text())
            .then(html => {
                document.getElementById("contenidoTW").innerHTML = html;
            });

        abrirModalTW();
    });
});
</script>

</body>
</html>
