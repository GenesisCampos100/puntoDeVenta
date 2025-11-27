<?php

require_once __DIR__ . '/../config/db.php';

$errors = [];
$success = false;

// Check for success message
if (!empty($_SESSION['cliente_updated'])) {
  $success = true;
  unset($_SESSION['cliente_updated']);
}

// cargar cliente
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  $_SESSION['flash'] = ['type'=>'error','msg'=>'ID de cliente inválido.'];
  header('Location: index.php?view=clientes'); exit;
}
$id = (int)$_GET['id'];

// si venimos de validación fallida, usar session
$cliente = [
  'nombre'=>'','apellido_paterno'=>'','apellido_materno'=>'','celular'=>'',
  'correo'=>'','calle'=>'','num_ext'=>'','num_int'=>'','colonia'=>'','cp'=>'','estado'=>''
];

if (!empty($_SESSION['form_cliente']) && (int)($_SESSION['form_cliente']['_editing_id'] ?? 0) === $id) {
  $tmp = $_SESSION['form_cliente'];
  unset($tmp['_editing_id']);
  $cliente = array_merge($cliente, $tmp);
  unset($_SESSION['form_cliente']);
  $errors = $_SESSION['form_errors'] ?? [];
  unset($_SESSION['form_errors']);
} else {
  $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id_cliente = :id LIMIT 1");
  $stmt->execute([':id'=>$id]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$row) {
    $_SESSION['flash'] = ['type'=>'error','msg'=>'Cliente no encontrado.'];
    header('Location: index.php?view=clientes'); exit;
  }
  $cliente = array_merge($cliente, $row);
}

// Procesamiento POST (update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $data = [
      'nombre' => trim($_POST['nombre'] ?? ''),
      'apellido_paterno' => trim($_POST['apellido_paterno'] ?? ''),
      'apellido_materno' => trim($_POST['apellido_materno'] ?? ''),
      'celular' => trim($_POST['celular'] ?? ''),
      'correo' => trim($_POST['correo'] ?? ''),
      'calle' => trim($_POST['calle'] ?? ''),
      'num_ext' => trim($_POST['num_ext'] ?? ''),
      'num_int' => trim($_POST['num_int'] ?? ''),
      'colonia' => trim($_POST['colonia'] ?? ''),
      'cp' => trim($_POST['cp'] ?? ''),
      'estado' => trim($_POST['estado'] ?? ''),
    ];

    // validación servidor
    $serverErrors = [];
    if ($data['nombre'] === '') $serverErrors[] = 'nombre';
    if ($data['apellido_paterno'] === '') $serverErrors[] = 'apellido_paterno';
    if ($data['celular'] === '') $serverErrors[] = 'celular';
    elseif (strlen($data['celular']) !== 10 || !ctype_digit($data['celular'])) $serverErrors[] = 'celular_invalid';
    if ($data['correo'] !== '' && !filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) $serverErrors[] = 'correo_invalid';

    if (!empty($serverErrors)) {
      $_SESSION['form_cliente'] = $data;
      $_SESSION['form_cliente']['_editing_id'] = $id;
      $msg = [];
      foreach ($serverErrors as $s) {
        if ($s === 'correo_invalid') $msg[] = 'El correo es inválido.';
        elseif ($s === 'celular_invalid') $msg[] = 'El celular debe contener exactamente 10 dígitos.';
        else $msg[] = ucfirst(str_replace('_',' ',$s)).' es obligatorio.';
      }
      $_SESSION['form_errors'] = $msg;
      header('Location: index.php?view=editar_cliente&id=' . $id);
      exit;
    }

    try {
      $sql = "UPDATE clientes SET nombre=:nombre, apellido_paterno=:apellido_paterno, apellido_materno=:apellido_materno,
              celular=:celular, correo=:correo, calle=:calle, num_ext=:num_ext, num_int=:num_int, colonia=:colonia, cp=:cp, estado=:estado
              WHERE id_cliente = :id";
      $stmt = $pdo->prepare($sql);
      $stmt->execute(array_merge($data, [':id'=>$id]));
      $_SESSION['cliente_updated'] = true;
      header('Location: index.php?view=editar_cliente&id=' . $id);
      exit;
    } catch (Exception $e) {
      $_SESSION['form_cliente'] = $data;
      $_SESSION['form_cliente']['_editing_id'] = $id;
      $_SESSION['form_errors'] = ['Error en el servidor: '.$e->getMessage()];
      header('Location: index.php?view=editar_cliente&id=' . $id);
      exit;
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Editar Cliente — Punto de Venta</title>

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
            --primary-light: #c5d65e;
            --secondary: #2d4353;
            --secondary-dark: #1e2d38;
            --accent: #e15871;
            --accent-light: #ff6b88;
            --surface: #ffffff;
            --background: #e8e8e8;
            --border: #d0d0d0;
            --text-primary: #1e2d38;
            --text-secondary: #475569;
            --text-tertiary: #64748b;
            --success: #10b981;
            --error: #ef4444;
            --font: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body { 
            font-family: var(--font); 
            background: var(--background);
            color: var(--text-primary);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            min-height: 100vh;
        }

        /* Container System */
        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 2.5rem 1.5rem;
        }

        /* Card Component - Premium Design */
        .card {
            background: var(--surface);
            border-radius: 24px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.03), 0 10px 20px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .card:hover {
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.05), 0 16px 32px rgba(0, 0, 0, 0.08);
        }

        .card-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid rgba(208, 208, 208, 0.4);
            background: linear-gradient(135deg, #2d4353 0%, #3a5468 100%);
        }

        .card-body {
            padding: 2rem;
        }

        .card-footer {
            padding: 1.25rem 2rem;
            border-top: 1px solid rgba(208, 208, 208, 0.4);
            background: #f5f5f5;
        }

        /* Typography */
        .heading-1 {
            font-size: 2.25rem;
            font-weight: 600;
            color: var(--text-primary);
            letter-spacing: -0.02em;
        }

        .heading-2 {
            font-size: 1.375rem;
            font-weight: 600;
            color: var(--text-primary);
            letter-spacing: -0.01em;
        }

        .text-body {
            font-size: 1rem;
            color: var(--text-secondary);
            font-weight: 400;
        }

        .text-caption {
            font-size: 0.75rem;
            color: var(--text-tertiary);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 500;
        }

        /* Page Header */
        .page-header-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: white;
            letter-spacing: -0.01em;
            margin: 0;
        }

        .page-header-subtitle {
            font-size: 0.9375rem;
            color: rgba(255, 255, 255, 0.85);
            margin-top: 0.25rem;
            font-weight: 400;
        }

        /* Form Input Component - Modern & Clean */
        .form-group {
            margin-bottom: 1.25rem;
            position: relative;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            letter-spacing: -0.005em;
        }

        .form-label .required {
            color: var(--accent);
            margin-left: 0.25rem;
            font-weight: 600;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 0.9375rem;
            font-family: var(--font);
            color: var(--text-primary);
            background: #f8f8f8;
            border: 1.5px solid #d8d8d8;
            border-radius: 10px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            outline: none;
            font-weight: 500;
        }

        .form-input::placeholder {
            color: var(--text-tertiary);
            font-weight: 400;
        }

        .form-input:hover {
            border-color: #c0c0c0;
            background: #ffffff;
        }

        .form-input:focus {
            border-color: var(--primary);
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(180, 194, 77, 0.08);
        }

        .form-input.error {
            border-color: var(--error);
            background: #fef2f2;
            animation: shake 0.4s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
        }

        .form-input.error:focus {
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.08);
        }

        .form-helper {
            font-size: 0.875rem;
            color: var(--text-tertiary);
            margin-top: 0.5rem;
            font-weight: 400;
        }

        .form-error {
            font-size: 0.875rem;
            color: var(--error);
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.375rem;
            font-weight: 500;
        }

        /* Button Component - Premium */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.625rem;
            padding: 0.75rem 1.5rem;
            font-size: 0.9375rem;
            font-weight: 600;
            font-family: var(--font);
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            outline: none;
            letter-spacing: -0.01em;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(180, 194, 77, 0.25);
        }

        .btn-primary:hover:not(:disabled) {
            box-shadow: 0 4px 12px rgba(180, 194, 77, 0.35);
            transform: translateY(-2px);
        }

        .btn-primary:active:not(:disabled) {
            transform: translateY(0);
        }

        .btn-secondary {
            background: white;
            color: var(--text-primary);
            border: 1.5px solid #e8e8e8;
        }

        .btn-secondary:hover:not(:disabled) {
            background: #fafafa;
            border-color: #d0d0d0;
        }

        .btn-text {
            background: transparent;
            color: var(--text-secondary);
        }

        .btn-text:hover:not(:disabled) {
            background: #f5f5f5;
            color: var(--text-primary);
        }

        .btn-lg {
            padding: 0.875rem 2rem;
            font-size: 1rem;
            font-weight: 600;
        }

        /* Section Component */
        .section {
            margin-bottom: 2.25rem;
        }

        .section:last-child {
            margin-bottom: 0;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.25rem;
            padding-bottom: 0;
        }

        .section-icon {
            width: 2rem;
            height: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.875rem;
            box-shadow: 0 2px 6px rgba(180, 194, 77, 0.25);
        }

        .section-title {
            font-size: 1.0625rem;
            font-weight: 600;
            color: var(--text-primary);
            letter-spacing: -0.005em;
            flex: 1;
        }

        /* Grid System */
        .grid {
            display: grid;
            gap: 1rem;
        }

        .grid-cols-2 {
            grid-template-columns: repeat(2, 1fr);
        }

        .grid-cols-3 {
            grid-template-columns: repeat(3, 1fr);
        }

        @media (max-width: 768px) {
            .grid-cols-2, .grid-cols-3 {
                grid-template-columns: 1fr;
            }
        }

        @media (min-width: 769px) and (max-width: 1024px) {
            .grid-cols-3 {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .col-span-2 {
            grid-column: span 2;
        }

        @media (max-width: 768px) {
            .col-span-2 {
                grid-column: span 1;
            }
        }

        /* Alert Component */
        .alert {
            padding: 1.125rem 1.375rem;
            border-radius: 12px;
            margin-bottom: 1.75rem;
            display: flex;
            gap: 0.875rem;
            border: 1.5px solid;
        }

        .alert-error {
            background: #fef2f2;
            border-color: rgba(239, 68, 68, 0.2);
            color: #991b1b;
        }

        .alert-icon {
            flex-shrink: 0;
            width: 1.375rem;
            height: 1.375rem;
        }

        .alert-content {
            flex: 1;
        }

        .alert-title {
            font-weight: 600;
            margin-bottom: 0.375rem;
            font-size: 1rem;
        }

        .alert-list {
            list-style: none;
            margin-top: 0.625rem;
        }

        .alert-list li {
            padding-left: 1.125rem;
            position: relative;
            margin-bottom: 0.375rem;
            font-size: 0.9375rem;
        }

        .alert-list li::before {
            content: '•';
            position: absolute;
            left: 0;
            font-weight: 600;
        }

        /* Badge */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.375rem 0.875rem;
            font-size: 0.8125rem;
            font-weight: 500;
            border-radius: 8px;
            background: rgba(238, 238, 238, 0.6);
            color: var(--text-secondary);
            letter-spacing: 0.02em;
        }

        .badge-primary {
            background: rgba(180, 194, 77, 0.12);
            color: var(--primary-dark);
        }

        /* Progress Steps */
        .progress-steps {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 2rem;
        }

        .progress-step {
            flex: 1;
            height: 0.375rem;
            background: rgba(238, 238, 238, 0.8);
            border-radius: 999px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .progress-step.active {
            background: linear-gradient(90deg, var(--primary) 0%, var(--primary-light) 100%);
            box-shadow: 0 2px 8px rgba(180, 194, 77, 0.3);
        }

        .progress-step.active::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            to {
                left: 100%;
            }
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(24px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes shake {
            0%, 100% {
                transform: translateX(0);
            }
            10%, 30%, 50%, 70%, 90% {
                transform: translateX(-8px);
            }
            20%, 40%, 60%, 80% {
                transform: translateX(8px);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .animate-in {
            animation: fadeInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .animate-in-delay-1 {
            animation: fadeInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1) 0.15s both;
        }

        .animate-in-delay-2 {
            animation: fadeInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1) 0.3s both;
        }

        .animate-in-delay-3 {
            animation: fadeInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1) 0.45s both;
        }

        /* Loading State */
        .btn-loading {
            position: relative;
            color: transparent !important;
        }

        .btn-loading::after {
            content: '';
            position: absolute;
            width: 1.125rem;
            height: 1.125rem;
            top: 50%;
            left: 50%;
            margin-left: -0.5625rem;
            margin-top: -0.5625rem;
            border: 2px solid white;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Utility Classes */
        .flex { display: flex; }
        .items-center { align-items: center; }
        .justify-between { justify-content: space-between; }
        .justify-end { justify-content: flex-end; }
        .gap-2 { gap: 0.5rem; }
        .gap-3 { gap: 0.75rem; }
        .gap-4 { gap: 1rem; }
        .mb-6 { margin-bottom: 1.5rem; }
        .mt-2 { margin-top: 0.5rem; }
        .mt-8 { margin-top: 2rem; }
        .pb-8 { padding-bottom: 2rem; }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 1.5rem 1rem;
            }
            .card-body {
                padding: 1.75rem;
            }
            .card-header {
                padding: 1.5rem 1.75rem;
            }
            .card-footer {
                padding: 1.5rem 1.75rem;
            }
            .heading-1 {
                font-size: 1.5rem;
            }
            .section {
                margin-bottom: 2rem;
            }
            .btn-lg {
                padding: 1rem 1.75rem;
            }
        }

        @media (min-width: 769px) and (max-width: 1024px) {
            .container {
                max-width: 95%;
            }
        }

        /* Tema oscuro */
        /* Títulos del formulario */
body.dark-mode .form-header h1,
body.dark-mode .form-header p,
body.dark-mode .section-title {
  color: #ffffff !important;
}
/* Formularios en modo oscuro */
body.dark-mode .form-card,
body.dark-mode .card-body,
body.dark-mode .section {
  background-color: #1f1f1f !important;
  color: #f5f5f5 !important;
}
/* Inputs */
body.dark-mode .form-input,
body.dark-mode .form-select {
  background-color: #2a2a2a !important;
  color: #ffffff !important;
  border-color: #444 !important;
}

/* Placeholders */
body.dark-mode .form-input::placeholder,
body.dark-mode .form-select::placeholder {
  color: #cccccc !important;
}

/* Labels */
body.dark-mode .form-label,
body.dark-mode label {
  color: #eaeaea !important;
}
body.dark-mode .switch-label {
  color: #eaeaea !important;
}

body.dark-mode .slider {
  background-color: #444 !important;
}
body.dark-mode .card-footer {
  background-color: #1a1a1a !important;
  border-top: 1px solid #333 !important;
}
/* Botón Guardar  → verde en modo oscuro */
body.dark-mode .btn-primary {
  background-color: #b4c24d !important; /* verde */
  color: white !important;
  border: none !important;
}

body.dark-mode .btn-primary:hover {
  background-color: #b4c24d !important; /* verde más claro */
}

    </style>
</head>
<body>
  <div class="container">

    <!-- Progress Steps -->
    <div class="progress-steps animate-in-delay-1">
      <div class="progress-step active"></div>
      <div class="progress-step"></div>
    </div>

    <!-- Error Alert -->
    <?php if(!empty($errors)): ?>
      <div class="alert alert-error animate-in-delay-1">
        <svg class="alert-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <div class="alert-content">
          <div class="alert-title">Se encontraron errores en el formulario</div>
          <ul class="alert-list">
            <?php foreach($errors as $err): ?>
              <li><?=htmlspecialchars($err)?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    <?php endif; ?>

    <!-- Main Form Card -->
    <form id="formCliente" method="POST" action="index.php?view=editar_cliente&id=<?=$id?>" class="animate-in-delay-2">
      <input type="hidden" name="action" value="update">
      
      <div class="card">
        <!-- Card Header -->
        <div class="card-header">
          <h1 class="page-header-title">Editar Cliente</h1>
          <p class="page-header-subtitle">Actualiza la información del cliente. Los campos marcados con <span style="color: var(--accent); font-weight: 600;">*</span> son obligatorios.</p>
        </div>

        <div class="card-body">
          
          <!-- Personal Information Section -->
          <div class="section animate-in-delay-3">
            <div class="section-header">
              <div class="section-icon">1</div>
              <h2 class="section-title">Información Personal</h2>
            </div>

            <div class="grid grid-cols-3">
              <div class="form-group">
                <label class="form-label">
                  Nombre
                  <span class="required">*</span>
                </label>
                <input 
                  type="text" 
                  name="nombre" 
                  id="nombre"
                  value="<?=htmlspecialchars($cliente['nombre'] ?? '')?>"
                  class="form-input <?= in_array('nombre', array_keys($errors)) ? 'error' : '' ?>"
                  placeholder="Ej: Juan"
                />
              </div>

              <div class="form-group">
                <label class="form-label">
                  Apellido Paterno
                  <span class="required">*</span>
                </label>
                <input 
                  type="text" 
                  name="apellido_paterno" 
                  id="apellido_paterno"
                  value="<?=htmlspecialchars($cliente['apellido_paterno'] ?? '')?>"
                  class="form-input <?= in_array('apellido_paterno', array_keys($errors)) ? 'error' : '' ?>"
                  placeholder="Ej: Pérez"
                />
              </div>

              <div class="form-group">
                <label class="form-label">Apellido Materno</label>
                <input 
                  type="text" 
                  name="apellido_materno" 
                  id="apellido_materno"
                  value="<?=htmlspecialchars($cliente['apellido_materno'] ?? '')?>"
                  class="form-input"
                  placeholder="Ej: García"
                />
              </div>
            </div>

            <div class="grid grid-cols-2">
              <div class="form-group">
                <label class="form-label">
                  Teléfono Celular
                  <span class="required">*</span>
                </label>
                <input 
                  type="tel" 
                  name="celular" 
                  id="celular"
                  value="<?=htmlspecialchars($cliente['celular'] ?? '')?>"
                  class="form-input <?= in_array('celular', array_keys($errors)) ? 'error' : '' ?>"
                  placeholder="Ej: 5512345678"
                />
                <div class="form-helper">10 dígitos sin espacios</div>
              </div>

              <div class="form-group">
                <label class="form-label">Correo Electrónico</label>
                <input 
                  type="email" 
                  name="correo" 
                  id="correo"
                  value="<?=htmlspecialchars($cliente['correo'] ?? '')?>"
                  class="form-input <?= in_array('correo', array_keys($errors)) ? 'error' : '' ?>"
                  placeholder="ejemplo@correo.com"
                />
              </div>
            </div>
          </div>

          <!-- Address Section -->
          <div class="section animate-in-delay-3">
            <div class="section-header">
              <div class="section-icon">2</div>
              <h2 class="section-title">Dirección</h2>
              <span class="badge">Opcional</span>
            </div>

            <div class="grid grid-cols-3">
              <div class="form-group col-span-2">
                <label class="form-label">Calle</label>
                <input 
                  type="text" 
                  name="calle" 
                  id="calle"
                  value="<?=htmlspecialchars($cliente['calle'] ?? '')?>"
                  class="form-input"
                  placeholder="Nombre de la calle"
                />
              </div>

              <div class="form-group">
                <label class="form-label">Número Exterior</label>
                <input 
                  type="text" 
                  name="num_ext" 
                  id="num_ext"
                  value="<?=htmlspecialchars($cliente['num_ext'] ?? '')?>"
                  class="form-input"
                  placeholder="Núm. ext"
                />
              </div>
            </div>

            <div class="grid grid-cols-3">
              <div class="form-group">
                <label class="form-label">Número Interior</label>
                <input 
                  type="text" 
                  name="num_int" 
                  id="num_int"
                  value="<?=htmlspecialchars($cliente['num_int'] ?? '')?>"
                  class="form-input"
                  placeholder="Núm. int"
                />
              </div>

              <div class="form-group">
                <label class="form-label">Colonia</label>
                <input 
                  type="text" 
                  name="colonia" 
                  id="colonia"
                  value="<?=htmlspecialchars($cliente['colonia'] ?? '')?>"
                  class="form-input"
                  placeholder="Nombre de la colonia"
                />
              </div>

              <div class="form-group">
                <label class="form-label">Código Postal</label>
                <input 
                  type="text" 
                  name="cp" 
                  id="cp"
                  value="<?=htmlspecialchars($cliente['cp'] ?? '')?>"
                  class="form-input"
                  placeholder="C.P."
                  maxlength="5"
                />
              </div>
            </div>

            <div class="grid grid-cols-3">
              <div class="form-group">
                <label class="form-label">Estado</label>
                <input 
                  type="text" 
                  name="estado" 
                  id="estado"
                  value="<?=htmlspecialchars($cliente['estado'] ?? '')?>"
                  class="form-input"
                  placeholder="Estado"
                />
              </div>
            </div>
          </div>

        </div>

        <!-- Card Footer with Actions -->
        <div class="card-footer">
          <div class="flex justify-end gap-3">
            <button type="button" id="cancelBtn" class="btn btn-secondary">
              Cancelar
            </button>
            <button type="submit" id="saveBtn" class="btn btn-primary btn-lg">
              <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              Actualizar Cliente
            </button>
          </div>
        </div>
      </div>
    </form>

    <!-- Bottom Spacer -->
    <div style="height: 3rem;"></div>

  </div>

<script>
  const requiredFields = ['nombre','apellido_paterno','celular'];
  const form = document.getElementById('formCliente');
  const saveBtn = document.getElementById('saveBtn');
  const cancelBtn = document.getElementById('cancelBtn');
  const progressSteps = document.querySelectorAll('.progress-step');

  <?php if($success): ?>
    Swal.fire({
      icon: 'success',
      title: '¡Cliente actualizado!',
      text: 'Los cambios se han guardado correctamente.',
      confirmButtonColor: '#b4c24d',
      confirmButtonText: 'Aceptar',

      /* 🎯 MODO OSCURO DIRECTO */
      background: '#1e1e1e',
      color: '#ffffff',
      iconColor: '#4caf50',

      /* Botón */
      customClass: {
        confirmButton: 'swal-dark-confirm'
      }
    }).then(() => {
      window.location.href = 'index.php?view=clientes';
    });
<?php endif; ?>


  // Server errors
  <?php if(!empty($errors)): ?>
    const serverErrors = <?= json_encode($errors) ?>;
    requiredFields.forEach(name => {
      const el = document.getElementById(name);
      if (el && el.value.trim() === '') el.classList.add('error');
    });
  <?php endif; ?>

  // Progress indicator
  function updateProgress() {
    const totalFields = requiredFields.length;
    let filledFields = 0;
    
    requiredFields.forEach(name => {
      const el = document.getElementById(name);
      if (el && el.value.trim() !== '') filledFields++;
    });

    const progress = filledFields / totalFields;
    progressSteps.forEach((step, index) => {
      if (index === 0) {
        step.classList.add('active');
      } else if (progress >= 0.5) {
        step.classList.add('active');
      } else {
        step.classList.remove('active');
      }
    });
  }

  // Update progress on input
  requiredFields.forEach(name => {
    const el = document.getElementById(name);
    if (el) {
      el.addEventListener('input', updateProgress);
    }
  });

  // Initial progress
  updateProgress();

  // Client-side validation
  form.addEventListener('submit', function(e){
    // Clear previous errors and animations
    document.querySelectorAll('.form-input').forEach(input => {
      input.classList.remove('error');
      // Remove animation class to allow re-triggering
      input.style.animation = 'none';
      setTimeout(() => { input.style.animation = ''; }, 10);
    });

    let isValid = true;
    const errors = [];

    // Required fields
    requiredFields.forEach(name => {
      const el = document.getElementById(name);
      if (el && el.value.trim() === '') {
        el.classList.add('error');
        isValid = false;
        errors.push(`El campo ${el.previousElementSibling.textContent.replace('*', '').trim()} es obligatorio`);
      }
    });

    // Phone validation
    const phoneEl = document.getElementById('celular');
    if (phoneEl && phoneEl.value.trim() !== '') {
      if (phoneEl.value.trim().length !== 10) {
        phoneEl.classList.add('error');
        isValid = false;
        errors.push('El teléfono debe contener exactamente 10 dígitos');
      }
    }

    // Email validation
    const emailEl = document.getElementById('correo');
    if (emailEl && emailEl.value.trim() !== '') {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(emailEl.value.trim())) {
        emailEl.classList.add('error');
        isValid = false;
        errors.push('El correo electrónico no es válido');
      }
    }

    if (!isValid) {
    e.preventDefault();

    const dark = document.body.classList.contains("dark-mode");

    Swal.fire({
        icon: "error",
        title: "Formulario incompleto",
        html: errors.join("<br>"),

        // 🎨 FONDO Y TEXTO
        background: dark ? "#121212" : "#ffffff",
        color: dark ? "#f1f5f9" : "#1e293b",

        // 🎨 BOTÓN
        confirmButtonText: "Entendido",
        confirmButtonColor: dark ? "#84cc16" : "#b4c24d"
    });

    return false;
}
    // Form is valid, allow submission
  });

  // Cancel button
cancelBtn.addEventListener('click', function(){

  const dark = document.body.classList.contains('dark-mode');

  Swal.fire({
    title: '¿Cancelar edición?',
    text: "Los cambios no guardados se perderán",
    icon: 'warning',
    showCancelButton: true,

    // 🎨 COLORES DIFERENTES SEGÚN EL MODO
    background: dark ? '#121212' : '#ffffff',      // fondo
    color: dark ? '#f1f5f9' : '#1e293b',            // texto

    confirmButtonColor: dark ? '#e11d48' : '#e15871', // rojo más fuerte en oscuro
    cancelButtonColor: dark ? '#475569' : '#64748b',

    confirmButtonText: 'Sí, cancelar',
    cancelButtonText: 'Continuar editando'
  }).then((result) => {
    if (result.isConfirmed) {
      window.location.href = 'index.php?view=clientes';
    }
  });

});


  // Auto-format phone number
  const phoneInput = document.getElementById('celular');
  phoneInput?.addEventListener('input', function(e) {
    this.value = this.value.replace(/\D/g, '').substring(0, 10);
  });

  // Auto-format postal code
  const cpInput = document.getElementById('cp');
  cpInput?.addEventListener('input', function(e) {
    this.value = this.value.replace(/\D/g, '').substring(0, 5);
  });
</script>
</body>
</html>