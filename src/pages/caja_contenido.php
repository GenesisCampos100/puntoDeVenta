<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caja Principal</title>
    <link rel="stylesheet" href="./styles/caja.css">
    <link rel="stylesheet" href="./styles/modo-oscuro.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
    body { 
            font-family: 'Poppins', sans-serif; 
            padding-left: 250px; /* Compensa el ancho del sidebar */
        }
  </style>
</head>
<body>
  <main class="contenedor">
    <h2 class="titulo">CAJA PRINCIPAL</h2>
    <p class="subtitulo">Registra ingresos y egresos en efectivo de tu caja</p>

    <div class="acciones">
      <button class="btn ingreso">⬆ Ingreso Efectivo</button>
      <button class="btn retiro">⬇ Retiro Efectivo</button>
      <button class="btn corte">💵 Hacer Corte de Caja</button>
    </div>

    <section class="caja-contenedor">
      <div class="caja">
        <h3>CAJA</h3>
        <table>
          <tr><td>Efectivo</td><td>$1,250</td></tr>
          <tr><td>Tarjeta de Crédito</td><td>$1,250</td></tr>
          <tr><td>Tarjeta de Débito</td><td>$1,250</td></tr>
          <tr class="total"><td>Total:</td><td>$3,750</td></tr>
        </table>
      </div>

      <div class="efectivo">
        <h3>Efectivo</h3>
        <p>Al inicio</p>
        <h4>$0.00</h4>
      </div>
    </section>
  </main>

  <!-- Modal Ingreso -->
  <div class="modal" id="modalIngreso">
    <div class="modal-content">
      <div class="modal-header">
        <h3>Ingresar Efectivo a Caja</h3>
        <span class="close">&times;</span>
      </div>
      <div class="modal-body">
        <label style="color:#696969;">Monto</label>
        <input style="color:#696969;" type="number" placeholder="$ 0.00">

        <label style="color:#696969;">Motivo (opcional)</label>
        <input style="color:#696969;" type="text" placeholder="Ej: Reposición de billetes">

        <p><b>Realizado por: </b></p>
        <p style="margin-top:4px; font-size:14px; color:#666;">
        <?= htmlspecialchars($_SESSION['nombre_completo'] ?? '') ?>
        </p>
      </div>
      <div class="modal-footer">
        <button class="cancelar">Cancelar</button>
        <button class="confirmar">Confirmar</button>
      </div>
    </div>
  </div>

  <!-- Modal Retiro -->
  <div class="modal" id="modalRetiro">
    <div class="modal-content">
      <div class="modal-header">
        <h3>Retirar Efectivo de Caja</h3>
        <span class="close">&times;</span>
      </div>
      <div class="modal-body">
        <label style="color:#696969;">Monto</label>
        <input style="color:#696969;" type="number" placeholder="$ 0.00">

        <label style="color:#696969;">Motivo (opcional)</label>
        <input style="color:#696969;" type="text" placeholder="Ej: Pago de proveedor">

        <p><b>Realizado por: </b></p>
        <p style="margin-top:4px; font-size:14px; color:#666;">
        <?= htmlspecialchars($_SESSION['nombre_completo'] ?? '') ?>
        </p>
      </div>
      <div class="modal-footer">
        <button class="cancelar">Cancelar</button>
        <button class="confirmar">Confirmar</button>
      </div>
    </div>
  </div>

  <script src="scripts/modal-cajaIR.js"></script>
</body>
</html>