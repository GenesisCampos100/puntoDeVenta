<?php 
    require_once __DIR__ . "/../config/db.php";


    $estatus = 1;
    if($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $nombre_completo = $_POST['nombre_completo'];
            $correo = $_POST['correo'];
            $password = md5($_POST['password']); // Encriptar la contraseña
            $telefono = $_POST['telefono'];
            $direccion = $_POST['direccion'];
            $estatus = isset($_POST['estatus']) ? (int)$_POST['estatus'] : 0;
            $rol_id = $_POST['rol_id'];

            if ($nombre_completo === '') {
            throw new Exception("El nombre del producto es obligatorio.");
            }

            $stmt = $pdo->prepare("INSERT INTO usuarios(id, nombre_completo, telefono, direccion, correo, password, estatus, fecha, rol_id) 
            VALUES (NULL, ?, ?, ?, ?, ?, ?, NOW(), ?)");
            
            $stmt->execute([$nombre_completo, $telefono, $direccion, $correo, $password, $estatus, $rol_id]);
            header("Location: index.php?view=empleados");
            exit;
        } catch (Exception $e) {
            echo "Error al agregar empleado: " . $e->getMessage(); 
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Empleados</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* --- ESTILOS BASE Y GENERALES --- */
        body {
            background: #f9fafb; 
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif; 
            color: #374151; 
        }

        /* --- TÍTULO PRINCIPAL DE LA VISTA --- */
        h2 {
            text-align: center;
            color: #f43f5e; 
            margin: 40px auto 25px; 
            font-weight: 700; 
            font-size: 28px; 
            letter-spacing: 1.5px; 
            text-transform: uppercase;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 40px;
            height: 22px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #ccc;
            border-radius: 22px;
            transition: .4s;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 14px;
            width: 14px;
            left: 4px;
            top: 4px;
            background-color: white;
            border-radius: 50%;
            transition: .4s;
        }

        input:checked + .slider {
            background-color: #4ade80;
        }

        input:checked + .slider:before {
            transform: translateX(18px);
        }

    </style>
</head>
<body>
    <h2>Registro de Empleados</h2>
    <div style="max-width: 600px; margin: 40px auto; background: #fff; padding: 40px 38px 32px 38px; border-radius: 18px; box-shadow: 0 2px 16px rgba(0,0,0,0.10); position:relative;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;">
            <span style="font-size:22px; font-weight:700; color:#b3c428; font-family:'Poppins',sans-serif;">Datos Básicos</span>
            <span style="font-size:28px; color:#b3c428; cursor:pointer; font-weight:700; line-height:1;" onclick="window.history.back()">&#10005;</span>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <div style="display:flex; flex-direction:column; gap:12px;">
                <label style="font-size:14px; font-weight:500; color:#374151;">Nombre:</label>
                <input type="text" name="nombre_completo" maxlength="50" required style="padding:10px 16px; border:1.5px solid #b3c428; border-radius:8px; font-size:17px; width:100%;">

                <label style="font-size:14px; font-weight:500; color:#374151;">E-mail:</label>
                <input type="email" name="correo" maxlength="100" required style="padding:10px 16px; border:1.5px solid #b3c428; border-radius:8px; font-size:17px; width:100%;">

                <div style="display:flex; gap:10px;">
                    <div style="flex:1;">
                        <label style="font-size:14px; font-weight:500; color:#374151;">Contraseña:</label>
                        <input type="password" name="password" maxlength="255" required style="padding:10px 16px; border:1.5px solid #b3c428; border-radius:8px; font-size:17px; width:100%;">
                    </div>
                    <div style="flex:1;">
                        <label style="font-size:14px; font-weight:500; color:#374151;">Teléfono:</label>
                        <input type="text" name="telefono" maxlength="20" style="padding:10px 16px; border:1.5px solid #b3c428; border-radius:8px; font-size:17px; width:100%;">
                    </div>
                </div>

                <div style="display:flex; gap:10px; align-items:center;">
                    <div style="flex:1;">
                        <label style="font-size:14px; font-weight:500; color:#374151;">Dirección:</label>
                        <input type="text" name="direccion" maxlength="100" style="padding:10px 16px; border:1.5px solid #b3c428; border-radius:8px; font-size:17px; width:100%;">
                    </div>
                    <div style="flex:1; display:flex; align-items:center; gap:8px;">
                        <label style="font-size:14px; font-weight:500; color:#374151;">Estatus:</label>
                        <label class="switch">
                            <input type="hidden" name="estatus" value="0">
                            <input type="checkbox" name="estatus" value="1" <?= ($estatus == 1 ? 'checked' : '') ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

                <div style="display:flex; gap:10px; align-items:center;">
                    <label style="font-size:14px; font-weight:500; color:#374151;">Puesto:</label><br>
                    <select name="rol_id" required style="padding:10px 16px; border:1.5px solid #b3c428; border-radius:8px; font-size:17px; width:100%;">
                        <option value="">Selecciona un rol</option>
                        <option value="1">Admin</option>
                        <option value="2">Gerente</option>
                        <option value="3">Cajero</option>
                        <!-- Agrega más opciones según los roles disponibles -->
                    </select>
                </div>
                
            </div>
            <div style="display:flex; justify-content:center; gap:18px; margin-top:28px;">
                <button type="submit" style="background:#f43f5e; color:#fff; font-weight:600; padding:10px 28px; border:none; border-radius:6px; font-size:15px; cursor:pointer;">Guardar</button>
                <button type="button" onclick="window.history.back()" style="background:#b3c428; color:#fff; font-weight:600; padding:10px 28px; border:none; border-radius:6px; font-size:15px; cursor:pointer;">Cancelar</button>
            </div>
        </form>
    </div>
</body>
</html>