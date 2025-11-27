<?php 
    require_once __DIR__ . "/../config/db.php";
    require_once __DIR__ . "/../config/translation.php";

    $id_empleado = '';

    // Obtener el id del empleado desde GET (al cargar) o desde POST (al enviar el formulario)
    $empleado_id = $_GET['id'] ?? ($_POST['actual_empleado'] ?? null);

    if($empleado_id) {
        $stmt = $pdo->prepare("SELECT e.*, u.* FROM empleados e 
                               INNER JOIN usuarios u ON e.id_empleado = u.id_empleado 
                               WHERE e.id_empleado = ?");
        $stmt->execute([$empleado_id]);
        $empleado = $stmt->fetch(PDO::FETCH_ASSOC); 
        
        if (!$empleado) {
            echo json_encode(["error" => "Empleado no encontrado.", "icon" => "error"]);
            exit();
        }
    }

    $estatus = (int)($empleado['estatus'] ?? 0);

    // Traer los roles
    $stmt = $pdo->query("SELECT * FROM roles");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Validación: comprobar que haya roles disponibles
    if (!$roles) {
        echo json_encode(["error" => "No hay roles disponibles.", "icon" => "error"]);
        exit();
    }

    // Obtener el rol actual
    $rol_actual_id = $empleado['id_rol'] ?? '';
    $rol_actual_nombre = '';
    foreach ($roles as $rol) {
        if ($rol['id_rol'] == $rol_actual_id) {
            $rol_actual_nombre = $rol['nombre_rol'];
            break;
        }
    }
      
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $campos_obligatorios = [
                'apellido_p' => 'Apellido Paterno',
                'nombres' => 'Nombre',
                'correo' => 'Correo',
                'telefono' => 'Teléfono',
                'calle' => 'Calle',
                'num_ext' => 'Número Exterior',
                'colonia' => 'Colonia',
                'estado' => 'Estado',
            ];

            foreach ($campos_obligatorios as $campo => $etiqueta) {
                if (empty($_POST[$campo])) {
                    echo json_encode(["error" => "El campo $etiqueta es obligatorio. Por favor, complételo.", "icon" => "warning"]);
                    exit;
                }
            }

            /* --- Validar nombre y apellidos --- */
            $nombre = trim(filter_input(INPUT_POST, 'nombres', FILTER_SANITIZE_STRING));
            $apellido_paterno = trim(filter_input(INPUT_POST, 'apellido_p', FILTER_SANITIZE_STRING));
            $apellido_materno = trim(filter_input(INPUT_POST, 'apellido_m', FILTER_SANITIZE_STRING));

            $regexNombre = "/^[A-Za-zÁÉÍÓÚáéíóúÑñ\\s]+$/u";
            if (!preg_match($regexNombre, $nombre) || !preg_match($regexNombre, $apellido_paterno) || !preg_match($regexNombre, $apellido_materno)) {
                echo json_encode(["error" => "Los nombres y apellidos solo deben contener letras.", "icon" => "warning"]);
                exit;
            }

            /* --- Validar correo --- */
            $correo = trim(filter_input(INPUT_POST, 'correo', FILTER_SANITIZE_EMAIL));
            if(!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(["error" => "Por favor, ingresa una dirección de correo electrónico valido.", "icon" => "warning"]);
                exit;
            } else {
                try {
                    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo = :correo AND id_empleado != :id_emp");
                    $stmt->execute(['correo' => $correo, 'id_emp' => $empleado_id]);
                    $existe = $stmt->fetchColumn();

                    if ($existe > 0) {
                        echo json_encode(["error" => "El correo electrónico ya está registrado. Por favor, utiliza otro.", "icon" => "error"]);
                        exit;
                    }
                } catch (PDOException $e) {
                    echo json_encode(["error" => "Error al verificar el correo electrónico: " . $e->getMessage(), "icon" => "error"]);
                    exit;
                }
            }

            /* --- Validar y cifrar la contraseña --- */
            $contra = $_POST['contra'] ?? '';

            // Si la contraseña viene vacía se interpretará como "no cambiar".
            if (!empty($contra)) {
                if (strlen($contra) < 8) {
                    echo json_encode(["error" => "La contraseña debe tener al menos 8 caracteres.", "icon" => "warning"]);
                    exit;
                }

                if (!preg_match('/[A-Z]/', $contra)) {
                    echo json_encode(["error" => "La contraseña debe contener al menos un carácter en mayúscula.", "icon" => "warning"]);
                    exit;
                } else if (!preg_match('/[a-z]/', $contra)) {
                    echo json_encode(["error" => "La contraseña debe contener al menos un carácter en minúscula.", "icon" => "warning"]);
                    exit;
                } else if (!preg_match('/[0-9]/', $contra)) {
                    echo json_encode(["error" => "La contraseña debe contener al menos un número.", "icon" => "warning"]);
                    exit;
                }
            }

            /* --- Validar telefono --- */
            $telefono = trim(filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_STRING));

            $regexTelefono = "/^[0-9]{10}$/";
            if (!preg_match($regexTelefono, $telefono)) { 
                echo json_encode(["error" => "El número de teléfono debe contener dígitos numéricos.", "icon" => "warning"]);
                exit;
            }

            /* --- Validar domicilio --- */
            $calle = trim(filter_input(INPUT_POST, 'calle', FILTER_SANITIZE_STRING));
            $num_ext = trim(filter_input(INPUT_POST, 'num_ext', FILTER_SANITIZE_STRING));
            $num_int = trim(filter_input(INPUT_POST, 'num_int', FILTER_SANITIZE_STRING));
            $colonia = trim(filter_input(INPUT_POST, 'colonia', FILTER_SANITIZE_STRING));
            $cp = trim(filter_input(INPUT_POST, 'cp', FILTER_SANITIZE_STRING));
            $estado = trim(filter_input(INPUT_POST, 'estado', FILTER_SANITIZE_STRING));

            $regexLetras = "/^[A-Za-zÁÉÍÓÚáéíóúÑñ\\s]+$/u";
            $regexAlfanumerico = "/^[A-Za-z0-9\\s]+$/u";  
            $regexCP = "/^[0-9]{5}$/";

            $errores = [];

            if (!preg_match($regexAlfanumerico, $calle)) {
                $errores[] = "Calle inválida."; 
            } elseif (!preg_match($regexAlfanumerico, $num_ext)) {
                $errores[] = "Número exterior inválido.";
            } elseif ($num_int !== "" && !preg_match($regexAlfanumerico, $num_int)) {
                $errores[] = "Número interior inválido.";
            } elseif (!preg_match($regexAlfanumerico, $colonia)) {
                $errores[] = "Colonia inválida.";
            } elseif ($cp !== "" && !preg_match($regexCP, $cp)) {
                $errores[] = "Código postal inválido.";
            } elseif (!preg_match($regexLetras, $estado)) {
                $errores[] = "Estado inválido.";
            }

            if (count($errores) > 0) {
                echo json_encode(["error" => $errores[0], "icon" => "warning"]);
                exit;
            }

            /* --- Validar estatus --- */
            $estatus = isset($_POST['estatus']) ? (int)$_POST['estatus'] : 0;

            /* --- Validar puesto --- */
            $id_rol = filter_input(INPUT_POST, 'id_rol', FILTER_SANITIZE_NUMBER_INT);
            // Si no se envía un puesto, mantener el puesto actual
            if ($id_rol === null || $id_rol === false || $id_rol === '') {
                $id_rol = $rol_actual_id !== '' ? (int)$rol_actual_id : null;
            } else {
                $id_rol = (int)$id_rol;
            }

            /* --- Validar numero de empleado --- */
            $nuevo_id_empleado = trim(filter_input(INPUT_POST, 'num_empleado', FILTER_SANITIZE_STRING));
            // Si no se proporciona un nuevo número, conservar el número actual
            if ($nuevo_id_empleado === '' || $nuevo_id_empleado === null) {
                $nuevo_id_empleado = $empleado_id;
            }

            try {
                // Comprobar si el nuevo id de empleado ya existe en otro registro (excluyendo el actual)
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM empleados WHERE id_empleado = :id_empleado AND id_empleado != :current_id");
                $stmt->execute(['id_empleado' => $nuevo_id_empleado, 'current_id' => $empleado_id]);
                $existeEmpleado = (int)$stmt->fetchColumn();

                if ($existeEmpleado > 0) {
                    echo json_encode(["error" => "El número de empleado ya está registrado. Por favor, utiliza otro.", "icon" => "error"]);
                    exit;
                }
            } catch (PDOException $e) {
                echo json_encode(["error" => "Error al verificar el número de empleado: " . $e->getMessage(), "icon" => "error"]);
                exit;
            }

            $original_id = $empleado_id;

            $pdo->beginTransaction();
            // Actualizar usuarios primero
            if (empty($contra)) {
                $sql_2 = "UPDATE usuarios SET correo = :correo WHERE id_empleado = :id_empleado";
                $stmt_2 = $pdo->prepare($sql_2);
                $stmt_2->execute([
                    'correo' => $correo,
                    'id_empleado' => $original_id
                ]);
            } else {
                $hash = password_hash($contra, PASSWORD_DEFAULT);
                $sql_2 = "UPDATE usuarios SET correo = :correo, contrasena = :contrasena WHERE id_empleado = :id_empleado";
                $stmt_2 = $pdo->prepare($sql_2);
                $stmt_2->execute([
                    'correo' => $correo,
                    'contrasena' => $hash,
                    'id_empleado' => $original_id
                ]);
            }

            // Ahora actualizar empleados (puede cambiar id_empleado)
            $sql = "UPDATE empleados 
                    SET id_empleado = :id_empleado, nombre = :nombre, apellido_paterno = :apellido_paterno, apellido_materno = :apellido_materno, celular = :celular, 
                    calle = :calle, num_ext = :num_ext, num_int = :num_int, colonia = :colonia, cp = :cp, estado = :estado, estatus = :estatus, fecha = NOW(), id_rol = :id_rol
                    WHERE id_empleado = :id_emp";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'id_empleado' => $nuevo_id_empleado,
                'nombre' => $nombre,
                'apellido_paterno' => $apellido_paterno,
                'apellido_materno' => $apellido_materno,
                'celular' => $telefono,
                'calle' => $calle,
                'num_ext' => $num_ext,
                'num_int' => $num_int,
                'colonia' => $colonia,
                'cp' => $cp,
                'estado' => $estado,
                'estatus' => $estatus,
                'id_rol' => $id_rol,
                'id_emp' => $original_id
            ]);

            $pdo->commit();
            echo json_encode(["success" => "Empleado actualizado correctamente.", "redirect" => "index.php?view=empleados", "icon" => "success"]);
            exit();
        } catch (Exception $e) {
            echo json_encode(["error" => "Error al actualizar el empleado: " . $e->getMessage(), "icon" => "error"]);
            exit();
        }
    }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Empleado</title>
    
    <!-- Poppins Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    },
                },
            },
        }
    </script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <style>
        :root {
            --primary: #b4c24d;
            --primary-dark: #9fb03d;
            --secondary: #2d4353;
            --accent: #e15871;
            --error: #ef4444;
            --success: #10b981;
            --text-primary: #1e2d38;
            --text-tertiary: #64748b;
            --font: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        body {
            font-family: var(--font);
            background: linear-gradient(135deg, #f9fafb 0%, #eeeeee 100%);
            min-height: 100vh;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-8px); }
            20%, 40%, 60%, 80% { transform: translateX(8px); }
        }

        .animate-in { animation: fadeIn 0.6s ease-out; }
        .animate-in-delay-1 { animation: slideDown 0.5s ease-out 0.1s both; }
        .animate-in-delay-2 { animation: slideUp 0.5s ease-out 0.2s both; }
        .animate-in-delay-3 { animation: slideUp 0.5s ease-out 0.3s both; }

        .form-container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 2.5rem 1.5rem;
        }

        .form-card {
            background: white;
            border-radius: 1.5rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .form-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid rgba(208, 208, 208, 0.4);
            background: linear-gradient(135deg, #2d4353 0%, #3a5468 100%);
        }

        .form-header h1 {
            font-size: 1.25rem;
            font-weight: 600;
            color: white;
            letter-spacing: -0.01em;
            margin: 0;
        }

        .form-header p {
            font-size: 0.9375rem;
            color: rgba(255, 255, 255, 0.85);
            margin-top: 0.25rem;
            font-weight: 400;
        }

        .form-body {
            padding: 2rem;
        }

        .section {
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.375rem;
            font-weight: 600;
            color: var(--text-primary);
            letter-spacing: -0.01em;
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--primary);
            display: inline-block;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            letter-spacing: -0.005em;
        }

        .form-label .required {
            color: var(--accent);
            margin-left: 0.25rem;
        }

        .form-input, .form-select {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 0.9375rem;
            font-family: var(--font);
            color: var(--text-primary);
            background: #f8f8f8;
            border: 1.5px solid #d8d8d8;
            border-radius: 10px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            outline: none;
            font-weight: 500;
        }

        .form-input::placeholder {
            color: var(--text-tertiary);
            font-weight: 400;
        }

        .form-input:focus, .form-select:focus {
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(180, 194, 77, 0.08);
        }

        .form-input.error, .form-select.error {
            border-color: var(--error);
            background: #fef2f2;
            animation: shake 0.4s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
        }

        .form-input.error:focus, .form-select.error:focus {
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.08);
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1.25rem;
        }

        @media (max-width: 768px) {
            .grid-2, .grid-3 {
                grid-template-columns: 1fr;
            }
        }

        .switch-container {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 52px;
            height: 28px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #cbd5e1;
            border-radius: 28px;
            transition: 0.3s;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            border-radius: 50%;
            transition: 0.3s;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        input:checked + .slider {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        }

        input:checked + .slider:before {
            transform: translateX(24px);
        }

        .switch-label {
            font-size: 0.9rem;
            font-weight: 500;
            color: #6b7280;
        }

        .form-footer {
            padding: 1.25rem 2rem;
            border-top: 1px solid rgba(208, 208, 208, 0.4);
            background: #f5f5f5;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .btn {
            padding: 0.75rem 1.75rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9375rem;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            font-family: var(--font);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(180, 194, 77, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(180, 194, 77, 0.4);
        }

        .btn-secondary {
            background: #e5e7eb;
            color: #374151;
        }

        .btn-secondary:hover {
            background: #d1d5db;
        }

        .btn-change-password {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        .btn-change-password:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .loading-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
         /* Títulos del formulario (Registro de Empleados) */
body.dark-mode .form-header h1,
body.dark-mode .form-header p,
body.dark-mode .section-title {
  color: #ffffff !important;
}
/* Formularios en modo oscuro */
body.dark-mode .form-card,
body.dark-mode .form-body,
body.dark-mode .section {
  background-color: #1f1f1f !important;
  color: #f5f5f5 !important;
}
/* Inputs */
body.dark-mode .form-input,
body.dark-mode .form-select {
  background-color: #2a2a2a !important;
  color: #ffffff !important;
  border-color: #444 !important;
}

/* Placeholders */
body.dark-mode .form-input::placeholder,
body.dark-mode .form-select::placeholder {
  color: #cccccc !important;
}

/* Labels */
body.dark-mode .form-label,
body.dark-mode label {
  color: #eaeaea !important;
}
body.dark-mode .switch-label {
  color: #eaeaea !important;
}

body.dark-mode .slider {
  background-color: #444 !important;
}
body.dark-mode .form-footer {
  background-color: #1a1a1a !important;
  border-top: 1px solid #333 !important;
}
/* Botón Guardar Empleado → verde en modo oscuro */
body.dark-mode .btn-primary {
  background-color:  #b4c24d  !important; /* verde */
  color: white !important;
  border: none !important;
}
/* Botón Guardar Empleado → verde en modo oscuro */
body.dark-mode .btn-primary:hover {
  background-color:  #b4c24d  !important; /* verde */
  
}
body.dark-mode main,
body.dark-mode .content {
    background-color: #121212 !important;
}
    </style>
    
</head>
<body>
    <div class="form-container animate-in">
        <div class="form-card">
            <!-- Header -->
            <div class="form-header animate-in-delay-1">
                <h1><?= __('edit_employee_title') ?></h1>
                <p><?= __('edit_employee_subtitle') ?></p>
            </div>

            <!-- Form -->
            <form id="editar" action="index.php?view=editar_empleado" method="POST" enctype="multipart/form-data">
                <div class="form-body">
                    <!-- Información Personal -->
                    <div class="section animate-in-delay-2">
                        <h2 class="section-title"><?= __('personal_information') ?></h2>
                        
                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label"><?= __('paternal_surname') ?><span class="required">*</span></label>
                                <input type="text" name="apellido_p" maxlength="50" class="form-input" value="<?= htmlspecialchars($empleado['apellido_paterno'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?= __('maternal_surname') ?></label>
                                <input type="text" name="apellido_m" maxlength="50" class="form-input" value="<?= htmlspecialchars($empleado['apellido_materno'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label"><?= __('names') ?><span class="required">*</span></label>
                            <input type="text" name="nombres" maxlength="50" class="form-input" value="<?= htmlspecialchars($empleado['nombre'] ?? '') ?>">
                        </div>

                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label"><?= __('email') ?><span class="required">*</span></label>
                                <input type="email" name="correo" maxlength="100" class="form-input" value="<?= htmlspecialchars($empleado['correo'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?= __('phone') ?><span class="required">*</span></label>
                                <input type="text" name="telefono" maxlength="10" class="form-input" placeholder="<?= __('phone_10_digits') ?>" value="<?= htmlspecialchars($empleado['celular'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label"><?= __('password') ?></label>
                                <input id="contra" type="password" name="contra" maxlength="255" class="form-input" placeholder="<?= __('leave_empty_no_change') ?>" disabled>
                            </div>
                            <div class="form-group" style="display: flex; align-items: flex-end;">
                                <button type="button" id="btnCambiarContra" class="btn btn-change-password"><?= __('change_password_btn') ?></button>
                            </div>
                        </div>
                    </div>

                    <!-- Dirección -->
                    <div class="section animate-in-delay-3">
                        <h2 class="section-title"><?= __('address') ?></h2>
                        
                        <div class="grid-3">
                            <div class="form-group" style="grid-column: span 2;">
                                <label class="form-label"><?= __('street') ?><span class="required">*</span></label>
                                <input type="text" name="calle" maxlength="100" class="form-input" value="<?= htmlspecialchars($empleado['calle'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?= __('ext_num') ?><span class="required">*</span></label>
                                <input type="text" name="num_ext" maxlength="10" class="form-input" value="<?= htmlspecialchars($empleado['num_ext'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="grid-3">
                            <div class="form-group">
                                <label class="form-label"><?= __('int_num') ?></label>
                                <input type="text" name="num_int" maxlength="10" class="form-input" value="<?= htmlspecialchars($empleado['num_int'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?= __('colony') ?><span class="required">*</span></label>
                                <input type="text" name="colonia" maxlength="100" class="form-input" value="<?= htmlspecialchars($empleado['colonia'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?= __('zip_code') ?></label>
                                <input type="text" name="cp" maxlength="5" class="form-input" placeholder="<?= __('zip_code_5_digits') ?>" value="<?= htmlspecialchars($empleado['cp'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label"><?= __('state') ?><span class="required">*</span></label>
                            <input type="text" name="estado" maxlength="100" class="form-input" value="<?= tr_content(htmlspecialchars($empleado['estado'] ?? '')) ?>">
                        </div>
                    </div>

                    <!-- Información Laboral -->
                    <div class="section animate-in-delay-3">
                        <h2 class="section-title"><?= __('work_information') ?></h2>
                        
                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label"><?= __('position') ?><span class="required">*</span></label>
                                <select id="id_rol" name="id_rol" class="form-select">
                                    <option value=""><?= __('select_position') ?></option>
                                    <?php foreach ($roles as $rol): ?>
                                        <?php if ($rol['id_rol'] != $rol_actual_id): ?>
                                            <option value="<?= $rol['id_rol'] ?>">
                                                <?= htmlspecialchars($rol['nombre_rol']) ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?= __('current_employee_number') ?></label>
                                <input id="actual_empleado" type="text" name="actual_empleado" value="<?= htmlspecialchars($empleado['id_empleado'] ?? '') ?>" class="form-input" readonly>
                            </div>
                        </div>

                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label"><?= __('new_employee_number') ?></label>
                                <input id="num_empleado" type="text" name="num_empleado" value="<?php echo htmlspecialchars($id_empleado); ?>" class="form-input" readonly>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?= __('status') ?></label>
                                <div class="switch-container">
                                    <label class="switch">
                                        <input type="hidden" name="estatus" value="0">
                                        <input type="checkbox" name="estatus" value="1" <?= ($estatus == 1 ? 'checked' : '') ?>>
                                        <span class="slider"></span>
                                    </label>
                                    <span class="switch-label"><?= __('employee_status_active') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="form-footer">
                    <button type="button" id="btnCancelar" class="btn btn-secondary"><?= __('cancel') ?></button>
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 1.25rem; height: 1.25rem;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="btn-text"><?= __('update_employee') ?></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Form submission with AJAX (ORIGINAL LOGIC PRESERVED)
        $(document).ready(function() {
            $('#editar').on('submit', function(e) {
               e.preventDefault();
                
                // Clear previous errors
                document.querySelectorAll('.form-input, .form-select').forEach(input => {
                    input.classList.remove('error');
                    input.style.animation = 'none';
                    setTimeout(() => { input.style.animation = ''; }, 10);
                });

                // Client-side validation for required fields
                const requiredFields = [
                    { name: 'apellido_p', label: 'Apellido Paterno' },
                    { name: 'nombres', label: 'Nombre' },
                    { name: 'correo', label: 'Correo' },
                    { name: 'telefono', label: 'Teléfono' },
                    { name: 'calle', label: 'Calle' },
                    { name: 'num_ext', label: 'Número Exterior' },
                    { name: 'colonia', label: 'Colonia' },
                    { name: 'estado', label: 'Estado' }
                ];

                let hasErrors = false;
                const errors = [];

                requiredFields.forEach(field => {
                    const input = document.querySelector(`[name="${field.name}"]`);
                    if (input && input.value.trim() === '') {
                        input.classList.add('error');
                        hasErrors = true;
                        errors.push(`El campo ${field.label} es obligatorio`);
                    }
                });

                if (hasErrors) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Formulario incompleto',
                        html: errors.join('<br>'),
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#b4c24d'
                    });
                    return false;
                }

                const submitBtn = this.querySelector('button[type="submit"]');
                const btnText = submitBtn.querySelector('.btn-text');
                const originalText = btnText.textContent;
                
                submitBtn.disabled = true;
                btnText.innerHTML = '<span class="loading-spinner"></span> Actualizando...';

                $.ajax({
                    url: "index.php?view=editar_empleado",
                    type: "POST",
                    data: $(this).serialize(),
                    success: function(response) {
                        try {
                            const res = JSON.parse(response);
                            if (res.success) {
                                Swal.fire({
                                    title: res.success,
                                    icon: res.icon || 'success',
                                    showConfirmButton: false,
                                    timer: 1500
                                }).then(() => {
                                    if (res.redirect) {
                                        window.location.href = res.redirect;
                                    }
                                });
                            } else if (res.error) {
                                Swal.fire({
                                    title: res.error,
                                    icon: res.icon || 'error',
                                    showConfirmButton: true
                                });
                                submitBtn.disabled = false;
                                btnText.textContent = originalText;
                            }
                        } catch (e) {
                            console.error("Error al procesar JSON: ", e, response);
                            Swal.fire({
                                title: 'Error',
                                text: 'Ocurrió un error al procesar la respuesta',
                                icon: 'error',
                                showConfirmButton: true
                            });
                            submitBtn.disabled = false;
                            btnText.textContent = originalText;
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error: ", status, error);
                        Swal.fire({
                            title: 'Error de conexión',
                            text: 'No se pudo conectar con el servidor',
                            icon: 'error',
                            showConfirmButton: true
                        });
                        submitBtn.disabled = false;
                        btnText.textContent = originalText;
                    }
                });
            });
        });

        // Auto-generate employee number (ORIGINAL LOGIC PRESERVED)
        document.addEventListener('DOMContentLoaded', function () {
            const rolSelect = document.getElementById('id_rol');
            const numInput = document.getElementById('num_empleado');

            if (!rolSelect || !numInput) return;

            async function fetchNext(idRol, currentId = null) {
                if (currentId) {
                    numInput.value = currentId;
                    return;
                }

                if (!idRol) {
                    console.warn("No se seleccionó un rol válido.");
                    return;
                }

                try {
                    const resp = await fetch('scripts/next_employee.php?id_rol=' + encodeURIComponent(idRol));
                    if (!resp.ok) throw new Error('Error en la petición');
                    const data = await resp.json();
                    if (data && data.next) numInput.value = data.next;
                } catch (e) {
                    console.error("Error al obtener el siguiente ID de empleado:", e);
                }
            }

            rolSelect.addEventListener('change', function () {
                if (!numInput.dataset.editing) numInput.value = '';
                fetchNext(this.value);
            });

            // Si hay un valor seleccionado al cargar, pedir el siguiente
            if (numInput.value) {
                numInput.dataset.editing = 'true';
            } else if (rolSelect.value) {
                fetchNext(rolSelect.value);
            }

            // Change password logic (ORIGINAL LOGIC PRESERVED)
            const btnCambiarContra = document.getElementById('btnCambiarContra');
            const contraInput = document.getElementById('contra');
            if (btnCambiarContra && contraInput) {
                btnCambiarContra.addEventListener('click', async function (e) {
                    e.preventDefault();

                    // Si el campo ya está habilitado, actuar como "Cancelar cambio"
                    if (!contraInput.disabled) {
                        contraInput.disabled = true;
                        contraInput.value = '';
                        btnCambiarContra.textContent = 'Cambiar Contraseña';
                        return;
                    }

                    // Pedir la contraseña del usuario que autoriza
                    const { value: password } = await Swal.fire({
                        title: 'Ingresa tu contraseña',
                        input: 'password',
                        inputLabel: 'Contraseña',
                        inputPlaceholder: 'Ingresa tu contraseña',
                        inputAttributes: {
                            maxlength: '100',
                            autocapitalize: 'off',
                            autocorrect: 'off'
                        },
                        showCancelButton: true,
                        confirmButtonText: 'Confirmar',
                        cancelButtonText: 'Cancelar'
                    });

                    if (!password) return;

                    try {
                        const resp = await fetch('scripts/verify_password.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                            body: new URLSearchParams({ password })
                        });

                        if (!resp.ok) throw new Error('Error en la petición');
                        const data = await resp.json();
                        if (data && data.success) {
                            contraInput.disabled = false;
                            contraInput.focus();
                            btnCambiarContra.textContent = 'Cancelar cambio';
                            Swal.fire({ title: 'Verificado', text: 'Ahora puedes ingresar la nueva contraseña.', icon: 'success', timer: 1200, showConfirmButton: false });
                        } else {
                            Swal.fire({ title: 'Error', text: data.error || 'Contraseña incorrecta', icon: 'error' });
                        }
                    } catch (err) {
                        console.error(err);
                        Swal.fire({ title: 'Error', text: 'No se pudo verificar la contraseña. Intenta nuevamente.', icon: 'error' });
                    }
                });
            }
        });

        // Confirm discard (ORIGINAL LOGIC PRESERVED)
        (function(){
            function confirmDiscard(e) {
                if (e && e.preventDefault) e.preventDefault();
                Swal.fire({
                    title: "¿Descartar cambios?",
                    text: "Se eliminarán los datos ingresados para este empleado. ¿Desea continuar?",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#e15871",
                    cancelButtonColor: "#6b7280",
                    confirmButtonText: "Sí, descartar",
                    cancelButtonText: "Cancelar",
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: "Descartado",
                            text: "Los datos fueron descartados.",
                            icon: "success",
                            timer: 900,
                            showConfirmButton: false
                        }).then(() => {
                            window.history.back();
                        });
                    }
                });
            }

            const btnCancel = document.getElementById('btnCancelar');
            if (btnCancel) btnCancel.addEventListener('click', confirmDiscard);
        })();
    </script>
</body>
</html>