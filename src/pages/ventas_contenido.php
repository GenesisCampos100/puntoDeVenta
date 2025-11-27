<!-- Archivo: ventas_contenido.php -->
<?php
require_once __DIR__ . '/../config/translation.php';
require_once __DIR__ . '/../config/db.php';

$sql = "
    SELECT 
        v.id_venta,
        v.fecha,
        v.total AS pago_total,
        CONCAT(e.nombre, ' ', e.apellido_paterno, ' ', e.apellido_materno) AS nombre_empleado
    FROM ventas v
    LEFT JOIN empleados e ON v.id_empleado = e.id_empleado
    ORDER BY v.fecha DESC
";
$stmt = $pdo->query($sql);
$ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ventas Realizadas</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
/* ----------- MODO OSCURO ----------- */
body.dark-mode {
    background-color: #121212 !important;
    color: #ffffff !important;
}

/* Forzar todos los textos a blanco */

body.dark-mode .content,
body.dark-mode main {
    background-color: #121212 !important;
}


/* Tarjetas */
body.dark-mode .bg-white {
    background: #1e293b !important;
    color: #e2e8f0 !important;
}

body.dark-mode .text-gray-800,
body.dark-mode .text-gray-900 {
    color: #f1f5f9 !important;
}

body.dark-mode .text-gray-700,
body.dark-mode .text-gray-600 {
    color: #cbd5e1 !important;
}

/* Inputs */
body.dark-mode input,
body.dark-mode select {
    background: #1e293b !important;
    border-color: #334155 !important;
    color: #f1f5f9 !important;
}

body.dark-mode input::placeholder {
    color: #94a3b8 !important;
}

/* Tabla */
body.dark-mode table {
    color: #e2e8f0;
}

body.dark-mode thead {
    background: linear-gradient(to right, #0f172a, #1e293b) !important;
}

body.dark-mode tbody tr:hover {
    background: #1e293b !important;
}

/* Celdas */
body.dark-mode td {
    border-color: #334155 !important;
}

/* Botones */
body.dark-mode button {
    border-color: #475569 !important;
}

body.dark-mode .pagination-btn {
    background: #1e293b !important;
    color: #e2e8f0 !important;
}

body.dark-mode .pagination-btn:hover {
    background: #334155 !important;
}

/* Modales */
body.dark-mode #venta-modal .bg-white,
body.dark-mode #ticket-modal .bg-white {
    background: #1e293b !important;
    color: #f1f5f9 !important;
}

/* SweetAlert2 */
body.dark-mode .swal2-popup {
    background: #1e293b !important;
    color: #f1f5f9 !important;
}

body.dark-mode .swal2-title,
body.dark-mode .swal2-text {
    color: #f8fafc !important;
}

</style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen p-6"> 

<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-800 mb-2">Ventas Realizadas</h1>
        <p class="text-gray-600">Gestiona y visualiza todas las ventas registradas</p>
    </div>

    <!-- Controles superiores -->
    <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            
            <!-- Barra de búsqueda -->
            <div class="flex-1 relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text" 
                       id="searchVenta" 
                       placeholder="Buscar por ID, empleado o fecha..." 
                       class="w-full pl-12 pr-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none transition-all">
            </div>

            <!-- Filtros -->
            <div class="flex gap-3">
                <select id="filterOrden" class="px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none bg-white font-medium text-gray-700">
                    <option value="fecha_desc"> Fecha (Recientes)</option>
                    <option value="fecha_asc"> Fecha (Antiguas)</option>
                    <option value="total_desc"> Total (Mayor)</option>
                    <option value="total_asc"> Total (Menor)</option>
                </select>
                
                <button id="resetFilters" class="px-4 py-3 rounded-xl bg-gray-200 hover:bg-gray-300 font-medium text-gray-700 transition-all">
                     Resetear
                </button>
            </div>
        </div>
        
        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-4">
                <p class="text-sm text-blue-600 font-semibold mb-1">Total Ventas</p>
                <p class="text-2xl font-bold text-blue-700" id="totalVentas"><?= count($ventas) ?></p>
            </div>
            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-4">
                <p class="text-sm text-green-600 font-semibold mb-1">Ingresos Totales</p>
                <p class="text-2xl font-bold text-green-700" id="ingresosTotales">$<?= number_format(array_sum(array_column($ventas, 'pago_total')), 2) ?></p>
            </div>
            <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-4">
                <p class="text-sm text-purple-600 font-semibold mb-1">Mostrando</p>
                <p class="text-2xl font-bold text-purple-700" id="ventasMostradas">0 de <?= count($ventas) ?></p>
            </div>
        </div>
    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-slate-700 to-slate-800 text-white">
                    <tr>
                        <th class="px-6 py-4 text-left font-semibold">Empleado</th>
                        <th class="px-6 py-4 text-left font-semibold">Fecha</th>
                        <th class="px-6 py-4 text-right font-semibold">Total</th>
                        <th class="px-6 py-4 text-center font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tablaVentas" class="divide-y divide-gray-100">
                    <?php foreach($ventas as $v): ?>
                    <tr class="table-row-hover" data-fecha="<?= $v['fecha'] ?>" data-total="<?= $v['pago_total'] ?>">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 bg-gradient-to-br from-indigo-400 to-purple-500 rounded-full flex items-center justify-center text-white font-bold">
                                    <?= strtoupper(substr($v['nombre_empleado'], 0, 1)) ?>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($v['nombre_empleado']) ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            <?= date('d/m/Y H:i', strtotime($v['fecha'])) ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-lg font-bold text-green-600">$<?= number_format($v['pago_total'], 2) ?></span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-2">
                                <button class="ver-detalle-btn px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all shadow-md hover:shadow-lg font-medium" 
                                        data-id="<?= $v['id_venta'] ?>">
                                    Ver
                                </button>
                                <button class="delete-sale-btn px-4 py-2 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-lg hover:from-red-600 hover:to-red-700 transition-all shadow-md hover:shadow-lg font-medium" 
                                        data-id="<?= $v['id_venta'] ?>">
                                    Eliminar
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Paginación -->
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-700">
                        Mostrando <span class="font-medium" id="rangoInicio">1</span> a <span class="font-medium" id="rangoFin">20</span> de <span class="font-medium" id="totalRegistros"><?= count($ventas) ?></span> ventas
                    </p>
                </div>
                <div class="flex gap-2">
                    <button id="prevPage" class="pagination-btn px-4 py-2 bg-white border-2 border-gray-300 rounded-lg hover:bg-gray-50 font-medium text-gray-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                        ← Anterior
                    </button>
                    <div id="pageNumbers" class="flex gap-1"></div>
                    <button id="nextPage" class="pagination-btn px-4 py-2 bg-white border-2 border-gray-300 rounded-lg hover:bg-gray-50 font-medium text-gray-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                        Siguiente →
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal detalle de venta -->
<div id="venta-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-2xl m-4">
        <h2 class="text-2xl font-bold mb-6 text-gray-800">Detalle de Venta</h2>
        <div id="venta-detalles" class="space-y-3 max-h-96 overflow-y-auto"></div>
        <div class="flex justify-end mt-6 gap-3">
            <button id="close-venta-modal" class="px-6 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg font-medium transition-all">
                Cerrar
            </button>
        </div>
    </div>
</div>

<script src="../src/scripts/modal.js"></script>

<script>
// Variables globales
let todasLasFilas = [];
let filasFiltradas = [];
let paginaActual = 1;
const porPagina = 20;

// Inicializar
document.addEventListener('DOMContentLoaded', function() {
    todasLasFilas = Array.from(document.querySelectorAll('#tablaVentas tr'));
    filasFiltradas = [...todasLasFilas];
    mostrarPagina(1);
});

// Función de paginación
function mostrarPagina(num) {
    paginaActual = num;
    const inicio = (num - 1) * porPagina;
    const fin = inicio + porPagina;
    const totalPaginas = Math.ceil(filasFiltradas.length / porPagina);

    // Ocultar todas las filas primero
    todasLasFilas.forEach(fila => fila.style.display = 'none');

    // Mostrar solo las filas de la página actual
    filasFiltradas.slice(inicio, fin).forEach(fila => {
        fila.style.display = '';
    });

    // Actualizar controles
    document.getElementById('prevPage').disabled = paginaActual === 1;
    document.getElementById('nextPage').disabled = paginaActual === totalPaginas || totalPaginas === 0;
    
    // Actualizar rango
    document.getElementById('rangoInicio').textContent = filasFiltradas.length > 0 ? inicio + 1 : 0;
    document.getElementById('rangoFin').textContent = Math.min(fin, filasFiltradas.length);
    document.getElementById('totalRegistros').textContent = filasFiltradas.length;
    document.getElementById('ventasMostradas').textContent = `${Math.min(fin - inicio, filasFiltradas.length - inicio)} de ${filasFiltradas.length}`;
    
    // Generar números de página
    generarNumerosPagina(totalPaginas);
}

function generarNumerosPagina(totalPaginas) {
    const container = document.getElementById('pageNumbers');
    container.innerHTML = '';
    
    if (totalPaginas <= 1) return;
    
    const maxBotones = 5;
    let inicio = Math.max(1, paginaActual - Math.floor(maxBotones / 2));
    let fin = Math.min(totalPaginas, inicio + maxBotones - 1);
    
    if (fin - inicio < maxBotones - 1) {
        inicio = Math.max(1, fin - maxBotones + 1);
    }
    
    for (let i = inicio; i <= fin; i++) {
        const btn = document.createElement('button');
        btn.textContent = i;
        btn.className = `px-3 py-2 rounded-lg font-medium transition-all ${
            i === paginaActual 
                ? 'bg-indigo-600 text-white' 
                : 'bg-white border-2 border-gray-300 text-gray-700 hover:bg-gray-50'
        }`;
        btn.onclick = () => mostrarPagina(i);
        container.appendChild(btn);
    }
}

// Búsqueda
document.getElementById("searchVenta").addEventListener("input", function() {
    const texto = this.value.toLowerCase();
    
    filasFiltradas = todasLasFilas.filter(fila => {
        const contenido = fila.innerText.toLowerCase();
        return contenido.includes(texto);
    });
    
    mostrarPagina(1);
});

// Ordenamiento
document.getElementById("filterOrden").addEventListener("change", function() {
    const criterio = this.value;
    
    filasFiltradas.sort((a, b) => {
        const fechaA = new Date(a.dataset.fecha);
        const fechaB = new Date(b.dataset.fecha);
        const totalA = parseFloat(a.dataset.total);
        const totalB = parseFloat(a.dataset.total);

        switch(criterio) {
            case "fecha_desc": return fechaB - fechaA;
            case "fecha_asc": return fechaA - fechaB;
            case "total_desc": return totalB - totalA;
            case "total_asc": return totalA - totalB;
            default: return 0;
        }
    });
    
    // Reordenar en el DOM
    const tbody = document.getElementById('tablaVentas');
    filasFiltradas.forEach(fila => tbody.appendChild(fila));
    
    mostrarPagina(1);
});

// Resetear filtros
document.getElementById("resetFilters").addEventListener("click", function() {
    document.getElementById("searchVenta").value = '';
    document.getElementById("filterOrden").value = 'fecha_desc';
    filasFiltradas = [...todasLasFilas];
    mostrarPagina(1);
});

// Navegación de páginas
document.getElementById('prevPage').addEventListener('click', () => {
    if (paginaActual > 1) {
        mostrarPagina(paginaActual - 1);
    }
});

document.getElementById('nextPage').addEventListener('click', () => {
    const totalPaginas = Math.ceil(filasFiltradas.length / porPagina);
    if (paginaActual < totalPaginas) {
        mostrarPagina(paginaActual + 1);
    }
});
</script>

<script src="../src/scripts/modal.js"></script>
<script src="../src/scripts/modal_ventas.js"></script>

</body>
</html>

<!-- MODAL TICKET -->
<div id="ticket-modal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
    <div class="bg-white w-[380px] md:w-[430px] rounded-2xl shadow-xl p-4 animate-slide">

        <h2 class="text-xl font-bold text-gray-800 mb-3 text-center">Ticket de Venta</h2>

        <!-- Iframe donde se carga el ticket -->
        <iframe id="ticket-iframe" class="w-full h-[450px] rounded-lg border"></iframe>

        <div class="flex justify-end">
    <button id="close-ticket-modal"
        class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">
        Cerrar
    </button>
</div>

    </div>
</div>

<style>
    .animate-slide {
        transform: translateY(20px);
        opacity: 0;
        transition: all 0.3s ease;
    }
    .slide-active {
        transform: translateY(0px);
        opacity: 1;
    }
</style>