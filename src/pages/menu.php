<?php
// Si no hay login, redirigir
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// Incluir permisos
require_once __DIR__ . "/../config/permisos.php";

$rol = $_SESSION['rol'];

// Seguridad extra: si el rol no tiene permisos, redirigir
if (!isset($permisos[$rol])) {
    header("Location: login.php");
    exit;
}
?>

<!-- Script para recordar el estado del menú -->
<script>
  (function() {
    const menuState = localStorage.getItem('menu');
    if (menuState === 'open') {
      document.documentElement.classList.add('menu-open');
    } else {
      document.documentElement.classList.add('menu-closed');
    }
  })();
</script>

<!-- Header -->
<header class="flex items-center bg-white text-black p-4 fixed top-0 left-0 right-0 z-40 shadow h-16">
  <button id="menu-btn" class="text-2xl focus:outline-none mr-4">&#9776;</button>
  <img src="../public/img/logo.jpeg" alt="logo" class="h-12">
</header>

<!-- Sidebar -->
<nav id="sidebar" 
     class="fixed top-0 left-0 h-full w-64 bg-gray-800 text-white 
            -translate-x-64 transition-transform duration-300 z-50 flex flex-col justify-between">

  <div>
    <!-- Logo y botón -->
    <div class="flex items-center justify-center p-4 border-b border-gray-700">
      <button id="sidebar-menu-btn" class="text-2xl focus:outline-none mr-4">&#9776;</button>
      <img src="../public/img/Logo_prisma_claro.png" alt="Logo" class="h-12">
    </div>

    <?php
    // Íconos SVG según módulo
    $iconos = [
        'nueva venta' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>',
        'ventas' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18V3H3zm5 14H6v-4h2v4zm4 0h-2v-8h2v8zm4 0h-2v-6h2v6z" /></svg>',
        'productos' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581a2.25 2.25 0 0 0 2.607.33 18.095 18.095 0 0 0 5.223-5.223 2.056 2.056 0 0 0-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" /></svg>',
        'proveedores' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" /></svg>',
        'caja' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 20.25h12m-7.5-3v3m3-3v3m-10.125-3h17.25c.621 0 1.125-.504 1.125-1.125V4.875c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125Z" /></svg>',
        'reportes' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18V3H3zm5 14H6v-4h2v4zm4 0h-2v-8h2v8zm4 0h-2v-6h2v6z" /></svg>',
        'clientes' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 0 0-4-4h-1M9 20H4v-2a4 4 0 0 1 4-4h1m0-6a4 4 0 1 1 8 0 4 4 0 0 1-8 0z"/></svg>',
        'empleados' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm-6 8v-2a6 6 0 0 1 12 0v2H6z"/></svg>',
    ];
    ?>

    <?php if (!empty($permisos[$rol])): ?>
      <ul class="mt-4 space-y-2 pl-4">
        <?php foreach ($permisos[$rol] as $modulo): ?>
          <?php $modulo_url = str_replace(' ', '_', $modulo); ?>
          <li>
            <a href="index.php?view=<?= $modulo_url ?>" 
               class="flex items-center gap-3 hover:bg-red-500 p-4 rounded-full transition-colors">
              <?= $iconos[$modulo] ?? '' ?>
              <span><?= ucfirst($modulo) ?></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>

<!-- Bloque de usuario -->
<div class="w-full mt-auto mb-4 px-4 flex justify-center">
  <div class="flex items-center gap-3 shadow-lg px-4 py-2"
       style="background-color:#0A2342; border-radius:50px;">
    <img src="../public/img/1.png" alt="Foto usuario"
         style="width:40px; height:40px; border-radius:50%; object-fit:cover; border:2px ;">
    <div class="flex flex-col leading-tight">
      <span style="color:#32CD32; font-weight:600; font-size:14px;">
        <?= htmlspecialchars($_SESSION['nombre_usuario'] ?? '') ?>
      </span>
      <span style="color:#cbd5e1; font-size:12px;">
        <?= htmlspecialchars($_SESSION['rol'] ?? '') ?>
      </span>
    </div>
  </div>
</div>
</nav>




