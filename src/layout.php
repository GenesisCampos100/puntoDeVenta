
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

  <main id="content" class="main-content p-6">
  <?php
    // Aquí se cargará el contenido de cada vista
    if (isset($contenido)) {
        include $contenido;
    }
  ?>
</main id="content" class="pt-20 pl-0 pr-80 transition-all duration-300">
 
    <script src="../src/scripts/menu.js"></script>
</body>
</html>
