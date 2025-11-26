// Función para eliminar producto desde el modal de detalles
function confirmarEliminar(button) {
    const id = button.dataset.id;
    const nombre = button.dataset.nombre || 'este producto';

    const confirmModal = document.getElementById('confirmModal');
    const confirmMessage = document.getElementById('confirmMessage');
    const confirmBtn = document.getElementById('confirmBtn');
    const cancelBtn = document.getElementById('cancelBtn');

    if (!confirmModal || !confirmBtn || !cancelBtn) {
        console.error('Elementos del modal de confirmación no encontrados');
        return;
    }

    // Actualizar mensaje
    if (confirmMessage) {
        confirmMessage.innerHTML = `¿Estás seguro de eliminar <strong>${nombre}</strong>?<br><span class="text-sm text-gray-500">Esta acción no se puede deshacer</span>`;
    }

    // Mostrar modal
    confirmModal.classList.remove('hidden');
    confirmModal.classList.add('flex');

    // Función para cerrar modal
    const closeModal = () => {
        confirmModal.classList.add('hidden');
        confirmModal.classList.remove('flex');
        // Limpiar eventos para evitar duplicados
        confirmBtn.onclick = null;
        cancelBtn.onclick = null;
    };

    // Configurar botón cancelar
    cancelBtn.onclick = closeModal;

    // Configurar botón confirmar
    confirmBtn.onclick = () => {
        closeModal();

        // Mostrar loading
        Swal.fire({
            title: 'Eliminando...',
            text: 'Por favor espera',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Llamada AJAX real al backend para eliminar
        fetch('index.php?view=eliminar_producto', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ id: id })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: '¡Eliminado!',
                        text: 'El producto ha sido eliminado correctamente',
                        icon: 'success',
                        confirmButtonColor: '#b4c24d',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        // Cerrar modal de detalles si está abierto
                        const modalDetalle = document.getElementById('modalDetalle');
                        if (modalDetalle) {
                            modalDetalle.classList.add('hidden');
                            modalDetalle.classList.remove('flex');
                        }
                        // Recargar la página para actualizar la tabla
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.error || 'No se pudo eliminar el producto',
                        icon: 'error',
                        confirmButtonColor: '#e15871'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Ocurrió un error al eliminar el producto',
                    icon: 'error',
                    confirmButtonColor: '#e15871'
                });
            });
    };

    // Cerrar al hacer clic fuera
    confirmModal.onclick = (e) => {
        if (e.target === confirmModal) closeModal();
    };
}
