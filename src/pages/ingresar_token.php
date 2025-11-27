<?php
require_once __DIR__ . '/../config/translation.php';
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('recover_password_title'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=lock" />
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="/build/css/login.css">
</head>

<body class="fondo_login">

    <?php include __DIR__ . '/../includes/language_switcher.php'; ?>

    <main class="contenedor">

        <section class="contenedor-login">

            <div class="formulario">

            </div>


            <div class="formulario">
                <div class="login-logo">
                    <img src="/src/img/Logo_prisma.png">
                </div>
                <H1 class=""><?php echo __('favorite_pos'); ?></H1>

                <h2>Ingrese su token para recuperar contraseña</h2>

                <div class="contenedor-campos">

                    <div class="campo">
                        <label for="email">Ingrese su token:</label>
                        <svg class="input-icon icon__left" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                            <path fill-rule="evenodd"
                                d="M15.75 1.5a6.75 6.75 0 0 0-6.651 7.906c.067.39-.032.717-.221.906l-6.5 6.499a3 3 0 0 0-.878 2.121v2.818c0 .414.336.75.75.75H6a.75.75 0 0 0 .75-.75v-1.5h1.5A.75.75 0 0 0 9 19.5V18h1.5a.75.75 0 0 0 .53-.22l2.658-2.658c.19-.189.517-.288.906-.22A6.75 6.75 0 1 0 15.75 1.5Zm0 3a.75.75 0 0 0 0 1.5A2.25 2.25 0 0 1 18 8.25a.75.75 0 0 0 1.5 0 3.75 3.75 0 0 0-3.75-3.75Z"
                                clip-rule="evenodd" />
                        </svg>
                        <input type="text" id="token" name="token" required placeholder="">
                    </div>

                    <button class="boton" type="submit"><?php echo __('send_button'); ?></button>

                </div>

                <div class="login-navegacion_registrate">
                    <p><?php echo __('have_account'); ?> <a href="/src/pages/login.php"><?php echo __('log_in'); ?></a></p>

                    <a href="#"><?php echo __('home'); ?></a>
                </div>

            </div>

        </section>

        <script src="/src/js/alertaslogin_y_mostrarcontra.js"> </script>
    </main>

</body>

</html>
