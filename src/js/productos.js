/**
 * productos.js
 * RECONSTRUCCIÓN TOTAL - LÓGICA ROBUSTA
 */

// 1. Configuración y Utilidades
// -------------------------------------------------------------------

// Usamos la BASE_URL inyectada desde PHP. Si no existe, fallback (aunque no debería pasar)
const API_ENDPOINT = (typeof BASE_URL !== 'undefined') ? BASE_URL : 'api/inventario_api.php';

// Función Genérica para llamadas API
async function apiCall(action, params = {}, method = 'GET') {
    try {
        let url = new URL(API_ENDPOINT, window.location.origin);
        let options = {
            method: method,
            headers: {
                'X-Requested-With': 'XMLHttpRequest' // Identificador AJAX estándar
            }
        };

        // Preparar datos según método
        if (method === 'GET') {
            url.searchParams.append('action', action);
            Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));
        } else {
            // POST
            let formData = new FormData();
            formData.append('action', action);
            Object.keys(params).forEach(key => formData.append(key, params[key]));
            options.body = formData;
        }

        const response = await fetch(url, options);

        // Verificar si la respuesta es JSON válido
        const text = await response.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error("Respuesta no JSON:", text);
            throw new Error("El servidor devolvió una respuesta inválida (posible error PHP).");
        }

        if (!response.ok) {
            throw new Error(data.message || `Error HTTP ${response.status}`);
        }

        if (!data.success) {
            throw new Error(data.message || "Error desconocido en la operación.");
        }

        return data;

    } catch (error) {
        console.error("API Error:", error);
        Swal.fire({
            icon: 'error',
            title: 'Error de Conexión',
            text: error.message,
            confirmButtonColor: '#1f2937'
        });
        return null; // Retornar null para indicar fallo
    }
}

// 2. Lógica Principal (Carga de Productos)
// -------------------------------------------------------------------
async function cargarProductos() {
    const $tabla = $('#tabla-productos');
    const $loader = $('#loader'); // Si existe

    // UI Feedback
    $tabla.addClass('opacity-50 pointer-events-none');

    // Recoger filtros
    const filters = {
        busqueda: $('#busqueda').val().trim(),
        categoria: $('#categoria').val(),
        orden: $('#orden').val(),
        tab: $('.tab-btn.active').data('status') || 'activo'
    };

    const res = await apiCall('filtrar', filters, 'GET');

    $tabla.removeClass('opacity-50 pointer-events-none');

    if (res) {
        $tabla.html(res.html);
        if (res.total !== undefined && $('#totalProductos').length) {
            $('#totalProductos').text(res.total);
        }
        // Reinicializar iconos
        if (window.lucide) lucide.createIcons();
    }
}

// 3. Inicialización y Event Listeners
// -------------------------------------------------------------------
$(document).ready(function () {
    console.log("Sistema de Productos Iniciado. API:", API_ENDPOINT);

    // Carga inicial
    cargarProductos();

    // Filtros
    $('#categoria, #orden').on('change', cargarProductos);

    // Búsqueda (Debounce)
    let timeout;
    $('#busqueda').on('input', function () {
        clearTimeout(timeout);
        const val = $(this).val();
        $('#clear-search').toggleClass('hidden', val === '');
        timeout = setTimeout(cargarProductos, 300);
    });

    $('#clear-search').on('click', function () {
        $('#busqueda').val('').trigger('input').focus();
    });

    // Tabs
    $('.tab-btn').on('click', function () {
        $('.tab-btn').removeClass('active bg-white text-gray-900 shadow-sm').addClass('text-gray-500');
        $(this).addClass('active bg-white text-gray-900 shadow-sm').removeClass('text-gray-500');
        cargarProductos();
    });

    // 4. Acciones (Event Delegation)
    // ---------------------------------------------------------------

    // A) Toggle Variantes
    $(document).on('click', '.toggle-variants', function (e) {
        e.stopPropagation();
        const target = $(this).data('target-id');
        const $row = $('#' + target);
        const $icon = $(this).find('.arrow-icon');

        if ($row.hasClass('hidden')) {
            $row.removeClass('hidden');
            $icon.addClass('rotate-180');
        } else {
            $row.addClass('hidden');
            $icon.removeClass('rotate-180');
        }
    });

    // B) Ver Detalles (Modal)
    $(document).on('click', '.open-modal-btn', function (e) {
        e.preventDefault();
        const json = $(this).attr('data-details');
        if (!json) return;

        try {
            const data = JSON.parse(json);

            // Llenar Modal
            $('#modal-nombre').text(data.nom_producto || data.producto_nombre);
            $('#modal-categoria').text(data.nombre_categoria || data.categoria || 'General');
            $('#modal-codigo').text(data.cod_barras || data.sku || 'N/A');
            $('#modal-precio').text(parseFloat(data.precio).toFixed(2));
            $('#modal-costo').text(data.costo ? parseFloat(data.costo).toFixed(2) : '0.00');
            $('#modal-stock').text(data.cantidad);
            $('#modal-stock-min').text(data.cantidad_min);

            const img = data.imagen ? `uploads/${data.imagen}` : '../uploads/sin-imagen.png';
            $('#modal-img').attr('src', img);

            // Configurar botones del modal
            const id = data.cod_barras || data.id_producto; // Ajustar según datos
            $('#modal-btn-eliminar').data('id', id).data('type', 'producto'); // Simplificado

            // FIX: Actualizar input oculto para que el script inline lo detecte
            $('#id_producto_detalle').val(id);

            // FIX: Actualizar enlace de edición
            $('#modal-btn-editar').attr('href', `index.php?view=editar_producto&id=${id}`);

            $('#modalDetalle').removeClass('hidden').fadeIn(200).css('display', 'flex');
        } catch (err) {
            console.error("Error al abrir modal:", err);
        }
    });

    // C) Ajustar Stock
    $(document).on('click', '.btn-ajuste', async function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        const type = $(this).data('type'); // 'producto' o 'variante'

        const { value: cantidad } = await Swal.fire({
            title: 'Ajustar Stock',
            text: `Producto: ${nombre}`,
            input: 'number',
            inputLabel: 'Cantidad a sumar (positivo) o restar (negativo)',
            inputPlaceholder: 'Ej: 5 o -2',
            showCancelButton: true
        });

        if (cantidad && parseInt(cantidad) !== 0) {
            const res = await apiCall('ajustar_stock', {
                cod_entidad: id,
                cantidad: parseInt(cantidad),
                ajusteEsVariante: (type === 'variante')
            }, 'POST');

            if (res) {
                Swal.fire('Éxito', res.message, 'success');
                cargarProductos(); // Recargar tabla
            }
        }
    });

    // D) Descatalogar / Activar
    $(document).on('click', '.toggle-active', async function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        const currentActive = $(this).data('active') === true || $(this).data('active') === 'true';
        const action = currentActive ? 'descatalogar' : 'activar';

        const result = await Swal.fire({
            title: `¿${action.charAt(0).toUpperCase() + action.slice(1)}?`,
            text: "El estado del producto cambiará.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, cambiar',
            cancelButtonText: 'Cancelar'
        });

        if (result.isConfirmed) {
            const res = await apiCall('toggle_activo', {
                id: id,
                status: currentActive ? 0 : 1
            }, 'POST');

            if (res) {
                Swal.fire('Hecho', res.message, 'success');
                cargarProductos();
            }
        }
    });
});

// Funciones Globales para Modales (Cerrar)
window.cerrarModal = function () {
    $('#modalDetalle').fadeOut(200, function () { $(this).addClass('hidden'); });
    $('#confirmModal').fadeOut(200, function () { $(this).addClass('hidden'); });
};
