<?php
require_once __DIR__ . '/../config/translation.php';
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'es' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('cash_register_title') ?></title>
    <link rel="stylesheet" href="./styles/caja.css">
    <link rel="stylesheet" href="./styles/modo-oscuro.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
    body { 
            font-family: 'Poppins', sans-serif; 
            padding-left: 120px; /* Compensa el ancho del sidebar */
        }
  </style>
</head>
<body>
  <main class="contenedor">
    <h2 class="titulo"><?= __('main_cash_register') ?></h2>
    <p class="subtitulo"><?= __('cash_register_subtitle') ?></p>

    <div class="acciones">
      <button class="btn ingreso">
        <span style="color:#16a34a;">⬆</span> <?= __('cash_in') ?>
      </button>

      <button class="btn retiro">
        <span style="color:#dc2626;">⬇</span> <?= __('cash_out') ?>
      </button>

      <button class="btn corte">
        💵 <?= __('close_cash_register') ?>
      </button>
    </div>


    <section class="caja-contenedor">
      <div class="caja">
        <table>
          <tr><td><?= __('cash') ?></td><td>$1,250</td></tr>
          <tr><td><?= __('credit_card') ?></td><td>$1,250</td></tr>
          <tr><td><?= __('debit_card') ?></td><td>$1,250</td></tr>
          <tr class="total"><td><?= __('total') ?>:</td><td>$3,750</td></tr>
        </table>
      </div>

      <div class="efectivo">
       <h3><?= __('cash') ?></h3>
      <div class="fila">
        <p><?= __('income') ?></p>
        <h4>$0.00</h4>
       </div>
      </div>

    </section>
  </main>

  <!-- Modal Ingreso -->
  <div class="modal" id="modalIngreso">
    <div class="modal-content">
      <div class="modal-header">
        <h3><?= __('modal_cash_in_title') ?></h3>
        <span class="close">&times;</span>
      </div>
      <div class="modal-body">
        <label style="color:#696969;"><?= __('amount') ?></label>
        <input style="color:#696969;" type="number" placeholder="$ 0.00">

        <label style="color:#696969;"><?= __('reason_optional') ?></label>
        <input style="color:#696969;" type="text" placeholder="<?= __('reason_placeholder_in') ?>">

        <p><b><?= __('performed_by') ?>: </b></p>
        <p style="margin-top:4px; font-size:14px; color:#666;">
        <?= htmlspecialchars($_SESSION['nombre_completo'] ?? '') ?>
        </p>
      </div>
      <div class="modal-footer">
        <button class="cancelar"><?= __('cancel') ?></button>
        <button class="confirmar"><?= __('confirm') ?></button>
      </div>
    </div>
  </div>

  <!-- Modal Retiro -->
  <div class="modal" id="modalRetiro">
    <div class="modal-content">
      <div class="modal-header">
        <h3><?= __('modal_cash_out_title') ?></h3>
        <span class="close">&times;</span>
      </div>
      <div class="modal-body">
        <label style="color:#696969;"><?= __('amount') ?></label>
        <input style="color:#696969;" type="number" placeholder="$ 0.00">

        <label style="color:#696969;"><?= __('reason_optional') ?></label>
        <input style="color:#696969;" type="text" placeholder="<?= __('reason_placeholder_out') ?>">

        <p><b><?= __('performed_by') ?>: </b></p>
        <p style="margin-top:4px; font-size:14px; color:#666;">
        <?= htmlspecialchars($_SESSION['nombre_completo'] ?? '') ?>
        </p>
      </div>
      <div class="modal-footer">
        <button class="cancelar"><?= __('cancel') ?></button>
        <button class="confirmar"><?= __('confirm') ?></button>
      </div>
    </div>
  </div>


 <!-- Modal Corte de Caja -->
<div class="modal" id="modalCorte">
  <div class="modal-content modal-corte">
    <div class="modal-header">
      <h3><?= __('close_cash_register_title') ?></h3>
      <span class="close">&times;</span>
    </div>

    <div class="modal-body">
      <p style="color:#666; margin-bottom:16px;">
        <?= __('close_cash_register_desc') ?>
      </p>

      <table class="tabla-corte">
        <thead>
          <tr>
            <th><?= __('payment_method') ?></th>
            <th><?= __('manual_count') ?></th>
            <th><?= __('expected_total') ?></th>
            <th><?= __('difference') ?></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>💵 <?= __('cash') ?></td>
            <td><input type="number" placeholder="$0.00"></td>
            <td>$1,250.00</td>
            <td>$0.00</td>
          </tr>
          <tr>
            <td>💳 <?= __('credit_card') ?></td>
            <td><input type="number" placeholder="$0.00"></td>
            <td>$800.00</td>
            <td>$0.00</td>
          </tr>
          <tr>
            <td>🏧 <?= __('debit_card') ?></td>
            <td><input type="number" placeholder="$0.00"></td>
            <td>$1,200.00</td>
            <td>$0.00</td>
          </tr>
          <tr>
            <td>💸 <?= __('transfer') ?></td>
            <td><input type="number" placeholder="$0.00"></td>
            <td>$500.00</td>
            <td>$0.00</td>
          </tr>
          <tr class="total">
            <td><strong><?= __('total') ?></strong></td>
            <td>$0.00</td>
            <td><strong>$3,750.00</strong></td>
            <td>$0.00</td>
          </tr>
        </tbody>
      </table>

      <label for="comentarios" style="color:#555; margin-top:16px; display:block;">
        <?= __('comments_optional') ?>
      </label>
      <textarea id="comentarios" placeholder="<?= __('comments_placeholder') ?>"></textarea>
    </div>

    <div class="modal-footer">
      <button class="cancelar"><?= __('cancel') ?></button>
      <button class="confirmar"><?= __('continue') ?></button>
    </div>
  </div>
</div>


<script src="scripts/modal-cajaIR.js"></script>

</body>
</html>
