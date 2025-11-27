<?php
session_start();
// Si ya hay sesión, redirigir al dashboard
if (isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php?view=nueva_venta");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Punto de Venta</title>
    
    <!-- Estilos -->
    <link rel="stylesheet" href="../styles/login.css">
    
    <!-- Fuentes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    
    <!-- Alertify (Opcional, mantenido por compatibilidad) -->
    <script src="//cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/alertify.min.js"></script>
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/css/alertify.min.css"/>
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/css/themes/default.min.css"/>
</head>

<body class="fondo_login">

    <main class="contenedor">
        <section class="contenedor-login">
            
            <div class="formulario">
                <!-- Espacio vacío o imagen decorativa -->
            </div>

            <div class="formulario">
                <div class="login-logo">
                    <img src="../../public/img/logo2.png" alt="Logo">
                </div>

                <h1 class="login-titulo">Tu punto de venta favorito</h1>

                <!-- Mensajes de sesión PHP (Fallback) -->
                <?php if (!empty($_SESSION['error'])): ?>
                    <div class="alert alert-danger" style="text-align:center; background:#f8d7da; color:#842029; padding:10px; border-radius:8px; margin:10px 0;">
                        <?= htmlspecialchars($_SESSION['error']); ?>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                
                <?php if (!empty($_SESSION['registro_success'])): ?>
                    <div class="alert alert-success" style="text-align:center; background:#d1e7dd; color:#0f5132; padding:10px; border-radius:8px; margin:10px 0;">
                        <?= htmlspecialchars($_SESSION['registro_success']); ?>
                    </div>
                    <?php unset($_SESSION['registro_success']); ?>
                <?php endif; ?>

                <!-- Formulario -->
                <form id="loginFormulario" action="../scripts/validar_login.php" method="POST">
                    <div class="contenedor-campos">
                        
                        <div class="campo">
                            <label for="correo">Ingrese su correo:</label>
                            <div style="position: relative;">
                                <svg class="input-icon icon__left" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 20px; color: #666;">
                                    <path d="M1.5 8.67v8.58a3 3 0 0 0 3 3h15a3 3 0 0 0 3-3V8.67l-8.928 5.493a3 3 0 0 1-3.144 0L1.5 8.67Z" />
                                    <path d="M22.5 6.908V6.75a3 3 0 0 0-3-3h-15a3 3 0 0 0-3 3v.158l9.714 5.978a1.5 1.5 0 0 0 1.572 0L22.5 6.908Z" />
                                </svg>
                                <input type="email" id="correo" name="correo" required style="padding-left: 40px; width: 100%;">
                            </div>
                        </div>

                        <div class="campo">
                            <label for="password">Ingrese su contraseña:</label>
                            <div style="position: relative;">
                                <svg class="input-icon icon__left" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 20px; color: #666;">
                                    <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 0 0-5.25 5.25v3a3 3 0 0 0-3 3v6.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-6.75a3 3 0 0 0-3-3v-3c0-2.9-2.35-5.25-5.25-5.25Zm3.75 8.25v-3a3.75 3.75 0 1 0-7.5 0v3h7.5Z" clip-rule="evenodd" />
                                </svg>
                                <input type="password" id="password" name="password" required style="padding-left: 40px; padding-right: 40px; width: 100%;">
                                
                                <div class="icon_right js-password-toggle" style="position: absolute; right: 20px; top: 20%; transform: translateY(-40%); cursor: pointer;">
                                    <svg class="input-icon toggle-closed" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width: 20px; display: block;">
                                        <path d="M3.53 2.47a.75.75 0 0 0-1.06 1.06l18 18a.75.75 0 1 0 1.06-1.06l-18-18ZM22.676 12.553a11.249 11.249 0 0 1-2.631 4.31l-3.099-3.099a5.25 5.25 0 0 0-6.71-6.71L7.759 4.577a11.217 11.217 0 0 1 4.242-.827c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113Z" />
                                        <path d="M15.75 12c0 .18-.013.357-.037.53l-4.244-4.243A3.75 3.75 0 0 1 15.75 12ZM12.53 15.713l-4.243-4.244a3.75 3.75 0 0 0 4.244 4.243Z" />
                                        <path d="M6.75 12c0-.619.107-1.213.304-1.764l-3.1-3.1a11.25 11.25 0 0 0-2.63 4.31c-.12.362-.12.752 0 1.114 1.489 4.467 5.704 7.69 10.675 7.69 1.5 0 2.933-.294 4.242-.827l-2.477-2.477A5.25 5.25 0 0 1 6.75 12Z" />
                                    </svg>
                                    <svg class="input-icon toggle-open" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width: 20px; display: none;">
                                        <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
                                        <path fill-rule="evenodd" d="M1.323 11.447C2.811 6.976 7.028 3.75 12.001 3.75c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113-1.487 4.471-5.705 7.697-10.677 7.697-4.97 0-9.186-3.223-10.675-7.69a1.762 1.762 0 0 1 0-1.113ZM17.25 12a5.25 5.25 0 1 1-10.5 0 5.25 5.25 0 0 1 10.5 0Z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <button class="boton" type="submit">Entrar</button>

                    </div>
                </form>

                <div class="login-navegacion">
                    <a href="recuperar_contrasena.html">¿Has olvidado tu contraseña?</a>
                </div>

            </div>
        </section>
    </main>

    <!-- Scripts -->
    <!-- Agregamos timestamp para evitar caché -->
    <script src="../scripts/alertaslogin_y_mostrarcontra.js?v=<?= time() ?>"></script>

    <!-- Modal de Éxito -->
    <div id="successModal" class="modal-overlay">
        <div class="modal-content">
            <img src="../../public/img/logo2.png" alt="Logo" class="modal-logo">
            <p class="modal-message">Inicio exitoso</p>
        </div>
    </div>

    <!-- Modal de Error -->
    <div id="errorModal" class="modal-overlay">
        <div class="modal-content">
            <img src="../../public/img/logo2.png" alt="Logo" class="modal-logo">
            <p id="errorMessage" class="modal-message"></p>
            <button id="closeErrorModal" class="boton" style="margin-top: 20px;">Cerrar</button>
        </div>
    </div>

</body>
</html>