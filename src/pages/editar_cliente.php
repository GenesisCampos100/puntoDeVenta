<?php

require_once __DIR__ . '/../config/db.php'; // <<--- ajusta ruta si es necesario

$errors = [];
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
    if ($data['correo'] !== '' && !filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) $serverErrors[] = 'correo_invalid';

    if (!empty($serverErrors)) {
      $_SESSION['form_cliente'] = $data;
      $_SESSION['form_cliente']['_editing_id'] = $id;
      $msg = [];
      foreach ($serverErrors as $s) {
        if ($s === 'correo_invalid') $msg[] = 'El correo es inválido.';
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
      $_SESSION['flash'] = ['type'=>'success','msg'=>'Cliente actualizado correctamente.'];
      header('Location: index.php?view=clientes');
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
  <title>Editar cliente — Punto de Venta</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
    :root{
      --verde: #b4c24d;
      --azul:  #2d4353;
      --rosa:  #e15871;
      --gris:  #eeeeee;
      --font: 'Poppins', sans-serif;
    }
    body { font-family: var(--font); background: linear-gradient(180deg,#fbfdff 0%, #f8fbf6 100%); }
    .btn-primary { background: var(--verde); color: white; }
    .input-error { border-color: var(--rosa) !important; box-shadow: 0 0 0 4px rgba(225,89,113,0.06); }
    @keyframes floatUp { from { opacity:0; transform: translateY(18px)} to { opacity:1; transform:translateY(0)} }
    .wow-in { animation: floatUp .45s cubic-bezier(.2,.9,.3,1) both; }
  </style>
</head>
<body class="p-6">
  <div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-6 wow-in">
      <div>
        <h2 class="text-2xl font-semibold" style="color:var(--azul)">Editar cliente</h2>
        <p class="text-sm text-gray-600 mt-1">Actualiza la información del cliente. Los campos con <span class="text-[#e15871] font-semibold">*</span> son obligatorios.</p>
      </div>
      <div>
        <button id="backBtn" class="px-3 py-2 rounded-lg border hover:shadow-sm">Volver</button>
      </div>
    </div>

    <div class="bg-white rounded-2xl shadow p-6 wow-in">
      <?php if(!empty($errors)): ?>
        <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-100 text-red-700">
          <?php foreach($errors as $err): ?><div><?=htmlspecialchars($err)?></div><?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form id="formCliente" method="POST" action="index.php?view=editar_cliente&id=<?=$id?>" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input type="hidden" name="action" value="update">

        <div>
          <label class="block text-sm font-medium text-gray-700">Nombre <span class="text-[#e15871]">*</span></label>
          <input name="nombre" id="nombre" value="<?=htmlspecialchars($cliente['nombre'])?>" class="input mt-1 block w-full rounded-lg border px-3 py-2" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Apellido paterno <span class="text-[#e15871]">*</span></label>
          <input name="apellido_paterno" id="apellido_paterno" value="<?=htmlspecialchars($cliente['apellido_paterno'])?>" class="input mt-1 block w-full rounded-lg border px-3 py-2" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Apellido materno</label>
          <input name="apellido_materno" id="apellido_materno" value="<?=htmlspecialchars($cliente['apellido_materno'])?>" class="input mt-1 block w-full rounded-lg border px-3 py-2" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Celular <span class="text-[#e15871]">*</span></label>
          <input name="celular" id="celular" value="<?=htmlspecialchars($cliente['celular'])?>" class="input mt-1 block w-full rounded-lg border px-3 py-2" />
        </div>

        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700">Correo</label>
          <input name="correo" id="correo" value="<?=htmlspecialchars($cliente['correo'])?>" class="input mt-1 block w-full rounded-lg border px-3 py-2" />
        </div>

        <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Calle</label>
            <input name="calle" id="calle" value="<?=htmlspecialchars($cliente['calle'])?>" class="input mt-1 block w-full rounded-lg border px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Número exterior</label>
            <input name="num_ext" id="num_ext" value="<?=htmlspecialchars($cliente['num_ext'])?>" class="input mt-1 block w-full rounded-lg border px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Número interior</label>
            <input name="num_int" id="num_int" value="<?=htmlspecialchars($cliente['num_int'])?>" class="input mt-1 block w-full rounded-lg border px-3 py-2" />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Colonia</label>
          <input name="colonia" id="colonia" value="<?=htmlspecialchars($cliente['colonia'])?>" class="input mt-1 block w-full rounded-lg border px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Código postal</label>
          <input name="cp" id="cp" value="<?=htmlspecialchars($cliente['cp'])?>" class="input mt-1 block w-full rounded-lg border px-3 py-2" />
        </div>
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700">Estado</label>
          <input name="estado" id="estado" value="<?=htmlspecialchars($cliente['estado'])?>" class="input mt-1 block w-full rounded-lg border px-3 py-2" />
        </div>

        <div class="md:col-span-2 flex justify-end gap-3 mt-4">
          <button type="button" id="cancelBtn" class="px-4 py-2 rounded-lg border">Cancelar</button>
          <button type="submit" id="saveBtn" class="px-4 py-2 rounded-lg btn-primary">Guardar cambios</button>
        </div>
      </form>
    </div>
  </div>

<script>
  // If server sent errors, show them and mark inputs
  <?php if(!empty($errors)): ?>
    const serverErrors = <?= json_encode($errors) ?>;
    required = ['nombre','apellido_paterno','celular'];
    required.forEach(n => {
      const el = document.getElementById(n);
      if (el && el.value.trim() === '') el.classList.add('input-error');
    });
    Swal.fire({ title: 'Errores', html: serverErrors.join('<br>'), icon: 'error', confirmButtonColor: 'var(--rosa)' });
  <?php endif; ?>

  const form = document.getElementById('formCliente');
  const saveBtn = document.getElementById('saveBtn');
  const cancelBtn = document.getElementById('cancelBtn');
  const backBtn = document.getElementById('backBtn');

  const requiredFields = ['nombre','apellido_paterno','celular'];

  form.addEventListener('submit', function(e){
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
      Swal.fire({ icon: 'error', title: 'Campos incompletos', html: 'Completa los campos obligatorios y corrige el correo si aplica.', confirmButtonColor: 'var(--rosa)' });
      return false;
    }

    saveBtn.disabled = true;
    saveBtn.innerText = 'Guardando...';
  });

  cancelBtn.addEventListener('click', function(){
    Swal.fire({
      title: '¿Cancelar edición?',
      text: "Se perderán los cambios no guardados.",
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
