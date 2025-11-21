<?php
require_once __DIR__ . '/../config/translation.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang === 'es' ? 'es' : 'en'; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $translations['recover_password_title']; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=lock" />
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../styles/login.css">
</head>

<body class="fondo_login">
    <?php require_once __DIR__ . '/../includes/language_switcher.php'; ?>

    <main class="contenedor">

        <section class="contenedor-login"> <!--Inicio de login-->

            <div class="formulario">

            </div>


            <div class="formulario"> <!--Inicio formulario-->
                <div class="login-logo">
                    <img src="../../public/img/logo2.png">
                </div>
                <H1 class=""><?php echo $translations['favorite_pos']; ?></H1>

                <h2><?php echo $translations['enter_email_to_recover']; ?></h2>

                <form action="../scripts/request_reset.php" method="POST">
                <div class="contenedor-campos"> <!--  Inicio contenedor de campos-->

                    <div class="campo">
                        <label for="email"><?php echo $translations['enter_your_email']; ?></label>
                        <svg class="input-icon icon__left" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                            fill="currentColor" class="size-6">
                            <path
                                d="M1.5 8.67v8.58a3 3 0 0 0 3 3h15a3 3 0 0 0 3-3V8.67l-8.928 5.493a3 3 0 0 1-3.144 0L1.5 8.67Z" />
                            <path
                                d="M22.5 6.908V6.75a3 3 0 0 0-3-3h-15a3 3 0 0 0-3 3v.158l9.714 5.978a1.5 1.5 0 0 0 1.572 0L22.5 6.908Z" />
                        </svg>
                        <input type="email" id="email" name="email" required placeholder="">
                    </div>

                    <button class="boton" type="submit"><?php echo $translations['send_button']; ?></button>

                </div> <!--Fin contenedor de campos-->
                </form>

                <div class="login-navegacion_registrate"> <!--Inicio de navegación-->
                    <p><?php echo $translations['have_account']; ?> <a href="login.php"><?php echo $translations['log_in']; ?></a></p>

                    <a href="#"><?php echo $translations['home']; ?></a>
                </div> <!--Final de navegación-->

            </div> <!--Fin formulario-->

        </section> <!--Fin de login-->

    </main>

</body>

</html>