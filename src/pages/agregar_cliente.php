<?php

require_once __DIR__ . '/../config/db.php'; // <<--- ajusta ruta si es necesario

$cliente = [
  'nombre'=>'','apellido_paterno'=>'','apellido_materno'=>'','celular'=>'',
  'correo'=>'','calle'=>'','num_ext'=>'','num_int'=>'','colonia'=>'','cp'=>'','estado'=>''
];
$errors = [];

// Si venimos de validación fallida (server) repoblar
if (!empty($_SESSION['form_cliente'])) {
  $cliente = array_merge($cliente, $_SESSION['form_cliente']);
  unset($_SESSION['form_cliente']);
  $errors = $_SESSION['form_errors'] ?? [];
  unset($_SESSION['form_errors']);
}

// Procesamiento del POST (crear)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
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

    // Validación servidor
    $serverErrors = [];
    if ($data['nombre'] === '') $serverErrors[] = 'nombre';
    if ($data['apellido_paterno'] === '') $serverErrors[] = 'apellido_paterno';
    if ($data['celular'] === '') $serverErrors[] = 'celular';
    if ($data['correo'] !== '' && !filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) $serverErrors[] = 'correo_invalid';

    if (!empty($serverErrors)) {
      // Guardar datos y campos con error
      $_SESSION['form_cliente'] = $data;
      $msg = [];
      foreach ($serverErrors as $s) {
        if ($s === 'correo_invalid') $msg[] = 'El correo es inválido.';
        else $msg[] = ucfirst(str_replace('_',' ',$s)).' es obligatorio.';
      }
      $_SESSION['form_errors'] = $msg;
      header('Location: index.php?view=agregar_cliente');
      exit;
    }

    // Insertar
    try {
      $sql = "INSERT INTO clientes (nombre, apellido_paterno, apellido_materno, celular, correo, calle, num_ext, num_int, colonia, cp, estado)
              VALUES (:nombre,:apellido_paterno,:apellido_materno,:celular,:correo,:calle,:num_ext,:num_int,:colonia,:cp,:estado)";
      $stmt = $pdo->prepare($sql);
      $stmt->execute([
        ':nombre'=>$data['nombre'],
        ':apellido_paterno'=>$data['apellido_paterno'],
        ':apellido_materno'=>$data['apellido_materno'],
        ':celular'=>$data['celular'],
        ':correo'=>$data['correo'],
        ':calle'=>$data['calle'],
        ':num_ext'=>$data['num_ext'],
        ':num_int'=>$data['num_int'],
        ':colonia'=>$data['colonia'],
        ':cp'=>$data['cp'],
        ':estado'=>$data['estado']
      ]);
      $_SESSION['flash'] = ['type'=>'success','msg'=>'Cliente creado correctamente.'];
      header('Location: index.php?view=clientes');
      exit;
    } catch (Exception $e) {
      $_SESSION['form_cliente'] = $data;
      $_SESSION['form_errors'] = ['Error en el servidor: '.$e->getMessage()];
      header('Location: index.php?view=agregar_cliente');
      exit;
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Agregar cliente — Punto de Venta</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
        /* Variables de tu paleta */
        :root{
            --verde: #b4c24d;
            --azul:  #2d4353;
            --rosa:  #e15871;
            --gris:  #eeeeee;
            --font: 'Poppins', sans-serif;
        }
        
        body { 
            font-family: var(--font); 
            /* Fondo muy claro/blanco para que la card principal resalte */
            background: #f9fafb; 
            color: #0f172a; 
        }

        /* Estilo general de Inputs (Clave para que no se pierdan en el fondo blanco) */
        .input-style {
            background-color: #ffffff;
            border: 1px solid #d1d5db; /* Borde gris más visible */
            padding: 0.65rem 1rem;
            border-radius: 0.5rem; 
            transition: all 0.2s;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.03); 
        }
        .input-style:focus {
            outline: none;
            border-color: var(--verde); 
            box-shadow: 0 0 0 3px rgba(180, 194, 77, 0.3); /* Focus ring profesional */
        }
        /* La clase 'input' original se redirige al nuevo estilo 'input-style' */
        .input {
             border: none; /* Asegura que el borde de 'input-style' sea el que domina */
        }
        
        .input-error { 
            border-color: var(--rosa) !important; 
            box-shadow: 0 0 0 3px rgba(225,89,113,0.3) !important; 
        }

        /* Estilos de botones (Añadiendo hover y sombra) */
        .btn-primary { 
            background: var(--verde); 
            color: white; 
            font-weight: 500;
            transition: background-color 0.2s, transform 0.2s, box-shadow 0.2s; 
            box-shadow: 0 4px 6px -1px rgba(180, 194, 77, 0.3);
        }
        .btn-primary:hover { 
            background: #a5b342; 
            transform: translateY(-1px); 
            box-shadow: 0 6px 12px -2px rgba(180, 194, 77, 0.4); 
        }
        /* Estilo para el botón Volver/Cancelar */
        .btn-secondary {
            border: 1px solid #d1d5db;
            background: white;
            color: #4b5563; 
            font-weight: 500;
            transition: background-color 0.2s, transform 0.2s, box-shadow 0.2s;
        }
        .btn-secondary:hover {
            background: #f3f4f6;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.08);
        }

        /* Contenedor principal (El "Card" tipo React) */
        .app-card {
            background-color: #ffffff;
            border-radius: 1.5rem; 
            box-shadow: 0 10px 20px -5px rgba(45,67,83,0.1), 0 4px 10px -4px rgba(45,67,83,0.08); 
        }
        
        /* Estilo para la cabecera de sección (Mejora visual) */
        .section-header {
            color: var(--azul);
            border-left: 4px solid var(--verde); 
            padding-left: 1rem;
        }
        
        /* Animaciones originales */
        @keyframes floatUp { from { opacity:0; transform: translateY(18px)} to { opacity:1; transform:translateY(0)} }
        .wow-in { animation: floatUp .5s cubic-bezier(.2,.9,.3,1) both; }
        .pulse-cta { animation: pulse 2.4s infinite; }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(180,194,77,0.18); } 70% { box-shadow: 0 0 0 14px rgba(180,194,77,0); } 100% { box-shadow: 0 0 0 0 rgba(180,194,77,0); } }
    </style>
</head>
<body class="p-6 sm:p-10">
  <div class="max-w-4xl mx-auto">
    
        <div class="flex items-center justify-between mb-8 wow-in">
      <div>
        <h2 class="text-3xl font-bold" style="color:var(--azul)">Agregar cliente</h2>
        <p class="text-gray-500 mt-1">Llena los datos del cliente. Los campos con <span class="text-[#e15871] font-semibold">*</span> son obligatorios.</p>
      </div>
      <div>
        <button id="backBtn" class="px-4 py-2 rounded-xl btn-secondary">Volver</button>
      </div>
    </div>

        <div class="app-card p-6 sm:p-8 wow-in">
        
      <?php if(!empty($errors)): ?>
        <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 font-medium">
            <p class="font-bold mb-1">⚠️ Errores de validación:</p>
          <?php foreach($errors as $err): ?><div><?=htmlspecialchars($err)?></div><?php endforeach; ?>
        </div>
      <?php endif; ?>

              <form id="formCliente" method="POST" action="index.php?view=agregar_cliente" class="space-y-8">
        <input type="hidden" name="action" value="create">

        <div class="space-y-6">
            <h3 class="text-xl font-semibold section-header mb-6">Datos Personales 👤</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                <div>
                  <label class="block text-sm font-medium text-gray-700">Nombre <span class="text-[var(--rosa)]">*</span></label>
                  <input name="nombre" value="<?=htmlspecialchars($cliente['nombre'] ?? '')?>" id="nombre" 
                    class="input-style mt-1 block w-full <?= in_array('nombre', array_keys($errors)) ? 'input-error' : '' ?>" />
                </div>

                                <div>
                  <label class="block text-sm font-medium text-gray-700">Apellido paterno <span class="text-[var(--rosa)]">*</span></label>
                  <input name="apellido_paterno" value="<?=htmlspecialchars($cliente['apellido_paterno'] ?? '')?>" id="apellido_paterno" 
                    class="input-style mt-1 block w-full <?= in_array('apellido_paterno', array_keys($errors)) ? 'input-error' : '' ?>" />
                </div>

                                <div>
                  <label class="block text-sm font-medium text-gray-700">Apellido materno</label>
                  <input name="apellido_materno" value="<?=htmlspecialchars($cliente['apellido_materno'] ?? '')?>" id="apellido_materno" 
                    class="input-style mt-1 block w-full <?= in_array('apellido_materno', array_keys($errors)) ? 'input-error' : '' ?>" />
                </div>

                                <div>
                  <label class="block text-sm font-medium text-gray-700">Celular <span class="text-[var(--rosa)]">*</span></label>
                  <input name="celular" value="<?=htmlspecialchars($cliente['celular'] ?? '')?>" id="celular" 
                    class="input-style mt-1 block w-full <?= in_array('celular', array_keys($errors)) ? 'input-error' : '' ?>" />
                </div>

                                <div class="md:col-span-2">
                  <label class="block text-sm font-medium text-gray-700">Correo</label>
                  <input name="correo" type="email" value="<?=htmlspecialchars($cliente['correo'] ?? '')?>" id="correo" 
                    class="input-style mt-1 block w-full <?= in_array('correo', array_keys($errors)) ? 'input-error' : '' ?>" />
                </div>
            </div>
        </div>

        <hr class="border-gray-100">

        <div class="space-y-6">
            <h3 class="text-xl font-semibold section-header mb-6">Datos de Dirección 📍</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Calle</label>
                    <input name="calle" value="<?=htmlspecialchars($cliente['calle'] ?? '')?>" id="calle" 
                        class="input-style mt-1 block w-full <?= in_array('calle', array_keys($errors)) ? 'input-error' : '' ?>" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Número exterior</label>
                    <input name="num_ext" value="<?=htmlspecialchars($cliente['num_ext'] ?? '')?>" id="num_ext" 
                        class="input-style mt-1 block w-full <?= in_array('num_ext', array_keys($errors)) ? 'input-error' : '' ?>" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Número interior</label>
                    <input name="num_int" value="<?=htmlspecialchars($cliente['num_int'] ?? '')?>" id="num_int" 
                        class="input-style mt-1 block w-full <?= in_array('num_int', array_keys($errors)) ? 'input-error' : '' ?>" />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Colonia</label>
                    <input name="colonia" value="<?=htmlspecialchars($cliente['colonia'] ?? '')?>" id="colonia" 
                        class="input-style mt-1 block w-full <?= in_array('colonia', array_keys($errors)) ? 'input-error' : '' ?>" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Código postal</label>
                    <input name="cp" value="<?=htmlspecialchars($cliente['cp'] ?? '')?>" id="cp" 
                        class="input-style mt-1 block w-full <?= in_array('cp', array_keys($errors)) ? 'input-error' : '' ?>" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Estado</label>
                    <input name="estado" value="<?=htmlspecialchars($cliente['estado'] ?? '')?>" id="estado" 
                        class="input-style mt-1 block w-full <?= in_array('estado', array_keys($errors)) ? 'input-error' : '' ?>" />
                </div>
            </div>
        </div>

                <div class="pt-6 border-t border-gray-100 flex justify-end gap-4 mt-8">
                      <button type="button" id="cancelBtn" class="px-6 py-3 rounded-xl btn-secondary">Cancelar</button>
                      <button type="submit" id="saveBtn" class="px-6 py-3 rounded-xl btn-primary pulse-cta">Guardar</button>
        </div>
      </form>
    </div>
  </div>

<script>
  // Campos obligatorios
  const requiredFields = ['nombre','apellido_paterno','celular'];

  const form = document.getElementById('formCliente');
  const saveBtn = document.getElementById('saveBtn');
  const cancelBtn = document.getElementById('cancelBtn');
  const backBtn = document.getElementById('backBtn');

  // Marca inputs en rojo si vienen errores del servidor
  <?php if(!empty($errors)): ?>
    const serverErrors = <?= json_encode($errors) ?>;
    // serverErrors is array of messages; we cannot reliably map to inputs, but we highlight any empty required ones
    requiredFields.forEach(name => {
      const el = document.getElementById(name);
      if (el && el.value.trim() === '') el.classList.add('input-error');
    });
    // show server error messages
    Swal.fire({ title: 'Errores', html: serverErrors.join('<br>'), icon: 'error', confirmButtonColor: 'var(--rosa)' });
  <?php endif; ?>

  // Client-side validation before submit (custom red border)
  form.addEventListener('submit', function(e){
    // remove previous
    requiredFields.forEach(name => {
      const el = document.getElementById(name);
      if (el) el.classList.remove('input-error');
    });

    let ok = true;
    requiredFields.forEach(name => {
      const el = document.getElementById(name);
      if (el && el.value.trim() === '') {
        el.classList.add('input-error');
        ok = false;
      }
    });

    // email format check (optional)
    const emailEl = document.getElementById('correo');
    if (emailEl && emailEl.value.trim() !== '') {
      const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!re.test(emailEl.value.trim())) {
        emailEl.classList.add('input-error');
        ok = false;
      }
    }

    if (!ok) {
      e.preventDefault();
      Swal.fire({
        icon: 'error',
        title: 'Campos incompletos',
        html: 'Por favor completa los campos obligatorios (marcados con <strong>*</strong>) y corrige el correo si aplica.',
        confirmButtonColor: 'var(--rosa)'
      });
      return false;
    }

    // show saving micro interaction
    saveBtn.disabled = true;
    saveBtn.innerText = 'Guardando...';
  });

  // Cancelar: mostrar confirmación
  cancelBtn.addEventListener('click', function(){
    Swal.fire({
      title: '¿Cancelar?',
      text: "Se perderán los datos ingresados.",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: 'var(--rosa)',
      cancelButtonColor: 'var(--azul)',
      confirmButtonText: 'Sí, cancelar'
    }).then((res) => {
      if (res.isConfirmed) {
        window.location.href = 'index.php?view=clientes';
      }
    });
  });

  backBtn && backBtn.addEventListener('click', ()=> window.location.href='index.php?view=clientes');
</script>
</body>
</html>
