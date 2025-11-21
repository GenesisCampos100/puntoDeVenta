<?php 
// empleados_contenido_v5.php - Premium Modern Design (Same as clientes_contenido_v5)
require_once __DIR__ . '/../config/db.php';

$busqueda = $_GET['busqueda'] ?? '';
$puesto = $_GET['puesto'] ?? '';
$orden = $_GET['orden'] ?? 'e.nombre ASC';
$allowed_order = ['e.nombre ASC', 'e.nombre DESC', '.id_empleado ASC', 'e.id_empleado DESC'];
if(!in_array($orden, $allowed_order)) $orden = 'e.nombre ASC';
$vista_actual = $_GET['view'] ?? 'empleados_contenido';

$sql = "SELECT
            e.id_empleado AS numero,
            CONCAT(e.nombre, ' ', e.apellido_paterno, ' ', e.apellido_materno) AS nombre_completo,
            u.correo AS correo,
            e.estatus AS estatus,
            e.fecha AS fecha
        FROM usuarios u 
        INNER JOIN empleados e ON u.id_empleado = e.id_empleado
        LEFT JOIN roles r ON e.id_rol = r.id_rol
        WHERE 1=1";

if(!empty($busqueda)) $sql .= " AND (
                            e.id_empleado LIKE :busqueda
                            OR e.nombre LIKE :busqueda
                            OR e.apellido_paterno LIKE :busqueda
                            OR e.apellido_materno LIKE :busqueda
                            OR u.correo LIKE :busqueda)";
if(!empty($puesto)) $sql .= " AND e.id_rol = :puesto";

$sql .= " ORDER BY $orden";

$stmt = $pdo->prepare($sql);

$params = [];

if(!empty($busqueda)) {
    $params[':busqueda'] = "%$busqueda%";
}

if(!empty($puesto)) {
    $params[':puesto'] = $puesto;
}

$stmt->execute($params);
$empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt_roles = $pdo->query("SELECT id_rol, nombre_rol FROM roles");
$puestos = $stmt_roles->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Gestión de Empleados</title>

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

    .dropdown {
      position: relative;
    }

    .dropdown-menu {
      position: absolute;
      background: white;
      border-radius: 1rem;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
      padding: 1.25rem;
      min-width: 280px;
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
      background: linear-gradient(135deg, var(--secondary) 0%, #1e2d38 100%);
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

    .hover-lift {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .hover-lift:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
    }

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

    .empty-state {
      padding: 4rem 2rem;
      text-align: center;
    }
  </style>
</head>
<body class="p-4 md:p-6">
  <div class="max-w-7xl mx-auto pb-32">
    
    <!-- Header -->
    <div class="mb-8 animate-slideDown">
      <div class="mb-6">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">Gestión de Empleados</h1>
        <p class="text-gray-600 text-base">Administra y organiza tu equipo de trabajo de forma eficiente</p>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl p-5 shadow-lg hover-lift animate-slideUp border border-gray-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 text-sm font-medium mb-1">Total Empleados</p>
              <p class="text-3xl font-bold text-gray-900"><?= count($empleados) ?></p>
            </div>
            <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
              <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-lg hover-lift animate-slideUp delay-100 border border-gray-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 text-sm font-medium mb-1">Activos</p>
              <p class="text-3xl font-bold" style="color: #b4c24d;"><?= count(array_filter($empleados, fn($e) => $e['estatus'] == 1)) ?></p>
            </div>
            <div class="w-14 h-14 rounded-xl flex items-center justify-center shadow-lg" style="background: linear-gradient(135deg, #b4c24d 0%, #9fb03d 100%);">
              <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-lg hover-lift animate-slideUp delay-200 border border-gray-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 text-sm font-medium mb-1">Inactivos</p>
              <p class="text-3xl font-bold text-gray-900"><?= count(array_filter($empleados, fn($e) => $e['estatus'] == 0)) ?></p>
            </div>
            <div class="w-14 h-14 bg-gradient-to-br from-red-500 to-pink-600 rounded-xl flex items-center justify-center shadow-lg">
              <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
              </svg>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Search and Filters -->
    <div class="bg-white rounded-2xl shadow-lg p-6 mb-6 animate-slideUp delay-100 border border-gray-100">
      <form method="GET" action="index.php" id="toolbar-form">
        <input type="hidden" name="view" value="empleados">
        
        <div class="flex flex-col md:flex-row gap-4 items-stretch md:items-center">
          <!-- Search Bar -->
          <div class="relative flex-1">
            <svg class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input 
              id="busqueda-input" 
              name="busqueda"
              type="text" 
              placeholder="Buscar por nombre, correo o número..." 
              value="<?= htmlspecialchars($busqueda) ?>"
              class="search-input w-full pl-12 pr-12 py-3.5 rounded-xl border-2 border-gray-200 focus:border-primary focus:outline-none transition-all duration-200 text-gray-900 placeholder-gray-400 font-medium"
            />
            <button type="button" onclick="document.getElementById('busqueda-input').value=''; document.getElementById('toolbar-form').submit();" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-accent transition-colors <?= empty($busqueda) ? 'hidden' : '' ?>">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <!-- Filter Dropdown (Puesto) -->
          <div class="dropdown">
            <button type="button" id="filterBtn" class="inline-flex items-center gap-2 px-6 py-3.5 rounded-xl bg-gradient-to-r from-gray-50 to-gray-100 border-2 border-gray-200 text-gray-700 font-semibold hover:border-gray-300 hover:shadow-lg transition-all duration-200">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
              </svg>
              Filtrar
              <svg class="w-4 h-4 transition-transform duration-300" id="filterIcon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
              </svg>
            </button>

            <div id="filterMenu" class="dropdown-menu" style="top: auto; bottom: calc(100% + 0.75rem);">
              <div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Filtrar por puesto</p>
                <select name="puesto" onchange="document.getElementById('toolbar-form').submit()" class="w-full px-3 py-2 rounded-lg border-2 border-gray-200 focus:border-primary focus:outline-none">
                  <option value="">-- Todos los puestos --</option>
                  <?php foreach ($puestos as $pu): ?>
                    <option value="<?= $pu['id_rol']?>" <?= ($puesto == $pu['id_rol']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($pu['nombre_rol']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>

          <!-- Order Dropdown -->
          <div class="dropdown">
            <button type="button" id="orderBtn" class="inline-flex items-center gap-2 px-6 py-3.5 rounded-xl bg-gradient-to-r from-gray-50 to-gray-100 border-2 border-gray-200 text-gray-700 font-semibold hover:border-gray-300 hover:shadow-lg transition-all duration-200">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
              </svg>
              Ordenar
              <svg class="w-4 h-4 transition-transform duration-300" id="orderIcon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
              </svg>
            </button>

            <div id="orderMenu" class="dropdown-menu" style="top: auto; bottom: calc(100% + 0.75rem);">
              <div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Ordenar por</p>
                <select name="orden" onchange="document.getElementById('toolbar-form').submit()" class="w-full px-3 py-2 rounded-lg border-2 border-gray-200 focus:border-primary focus:outline-none">
                  <option value="e.nombre ASC" <?= ($orden == 'e.nombre ASC') ? 'selected' : '' ?>>Nombre A-Z</option>
                  <option value="e.nombre DESC" <?= ($orden == 'e.nombre DESC') ? 'selected' : '' ?>>Nombre Z-A</option>
                  <option value="e.id_empleado ASC" <?= ($orden == 'e.id_empleado ASC') ? 'selected' : '' ?>>No. Empleado A-Z</option>
                  <option value="e.id_empleado DESC" <?= ($orden == 'e.id_empleado DESC') ? 'selected' : '' ?>>No. Empleado Z-A</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Add Button -->
          <button type="button" onclick="window.location.href='index.php?view=agregar_empleado'" class="inline-flex items-center gap-2 px-6 py-3.5 rounded-xl text-white font-semibold shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-200" style="background: linear-gradient(135deg, #2d4353 0%, #1e2d38 100%);">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Agregar
          </button>
        </div>
      </form>

      <!-- Bulk Actions -->
      <div id="bulkActions" class="hidden mt-5 bg-gradient-to-r from-primary/10 to-primary/5 rounded-xl p-4 border-2 border-primary/20">
        <div class="flex items-center justify-between flex-wrap gap-3">
          <div class="flex items-center gap-3">
            <input type="checkbox" id="selectAll" class="custom-checkbox" />
            <span class="text-sm font-semibold text-gray-700">
              <span id="bulkSelectedCount">0</span> empleado(s) seleccionado(s)
            </span>
          </div>
          <div class="flex gap-2">
            <button onclick="bulkDelete()" class="px-5 py-2.5 text-white rounded-lg font-semibold transition-all flex items-center gap-2 shadow-md hover:shadow-lg hover:scale-105" style="background: linear-gradient(135deg, #e15871 0%, #d14560 100%);">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
              Eliminar
            </button>
            <button onclick="clearSelection()" class="px-5 py-2.5 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition-all shadow-sm">
              Cancelar
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
              <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">No.</th>
              <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Nombre Completo</th>
              <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Correo</th>
              <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Estado</th>
              <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Fecha de Ingreso</th>
              <th class="px-6 py-4 text-right text-xs font-bold text-white uppercase tracking-wider">Acciones</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <?php if (!empty($empleados)): ?>
              <?php foreach ($empleados as $emp): ?>
                <tr class="table-row" data-id="<?= $emp['numero'] ?>">
                  <td class="px-6 py-4">
                    <input type="checkbox" class="custom-checkbox row-checkbox" data-id="<?= $emp['numero'] ?>" />
                  </td>
                  <td class="px-6 py-4 text-sm font-semibold text-gray-900"><?= htmlspecialchars($emp['numero']) ?></td>
                  <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm shadow-md" style="background: linear-gradient(135deg, #b4c24d 0%, #9fb03d 100%);">
                        <?= strtoupper(substr($emp['nombre_completo'], 0, 1)) ?>
                      </div>
                      <div>
                        <p class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($emp['nombre_completo']) ?></p>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-700"><?= htmlspecialchars($emp['correo']) ?></td>
                  <td class="px-6 py-4">
                    <?php if ($emp['estatus'] == 1): ?>
                      <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold" style="background: rgba(180, 194, 77, 0.1); color: #b4c24d;">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Activo
                      </span>
                    <?php else: ?>
                      <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold" style="background: rgba(225, 88, 113, 0.1); color: #e15871;">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        Inactivo
                      </span>
                    <?php endif; ?>
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($emp['fecha']) ?></td>
                  <td class="px-6 py-4 text-right">
                    <div class="inline-flex gap-2">
                      <a href="index.php?view=editar_empleado&id=<?= $emp['numero'] ?>" class="px-3 py-1.5 bg-blue-50 text-blue-600 rounded-lg text-sm font-semibold hover:bg-blue-100 transition-colors">
                        Editar
                      </a>
                      <a href="index.php?view=eliminar_empleado&id=<?= $emp['numero'] ?>" class="btn-eliminar px-3 py-1.5 bg-red-50 text-red-600 rounded-lg text-sm font-semibold hover:bg-red-100 transition-colors" data-id="<?= htmlspecialchars($emp['numero']) ?>">
                        Eliminar
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="7" class="px-6 py-16">
                  <div class="empty-state">
                    <svg class="w-24 h-24 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">No se encontraron empleados</h3>
                    <p class="text-gray-500">Intenta ajustar los filtros o agregar un nuevo empleado</p>
                  </div>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

  <!-- FAB Button -->
  <button class="fab" onclick="window.location.href='index.php?view=agregar_empleado'">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
    </svg>
  </button>

  <script>
    // Selection state
    let selectedIds = new Set();

    // Dropdown toggles
    const filterBtn = document.getElementById('filterBtn');
    const filterMenu = document.getElementById('filterMenu');
    const filterIcon = document.getElementById('filterIcon');

    const orderBtn = document.getElementById('orderBtn');
    const orderMenu = document.getElementById('orderMenu');
    const orderIcon = document.getElementById('orderIcon');

    filterBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      filterMenu.classList.toggle('active');
      filterIcon.style.transform = filterMenu.classList.contains('active') ? 'rotate(180deg)' : 'rotate(0deg)';
      
      orderMenu.classList.remove('active');
      orderIcon.style.transform = 'rotate(0deg)';
    });

    orderBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      orderMenu.classList.toggle('active');
      orderIcon.style.transform = orderMenu.classList.contains('active') ? 'rotate(180deg)' : 'rotate(0deg)';
      
      filterMenu.classList.remove('active');
      filterIcon.style.transform = 'rotate(0deg)';
    });

    document.addEventListener('click', (e) => {
      if (!filterMenu.contains(e.target) && !filterBtn.contains(e.target)) {
        filterMenu.classList.remove('active');
        filterIcon.style.transform = 'rotate(0deg)';
      }
      if (!orderMenu.contains(e.target) && !orderBtn.contains(e.target)) {
        orderMenu.classList.remove('active');
        orderIcon.style.transform = 'rotate(0deg)';
      }
    });

    // Search input visual feedback
    const searchInput = document.getElementById('busqueda-input');
    if (searchInput.value.trim()) {
      searchInput.classList.add('has-value');
    }

    searchInput.addEventListener('input', function() {
      if (this.value.trim()) {
        this.classList.add('has-value');
      } else {
        this.classList.remove('has-value');
      }
    });

    // Checkbox selection logic
    function updateBulkActions() {
      const bulkActions = document.getElementById('bulkActions');
      const count = selectedIds.size;
      
      if (count > 0) {
        bulkActions.classList.remove('hidden');
        document.getElementById('bulkSelectedCount').textContent = count;
      } else {
        bulkActions.classList.add('hidden');
      }
    }

    function updateRowSelection() {
      document.querySelectorAll('.table-row').forEach(row => {
        const id = parseInt(row.dataset.id);
        if (selectedIds.has(id)) {
          row.classList.add('selected');
        } else {
          row.classList.remove('selected');
        }
      });
    }

    // Select all checkboxes
    document.getElementById('selectAll')?.addEventListener('change', function() {
      const checkboxes = document.querySelectorAll('.row-checkbox');
      if (this.checked) {
        checkboxes.forEach(cb => {
          selectedIds.add(parseInt(cb.dataset.id));
          cb.checked = true;
        });
      } else {
        selectedIds.clear();
        checkboxes.forEach(cb => cb.checked = false);
      }
      document.getElementById('selectAllHeader').checked = this.checked;
      updateBulkActions();
      updateRowSelection();
    });

    document.getElementById('selectAllHeader')?.addEventListener('change', function() {
      const checkboxes = document.querySelectorAll('.row-checkbox');
      if (this.checked) {
        checkboxes.forEach(cb => {
          selectedIds.add(parseInt(cb.dataset.id));
          cb.checked = true;
        });
      } else {
        selectedIds.clear();
        checkboxes.forEach(cb => cb.checked = false);
      }
      document.getElementById('selectAll').checked = this.checked;
      updateBulkActions();
      updateRowSelection();
    });

    // Individual checkbox selection
    document.querySelectorAll('.row-checkbox').forEach(cb => {
      cb.addEventListener('change', function() {
        const id = parseInt(this.dataset.id);
        if (this.checked) {
          selectedIds.add(id);
        } else {
          selectedIds.delete(id);
          document.getElementById('selectAll').checked = false;
          document.getElementById('selectAllHeader').checked = false;
        }
        updateBulkActions();
        updateRowSelection();
      });
    });

    // Clear selection
    function clearSelection() {
      selectedIds.clear();
      document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false);
      document.getElementById('selectAll').checked = false;
      document.getElementById('selectAllHeader').checked = false;
      updateBulkActions();
      updateRowSelection();
    }

    // Bulk delete function
    function bulkDelete() {
      const count = selectedIds.size;
      const ids = Array.from(selectedIds);
      
      Swal.fire({
        title: '¿Eliminar empleados seleccionados?',
        html: `¿Estás seguro de eliminar <strong>${count}</strong> empleado(s)?<br><span class="text-sm text-gray-500">Esta acción no se puede deshacer</span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e15871',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar todos',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
          // Redirect to delete multiple employees
          const idsParam = ids.join(',');
          window.location.href = `index.php?view=eliminar_empleados_multiple&ids=${idsParam}`;
        }
      });
    }

    // Delete confirmation with SweetAlert2 (ORIGINAL LOGIC PRESERVED)
    (function(){
      function attachDeleteHandlers() {
        document.querySelectorAll('.btn-eliminar').forEach(btn => {
          btn.addEventListener('click', function (e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            Swal.fire({
              title: '¿Estás seguro?',
              html: '¿Realmente deseas eliminar este empleado?<br>Esta acción no se puede deshacer.',
              icon: 'warning',
              showCancelButton: true,
              confirmButtonColor: '#e15871',
              cancelButtonColor: '#6b7280',
              confirmButtonText: 'Sí, eliminar',
              cancelButtonText: 'Cancelar',
              reverseButtons: true
            }).then((result) => {
              if (result.isConfirmed) {
                window.location.href = href;
              }
            });
          });
        });
      }

      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', attachDeleteHandlers);
      } else {
        attachDeleteHandlers();
      }
    })();
  </script>
</body>
</html>
