<?php
// clientes_contenido_v5.php - Premium Modern Design (FINAL)
require_once __DIR__ . '/../config/db.php';

// AJAX: obtener cliente por id
if (isset($_GET['action']) && $_GET['action'] === "getCliente") {
    while (ob_get_level()) ob_end_clean();
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
    exit;
}

// AJAX: búsqueda en tiempo real
if (isset($_GET['action']) && $_GET['action'] === 'search') {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    $letter = isset($_GET['letter']) ? trim($_GET['letter']) : '';
    
    $whereParts = [];
    $params = [];
    
    if ($q !== '') {
        $whereParts[] = "(nombre LIKE :q OR apellido_paterno LIKE :q OR apellido_materno LIKE :q OR correo LIKE :q OR celular LIKE :q)";
        $params[':q'] = "%$q%";
    }
    
    if ($letter !== '' && $letter !== 'ALL') {
        $whereParts[] = "(nombre LIKE :letter OR apellido_paterno LIKE :letter)";
        $params[':letter'] = "$letter%";
    }
    
    $sql = "SELECT id_cliente, nombre, apellido_paterno, apellido_materno, celular, correo, calle, num_ext, num_int, colonia, cp, estado FROM clientes";
    if (!empty($whereParts)) $sql .= " WHERE " . implode(' AND ', $whereParts);
    $sql .= " ORDER BY nombre ASC, apellido_paterno ASC LIMIT 200";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $data = array_map(function($r){
            $displayName = trim($r['nombre'].' '.$r['apellido_paterno'].' '.$r['apellido_materno']);
            $direccion = trim(($r['calle'] ?? '') . ' ' . ($r['num_ext'] ?? '') . ' ' . ($r['num_int'] ?? '') . ' ' . ($r['colonia'] ?? '') . ' ' . ($r['cp'] ?? '') . ' ' . ($r['estado'] ?? ''));
            return [
                'id_cliente' => $r['id_cliente'],
                'nombre' => tr_content($displayName),
                'celular' => $r['celular'] ?? '',
                'correo' => $r['correo'] ?? '',
                'direccion' => tr_content($direccion),
                'estado' => tr_content($r['estado'] ?? ''),
                'inicial' => strtoupper(substr($r['nombre'], 0, 1))
            ];
        }, $rows);
        echo json_encode(['success'=>true,'clientes'=>$data]);
    } catch (Exception $e) {
        echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
    }
    exit;
}

// Parámetro de ordenamiento
$orden = $_GET['orden'] ?? 'nombre ASC';
$allowed_order = ['nombre ASC', 'nombre DESC'];
if(!in_array($orden, $allowed_order)) $orden = 'nombre ASC';

// Cargar clientes iniciales con ordenamiento
$sql = "SELECT id_cliente, nombre, apellido_paterno, apellido_materno, celular, correo, calle, num_ext, num_int, colonia, cp, estado FROM clientes ORDER BY $orden LIMIT 200";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?= __('customers_management') ?></title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['Poppins', 'sans-serif'],
          },
        },
      },
    }
  </script>
  
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    :root {
      --primary: #b4c24d;
      --primary-dark: #9fb03d;
      --secondary: #2d4353;
      --accent: #e15871;
      --gray-bg: #eeeeee;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #f9fafb 0%, #eeeeee 100%);
      min-height: 100vh;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    @keyframes slideDown {
      from { opacity: 0; transform: translateY(-15px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @keyframes slideUp {
      from { opacity: 0; transform: translateY(15px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @keyframes scaleIn {
      from { opacity: 0; transform: scale(0.96); }
      to { opacity: 1; transform: scale(1); }
    }

    .animate-fadeIn { animation: fadeIn 0.4s ease-out; }
    .animate-slideDown { animation: slideDown 0.5s cubic-bezier(0.4, 0, 0.2, 1); }
    .animate-slideUp { animation: slideUp 0.5s cubic-bezier(0.4, 0, 0.2, 1); }
    .animate-scaleIn { animation: scaleIn 0.3s cubic-bezier(0.4, 0, 0.2, 1); }

    .delay-100 { animation-delay: 0.1s; animation-fill-mode: both; }
    .delay-200 { animation-delay: 0.2s; animation-fill-mode: both; }

    .custom-checkbox {
      appearance: none;
      width: 1.125rem;
      height: 1.125rem;
      border: 2px solid #cbd5e1;
      border-radius: 0.375rem;
      background: white;
      cursor: pointer;
      transition: all 0.2s;
      position: relative;
    }

    .custom-checkbox:checked {
      background: var(--primary);
      border-color: var(--primary);
    }

    .custom-checkbox:checked::after {
      content: '✓';
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      color: white;
      font-size: 0.75rem;
      font-weight: 700;
    }

    /* Dropdown con z-index alto para que siempre esté encima */
    .dropdown {
      position: relative;
    }

    .dropdown-menu {
      position: absolute;
      background: white;
      border-radius: 1rem;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
      padding: 1.25rem;
      min-width: 320px;
      z-index: 1000;
      opacity: 0;
      transform: translateY(10px) scale(0.95);
      pointer-events: none;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      border: 1px solid rgba(0, 0, 0, 0.06);
    }

    .dropdown-menu.active {
      opacity: 1;
      transform: translateY(0) scale(1);
      pointer-events: all;
    }

    .table-row {
      transition: all 0.2s ease;
    }

    .table-row:hover {
      background: rgba(180, 194, 77, 0.04);
    }

    .table-row.selected {
      background: linear-gradient(90deg, rgba(180, 194, 77, 0.15) 0%, rgba(180, 194, 77, 0.08) 100%);
      border-left: 3px solid #b4c24d;
    }

    .fab {
      position: fixed;
      bottom: 2rem;
      right: 2rem;
      width: 3.5rem;
      height: 3.5rem;
      border-radius: 50%;
      background: linear-gradient(135deg, #2d4353 0%, #1e2d38 100%);
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 8px 24px rgba(45, 67, 83, 0.3);
      cursor: pointer;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      z-index: 40;
    }

    .fab:hover {
      transform: scale(1.1) rotate(90deg);
      box-shadow: 0 12px 32px rgba(45, 67, 83, 0.4);
    }

    .empty-state {
      padding: 4rem 2rem;
      text-align: center;
    }

    .modal-backdrop {
      backdrop-filter: blur(8px);
      background: rgba(0, 0, 0, 0.4);
    }

    .letter-pill {
      transition: all 0.2s ease;
      cursor: pointer;
    }

    .letter-pill:hover {
      background: rgba(180, 194, 77, 0.1);
      transform: scale(1.05);
    }

    .letter-pill.active {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      color: white;
      box-shadow: 0 2px 8px rgba(180, 194, 77, 0.3);
    }

    .hover-lift {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .hover-lift:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
    }

    .btn-primary {
      background: var(--primary);
      color: white;
    }

    .truncate-2 {
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    /* Barra de búsqueda mejorada */
    .search-input {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .search-input:focus {
      box-shadow: 0 0 0 4px rgba(180, 194, 77, 0.1);
    }

    .search-input.has-value {
      border-color: var(--primary);
      background: rgba(180, 194, 77, 0.02);
    }

    /* Tema oscuro */
    /* Textos oscuros en modo oscuro */
    body.dark-mode .text-gray-500,
    body.dark-mode .text-gray-600,
    body.dark-mode .text-gray-700,
    body.dark-mode .text-gray-800,
    body.dark-mode .text-gray-900 {
        color: #f5f5f5 !important; /* texto claro */
    }
    /* Sobrescribir fondos blancos */
body.dark-mode .bg-white,
body.dark-mode .bg-gray-50,
body.dark-mode .bg-gray-100 {
    background-color: #1e1e1e !important;
}
body.dark-mode table,
body.dark-mode thead,
body.dark-mode tbody,
body.dark-mode tr,
body.dark-mode td,
body.dark-mode th {
    background-color: #1e1e1e !important;
    color: #f5f5f5 !important;
}

body.dark-mode .divide-gray-200 {
    border-color: #444 !important;
}
body.dark-mode .border-gray-100,
body.dark-mode .border-gray-200,
body.dark-mode .border-gray-300 {
    border-color: #333 !important;
}
body.dark-mode span[style*="rgba("] {
    background-color: rgba(255,255,255,0.1) !important;
}
body.dark-mode button[style*="linear-gradient"] {
    filter: brightness(0.8);
}
body.dark-mode main,
body.dark-mode .content {
    background-color: #121212 !important;
}
body.dark-mode .dropdown-menu {
    background: #1e1e1e !important;
    border-color: #333 !important;
}

body.dark-mode .dropdown-menu p,
body.dark-mode .dropdown-menu select,
body.dark-mode .dropdown-menu option {
    color: #ffffff !important;
    background: #2a2a2a !important;
}
body.dark-mode select {
    background: #2c2c2c !important;
    color: #fff !important;
    border-color: #000 !important;
}
/* Botón Eliminar (modo oscuro) */
body.dark-mode .btn-eliminar {
    background-color: #b30000 !important;  /* Rojo fuerte */
    color: #fff !important;
    border: none !important;
}

body.dark-mode .btn-eliminar:hover {
    background-color: #cc0000 !important; /* Rojo más brillante */
}


body.dark-mode .btn-add:hover {
    background-color: #b4c24d !important;
}

/* BOTÓN AGREGAR EN MODO OSCURO */
body.dark-mode .btn-add {
  background-color: #b4c24d !important;
}
  </style>
  
</head>

<body class="p-4 md:p-6">
  <div class="max-w-7xl mx-auto pb-32">
    
    <!-- Header -->
    <div class="mb-8 animate-slideDown">
      <div class="mb-6">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2"><?= __('customers_management') ?></h1>
        <p class="text-gray-600 text-base"><?= __('customers_subtitle') ?></p>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl p-5 shadow-lg hover-lift animate-slideUp border border-gray-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 text-sm font-medium mb-1"><?= __('total_customers') ?></p>
              <p id="totalClientes" class="text-3xl font-bold text-gray-900"><?= count($clientes) ?></p>
            </div>
            <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
              <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.653-.124-1.28-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.653.124-1.28.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a4 4 0 11-8 0 4 4 0 018 0z"/>
              </svg>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-lg hover-lift animate-slideUp delay-100 border border-gray-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 text-sm font-medium mb-1"><?= __('selected') ?></p>
              <p id="selectedCount" class="text-3xl font-bold" style="color: #b4c24d;">0</p>
            </div>
            <div class="w-14 h-14 rounded-xl flex items-center justify-center shadow-lg" style="background: linear-gradient(135deg, #b4c24d 0%, #9fb03d 100%);">
              <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
              </svg>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-lg hover-lift animate-slideUp delay-200 border border-gray-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 text-sm font-medium mb-1"><?= __('results') ?></p>
              <p id="filteredCount" class="text-3xl font-bold text-gray-900"><?= count($clientes) ?></p>
            </div>
            <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center shadow-lg">
              <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11"/>
              </svg>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="mb-6 animate-slideDown delay-100">
      <form id="toolbar-form" class="flex flex-col md:flex-row items-center justify-between gap-4">
        <!-- Search -->
        <div class="relative w-full md:w-1/2 lg:w-1/3">
          <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
            <svg class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input 
              id="searchInput" 
              type="text" 
              placeholder="<?= __('search_customer_placeholder') ?>" 
              class="search-input w-full pl-12 pr-12 py-3.5 rounded-xl border-2 border-gray-200 focus:border-primary focus:outline-none transition-all duration-200 text-gray-900 placeholder-gray-400 font-medium"
            />
            <button type="button" id="clearSearch" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-accent transition-colors hidden">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <!-- Order Button -->
          <div class="relative">
            <button type="button" id="orderBtn" class="inline-flex items-center gap-2 px-5 py-3.5 rounded-xl bg-white border-2 border-gray-200 text-gray-700 font-semibold hover:bg-gray-50 transition-all duration-200">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
              </svg>
              <?= __('sort') ?>
              <svg class="w-4 h-4 transition-transform duration-300" id="orderIcon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
              </svg>
            </button>

            <div id="orderMenu" class="dropdown-menu" style="top: auto; bottom: calc(100% + 0.75rem);">
              <div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3"><?= __('sort_by') ?></p>
                <select name="orden" onchange="document.getElementById('toolbar-form').submit()" class="w-full px-3 py-2 rounded-lg border-2 border-gray-200 focus:border-primary focus:outline-none">
                  <option value="nombre ASC" <?= ($orden == 'nombre ASC') ? 'selected' : '' ?>><?= __('name_az') ?></option>
                  <option value="nombre DESC" <?= ($orden == 'nombre DESC') ? 'selected' : '' ?>><?= __('name_za') ?></option>
                </select>
              </div>
            </div>
          </div>

          <!-- Add Button -->
          <button type="button" onclick="window.location.href='index.php?view=agregar_cliente'" class="inline-flex items-center gap-2 px-6 py-3.5 rounded-xl text-white font-semibold shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-200" style="background: linear-gradient(135deg, #2d4353 0%, #1e2d38 100%);">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <?= __('add_customer') ?>
          </button>
        </div>
      </form>

      <!-- Bulk Actions -->
      <div id="bulkActions" class="hidden mt-5 bg-gradient-to-r from-primary/10 to-primary/5 rounded-xl p-4 border-2 border-primary/20">
        <div class="flex items-center justify-between flex-wrap gap-3">
          <div class="flex items-center gap-3">
            <input type="checkbox" id="selectAll" class="custom-checkbox" />
            <span class="text-sm font-semibold text-gray-700">
              <span id="bulkSelectedCount">0</span> <?= __('customers_selected') ?>
            </span>
          </div>
          <div class="flex gap-2">
            <button onclick="bulkDelete()" class="px-5 py-2.5 text-white rounded-lg font-semibold transition-all flex items-center gap-2 shadow-md hover:shadow-lg hover:scale-105" style="background: linear-gradient(135deg, #e15871 0%, #d14560 100%);">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
              <?= __('delete') ?>
            </button>
            <button onclick="clearSelection()" class="px-5 py-2.5 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition-all shadow-sm">
              <?= __('cancel') ?>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden animate-slideUp delay-200 border border-gray-100 mb-8">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead style="background: linear-gradient(135deg, #2d4353 0%, #1e2d38 100%);">
            <tr>
              <th class="px-6 py-4 text-left">
                <input type="checkbox" id="selectAllHeader" class="custom-checkbox" />
              </th>
              <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider"><?= __('hash_col') ?></th>
              <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider"><?= __('name_col') ?></th>
              <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider"><?= __('phone_col') ?></th>
              <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider"><?= __('email_col') ?></th>
              <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider"><?= __('address_col') ?></th>
              <th class="px-6 py-4 text-right text-xs font-bold text-white uppercase tracking-wider"><?= __('actions_col') ?></th>
            </tr>
          </thead>
          <tbody id="tableBody" class="bg-white divide-y divide-gray-200">
            <!-- Rows will be injected by JS -->
          </tbody>
        </table>
      </div>
      
      <div id="emptyState" class="hidden empty-state">
        <svg class="w-24 h-24 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
        </svg>
        <h3 class="text-xl font-semibold text-gray-700 mb-2"><?= __('no_customers_found') ?></h3>
        <p class="text-gray-500"><?= __('try_adjusting_filters_or_add') ?></p>
      </div>
    </div>

  </div>

  <!-- FAB -->
  <button class="fab" onclick="window.location.href='index.php?view=agregar_cliente'">
    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
  </button>

  <!-- Modal Detalle -->
  <div id="modalDetalle" class="fixed inset-0 bg-black bg-opacity-60 hidden items-center justify-center z-50 p-4">
    <div class="relative bg-white rounded-2xl shadow-2xl max-w-3xl w-full mx-4 overflow-hidden animate-slideUp">
      <!-- Header con gradiente -->
      <div class="px-6 py-4 border-b flex items-center justify-between" style="background: linear-gradient(135deg, #2d4353 0%, #1e2d38 100%);">
        <h3 class="text-xl font-bold text-white flex items-center gap-2">
          <svg class="w-6 h-6 text-[#b4c24d]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
          <?= __('customer_details') ?>
        </h3>
        <button class="text-white/70 hover:text-white text-2xl leading-none transition-colors" onclick="closeDetalle()">&times;</button>
      </div>

      <div class="p-8 space-y-6">
        <div id="detalle-contenido" class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
          <!-- Nombre -->
          <div class="md:col-span-2 group">
            <p class="text-xs font-bold text-[#b4c24d] uppercase tracking-wider mb-1 flex items-center gap-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
              <?= __('full_name') ?>
            </p>
            <p id="d-nombre" class="text-lg font-semibold text-gray-800 border-b border-gray-100 pb-1">-</p>
          </div>
          
          <!-- Correo -->
          <div class="group">
            <p class="text-xs font-bold text-[#b4c24d] uppercase tracking-wider mb-1 flex items-center gap-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
              <?= __('email_address') ?>
            </p>
            <p id="d-correo" class="text-base text-gray-700">-</p>
          </div>

          <!-- Celular -->
          <div class="group">
            <p class="text-xs font-bold text-[#b4c24d] uppercase tracking-wider mb-1 flex items-center gap-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
              <?= __('phone_col') ?>
            </p>
            <p id="d-celular" class="text-base text-gray-700">-</p>
          </div>

          <!-- Dirección -->
          <div class="md:col-span-2 group">
            <p class="text-xs font-bold text-[#b4c24d] uppercase tracking-wider mb-1 flex items-center gap-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
              <?= __('full_address') ?>
            </p>
            <p id="d-direccion" class="text-base text-gray-700 border-b border-gray-100 pb-1">-</p>
          </div>

          <!-- CP -->
          <div class="group">
            <p class="text-xs font-bold text-[#b4c24d] uppercase tracking-wider mb-1 flex items-center gap-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
              <?= __('zip_code') ?>
            </p>
            <p id="d-cp" class="text-base text-gray-700">-</p>
          </div>

          <!-- Estado -->
          <div class="group">
            <p class="text-xs font-bold text-[#b4c24d] uppercase tracking-wider mb-1 flex items-center gap-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              <?= __('state') ?>
            </p>
            <p id="d-estado" class="text-base text-gray-700">-</p>
          </div>
        </div>

        <div class="flex items-center justify-end gap-3">
          <a id="btnVerMas" href="" data-id="" class="px-4 py-2 rounded-lg btn-primary"><?= __('view_more') ?></a>
        </div>
      </div>
    </div>
  </div>

  <script>
    let allClientes = <?= json_encode($clientes) ?>;
    let filteredClientes = [...allClientes];
    let selectedIds = new Set();
    let currentLetter = 'ALL';
    let currentSearch = '';

    function debounce(fn, ms) {
      let timeout;
      return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => fn.apply(this, args), ms);
      };
    }

    function escapeHtml(str) {
      if (!str) return '';
      const div = document.createElement('div');
      div.textContent = str;
      return div.innerHTML;
    }

    function init() {
      renderTable();
      setupEventListeners();
      updateStats();
    }

    function setupEventListeners() {
      const searchInput = document.getElementById('searchInput');
      const clearSearch = document.getElementById('clearSearch');
      
      // BÚSQUEDA EN TIEMPO REAL (exactamente como nueva_venta.php)
      searchInput.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase().trim();
        const rows = document.querySelectorAll('#tableBody .table-row');
        
        // Mostrar/ocultar botón de limpiar
        if (searchTerm) {
          clearSearch.classList.remove('hidden');
          this.classList.add('has-value');
        } else {
          clearSearch.classList.add('hidden');
          this.classList.remove('has-value');
        }
        
        // Filtrar filas en tiempo real
        rows.forEach(row => {
          const nombre = row.querySelector('td:nth-child(3)')?.textContent.toLowerCase() || '';
          const celular = row.querySelector('td:nth-child(4)')?.textContent.toLowerCase() || '';
          const correo = row.querySelector('td:nth-child(5)')?.textContent.toLowerCase() || '';
          const direccion = row.querySelector('td:nth-child(6)')?.textContent.toLowerCase() || '';
          
          if (nombre.includes(searchTerm) || celular.includes(searchTerm) || correo.includes(searchTerm) || direccion.includes(searchTerm)) {
            row.style.display = '';
          } else {
            row.style.display = 'none';
          }
        });
        
        // Actualizar estado vacío
        const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
        const emptyState = document.getElementById('emptyState');
        if (visibleRows.length === 0 && searchTerm) {
          emptyState.classList.remove('hidden');
        } else {
          emptyState.classList.add('hidden');
        }
      });

      clearSearch.addEventListener('click', () => {
        searchInput.value = '';
        clearSearch.classList.add('hidden');
        searchInput.classList.remove('has-value');
        // Mostrar todas las filas
        document.querySelectorAll('#tableBody .table-row').forEach(row => row.style.display = '');
        // Ocultar empty state
        document.getElementById('emptyState').classList.add('hidden');
      });

      // Dropdown de ordenamiento
      const orderBtn = document.getElementById('orderBtn');
      const orderMenu = document.getElementById('orderMenu');
      const orderIcon = document.getElementById('orderIcon');

      if (orderBtn) {
        orderBtn.addEventListener('click', (e) => {
          e.stopPropagation();
          orderMenu.classList.toggle('active');
          orderIcon.style.transform = orderMenu.classList.contains('active') ? 'rotate(180deg)' : 'rotate(0deg)';
        });

        document.addEventListener('click', (e) => {
          if (!orderMenu.contains(e.target) && !orderBtn.contains(e.target)) {
            orderMenu.classList.remove('active');
            orderIcon.style.transform = 'rotate(0deg)';
          }
        });
      }

      document.getElementById('selectAll')?.addEventListener('change', function() {
        if (this.checked) {
          filteredClientes.forEach(c => selectedIds.add(c.id_cliente));
        } else {
          selectedIds.clear();
        }
        renderTable();
        updateBulkActions();
      });

      document.getElementById('selectAllHeader').addEventListener('change', function() {
        if (this.checked) {
          filteredClientes.forEach(c => selectedIds.add(c.id_cliente));
        } else {
          selectedIds.clear();
        }
        renderTable();
        updateBulkActions();
      });
    }

    function performSearch() {
      // Construir URL correctamente
      const currentUrl = new URL(window.location.href);
      currentUrl.searchParams.set('action', 'search');
      currentUrl.searchParams.set('q', currentSearch);
      currentUrl.searchParams.set('letter', currentLetter);
      
      console.log('Buscando con:', { search: currentSearch, letter: currentLetter }); // Debug
      
      fetch(currentUrl.toString())
        .then(r => {
          if (!r.ok) throw new Error(`HTTP ${r.status}`);
          return r.json();
        })
        .then(json => {
          console.log('Resultados:', json.clientes?.length || 0); // Debug
          if (json.success) {
            filteredClientes = json.clientes || [];
            renderTable();
            updateStats();
          } else {
            console.error('Error en búsqueda:', json.error);
            Swal.fire('Error', json.error || 'Error al buscar', 'error');
          }
        })
        .catch(err => {
          console.error('Search error:', err);
          // No mostrar error en cada búsqueda, solo en consola
        });
    }

    function renderTable() {
      const tbody = document.getElementById('tableBody');
      const emptyState = document.getElementById('emptyState');
      
      if (filteredClientes.length === 0) {
        tbody.innerHTML = '';
        emptyState.classList.remove('hidden');
        return;
      }

      emptyState.classList.add('hidden');
      
      tbody.innerHTML = filteredClientes.map((cliente, index) => {
        const isSelected = selectedIds.has(cliente.id_cliente);
        const displayName = escapeHtml(cliente.nombre || `${cliente.nombre} ${cliente.apellido_paterno || ''} ${cliente.apellido_materno || ''}`);
        const direccion = escapeHtml(cliente.direccion || '-');
        
        return `
          <tr class="table-row ${isSelected ? 'selected' : ''}" data-id="${cliente.id_cliente}">
            <td class="px-6 py-4">
              <input type="checkbox" class="custom-checkbox row-checkbox" data-id="${cliente.id_cliente}" ${isSelected ? 'checked' : ''} />
            </td>
            <td class="px-6 py-4 text-sm font-semibold text-gray-900">${index + 1}</td>
            <td class="px-6 py-4">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm shadow-md" style="background: linear-gradient(135deg, #b4c24d 0%, #9fb03d 100%);">
                  ${(cliente.inicial || cliente.nombre?.charAt(0) || 'C').toUpperCase()}
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-900">${displayName}</p>
                </div>
              </div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-700 font-medium">${escapeHtml(cliente.celular || '-')}</td>
            <td class="px-6 py-4 text-sm text-gray-700">${escapeHtml(cliente.correo || '-')}</td>
            <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate">${direccion}</td>
            <td class="px-6 py-4 text-right">
              <div class="inline-flex gap-2">
                <button onclick="openDetalle(${cliente.id_cliente})" class="px-3 py-1.5 bg-blue-50 text-blue-600 rounded-lg text-sm font-semibold hover:bg-blue-100 transition-colors">
                  <?= __('view') ?>
                </button>
                <button onclick="window.location.href='index.php?view=editar_cliente&id=${cliente.id_cliente}'" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-200 transition-colors">
                  <?= __('edit') ?>
                </button>
                <button onclick="confirmDelete(${cliente.id_cliente}, '${escapeHtml(displayName).replace(/'/g, "\\'")}')" class="px-3 py-1.5 bg-red-50 text-red-600 rounded-lg text-sm font-semibold hover:bg-red-100 transition-colors">
                  <?= __('delete') ?>
                </button>
              </div>
            </td>
          </tr>
        `;
      }).join('');

      document.querySelectorAll('.row-checkbox').forEach(cb => {
        cb.addEventListener('change', function() {
          const id = parseInt(this.dataset.id);
          if (this.checked) {
            selectedIds.add(id);
          } else {
            selectedIds.delete(id);
          }
          updateBulkActions();
          renderTable();
        });
      });
    }

    function updateStats() {
      document.getElementById('totalClientes').textContent = allClientes.length;
      document.getElementById('filteredCount').textContent = filteredClientes.length;
      document.getElementById('selectedCount').textContent = selectedIds.size;
    }

    function updateBulkActions() {
      const bulkActions = document.getElementById('bulkActions');
      const count = selectedIds.size;
      
      if (count > 0) {
        bulkActions.classList.remove('hidden');
        document.getElementById('bulkSelectedCount').textContent = count;
      } else {
        bulkActions.classList.add('hidden');
      }
      
      updateStats();
    }

    function clearSelection() {
      selectedIds.clear();
      document.getElementById('selectAll').checked = false;
      document.getElementById('selectAllHeader').checked = false;
      renderTable();
      updateBulkActions();
    }

    function confirmDelete(id, nombre) {
      Swal.fire({
        title: '<?= __('confirm_delete_customer_title') ?>',
        html: `<?= __('confirm_delete_customer_text') ?> <strong>${nombre}</strong>?<br><span class="text-sm text-gray-500"><?= __('action_cannot_be_undone') ?></span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e15871',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<?= __('yes_delete') ?>',
        cancelButtonText: '<?= __('cancel') ?>',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
          deleteCliente(id);
        }
      });
    }

    function deleteCliente(id) {
      fetch(`index.php?view=eliminar_cliente`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id: id})
      })
      .then(r => r.json())
      .then(json => {
        if (json.success) {
          Swal.fire({
            title: '<?= __('deleted') ?>',
            text: '<?= __('customer_deleted_successfully') ?>',
            icon: 'success',
            confirmButtonColor: '#b4c24d',
            timer: 2000
          });
          performSearch();
        } else {
          Swal.fire('<?= __('error') ?>', json.error || '<?= __('could_not_delete') ?>', 'error');
        }
      })
      .catch(() => Swal.fire('<?= __('error') ?>', '<?= __('server_error') ?>', 'error'));
    }

    function bulkDelete() {
      const count = selectedIds.size;
      
      Swal.fire({
        title: '<?= __('confirm_delete_multiple_title') ?>',
        html: `<?= __('confirm_delete_multiple_text') ?> <strong>${count}</strong> <?= __('customers_suffix') ?><br><span class="text-sm text-gray-500"><?= __('action_cannot_be_undone') ?></span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e15871',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<?= __('yes_delete_all') ?>',
        cancelButtonText: '<?= __('cancel') ?>',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
          const ids = Array.from(selectedIds);
          fetch(`index.php?view=eliminar_clientes_multiple`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ids: ids})
          })
          .then(r => r.json())
          .then(json => {
            if (json.success) {
              Swal.fire({
                title: '<?= __('deleted_multiple') ?>',
                text: `${json.count} <?= __('customers_deleted_successfully') ?>`,
                icon: 'success',
                confirmButtonColor: '#b4c24d',
                timer: 2000
              });
              clearSelection();
              performSearch();
            } else {
              Swal.fire('<?= __('error') ?>', json.error || '<?= __('could_not_delete') ?>', 'error');
            }
          })
          .catch(() => Swal.fire('<?= __('error') ?>', '<?= __('server_error') ?>', 'error'));
        }
      });
    }

    // MODAL DETALLE (VERSIÓN ORIGINAL - LÓGICA INTACTA)
    const modal = document.getElementById('modalDetalle');
    const detalle = {
      nombre: document.getElementById('d-nombre'),
      correo: document.getElementById('d-correo'),
      celular: document.getElementById('d-celular'),
      direccion: document.getElementById('d-direccion'),
      cp: document.getElementById('d-cp'),
      estado: document.getElementById('d-estado'),
      btnVerMas: document.getElementById('btnVerMas')
    };

    function openDetalle(id) {
      modal.classList.remove('hidden');
      modal.classList.add('flex');
      detalle.nombre.textContent = '<?= __('loading') ?>...';

      const url = `index.php?view=clientes&action=getCliente&id=${encodeURIComponent(id)}`;

      fetch(url)
        .then(response => { if (!response.ok) throw new Error('HTTP ' + response.status); return response.json(); })
        .then(json => {
          if (!json.success) { Swal.fire('<?= __('error') ?>', json.error || '<?= __('could_not_get_info') ?>', 'error'); closeDetalle(); return; }
          const c = json.cliente;
          detalle.nombre.textContent = `${c.nombre || ''} ${c.apellido_paterno || ''} ${c.apellido_materno || ''}`;
          detalle.correo.textContent = c.correo || '-';
          detalle.celular.textContent = c.celular || '-';
          detalle.direccion.textContent = c.direccion || '-';
          detalle.cp.textContent = c.cp || '-';
          detalle.estado.textContent = c.estado || '-';
          detalle.btnVerMas.href = `index.php?view=detalle_cliente&id=${encodeURIComponent(id)}`;
        })
        .catch(err => { console.error(err); Swal.fire('<?= __('error') ?>','<?= __('error_loading_data') ?>: '+err.message,'error'); closeDetalle(); });
    }
    
    function closeDetalle(){ modal.classList.add('hidden'); modal.classList.remove('flex'); }

    document.addEventListener('DOMContentLoaded', init);
  </script>
</body>
</html>
