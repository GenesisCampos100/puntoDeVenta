<?php
// =========================================================
// productos_contenido.php - PARTE 1/4
// PHP: consulta y HEAD del HTML (Tailwind + SweetAlert2 + Lucide icons)
// =========================================================

require_once __DIR__ . "/../config/db.php";

/**
 * NOTA IMPORTANTE:
 * - Traemos TODOS los productos (activos + inactivos). El filtrado Activo/Descatalogado
 *   lo hará JavaScript para que las pestañas funcionen sin recargar.
 * - Identificamos variantes por su 'sku' (cod_barras en variantes) y relacionamos
 *   por el cod_barras del producto padre.
 */

// Parámetros GET opcionales (se usan para prefilling si se quiere)
$busqueda = $_GET['busqueda'] ?? '';
$categoria = $_GET['categoria'] ?? '';
$orden = $_GET['orden'] ?? 'p.nom_producto ASC';
$vista_actual = $_GET['view'] ?? 'productos_contenido';

// === Consulta productos (traemos todos) ===
$sql = "SELECT 
            p.cod_barras AS id_producto,
            p.cod_barras AS producto_cod_barras,
            p.nom_producto AS producto_nombre,
            p.imagen AS producto_imagen,
            p.marca,
            p.descripcion,
            c.nombre AS categoria,
            p.cantidad,
            p.color,
            p.cantidad_min,
            p.costo,
            p.precio AS precio_unitario,
            p.id_categoria,
            p.is_active,
            (SELECT COUNT(*) FROM variantes v2 WHERE v2.cod_barras = p.cod_barras) AS tiene_variante
        FROM productos p
        LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
        WHERE 1=1"; // NOTA: no filtramos is_active aquí, lo hará JS

// Filtros server-side opcionales (solo para prefill/compatibilidad; JS se encargará)
$params = [];
if (!empty($busqueda)) {
    $sql .= " AND (p.nom_producto LIKE :busqueda OR p.cod_barras LIKE :busqueda)";
    $params[':busqueda'] = "%{$busqueda}%";
}
if (!empty($categoria)) {
    $sql .= " AND p.id_categoria = :categoria";
    $params[':categoria'] = $categoria;
}

// Orden (trusted input caution: we accept only known tokens)
$allowed_orders = [
    'p.nom_producto ASC' => 'p.nom_producto ASC',
    'p.nom_producto DESC' => 'p.nom_producto DESC',
    'p.precio ASC' => 'p.precio ASC',
    'p.precio DESC' => 'p.precio DESC'
];
// Map simple keys to allowed orders if user passed friendlier keys
if (in_array($orden, array_keys($allowed_orders))) {
    $sql .= " ORDER BY " . $allowed_orders[$orden];
} else {
    // fallback default
    $sql .= " ORDER BY p.nom_producto ASC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// === Traemos VARIANTES completas (para in-memory grouping) ===
$variantesStmt = $pdo->query("
    SELECT 
        v.cod_barras AS id_producto_padre, -- referencia al cod_barras del padre
        v.id_variante AS id_variante,
        v.sku AS cod_barras,                 -- SKU / código de barras de la variante
        v.talla,
        v.color,
        v.cantidad,
        v.cantidad_min,
        v.precio,                                
        v.costo,
        v.imagen
    FROM variantes v
");
$variantesRaw = $variantesStmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupar variantes por cod_barras del producto padre
$variantesPorProducto = [];
foreach ($variantesRaw as $v) {
    $parent = $v['id_producto_padre'] ?? '';
    if (!isset($variantesPorProducto[$parent])) $variantesPorProducto[$parent] = [];
    $variantesPorProducto[$parent][] = $v;
}

// === Categorías para el filtro ===
$categorias = $pdo->query("SELECT * FROM categorias ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

// -----------------------
// Inicia HTML (head)
// -----------------------
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Productos — Inventario</title>

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Lucide icons (elegiste A) -->
    <script src="https://unpkg.com/lucide@latest/dist/lucide.min.js"></script>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        /* Pequeños ajustes visuales compartidos */
        body { font-family: 'Poppins', sans-serif; background-color: #f3f6f9; color: #0f172a; }
        .product-inactive { background-color: #f8fafc !important; color: #6b7280 !important; }
        .product-inactive strong { color: #6b7280 !important; }
        .fade-in { animation: fadeIn .18s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(6px);} to { opacity: 1; transform: translateY(0); } }
        /* Utilities small-screen */
        @media (max-width: 640px) {
            .btn-text-mobile-hidden { display: none; }
        }
    </style>
</head>

<body class="antialiased">
<!-- CONTENEDOR PRINCIPAL: La parte visual (toolbar + tabla) vendrá en la PARTE 2 -->
<!-- guardamos las variables PHP como JSON para usar en JS de forma segura -->
<script>
    // Datos iniciales pasados a JS (solo lectura)
    window.__INITIAL_DATA = {
        productos: <?= json_encode($productos, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
        variantesPorProducto: <?= json_encode($variantesPorProducto, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
        categorias: <?= json_encode($categorias, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
        // valores GET prefills
        initialSearch: <?= json_encode($busqueda) ?>,
        initialCategoria: <?= json_encode($categoria) ?>,
        initialOrden: <?= json_encode($orden) ?>
    };
</script>

<div class="max-w-7xl mx-auto p-4 lg:pt-8">
  <!-- TOOLBAR: SEARCH, TABS, FILTERS, AGREGAR -->
  <div class="bg-white shadow rounded-xl p-4 flex flex-col lg:flex-row gap-3 lg:items-center justify-between">
    <div class="flex items-center gap-3 w-full lg:w-2/3">
      <!-- Search (realtime) -->
      <div class="relative w-full">
        <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 1116.65 16.65z"/></svg>
        <input id="busqueda" type="text" placeholder="Buscar producto por nombre o Cód. Barras..." class="pl-10 pr-10 py-2 w-full rounded-full border border-gray-200 focus:ring-2 focus:ring-green-200 focus:border-green-300" />
        <button id="clear-search" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hidden" aria-label="Limpiar búsqueda">
          <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <!-- Tabs (Activos / Descatalogados) -->
      <div class="ml-2">
        <div id="tabs" class="inline-flex bg-gray-100 rounded-full p-1">
          <button data-status="activo" class="tab-btn px-3 py-1 rounded-full bg-white text-sm font-semibold">Activos</button>
          <button data-status="descatalogado" class="tab-btn px-3 py-1 rounded-full text-sm font-semibold text-gray-600">Descatalogados</button>
        </div>
      </div>
    </div>

    <div class="flex gap-3 items-center w-full lg:w-auto">
      <!-- Category filter -->
      <div class="relative">
        <select id="categoria" class="rounded-full border border-gray-200 px-4 py-2 pr-8 bg-white">
          <option value="">Todas las categorías</option>
        </select>
      </div>

      <!-- Orden -->
      <div>
        <select id="orden" class="rounded-full border border-gray-200 px-4 py-2 pr-8 bg-white">
          <option value="nom_asc">Nombre (A → Z)</option>
          <option value="nom_desc">Nombre (Z → A)</option>
          <option value="precio_asc">Precio ↑</option>
          <option value="precio_desc">Precio ↓</option>
        </select>
      </div>

      <a href="index.php?view=agregar_producto" class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-[#2d4353] text-white hover:bg-[#243747]">
        <!-- Plus icon (Lucide) -->
        <i data-lucide="plus" class="w-5 h-5"></i>
        <span class="hidden sm:inline">Agregar</span>
      </a>
    </div>
  </div>

  <!-- TABLE CARD -->
  <div class="mt-4 bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
      <table id="productos-table" class="min-w-full divide-y">
        <thead class="bg-[#0f172a] text-white">
          <tr>
            <th class="px-4 py-3 text-left text-sm font-semibold">Producto</th>
            <th class="px-4 py-3 text-left text-sm font-semibold w-28">Stock</th>
            <th class="px-4 py-3 text-left text-sm font-semibold w-40 hidden sm:table-cell">Categoría</th>
            <th class="px-4 py-3 text-left text-sm font-semibold w-28">Precio</th>
            <th class="px-4 py-3 text-right text-sm font-semibold w-36">Acciones</th>
          </tr>
        </thead>

        <tbody id="tabla-productos" class="bg-white divide-y">
          <!-- FILAS RENDERIZADAS POR PHP (ya están en window.__INITIAL_DATA) -->
          <!-- Menos lógica JS: insertadas por JS en la Parte 3 para mantener comportamiento consistente -->
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ===========================
     MODALES (estáticos; JS inyectará contenido)
     =========================== -->

<!-- Modal DETALLE -->
<div id="modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/30">
  <div class="bg-white rounded-xl p-6 w-full max-w-2xl fade-in relative">
    <button class="absolute right-4 top-4 text-gray-600" onclick="cerrarModal()" aria-label="Cerrar detalle">
      <i data-lucide="x" class="w-6 h-6"></i>
    </button>

    <div id="modal-content" class="space-y-4">
      <!-- contenido inyectado: info + historial (por JS) -->
      <div class="text-center text-gray-500">Cargando...</div>
    </div>
  </div>
</div>

<!-- Modal AJUSTE -->
<div id="ajusteModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/30">
  <div class="bg-white rounded-xl p-6 w-full max-w-lg fade-in relative">
    <button class="absolute right-4 top-4 text-gray-600" onclick="closeAjusteModal()" aria-label="Cerrar ajuste">
      <i data-lucide="x" class="w-6 h-6"></i>
    </button>

    <h3 class="text-xl font-semibold mb-2">Ajuste de stock</h3>
    <div id="ajuste-content">
    </div>
  </div>
</div>

<!-- Confirm modal eliminar / inactivar -->
<div id="confirmModal" class="hidden fixed inset-0 z-60 flex items-center justify-center p-4 bg-black/30">
  <div class="bg-white rounded-xl p-6 w-full max-w-sm fade-in">
    <h3 class="font-semibold mb-2">Confirmar acción</h3>
    <p id="confirmMessage" class="text-sm text-gray-600 mb-4">¿Seguro?</p>
    <div class="flex gap-2">
      <button id="confirmCancel" class="flex-1 py-2 rounded bg-gray-100">Cancelar</button>
      <button id="confirmOk" class="flex-1 py-2 rounded bg-red-600 text-white">Confirmar</button>
    </div>
  </div>
</div>

<!-- Insertamos los iconos Lucide en el DOM (init) -->
<script>
  if (window.lucide) {
    window.lucide && window.lucide.replace();
  } else {
    // si no está cargado todavía, intentamos después
    window.addEventListener('load', () => { window.lucide && window.lucide.replace(); });
  }
</script>

<script>
// ===========================
// 🔄 Estado local
// ===========================
let currentStatus = "activo"; // tab seleccionado
let currentSearch = "";
let currentCategory = "";
let currentOrder = "nom_asc";

// ===========================
// 🔎 Debounce para búsqueda realtime
// ===========================
function debounce(fn, delay = 300) {
  let t;
  return (...args) => {
    clearTimeout(t);
    t = setTimeout(() => fn(...args), delay);
  };
}

// ===========================
// 🧠 Cargar datos desde PHP ya insertados en window.__INITIAL_DATA
// ===========================
function getFilteredData() {
  return window.__INITIAL_DATA.filter(p => {
    const matchStatus = (currentStatus === "activo") 
      ? p.is_active == 1 
      : p.is_active == 0;

    const matchSearch = 
      p.nombre.toLowerCase().includes(currentSearch) ||
      p.cod_barras.toLowerCase().includes(currentSearch);

    const matchCategory = 
      currentCategory === "" || p.categoria === currentCategory;

    return matchStatus && matchSearch && matchCategory;
  }).sort((a,b)=>{
    switch(currentOrder){
      case "nom_asc": return a.nombre.localeCompare(b.nombre);
      case "nom_desc": return b.nombre.localeCompare(a.nombre);
      case "precio_asc": return a.precio - b.precio;
      case "precio_desc": return b.precio - a.precio;
      default: return 0;
    }
  });
}

// ===========================
// 🖼️ Pintar tabla completa
// ===========================
function renderTable() {
  const tbody = document.querySelector("#tabla-productos");
  tbody.innerHTML = "";

  const data = getFilteredData();
  if (data.length === 0) {
    tbody.innerHTML = `
      <tr><td colspan="5" class="text-center py-6 text-gray-500">
        No se encontraron resultados.
      </td></tr>`;
    return;
  }

  data.forEach(producto => {
    const row = document.createElement("tr");
    row.classList.add("cursor-pointer", "hover:bg-gray-50");

    row.innerHTML = `
      <td class="px-4 py-3">
        <div class="font-semibold">${producto.nombre}</div>
        <div class="text-xs text-gray-500">SKU: ${producto.cod_barras}</div>
      </td>
      <td class="px-4 py-3 font-semibold text-gray-800">${producto.stock}</td>
      <td class="px-4 py-3 hidden sm:table-cell">${producto.categoria}</td>
      <td class="px-4 py-3">$${producto.precio}</td>
      <td class="px-4 py-3 text-right">
        <div class="flex justify-end gap-2">
          <button class="btn-detalle" data-cod="${producto.cod_barras}" aria-label="Detalles del producto">
            <i data-lucide="eye" class="w-5 h-5"></i>
          </button>
          <button class="btn-ajuste" data-cod="${producto.cod_barras}" aria-label="Ajustar inventario">
            <i data-lucide="settings" class="w-5 h-5 text-blue-600"></i>
          </button>
          <button class="btn-toggle" data-cod="${producto.cod_barras}" data-current="${producto.is_active}">
            <i data-lucide="${producto.is_active == 1 ? 'archive' : 'refresh'}" class="w-5 h-5 ${producto.is_active==1 ? 'text-red-600' : 'text-green-600'}"></i>
          </button>
        </div>
      </td>
    `;

    tbody.appendChild(row);
  });

  lucide.replace();
  bindRowEvents();
}

// ===========================
// 🧩 Eventos de Acciones
// ===========================
function bindRowEvents() {
  document.querySelectorAll(".btn-detalle").forEach(btn => {
    btn.onclick = () => openModal(btn.dataset.cod);
  });

  document.querySelectorAll(".btn-ajuste").forEach(btn => {
    btn.onclick = () => {
    const cod = btn.dataset.cod;
    const name = btn.closest("tr").querySelector(".font-semibold").textContent.trim();
    const isVar = String(btn.dataset.isvariante) === "true";

    openAjusteModal(cod, isVar ? "variante" : "producto", name, isVar);
};

});

  document.querySelectorAll(".btn-toggle").forEach(btn => {
    btn.onclick = () => toggleEstado(btn.dataset.cod, btn.dataset.current);
  });
}

// ===========================
// ❌ / ✅ Activar / Descatalogar producto
// ===========================
function toggleEstado(cod, status) {
  const nuevo = status == 1 ? 0 : 1;

  fetch(`pages/inventario_inactivar.php?id=${cod}&type=producto&status=${nuevo}`)
    .then(r=>r.json()).then(res=>{
      if(!res.success) return Swal.fire("Error",res.message,"error");
      
      Swal.fire("Completado",res.message,"success");
      const p = window.__INITIAL_DATA.find(x=>x.cod_barras===cod);
      if(p) p.is_active = nuevo;
      renderTable();
    });
}

// ===========================
// 📊 Modal de Detalles
// ===========================
function openModal(cod) {
  const modal = document.querySelector("#modal");
  const content = document.querySelector("#modal-content");

  const product = window.__INITIAL_DATA.find(x=>x.cod_barras===cod);
  if(!product) return;

  modal.classList.remove("hidden");
  content.innerHTML = `
    <h2 class="text-lg font-semibold">${product.nombre}</h2>
    <p class="text-sm text-gray-500">SKU: ${product.cod_barras}<br>
    Categoría: ${product.categoria}<br>
    Stock: ${product.stock}</p>
    <hr/>
    <p class="font-semibold text-gray-800 mt-2">🕓 Historial de movimientos</p>
    <div id="history">Cargando historial...</div>
  `;

  fetch(`pages/fetch_historial.php?cod=${cod}`)
    .then(r=>r.json()).then(hist=>{
      const area = document.querySelector("#history");
      if(hist.error) return area.textContent = hist.error;
      if(hist.length===0) return area.textContent = "Sin movimientos registrados.";
      
      area.innerHTML = hist.map(m => `
        <div class="border-l-4 pl-3 py-1 my-1 border-blue-400 text-sm">
          <b>${m.tipo_movimiento}</b> (${m.cantidad_impactada}) - ${m.fecha_movimiento}<br>
          <span class="text-gray-500">${m.motivo || 'Sin motivo'}</span>
        </div>
      `).join("");
    });
}
function cerrarModal(){ document.querySelector("#modal").classList.add("hidden"); }

// ===========================
// 🔧 Modal AJUSTE
// ===========================
let ajusteCod="";

function openAjusteModal(cod, type = "producto", name = "", isVar = false) {
    ajusteCod = cod;
    ajusteType = type;
    ajusteIsVar = isVar;

    document.querySelector("#ajuste-content").innerHTML = `
      <label class="text-sm">Cantidad a ajustar:</label>
      <input id="ajuste-cantidad" type="number" class="mt-1 w-full border rounded p-2">

      <label class="text-sm mt-3">Motivo:</label>
      <textarea id="ajuste-motivo" class="w-full border rounded p-2"></textarea>

      <button onclick="submitAjuste()" class="mt-4 w-full bg-blue-600 text-white py-2 rounded">
        Confirmar ajuste
      </button>
    `;

    document.querySelector("#ajusteModal").classList.remove("hidden");
}

function closeAjusteModal() {
    document.querySelector("#ajusteModal").classList.add("hidden");
}

function submitAjuste() {
    const cantidad = parseInt(document.querySelector("#ajuste-cantidad").value, 10);
    const motivo = document.querySelector("#ajuste-motivo").value;

    const fd = new FormData();
    fd.append("cod_entidad", ajusteCod);
    fd.append("cantidad", cantidad);
    fd.append("motivo", motivo);
    fd.append("ajusteEsVariante", ajusteIsVar ? "true" : "false");

    fetch(API_URL + "?action=ajustar_stock", {
        method: "POST",
        body: fd
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) {
            return Swal.fire("Error", res.message, "error");
        }

        Swal.fire("OK", res.message, "success");
        closeAjusteModal();

        const target = document.getElementById("stock-" + ajusteCod);
        if (target && typeof res.nuevo_stock !== "undefined") {
            target.textContent = res.nuevo_stock + " unid.";
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire("Error", "Falla de conexión", "error");
    });
}

// ===========================
// 🗂️ TABS
// ===========================
document.querySelectorAll(".tab-btn").forEach(btn=>{
 btn.onclick = ()=>{
   document.querySelectorAll(".tab-btn").forEach(b=>b.classList.remove("bg-white","text-gray-900"));
   btn.classList.add("bg-white","text-gray-900");
   currentStatus = btn.dataset.status;
   renderTable();
 };
});

// ===========================
// 🔍 Search realtime
// ===========================
const inputSearch = document.querySelector("#busqueda");
const clearButton = document.querySelector("#clear-search");

inputSearch.addEventListener("input", debounce(()=>{
  currentSearch = inputSearch.value.trim().toLowerCase();
  clearButton.classList.toggle("hidden", currentSearch==="");
  renderTable();
}, 200));

clearButton.onclick = ()=>{
  inputSearch.value = "";
  currentSearch = "";
  clearButton.classList.add("hidden");
  renderTable();
};

// ===========================
// 🎯 Filtros
// ===========================
document.querySelector("#categoria").onchange = (e)=>{
  currentCategory = e.target.value;
  renderTable();
};

document.querySelector("#orden").onchange = (e)=>{
  currentOrder = e.target.value;
  renderTable();
};

// ===========================
// ▶️ Init
// ===========================
renderTable();
</script>
<?php
// Opcional: crear backup del archivo producto_contenido (solo si quieres)
// Ejecutar solo en entorno de desarrollo, porque copiar el archivo en cada request no es ideal en producción.
$src = __FILE__;
$bakDir = __DIR__ . '/backups';
if (!is_dir($bakDir)) @mkdir($bakDir, 0755, true);
$bk = $bakDir . '/productos_contenido_' . date('Ymd_His') . '.php';
@copy($src, $bk);
// Comentario: desactiva o elimina este bloque cuando no lo necesites
?>

