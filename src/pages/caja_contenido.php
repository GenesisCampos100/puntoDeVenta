<?php
// src/pages/caja_contenido.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/translation.php';
?>

<!-- Dependencias (Mismas que productos) -->
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['Poppins', 'sans-serif'],
          },
        },
      },
    }
</script>

<style>
    :root {
      --primary: #b4c24d;
      --primary-dark: #9fb03d;
      --secondary: #2d4353;
      --accent: #e15871;
      --gray-bg: #eeeeee;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #f9fafb 0%, #eeeeee 100%);
    }

    .animate-fadeIn { animation: fadeIn 0.4s ease-out; }
    .animate-slideUp { animation: slideUp 0.5s cubic-bezier(0.4, 0, 0.2, 1); }
    .hover-lift { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    .hover-lift:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08); }

    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

    .modal-backdrop {
      backdrop-filter: blur(8px);
      background: rgba(0, 0, 0, 0.4);
    }

    .btn-primary {
      background: var(--primary);
      color: white;
    }
    
    .card-gradient {
        background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
    }
</style>

<div class="max-w-7xl mx-auto p-4 md:p-6 pb-32">

    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-6 animate-slideUp">
        <div>
            <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2"><?= __('main_cashier_title') ?></h1>
            <p class="text-gray-600 text-base"><?= __('cash_flow_management') ?></p>
        </div>
        
        <div class="flex items-center gap-2 px-4 py-2 bg-green-50 text-green-700 rounded-full border border-green-100 shadow-sm">
            <div class="w-2.5 h-2.5 rounded-full bg-green-500 animate-pulse"></div>
            <span class="text-sm font-semibold tracking-wide"><?= __('register_open') ?></span>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8 animate-slideUp" style="animation-delay: 0.1s;">
        
        <!-- Efectivo Card -->
        <div class="bg-white rounded-2xl p-6 shadow-lg hover-lift border border-gray-100 relative overflow-hidden group">
            <div class="flex items-center justify-between relative z-10">
                <div>
                    <p class="text-gray-500 text-sm font-medium mb-1 uppercase tracking-wider"><?= __('expected_cash') ?></p>
                    <h3 class="text-3xl font-bold text-gray-900 tracking-tight" id="stat-efectivo">$0.00</h3>
                    <p class="text-xs text-gray-400 mt-2 font-medium"><?= __('sales_income_withdrawals') ?></p>
                </div>
                <div class="w-14 h-14 rounded-xl flex items-center justify-center shadow-lg" style="background: linear-gradient(135deg, #b4c24d 0%, #9fb03d 100%);">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
            </div>
            <!-- Decorative Icon -->
            <div class="absolute -bottom-4 -right-4 opacity-5 transform rotate-12 group-hover:scale-110 transition-transform duration-500">
                <svg class="w-32 h-32 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
            </div>
        </div>

        <!-- Tarjeta Card -->
        <div class="bg-white rounded-2xl p-6 shadow-lg hover-lift border border-gray-100 relative overflow-hidden group">
            <div class="flex items-center justify-between relative z-10">
                <div>
                    <p class="text-gray-500 text-sm font-medium mb-1 uppercase tracking-wider"><?= __('expected_card') ?></p>
                    <h3 class="text-3xl font-bold text-gray-900 tracking-tight" id="stat-tarjeta">$0.00</h3>
                    <p class="text-xs text-gray-400 mt-2 font-medium"><?= __('card_payments_processed') ?></p>
                </div>
                <div class="w-14 h-14 rounded-xl flex items-center justify-center shadow-lg bg-gradient-to-br from-blue-500 to-indigo-600">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                </div>
            </div>
            <!-- Decorative Icon -->
            <div class="absolute -bottom-4 -right-4 opacity-5 transform rotate-12 group-hover:scale-110 transition-transform duration-500">
                <svg class="w-32 h-32 text-blue-900" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
            </div>
        </div>

        <!-- Actions Card -->
        <div class="rounded-2xl shadow-lg p-6 text-white flex flex-col justify-between relative overflow-hidden" style="background: linear-gradient(135deg, #2d4353 0%, #1e2d38 100%);">
            <div class="relative z-10">
                <h3 class="text-xl font-bold mb-1"><?= __('quick_actions') ?></h3>
                <p class="text-gray-300 text-sm mb-6"><?= __('manage_daily_movements') ?></p>
            </div>
            <div class="grid grid-cols-2 gap-3 relative z-10">
                <button onclick="abrirModalMovimiento('ingreso')" class="bg-white/10 hover:bg-white/20 border border-white/10 p-3 rounded-xl flex flex-col items-center justify-center gap-2 transition-all hover:-translate-y-0.5 backdrop-blur-sm">
                    <svg class="w-6 h-6 text-[#b4c24d]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12l4 4 4-4"/></svg>
                    <span class="text-xs font-bold"><?= __('cash_deposit') ?></span>
                </button>
                <button onclick="abrirModalMovimiento('retiro')" class="bg-white/10 hover:bg-white/20 border border-white/10 p-3 rounded-xl flex flex-col items-center justify-center gap-2 transition-all hover:-translate-y-0.5 backdrop-blur-sm">
                    <svg class="w-6 h-6 text-[#e15871]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16V8M8 12l4-4 4 4"/></svg>
                    <span class="text-xs font-bold"><?= __('cash_withdrawal') ?></span>
                </button>
                <button onclick="abrirModalCorte()" class="col-span-2 p-3 rounded-xl flex items-center justify-center gap-2 font-bold transition-all shadow-lg hover:shadow-xl hover:-translate-y-0.5 text-white" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><line x1="20" x2="8.12" y1="4" y2="15.88"/><line x1="14.47" x2="20" y1="14.48" y2="20"/><line x1="8.12" x2="12" y1="8.12" y2="12"/></svg>
                    <?= __('perform_cash_cut') ?>
                </button>
            </div>
            <!-- Decorative Background -->
            <div class="absolute top-0 right-0 p-4 opacity-10">
                <svg class="w-40 h-40 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
            </div>
        </div>
    </div>

    <!-- Info Banner -->
    <div class="bg-white border border-blue-100 rounded-2xl p-5 shadow-md flex items-start gap-4 animate-slideUp" style="animation-delay: 0.2s;">
        <div class="p-2 bg-blue-50 rounded-lg text-blue-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
        </div>
        <div>
            <h4 class="text-base font-bold text-gray-900"><?= __('shift_information') ?></h4>
            <p class="text-sm text-gray-600 mt-1 leading-relaxed">
                <?= __('last_cut_registered') ?>: <span id="last-cut-date" class="font-mono font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded"><?= __('loading_text') ?></span>. 
                <?= __('totals_after_cut') ?>
            </p>
        </div>
    </div>

</div>

<!-- Modal Movimiento (Ingreso/Retiro) -->
<div id="modalMovimiento" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm p-4 transition-opacity duration-300">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all scale-100 animate-fadeIn relative">
        <!-- Header con gradiente -->
        <div class="px-6 py-4 border-b flex items-center justify-between" style="background: linear-gradient(135deg, #2d4353 0%, #1e2d38 100%);">
            <h3 class="text-xl font-bold text-white flex items-center gap-2" id="modal-mov-title">
                <?= __('register_movement') ?>
            </h3>
            <button onclick="cerrarModal('modalMovimiento')" class="text-white/70 hover:text-white bg-white/10 p-2 rounded-full transition-all hover:bg-white/20">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>
        
        <form id="formMovimiento" class="p-6 space-y-5">
            <input type="hidden" id="mov-action" name="action">
            
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2"><?= __('movement_amount') ?></label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold">$</span>
                    <input type="number" step="0.01" id="mov-monto" name="monto" required 
                           class="w-full pl-8 pr-4 py-3.5 rounded-xl border-2 border-gray-200 focus:border-[#b4c24d] focus:outline-none transition-all font-bold text-lg text-gray-900 placeholder-gray-300" placeholder="0.00">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2"><?= __('movement_method') ?></label>
                <div class="relative">
                    <select id="mov-metodo" name="metodo" class="w-full px-4 py-3.5 rounded-xl border-2 border-gray-200 bg-white focus:border-[#b4c24d] focus:outline-none transition-all font-medium text-gray-700 appearance-none">
                        <option value="EFECTIVO"><?= __('cash_method') ?></option>
                        <option value="TARJETA"><?= __('card_method') ?></option>
                    </select>
                    <svg class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2"><?= __('reason_description') ?></label>
                <textarea id="mov-motivo" name="motivo" rows="2" required 
                          class="w-full px-4 py-3.5 rounded-xl border-2 border-gray-200 focus:border-[#b4c24d] focus:outline-none transition-all font-medium text-gray-700 placeholder-gray-300" placeholder="<?= __('reason_placeholder') ?>"></textarea>
            </div>

            <button type="submit" class="w-full py-4 rounded-xl font-bold shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all flex items-center justify-center gap-2 mt-4 text-white" style="background: linear-gradient(135deg, #2d4353 0%, #1e2d38 100%);">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                <?= __('save_movement') ?>
            </button>
        </form>
    </div>
</div>

<!-- Modal Corte de Caja -->
<div id="modalCorte" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm p-4 transition-opacity duration-300">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden transform transition-all scale-100 animate-fadeIn">
        <!-- Header con gradiente -->
        <div class="px-6 py-4 border-b flex items-center justify-between" style="background: linear-gradient(135deg, #2d4353 0%, #1e2d38 100%);">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><line x1="20" x2="8.12" y1="4" y2="15.88"/><line x1="14.47" x2="20" y1="14.48" y2="20"/><line x1="8.12" x2="12" y1="8.12" y2="12"/></svg>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-white"><?= __('cash_cut') ?></h3>
                    <p class="text-xs text-white/70 font-medium"><?= __('shift_close_count') ?></p>
                </div>
            </div>
            <button onclick="cerrarModal('modalCorte')" class="text-white/70 hover:text-white bg-white/10 p-2 rounded-full transition-all hover:bg-white/20">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>
        
        <form id="formCorte" class="p-6 space-y-6">
            <input type="hidden" name="action" value="corte_caja">
            
            <!-- Grid de Comparación -->
            <div class="grid grid-cols-2 gap-6">
                <!-- Esperado (Read Only) -->
                <div class="space-y-4">
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100 pb-2"><?= __('system_expected') ?></h4>
                    
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1"><?= __('cash_method') ?></label>
                        <input type="text" id="corte-efectivo-esp" name="efectivo_esperado" readonly 
                               class="w-full px-3 py-2.5 rounded-lg bg-gray-50 border-2 border-gray-100 text-gray-500 font-mono font-bold text-right cursor-not-allowed">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1"><?= __('card_method') ?></label>
                        <input type="text" id="corte-tarjeta-esp" name="tarjeta_esperado" readonly 
                               class="w-full px-3 py-2.5 rounded-lg bg-gray-50 border-2 border-gray-100 text-gray-500 font-mono font-bold text-right cursor-not-allowed">
                    </div>
                </div>

                <!-- Contado (Input) -->
                <div class="space-y-4">
                    <h4 class="text-xs font-bold text-[#b4c24d] uppercase tracking-wider border-b border-[#b4c24d]/20 pb-2"><?= __('real_counted') ?></h4>
                    
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1"><?= __('cash_method') ?></label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-xs">$</span>
                            <input type="number" step="0.01" id="corte-efectivo-real" name="efectivo_contado" required 
                                   class="w-full pl-6 pr-3 py-2.5 rounded-lg border-2 border-gray-200 focus:border-[#b4c24d] focus:outline-none font-mono font-bold text-right text-gray-900 bg-white" placeholder="0.00">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1"><?= __('card_method') ?></label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-xs">$</span>
                            <input type="number" step="0.01" id="corte-tarjeta-real" name="tarjeta_contado" required 
                                   class="w-full pl-6 pr-3 py-2.5 rounded-lg border-2 border-gray-200 focus:border-[#b4c24d] focus:outline-none font-mono font-bold text-right text-gray-900 bg-white" placeholder="0.00">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Diferencia Live -->
            <div class="bg-gray-50 rounded-xl p-4 border border-gray-200 flex justify-between items-center shadow-inner">
                <span class="text-sm font-bold text-gray-600"><?= __('calculated_difference') ?>:</span>
                <span id="corte-diferencia" class="text-xl font-bold text-gray-400">$0.00</span>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2"><?= __('comments_optional') ?></label>
                <textarea name="comentarios" rows="2" 
                          class="w-full px-4 py-3.5 rounded-xl border-2 border-gray-200 focus:border-[#b4c24d] focus:outline-none transition-all font-medium text-gray-700 placeholder-gray-300" placeholder="<?= __('observations_placeholder') ?>"></textarea>
            </div>

            <button type="submit" class="w-full py-4 rounded-xl font-bold shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all flex items-center justify-center gap-2 text-white" style="background: linear-gradient(135deg, #2d4353 0%, #1e2d38 100%);">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <?= __('finalize_cut') ?>
            </button>
        </form>
    </div>
</div>

<script>
    window.TR = {
        monto_invalido_title: <?= json_encode(__('amount_received')) ?>,
        monto_invalido_text: <?= json_encode(__('amount_too_low')) ?>,
        motivo_requerido_title: <?= json_encode(__('reason_description')) ?>,
        motivo_requerido_text: <?= json_encode(__('field_required')) ?>,
        error_conexion_title: 'Error de Conexión',
        error_conexion_text: 'No se pudo conectar con el servidor.',
        ingreso_registrado: <?= json_encode(__('register_income_title')) ?>,
        retiro_registrado: <?= json_encode(__('register_withdrawal_title')) ?>,
        movimiento_guardado: <?= json_encode(__('save_movement')) ?>,
        confirmar_corte: <?= json_encode(__('confirm_cash_cut_title')) ?>,
        efectivo_esperado: <?= json_encode(__('cash_method')) ?>,
        tarjeta_esperado: <?= json_encode(__('card_method')) ?>,
        diferencia_label: <?= json_encode(__('calculated_difference')) ?>,
        corte_finalizado: <?= json_encode(__('finalize_cut')) ?>,
        yes_finalize_cut: <?= json_encode(__('yes_finalize_cut')) ?>,
        cancel_btn: <?= json_encode(__('cancel_btn')) ?>
    };
</script>
<script src="/PrismaMK2C/src/js/caja.js?v=<?= time() ?>"></script>