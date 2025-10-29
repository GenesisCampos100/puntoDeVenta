document.addEventListener('DOMContentLoaded', () => {
    // 1. Selecciona TODOS los botones de toggle (todos los elementos con esa clase)
    const toggleButtons = document.querySelectorAll('.js-password-toggle');

    // 2. Itera sobre cada botón encontrado para asignarle el evento click
    toggleButtons.forEach(toggleButton => {
        
        // En cada botón, encontramos su contenedor padre (.campo)
        const campoDiv = toggleButton.closest('.campo');
        
        // Dentro de ese contenedor, buscamos el input
        // Usamos querySelector('input') porque sabemos que solo hay un input dentro de cada .campo
        const passwordInput = campoDiv.querySelector('input[type="password"], input[type="text"]');

        if (passwordInput) {
            toggleButton.addEventListener('click', () => {
                
                // A. Alternar el tipo de input del campo actual
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);

                // B. Alternar la clase 'show-password' en el div.campo del campo actual
                campoDiv.classList.toggle('show-password');
            });
        }
    });
});