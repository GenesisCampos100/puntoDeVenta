<?php
// clientes.php
// Ruta sugerida: index.php?view=clientes

require_once __DIR__ . '/../config/db.php'; // <<--- ajusta ruta si es necesario

// Manejo de eliminación (ajax JSON POST a este mismo archivo)
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

// Mensajes flash (opcional)
$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Búsqueda simple
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

$sql = "SELECT * FROM clientes";
$params = [];
if ($q !== '') {
    $sql .= " WHERE nombre LIKE :q OR apellido_paterno LIKE :q OR apellido_materno LIKE :q OR correo LIKE :q OR celular LIKE :q";
    $params[':q'] = "%$q%";
}
$sql .= " ORDER BY id_cliente DESC";
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
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

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
    body { font-family: var(--font); background: linear-gradient(180deg,#ffffff 0%, #f7fafc 100%); }
    .btn-primary { background: var(--verde); color: white; }
    .animate-fade { animation: fadeIn .35s ease both; }
    @keyframes fadeIn { from { opacity:0; transform: translateY(6px) } to { opacity:1; transform: translateY(0) } }
  </style>
</head>
<body class="p-6">
  <div class="max-w-6xl mx-auto animate-fade">
    <header class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-semibold" style="color:var(--azul)">Clientes</h1>
      <div class="flex items-center gap-3">
        <input type="text" id="search" value="<?=htmlspecialchars($q)?>" placeholder="Buscar cliente..." class="px-3 py-2 rounded-lg border w-64 focus:outline-none focus:ring-2" />
        <button id="btnBuscar" class="px-4 py-2 rounded-lg border hover:shadow-sm transition">Buscar</button>
        <button id="btnAgregar" class="px-4 py-2 rounded-lg btn-primary hover:opacity-95 transition shadow-md">
          Agregar cliente
        </button>
      </div>
    </header>

    <div class="bg-white rounded-2xl shadow p-4">
      <?php if($flash_success): ?>
        <div class="p-3 mb-4 rounded-lg bg-green-50 border border-green-100 text-green-700"><?=htmlspecialchars($flash_success)?></div>
      <?php endif; ?>
      <?php if($flash_error): ?>
        <div class="p-3 mb-4 rounded-lg bg-red-50 border border-red-100 text-red-700"><?=htmlspecialchars($flash_error)?></div>
      <?php endif; ?>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y">
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
          <tbody class="divide-y">
            <?php if (count($clientes)===0): ?>
              <tr><td colspan="6" class="p-6 text-center text-gray-500">No hay clientes registrados.</td></tr>
            <?php else: ?>
              <?php foreach($clientes as $c): ?>
                <tr class="hover:bg-gray-50 transition">
                  <td class="px-4 py-3 text-sm"><?=htmlspecialchars($c['id_cliente'])?></td>
                  <td class="px-4 py-3 text-sm">
                    <?=htmlspecialchars(trim($c['nombre'].' '.$c['apellido_paterno'].' '.$c['apellido_materno']))?>
                  </td>
                  <td class="px-4 py-3 text-sm"><?=htmlspecialchars($c['celular'])?></td>
                  <td class="px-4 py-3 text-sm"><?=htmlspecialchars($c['correo'])?></td>
                  <td class="px-4 py-3 text-sm">
                    <?=htmlspecialchars(trim($c['calle'].' '.$c['num_ext'].' '.$c['num_int'].' '.$c['colonia'].' '.$c['cp'].' '.$c['estado']))?>
                  </td>
                  <td class="px-4 py-3 text-right">
                    <div class="inline-flex gap-2">
                      <button onclick="location.href='index.php?view=agregar_cliente&id=<?= $c['id_cliente'] ?>'" class="px-3 py-1 rounded-lg border hover:bg-[var(--gris)] transition">Editar</button>
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

<script>
  // Búsqueda simple
  document.getElementById('btnBuscar').addEventListener('click', ()=> {
    const q = document.getElementById('search').value;
    const url = new URL(window.location.href);
    url.searchParams.set('q', q);
    window.location.href = url.toString();
  });
  document.getElementById('search').addEventListener('keypress', (e) => {
    if (e.key === 'Enter') document.getElementById('btnBuscar').click();
  });

  // Agregar cliente (ruta conforme a tu ejemplo)
  document.getElementById('btnAgregar').addEventListener('click', ()=> {
    window.location.href = "index.php?view=agregar_cliente";
  });

  // Eliminar con SweetAlert + fetch a este mismo archivo
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
        fetch('index.php?view=clientes&action=delete', {
          method: 'POST',
          headers: {'Content-Type':'application/json'},
          body: JSON.stringify({id: id})
        })
        .then(r => r.json())
        .then(json => {
          if(json.success){
            Swal.fire('Eliminado','Cliente eliminado correctamente','success').then(()=> location.reload());
          } else {
            Swal.fire('Error', json.error || 'No se pudo eliminar','error');
          }
        })
        .catch(()=> Swal.fire('Error','Error en el servidor','error'));
      }
    });
  }
</script>
</body>
</html>
