<?php
session_start();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - Punto de Venta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: #f0f2f5;
        }
        .login-box {
            width: 400px;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0px 4px 12px rgba(0,0,0,0.15);
        }
        .login-box h2 {
            text-align: center;
            margin-bottom: 25px;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Iniciar Sesión</h2>

        <form action="validar_login.php" method="POST">
            <!-- Usuario -->
            <div class="mb-3">
                <label for="usuario" class="form-label">Usuario</label>
                <input type="text" id="usuario" name="usuario" class="form-control" required>
            </div>

            <!-- Contraseña -->
            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>

            <!-- Botón -->
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Ingresar</button>
            </div>
        </form>
    </div>
</body>
</html>
