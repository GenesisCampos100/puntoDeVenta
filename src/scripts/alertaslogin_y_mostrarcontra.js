// ALERTAS LOGIN (AJAX)
(function setupAjaxLogin() {
    const form = document.getElementById('loginFormulario');
    if (!form) { console.log('[login.js] form #loginFormulario not found'); return; }
    console.log('[login.js] form found, attaching submit handler');

    form.addEventListener('submit', async (e) => {
        console.log('[login.js] submit intercepted');
        e.preventDefault();

        const formData = new FormData(form);
        // Señalamos al servidor que esta petición viene vía AJAX (fallback adicional)
        formData.append('ajax', '1');

        try {
            console.log('[login.js] sending fetch to', form.action);
            const res = await fetch(form.action, {
                method: form.method || 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            });

            console.log('[login.js] fetch response status', res.status);
            let data = null;
            try {
                data = await res.json();
            } catch (jsonErr) {
                console.error('[login.js] failed to parse JSON', jsonErr);
            }

            console.log('[login.js] response JSON', data);

                if (data && data.success) {
                    // En lugar de redirigir directamente, muestra el modal
                    showSuccessModal(data.redirect);
                } else {
                    const msg = data && data.message ? data.message : 'Error al iniciar sesión, contraseña o correo incorrectos';
                    showErrorModal(msg);
                }
            } catch (err) {
                console.error('[login.js] fetch error', err);
                showErrorModal('Error de red. Intenta de nuevo.');
            }
        });
    })();

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

    function showSuccessModal(redirectUrl) {
        const modal = document.getElementById('successModal');
        if (!modal) return;

        modal.classList.add('visible');

        // Esperar 2 segundos y luego redirigir
        setTimeout(() => {
            window.location.href = redirectUrl;
        }, 2000);
    }
// Ejecutar la inicialización inmediatamente si el DOM ya está listo, o esperar al evento
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLoginScripts);
} else {
    initLoginScripts();
}
