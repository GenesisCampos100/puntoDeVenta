<?php

// Si no hay login, redirigir
if (!isset($_SESSION['usuario_id'])) {
    header("Location: pages/login.php");
    exit;
}

require_once __DIR__ . "/../config/permisos.php";

$rol = $_SESSION['rol'] ?? null;

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
        'ventas' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"> <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>',
        'productos' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581a2.25 2.25 0 0 0 2.607.33 18.095 18.095 0 0 0 5.223-5.223 2.056 2.056 0 0 0-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" /></svg>',
        'proveedores' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" /></svg>',
        'caja' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 20.25h12m-7.5-3v3m3-3v3m-10.125-3h17.25c.621 0 1.125-.504 1.125-1.125V4.875c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125Z" /></svg>',
        'reportes' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"> <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" /></svg>',
        'clientes' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"> <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" /></svg>',
        'empleados' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"> <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" /></svg>',
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

  <!-- Separador y bloque usuario centrado/fijado -->
  <div class="w-full mb-4">
    <hr class="border-t border-gray-700 mb-2 mx-4">
    <div class="flex items-center bg-blue-900 rounded-xl p-3 mx-4 gap-3 shadow-lg">
  <img src="../public/img/1.png" alt="Foto usuario" class="w-12 h-12 rounded-full object-cover border-2 border-white mt-2">
      <div class="flex flex-col justify-center">
        <span class="text-lg font-semibold text-yellow-100 leading-tight"><?= htmlspecialchars($_SESSION['nombre_completo'] ?? '') ?></span>
        <span class="text-sm text-blue-100"><?= htmlspecialchars($_SESSION['rol'] ?? '') ?></span>
      </div>


  
    
   <!-- Menú flotante -->
<div id="logoutMenu"
     style="
        display:none;
        position:absolute;
        top:-50px;
        background:#e63946; /* 🔴 fondo rojo */
        color:white; /* ⚪ texto blanco */
        border-radius:12px;
        box-shadow:0 2px 10px rgba(0,0,0,0.3);
        padding:10px 20px;
        cursor:pointer;
        font-size:14px;
        z-index:9999;
        opacity:0;
        transform:translateY(10px);
        transition:opacity 0.2s ease, transform 0.2s ease, background-color 0.2s ease;
     ">
  Cerrar sesión
</div>

<style>
  /* Efecto hover: un rojo más oscuro */
  #logoutMenu:hover {
    background-color: #b91c1c; /* tono rojo más fuerte */
    transform: scale(1.05);
  }
</style>

  </div>
</nav>

<!-- 🔒 Modal de confirmación personalizado -->
<div id="confirmLogout" 
     style="display:none; position:fixed; top:0; left:0; width:100%; height:100%;
            background:rgba(0,0,0,0.5); z-index:99999; justify-content:center; align-items:center;">
  <div style="background:#0A2342; padding:25px 30px; border-radius:16px; text-align:center; width:90%; max-width:350px; color:white; box-shadow:0 5px 20px rgba(0,0,0,0.3);">
    <h3 style="font-size:18px; font-weight:600; margin-bottom:15px;">¿Seguro que deseas cerrar sesión?</h3>
    <div style="display:flex; justify-content:center; gap:15px; margin-top:10px;">
      <button id="btnConfirmarLogout" 
              style="background:#e63946; color:white; border:none; padding:8px 15px; border-radius:8px; cursor:pointer; font-weight:500;">
        Sí, cerrar sesión
      </button>
      <button id="btnCancelarLogout" 
              style="background:#475569; color:white; border:none; padding:8px 15px; border-radius:8px; cursor:pointer; font-weight:500;">
        Cancelar
      </button>
>>>>>>> origin/Genesis
    </div>
  </div>
</div>

<script>
window.addEventListener("load", () => {
  const userBlock = document.getElementById("userBlock");
  const logoutMenu = document.getElementById("logoutMenu");
  const modal = document.getElementById("confirmLogout");
  const btnConfirmar = document.getElementById("btnConfirmarLogout");
  const btnCancelar = document.getElementById("btnCancelarLogout");

  if (!userBlock || !logoutMenu || !modal) return;

  // Mostrar/ocultar menú
  userBlock.addEventListener("click", (e) => {
    e.stopPropagation();

    if (logoutMenu.style.display === "none" || logoutMenu.style.display === "") {
      logoutMenu.style.display = "block";
      requestAnimationFrame(() => {
        logoutMenu.style.opacity = "1";
        logoutMenu.style.transform = "translateY(0)";
      });
    } else {
      logoutMenu.style.opacity = "0";
      logoutMenu.style.transform = "translateY(10px)";
      setTimeout(() => (logoutMenu.style.display = "none"), 200);
    }
  });

  // Cerrar menú si clic fuera
  document.addEventListener("click", (e) => {
    if (!userBlock.contains(e.target) && logoutMenu.style.display === "block") {
      logoutMenu.style.opacity = "0";
      logoutMenu.style.transform = "translateY(10px)";
      setTimeout(() => (logoutMenu.style.display = "none"), 200);
    }
  });

  // Mostrar modal al hacer clic en "Cerrar sesión"
  logoutMenu.addEventListener("click", () => {
    modal.style.display = "flex";
    logoutMenu.style.opacity = "0";
    logoutMenu.style.transform = "translateY(10px)";
    setTimeout(() => (logoutMenu.style.display = "none"), 200);
  });

  // Confirmar cierre
  btnConfirmar.addEventListener("click", () => {
    window.location.href = "../src/pages/login.php"; 
  });

  // Cancelar cierre
  btnCancelar.addEventListener("click", () => {
    modal.style.display = "none";
  });

  // Cerrar modal al hacer clic fuera del cuadro
  modal.addEventListener("click", (e) => {
    if (e.target === modal) {
      modal.style.display = "none";
    }
  });
});
</script>
