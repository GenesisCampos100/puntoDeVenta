<?php
    require_once __DIR__ . '/../config/db.php';

    if (isset($_GET['action']) && $_GET['action'] === 'getProveedores') {
        header('Content-Type: application/json; charset=UTF-8');
        $id = $_GET['id'] ?? '';
        if (empty($id)) {
            echo json_encode(['success' => false, 'error' => 'ID inválido']);
            exit;
        }

        $sql = "SELECT p.*, GROUP_CONCAT(DISTINCT c.nombre SEPARATOR ', ') AS categoria
                FROM proveedores p
                LEFT JOIN productos pr ON pr.id_proveedor = p.id_proveedor
                LEFT JOIN categorias c ON c.id_categoria = pr.id_categoria
                WHERE p.id_proveedor = ? GROUP BY p.id_proveedor";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $proveedor = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($proveedor) {
            echo json_encode(['success' => true, 'proveedor' => $proveedor]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Proveedor no encontrado']);
        }
        exit;
    }

    $busqueda = $_GET['busqueda'] ?? '';
    $categoria = $_GET['categoria'] ?? '';
    $orden = $_GET['orden'] ?? 'p.representante ASC';
    $allowed_order = ['p.representante ASC', 'p.representante DESC', 'p.correo ASC', 'p.correo DESC'];
    if (!in_array($orden, $allowed_order)) $orden = 'p.representante ASC';
    $vista_actual = $_GET['view'] ?? 'proveedores';

    $sql = "SELECT 
                p.id_proveedor AS numero,
                p.representante AS representante,
                p.empresa AS empresa,
                p.celular AS celular,
                p.correo AS correo,
                p.estatus AS estatus,
                GROUP_CONCAT(DISTINCT c.nombre SEPARATOR ', ') AS categoria
            FROM proveedores p
            LEFT JOIN productos pr ON pr.id_proveedor = p.id_proveedor
            LEFT JOIN categorias c ON c.id_categoria = pr.id_categoria
            WHERE 1 = 1
    ";

    if(!empty($busqueda)) $sql .= " AND (
                                p.representante LIKE :busqueda 
                                OR p.empresa LIKE :busqueda
                                OR p.correo LIKE :busqueda)";
    if(!empty($categoria)) $sql .= " AND pr.id_categoria = :categoria";

    $sql .= " GROUP BY p.id_proveedor ORDER BY $orden";

    $stmt = $pdo->prepare($sql);

    $params = [];

    if (!empty($busqueda)) {
        $params[':busqueda'] = '%' . $busqueda . '%';
    }

    if (!empty($categoria)) {
        $params[':categoria'] = $categoria;
    }

    $stmt->execute($params);
    $proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt_cate = $pdo->query("SELECT id_categoria, nombre FROM categorias");
    $categorias = $stmt_cate->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Proveedores</title>
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

        /* Dropdown Premium */
        .dropdown {
            position: relative;
        }

        .dropdown-menu {
            position: absolute;
            background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2), 0 0 1px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            min-width: 340px;
            z-index: 9999;
            opacity: 0;
            transform: translateY(-10px) scale(0.95);
            pointer-events: none;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid rgba(180, 194, 77, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .dropdown-menu.active {
            opacity: 1;
            transform: translateY(0) scale(1);
            pointer-events: all;
        }
        
        .dropdown-menu select {
            transition: all 0.3s ease;
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .dropdown-menu select:hover {
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(180, 194, 77, 0.2);
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
        
        .modal-backdrop {
            backdrop-filter: blur(8px);
            background: rgba(0, 0, 0, 0.4);
        }
    </style>
</head>
<body class = "p-4 md:p-6">
    <div class="max-w-7xl mx-auto pb-32">

        <!-- Header -->
        <div class="mb-8 animate-slideDown">
            <div class="mb-6">
                <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">Gestión de Proveedores</h1>
                <p class="text-gray-600 text-base">Administra y organiza</p>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl p-5 shadow-lg hover-lift animate-slideUp border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium mb-1">Total Proveedores</p>
                            <p class="text-3xl font-bold text-gray-900"><?= count($proveedores) ?></p>
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
                            <p class="text-3xl font-bold" style="color: #b4c24d;"><?= count(array_filter($proveedores, fn($e) => $e['estatus'] == 1)) ?></p>
                        </div>
                        <div class="w-14 h-14 rounded-xl flex items-center justify-center shadow-lg" style="background: linear-gradient(135deg, #b4c24d 0%, #9fb03d 100%);">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 shadow-lg hover-lift animate-slideUp delay-100 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium mb-1">Inactivos</p>
                            <p class="text-3xl font-bold text-gray-900"><?= count(array_filter($proveedores, fn($e) => $e['estatus'] == 0)) ?></p>
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

        <!-- Search and Filtres -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6 animate-slideUp delay-100 border border-gray-100">
            <form method="GET" action="index.php" id="toolbar-form">
                <input type="hidden" name="view" value="proveedores">

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
                            placeholder="Buscar por nombre o correo..."
                            value="<?= htmlspecialchars($busqueda) ?>"
                            class="search-input w-full pl-12 pr-12 py-3.5 rounded-xl border-2 border-gray-200 focus:border-primary focus:outline-none transition-all duration-200 text-gray-900 placeholder-gray-400 font-medium"
                        />
                        <button type="button" onclick="document.getElementById('busqueda-input').value=''; document.getElementById('toolbar-form').submit();" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-accent transition-colors <?= empty($busqueda) ? 'hidden' : '' ?>">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Filter Dropdown (Categorías) -->
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
                                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Filtrar por categorías</p>
                                <select name="categoria" onchange="document.getElementById('toolbar-form').submit()" class="w-full px-3 py-2 rounded-lg border-2 border-gray-200 focus:border-primary focus:outline-none">
                                    <option value="">-- Todas las categorías --</option>
                                    <?php foreach ($categorias as $ca): ?>
                                        <option value="<?= $ca['id_categoria']?>" <?= ($categoria == $ca['id_categoria']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($ca['nombre']) ?>
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
                                    <option value="p.nombre ASC" <?= ($orden == 'p.nombre ASC') ? 'selected' : '' ?>>Nombre A-Z</option>
                                    <option value="p.nombre DESC" <?= ($orden == 'p.nombre DESC') ? 'selected' : '' ?>>Nombre Z-A</option>
                                    <option value="p.correo ASC" <?= ($orden == 'p.correo ASC') ? 'selected' : '' ?>>Correo A-Z</option>
                                    <option value="p.correo DESC" <?= ($orden == 'p.correo DESC') ? 'selected' : '' ?> >Correo Z-A</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Add Button -->
                    <button type="button" onclick="window.location.href='index.php?view=agregar_proveedor'" class="inline-flex items-center gap-2 px-6 py-3.5 rounded-xl text-white font-semibold shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-200" style="background: linear-gradient(135deg, #2d4353 0%, #1e2d38 100%);">
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
                            <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Negocio</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Representante</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Correo</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Estado</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Catálogo</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-white uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (!empty($proveedores)): ?>
                            <?php foreach ($proveedores as $pr): ?>
                                <tr class="table-row" id="row-<?= $pr['numero'] ?>" data-id="<?= $pr['numero'] ?>">
                                    <td class="px-6 py-4">
                                        <input type="checkbox" class="custom-checkbox row-checkbox" data-id="<?= $pr['numero'] ?>" />
                                    </td>
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-900"><?= tr_content(htmlspecialchars($pr['empresa'])) ?></td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm shadow-md" style="background: linear-gradient(135deg, #b4c24d 0%, #9fb03d 100%);">
                                                <?= strtoupper(substr($pr['representante'], 0, 1))?>
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-gray-900"><?= tr_content(htmlspecialchars($pr['representante'])) ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700"><?= htmlspecialchars($pr['correo']) ?></td>
                                    <td class="px-6 py-4">
                                        <?php if ($pr['estatus'] == 1): ?>
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
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        <button
                                            onclick="window.location.href='index.php?view=catalogo_proveedor&proveedor=<?= urlencode($pr['numero']) ?>'"
                                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-white font-semibold transition-shadow"
                                            style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); box-shadow: 0 6px 18px rgba(31, 64, 24, 0.12);">
                                            <!-- Catalog icon -->
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 7v10a2 2 0 002 2h14a2 2 0 002-2V7M7 7v10M12 7v10M17 7v10"/></svg>
                                            <span>Catálogo</span>
                                        </button>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="inline-flex gap-2">
                                            <button onclick="openDetalle('<?= $pr['numero'] ?>')" class="px-3 py-1.5 bg-blue-50 text-blue-600 rounded-lg text-sm font-semibold hover:bg-blue-100 transition-colors">
                                                Ver
                                            </button>
                                            <a href="index.php?view=editar_proveedor&id=<?= $pr['numero'] ?>" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-200 transition-colors">
                                                Editar
                                            </a>
                                            <button onclick="confirmDelete('<?= $pr['numero'] ?>', '<?= htmlspecialchars(addslashes($pr['representante'])) ?>')" class="px-3 py-1.5 bg-red-50 text-red-600 rounded-lg text-sm font-semibold hover:bg-red-100 transition-colors">
                                                Eliminar
                                            </button>
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
                                        <h3 class="text-xl font-semibold text-gray-700 mb-2">No se encontraron proveedores</h3>
                                        <p class="text-gray-500">Intenta ajustar los filtros o agregar un nuevo proveedor</p>
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
    <button class="fab" onclick="window.location.href='index.php?view=agregar_proveedor'">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
    </button>

    <!-- MODAL VER PROVEEDOR -->
    <div id="modalDetalle" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50">
        <div class="absolute inset-0 modal-backdrop" onclick="closeDetalle()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-3xl w-full mx-4 overflow-hidden animate-scaleIn">
            <div>
                <!-- Header con gradiente -->
                <div class="px-6 py-4 border-b flex items-center justify-between" style="background: linear-gradient(135deg, #2d4353 0%, #1e2d38 100%);">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <svg class="w-6 h-6 text-[#b4c24d]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Detalle del Proveedor
                    </h3>
                    <button class="text-white/70 hover:text-white text-2xl leading-none transition-colors" onclick="closeDetalle()">&times;</button>
                </div>
            </div>

            <div class="p-8 space-y-6">
                <div id="detalle-contenido" class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                    <!-- Representante -->
                    <div class="md:col-span-2 group">
                        <p class="text-xs font-bold text-[#b4c24d] uppercase tracking-wider mb-1 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                            </svg>
                            Representante
                        </p>
                        <p id="d-nombre" class="text-lg font-semibold text-gray-800 border-b border-gray-100 pb-1">-</p>
                    </div>

                    <!-- Correo -->
                    <div class="group">
                        <p class="text-xs font-bold text-[#b4c24d] uppercase tracking-wider mb-1 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            Correo Electrónico
                        </p>
                        <p id="d-correo" class="text-base text-gray-700">-</p>
                    </div>

                    <!-- Celular -->
                    <div class="group">
                        <p class="text-xs font-bold text-[#b4c24d] uppercase tracking-wider mb-1 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                            Celular
                        </p>
                        <p id="d-celular" class="text-base text-gray-700">-</p>
                    </div>

                    <!-- Empresa -->
                    <div class="group">
                        <p class="text-xs font-bold text-[#b4c24d] uppercase tracking-wider mb-1 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            Empresa
                        </p>
                        <p id="d-empresa" class="text-base text-gray-700">-</p>
                    </div>

                    <!-- Estatus -->
                    <div class="group">
                        <p class="text-xs font-bold text-[#b4c24d] uppercase tracking-wider mb-1 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Estatus
                        </p>
                        <p id="d-estatus" class="text-base font-medium">-</p>
                    </div>

                    <!-- Proveedor -->
                    <div class="md:col-span-2 group">
                        <p class="text-xs font-bold text-[#b4c24d] uppercase tracking-wider mb-1 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.59 13.41L13.41 20.59a2 2 0 01-2.83 0L3 13.9V3h10.9l6.69 6.69a2 2 0 010 2.82z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7a1 1 0 100-2 1 1 0 000 2z" />
                            </svg>
                            Proveedor de:
                        </p>
                        <p id="d-proveedor" class="text-base text-gray-700 border-b border-gray-100 pb-1">-</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // -- REFERENCIAS --
        const modal = document.getElementById('modalDetalle');
        const detalle = {
            nombre: document.getElementById('d-nombre'),
            correo: document.getElementById('d-correo'),
            celular: document.getElementById('d-celular'),
            empresa: document.getElementById('d-empresa'),
            estatus: document.getElementById('d-estatus'),
            proveedor: document.getElementById('d-proveedor'),
        }

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

        // BÚSQUEDA EN TIEMPO REAL (exactamente como nueva_venta.php)
        const searchInput = document.getElementById('busqueda-input');
            if (searchInput.value.trim()) {
                searchInput.classList.add('has-value');
            }

        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase().trim();
            const rows = document.querySelectorAll('tbody .table-row');
            
            // Visual feedback
            if (searchTerm) {
                this.classList.add('has-value');
            } else {
                this.classList.remove('has-value');
            }
            
            // Filtrar filas en tiempo real
            let visibleCount = 0;
            rows.forEach(row => {
                const numero = row.querySelector('td:nth-child(2)')?.textContent.toLowerCase() || '';
                const nombre = row.querySelector('td:nth-child(3)')?.textContent.toLowerCase() || '';
                const correo = row.querySelector('td:nth-child(4)')?.textContent.toLowerCase() || '';
                const estado = row.querySelector('td:nth-child(5)')?.textContent.toLowerCase() || '';
                
                if (numero.includes(searchTerm) || nombre.includes(searchTerm) || correo.includes(searchTerm) || estado.includes(searchTerm)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Mostrar/ocultar mensaje de "no se encontraron empleados"
            if (visibleCount === 0 && searchTerm && rows.length > 0) {
                let noResultsRow = document.getElementById('noResultsRow');
                if (!noResultsRow) {
                    noResultsRow = document.createElement('tr');
                    noResultsRow.id = 'noResultsRow';
                    noResultsRow.innerHTML = `
                        <td colspan="7" class="px-6 py-16">
                        <div class="empty-state">
                            <svg class="w-24 h-24 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <h3 class="text-xl font-semibold text-gray-700 mb-2">No se encontraron proveedores</h3>
                            <p class="text-gray-500">Intenta ajustar tu búsqueda</p>
                        </div>
                        </td>
                    `;
                    document.querySelector('tbody').appendChild(noResultsRow);
                }
                noResultsRow.style.display = '';
            } else {
                const noResultsRow = document.getElementById('noResultsRow');
                if (noResultsRow) {
                    noResultsRow.style.display = 'none';
                }
            }
        });

        // -- Función abrir detalle con ajax --
        function openDetalle(id) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            // Reset fields
            detalle.nombre.textContent = 'Cargando...';
            detalle.correo.textContent = '-';
            detalle.celular.textContent = '-';
            detalle.empresa.textContent = '-';
            detalle.estatus.textContent = '-';
            detalle.proveedor.textContent = '-';

            const url = `index.php?view=proveedores&action=getProveedores&id=${encodeURIComponent(id)}`

            fetch(url)
                .then(response => { if (!response.ok) throw new Error('HTTP ' + response.status); return response.json(); })
                .then(json => {
                    if (!json.success) { Swal.fire('Error', json.error || 'No se pudo obtener información', 'error'); closeDetalle(); return; }
                    const e = json.proveedor;

                    // Representante
                    const representante = `${e.nombre || ''} ${e.apellido_paterno || ''} ${e.apellido_materno || ''}`.trim();
                    detalle.nombre.textContent = representante || '-';

                    detalle.correo.textContent = e.correo || '-';
                    detalle.celular.textContent = e.celular || '-';
                    detalle.empresa.textContent = e.empresa || '-';

                    if (e.estatus == 1) {
                        detalle.estatus.innerHTML = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Activo</span>';
                    } else {
                        detalle.estatus.innerHTML = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Inactivo</span>';
                    }

                    detalle.proveedor.textContent = e.categoria || '-';
                }) 
                .catch(err => {console.error(err); Swal.fire('Error','Error al cargar datos: '+err.message,'error'); closeDetalle(); });
        }

        function closeDetalle() { modal.classList.add('hidden'); modal.classList.remove('flex'); }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (!modal.classList.contains('hidden')) closeDetalle();
            }
        });

        function confirmDelete(id, nombre) {
            Swal.fire({
                title: '¿Eliminar proveedor?',
                html: `¿Estás seguro de eliminar a <strong>${nombre}</strong>?<br><span class="text-sm text-gray-500">Esta acción no se puede deshacer</span>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e15871',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    deleteProveedor(id);
                }
            });
        }

        function deleteProveedor(id) {
            fetch(`index.php?view=eliminar_proveedor`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ id: id })
            })
            .then(r => r.json())
            .then(json => {
                if (json.success) {
                    Swal.fire({
                        title: 'Eliminado',
                        text: 'El proveedor ha sido eliminado correctamente',
                        icon: 'success',
                        confirmButtonColor: '#b4c24d',
                        timer: 2000
                    });

                    const row = document.getElementById('row-' + id);
                    if (row) {
                        row.remove();

                        const totalEl = document.getElementById('totalProveedores');
                        if (totalEl) totalEl.textContent = parseInt(totalEl.textContent) - 1;
                    } else {
                        setTimeout(() => window.location.reload(), 1000);
                    }
                } else {
                    Swal.fire('Error', json.error || 'No se pudo eliminar el proveedor', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Error en el servidor', 'error');
            });
        }
    </script>
</body>
</html>