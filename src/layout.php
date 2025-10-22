
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
