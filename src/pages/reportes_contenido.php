<?php
// reportes_contenido.php
// Módulo de Estadísticas y Dashboard Premium
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes y Estadísticas</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    },
                    colors: {
                        primary: '#b4c24d',
                        secondary: '#2d4353',
                        accent: '#e15871',
                        graybg: '#eeeeee',
                    }
                }
            }
        }
    </script>

    <style>
        body {
            background-color: #f3f4f6;
        }
        
        /* Animaciones */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out forwards;
        }
        
        .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.15);
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1; 
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1; 
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8; 
        }
        
/* ================================
   MODO OSCURO GLOBAL
================================ */
body.dark-mode {
    background-color: #121212 !important;
    color: #ffffff !important;
}

/* Forzar todos los textos a blanco */
body.dark-mode h1,
body.dark-mode h2,
body.dark-mode h3,
body.dark-mode h4,
body.dark-mode p,
body.dark-mode span,
body.dark-mode small,
body.dark-mode label,
body.dark-mode .text-gray-400,
body.dark-mode .text-gray-500,
body.dark-mode .text-gray-600,
body.dark-mode .text-gray-700,
body.dark-mode .text-gray-800,
body.dark-mode .text-gray-900,
body.dark-mode .text-slate-700,
body.dark-mode .text-slate-800 {
    color: #ffffff !important;
}

/* ================================
   TARJETAS / CARDS BLANCAS 
================================ */
body.dark-mode .bg-white,
body.dark-mode .bg-gray-50,
body.dark-mode .bg-gray-100,
body.dark-mode .bg-gray-200 {
    background-color: #1e1e1e !important;
    border-color: #333 !important;
}

/* Bordes decorativos de colores → mantener pero más discretos */
body.dark-mode .card-border-green {
    border-right-color: #b4c24d !important;
}
body.dark-mode .card-border-blue {
    border-right-color: #4d9ac2 !important;
}
body.dark-mode .card-border-red {
    border-right-color: #e15871 !important;
}


/* ================================
   INPUTS Y SELECTS
================================ */
body.dark-mode input,
body.dark-mode select {
    background-color: #1e1e1e !important;
    border-color: #333 !important;
    color: #ffffff !important;
}

/* Placeholder */
body.dark-mode input::placeholder,
body.dark-mode select::placeholder {
    color: #bbbbbb !important;
}

/* ================================
   BOTONES DEL FILTRO (Hoy, Semana, Mes)
================================ */
body.dark-mode .filter-btn {
    background-color: #2a2a2a !important;
    color: #ffffff !important;
    border-color: #444 !important;
}

body.dark-mode .filter-btn:hover {
    background-color: #3a3a3a !important;
    color: #b4c24d !important;
}

/* ================================
   CONTENEDORES GRANDES
================================ */
body.dark-mode .content,
body.dark-mode main {
    background-color: #121212 !important;
}

/* ================================
   GRÁFICAS CHART.JS
================================ */
body.dark-mode canvas {
    background-color: transparent !important;
}


    </style>
</head>
<body class="text-gray-800 antialiased p-4 md:p-8 ml-0 md:ml-20"> <!-- Margin left to account for sidebar if needed -->

    <div class="max-w-7xl mx-auto space-y-8 animate-fade-in">
        
        <!-- Header & Filters -->
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-end gap-6 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <div>
                <h1 class="text-3xl font-bold text-secondary flex items-center gap-3">
                    <i data-lucide="bar-chart-2" class="w-8 h-8 text-primary"></i>
                    Reportes y Estadísticas
                </h1>
                <p class="text-gray-500 mt-2 text-sm">Análisis financiero y rendimiento del negocio en tiempo real.</p>
            </div>
            
            <div class="flex flex-wrap gap-3 items-center bg-gray-50 p-2 rounded-xl border border-gray-200">
                <button onclick="setFilter('today')" class="filter-btn px-4 py-2 rounded-lg text-sm font-medium transition-all hover:shadow-md focus:ring-2 focus:ring-primary focus:ring-offset-1 bg-white text-gray-600 shadow-sm hover:text-primary">
                    Hoy
                </button>
                <button onclick="setFilter('week')" class="filter-btn px-4 py-2 rounded-lg text-sm font-medium transition-all hover:shadow-md focus:ring-2 focus:ring-primary focus:ring-offset-1 text-gray-600 hover:text-primary">
                    Esta Semana
                </button>
                <button onclick="setFilter('month')" class="filter-btn px-4 py-2 rounded-lg text-sm font-medium transition-all hover:shadow-md focus:ring-2 focus:ring-primary focus:ring-offset-1 text-gray-600 hover:text-primary">
                    Este Mes
                </button>
                
                <div class="h-6 w-px bg-gray-300 mx-1"></div>
                
                <div class="flex items-center gap-2">
                    <div class="relative">
                        <input type="date" id="date-start" class="pl-8 pr-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all w-36">
                        <i data-lucide="calendar" class="w-4 h-4 text-gray-400 absolute left-2.5 top-2.5"></i>
                    </div>
                    <span class="text-gray-400">-</span>
                    <div class="relative">
                        <input type="date" id="date-end" class="pl-8 pr-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all w-36">
                        <i data-lucide="calendar" class="w-4 h-4 text-gray-400 absolute left-2.5 top-2.5"></i>
                    </div>
                    <button onclick="loadDashboard()" class="bg-secondary text-white p-2 rounded-lg hover:bg-opacity-90 transition-all shadow-md hover:shadow-lg active:scale-95">
                        <i data-lucide="search" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- KPIs Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- KPI 1: Ventas Totales -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 card-hover relative overflow-hidden group">
                <div class="absolute right-0 top-0 h-full w-1 bg-primary"></div>
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Ventas Totales</p>
                        <h3 class="text-3xl font-bold text-secondary mt-1" id="kpi-total">$0.00</h3>
                    </div>
                    <div class="p-3 bg-primary/10 rounded-xl text-primary group-hover:bg-primary group-hover:text-white transition-colors">
                        <i data-lucide="dollar-sign" class="w-6 h-6"></i>
                    </div>
                </div>
                <div class="flex items-center text-xs text-green-600 font-medium bg-green-50 w-fit px-2 py-1 rounded-md">
                    <i data-lucide="trending-up" class="w-3 h-3 mr-1"></i>
                    Ingresos Brutos
                </div>
            </div>

            <!-- KPI 2: Transacciones -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 card-hover relative overflow-hidden group">
                <div class="absolute right-0 top-0 h-full w-1 bg-secondary"></div>
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Transacciones</p>
                        <h3 class="text-3xl font-bold text-secondary mt-1" id="kpi-transacciones">0</h3>
                    </div>
                    <div class="p-3 bg-secondary/10 rounded-xl text-secondary group-hover:bg-secondary group-hover:text-white transition-colors">
                        <i data-lucide="shopping-bag" class="w-6 h-6"></i>
                    </div>
                </div>
                <div class="text-xs text-gray-400 mt-1">Tickets generados en el periodo</div>
            </div>

            <!-- KPI 3: Ticket Promedio -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 card-hover relative overflow-hidden group">
                <div class="absolute right-0 top-0 h-full w-1 bg-accent"></div>
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Ticket Promedio</p>
                        <h3 class="text-3xl font-bold text-secondary mt-1" id="kpi-ticket">$0.00</h3>
                    </div>
                    <div class="p-3 bg-accent/10 rounded-xl text-accent group-hover:bg-accent group-hover:text-white transition-colors">
                        <i data-lucide="credit-card" class="w-6 h-6"></i>
                    </div>
                </div>
                <div class="text-xs text-gray-400 mt-1">Promedio por venta realizada</div>
            </div>

            <!-- KPI 4: Margen Bruto -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 card-hover relative overflow-hidden group">
                <div class="absolute right-0 top-0 h-full w-1 bg-indigo-500"></div>
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Margen Bruto</p>
                        <h3 class="text-3xl font-bold text-secondary mt-1" id="kpi-margen">$0.00</h3>
                    </div>
                    <div class="p-3 bg-indigo-50 rounded-xl text-indigo-500 group-hover:bg-indigo-500 group-hover:text-white transition-colors">
                        <i data-lucide="pie-chart" class="w-6 h-6"></i>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-2xl font-bold text-primary" id="kpi-margen-pct">0%</span>
                    <span class="text-xs text-gray-400">de rentabilidad</span>
                </div>
            </div>
        </div>

        <!-- Main Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Chart: Ingresos (Line) -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 lg:col-span-2 card-hover">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold text-secondary flex items-center gap-2">
                        <i data-lucide="activity" class="w-5 h-5 text-primary"></i>
                        Tendencia de Ingresos
                    </h3>
                    <button class="text-gray-400 hover:text-secondary transition-colors"><i data-lucide="more-horizontal" class="w-5 h-5"></i></button>
                </div>
                <div class="relative h-80 w-full">
                    <canvas id="chartIngresos"></canvas>
                </div>
            </div>

            <!-- Chart: Categorías (Doughnut) -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 card-hover">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold text-secondary flex items-center gap-2">
                        <i data-lucide="layers" class="w-5 h-5 text-accent"></i>
                        Ventas por Categoría
                    </h3>
                </div>
                <div class="relative h-64 w-full flex items-center justify-center">
                    <canvas id="chartCategorias"></canvas>
                </div>
                <div class="mt-4 text-center">
                    <p class="text-xs text-gray-400">Distribución de ingresos por familia de productos</p>
                </div>
            </div>
        </div>

        <!-- Secondary Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 pb-8">
            
            <!-- Chart: Top Productos (Bar) -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 card-hover">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold text-secondary flex items-center gap-2">
                        <i data-lucide="star" class="w-5 h-5 text-yellow-500"></i>
                        Top 10 Productos Más Vendidos
                    </h3>
                </div>
                <div class="relative h-72 w-full">
                    <canvas id="chartProductos"></canvas>
                </div>
            </div>

            <!-- Chart: Empleados (Bar) -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 card-hover">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold text-secondary flex items-center gap-2">
                        <i data-lucide="users" class="w-5 h-5 text-blue-500"></i>
                        Rendimiento por Empleado
                    </h3>
                </div>
                <div class="relative h-72 w-full">
                    <canvas id="chartEmpleados"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Logic -->
    <script>
        // Initialize Lucide Icons
        lucide.createIcons();

        // Global Chart Config
        Chart.defaults.font.family = "'Poppins', sans-serif";
        Chart.defaults.color = '#64748b';
        Chart.defaults.scale.grid.color = '#f1f5f9';
        
        // State
        let charts = {};
        const colors = {
            primary: '#b4c24d',
            secondary: '#2d4353',
            accent: '#e15871',
            gray: '#e2e8f0',
            palette: ['#b4c24d', '#2d4353', '#e15871', '#fbbf24', '#60a5fa', '#a78bfa', '#34d399', '#f472b6']
        };

        // Filter Logic
        function setFilter(range) {
            const today = new Date();
            let start = new Date();
            let end = new Date();

            // Update UI
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('bg-primary', 'text-white', 'shadow-md');
                btn.classList.add('text-gray-600');
            });
            event.target.classList.remove('text-gray-600');
            event.target.classList.add('bg-primary', 'text-white', 'shadow-md');

            if (range === 'today') {
                // start = today
            } else if (range === 'week') {
                const day = today.getDay() || 7; 
                if (day !== 1) start.setHours(-24 * (day - 1));
            } else if (range === 'month') {
                start.setDate(1);
            }

            document.getElementById('date-start').valueAsDate = start;
            document.getElementById('date-end').valueAsDate = end;
            
            loadDashboard();
        }

        // Data Fetching
        async function loadDashboard() {
            const start = document.getElementById('date-start').value;
            const end = document.getElementById('date-end').value;
            const params = `?fecha_inicio=${start}&fecha_fin=${end}`;
            const baseUrl = 'api/estadisticas.php';

            try {
                // 1. KPIs
                const kpis = await fetch(`${baseUrl}${params}&action=getKpis`).then(r => r.json());
                if(kpis.success) {
                    animateValue('kpi-total', kpis.total_ventas, '$');
                    animateValue('kpi-transacciones', kpis.transacciones, '');
                    animateValue('kpi-ticket', kpis.ticket_promedio, '$');
                    animateValue('kpi-margen', kpis.margen_bruto, '$');
                    document.getElementById('kpi-margen-pct').textContent = Number(kpis.porcentaje_margen).toFixed(1) + '%';
                }

                // 2. Ingresos Chart
                const ingresos = await fetch(`${baseUrl}${params}&action=getIngresos`).then(r => r.json());
                renderChart('chartIngresos', 'line', 
                    ingresos.data.map(d => d.periodo), 
                    ingresos.data.map(d => d.total), 
                    'Ingresos', 
                    colors.primary
                );

                // 3. Categorias Chart
                const categorias = await fetch(`${baseUrl}${params}&action=getVentasCategoria`).then(r => r.json());
                renderChart('chartCategorias', 'doughnut', 
                    categorias.data.map(d => d.categoria), 
                    categorias.data.map(d => d.total), 
                    'Ventas', 
                    colors.palette
                );

                // 4. Top Productos
                const productos = await fetch(`${baseUrl}${params}&action=getTopProductos`).then(r => r.json());
                renderChart('chartProductos', 'bar', 
                    productos.data.map(d => d.nom_producto), 
                    productos.data.map(d => d.cantidad), 
                    'Unidades', 
                    colors.secondary
                );

                // 5. Empleados
                const empleados = await fetch(`${baseUrl}${params}&action=getVentasEmpleado`).then(r => r.json());
                renderChart('chartEmpleados', 'bar', 
                    empleados.data.map(d => d.empleado), 
                    empleados.data.map(d => d.total), 
                    'Ventas ($)', 
                    colors.accent
                );

            } catch (e) {
                console.error("Error loading dashboard:", e);
            }
        }

        // Chart Rendering
        function renderChart(canvasId, type, labels, data, label, color) {
            const ctx = document.getElementById(canvasId).getContext('2d');
            
            if (charts[canvasId]) {
                charts[canvasId].destroy();
            }

            const config = {
                type: type,
                data: {
                    labels: labels,
                    datasets: [{
                        label: label,
                        data: data,
                        backgroundColor: Array.isArray(color) ? color : (type === 'line' ? color : color),
                        borderColor: Array.isArray(color) ? 'white' : color,
                        borderWidth: 2,
                        tension: 0.4,
                        borderRadius: 6,
                        pointBackgroundColor: 'white',
                        pointBorderColor: color,
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        fill: type === 'line' ? {
                            target: 'origin',
                            above: hexToRgba(color, 0.1)
                        } : false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 1000,
                        easing: 'easeOutQuart'
                    },
                    plugins: {
                        legend: {
                            display: type === 'doughnut',
                            position: 'right',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: { size: 12 }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(45, 67, 83, 0.9)',
                            padding: 12,
                            cornerRadius: 8,
                            titleFont: { size: 13, weight: 600 },
                            bodyFont: { size: 12 },
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        if(type === 'line' || label.includes('$')) {
                                            label += new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(context.parsed.y);
                                        } else {
                                            label += context.parsed.y;
                                        }
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: type === 'doughnut' ? {
                        x: { display: false },
                        y: { display: false }
                    } : {
                        y: {
                            beginAtZero: true,
                            grid: { borderDash: [5, 5], drawBorder: false },
                            ticks: { padding: 10 }
                        },
                        x: {
                            grid: { display: false, drawBorder: false },
                            ticks: { padding: 10 }
                        }
                    }
                }
            };

            charts[canvasId] = new Chart(ctx, config);
        }

        // Helper: Hex to RGBA
        function hexToRgba(hex, alpha) {
            if(Array.isArray(hex)) return hex[0];
            const r = parseInt(hex.slice(1, 3), 16);
            const g = parseInt(hex.slice(3, 5), 16);
            const b = parseInt(hex.slice(5, 7), 16);
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        }

        // Helper: Number Animation
        function animateValue(id, end, prefix = '') {
            const obj = document.getElementById(id);
            const start = 0;
            const duration = 1000;
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                const value = Math.floor(progress * (end - start) + start);
                
                if(prefix === '$') {
                    obj.innerHTML = prefix + Number(value).toLocaleString('en-US', {minimumFractionDigits: 2});
                } else {
                    obj.innerHTML = Number(value).toLocaleString('en-US');
                }
                
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                } else {
                     // Ensure final value is exact (especially for floats)
                     if(prefix === '$') {
                        obj.innerHTML = prefix + Number(end).toLocaleString('en-US', {minimumFractionDigits: 2});
                    } else {
                        obj.innerHTML = Number(end).toLocaleString('en-US');
                    }
                }
            };
            window.requestAnimationFrame(step);
        }

        // Init
        document.addEventListener('DOMContentLoaded', () => {
            setFilter('today');
        });

    </script>
</body>
</html>