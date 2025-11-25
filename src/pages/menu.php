<?php
// Si no hay login, redirigir
if (!isset($_SESSION['usuario_id'])) {
    header("Location: pages/login.php");
    exit;
}

// Incluir el sistema de traducción
require_once __DIR__ . '/../config/translation.php';

// Incluir permisos
require_once (__DIR__ . "/../config/permisos.php");

$rol = $_SESSION['rol'];

// Seguridad extra: si el rol no tiene permisos, redirigir
if (!isset($permisos[$rol])) {
    header("Location: pages/login.php");
    exit;
}

// Foto del usuario
$fotoUsuario = $_SESSION['foto_perfil'] ?? '../public/img/1.png';
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

<style>
  :root {
    --primary: #b4c24d;
    --primary-dark: #9fb03d;
    --secondary: #2d4353;
    --accent: #e15871;
    --bg-gray: #eeeeee;
  }
  
  /* Header Premium */
  header {
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    background: rgba(255, 255, 255, 0.95);
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
  }
  
  /* Sidebar Premium */
  #sidebar {
    background: linear-gradient(180deg, var(--secondary) 0%, #1e3244 100%);
    box-shadow: 4px 0 24px rgba(0, 0, 0, 0.15);
  }
  
  /* Animaciones para items del sidebar */
  #sidebar ul li {
    animation: slideInLeft 0.3s ease-out;
    animation-fill-mode: both;
  }
  
  #sidebar ul li:nth-child(1) { animation-delay: 0.05s; }
  #sidebar ul li:nth-child(2) { animation-delay: 0.1s; }
  #sidebar ul li:nth-child(3) { animation-delay: 0.15s; }
  #sidebar ul li:nth-child(4) { animation-delay: 0.2s; }
  #sidebar ul li:nth-child(5) { animation-delay: 0.25s; }
  #sidebar ul li:nth-child(6) { animation-delay: 0.3s; }
  #sidebar ul li:nth-child(7) { animation-delay: 0.35s; }
  #sidebar ul li:nth-child(8) { animation-delay: 0.4s; }
  
  @keyframes slideInLeft {
    from { opacity: 0; transform: translateX(-20px); }
    to { opacity: 1; transform: translateX(0); }
  }
  
  #sidebar ul li a {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    padding-left: 1rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }
  
  #sidebar ul li a:hover {
    transform: translateX(4px);
  }
  
  #sidebar ul li a.bg-red-600 {
    background: linear-gradient(135deg, var(--accent) 0%, #dc2f4b 100%);
    box-shadow: 0 4px 12px rgba(225, 88, 113, 0.3);
  }
  
  #sidebar ul li a svg {
    transition: transform 0.3s ease;
  }
  
  #sidebar ul li a:hover svg {
    transform: scale(1.1);
  }
  
  /* User Block Premium */
  #userBlock {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }
  
  #userBlock:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2) !important;
  }
  
  /* Theme Toggle Premium */
  #themeToggle, #languageToggle {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }
  
  #themeToggle:hover, #languageToggle:hover {
    box-shadow: 0 4px 16px rgba(10, 35, 66, 0.4) !important;
  }
  
  /* Sidebar compacto (solo íconos) */
  .sidebar-cerrado {
    width: 80px !important;
    transition: width 0.3s ease;
  }

  .sidebar-cerrado ul li a {
    justify-content: center;
    padding: 1rem;
  }

  .sidebar-cerrado svg {
    margin: 0 auto;
  }

  /* Usuario reducido a círculo */
  .user-mini {
    justify-content: center !important;
    width: 60px !important;
    height: 60px !important;
    padding: 0 !important;
  }

  .user-mini img {
    width: 45px !important;
    height: 45px !important;
    border-radius: 50%;
  }

  .user-mini div {
    display: none;
  }

  /* Transiciones suaves */
  #sidebar,
  #userBlock {
    transition: all 0.3s ease;
  }
</style>

<!-- Header Premium -->
<header class="flex items-center bg-white text-black p-4 fixed top-0 left-0 right-0 z-40 shadow-lg h-18">
  <button id="menu-btn" class="text-2xl focus:outline-none mr-4 transition-all hover:scale-110">&#9776;</button>
  <img src="../public/img/logo2.png" alt="logo" class="h-12 ml-6">
  
  <!-- Botones de Tema e Idioma Premium -->
  <div style="display:flex; align-items:center; gap:14px; position:absolute; right:20px; top:50%; transform:translateY(-50%);">
    
    <!-- Botón de tema -->
    <div id="themeToggle"
         style="width:38px; height:38px; 
                display:flex; align-items:center; justify-content:center;
                border-radius:50%; background:#0A2342; 
                cursor:pointer; transition:all 0.3s ease; 
                box-shadow:0 4px 12px rgba(0,0,0,0.2);"
         onmouseover="this.style.transform='scale(1.15) rotate(15deg)';"
         onmouseout="this.style.transform='scale(1) rotate(0deg)';">
      <img id="themeIcon" src="../public/img/tema.png" alt="Tema" style="width:18px; height:18px; filter:invert(1);">
    </div>

    <!-- Botón de idioma MEJORADO con funcionalidad de HEAD -->
    <?php
      // Lógica para construir la URL del cambio de idioma
      $current_lang = isset($_GET['lang']) && $_GET['lang'] == 'en' ? 'en' : 'es';
      $new_lang = $current_lang == 'es' ? 'en' : 'es';
      
      $query_params = $_GET;
      $query_params['lang'] = $new_lang;
      $url = 'index.php?' . http_build_query($query_params);
    ?>
    <a href="<?= $url ?>" 
       title="<?= __('change_language') ?>"
       style="display:flex; align-items:center; gap:6px; text-decoration:none; color:inherit;">
      <div id="languageToggle"
           style="width:38px; height:38px; 
                  display:flex; align-items:center; justify-content:center;
                  border-radius:50%; background:#0A2342; 
                  cursor:pointer; transition:all 0.3s ease; 
                  box-shadow:0 4px 12px rgba(0,0,0,0.2);"
           onmouseover="this.style.transform='scale(1.15)';"
           onmouseout="this.style.transform='scale(1)';">
        <img src="../public/img/idiomaIcon.png" alt="<?= __('language_icon_alt') ?>" style="width:18px; height:18px; filter:invert(1);">
      </div>
      <span id="languageCode" style="font-weight:600; font-size:14px; color:#0A2342;"><?= strtoupper($current_lang) ?></span>
    </a>
  </div>
</header>

<!-- Sidebar Premium -->
<nav id="sidebar" 
     class="fixed top-0 left-0 h-full w-64 text-white transition-all duration-300 z-40 flex flex-col justify-between">
  <div>
    <!-- Logo y botón premium -->
    <div class="flex items-center border-b border-white/20" style="gap: 15px; padding: 1.25rem 0 1.25rem 1.25rem; background: rgba(0, 0, 0, 0.1);">
      <button id="sidebar-menu-btn" 
              class="text-2xl focus:outline-none hover:text-pink-400 transition-all" 
              style="width: 40px; text-align: left; display: flex; align-items: center; justify-content: flex-start; padding-left: 1rem;">
        &#9776;
      </button>
      <img src="../public/img/Logo_prisma_claro.png" alt="Logo" class="h-12 transition-all">
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
      <ul class="mt-5 space-y-2 px-3">
        <?php 
          // Detectar la vista actual
          $vista_actual = isset($_GET['view']) ? $_GET['view'] : '';
        ?>

        <?php foreach ($permisos[$rol] as $modulo): ?>
          <?php 
            $modulo_url = str_replace(' ', '_', $modulo); 
            // Comprobar si esta vista es la actual
            $activo = ($vista_actual === $modulo_url) ? 'bg-red-600 text-white' : 'hover:bg-red-500';
          ?>
          <li>
            <a href="index.php?view=<?= $modulo_url ?>" 
               class="flex items-center gap-3 p-3.5 rounded-xl transition-all <?= $activo ?>">
              <?= $iconos[$modulo] ?? '' ?>
              <span class="font-medium"><?= __($modulo_url) ?></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>

  <!-- Bloque de usuario premium -->
  <div class="w-full mt-auto mb-5 px-4 flex justify-center relative">
    <div id="userBlock" 
         class="flex items-center gap-3 shadow-lg px-4 py-3 cursor-pointer select-none"
         style="background:linear-gradient(135deg, #0A2342 0%, #04172e 100%); 
                border-radius:50px; transition:all 0.3s ease;">
      <div style="padding:2px; border-radius:50%; background:linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);">
        <img id="sidebarFoto" 
             src="<?= htmlspecialchars($fotoUsuario) ?>" 
             alt="Foto usuario"
             style="width:42px; height:42px; border-radius:50%; object-fit:cover; border:2px solid white;">
      </div>
      <div class="flex flex-col leading-tight">
        <span style="color:#b4c24d; font-weight:700; font-size:14px; letter-spacing:-0.02em;">
          <?= htmlspecialchars($_SESSION['nombre_completo'] ?? '') ?>
        </span>
        <span style="color:#cbd5e1; font-size:12px; font-weight:500;">
          <?= htmlspecialchars($_SESSION['rol'] ?? '') ?>
        </span>
      </div>
    </div>
  </div>
</nav>