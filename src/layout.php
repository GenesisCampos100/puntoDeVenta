
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Punto de Venta</title>

  <!-- Tu CSS personalizado -->
  <link rel="stylesheet" href="styles/output.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

</head>
<body class="bg-gray-100">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
    }
  </style>

  <?php include __DIR__ . "/pages/menu.php"; ?>
  

  <main 
  id="content" 
  class="mt-16 pt-20 pl-0 pr-80 transition-all duration-300"
>
  <?php
    // Aquí se cargará el contenido de cada vista
    if (isset($contenido)) {
        include $contenido;
    }
  ?>
</main>

<!-- 🌙 MODAL PERFIL -->
<div id="userModal" 
     style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5);
            z-index:99999; justify-content:center; align-items:center;">
  <div style="background:#f9f9f9; border-radius:24px; width:90%; max-width:400px;
              padding:30px 25px; text-align:center; box-shadow:0 6px 25px rgba(0,0,0,0.25);
              position:relative;">
     <!-- ❌ Botón elegante para cerrar -->
    <button id="closeUserModal"
            style="position:absolute; top:15px; left:15px; background:none; border:none;
                   cursor:pointer; padding:6px; border-radius:50%; transition:all 0.25s ease;">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="26" height="26"
           fill="none" stroke="#ff4d6d" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <line x1="18" y1="6" x2="6" y2="18" />
        <line x1="6" y1="6" x2="18" y2="18" />
      </svg>
    </button>

    <script>
      const closeUserModal = document.getElementById('closeUserModal');
      closeUserModal.addEventListener('mouseover', () => {
        const xIcon = closeUserModal.querySelector('svg');
        xIcon.style.stroke = '#e60000'; // cambia a rojo intenso
        closeUserModal.style.background = '#ffe6ea'; // fondo rosado suave
      });
      closeUserModal.addEventListener('mouseout', () => {
        const xIcon = closeUserModal.querySelector('svg');
        xIcon.style.stroke = '#ff4d6d'; // vuelve al color base
        closeUserModal.style.background = 'none';
      });
      closeUserModal.addEventListener('click', () => {
        document.getElementById('userModal').style.display = 'none';
      });
    </script>

    <!-- Foto y botón cámara -->
<div style="position:relative; display:inline-block;">
  <!-- Imagen visible -->
  <img id="mainFotoPerfil" 
       src="<?= htmlspecialchars($_SESSION['foto_perfil'] ?? '../public/img/1.png') ?>" 
       alt="Usuario"
       style="width:90px; height:90px; border-radius:50%; object-fit:cover; cursor:pointer;">

  <!-- Botón para abrir input file -->
  <label for="fotoPerfilInput" 
         style="position:absolute; bottom:0; right:0; background:#FFFFFF; 
                border-radius:50%; width:28px; height:28px; display:flex; 
                align-items:center; justify-content:center; cursor:pointer;">
    <img src="../public/img/cambioUsuario.png" alt="">
  </label>

  <!-- Input file oculto -->
  <input type="file" id="fotoPerfilInput" name="foto" accept="image/*" style="display:none;">
</div>

    <!-- Nombre -->
    <h3 style="margin-top:12px; font-size:14 font-weight:100; color:#000;">
      <span style="color:#DC143C;">¡Hola!</span>
      <?= htmlspecialchars($_SESSION['nombre_completo'] ?? '') ?>
    </h3>
    <!-- Correo -->
    <p style="margin-top:4px; font-size:14px; color:#666;">
      <?= htmlspecialchars($_SESSION['correo'] ?? '') ?>
    </p>

    <!-- Caja blanca con opciones -->
    <div style="margin-top:20px; background:#fff; border-radius:16px; padding:15px 20px; box-shadow:0 2px 10px rgba(0,0,0,0.05);">
      <!-- Tema -->
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
        <span style="display:inline-flex; align-items:center; gap:6px;">
          <img src="../public/img/tema.png" alt="Tema" style="width:16px; height:16px;">
          Tema
        </span>
        <select id="temaSelect" style="border:none; background:#f1f1f1; border-radius:8px; padding:7px 12px;">
          <option value="claro">Claro</option>
          <option value="oscuro">Oscuro</option>
        </select>
      </div>

      <!-- IDIOMA -->
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
        <span style="display:inline-flex; align-items:center; gap:6px;">
          <img src="../public/img/idiomaIcon.png" alt="Tema" style="width:16px; height:16px;">
          Idioma
        </span>
        <select id="idimaSelect" style="border:none; background:#f1f1f1; border-radius:8px; padding:5px 10px;">
          <option value="claro">Español</option>
          <option value="oscuro">Inglés</option>
        </select>
      </div>


      <!-- Cerrar sesión -->
      <div id="logoutOption" style="display:flex; align-items:center; justify-content:space-between; cursor:pointer; color:#e63946; font-weight:600;">
        <span style="display:inline-flex; align-items:center; gap:6px;">
          <img src="../public/img/logout.png" alt="Cerrar sesión" style="width:16px; height:16px;">
          Cerrar sesión
        </span>
        <span></span>
      </div>

    <!-- Confirmación de logout -->
    <div id="confirmLogout" style="display:none; margin-top:20px; background:#0A2342; color:white; padding:15px; border-radius:12px;">
      <p>¿Seguro que deseas cerrar sesión?</p>
      <div style="display:flex; justify-content:center; gap:10px; margin-top:10px;">
        <button id="btnConfirmLogout" 
                style="background:#e63946; border:none; color:white; padding:8px 15px; border-radius:8px; cursor:pointer;">Sí</button>
        <button id="btnCancelLogout" 
                style="background:#475569; border:none; color:white; padding:8px 15px; border-radius:8px; cursor:pointer;">No</button>
      </div>
    </div>

    <!-- Enlaces -->
    <div style="margin-top:20px; font-size:12px;">
      <a href="../public/docs/privacidad.pdf" target="_blank" style="color:#555; text-decoration:none;">Política de Privacidad</a>
      <span style="margin:0 8px;">|</span>
      <a href="../public/docs/terminos.pdf" target="_blank" style="color:#555; text-decoration:none;">Términos del Servicio</a>
    </div>
  </div>
</div>

<!-- 🌸 MODAL CAMBIAR FOTO -->
<div id="changePhotoModal"
    style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5);
            z-index:100000; justify-content:center; align-items:center;">
  <div style="background:#f9f9f9; border-radius:24px; width:90%; max-width:420px;
              padding:35px 25px; text-align:center; box-shadow:0 6px 25px rgba(0,0,0,0.25);
              position:relative;">
    <!-- 🔙 Botón de regresar con ícono SVG -->
    <button id="btnVolver"
            style="position:absolute; top:15px; left:15px; background:none; border:none;
                  cursor:pointer; padding:6px; border-radius:50%; transition:all 0.25s ease;">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="26" height="26" 
          fill="none" stroke="#ff4d6d" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="15 18 9 12 15 6" />
      </svg>
    </button>          
    <h2 style="font-size:20px; margin-bottom:20px;">Agregar una foto de perfil</h2>

    <form id="formFoto" enctype="multipart/form-data" method="POST" action="../src/scripts/guardar_foto.php">
      <div style="background:#f0f8ff; border-radius:20px; padding:25px;">
        <div style="background:#ffb6c1; border-radius:50%; width:120px; height:120px;
                    display:flex; justify-content:center; align-items:center; margin:0 auto 15px;">
          <img id="previewFoto" src="../public/img/1.png"
               style="width:120px; height:120px; border-radius:50%; object-fit:cover;">
        </div>
        <p style="font-weight:500; margin-bottom:15px;">Seleccione una foto</p>
        <!-- contenido seleccion de imagen -->
        <div style="display:flex; justify-content:center; gap:10px;">
          <label for="inputArchivo"
                 style="background:#ffb6c1; color:#000; border:none; padding:5px 12px;
                        border-radius:20px; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
           <span style="display:inline-flex; align-items:center; gap:4px;">
          <img src="../public/img/icono_laptop.png" alt="computadora-icono" style="width:16px; height:16px;">
          Subir desde mi computadora
        </span>
          </label>
          <input type="file" id="inputArchivo" name="foto" accept="image/*" style="display:none;">
        </div>

        <button type="submit"
                style="margin-top:20px; background:#0A2342; color:#fff; border:none;
                       padding:5px 10px; border-radius:10px; cursor:pointer;">
          Guardar cambios
        </button>
      </div>
    </form>
  </div>
</div>
<script>
  // 🎨 Efecto hover y volver al modal anterior
  const btnVolver = document.getElementById('btnVolver');

  btnVolver.addEventListener('mouseover', () => {
    const arrow = btnVolver.querySelector('svg');
    arrow.style.stroke = '#e60000';      // cambia a rojo
    btnVolver.style.background = '#ffe6ea'; // fondo rosado suave
  });

  btnVolver.addEventListener('mouseout', () => {
    const arrow = btnVolver.querySelector('svg');
    arrow.style.stroke = '#ff4d6d';      // vuelve al color rosa
    btnVolver.style.background = 'none';
  });

  btnVolver.addEventListener('click', () => {
    document.getElementById('changePhotoModal').style.display = 'none';
    document.getElementById('userModal').style.display = 'flex'; // regresa al modal anterior
  });
</script>
<script>
document.addEventListener("DOMContentLoaded", () => {
  const userBlock = document.getElementById("userBlock");
  const userModal = document.getElementById("userModal");
  const logoutOption = document.getElementById("logoutOption");
  const confirmBox = document.getElementById("confirmLogout");
  const btnConfirm = document.getElementById("btnConfirmLogout");
  const btnCancel = document.getElementById("btnCancelLogout");
  const changePhotoModal = document.getElementById("changePhotoModal");
  const inputArchivo = document.getElementById("inputArchivo");
  const previewFoto = document.getElementById("previewFoto");
  const formFoto = document.getElementById("formFoto");

  const mainFotoPerfil = document.getElementById("mainFotoPerfil");
  const sidebarFoto = document.getElementById("sidebarFoto");

  // Abrir modal principal
  userBlock.addEventListener("click", () => {
    userModal.style.display = "flex";
    document.body.style.overflow = "hidden";
  });

   // Abrir confirmación de logout
  logoutOption.addEventListener("click", () => {
    confirmBox.style.display = "block";
  });

  // Confirmar logout
  btnConfirm.addEventListener("click", () => {
    window.location.href = "../src/pages/login.php";
  });

  // Cancelar logout
  btnCancel.addEventListener("click", () => {
    confirmBox.style.display = "none";
  });

  // Cerrar modal principal al hacer clic fuera
  userModal.addEventListener("click", (e) => {
    if (e.target === userModal) {
      userModal.style.display = "none";
      document.body.style.overflow = "auto";
    }
  });

  // Abrir modal de cambio de foto al hacer clic en la imagen
  mainFotoPerfil.addEventListener("click", () => {
    changePhotoModal.style.display = "flex";
  });

  // Previsualizar imagen seleccionada
  inputArchivo.addEventListener("change", (e) => {
    const file = e.target.files[0];
    if (file) previewFoto.src = URL.createObjectURL(file);
  });

  // Cerrar modal de cambio de foto al hacer clic fuera
  changePhotoModal.addEventListener("click", (e) => {
    if (e.target === changePhotoModal) {
      changePhotoModal.style.display = "none";
    }
  });

  // Subir foto al servidor
  formFoto.addEventListener("submit", (e) => {
    e.preventDefault();
    const formData = new FormData(formFoto);

    fetch("../src/scripts/guardar_foto.php", {
      method: "POST",
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        // Actualizar fotos en sidebar y modal principal
        mainFotoPerfil.src = data.newPhoto;
        sidebarFoto.src = data.newPhoto;

        // Cerrar modal de cambio de foto
        changePhotoModal.style.display = "none";
      } else {
        alert("Error al guardar la foto: " + data.error);
      }
    })
    .catch(err => {
      alert("Error en la conexión: " + err);
    });
  });
});
</script>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const fotoPerfil = document.querySelector("#userModal img"); // la imagen actual
  const changePhotoModal = document.getElementById("changePhotoModal");
  const inputArchivo = document.getElementById("inputArchivo");
  const previewFoto = document.getElementById("previewFoto");
  const formFoto = document.getElementById("formFoto");

  // 🖱️ Abrir modal al hacer clic en la imagen
  fotoPerfil.addEventListener("click", () => {
    changePhotoModal.style.display = "flex";
  });

  // 📷 Previsualizar imagen antes de subir
  inputArchivo.addEventListener("change", (e) => {
    const file = e.target.files[0];
    if (file) previewFoto.src = URL.createObjectURL(file);
  });

  // 🚪 Cerrar modal si haces clic fuera
  changePhotoModal.addEventListener("click", (e) => {
    if (e.target === changePhotoModal) {
      changePhotoModal.style.display = "none";
    }
  });

  // ✅ Al guardar, recargar imagen en el modal principal
  formFoto.addEventListener("submit", (e) => {
    e.preventDefault();
    const formData = new FormData(formFoto);
    fetch("../src/scripts/guardar_foto.php", {
      method: "POST",
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        fotoPerfil.src = data.newPhoto;
        changePhotoModal.style.display = "none";
      } else {
        alert("Error al guardar la foto: " + data.error);
      }
    });
  });
});
</script>

 
    <script src="../src/scripts/menu.js"></script>

    <?php if (!empty($_SESSION['mensaje'])): ?>
  <div id="toast" class="toast <?= $_SESSION['mensaje_tipo'] ?? 'info' ?>">
    <?= htmlspecialchars($_SESSION['mensaje']) ?>
  </div>

  <script>
    const toast = document.getElementById('toast');
    if (toast) {
      setTimeout(() => toast.classList.add('show'), 100); // Aparece con animación
      setTimeout(() => toast.classList.remove('show'), 4000); // Desaparece
      setTimeout(() => toast.remove(), 4500);
    }
  </script>

  <style>
    .toast {
      position: fixed;
      bottom: 30px;
      right: 30px;
      background: #333;
      color: white;
      padding: 14px 20px;
      border-radius: 10px;
      font-weight: 500;
      box-shadow: 0 4px 12px rgba(0,0,0,0.25);
      opacity: 0;
      transform: translateY(20px);
      transition: all 0.5s ease;
      z-index: 9999;
    }
    .toast.show {
      opacity: 1;
      transform: translateY(0);
    }
    .toast.success { background: #16a34a; } /* verde */
    .toast.error { background: #dc2626; }   /* rojo */
    .toast.info { background: #2563eb; }    /* azul */
  </style>

  <?php unset($_SESSION['mensaje'], $_SESSION['mensaje_tipo']); ?>
<?php endif; ?>
</body>
</html>