<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=lock" />
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap"
        rel="stylesheet">
    <!-- <link rel="stylesheet" href="../styles/login.css"> -->
    <style>
        @charset "UTF-8";
/*! modern-normalize v3.0.1 | MIT License | https://github.com/sindresorhus/modern-normalize */
/*
Document
========
*/
/**
Use a better box model (opinionated).
*/
*,
::before,
::after {
  box-sizing: border-box;
}

/**
1. Improve consistency of default fonts in all browsers. (https://github.com/sindresorhus/modern-normalize/issues/3)
2. Correct the line height in all browsers.
3. Prevent adjustments of font size after orientation changes in iOS.
4. Use a more readable tab size (opinionated).
*/
html {
  font-family: system-ui, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji"; /* 1 */
  line-height: 1.15; /* 2 */
  -webkit-text-size-adjust: 100%; /* 3 */
  tab-size: 4; /* 4 */
}

/*
Sections
========
*/
/**
Remove the margin in all browsers.
*/
body {
  margin: 0;
}

/*
Text-level semantics
====================
*/
/**
Add the correct font weight in Chrome and Safari.
*/
b,
strong {
  font-weight: bolder;
}

/**
1. Improve consistency of default fonts in all browsers. (https://github.com/sindresorhus/modern-normalize/issues/3)
2. Correct the odd 'em' font sizing in all browsers.
*/
code,
kbd,
samp,
pre {
  font-family: ui-monospace, SFMono-Regular, Consolas, "Liberation Mono", Menlo, monospace; /* 1 */
  font-size: 1em; /* 2 */
}

/**
Add the correct font size in all browsers.
*/
small {
  font-size: 80%;
}

/**
Prevent 'sub' and 'sup' elements from affecting the line height in all browsers.
*/
sub,
sup {
  font-size: 75%;
  line-height: 0;
  position: relative;
  vertical-align: baseline;
}

sub {
  bottom: -0.25em;
}

sup {
  top: -0.5em;
}

/*
Tabular data
============
*/
/**
Correct table border color inheritance in Chrome and Safari. (https://issues.chromium.org/issues/40615503, https://bugs.webkit.org/show_bug.cgi?id=195016)
*/
table {
  border-color: currentcolor;
}

/*
Forms
=====
*/
/**
1. Change the font styles in all browsers.
2. Remove the margin in Firefox and Safari.
*/
button,
input,
optgroup,
select,
textarea {
  font-family: inherit; /* 1 */
  font-size: 100%; /* 1 */
  line-height: 1.15; /* 1 */
  margin: 0; /* 2 */
}

/**
Correct the inability to style clickable types in iOS and Safari.
*/
button,
[type=button],
[type=reset],
[type=submit] {
  -webkit-appearance: button;
}

/**
Remove the padding so developers are not caught out when they zero out 'fieldset' elements in all browsers.
*/
legend {
  padding: 0;
}

/**
Add the correct vertical alignment in Chrome and Firefox.
*/
progress {
  vertical-align: baseline;
}

/**
Correct the cursor style of increment and decrement buttons in Safari.
*/
::-webkit-inner-spin-button,
::-webkit-outer-spin-button {
  height: auto;
}

/**
1. Correct the odd appearance in Chrome and Safari.
2. Correct the outline style in Safari.
*/
[type=search] {
  -webkit-appearance: textfield; /* 1 */
  outline-offset: -2px; /* 2 */
}

/**
Remove the inner padding in Chrome and Safari on macOS.
*/
::-webkit-search-decoration {
  -webkit-appearance: none;
}

/**
1. Correct the inability to style clickable types in iOS and Safari.
2. Change font properties to 'inherit' in Safari.
*/
::-webkit-file-upload-button {
  -webkit-appearance: button; /* 1 */
  font: inherit; /* 2 */
}

/*
Interactive
===========
*/
/*
Add the correct display in Chrome and Safari.
*/
summary {
  display: list-item;
}

html {
  font-size: 62.5%;
  box-sizing: border-box;
  scroll-padding-top: 0rem;
}

body {
  font-family: "Montserrat", sans-serif;
  font-optical-sizing: auto;
  font-style: normal;
  color: #000000;
  font-size: 2rem;
}
body.overflow-hidden {
  overflow: hidden;
}

p {
  color: #000000;
  line-height: 1.5;
}

.contenedor {
  width: 95%;
  max-width: 160rem;
  margin: 0 auto;
  padding: 0 2rem;
}

a {
  text-decoration: none;
}

h1, h2, h3 {
  margin: 0 0 5rem 0;
  font-weight: 900;
}

h1 {
  font-size: 4rem;
}

h2 {
  font-size: 4.6rem;
}

h3 {
  font-size: 6rem;
}

img {
  max-width: 100%;
  width: 100%;
  height: auto;
  display: block;
}

body > section {
  padding: 10rem 0;
}

.fondo_login {
  background-image: url("../../public/img/Fondo_Login.jpg");
  background-size: cover;
  background-repeat: no-repeat;
  background-attachment: fixed;
}

.contenedor-login {
  display: flex;
}

.formulario {
  width: min(60rem, 100%); /*Min utiliza el valor mas pequeño de la pantalla para que se adapte a la pantalla*/
  margin: 0 auto; /*Centrar el formulario*/ /*Utilizarlo cuando sea un formulario y cuando el padre no sea display flex*/
  padding: 5rem;
  border-radius: 1rem; /*Borde redondeado*/
  margin: 0 auto;
  margin-top: 10rem;
  text-align: center;
}
.formulario h1 {
  text-align: center;
  margin-top: 0.5rem;
  font-weight: 400;
  font-size: 2.9rem;
}
.formulario h2 {
  font-weight: 400;
  font-size: 2rem;
}

.contenedor-campos {
  justify-items: center;
}
.contenedor-campos .campo {
  margin-bottom: 1.5rem;
  text-align: center;
  appearance: none;
  width: 95%;
  position: relative;
}
.contenedor-campos label {
  color: #000000;
  font-weight: bold; /*Engorda la letra*/
  margin-bottom: 0.5rem; /*Margen inferior*/
  display: block; /*Hace que el label ocupe todo el ancho*/
  text-align: left;
}
.contenedor-campos input {
  width: 100%;
  height: 4.8rem;
  border-radius: 1rem;
  padding-left: 4.5rem;
  padding-right: 4.5rem;
  background-color: #EAEAEA;
  border: none;
}
.contenedor-campos input::placeholder {
  color: #E54F6D;
}

.input-icon {
  position: absolute;
  width: 2.5rem;
  top: 50%;
  height: 2.5rem;
  color: #E54F6D;
  pointer-events: none; /* Asegura que el ícono no interfiera con la interacción del input */
}

.icon__left {
  left: 1.3rem;
}

.icon_right {
  right: 1.3rem;
  position: absolute;
  right: 2.5rem;
  top: 35%;
  width: 2.5rem;
  height: 2.5rem;
  cursor: pointer;
  z-index: 1rem;
}

.icon_right .input-icon {
  pointer-events: none;
  position: absolute;
  top: 50%;
  left: 50%;
}
.icon_right .toggle-open {
  display: none;
}

.campo.show-password .toggle-closed {
  display: none;
}
.campo.show-password .toggle-open {
  display: block;
}

.boton {
  background-color: #EAEAEA; /*Color del boton*/
  text-decoration: none; /*Quita el subrayado del boton*/
  color: #E54F6D; /*Color de la letra
padding: 1rem 3rem; /*Padding es el espacio dentro del boton*/
  margin-top: 1rem; /*Margen superior*/
  font-size: 2rem; /*Tamaño de la letra*/
  text-transform: uppercase; /*Pone las letras en mayusculas*/
  font-weight: bold; /*Engorda la letra*/
  border-radius: 0.5rem; /*Borde redondeado*/
  text-align: center; /*Centrar el texto*/
  width: 95%; /*Ancho del boton*/
  padding: 1rem 1.5rem; /*Padding es el espacio dentro del boton*/
  border: none; /*Quita el borde del boton*/
}
.boton:hover {
  background-color: #2C4251; /*Color del boton al pasar el mouse*/
  color: #B6C649; /*Color de la letra al pasar el mouse*/
  cursor: pointer; /*Cursor de mano al pasar el mouse*/
}

.login-navegacion {
  margin-top: 2rem;
}
.login-navegacion a {
  color: #E54F6D;
}

.registrate {
  width: min(60rem, 100%); /*Min utiliza el valor mas pequeño de la pantalla para que se adapte a la pantalla*/
  margin: 0 auto; /*Centrar el formulario*/ /*Utilizarlo cuando sea un formulario y cuando el padre no sea display flex*/
  padding: 3.5rem;
  margin-top: 0.5rem;
  text-align: center;
}
.registrate h1 {
  font-weight: 400;
  font-size: 2.9rem;
}

.login-logo img {
  height: auto;
  width: 90%;
}

.login-navegacion_registrate {
  /*Fletbox se utiliza en el elemento padre*/
  display: flex;
  flex-direction: column; /*Flexdirection permite columnas o filas*/
  /*Flexdirection permite columnas o filas*/
  align-items: center; /*Alinear horizontalmente*/
  margin-top: 1rem;
}
.login-navegacion_registrate a {
  margin: 1rem auto;
  color: #E54F6D;
}
    </style>
</head>
<body class="fondo_login">

    <main class="contenedor">

        <section class="contenedor-login"> <!--Inicio de login-->

            <div class="formulario">

            </div>
<<<<<<< HEAD
            <H1 class="login-titulo">Tu punto de venta favorito</H1>
            
            <form action="../scripts/validar_login.php" method="POST">
=======
>>>>>>> origin/Mario


            <div class="formulario "> <!--Inicio formulario-->
                <div class="login-logo">
                    <img src="../../public/img/logo2.png">
                </div>
                <H1 class="login-titulo">Tu punto de venta favorito</H1>

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
                            <input type="text" id="usuario" name="usuario" required placeholder="">
                        </div>

                        <div class="campo">
                            <label for="password">Ingrese su contraseña:</label>
                            <svg class="input-icon icon__left" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                fill="currentColor" class="size-6">
                                <path fill-rule="evenodd"
                                    d="M12 1.5a5.25 5.25 0 0 0-5.25 5.25v3a3 3 0 0 0-3 3v6.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-6.75a3 3 0 0 0-3-3v-3c0-2.9-2.35-5.25-5.25-5.25Zm3.75 8.25v-3a3.75 3.75 0 1 0-7.5 0v3h7.5Z"
                                    clip-rule="evenodd" />
                            </svg>
                            <input type="password" id="password" name="password" required placeholder="">

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

        <script src="/src/js/mostrar_contra.js"> </script>
        <script src="/src/js/alertas_login.js"></script>
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