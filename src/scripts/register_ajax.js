(function() {
    const form = document.getElementById('registerForm');
    if (!form) {
        return;
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(form);
        formData.append('ajax', '1'); // Indicar que es una petición AJAX

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (result.success) {
                showSuccessModal(result.message, result.redirect);
            } else {
                showErrorModal(result.message);
            }
        } catch (error) {
            console.error('Error en el registro:', error);
            showErrorModal('Ocurrió un error de red. Inténtalo de nuevo.');
        }
    });

    function showSuccessModal(message, redirectUrl) {
        const modal = document.getElementById('successModal');
        const messageElement = document.getElementById('successMessage');
        if (!modal || !messageElement) return;

        messageElement.textContent = message;
        modal.classList.add('visible');

        setTimeout(() => {
            window.location.href = redirectUrl;
        }, 3000); // Redirigir después de 3 segundos
    }

    function showErrorModal(message) {
        const modal = document.getElementById('errorModal');
        const messageElement = document.getElementById('errorMessage');
        const closeButton = document.getElementById('closeErrorModal');
        if (!modal || !messageElement || !closeButton) return;

        messageElement.textContent = message;
        modal.classList.add('visible');

        closeButton.onclick = () => modal.classList.remove('visible');
        modal.onclick = (e) => {
            if (e.target === modal) {
                modal.classList.remove('visible');
            }
        };
    }
})();
