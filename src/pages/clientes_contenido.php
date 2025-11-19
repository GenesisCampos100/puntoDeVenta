<?php
// clientes_contenido.php
require_once __DIR__ . '/../config/db.php'; // <<--- ajusta ruta si es necesario

// Si viene acción AJAX → devolver JSON
if (isset($_GET['action']) && $_GET['action'] === "getCliente") {
    header("Content-Type: application/json; charset=utf-8");

    $id = intval($_GET['id'] ?? 0);

    if ($id <= 0) {
        echo json_encode(["success" => false, "error" => "ID inválido"]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id_cliente = ?");
    $stmt->execute([$id]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cliente) {
        echo json_encode(["success" => true, "cliente" => $cliente]);
    } else {
        echo json_encode(["success" => false, "error" => "Cliente no encontrado"]);
    }
    exit; // 🚀 EVITA QUE SE IMPRIMA HTML
}

// ----------------------
// Helper: intentar obtener ventas por cliente probando nombres comunes de tabla
// ----------------------
function getVentasByClienteTryTables($pdo, $clienteId) {
    $tables = ['ventad','venta','ventas','venta_d']; // priorizamos 'ventad' si es tu tabla
    foreach ($tables as $t) {
        try {
            $sql = "SELECT id_venta, fecha, tipo_pago, pago_total, id_empleado FROM `$t` WHERE id_cliente = :id ORDER BY fecha DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $clienteId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['table' => $t, 'rows' => $rows];
        } catch (Exception $e) {
            continue;
        }
    }
    return ['error' => 'No se encontró la tabla de ventas (verifica: ventad / venta / ventas).'];
}




// ----------------------
// Handler: Búsqueda en tiempo real -> JSON (GET ?action=search&q=... or &name=...&email=...)
// ----------------------
if (isset($_GET['action']) && $_GET['action'] === 'search') {
    header('Content-Type: application/json; charset=utf-8');
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    $nameFilter = isset($_GET['name']) ? trim($_GET['name']) : '';
    $emailFilter = isset($_GET['email']) ? trim($_GET['email']) : '';
    $whereParts = [];
    $params = [];

    if ($q !== '') {
        $whereParts[] = "(nombre LIKE :q OR apellido_paterno LIKE :q OR apellido_materno LIKE :q OR correo LIKE :q OR celular LIKE :q)";
        $params[':q'] = "%$q%";
    }
    if ($nameFilter !== '') {
        $whereParts[] = "(nombre LIKE :name OR apellido_paterno LIKE :name OR apellido_materno LIKE :name OR CONCAT(nombre,' ',apellido_paterno) LIKE :name)";
        $params[':name'] = "%$nameFilter%";
    }
    if ($emailFilter !== '') {
        $whereParts[] = "correo LIKE :email";
        $params[':email'] = "%$emailFilter%";
    }

    $sql = "SELECT id_cliente, nombre, apellido_paterno, apellido_materno, celular, correo, calle, num_ext, num_int, colonia, cp, estado FROM clientes";
    if (!empty($whereParts)) $sql .= " WHERE " . implode(' AND ', $whereParts);
    $sql .= " ORDER BY id_cliente DESC LIMIT 100";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Enviar una versión "display" para cada fila (compact)
        $data = array_map(function($r){
            $displayName = trim($r['nombre'].' '.$r['apellido_paterno'].' '.$r['apellido_materno']);
            $direccion = trim(($r['calle'] ?? '') . ' ' . ($r['num_ext'] ?? '') . ' ' . ($r['num_int'] ?? '') . ' ' . ($r['colonia'] ?? '') . ' ' . ($r['cp'] ?? '') . ' ' . ($r['estado'] ?? ''));
            return [
                'id_cliente' => $r['id_cliente'],
                'nombre' => $displayName,
                'celular' => $r['celular'] ?? '',
                'correo' => $r['correo'] ?? '',
                'direccion' => $direccion
            ];
        }, $rows);
        echo json_encode(['success'=>true,'clientes'=>$data]);
    } catch (Exception $e) {
        echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
    }
    exit;
}

// ----------------------
// Handler: Obtener ventas (GET ?action=getVentas&id=...)
// ----------------------
if (isset($_GET['action']) && $_GET['action'] === 'getVentas' && isset($_GET['id'])) {
    header('Content-Type: application/json; charset=utf-8');
    $id = (int)$_GET['id'];
    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID de cliente inválido.']);
        exit;
    }
    $res = getVentasByClienteTryTables($pdo, $id);
    if (isset($res['error'])) {
        echo json_encode(['success' => false, 'error' => $res['error']]);
        exit;
    }
    echo json_encode(['success' => true, 'table' => $res['table'], 'ventas' => $res['rows']]);
    exit;
}

// ----------------------
// Eliminar via POST JSON a este archivo (fetch) => action=delete
// ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'delete') {
    header('Content-Type: application/json; charset=utf-8');
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    $id = isset($data['id']) ? (int)$data['id'] : 0;
    if ($id <= 0) {
        echo json_encode(['success'=>false,'error'=>'ID inválido']);
        exit;
    }
    try {
        $stmt = $pdo->prepare("DELETE FROM clientes WHERE id_cliente = :id");
        $stmt->execute([':id'=>$id]);
        echo json_encode(['success'=>true]);
    } catch (Exception $e) {
        echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
    }
    exit;
}

// ----------------------
// Mostrar HTML (server-rendered initial table + fallback)
// ----------------------
// Flash
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// initial server-side filters (for noscript or initial load)
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$nameFilter = isset($_GET['name']) ? trim($_GET['name']) : '';
$emailFilter = isset($_GET['email']) ? trim($_GET['email']) : '';
$estadoFilter = isset($_GET['estado']) ? trim($_GET['estado']) : '';

$whereParts = [];
$params = [];
if ($q !== '') {
    $whereParts[] = "(nombre LIKE :q OR apellido_paterno LIKE :q OR apellido_materno LIKE :q OR correo LIKE :q OR celular LIKE :q)";
    $params[':q'] = "%$q%";
}
if ($nameFilter !== '') {
    $whereParts[] = "(nombre LIKE :name OR apellido_paterno LIKE :name OR apellido_materno LIKE :name OR CONCAT(nombre,' ',apellido_paterno) LIKE :name)";
    $params[':name'] = "%$nameFilter%";
}
if ($emailFilter !== '') {
    $whereParts[] = "correo LIKE :email";
    $params[':email'] = "%$emailFilter%";
}

$sql = "SELECT id_cliente, nombre, apellido_paterno, apellido_materno, celular, correo, calle, num_ext, num_int, colonia, cp, estado FROM clientes";
if (!empty($whereParts)) $sql .= " WHERE " . implode(' AND ', $whereParts);
$sql .= " ORDER BY id_cliente DESC LIMIT 200";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Clientes — Punto de Venta</title>

  <!-- Poppins -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>

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
    body { font-family: var(--font); background: linear-gradient(180deg,#ffffff 0%, #f7fafc 100%); color: #0f172a; }
    .btn-primary { background: var(--verde); color: white; }
    .input-error { border-color: var(--rosa) !important; box-shadow: 0 0 0 4px rgba(225,89,113,0.06); }

    /* Animaciones & WOW */
    @keyframes popIn { from { opacity:0; transform: translateY(10px) scale(.98) } to { opacity:1; transform: translateY(0) scale(1) } }
    .animate-pop { animation: popIn .42s cubic-bezier(.2,.9,.3,1) both; }
    @keyframes slideUp { from { opacity:0; transform: translateY(12px) } to { opacity:1; transform: translateY(0) } }
    .animate-slideUp { animation: slideUp .5s cubic-bezier(.2,.9,.3,1) both; }

    /* Modal basics */
    .modal-backdrop { background: rgba(2,6,23,0.6); backdrop-filter: blur(4px); }
    .card-wow { transform-origin: center; transition: transform .28s cubic-bezier(.2,.9,.3,1), box-shadow .28s; }
    .card-wow:hover { transform: translateY(-6px) scale(1.005); box-shadow: 0 18px 40px rgba(45,67,83,0.06); }

    /* small helpers */
    .truncate-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
  </style>
</head>
<body class="p-6 bg-[var(--gris)]">
  <div class="max-w-7xl mx-auto animate-pop">

    <!-- ============================
         HEADER PREMIUM (BUSCADOR / FILTROS / AGREGAR)
         ============================ -->
    <div class="bg-white shadow-lg rounded-xl p-4 flex flex-col lg:flex-row gap-4 lg:items-center justify-between border-b border-gray-100 mb-6 animate-slideUp">
        <!-- BUSCADOR (UNIFICADO - tiempo real) -->
        <div class="flex items-center gap-3 w-full lg:w-3/5">
            <div class="relative w-full">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input id="busqueda" type="text" placeholder="Buscar por nombre o correo..." value="<?= htmlspecialchars($q ?: $nameFilter ?: $emailFilter) ?>"
                       class="pl-10 pr-10 py-2.5 w-full rounded-full border border-gray-200 focus:ring-2 focus:ring-[var(--verde)] focus:border-[var(--verde)] transition duration-150"/>
                <button id="clear-search" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[var(--rosa)] <?= ($q==='' && $nameFilter==='' && $emailFilter==='') ? 'hidden' : '' ?>">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        <!-- FILTROS Y BOTÓN AGREGAR -->
        <div class="flex gap-3 items-center w-full lg:w-auto flex-shrink-0">
            <select id="estadoFiltro" class="rounded-full border border-gray-200 px-4 py-2.5 bg-white text-sm focus:ring-[var(--verde)]/50 focus:border-[var(--verde)] transition duration-150">
                <option value="">Todos los estados</option>
                <option value="nuevo" <?= ($estadoFilter === 'nuevo') ? 'selected' : '' ?>>Nuevos</option>
                <option value="frecuente" <?= ($estadoFilter === 'frecuente') ? 'selected' : '' ?>>Frecuentes</option>
                <option value="moroso" <?= ($estadoFilter === 'moroso') ? 'selected' : '' ?>>Morosos</option>
            </select>

            <button id="btnAgregar" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-[var(--azul)] text-white font-semibold transition duration-200 hover:bg-[#1c2b39] shadow-md">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Agregar cliente
            </button>
        </div>
    </div>

    <!-- ============================
         TABLA DE CLIENTES (RENDERIZADA POR JS EN BÚSQUEDA EN TIEMPO REAL)
         ============================ -->
    <div id="tabla-wrap" class="bg-white rounded-2xl shadow p-4 card-wow">
      <?php if($flash): ?>
        <div id="flash-data" data-msg="<?=htmlspecialchars($flash['msg'])?>" data-type="<?=htmlspecialchars($flash['type'])?>" style="display:none"></div>
      <?php endif; ?>

      <div class="overflow-x-auto">
        <table id="tabla-clientes" class="min-w-full divide-y">
          <thead class="bg-[var(--gris)] rounded-t-lg">
            <tr>
              <th class="px-4 py-3 text-left text-sm font-medium">ID</th>
              <th class="px-4 py-3 text-left text-sm font-medium">Nombre</th>
              <th class="px-4 py-3 text-left text-sm font-medium">Celular</th>
              <th class="px-4 py-3 text-left text-sm font-medium">Correo</th>
              <th class="px-4 py-3 text-left text-sm font-medium">Dirección</th>
              <th class="px-4 py-3 text-right text-sm font-medium">Acciones</th>
            </tr>
          </thead>
          <tbody id="tabla-body" class="divide-y">
            <!-- Server-rendered rows as fallback / first paint -->
            <?php if (count($clientes)===0): ?>
              <tr><td colspan="6" class="p-6 text-center text-gray-500">No hay clientes registrados.</td></tr>
            <?php else: ?>
              <?php foreach($clientes as $c): 
                $displayName = htmlspecialchars(trim($c['nombre'].' '.$c['apellido_paterno'].' '.$c['apellido_materno']));
                $direccion = htmlspecialchars(trim(($c['calle'] ?? '') . ' ' . ($c['num_ext'] ?? '') . ' ' . ($c['num_int'] ?? '') . ' ' . ($c['colonia'] ?? '') . ' ' . ($c['cp'] ?? '') . ' ' . ($c['estado'] ?? '')));
              ?>
                <tr class="hover:bg-gray-50 transition">
                  <td class="px-4 py-3 text-sm"><?=htmlspecialchars($c['id_cliente'])?></td>
                  <td class="px-4 py-3 text-sm"><?= $displayName ?></td>
                  <td class="px-4 py-3 text-sm"><?=htmlspecialchars($c['celular'])?></td>
                  <td class="px-4 py-3 text-sm"><?=htmlspecialchars($c['correo'])?></td>
                  <td class="px-4 py-3 text-sm truncate-2"><?= $direccion ?></td>
                  <td class="px-4 py-3 text-right">
                    <div class="inline-flex gap-2">
                      <button onclick="location.href='index.php?view=editar_cliente&id=<?= $c['id_cliente'] ?>'" class="px-3 py-1 rounded-lg border hover:bg-[var(--gris)] transition">Editar</button>
                      <button onclick="openDetalle(<?= $c['id_cliente'] ?>)" class="px-3 py-1 rounded-lg border bg-white hover:bg-[var(--gris)] transition">Ver</button>
                      <button onclick="confirmDelete(<?= $c['id_cliente'] ?>, '<?=htmlspecialchars(addslashes($c['nombre']))?>')" class="px-3 py-1 rounded-lg bg-[var(--rosa)] text-white hover:opacity-95 transition">Eliminar</button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

  <!-- ============================
       MODAL: DETALLES DEL CLIENTE (solo datos básicos)
       - "Ver más" -> detalle_cliente.php?id=...
       - "Cargar en formulario" -> editar_cliente
       ============================ -->
  <div id="modalDetalle" class="fixed inset-0 hidden items-center justify-center z-50">
    <div class="absolute inset-0 modal-backdrop" onclick="closeDetalle()"></div>
    <div class="relative bg-white rounded-2xl shadow-xl max-w-3xl w-full mx-4 overflow-hidden animate-slideUp">
      <div class="p-4 border-b flex items-center justify-between">
        <h3 class="text-lg font-semibold" id="modal-title">Detalle del cliente</h3>
        <button class="text-gray-500 hover:text-gray-700 text-2xl leading-none px-3" onclick="closeDetalle()">&times;</button>
      </div>

      <div class="p-6 space-y-4">
        <div id="detalle-contenido" class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <p class="text-sm text-gray-500">Nombre</p>
            <p id="d-nombre" class="font-medium text-gray-900">-</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Correo</p>
            <p id="d-correo" class="font-medium text-gray-900">-</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Celular</p>
            <p id="d-celular" class="font-medium text-gray-900">-</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Dirección</p>
            <p id="d-direccion" class="font-medium text-gray-900 truncate-2">-</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Código postal</p>
            <p id="d-cp" class="font-medium text-gray-900">-</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Estado</p>
            <p id="d-estado" class="font-medium text-gray-900">-</p>
          </div>
        </div>

        <div class="flex items-center justify-end gap-3">
          <a id="btnVerMas" href="" data-id="" class="px-4 py-2 rounded-lg border text-sm">Ver más</a>
          <button id="btnCargarEnForm" class="px-4 py-2 rounded-lg btn-primary">Cargar en formulario</button>
        </div>
        
      </div>
    </div>
  </div>

<script>
  // ----------------------
  // Utilidades: debounce
  // ----------------------
  function debounce(fn, ms){ let t; return (...args)=>{ clearTimeout(t); t=setTimeout(()=>fn(...args), ms); }; }

  // ----------------------
  // Real-time search (name/email unified)
  // ----------------------
  const inputSearch = document.getElementById('busqueda');
  const estadoFiltro = document.getElementById('estadoFiltro');
  const clearSearch = document.getElementById('clear-search');
  const tablaBody = document.getElementById('tabla-body');

  // build row HTML
  function rowHtml(item){
    const direccion = item.direccion || '-';
    return `<tr class="hover:bg-gray-50 transition">
      <td class="px-4 py-3 text-sm">${item.id_cliente}</td>
      <td class="px-4 py-3 text-sm">${escapeHtml(item.nombre)}</td>
      <td class="px-4 py-3 text-sm">${escapeHtml(item.celular)}</td>
      <td class="px-4 py-3 text-sm">${escapeHtml(item.correo)}</td>
      <td class="px-4 py-3 text-sm truncate-2">${escapeHtml(direccion)}</td>
      <td class="px-4 py-3 text-right">
        <div class="inline-flex gap-2">
          <button onclick="location.href='index.php?view=editar_cliente&id=${item.id_cliente}'" class="px-3 py-1 rounded-lg border hover:bg-[var(--gris)] transition">Editar</button>
          <button onclick="openDetalle(${item.id_cliente})" class="px-3 py-1 rounded-lg border bg-white hover:bg-[var(--gris)] transition">Ver</button>
          <button onclick="confirmDelete(${item.id_cliente}, '${escapeJs(item.nombre)}')" class="px-3 py-1 rounded-lg bg-[var(--rosa)] text-white hover:opacity-95 transition">Eliminar</button>
        </div>
      </td>
    </tr>`;
  }

  // escape helpers
  function escapeHtml(str){ if(!str) return ''; return String(str).replace(/[&<>"'`]/g, s=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','`':'&#96;' })[s]); }
  function escapeJs(str){ if(!str) return ''; return String(str).replace(/'/g,"\\'").replace(/\n/g,' '); }

  const doSearch = debounce(() => {
    const q = inputSearch.value.trim();
    const estado = estadoFiltro ? estadoFiltro.value.trim() : '';
    // call search endpoint
    fetch(`${location.pathname}?action=search&q=${encodeURIComponent(q)}&estado=${encodeURIComponent(estado)}`)
      .then(r => r.json())
      .then(json => {
        if (!json.success) {
          // leave server-rendered content
          return;
        }
        const rows = json.clientes || [];
        if (rows.length === 0) {
          tablaBody.innerHTML = '<tr><td colspan="6" class="p-6 text-center text-gray-500">No hay clientes registrados.</td></tr>';
          return;
        }
        tablaBody.innerHTML = rows.map(r => rowHtml(r)).join('');
      })
      .catch(err => {
        console.error('search err', err);
      });
  }, 300);

  inputSearch && inputSearch.addEventListener('input', () => {
    clearSearch.classList.remove('hidden');
    doSearch();
  });

  clearSearch && clearSearch.addEventListener('click', () => {
    inputSearch.value = '';
    clearSearch.classList.add('hidden');
    doSearch();
  });

  estadoFiltro && estadoFiltro.addEventListener('change', () => {
    doSearch();
  });

  // ----------------------
  // Add / Back
  // ----------------------
  document.getElementById('btnAgregar') && document.getElementById('btnAgregar').addEventListener('click', () => {
    window.location.href = 'index.php?view=agregar_cliente';
  });

  // ----------------------
  // Delete with SweetAlert
  // ----------------------
  function confirmDelete(id, nombre){
    Swal.fire({
      title: 'Eliminar cliente',
      html: `¿Eliminar a <strong>${nombre}</strong>?`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: 'var(--rosa)',
      confirmButtonText: 'Sí, eliminar',
    }).then((res)=>{
      if(res.isConfirmed){
        fetch(`${location.pathname}?action=delete`, {
          method: 'POST',
          headers: {'Content-Type':'application/json'},
          body: JSON.stringify({id: id})
        })
        .then(r => r.json())
        .then(json => {
          if(json.success){
            Swal.fire({title:'Eliminado', text:'Cliente eliminado correctamente', icon:'success', confirmButtonColor:'var(--verde)'}).then(()=> doSearch());
          } else {
            Swal.fire('Error', json.error || 'No se pudo eliminar','error');
          }
        })
        .catch(()=> Swal.fire('Error','Error en el servidor','error'));
      }
    });
  }

  // ----------------------
// Modal: abrir detalle (fetch cliente JSON)
// ----------------------
const modal = document.getElementById('modalDetalle');
const detalle = {
  nombre: document.getElementById('d-nombre'),
  correo: document.getElementById('d-correo'),
  celular: document.getElementById('d-celular'),
  direccion: document.getElementById('d-direccion'),
  cp: document.getElementById('d-cp'),
  estado: document.getElementById('d-estado'),
  btnVerMas: document.getElementById('btnVerMas'),
  btnCargarEnForm: document.getElementById('btnCargarEnForm')
};

let currentClienteId = null;

function openDetalle(id) {
  currentClienteId = id;
  modal.classList.remove('hidden');
  modal.classList.add('flex');

  // placeholder mientras carga
  detalle.nombre.textContent = 'Cargando...';
  detalle.correo.textContent = '';
  detalle.celular.textContent = '';
  detalle.direccion.textContent = '';
  detalle.cp.textContent = '';
  detalle.estado.textContent = '';

  // ---------- ADAPTADO A TU CÓDIGO ----------
 const url = `index.php?view=clientes&action=getCliente&id=${encodeURIComponent(id)}`;
  // ------------------------------------------

  fetch(url)
    .then(response => {
      // VALIDAMOS ANTES DE PARSEAR
      if (!response.ok) throw new Error('Respuesta no OK del servidor (HTTP ' + response.status + ')');
      return response.json();
    })
    .then(json => {
      // VALIDAMOS ESTRUCTURA JSON
      if (!json || typeof json !== 'object') {
        throw new Error('Respuesta inesperada del servidor (no es JSON válido).');
      }

      if (!json.success) {
        Swal.fire('Error', json.error || 'No se pudo obtener información', 'error');
        closeDetalle();
        return;
      }

      const c = json.cliente;

      detalle.nombre.textContent = `${c.nombre || ''} ${c.apellido_paterno || ''} ${c.apellido_materno || ''}`;
      detalle.correo.textContent = c.correo || '-';
      detalle.celular.textContent = c.celular || '-';
      detalle.direccion.textContent = [c.calle, c.num_ext, c.num_int, c.colonia].filter(Boolean).join(' ') || '-';
      detalle.cp.textContent = c.cp || '-';
      detalle.estado.textContent = c.estado || '-';

      // Botones del modal
    // dentro de openDetalle(id) después de cargar los datos:
        detalle.btnVerMas.setAttribute('data-id', id);
        detalle.btnVerMas.href = `index.php?view=detalle_cliente&id=${encodeURIComponent(id)}`;

      detalle.btnCargarEnForm.onclick = () => {
        window.location.href = 'index.php?view=editar_cliente&id=' + encodeURIComponent(id);
      };
    })
    .catch(err => {
      console.error('ERROR al cargar detalle cliente:', err);

      Swal.fire({
        title: 'Error',
        text: 'Error al cargar los datos del cliente. Detalle técnico: ' + err.message,
        icon: 'error',
        confirmButtonColor: '#b4c24d'
      });

      closeDetalle();
    });
}

function closeDetalle() {
  modal.classList.add('hidden');
  modal.classList.remove('flex');
  currentClienteId = null;
}

</script>
</body>
</html>
