<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="../styles/login.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=lock" />
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap"
        rel="stylesheet">
    <!-- include the script -->
    <script src="{PATH}/alertify.min.js"></script>

    <!-- include the style -->
    <link rel="stylesheet" href="{PATH}/alertify.min.css" />
    <!-- include a theme -->
    <link rel="stylesheet" href="{PATH}/themes/default.min.css" />
        
    <!-- <link rel="stylesheet" href="../styles/login.css"> -->
    
</head>
<body class="fondo_login">

    <main class="contenedor">

        <section class="contenedor-login"> <!--Inicio de login-->

            <div class="formulario">

            </div>


            <div class="formulario "> <!--Inicio formulario-->
                <div class="login-logo">
                    <img src="../../public/img/logo2.png">
                </div>

                <H1 class="login-titulo">Tu punto de venta favorito</H1>
                <?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger" 
         style="text-align:center; background:#f8d7da; color:#842029; padding:10px; border-radius:8px; margin:10px 0;">
        <?= htmlspecialchars($_SESSION['error']); ?>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>


                <form id="loginFormulario" action="/puntoDeVenta/src/scripts/validar_login.php" method="POST">
                    <div class="contenedor-campos"> <!--Inicio contenedor de campos-->

                        <div class="campo">
                            <label for="usuario">Ingrese su usuario:</label>
                            <svg class="input-icon icon__left" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                fill="currentColor" class="size-6">
                                <path
                                    d="M1.5 8.67v8.58a3 3 0 0 0 3 3h15a3 3 0 0 0 3-3V8.67l-8.928 5.493a3 3 0 0 1-3.144 0L1.5 8.67Z" />
                                <path
                                    d="M22.5 6.908V6.75a3 3 0 0 0-3-3h-15a3 3 0 0 0-3 3v.158l9.714 5.978a1.5 1.5 0 0 0 1.572 0L22.5 6.908Z" />
                            </svg>
                            <input type="text" id="usuario" name="usuario" >
                        </div>

                        <div class="campo">
                            <label for="password">Ingrese su contraseña:</label>
                            <svg class="input-icon icon__left" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                fill="currentColor" class="size-6">
                                <path fill-rule="evenodd"
                                    d="M12 1.5a5.25 5.25 0 0 0-5.25 5.25v3a3 3 0 0 0-3 3v6.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-6.75a3 3 0 0 0-3-3v-3c0-2.9-2.35-5.25-5.25-5.25Zm3.75 8.25v-3a3.75 3.75 0 1 0-7.5 0v3h7.5Z"
                                    clip-rule="evenodd" />
                            </svg>
                            <input type="password" id="password" name="password" >

                            <div class="icon_right js-password-toggle">
                                <svg class="input-icon toggle-closed" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M3.53 2.47a.75.75 0 0 0-1.06 1.06l18 18a.75.75 0 1 0 1.06-1.06l-18-18ZM22.676 12.553a11.249 11.249 0 0 1-2.631 4.31l-3.099-3.099a5.25 5.25 0 0 0-6.71-6.71L7.759 4.577a11.217 11.217 0 0 1 4.242-.827c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113Z" />
                                    <path
                                        d="M15.75 12c0 .18-.013.357-.037.53l-4.244-4.243A3.75 3.75 0 0 1 15.75 12ZM12.53 15.713l-4.243-4.244a3.75 3.75 0 0 0 4.244 4.243Z" />
                                    <path
                                        d="M6.75 12c0-.619.107-1.213.304-1.764l-3.1-3.1a11.25 11.25 0 0 0-2.63 4.31c-.12.362-.12.752 0 1.114 1.489 4.467 5.704 7.69 10.675 7.69 1.5 0 2.933-.294 4.242-.827l-2.477-2.477A5.25 5.25 0 0 1 6.75 12Z" />
                                </svg>

                                <svg class="input-icon toggle-open" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
                                    <path fill-rule="evenodd"
                                        d="M1.323 11.447C2.811 6.976 7.028 3.75 12.001 3.75c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113-1.487 4.471-5.705 7.697-10.677 7.697-4.97 0-9.186-3.223-10.675-7.69a1.762 1.762 0 0 1 0-1.113ZM17.25 12a5.25 5.25 0 1 1-10.5 0 5.25 5.25 0 0 1 10.5 0Z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>

                        <button class="boton" type="submit">Entrar</button>

                    </div> <!--Fin contenedor de campos-->
                </form>

                <div class="login-navegacion"> <!--Inicio de navegación-->
                    <a href="/src/pages/recuperar_contrasena.html">¿Has olvidado tu contraseña?</a>

                    
                </div> <!--Final de navegación-->

            </div> <!--Fin formulario-->

        </section> <!--Fin de login-->

    </main>

    <script>
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
    </script>    
</body>
</html>