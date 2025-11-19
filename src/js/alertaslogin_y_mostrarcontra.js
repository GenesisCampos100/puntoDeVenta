//! MOSTRAR CONTRASEÑA
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

//! ALERTAS LOGIN
document.addEventListener('DOMContentLoaded', () => {
    // Seleccionar el formulario completo por su nuevo ID
    const form = document.getElementById('loginFormulario');

    if (form) {
        // Escuchar el evento de envío (submit) del formulario
        form.addEventListener('submit', function(event) {
            
            let camposVacios = false;
            
            // Seleccionar todos los campos que tienen el atributo 'required' dentro del formulario
            const requiredInputs = form.querySelectorAll('[required]');

            // Iterar sobre los campos requeridos
            requiredInputs.forEach(input => {
                // Si el valor del campo, sin espacios iniciales/finales, está vacío
                if (input.value.trim() === '') {
                    camposVacios = true; 
                    // Enfocar en el primer campo vacío encontrado (opcional, pero útil)
                    input.focus(); 
                    return; // Salir del bucle una vez que se encuentra el primer error
                }
            });

            // Si se encontró algún campo vacío
            if (camposVacios) {
                // Detener el envío del formulario
                event.preventDefault(); 
                
                // Mostrar la alerta al usuario
                alert('¡Faltan datos! Por favor, complete todos los campos obligatorios para iniciar sesión.');
            } else {
                // Si no hay campos vacíos, el formulario se envía al servidor
                console.log('Formulario válido. Iniciando sesión...');
            }
        });
    }
});