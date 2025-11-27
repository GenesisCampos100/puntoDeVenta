<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Punto de Venta</title>
  
  <!-- Fonts & Styles -->
  <link rel="stylesheet" href="../CSS/layout.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <style>
    :root {
      --primary: #b4c24d;
      --primary-dark: #9fb03d;
      --secondary: #2d4353;
      --accent: #e15871;
      --bg-gray: #eeeeee;
      --font: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
    }
    
    body {
      font-family: var(--font);
      background: linear-gradient(135deg, #f9fafb 0%, var(--bg-gray) 100%);
      min-height: 100vh;
      overflow-x: hidden;
    }
    
    /* Animaciones globales */
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    
    @keyframes scaleIn {
      from { opacity: 0; transform: scale(0.95); }
      to { opacity: 1; transform: scale(1); }
    }
    
    /* Main content adjustments */
    #content {
      animation: fadeIn 0.5s ease-out;
    }
    
    /* Toast Notifications Premium */
    .toast {
      position: fixed;
      bottom: 30px;
      right: 30px;
      color: white;
      padding: 16px 24px;
      border-radius: 16px;
      font-weight: 600;
      font-size:14px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.2);
      opacity: 0;
      transform: translateY(20px);
      transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
      z-index: 9999;
      backdrop-filter: blur(10px);
    }
    
    .toast.show {
      opacity: 1;
      transform: translateY(0);
    }
    
    .toast.success { 
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }
    
    .toast.error { 
      background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    }
    
    .toast.info { 
      background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    }
    
    /* Modal overlay premium */
    .modal-overlay {
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
    }
  </style>
</head>
<body class="bg-gray-100">

  <?php 
    require_once __DIR__ . "/config/translation.php";
    include __DIR__ . "/pages/menu.php"; 
  ?>
  
  <main 
    id="content" 
    class="mt-16 pt-20 px-8 transition-all duration-300"
  >
    <?php
      // Aquí se cargará el contenido de cada vista
      if (isset($contenido)) {
          include $contenido;
      }
    ?>
  </main>

  <!-- 🌟 MODAL PERFIL PREMIUM -->
  <div id="userModal" 
       class="modal-overlay"
       style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6);
              z-index:99999; justify-content:center; align-items:center;">
    <div style="background:linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%); 
                border-radius:28px; width:90%; max-width:420px;
                padding:40px 30px; text-align:center; 
                box-shadow:0 20px 60px rgba(0,0,0,0.3);
                position:relative; animation:scaleIn 0.3s ease-out;">
      
      <!-- Botón cerrar premium -->
      <button id="closeUserModal"
              style="position:absolute; top:20px; right:20px; background:rgba(239, 68, 68, 0.1); 
                     border:none; cursor:pointer; padding:8px; border-radius:50%; 
                     transition:all 0.25s ease; width:40px; height:40px;
                     display:flex; align-items:center; justify-content:center;">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="22" height="22"
             fill="none" stroke="#ef4444" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18" />
          <line x1="6" y1="6" x2="18" y2="18" />
        </svg>
      </button>

      <!-- Foto de perfil con efecto premium -->
      <div style="position:relative; display:inline-block; margin-bottom:20px;">
        <div style="padding:5px; border-radius:50%; background:linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);">
          <img id="mainFotoPerfil" 
               src="<?= htmlspecialchars($_SESSION['foto_perfil'] ?? 'public/img/1.png') ?>" 
               alt="Usuario"
               style="width:100px; height:100px; border-radius:50%; object-fit:cover; 
                      cursor:pointer; border:4px solid white;">
        </div>
        
        <label for="fotoPerfilInput" 
               style="position:absolute; bottom:0; right:0; 
                      background:linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); 
                      border-radius:50%; width:36px; height:36px; 
                      display:flex; align-items:center; justify-content:center; 
                      cursor:pointer; box-shadow:0 4px 12px rgba(0,0,0,0.2);
                      transition:all 0.3s ease; border:3px solid white;">
          <img src="../public/img/cambioUsuario.png" alt="Cambiar" style="width:18px; height:18px; filter:brightness(0) invert(1);">
        </label>
      </div>

      <!-- Nombre y saludo premium -->
      <h3 style="margin-top:12px; font-size:16px; font-weight:600; color:#1f2937; letter-spacing:-0.02em;">
        <span style="color:var(--accent); font-weight:700;"><?= __('hello') ?></span>
        <?= htmlspecialchars($_SESSION['nombre_completo'] ?? '') ?>
      </h3>
      
      <!-- Correo con icono -->
      <p style="margin-top:8px; font-size:14px; color:#6b7280; display:flex; align-items:center; justify-content:center; gap:6px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
          <polyline points="22,6 12,13 2,6"></polyline>
        </svg>
        <?= htmlspecialchars($_SESSION['correo'] ?? '') ?>
      </p>

      <!-- Opciones con diseño premium -->
      <div style="margin-top:28px; background:white; border-radius:20px; 
                  padding:20px; box-shadow:0 4px 20px rgba(0,0,0,0.08);">
        
        <!-- Cerrar sesión -->
        <div id="logoutOption" 
             style="display:flex; align-items:center; justify-content:space-between; 
                    cursor:pointer; padding:12px 16px; border-radius:12px;
                    transition:all 0.3s ease;">
          <span style="display:inline-flex; align-items:center; gap:10px; 
                       color:#ef4444; font-weight:600; font-size:15px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
              <polyline points="16 17 21 12 16 7"></polyline>
              <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            <?= __('logout') ?>
          </span>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2">
            <polyline points="9 18 15 12 9 6"></polyline>
          </svg>
        </div>

        <!-- Confirmación de logout premium -->
        <div id="confirmLogout" 
             style="display:none; margin-top:20px; 
                    background:linear-gradient(135deg, var(--secondary) 0%, #1e3244 100%); 
                    color:white; padding:20px; border-radius:16px;
                    box-shadow:0 8px 24px rgba(0,0,0,0.15);">
          <p style="font-weight:500; margin-bottom:16px;"><?= __('confirm_logout_text') ?></p>
          <div style="display:flex; justify-content:center; gap:12px;">
            <button id="btnConfirmLogout" 
                    style="background:linear-gradient(135deg, #ef4444 0%, #dc2626 100%); 
                           border:none; color:white; padding:10px 24px; 
                           border-radius:10px; cursor:pointer; font-weight:600;
                           box-shadow:0 4px 12px rgba(239, 68, 68, 0.3);
                           transition:all 0.3s ease;">
              <?= __('yes') ?>
            </button>
            <button id="btnCancelLogout" 
                    style="background:#64748b; border:none; color:white; 
                           padding:10px 24px; border-radius:10px; cursor:pointer;
                           font-weight:600; transition:all 0.3s ease;">
              <?= __('cancel') ?>
            </button>
          </div>
        </div>
      </div>

      <!-- Enlaces premium -->
      <div style="margin-top:24px; font-size:12px; color:#9ca3af; display:flex; gap:12px; justify-content:center;">
        <a href="../public/docs/privacidad.pdf" target="_blank" 
           style="color:#6b7280; text-decoration:none; transition:color 0.3s ease;">
          <?= __('privacy_policy') ?>
        </a>
        <span>•</span>
        <a href="../public/docs/terminos.pdf" target="_blank" 
           style="color:#6b7280; text-decoration:none; transition:color 0.3s ease;">
          <?= __('terms_of_service') ?>
        </a>
      </div>
    </div>
  </div>

  <!-- 🌸 MODAL CAMBIAR FOTO PREMIUM -->
  <div id="changePhotoModal"
       class="modal-overlay"
       style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6);
              z-index:100000; justify-content:center; align-items:center;">
    <div style="background:linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%); 
                border-radius:28px; width:90%; max-width:480px;
                padding:40px 30px; text-align:center; 
                box-shadow:0 20px 60px rgba(0,0,0,0.3);
                position:relative; animation:scaleIn 0.3s ease-out;">
      
      <!-- Botón volver premium -->
      <button id="btnVolver"
              style="position:absolute; top:20px; left:20px; 
                     background:rgba(239, 68, 68, 0.1); border:none;
                     cursor:pointer; padding:8px; border-radius:50%; 
                     transition:all 0.25s ease; width:40px; height:40px;
                     display:flex; align-items:center; justify-content:center;">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="22" height="22" 
             fill="none" stroke="#ef4444" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="15 18 9 12 15 6" />
        </svg>
      </button>
      
      <h2 style="font-size:22px; margin-bottom:24px; font-weight:700; color:var(--secondary); letter-spacing:-0.02em;">
        <?= __('add_profile_photo') ?>
      </h2>

      <form id="formFoto" enctype="multipart/form-data">
        <div style="background:linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); 
                    border-radius:24px; padding:32px;">
          
          <!-- Preview con borde premium -->
          <div style="background:linear-gradient(135deg, var(--accent) 0%, #dc2f4b 100%); 
                      border-radius:50%; width:140px; height:140px;
                      display:flex; justify-content:center; align-items:center; 
                      margin:0 auto 20px; padding:5px; box-shadow:0 12px 40px rgba(225, 88, 113, 0.3);">
            <img id="previewFoto" 
                 src="<?= htmlspecialchars($_SESSION['foto_perfil'] ?? 'public/img/1.png') ?>" 
                 alt="Usuario"
                 style="width:130px; height:130px; border-radius:50%; object-fit:cover; 
                        cursor:pointer; border:4px solid white;">
          </div>
          
          <p style="font-weight:600; margin-bottom:20px; color:var(--secondary); font-size:15px;">
            <?= __('select_photo') ?>
          </p>
          
          <!-- Botón subir archivo premium -->
          <label for="inputArchivo"
                 style="background:linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); 
                        color:white; border:none; padding:12px 24px;
                        border-radius:12px; cursor:pointer; display:inline-flex; 
                        align-items:center; gap:10px; font-weight:600;
                        box-shadow:0 4px 16px rgba(180, 194, 77, 0.3);
                        transition:all 0.3s ease;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
              <polyline points="17 8 12 3 7 8"></polyline>
              <line x1="12" y1="3" x2="12" y2="15"></line>
            </svg>
            <?= __('upload_from_computer') ?>
          </label>
          <input type="file" id="inputArchivo" name="foto" accept="image/*" style="display:none;">
          
          <button type="submit"
                  style="margin-top:24px; 
                         background:linear-gradient(135deg, var(--secondary) 0%, #1e3244 100%); 
                         color:#fff; border:none;
                         padding:12px 32px; border-radius:12px; cursor:pointer;
                         font-weight:700; font-size:15px;
                         box-shadow:0 4px 16px rgba(45, 67, 83, 0.3);
                         transition:all 0.3s ease;">
            <?= __('save_changes') ?>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Scripts de funcionalidad -->
  <script>
  document.addEventListener("DOMContentLoaded", () => {
    // Referencias a elementos
    const userBlock = document.getElementById("userBlock");
    const userModal = document.getElementById("userModal");
    const closeUserModalBtn = document.getElementById("closeUserModal");
    const logoutOption = document.getElementById("logoutOption");
    const confirmBox = document.getElementById("confirmLogout");
    const btnConfirm = document.getElementById("btnConfirmLogout");
    const btnCancel = document.getElementById("btnCancelLogout");
    
    const changePhotoModal = document.getElementById("changePhotoModal");
    const btnVolver = document.getElementById("btnVolver");
    const inputArchivo = document.getElementById("inputArchivo");
    const previewFoto = document.getElementById("previewFoto");
    const formFoto = document.getElementById("formFoto");
    
    const mainFotoPerfil = document.getElementById("mainFotoPerfil");
    const sidebarFoto = document.getElementById("sidebarFoto");

    // --- MODAL DE USUARIO ---
    if (userBlock) {
      userBlock.addEventListener("click", () => {
        userModal.style.display = "flex";
        document.body.style.overflow = "hidden";
      });
    }

    if (closeUserModalBtn) {
      closeUserModalBtn.addEventListener("click", () => {
        userModal.style.display = "none";
        document.body.style.overflow = "auto";
      });
    }

    if (logoutOption) {
      logoutOption.addEventListener("click", () => {
        confirmBox.style.display = "block";
      });
    }

    if (btnConfirm) {
      btnConfirm.addEventListener("click", () => {
        // Usar ruta absoluta correcta para evitar doble "src/src"
        window.location.href = "/PrismaMK2C/src/scripts/logout.php";
      });
    }

    if (btnCancel) {
      btnCancel.addEventListener("click", () => {
        confirmBox.style.display = "none";
      });
    }

    if (userModal) {
      userModal.addEventListener("click", (e) => {
        if (e.target === userModal) {
          userModal.style.display = "none";
          document.body.style.overflow = "auto";
        }
      });
    }

    // --- MODAL DE CAMBIO DE FOTO ---
    if (mainFotoPerfil) {
      mainFotoPerfil.addEventListener("click", () => {
        userModal.style.display = "none";
        changePhotoModal.style.display = "flex";
      });
    }
    
    if (btnVolver) {
        btnVolver.addEventListener('click', () => {
            changePhotoModal.style.display = 'none';
            userModal.style.display = 'flex';
        });
    }

    if (inputArchivo) {
      inputArchivo.addEventListener("change", (e) => {
        const file = e.target.files[0];
        if (file) {
            previewFoto.src = URL.createObjectURL(file);
        }
      });
    }

    if (changePhotoModal) {
      changePhotoModal.addEventListener("click", (e) => {
        if (e.target === changePhotoModal) {
          changePhotoModal.style.display = "none";
        }
      });
    }

    if (formFoto) {
      formFoto.addEventListener("submit", (e) => {
        e.preventDefault();
        const formData = new FormData(formFoto);

        fetch("scripts/guardar_foto.php", {
          method: "POST",
          body: formData
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            const newPhotoUrl = data.newPhoto + '?t=' + new Date().getTime();
            if(mainFotoPerfil) mainFotoPerfil.src = newPhotoUrl;
            if(sidebarFoto) sidebarFoto.src = newPhotoUrl;
            if(previewFoto) previewFoto.src = newPhotoUrl;
            
            changePhotoModal.style.display = "none";
            userModal.style.display = "flex";
          } else {
            alert("Error al guardar la foto: " + data.error);
          }
        })
        .catch(err => {
          console.error("Error en la conexión: ", err);
          alert("Error en la conexión: " + err);
        });
      });
    }

    // --- EFECTOS HOVER ---
    const logoutBtn = document.getElementById('logoutOption');
    if(logoutBtn) {
        logoutBtn.addEventListener('mouseover', function() { this.style.background = '#fef2f2'; });
        logoutBtn.addEventListener('mouseout', function() { this.style.background = 'transparent'; });
    }
    
    const closeBtn = document.getElementById('closeUserModal');
    if(closeBtn) {
        closeBtn.addEventListener('mouseover', () => {
            closeBtn.style.background = '#fee2e2';
            closeBtn.style.transform = 'scale(1.1)';
        });
        closeBtn.addEventListener('mouseout', () => {
            closeBtn.style.background = 'rgba(239, 68, 68, 0.1)';
            closeBtn.style.transform = 'scale(1)';
        });
    }
    
    const backBtn = document.getElementById('btnVolver');
    if(backBtn) {
        backBtn.addEventListener('mouseover', () => {
            backBtn.style.background = '#fee2e2';
            backBtn.style.transform = 'scale(1.1)';
        });
        backBtn.addEventListener('mouseout', () => {
            backBtn.style.background = 'rgba(239, 68, 68, 0.1)';
            backBtn.style.transform = 'scale(1)';
        });
    }
    
    const uploadLabel = document.querySelector('label[for="inputArchivo"]');
    if(uploadLabel) {
        uploadLabel.addEventListener('mouseover', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 6px 20px rgba(180, 194, 77, 0.4)';
        });
        uploadLabel.addEventListener('mouseout', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 4px 16px rgba(180, 194, 77, 0.3)';
        });
    }
  });
  </script>

  <script src="scripts/menu.js"></script>

  <?php if (!empty($_SESSION['mensaje'])): ?>
    <div id="toast" class="toast <?= $_SESSION['mensaje_tipo'] ?? 'info' ?>">
      <?= htmlspecialchars($_SESSION['mensaje']) ?>
    </div>

    <script>
      const toast = document.getElementById('toast');
      if (toast) {
        setTimeout(() => toast.classList.add('show'), 100);
        setTimeout(() => toast.classList.remove('show'), 4000);
        setTimeout(() => toast.remove(), 4500);
      }
    </script>

    <?php unset($_SESSION['mensaje'], $_SESSION['mensaje_tipo']); ?>
  <?php endif; ?>

</body>
</html>