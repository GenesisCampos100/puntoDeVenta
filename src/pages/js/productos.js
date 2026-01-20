// productos.js - Manejo del modal de detalles de productos

document.addEventListener('DOMContentLoaded', function () {
    // Manejar clic en botones "Ver" para abrir modal
    document.addEventListener('click', function (e) {
        if (e.target.closest('.open-modal-btn')) {
            const button = e.target.closest('.open-modal-btn');
            const details = JSON.parse(button.dataset.details || '{}');
            const directId = button.dataset.id;

            // Abrir modal con los detalles del producto
            abrirModal(details, directId);
        }
    });
});

function abrirModal(producto, directId = null) {
    const modal = document.getElementById('modalDetalle');
    if (!modal) return;

    // Llenar datos del modal
    const modalImg = document.getElementById('modal-img');
    const modalNombre = document.getElementById('modal-nombre');
    const modalCategoria = document.getElementById('modal-categoria');
    const modalPrecio = document.getElementById('modal-precio');
    const modalCosto = document.getElementById('modal-costo');
    const modalCodigo = document.getElementById('modal-codigo');
    const modalStock = document.getElementById('modal-stock');
    const modalStockMin = document.getElementById('modal-stock-min');
    const modalBtnEditar = document.getElementById('modal-btn-editar');
    const modalBtnEliminar = document.getElementById('modal-btn-eliminar');

    // Imagen - usar ruta correcta desde la raíz del proyecto
    const imagen = producto.imagen ? `/puntoDeVenta/src/uploads/${producto.imagen}` : '/puntoDeVenta/src/uploads/sin-imagen.png';
    if (modalImg) modalImg.src = imagen;

    // Nombre y categoría
    if (modalNombre) modalNombre.textContent = producto.nom_producto || '';
    if (modalCategoria) modalCategoria.textContent = producto.categoria || producto.nombre_categoria || '';

    // Precios
    if (modalPrecio) modalPrecio.textContent = parseFloat(producto.precio || 0).toFixed(2);
    if (modalCosto) modalCosto.textContent = parseFloat(producto.costo || 0).toFixed(2);

    // Código/SKU
    const codigo = producto.sku || producto.cod_barras || '';
    if (modalCodigo) modalCodigo.textContent = codigo;

    // Stock
    if (modalStock) modalStock.textContent = producto.cantidad || 0;
    if (modalStockMin) modalStockMin.textContent = producto.cantidad_min || 0;

    // Botón Editar - configurar enlace para ir a la página de edición
    const productId = directId || producto.cod_barras || producto.id_producto || '';
    if (modalBtnEditar) {
        // Establecer href directamente
        modalBtnEditar.href = `index.php?view=editar_producto&id=${productId}`;
        console.log('Edit button configured with href:', modalBtnEditar.href);
    }

    // Botón Eliminar - solo configurar data attributes
    // El event listener se maneja en el script inline de productos_contenido.php
    if (modalBtnEliminar) {
        modalBtnEliminar.dataset.id = productId;
        modalBtnEliminar.dataset.nombre = producto.nom_producto || 'este producto';
    }

    // Actualizar input hidden para mayor robustez
    const hiddenInput = document.getElementById('id_producto_detalle');
    console.log('Intentando asignar ID al hidden input. ProductId:', productId);

    if (hiddenInput) {
        hiddenInput.value = productId;
        // Forzar atributo value también para inspección visual en DOM
        hiddenInput.setAttribute('value', productId);
        console.log('ID asignado a hidden input (value y atributo):', hiddenInput.value);
    } else {
        console.error('CRITICO: Hidden input id_producto_detalle no encontrado en el DOM');
    }

    // Mostrar modal
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function cerrarModal() {
    const modal = document.getElementById('modalDetalle');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
}

// Cerrar modal al hacer clic fuera de él
document.addEventListener('click', function (e) {
    const modal = document.getElementById('modalDetalle');
    if (modal && e.target.classList.contains('modal-backdrop')) {
        cerrarModal();
    }
});

// Cerrar modal con tecla ESC
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        cerrarModal();
        cerrarModalEditar();
    }
});

// ------------------------------------------------------------------
// LÓGICA DEL MODAL DE EDICIÓN
// ------------------------------------------------------------------

function abrirModalEditar(producto) {
    const modal = document.getElementById('modalEditar');
    if (!modal) return;

    // Llenar campos del formulario
    document.getElementById('edit-id').value = producto.cod_barras || producto.id_producto || '';
    document.getElementById('edit-nombre').value = producto.nom_producto || '';
    document.getElementById('edit-codigo').value = producto.sku || producto.cod_barras || '';
    document.getElementById('edit-categoria').value = producto.categoria || producto.nombre_categoria || '';
    document.getElementById('edit-marca').value = producto.marca || '';
    document.getElementById('edit-color').value = producto.color || '';
    document.getElementById('edit-stock').value = producto.cantidad || 0;
    document.getElementById('edit-descripcion').value = producto.descripcion || '';
    document.getElementById('edit-costo').value = producto.costo || 0;
    document.getElementById('edit-precio').value = producto.precio || 0;

    // Imagen previa - usar ruta correcta desde la raíz del proyecto
    const imgPreview = document.getElementById('edit-img-preview');
    const imagen = producto.imagen ? `/puntoDeVenta/src/uploads/${producto.imagen}` : '/puntoDeVenta/src/uploads/sin-imagen.png';
    if (imgPreview) imgPreview.src = imagen;

    // Mostrar modal
    modal.classList.remove('hidden');
    modal.classList.add('flex');

    // Cerrar modal de detalles si está abierto (opcional, pero mejora UX)
    cerrarModal();
}

function cerrarModalEditar() {
    const modal = document.getElementById('modalEditar');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
}

function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById('edit-img-preview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function guardarEdicion() {
    const form = document.getElementById('formEditarProducto');
    const formData = new FormData(form);

    // Mostrar loading
    Swal.fire({
        title: 'Guardando...',
        text: 'Por favor espera',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    fetch('index.php?view=editar_producto_modal', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: '¡Actualizado!',
                    text: 'El producto ha sido actualizado correctamente',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    cerrarModalEditar();
                    location.reload();
                });
            } else {
                Swal.fire({
                    title: 'Error',
                    text: data.error || 'No se pudo actualizar el producto',
                    icon: 'error',
                    confirmButtonColor: '#e15871'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                title: 'Error',
                text: 'Ocurrió un error al conectar con el servidor',
                icon: 'error',
                confirmButtonColor: '#e15871'
            });
        });
}
