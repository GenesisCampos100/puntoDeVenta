<?php
session_start();

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


<!-- Header -->
<header class="flex items-center bg-white text-black p-4 fixed top-0 left-0 right-0 z-40 shadow">
  <button id="menu-btn" class="text-2xl focus:outline-none mr-4">&#9776;</button>
  <img src="../public/img/logo.jpeg" alt="logo" class="h-12">

  <div class="flex items-center bg-gray-100 rounded-full overflow-hidden ml-4 w-180">
    <input type="text" placeholder="Buscar..." class="w-full px-4 py-2 text-black focus:outline-none">
  </div>

  <button class="ml-2 bg-botonVerde hover:bg-botonVerde-hover text-black px-6 py-2 rounded-full">
    Filtros
  </button>
</header>

<!-- Sidebar -->
<nav id="sidebar" class="fixed top-0 left-0 h-full w-64 bg-gray-800 text-white -translate-x-64 transition-transform duration-300 z-50">
  <div class="flex items-center justify-center p-4 border-b border-gray-700">
    <img src="../public/img/logo2.png" alt="Logo" class="h-12">
  </div>
  <div class="flex justify-end p-4">
    <button id="close-btn" class="text-2xl">&times;</button>
  </div>

  <!-- Opciones dinámicas según rol -->
  <ul class="mt-4 space-y-2 pl-4">
    <?php foreach ($permisos[$rol] as $modulo): ?>
      <li>
       <a href="index.php?view=<?= $modulo ?>" class="flex items-center gap-2 hover:bg-red-500 p-2 rounded">
  <?= ucfirst($modulo) ?>
</a>
      </li>
    <?php endforeach; ?>
  </ul>
</nav>




