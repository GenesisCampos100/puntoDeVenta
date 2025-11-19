document.addEventListener('DOMContentLoaded', function () {
    // 1. Extraer el token de la URL y ponerlo en el campo oculto
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get('token');
    const tokenInput = document.getElementById('token');
    if (tokenInput) {
        tokenInput.value = token;
    }

    const form = document.getElementById('reset-form');
    if (!form) {
        return; // No hacer nada si el formulario no existe
    }

    // 2. Escuchar el evento de envío del formulario
    form.addEventListener('submit', async function (event) {
        event.preventDefault(); // Evitar el envío tradicional

        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const tokenField = document.getElementById('token');
        const token = tokenField ? tokenField.value : null;

        // 3. Validar que las contraseñas coincidan
        if (password !== confirmPassword) {
            showErrorModal('Las contraseñas no coinciden.');
            return;
        }

        // Validar que el token exista
        if (!token) {
            showErrorModal('Token de seguridad no encontrado. Por favor, solicita un nuevo enlace de recuperación.');
            return;
        }

        // 4. Enviar los datos al servidor con fetch
        const formData = new FormData(form);
        formData.append('ajax', '1'); // Añadir bandera para que PHP sepa que es AJAX

        try {
            const response = await fetch('../scripts/process_reset.php', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor.');
            }

            const result = await response.json();

            // 5. Procesar la respuesta del servidor
            if (result.success) {
                showSuccessModal(result.message || '¡Cambio de contraseña exitoso!');
                if (result.redirect) {
                    setTimeout(() => {
                        window.location.href = result.redirect; // Redirigir a la URL proporcionada
                    }, 2000); // Esperar 2 segundos
                }
            } else {
                showErrorModal(result.message || 'Ha ocurrido un error.');
            }
        } catch (error) {
            console.error('Error en la petición fetch:', error);
            showErrorModal('No se pudo conectar con el servidor. Inténtalo de nuevo.');
        }
    });

    // --- Funciones para mostrar los modales ---

    function showSuccessModal(message) {
        const modal = document.getElementById('successModal');
        const modalMessage = document.getElementById('successMessage');
        if (modal && modalMessage) {
            modalMessage.textContent = message;
            modal.classList.add('visible');
        }
    }

    function showErrorModal(message) {
        const modal = document.getElementById('errorModal');
        const modalMessage = document.getElementById('errorMessage');
        const closeButton = document.getElementById('closeErrorModal');
        if (!modal || !modalMessage || !closeButton) return;

        modalMessage.textContent = message;
        modal.classList.add('visible');

        closeButton.onclick = () => modal.classList.remove('visible');
        modal.onclick = (e) => {
            if (e.target === modal) {
                modal.classList.remove('visible');
            }
        };
    }
});