<?php


// Si no hay login, redirigir
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php"); // mismo nivel que este archivo
    exit;
}

// Incluir permisos (está en /src/config/permisos.php)
require_once __DIR__ . "/../config/permisos.php";

$rol = $_SESSION['rol'];

// Seguridad extra: si el rol no tiene permisos, redirigir
if (!isset($permisos[$rol])) {
    header("Location: login.php"); // igual, mismo nivel
    exit;
}
?>

<script>
  // Checa estado del menú ANTES de que pinte la página
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
  <div class="flex items-center bg-gray-100 rounded-full overflow-hidden ml-4 w-180"></div>
</header>



<!-- Sidebar -->
<nav id="sidebar" class="fixed top-0 left-0 h-full w-64 bg-gray-800 text-white -translate-x-64 transition-transform duration-300 z-50 flex flex-col justify-between">
  <div>
    <div class="flex items-center justify-center p-4 border-b border-gray-700">
      <button id="sidebar-menu-btn" class="text-2xl focus:outline-none mr-4">&#9776;</button>
      <img src="../public/img/Logo_prisma_claro.png" alt="Logo" class="h-12">
    </div>

    <!-- Opciones dinámicas según rol -->
    <?php if (!empty($permisos[$rol])): ?>
      <ul class="mt-4 space-y-2 pl-4">
        <?php foreach ($permisos[$rol] as $modulo): ?>
          <li>
            <a href="index.php?view=<?= $modulo ?>" class="flex items-center gap-2 hover:bg-red-500 p-4 rounded-full">
              <?= ucfirst($modulo) ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>

  <!-- Separador y bloque usuario centrado/fijado -->
  <div class="w-full mb-4">
    <hr class="border-t border-gray-700 mb-2 mx-4">
    <div class="flex items-center bg-blue-900 rounded-xl p-3 mx-4 gap-3 shadow-lg">
  <img src="../public/img/1.png" alt="Foto usuario" class="w-12 h-12 rounded-full object-cover border-2 border-white mt-2">
      <div class="flex flex-col justify-center">
        <span class="text-lg font-semibold text-yellow-100 leading-tight"><?= htmlspecialchars($_SESSION['nombre_completo'] ?? '') ?></span>
        <span class="text-sm text-blue-100"><?= htmlspecialchars($_SESSION['rol'] ?? '') ?></span>
      </div>
    </div>
  </div>
</nav>



