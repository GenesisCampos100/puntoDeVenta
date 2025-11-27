$(document).ready(function () {
    $('#modal-btn-eliminar').click(function (e) {
        e.preventDefault();

        // Obtener ID del producto (intentar data attribute o input oculto)
        const id = $(this).data('id') || $('#id_producto_detalle').val();

        if (!id) {
            Swal.fire('Error', 'No se pudo identificar el producto.', 'error');
            return;
        }

        Swal.fire({
            title: '¿Estás seguro?',
            text: "¿Deseas eliminar este producto del inventario?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e15871', // Rojo para acción destructiva
            cancelButtonColor: '#2d4353', // Secundario para cancelar
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            backdrop: `rgba(0,0,0,0.4)`
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/puntoDeVenta/src/pages/eliminar_producto.php',
                    type: 'POST',
                    data: { cod_barras: id },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            // 1. Cerrar el modal
                            if (typeof cerrarModal === 'function') {
                                cerrarModal();
                            } else {
                                $('#modalDetalle').fadeOut();
                            }

                            // 2. Mostrar mensaje de éxito
                            Swal.fire({
                                title: '¡Eliminado!',
                                text: 'El producto ha sido eliminado correctamente.',
                                icon: 'success',
                                confirmButtonColor: '#b4c24d'
                            });

                            // 3. Actualizar la tabla
                            // Eliminar la fila visualmente si existe
                            $(`#row-${id}`).fadeOut(300, function () { $(this).remove(); });

                            // Recargar la lista completa si la función existe
                            if (typeof cargarProductos === 'function') {
                                cargarProductos();
                            } else {
                                // Si no existe la función de recarga, recargar la página después de un momento
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1500);
                            }
                        } else {
                            Swal.fire('Error', response.message || response.error || 'No se pudo eliminar el producto.', 'error');
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error("Error en eliminación:", xhr.responseText);
                        Swal.fire('Error', 'Ocurrió un error de conexión al eliminar.', 'error');
                    }
                });
            }
        });
    });
});
