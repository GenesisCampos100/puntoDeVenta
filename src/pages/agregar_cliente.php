<?php
// agregar_cliente.php


require_once __DIR__ . '/../config/db.php'; // <<--- ajusta ruta si es necesario

$editing = false;
$cliente = [
  'nombre'=>'','apellido_paterno'=>'','apellido_materno'=>'','celular'=>'',
  'correo'=>'','calle'=>'','num_ext'=>'','num_int'=>'','colonia'=>'','cp'=>'','estado'=>''
];

// Si hay datos previos en sesión (por ejemplo, validación fallida), rellenar
if (!empty($_SESSION['form_cliente'])) {
  $cliente = array_merge($cliente, $_SESSION['form_cliente']);
  unset($_SESSION['form_cliente']);
  $errors = $_SESSION['form_errors'] ?? [];
  unset($_SESSION['form_errors']);
}

// Si viene id, cargar para editar (solo si no venían datos en sesión)
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
  $id = (int)$_GET['id'];
  $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id_cliente = :id LIMIT 1");
  $stmt->execute([':id'=>$id]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($row) {
    $editing = true;
    if (empty($cliente['nombre'])) {
      $cliente = $row;
    }
  } else {
    $_SESSION['flash_error'] = "Cliente no encontrado.";
    header("Location: index.php?view=clientes");
    exit;
  }
}

// Procesamiento del formulario (create / update) - se procesa en este mismo archivo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';
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

    // Validación simple
    $errors = [];
    if ($data['nombre'] === '') $errors[] = 'El nombre es obligatorio.';
    if ($data['correo'] !== '' && !filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) $errors[] = 'El correo es inválido.';

    if (!empty($errors)) {
      $_SESSION['form_cliente'] = $data;
      $_SESSION['form_errors'] = $errors;
      // Redirigir para repoblar (mantener modo editar si aplica)
      if (!empty($_POST['id_cliente'])) {
        header('Location: index.php?view=agregar_cliente&id=' . (int)$_POST['id_cliente']);
      } else {
        header('Location: index.php?view=agregar_cliente');
      }
      exit;
    }

    try {
      if ($action === 'create') {
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
        $_SESSION['flash_success'] = "Cliente creado correctamente.";
        header('Location: index.php?view=clientes');
        exit;
      } elseif ($action === 'update') {
        $id = isset($_POST['id_cliente']) ? (int)$_POST['id_cliente'] : 0;
        if ($id <= 0) throw new Exception('ID inválido.');
        $sql = "UPDATE clientes SET nombre=:nombre, apellido_paterno=:apellido_paterno, apellido_materno=:apellido_materno,
                celular=:celular, correo=:correo, calle=:calle, num_ext=:num_ext, num_int=:num_int, colonia=:colonia, cp=:cp, estado=:estado
                WHERE id_cliente = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge($data, [':id'=>$id]));
        $_SESSION['flash_success'] = "Cliente actualizado correctamente.";
        header('Location: index.php?view=clientes');
        exit;
      } else {
        $_SESSION['flash_error'] = 'Acción no válida.';
        header('Location: index.php?view=clientes');
        exit;
      }
    } catch (Exception $e) {
      $_SESSION['form_cliente'] = $data;
      $_SESSION['form_errors'] = [$e->getMessage()];
      if (!empty($_POST['id_cliente'])) {
        header('Location: index.php?view=agregar_cliente&id=' . (int)$_POST['id_cliente']);
      } else {
        header('Location: index.php?view=agregar_cliente');
      }
      exit;
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?= isset($_GET['id']) ? 'Editar cliente' : 'Agregar cliente' ?> — Punto de Venta</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
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
    body { font-family: var(--font); background: #fbfdff; }
    .btn-primary { background: var(--verde); color: white; }
    .animate-fade-in { animation: fadeIn .28s ease both; }
    @keyframes fadeIn { from { opacity:0; transform: translateY(8px) } to { opacity:1; transform: translateY(0) } }
  </style>
</head>
<body class="p-6">
  <div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-xl font-semibold"><?= isset($_GET['id']) ? 'Editar cliente' : 'Agregar cliente' ?></h2>
      <div class="flex gap-2">
        <button onclick="window.location.href='index.php?view=clientes'" class="px-3 py-2 rounded-lg border">Volver</button>
      </div>
    </div>

    <form id="formCliente" action="index.php?view=agregar_cliente" method="POST" class="bg-white rounded-2xl shadow p-6 space-y-4 animate-fade-in">
      <input type="hidden" name="action" value="<?= isset($_GET['id']) ? 'update' : 'create' ?>">
      <?php if(isset($_GET['id'])): ?><input type="hidden" name="id_cliente" value="<?= (int)$_GET['id'] ?>"><?php endif; ?>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium">Nombre</label>
          <input name="nombre" value="<?=htmlspecialchars($cliente['nombre'])?>" required class="mt-1 block w-full rounded-lg border px-3 py-2 focus:ring-2" />
        </div>
        <div>
          <label class="block text-sm font-medium">Apellido paterno</label>
          <input name="apellido_paterno" value="<?=htmlspecialchars($cliente['apellido_paterno'])?>" class="mt-1 block w-full rounded-lg border px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm font-medium">Apellido materno</label>
          <input name="apellido_materno" value="<?=htmlspecialchars($cliente['apellido_materno'])?>" class="mt-1 block w-full rounded-lg border px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm font-medium">Celular</label>
          <input name="celular" value="<?=htmlspecialchars($cliente['celular'])?>" class="mt-1 block w-full rounded-lg border px-3 py-2" />
        </div>
        <div class="md:col-span-2">
          <label class="block text-sm font-medium">Correo</label>
          <input name="correo" type="email" value="<?=htmlspecialchars($cliente['correo'])?>" class="mt-1 block w-full rounded-lg border px-3 py-2" />
        </div>
        <div class="md:col-span-2">
          <label class="block text-sm font-medium">Calle</label>
          <input name="calle" value="<?=htmlspecialchars($cliente['calle'])?>" class="mt-1 block w-full rounded-lg border px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm font-medium">Número exterior</label>
          <input name="num_ext" value="<?=htmlspecialchars($cliente['num_ext'])?>" class="mt-1 block w-full rounded-lg border px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm font-medium">Número interior</label>
          <input name="num_int" value="<?=htmlspecialchars($cliente['num_int'])?>" class="mt-1 block w-full rounded-lg border px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm font-medium">Colonia</label>
          <input name="colonia" value="<?=htmlspecialchars($cliente['colonia'])?>" class="mt-1 block w-full rounded-lg border px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm font-medium">Código postal</label>
          <input name="cp" value="<?=htmlspecialchars($cliente['cp'])?>" class="mt-1 block w-full rounded-lg border px-3 py-2" />
        </div>
        <div class="md:col-span-2">
          <label class="block text-sm font-medium">Estado</label>
          <input name="estado" value="<?=htmlspecialchars($cliente['estado'])?>" class="mt-1 block w-full rounded-lg border px-3 py-2" />
        </div>
      </div>

      <div class="flex items-center gap-3 justify-end">
        <button type="button" onclick="window.location.href='index.php?view=clientes'" class="px-4 py-2 rounded-lg border">Cancelar</button>
        <button type="submit" class="px-4 py-2 rounded-lg btn-primary hover:opacity-95 transition shadow">Guardar</button>
      </div>
    </form>
  </div>

<?php if(!empty($errors)): ?>
<script>
  // Mostrar errores si existieran (procedentes de validación en el servidor)
  Swal.fire({
    title: 'Errores en el formulario',
    html: '<?= implode("<br>", array_map("htmlspecialchars", $errors)) ?>',
    icon: 'error'
  });
</script>
<?php endif; ?>

<script>
  // Prevent double submit and show a small "guardando" feedback
  document.getElementById('formCliente').addEventListener('submit', function(e){
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerText = 'Guardando...';
  });
</script>
</body>
</html>
