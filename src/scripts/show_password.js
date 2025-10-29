// src/scripts/show_password.js
document.addEventListener('DOMContentLoaded', function () {
    const passwordToggles = document.querySelectorAll('.js-password-toggle');

    passwordToggles.forEach(toggle => {
        // El input de contraseña es el elemento hermano anterior al div del icono
        const passwordField = toggle.previousElementSibling;
        const toggleOpen = toggle.querySelector('.toggle-open');
        const toggleClosed = toggle.querySelector('.toggle-closed');

        if (!passwordField || passwordField.tagName !== 'INPUT' || !toggleOpen || !toggleClosed) {
            return; // Si la estructura no es la esperada, no hacer nada
        }

        // Estado inicial: contraseña oculta, ojo cerrado visible
        toggleOpen.style.display = 'none';
        toggleClosed.style.display = 'block';

        toggle.addEventListener('click', () => {
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);

            // Cambiar el icono visible
            if (type === 'password') {
                toggleOpen.style.display = 'none';
                toggleClosed.style.display = 'block';
            } else {
                toggleOpen.style.display = 'block';
                toggleClosed.style.display = 'none';
            }
        });
    });
});