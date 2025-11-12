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
            padding-left: 120px; /* Compensa el ancho del sidebar */
        }
  </style>
</head>
<body>
  <main class="contenedor">
    <h2 class="titulo">CAJA PRINCIPAL</h2>
    <p class="subtitulo">Registra ingresos y egresos en efectivo de tu caja</p>

    <div class="acciones">
      <button class="btn ingreso">
        <span style="color:#16a34a;">⬆</span> Ingreso Efectivo
      </button>

      <button class="btn retiro">
        <span style="color:#dc2626;">⬇</span> Retiro Efectivo
      </button>

      <button class="btn corte">
        💵 Hacer Corte de Caja
      </button>
    </div>


    <section class="caja-contenedor">
      <div class="caja">
        <table>
          <tr><td>Efectivo</td><td>$1,250</td></tr>
          <tr><td>Tarjeta de Crédito</td><td>$1,250</td></tr>
          <tr><td>Tarjeta de Débito</td><td>$1,250</td></tr>
          <tr class="total"><td>Total:</td><td>$3,750</td></tr>
        </table>
      </div>

      <div class="efectivo">
       <h3>Efectivo</h3>
      <div class="fila">
        <p>Ingreso</p>
        <h4>$0.00</h4>
       </div>
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


 <!-- Modal Corte de Caja -->
<div class="modal" id="modalCorte">
  <div class="modal-content modal-corte">
    <div class="modal-header">
      <h3>Corte de Caja</h3>
      <span class="close">&times;</span>
    </div>

    <div class="modal-body">
      <p style="color:#666; margin-bottom:16px;">
        Haz un recuento manual del efectivo en tu caja y otros métodos de pago
        y compáralos con el valor registrado.
      </p>

      <table class="tabla-corte">
        <thead>
          <tr>
            <th>Método</th>
            <th>Recuento Manual</th>
            <th>Total Esperado</th>
            <th>Diferencia</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>💵 Efectivo</td>
            <td><input type="number" placeholder="$0.00"></td>
            <td>$1,250.00</td>
            <td>$0.00</td>
          </tr>
          <tr>
            <td>💳 Tarjeta de Crédito</td>
            <td><input type="number" placeholder="$0.00"></td>
            <td>$800.00</td>
            <td>$0.00</td>
          </tr>
          <tr>
            <td>🏧 Tarjeta de Débito</td>
            <td><input type="number" placeholder="$0.00"></td>
            <td>$1,200.00</td>
            <td>$0.00</td>
          </tr>
          <tr>
            <td>💸 Transferencia</td>
            <td><input type="number" placeholder="$0.00"></td>
            <td>$500.00</td>
            <td>$0.00</td>
          </tr>
          <tr class="total">
            <td><strong>Total</strong></td>
            <td>$0.00</td>
            <td><strong>$3,750.00</strong></td>
            <td>$0.00</td>
          </tr>
        </tbody>
      </table>

      <label for="comentarios" style="color:#555; margin-top:16px; display:block;">
        Comentarios (opcional)
      </label>
      <textarea id="comentarios" placeholder="Registra comentarios de este corte"></textarea>
    </div>

    <div class="modal-footer">
      <button class="cancelar">Cancelar</button>
      <button class="confirmar">Continuar</button>
    </div>
  </div>
</div>


<script src="scripts/modal-cajaIR.js"></script>

</body>
</html>