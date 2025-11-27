// modal_ventas.js - Modal de detalles de venta y eliminación
(() => {
    document.addEventListener('DOMContentLoaded', () => {

        /* ===========================
             MODAL DETALLE DE VENTA
        ============================ */
        const ventaModal = document.getElementById('venta-modal');
        const ventaDetalles = document.getElementById('venta-detalles');
        const closeVentaModal = document.getElementById('close-venta-modal');

        // Abrir modal de detalle
        document.addEventListener('click', async (e) => {
            if (e.target.classList.contains('ver-detalle-btn')) {
                const idVenta = e.target.dataset.id;

                try {
                    const response = await fetch(`../src/scripts/obtener_detalle_venta.php?id_venta=${idVenta}`);
                    const data = await response.json();

                    if (data.success) {
                        ventaDetalles.innerHTML = `
              <div class="space-y-4">
                <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl p-4 border border-indigo-100">
                  <h3 class="font-bold text-lg text-indigo-900 mb-3">Información General</h3>
                  <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                      <p class="text-gray-600">ID Venta:</p>
                      <p class="font-semibold text-gray-900">#${data.venta.id_venta}</p>
                    </div>
                    <div>
                      <p class="text-gray-600">Fecha:</p>
                      <p class="font-semibold text-gray-900">${new Date(data.venta.fecha).toLocaleString('es-MX')}</p>
                    </div>
                    <div>
                      <p class="text-gray-600">Empleado:</p>
                      <p class="font-semibold text-gray-900">${data.venta.empleado || 'N/A'}</p>
                    </div>
                    <div>
                      <p class="text-gray-600">Cliente:</p>
                      <p class="font-semibold text-gray-900">${data.venta.cliente || 'Público General'}</p>
                    </div>
                  </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-200">
                  <h3 class="font-bold text-lg text-gray-900 p-4 border-b border-gray-200">Productos</h3>
                  <div class="divide-y divide-gray-100">
                    ${data.productos.map(p => `
                      <div class="p-4 hover:bg-gray-50 transition">
                        <div class="flex justify-between items-start">
                          <div class="flex-1">
                            <p class="font-semibold text-gray-900">${p.nombre}</p>
                            <p class="text-sm text-gray-600">${p.talla} / ${p.color}</p>
                            <p class="text-xs text-gray-500 mt-1">Cantidad: ${p.cantidad} × $${parseFloat(p.precio_unitario).toFixed(2)}</p>
                            ${p.descuento > 0 ? `<p class="text-xs text-red-600 mt-1">Descuento: -$${parseFloat(p.descuento).toFixed(2)}</p>` : ''}
                          </div>
                          <div class="text-right">
                            <p class="font-bold text-green-600">$${parseFloat(p.subtotal).toFixed(2)}</p>
                          </div>
                        </div>
                      </div>
                    `).join('')}
                  </div>
                </div>

                <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-4 border border-green-100">
                  <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                      <span class="text-gray-700">Subtotal:</span>
                      <span class="font-semibold text-gray-900">$${parseFloat(data.venta.subtotal || 0).toFixed(2)}</span>
                    </div>
                    ${data.venta.descuento_general > 0 ? `
                      <div class="flex justify-between text-red-600">
                        <span>Descuento General:</span>
                        <span class="font-semibold">-$${parseFloat(data.venta.descuento_general).toFixed(2)}</span>
                      </div>
                    ` : ''}
                    <div class="flex justify-between text-lg font-bold text-green-700 pt-2 border-t border-green-200">
                      <span>Total:</span>
                      <span>$${parseFloat(data.venta.total).toFixed(2)}</span>
                    </div>
                  </div>
                </div>

                ${data.pagos && data.pagos.length > 0 ? `
                  <div class="bg-blue-50 rounded-xl p-4 border border-blue-100">
                    <h3 class="font-bold text-sm text-blue-900 mb-2">Métodos de Pago</h3>
                    <div class="space-y-2">
                      ${data.pagos.map(pago => `
                        <div class="flex justify-between items-center text-sm">
                          <div>
                            <span class="font-medium text-gray-900">${pago.metodo}</span>
                            ${pago.referencia ? `<span class="text-xs text-gray-600 ml-2">(Ref: ${pago.referencia})</span>` : ''}
                          </div>
                          <span class="font-semibold text-blue-700">$${parseFloat(pago.monto).toFixed(2)}</span>
                        </div>
                      `).join('')}
                    </div>
                  </div>
                ` : ''}

                <div class="flex gap-3 pt-4">
                  <button onclick="window.open('../src/scripts/ticket.php?id_venta=${idVenta}', '_blank')" 
                          class="flex-1 px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl font-semibold flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Ver Ticket
                  </button>
                </div>
              </div>
            `;

                        ventaModal?.classList.remove('hidden');
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'No se pudo cargar el detalle de la venta'
                        });
                    }
                } catch (error) {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error al cargar los detalles de la venta'
                    });
                }
            }
        });

        // Cerrar modal
        closeVentaModal?.addEventListener('click', () => {
            ventaModal?.classList.add('hidden');
        });

        ventaModal?.addEventListener('click', (e) => {
            if (e.target === ventaModal) {
                ventaModal.classList.add('hidden');
            }
        });

        /* ===========================
             ELIMINAR VENTA
        ============================ */
        document.addEventListener('click', async (e) => {
            if (e.target.classList.contains('delete-sale-btn')) {
                const idVenta = e.target.dataset.id;

                const result = await Swal.fire({
                    title: '¿Estás seguro?',
                    text: "Esta acción no se puede deshacer",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                });

                if (result.isConfirmed) {
                    try {
                        const formData = new FormData();
                        formData.append('id_venta', idVenta);

                        const response = await fetch('../src/scripts/eliminar_venta.php', {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();

                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Eliminado!',
                                text: 'La venta ha sido eliminada',
                                timer: 2000,
                                showConfirmButton: false
                            });

                            // Recargar la página después de 2 segundos
                            setTimeout(() => {
                                window.location.reload();
                            }, 2000);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'No se pudo eliminar la venta'
                            });
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error al eliminar la venta'
                        });
                    }
                }
            }
        });

    }); // fin DOMContentLoaded
})(); // fin IIFE
