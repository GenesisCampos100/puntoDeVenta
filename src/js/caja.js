// js/caja.js - Gestión de Caja Principal
// Conecta con ../api/caja_controller.php

// Estado global
let expectedTotals = {
    efectivo: 0,
    tarjeta: 0
};

// ========================================
// INICIALIZACIÓN
// ========================================
document.addEventListener('DOMContentLoaded', () => {
    cargarTotales();
    configurarEventos();
});

// ========================================
// CARGAR TOTALES DESDE API
// ========================================
async function cargarTotales() {
    try {
        const formData = new FormData();
        formData.append('action', 'fetch_totales');

        const response = await fetch('../api/caja_controller.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.status === 'success') {
            expectedTotals.efectivo = parseFloat(data.efectivo_esperado) || 0;
            expectedTotals.tarjeta = parseFloat(data.tarjeta_esperado) || 0;

            // Actualizar UI
            actualizarStatsUI();

            // Obtener fecha del último corte
            if (data.ultima_fecha_corte) {
                document.getElementById('last-cut-date').textContent = formatearFecha(data.ultima_fecha_corte);
            } else {
                document.getElementById('last-cut-date').textContent = 'Sin cortes previos';
            }
        } else {
            console.error('Error al cargar totales:', data.message);
        }
    } catch (error) {
        console.error('Error de conexión:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error de Conexión',
            text: 'No se pudieron cargar los totales. Verifica tu conexión.',
            confirmButtonColor: '#2d4353'
        });
    }
}

// ========================================
// ACTUALIZAR UI CON TOTALES
// ========================================
function actualizarStatsUI() {
    const elEfectivo = document.getElementById('stat-efectivo');
    const elTarjeta = document.getElementById('stat-tarjeta');

    if (elEfectivo) elEfectivo.textContent = formatCurrency(expectedTotals.efectivo);
    if (elTarjeta) elTarjeta.textContent = formatCurrency(expectedTotals.tarjeta);
}

// ========================================
// MODALES - ABRIR/CERRAR
// ========================================
function abrirModalMovimiento(tipo) {
    const modal = document.getElementById('modalMovimiento');
    const title = document.getElementById('modal-mov-title');
    const actionInput = document.getElementById('mov-action');

    if (tipo === 'ingreso') {
        title.innerHTML = `
            <svg class="w-6 h-6 text-[#b4c24d]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12l4 4 4-4"/></svg>
            Registrar Ingreso
        `;
        actionInput.value = 'ingreso';
    } else {
        title.innerHTML = `
            <svg class="w-6 h-6 text-[#e15871]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16V8M8 12l4-4 4 4"/></svg>
            Registrar Retiro
        `;
        actionInput.value = 'retiro';
    }

    // Limpiar formulario
    document.getElementById('formMovimiento').reset();
    actionInput.value = tipo;

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function abrirModalCorte() {
    const modal = document.getElementById('modalCorte');

    // Pre-cargar valores esperados
    document.getElementById('corte-efectivo-esp').value = formatCurrency(expectedTotals.efectivo);
    document.getElementById('corte-tarjeta-esp').value = formatCurrency(expectedTotals.tarjeta);

    // Limpiar valores contados
    document.getElementById('corte-efectivo-real').value = '';
    document.getElementById('corte-tarjeta-real').value = '';
    document.getElementById('corte-diferencia').textContent = '$0.00';
    document.getElementById('corte-diferencia').className = 'text-xl font-bold text-gray-400';

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function cerrarModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// ========================================
// CONFIGURAR EVENTOS
// ========================================
function configurarEventos() {
    // Form Movimiento
    const formMov = document.getElementById('formMovimiento');
    if (formMov) {
        formMov.addEventListener('submit', handleSubmitMovimiento);
    }

    // Form Corte
    const formCorte = document.getElementById('formCorte');
    if (formCorte) {
        formCorte.addEventListener('submit', handleSubmitCorte);

        // Calcular diferencia en tiempo real
        const inputEfectivo = document.getElementById('corte-efectivo-real');
        const inputTarjeta = document.getElementById('corte-tarjeta-real');

        if (inputEfectivo) inputEfectivo.addEventListener('input', calcularDiferenciaCorte);
        if (inputTarjeta) inputTarjeta.addEventListener('input', calcularDiferenciaCorte);
    }

    // Cerrar modales al hacer clic fuera
    document.getElementById('modalMovimiento')?.addEventListener('click', (e) => {
        if (e.target.id === 'modalMovimiento') cerrarModal('modalMovimiento');
    });

    document.getElementById('modalCorte')?.addEventListener('click', (e) => {
        if (e.target.id === 'modalCorte') cerrarModal('modalCorte');
    });
}

// ========================================
// CALCULAR DIFERENCIA EN CORTE (LIVE)
// ========================================
function calcularDiferenciaCorte() {
    const efectivoReal = parseFloat(document.getElementById('corte-efectivo-real').value) || 0;
    const tarjetaReal = parseFloat(document.getElementById('corte-tarjeta-real').value) || 0;

    const totalReal = efectivoReal + tarjetaReal;
    const totalEsperado = expectedTotals.efectivo + expectedTotals.tarjeta;
    const diferencia = totalReal - totalEsperado;

    const elDiferencia = document.getElementById('corte-diferencia');
    elDiferencia.textContent = formatCurrency(diferencia);

    // Colorear según diferencia
    if (diferencia < 0) {
        elDiferencia.className = 'text-xl font-bold text-red-600';
    } else if (diferencia > 0) {
        elDiferencia.className = 'text-xl font-bold text-green-600';
    } else {
        elDiferencia.className = 'text-xl font-bold text-gray-900';
    }
}

// ========================================
// SUBMIT MOVIMIENTO (INGRESO/RETIRO)
// ========================================
async function handleSubmitMovimiento(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    const action = formData.get('action');
    const monto = parseFloat(formData.get('monto'));

    // Validación
    if (!monto || monto <= 0) {
        Swal.fire({
            icon: 'error',
            title: 'Monto Inválido',
            text: 'Ingresa un monto mayor a cero.',
            confirmButtonColor: '#2d4353'
        });
        return;
    }

    if (!formData.get('motivo').trim()) {
        Swal.fire({
            icon: 'error',
            title: 'Motivo Requerido',
            text: 'Debes especificar un motivo para este movimiento.',
            confirmButtonColor: '#2d4353'
        });
        return;
    }

    try {
        const response = await fetch('../api/caja_controller.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: action === 'ingreso' ? 'Ingreso Registrado' : 'Retiro Registrado',
                text: 'El movimiento se ha guardado correctamente.',
                confirmButtonColor: '#2d4353',
                timer: 2000
            });

            cerrarModal('modalMovimiento');
            cargarTotales(); // Recargar totales
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'No se pudo registrar el movimiento.',
                confirmButtonColor: '#2d4353'
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error de Conexión',
            text: 'No se pudo conectar con el servidor.',
            confirmButtonColor: '#2d4353'
        });
    }
}

// ========================================
// SUBMIT CORTE DE CAJA
// ========================================
async function handleSubmitCorte(e) {
    e.preventDefault();

    const formData = new FormData(e.target);

    const efectivoEsperado = expectedTotals.efectivo;
    const tarjetaEsperado = expectedTotals.tarjeta;
    const efectivoContado = parseFloat(formData.get('efectivo_contado')) || 0;
    const tarjetaContado = parseFloat(formData.get('tarjeta_contado')) || 0;

    // Agregar valores esperados al FormData
    formData.set('efectivo_esperado', efectivoEsperado);
    formData.set('tarjeta_esperado', tarjetaEsperado);

    // Confirmación
    const diferencia = (efectivoContado + tarjetaContado) - (efectivoEsperado + tarjetaEsperado);

    const result = await Swal.fire({
        icon: 'warning',
        title: '¿Confirmar Corte de Caja?',
        html: `
            <div class="text-left space-y-2 text-sm">
                <p><strong>Efectivo Esperado:</strong> ${formatCurrency(efectivoEsperado)}</p>
                <p><strong>Efectivo Contado:</strong> ${formatCurrency(efectivoContado)}</p>
                <hr class="my-2">
                <p><strong>Tarjeta Esperado:</strong> ${formatCurrency(tarjetaEsperado)}</p>
                <p><strong>Tarjeta Contado:</strong> ${formatCurrency(tarjetaContado)}</p>
                <hr class="my-2">
                <p class="text-lg font-bold ${diferencia < 0 ? 'text-red-600' : diferencia > 0 ? 'text-green-600' : 'text-gray-900'}">
                    <strong>Diferencia:</strong> ${formatCurrency(diferencia)}
                </p>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Sí, Finalizar Corte',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#2d4353',
        cancelButtonColor: '#6b7280'
    });

    if (!result.isConfirmed) return;

    try {
        const response = await fetch('../api/caja_controller.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.status === 'success') {
            await Swal.fire({
                icon: 'success',
                title: 'Corte Finalizado',
                html: `
                    <p class="text-lg mb-2">El corte de caja se ha registrado correctamente.</p>
                    <p class="text-2xl font-bold ${data.diferencia < 0 ? 'text-red-600' : data.diferencia > 0 ? 'text-green-600' : 'text-gray-900'}">
                        Diferencia: ${formatCurrency(data.diferencia)}
                    </p>
                `,
                confirmButtonColor: '#2d4353'
            });

            cerrarModal('modalCorte');
            location.reload(); // Recargar página para nuevo turno
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'No se pudo registrar el corte.',
                confirmButtonColor: '#2d4353'
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error de Conexión',
            text: 'No se pudo conectar con el servidor.',
            confirmButtonColor: '#2d4353'
        });
    }
}

// ========================================
// UTILIDADES
// ========================================
function formatCurrency(value) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2
    }).format(value);
}

function formatearFecha(fechaStr) {
    if (!fechaStr) return 'N/A';
    const fecha = new Date(fechaStr);
    return fecha.toLocaleString('es-MX', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}
