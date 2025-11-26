// Función para eliminar producto desde el modal de detalles
function confirmarEliminar(button) {
    const id = button.dataset.id;
    const nombre = button.dataset.nombre || 'este producto';

    Swal.fire({
        title: '¿Eliminar producto?',
        html: `¿Estás seguro de eliminar <strong>${nombre}</strong>?<br><span class="text-sm text-gray-500">Esta acción no se puede deshacer</span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e15871',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
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
                            // Cerrar modal si está abierto
                            const modal = document.getElementById('modalDetalle');
                            if (modal) {
                                modal.classList.add('hidden');
                                modal.classList.remove('flex');
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
        }
    });
}
