// src/js/caja.js – Gestión de Caja Principal
let expectedTotals = { efectivo: 0, tarjeta: 0 };

// Inicialización
document.addEventListener('DOMContentLoaded', () => {
    cargarTotales();
    configurarEventos();
});

// -------------------------------------------------
// Cargar totales desde API
// -------------------------------------------------
async function cargarTotales() {
    try {
        const formData = new FormData();
        formData.append('action', 'fetch_totales');

        // Ruta relativa desde src/index.php
        const response = await fetch('api/caja_controller.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.status === 'success') {
            expectedTotals.efectivo = parseFloat(data.efectivo_esperado) || 0;
            expectedTotals.tarjeta = parseFloat(data.tarjeta_esperado) || 0;

            actualizarStatsUI();

            const lastCutEl = document.getElementById('last-cut-date');
            if (lastCutEl) {
                lastCutEl.textContent = data.ultima_fecha_corte
                    ? formatearFecha(data.ultima_fecha_corte)
                    : i18n.no_previous_cuts;
            }
        } else {
            console.error('Error al cargar totales:', data.message);
        }
    } catch (err) {
        console.error('Error de conexión:', err);
        Swal.fire({
            icon: 'error',
            title: i18n.connection_error,
            text: i18n.could_not_connect_server,
            confirmButtonColor: '#2d4353'
        });
    }
}

// -------------------------------------------------
// Actualizar UI con totales
// -------------------------------------------------
function actualizarStatsUI() {
    const eEfectivo = document.getElementById('stat-efectivo');
    const eTarjeta = document.getElementById('stat-tarjeta');

    if (eEfectivo) eEfectivo.textContent = formatCurrency(expectedTotals.efectivo);
    if (eTarjeta) eTarjeta.textContent = formatCurrency(expectedTotals.tarjeta);
}

// -------------------------------------------------
// Modales – abrir / cerrar
// -------------------------------------------------
function abrirModalMovimiento(tipo) {
    const modal = document.getElementById('modalMovimiento');
    const title = document.getElementById('modal-mov-title');
    const actionInput = document.getElementById('mov-action');

    if (tipo === 'ingreso') {
        title.innerHTML = `
            <svg class="w-6 h-6 text-[#b4c24d]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12l4 4 4-4"/></svg>
            ${i18n.register_income_title}`;
        actionInput.value = 'ingreso';
    } else {
        title.innerHTML = `
            <svg class="w-6 h-6 text-[#e15871]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16V8M8 12l4-4 4 4"/></svg>
            ${i18n.register_withdrawal_title}`;
        actionInput.value = 'retiro';
    }

    document.getElementById('formMovimiento').reset();
    actionInput.value = tipo;

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function abrirModalCorte() {
    const modal = document.getElementById('modalCorte');

    document.getElementById('corte-efectivo-esp').value = formatCurrency(expectedTotals.efectivo);
    document.getElementById('corte-tarjeta-esp').value = formatCurrency(expectedTotals.tarjeta);

    document.getElementById('corte-efectivo-real').value = '';
    document.getElementById('corte-tarjeta-real').value = '';
    document.getElementById('corte-diferencia').textContent = '$0.00';
    document.getElementById('corte-diferencia').className = 'text-xl font-bold text-gray-400';

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function cerrarModal(id) {
    const modal = document.getElementById(id);
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// -------------------------------------------------
// Configurar eventos
// -------------------------------------------------
function configurarEventos() {
    const formMov = document.getElementById('formMovimiento');
    if (formMov) formMov.addEventListener('submit', handleSubmitMovimiento);

    const formCorte = document.getElementById('formCorte');
    if (formCorte) {
        formCorte.addEventListener('submit', handleSubmitCorte);

        const inpE = document.getElementById('corte-efectivo-real');
        const inpT = document.getElementById('corte-tarjeta-real');
        if (inpE) inpE.addEventListener('input', calcularDiferenciaCorte);
        if (inpT) inpT.addEventListener('input', calcularDiferenciaCorte);
    }

    // Cerrar al hacer clic fuera
    window.onclick = function (event) {
        const modalMov = document.getElementById('modalMovimiento');
        const modalCorte = document.getElementById('modalCorte');
        if (event.target === modalMov) cerrarModal('modalMovimiento');
        if (event.target === modalCorte) cerrarModal('modalCorte');
    }
}

// -------------------------------------------------
// Calcular diferencia en corte (live)
// -------------------------------------------------
function calcularDiferenciaCorte() {
    const efectivo = parseFloat(document.getElementById('corte-efectivo-real').value) || 0;
    const tarjeta = parseFloat(document.getElementById('corte-tarjeta-real').value) || 0;

    const totalReal = efectivo + tarjeta;
    const totalEsp = expectedTotals.efectivo + expectedTotals.tarjeta;
    const diff = totalReal - totalEsp;

    const el = document.getElementById('corte-diferencia');
    el.textContent = formatCurrency(diff);
    el.className = diff < 0
        ? 'text-xl font-bold text-red-600'
        : diff > 0
            ? 'text-xl font-bold text-green-600'
            : 'text-xl font-bold text-gray-900';
}

// -------------------------------------------------
// Submit movimiento (ingreso / retiro)
// -------------------------------------------------
async function handleSubmitMovimiento(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    const monto = parseFloat(formData.get('monto'));

    if (!monto || monto <= 0) {
        Swal.fire({
            icon: 'error',
            title: i18n.invalid_amount,
            text: i18n.enter_amount_greater_zero,
            confirmButtonColor: '#2d4353'
        });
        return;
    }

    if (!formData.get('motivo').trim()) {
        Swal.fire({
            icon: 'error',
            title: i18n.reason_required,
            text: i18n.must_specify_reason,
            confirmButtonColor: '#2d4353'
        });
        return;
    }

    try {
        const response = await fetch('api/caja_controller.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: formData.get('action') === 'ingreso' ? i18n.income_registered : i18n.withdrawal_registered,
                text: i18n.movement_saved_successfully,
                confirmButtonColor: '#2d4353',
                timer: 2000
            });
            cerrarModal('modalMovimiento');
            cargarTotales();
        } else {
            Swal.fire({
                icon: 'error',
                title: i18n.error,
                text: data.message || i18n.could_not_register_movement,
                confirmButtonColor: '#2d4353'
            });
        }
    } catch (err) {
        console.error(err);
        Swal.fire({
            icon: 'error',
            title: i18n.connection_error,
            text: i18n.could_not_connect_server,
            confirmButtonColor: '#2d4353'
        });
    }
}

// -------------------------------------------------
// Submit corte de caja
// -------------------------------------------------
async function handleSubmitCorte(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    const efectivoEsp = expectedTotals.efectivo;
    const tarjetaEsp = expectedTotals.tarjeta;
    const efectivoCont = parseFloat(formData.get('efectivo_contado')) || 0;
    const tarjetaCont = parseFloat(formData.get('tarjeta_contado')) || 0;

    formData.set('efectivo_esperado', efectivoEsp);
    formData.set('tarjeta_esperado', tarjetaEsp);

    const diferencia = (efectivoCont + tarjetaCont) - (efectivoEsp + tarjetaEsp);

    const result = await Swal.fire({
        icon: 'warning',
        title: i18n.confirm_cash_cut_title,
        html: `
            <div class="text-left space-y-2 text-sm">
                <p><strong>${i18n.expected_cash_label}:</strong> ${formatCurrency(efectivoEsp)}</p>
                <p><strong>${i18n.counted_cash_label}:</strong> ${formatCurrency(efectivoCont)}</p>
                <hr class="my-2">
                <p><strong>${i18n.expected_card_label}:</strong> ${formatCurrency(tarjetaEsp)}</p>
                <p><strong>${i18n.counted_card_label}:</strong> ${formatCurrency(tarjetaCont)}</p>
                <hr class="my-2">
                <p class="text-lg font-bold ${diferencia < 0 ? 'text-red-600' : diferencia > 0 ? 'text-green-600' : 'text-gray-900'}">
                    <strong>${i18n.difference}:</strong> ${formatCurrency(diferencia)}
                </p>
            </div>`,
        showCancelButton: true,
        confirmButtonText: i18n.yes_finalize_cut,
        cancelButtonText: i18n.cancel_btn,
        confirmButtonColor: '#2d4353',
        cancelButtonColor: '#6b7280'
    });

    if (!result.isConfirmed) return;

    try {
        const response = await fetch('api/caja_controller.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.status === 'success') {
            await Swal.fire({
                icon: 'success',
                title: i18n.cut_finalized,
                html: `
                    <p class="text-lg mb-2">${i18n.cut_registered_successfully}</p>
                    <p class="text-2xl font-bold ${data.diferencia < 0 ? 'text-red-600' : data.diferencia > 0 ? 'text-green-600' : 'text-gray-900'}">
                        ${i18n.difference}: ${formatCurrency(data.diferencia)}
                    </p>`,
                confirmButtonColor: '#2d4353'
            });
            cerrarModal('modalCorte');
            location.reload();
        } else {
            Swal.fire({
                icon: 'error',
                title: i18n.error,
                text: data.message || i18n.could_not_register_cut,
                confirmButtonColor: '#2d4353'
            });
        }
    } catch (err) {
        console.error(err);
        Swal.fire({
            icon: 'error',
            title: i18n.connection_error,
            text: i18n.could_not_connect_server,
            confirmButtonColor: '#2d4353'
        });
    }
}

// -------------------------------------------------
// Utilidades
// -------------------------------------------------
function formatCurrency(value) {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
        minimumFractionDigits: 2
    }).format(value);
}

function formatearFecha(str) {
    if (!str) return 'N/A';
    const d = new Date(str);
    return d.toLocaleString('es-MX', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}
