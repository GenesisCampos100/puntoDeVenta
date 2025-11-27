<!-- MODAL CLIENTES -->
<div id="modalClientes" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white w-full max-w-4xl rounded-2xl shadow-2xl p-6 m-4 animate-slide">
        <div class="flex justify-between items-center mb-5">
            <h2 class="text-2xl font-bold" style="color: var(--secondary);"><?= __('select_customer') ?></h2>
            <button id="cerrar-modal-cliente" class="text-gray-400 hover:text-gray-600 text-3xl font-bold">&times;</button>
        </div>
        <input type="text" id="buscarCliente" class="w-full border-2 px-4 py-3 rounded-xl mb-4 focus:border-primary focus:outline-none" placeholder="<?= __('search_customer_placeholder') ?>">
        <div class="overflow-y-auto max-h-96">
            <table class="w-full text-left border-collapse">
                <thead class="sticky top-0" style="background: var(--bg-gray);">
                    <tr>
                        <th class="p-3 border-b-2 font-semibold"><?= __('id_col') ?></th>
                        <th class="p-3 border-b-2 font-semibold"><?= __('customer_col') ?></th>
                        <th class="p-3 border-b-2 font-semibold"><?= __('phone_col') ?></th>
                        <th class="p-3 border-b-2 font-semibold"><?= __('action_col') ?></th>
                    </tr>
                </thead>
                <tbody id="tablaClientes">
                    <?php
                        $sql = "SELECT * FROM clientes ORDER BY nombre ASC";
                        $stmt = $pdo->query($sql);
                        while ($cli = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $full = htmlspecialchars($cli['nombre'].' '.$cli['apellido_paterno'].' '.$cli['apellido_materno']);
                            echo "<tr class='border-b hover:bg-gray-50 transition-colors'>
                                <td class='p-3'>{$cli['id_cliente']}</td>
                                <td class='p-3 font-medium'>{$full}</td>
                                <td class='p-3'>".htmlspecialchars($cli['celular'])."</td>
                                <td class='p-3'>
                                    <button class='seleccionarCliente px-4 py-2 text-white rounded-lg font-medium transition-all hover:shadow-md' style='background: var(--primary);' data-id='{$cli['id_cliente']}' data-nombre='{$full}'><?= __('select_btn') ?></button>
                                </td>
                            </tr>";
                        }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<!-- MODAL PAGO - ESTILO POS -->
<div id="payment-modal"
    class="fixed inset-0 bg-black/40 hidden flex items-center justify-center z-50">

    <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm p-5 animate-slide
                border border-gray-200">

        <!-- TÍTULO -->
        <h2 class="text-xl font-bold mb-4 text-center text-secondary tracking-wide">
            <?= __('select_payment_method') ?>
        </h2>

        <form id="payment-form" class="space-y-5">

            <!-- HIDDEN INPUTS -->
            <input type="hidden" name="cart_data" id="cart-data-input">
            <input type="hidden" id="cliente-input" name="id_cliente">
            <input type="hidden" name="descuento_general" id="descuento-general-input">
            <input type="hidden" name="tipo_descuento_general" id="descuento-general-type">
            <input type="hidden" name="subtotal" id="subtotal-input">
            <input type="hidden" name="total" id="total-input">

            <!-- MÉTODOS DE PAGO POS -->
            <div class="grid grid-cols-3 gap-2">

                <label class="flex flex-col items-center justify-center p-3 border rounded-lg
                               cursor-pointer hover:bg-gray-50 transition text-center">
                    <input type="radio" id="metodo-efectivo" name="metodo" value="efectivo"
                           class="payment-method hidden" checked>
                    <span class="text-2xl">💵</span>
                    <span class="text-xs font-semibold mt-1"><?= __('cash') ?></span>
                </label>

                <label class="flex flex-col items-center justify-center p-3 border rounded-lg
                               cursor-pointer hover:bg-gray-50 transition text-center">
                    <input type="radio" id="metodo-tarjeta" name="metodo" value="tarjeta"
                           class="payment-method hidden">
                    <span class="text-2xl">💳</span>
                    <span class="text-xs font-semibold mt-1"><?= __('card') ?></span>
                </label>

                <label class="flex flex-col items-center justify-center p-3 border rounded-lg
                               cursor-pointer hover:bg-gray-50 transition text-center">
                    <input type="radio" id="metodo-mixto" name="metodo" value="mixto"
                           class="payment-method hidden">
                    <span class="text-2xl">💵💳</span>
                    <span class="text-xs font-semibold mt-1"><?= __('mixed') ?></span>
                </label>

            </div>

            <!-- SECCIÓN: EFECTIVO -->
            <div id="efectivo-section" class="space-y-1">

                <label class="text-sm font-semibold"><?= __('amount_received') ?></label>

                <input type="number" step="0.01" id="monto-efectivo" name="monto_efectivo"
                    class="w-full text-lg border rounded-lg p-2.5 text-center font-semibold
                           tracking-wide focus:border-primary"
                    placeholder="0.00">

                <p id="alerta-efectivo" class="text-red-600 text-xs font-semibold hidden">
                    <?= __('amount_too_low') ?>
                </p>

                <p class="text-sm font-semibold">
                    <?= __('change_label') ?>: <span id="cambio-efectivo" class="text-green-600">0.00</span>
                </p>

            </div>

            <!-- SECCIÓN: TARJETA -->
            <div id="tarjeta-section" class="space-y-2 hidden">

                <label class="text-sm font-semibold"><?= __('reference') ?></label>

                <input type="text" id="referencia-tarjeta" name="referencia_tarjeta"
                    class="w-full border rounded-lg p-2.5 text-center font-medium
                           focus:border-primary"
                    placeholder="<?= __('folio_reference') ?>">

            </div>

            <!-- SECCIÓN: MIXTO -->
            <div id="mixto-section" class="space-y-2 hidden">

                <div>
                    <label class="text-sm font-semibold"><?= __('cash_label') ?></label>
                    <input type="number" step="0.01" id="mixto-efectivo" name="mixto_efectivo"
                        class="w-full border rounded-lg p-2.5 text-center font-semibold
                               focus:border-primary"
                        placeholder="0.00">
                </div>

                <div>
                    <label class="text-sm font-semibold"><?= __('card_label') ?></label>
                    <input type="number" step="0.01" id="mixto-tarjeta" name="mixto_tarjeta"
                        class="w-full border rounded-lg p-2.5 text-center font-semibold
                               focus:border-primary"
                        placeholder="0.00">
                </div>

                <div>
                    <label class="text-sm font-semibold"><?= __('card_reference_label') ?></label>
                    <input type="text" id="mixto-referencia" name="mixto_referencia"
                        class="w-full border rounded-lg p-2.5 text-center font-medium
                               focus:border-primary"
                        placeholder="Folio / Referencia">
                </div>

                <p id="alerta-mixto" class="text-red-600 text-xs font-semibold hidden">
                    <?= __('missing_label') ?>: $0.00
                </p>

                <p class="text-sm font-semibold">
                    <?= __('change_label') ?>: <span id="cambio-mixto" class="text-green-600">0.00</span>
                </p>

            </div>

            <!-- BOTONES POS -->
            <div class="flex justify-between gap-3 pt-2">

                <button type="button" id="cancel-payment"
                    class="w-1/2 py-3 bg-gray-200 rounded-lg font-bold text-sm hover:bg-gray-300">
                    <?= __('cancel') ?>
                </button>

                <button type="submit" id="confirm-payment"
                    class="w-1/2 py-3 text-white rounded-lg font-bold text-sm shadow-md hover:shadow-lg"
                    style="background: linear-gradient(135deg,var(--primary),var(--primary-dark));">
                    <?= __('confirm') ?>
                </button>

            </div>

        </form>
    </div>
</div>




<!-- MODAL DESCUENTO GENERAL -->
<div id="discount-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl p-8 w-96 animate-slide">
        <h2 class="text-xl font-bold mb-5" style="color: var(--secondary);"><?= __('general_discount') ?></h2>
        <div class="flex gap-3 mb-5">
            <select id="discount-type" class="border-2 rounded-xl p-3 w-1/3 text-center font-semibold focus:border-primary focus:outline-none">
                <option value="percent">%</option>
                <option value="amount">$</option>
            </select>
            <input type="number" id="discount-input" class="border-2 rounded-xl p-3 w-2/3 focus:border-primary focus:outline-none" placeholder="<?= __('value') ?>">
        </div>
        <div class="flex justify-end gap-3">
            <button id="close-discount" class="px-5 py-2.5 bg-gray-200 rounded-xl font-semibold hover:bg-gray-300 transition-all"><?= __('cancel') ?></button>
            <button id="apply-discount" class="px-5 py-2.5 text-white rounded-xl font-semibold transition-all hover:shadow-lg" style="background: var(--primary);"><?= __('apply') ?></button>
        </div>
    </div>
</div>

<!-- MODAL DESCUENTO POR PRODUCTO -->
<div id="product-discount-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl p-8 w-96 animate-slide">
        <h2 class="text-xl font-bold mb-5" style="color: var(--secondary);"><?= __('product_discount') ?></h2>
        <div class="flex gap-3 mb-5">
            <select id="product-discount-type" class="border-2 rounded-xl p-3 w-1/3 text-center font-semibold focus:border-primary focus:outline-none">
                <option value="percent">%</option>
                <option value="amount">$</option>
            </select>
            <input type="number" id="product-discount-input" class="border-2 rounded-xl p-3 w-2/3 focus:border-primary focus:outline-none" placeholder="<?= __('value') ?>">
        </div>
        <div class="flex justify-end gap-3">
            <button id="product-discount-close" class="px-5 py-2.5 bg-gray-200 rounded-xl font-semibold hover:bg-gray-300 transition-all"><?= __('cancel') ?></button>
            <button id="product-discount-apply" class="px-5 py-2.5 text-white rounded-xl font-semibold transition-all hover:shadow-lg" style="background: var(--primary);"><?= __('apply') ?></button>
        </div>
    </div>
</div>

<!-- MODAL TICKET -->
<div id="ticket-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-start justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl p-6 w-auto max-w-[95%] md:max-w-md animate-slide overflow-hidden mt-12">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold" style="color: var(--secondary);"><?= __('sale_ticket') ?></h2>
            <button id="close-ticket-modal" class="text-gray-400 hover:text-gray-600 text-3xl font-bold">&times;</button>
        </div>

        <div class="max-h-[60vh] mb-4 flex items-start justify-center">
            <div class="overflow-auto pr-2" style="max-height:60vh; width: fit-content;">
                <div class="border p-1 bg-gray-50" style="width:85mm;max-width:100%;">
                    <iframe id="ticket-iframe" src="" frameborder="0" style="width:100%;height:60vh;background:white;display:block;margin:0"></iframe>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <button id="cancel-ticket" class="px-5 py-2.5 bg-gray-200 rounded-xl font-semibold hover:bg-gray-300 transition-all"><?= __('cancel') ?></button>
            <button id="print-ticket" class="px-5 py-2.5 text-white rounded-xl font-semibold transition-all hover:shadow-lg" style="background: var(--primary);"><?= __('print') ?></button>
        </div>
    </div>
</div>

<!-- Modal de búsqueda de productos -->
<div id="modalProductos" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white w-full max-w-5xl rounded-2xl shadow-2xl p-6 m-4 animate-slide">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-bold text-primary"><?= __('search_product_title') ?></h2>
            <button id="cerrar-modal-producto" class="text-gray-400 hover:text-gray-600 text-3xl font-bold">&times;</button>
        </div>
        <input type="text" id="buscarProductoModal" class="w-full border-2 px-4 py-3 rounded-xl mb-4 focus:border-primary focus:outline-none" placeholder="<?= __('search_product_placeholder') ?>">
        <div class="overflow-y-auto max-h-96">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-100 sticky top-0">
                    <tr>
                        <th class="p-3 border-b-2 font-semibold"><?= __('code_col') ?></th>
                        <th class="p-3 border-b-2 font-semibold"><?= __('product_col') ?></th>
                        <th class="p-3 border-b-2 font-semibold"><?= __('price_col') ?></th>
                        <th class="p-3 border-b-2 font-semibold"><?= __('stock_col') ?></th>
                        <th class="p-3 border-b-2 font-semibold"><?= __('action_col') ?></th>
                    </tr>
                </thead>
                <tbody id="tablaProductosModal"></tbody>
            </table>
        </div>
    </div>
</div>
