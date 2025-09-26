
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Punto de Venta</title>

  <!-- Tu CSS personalizado -->
  <link rel="stylesheet" href="styles/output.css">

</head>
<body class="bg-gray-100">

  <?php include __DIR__ . "/pages/menu.php"; ?>

  <main class="main-content p-6">
    <?php
      // Aquí se cargará el contenido de cada vista
      if (isset($contenido)) {
          include $contenido;
      }
    ?>
  </main>
    <script src="scripts/menu.js"></script>
</body>
</html>
