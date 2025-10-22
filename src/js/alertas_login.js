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
    
    // **NOTA:** Aquí puedes mantener la lógica del toggle de contraseña que ya tienes.
});