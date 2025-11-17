<?php
// Incluir el sistema de traducción al principio de todo.
// La sesión se inicia dentro de translation.php si es necesario.
require_once __DIR__ . '/../config/translation.php';
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('login_title'); ?></title>
    <link rel="stylesheet" href="../styles/login.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=lock" />
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../styles/login.css">
    <style>
        /* Estilos para el selector de idioma */
        .language-selector {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: rgba(0, 0, 0, 0.3);
            padding: 5px;
            border-radius: 8px;
            display: flex;
            gap: 5px;
        }
        .language-selector a {
            color: white;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 14px;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        .language-selector a.active {
            border-color: #FFC107; /* Borde amarillo */
            box-shadow: 0 0 10px rgba(255, 193, 7, 0.7); /* Sombra iluminada */
            background-color: rgba(255, 193, 7, 0.2);
        }
        .language-selector a:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
    </style>
</head>

<body class="fondo_login">

    <!-- Selector de Idioma -->
    <div class="language-selector">
        <a href="?lang=es" class="<?php echo ($lang === 'es') ? 'active' : ''; ?>">ES</a>
        <a href="?lang=en" class="<?php echo ($lang === 'en') ? 'active' : ''; ?>">EN</a>
    </div>

    <?php
    // Mostrar error de sesión si existe
    // session_start() ya se llamó al inicio del archivo; no llamarlo de nuevo para evitar warning
    if (!empty($_SESSION['error'])) {
        echo '<div class="alert alert-error">' . htmlspecialchars($_SESSION['error']) . '</div>';
        unset($_SESSION['error']);
    }
    if (!empty($_SESSION['registro_success'])) {
        echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['registro_success']) . '</div>';
        unset($_SESSION['registro_success']);
    }
    if (!empty($_SESSION['info'])) {
        echo '<div class="alert alert-info">' . htmlspecialchars($_SESSION['info']) . '</div>';
        unset($_SESSION['info']);
    }
    ?>

    <main class="contenedor">

        <section class="contenedor-login"> <!--Inicio de login-->

            <div class="formulario">

            </div>


            <div class="formulario "> <!--Inicio formulario-->
                <div class="login-logo">
                    <img src="../imagenesDev/logo2.png">
                </div>
                <h2><?php echo __('login_welcome'); ?></h2>

                    <form id="loginFormulario" action="../scripts/validar_login.php" method="post">
                    <div class="contenedor-campos"> <!--Inicio contenedor de campos-->

                        <div class="campo">
                            <label for="usuario"><?php echo __('login_email_label'); ?></label>
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
                            <label for="password"><?php echo __('login_password_label'); ?></label>
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

                        <button class="boton" type="submit"><?php echo __('login_button_text'); ?></button>

                    </div> <!--Fin contenedor de campos-->
                </form>

                <div class="login-navegacion"> <!--Inicio de navegación-->
                <a href="recuperar_contrasena.html"><?php echo __('login_forgot_password'); ?></a>

                </div> <!--Final de navegación-->

            </div> <!--Fin formulario-->

        </section> <!--Fin de login-->

                <script src="../scripts/show_password.js"></script>
                <script src="../scripts/alertaslogin_y_mostrarcontra.js"></script>

    </main>

    <!-- Modal de Éxito -->
    <div id="successModal" class="modal-overlay">
        <div class="modal-content">
            <img src="../imagenesDev/logo2.png" alt="Logo" class="modal-logo">
            <p class="modal-message">Inicio exitoso</p>
        </div>
    </div>

    <!-- Modal de Error -->
    <div id="errorModal" class="modal-overlay">
        <div class="modal-content">
            <img src="../imagenesDev/logo2.png" alt="Logo" class="modal-logo">
            <p id="errorMessage" class="modal-message"></p>
            <button id="closeErrorModal" class="boton" style="margin-top: 20px;">Cerrar</button>
        </div>
    </div>
</body>
</html>