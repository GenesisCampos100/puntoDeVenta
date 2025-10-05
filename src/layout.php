
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Punto de Venta</title>

  <!-- Tu CSS personalizado -->
  <link href="src/styles/output.css" rel="stylesheet">

</head>
<body class="bg-gray-100">

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
</body>
</html>
