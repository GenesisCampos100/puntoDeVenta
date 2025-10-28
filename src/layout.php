
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

    <!-- Foto y botón cámara -->
    <div style="position:relative; display:inline-block;">
      <img src="../public/img/1.png" alt="Usuario" 
           style="width:90px; height:90px; border-radius:50%; object-fit:cover; border:3px solid #0A2342;">
      <label for="fotoPerfil" 
             style="position:absolute; bottom:0; right:0; background:#FFF8DC; 
                    border-radius:50%; width:28px; height:28px; display:flex; 
                    align-items:center; justify-content:center; cursor:pointer;">
        📷
      </label>
      <input type="file" id="fotoPerfil" accept="image/*" style="display:none;">
    </div>

    <!-- Nombre -->
    <h3 style="margin-top:12px; font-weight:700; color:#000;">
      <span style="color:#DC143C;">¡Hola!</span>
      <?= htmlspecialchars($_SESSION['nombre_completo'] ?? '') ?>
    </h3>

    <!-- Caja blanca con opciones -->
    <div style="margin-top:20px; background:#fff; border-radius:16px; padding:15px 20px; box-shadow:0 2px 10px rgba(0,0,0,0.05);">
      <!-- Tema -->
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
        <span style="display:inline-flex; align-items:center; gap:6px;">
          <img src="../public/img/tema.png" alt="Tema" style="width:16px; height:16px;">
          Tema
        </span>
        <select id="temaSelect" style="border:none; background:#f1f1f1; border-radius:8px; padding:5px 10px;">
          <option value="claro">Claro</option>
          <option value="oscuro">Oscuro</option>
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

<script>
document.addEventListener("DOMContentLoaded", () => {
  const userBlock = document.getElementById("userBlock");
  const modal = document.getElementById("userModal");
  const logoutOption = document.getElementById("logoutOption");
  const confirmBox = document.getElementById("confirmLogout");
  const btnConfirm = document.getElementById("btnConfirmLogout");
  const btnCancel = document.getElementById("btnCancelLogout");
  const temaSelect = document.getElementById("temaSelect");
  const fotoInput = document.getElementById("fotoPerfil");

  // Abrir modal
  userBlock.addEventListener("click", () => {
    modal.style.display = "flex";
    document.body.style.overflow = "hidden";
  });

  // Cerrar modal al hacer clic fuera
  modal.addEventListener("click", (e) => {
    if (e.target === modal) {
      modal.style.display = "none";
      document.body.style.overflow = "auto";
    }
  });

  // Cambiar foto (previsualizar)
  fotoInput.addEventListener("change", (e) => {
    const file = e.target.files[0];
    if (file) {
      const img = modal.querySelector("img");
      img.src = URL.createObjectURL(file);
    }
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

  // Tema (guardado en localStorage)
  temaSelect.addEventListener("change", () => {
    const tema = temaSelect.value;
    localStorage.setItem("tema", tema);
    document.documentElement.dataset.tema = tema;
  });

  // Aplicar tema guardado
  const temaGuardado = localStorage.getItem("tema");
  if (temaGuardado) {
    temaSelect.value = temaGuardado;
    document.documentElement.dataset.tema = temaGuardado;
  }
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