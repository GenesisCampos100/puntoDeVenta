<?php 
    require_once __DIR__ . "/../config/db.php";
    require_once __DIR__ . "/../config/translation.php";

// Calcular un id_empleado por defecto
$id_empleado = '';

$stmt = $pdo->query("SELECT * FROM roles");
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Función auxiliar para enviar respuestas JSON limpias
function send_json_response($data) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

$estatus = 1;
if($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
    try {
        // Validación de campos obligatorios
        $campos_obligatorios = [
            'apellido_p' => 'Apellido Paterno',
            'nombres' => 'Nombres',
            'correo' => 'Correo',
            'contra' => 'Contraseña',
            'telefono' => 'Teléfono',
            'calle' => 'Calle',
            'num_ext' => 'Número Exterior',
            'colonia' => 'Colonia',
            'estado' => 'Estado',
            'id_rol' => 'Puesto'
        ];

        foreach ($campos_obligatorios as $campo => $etiqueta) {
            if (empty($_POST[$campo])) {
                send_json_response(["error" => "El campo $etiqueta es obligatorio. Por favor, complételo.", "icon" => "warning"]);
            }
        }

        // Validar nombre y apellidos
        $nombre = trim(filter_input(INPUT_POST, 'nombres', FILTER_SANITIZE_STRING));
        $apellido_paterno = trim(filter_input(INPUT_POST, 'apellido_p', FILTER_SANITIZE_STRING));
        $apellido_materno = trim(filter_input(INPUT_POST, 'apellido_m', FILTER_SANITIZE_STRING));

        $regexNombre = "/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/u";
        if (!preg_match($regexNombre, $nombre) || !preg_match($regexNombre, $apellido_paterno)) {
            send_json_response(["error" => "Los nombres y apellidos solo deben contener letras.", "icon" => "warning"]);
        }
        if ($apellido_materno !== "" && !preg_match($regexNombre, $apellido_materno)) {
            send_json_response(["error" => "El apellido materno solo debe contener letras.", "icon" => "warning"]);
        }

        // Validar correo y duplicados
        $correo = trim(filter_input(INPUT_POST, 'correo', FILTER_SANITIZE_EMAIL));
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            send_json_response(["error" => "Por favor, ingresa una dirección de correo electrónico valido.", "icon" => "warning"]);
        }
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE correo = :correo");
        $stmt->execute(['correo' => $correo]);
        if ((int)$stmt->fetchColumn() > 0) {
            send_json_response(["error" => "El correo electrónico ya está registrado. Por favor, utiliza otro.", "icon" => "error"]);
        }

        // Validar y cifrar contraseña
        $contrasena = trim($_POST['contra']);
        if (strlen($contrasena) < 8) {
            send_json_response(["error" => __('password_min_8'), "icon" => "warning"]);
        }
        if (!preg_match('/[A-Z]/', $contrasena)) {
            send_json_response(["error" => __('password_uppercase'), "icon" => "warning"]);
        }
        if (!preg_match('/[a-z]/', $contrasena)) {
            send_json_response(["error" => __('password_lowercase'), "icon" => "warning"]);
        }
        if (!preg_match('/[0-9]/', $contrasena)) {
            send_json_response(["error" => __('password_number'), "icon" => "warning"]);
        }
        $hash = password_hash($contrasena, PASSWORD_DEFAULT);

        // Validar teléfono
        $telefono = trim(filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_STRING));
        if (!preg_match("/^[0-9]{10}$/", $telefono)) {
            send_json_response(["error" => "El número de teléfono debe contener dígitos numéricos.", "icon" => "warning"]);
        }

        // Validar domicilio
        $calle = trim(filter_input(INPUT_POST, 'calle', FILTER_SANITIZE_STRING));
        $num_ext = trim(filter_input(INPUT_POST, 'num_ext', FILTER_SANITIZE_STRING));
        $num_int = trim(filter_input(INPUT_POST, 'num_int', FILTER_SANITIZE_STRING));
        $colonia = trim(filter_input(INPUT_POST, 'colonia', FILTER_SANITIZE_STRING));
        $cp = trim(filter_input(INPUT_POST, 'cp', FILTER_SANITIZE_STRING));
        $estado = trim(filter_input(INPUT_POST, 'estado', FILTER_SANITIZE_STRING));

        $regexLetras = "/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/u";
        $regexAlfanumerico = "/^[A-Za-z0-9\s]+$/u";
        $regexCP = "/^[0-9]{5}$/";

        $errores = [];
        if (!preg_match($regexAlfanumerico, $calle)) { $errores[] = "Calle inválida."; }
        if (!preg_match($regexAlfanumerico, $num_ext)) { $errores[] = "Número exterior inválido."; }
        if ($num_int !== "" && !preg_match($regexAlfanumerico, $num_int)) { $errores[] = "Número interior inválido."; }
        if (!preg_match($regexAlfanumerico, $colonia)) { $errores[] = "Colonia inválida."; }
        if ($cp !== "" && !preg_match($regexCP, $cp)) { $errores[] = "Código postal inválido."; }
        if (!preg_match($regexLetras, $estado)) { $errores[] = "Estado inválido."; }
        if (!empty($errores)) {
            send_json_response(["error" => $errores[0], "icon" => "warning"]);
        }

        // Estatus y rol
        $estatus = isset($_POST['estatus']) ? (int)$_POST['estatus'] : 0;
        $id_rol = filter_input(INPUT_POST, 'id_rol', FILTER_SANITIZE_NUMBER_INT);

        // Número de empleado (autogenerado si vacío)
        $id_empleado = trim(filter_input(INPUT_POST, 'num_empleado', FILTER_SANITIZE_STRING));
        if (empty($id_empleado)) {
            $stmtRol = $pdo->prepare("SELECT nombre_rol FROM roles WHERE id_rol = :id_rol");
            $stmtRol->execute(['id_rol' => $id_rol]);
            $rolData = $stmtRol->fetch(PDO::FETCH_ASSOC);
            $prefijo = 'EMP';
            if ($rolData && !empty($rolData['nombre_rol'])) {
                $prefijo = strtoupper(substr($rolData['nombre_rol'], 0, 3));
            }
            $stmtLast = $pdo->prepare("SELECT id_empleado FROM empleados WHERE id_empleado LIKE :prefijo ORDER BY id_empleado DESC LIMIT 1");
            $stmtLast->execute(['prefijo' => $prefijo . '%']);
            $lastEmp = $stmtLast->fetch(PDO::FETCH_ASSOC);
            if ($lastEmp) {
                $lastNum = (int) substr($lastEmp['id_empleado'], strlen($prefijo));
                $id_empleado = $prefijo . str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
            } else {
                $id_empleado = $prefijo . '0001';
            }
        }

        // Validar duplicado de número de empleado
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM empleados WHERE id_empleado = :id_empleado");
        $stmt->execute(['id_empleado' => $id_empleado]);
        if ((int)$stmt->fetchColumn() > 0) {
            send_json_response(["error" => "El número de empleado ya está registrado. Por favor, utiliza otro.", "icon" => "error"]);
        }

        // Insertar empleado
        $sql = "INSERT INTO empleados (id_empleado, nombre, apellido_paterno, apellido_materno, celular, calle, num_ext, num_int, colonia, cp, estado, estatus, fecha, id_rol)
                VALUES (:id_empleado, :nombre, :apellido_paterno, :apellido_materno, :celular, :calle, :num_ext, :num_int, :colonia, :cp, :estado, :estatus, NOW(), :id_rol)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'id_empleado' => $id_empleado,
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
            'id_rol' => $id_rol
        ]);

        // Insertar usuario
        $sql_2 = "INSERT INTO usuarios (id_usuario, correo, contrasena, id_empleado) VALUES (:id_usuario, :correo, :contrasena, :id_empleado)";
        $stmt_2 = $pdo->prepare($sql_2);
        $stmt_2->execute([
            'id_usuario' => NULL,
            'correo' => $correo,
            'contrasena' => $hash,
            'id_empleado' => $id_empleado
        ]);

        // Nombre del puesto (para correo)
        $nombre_p = 'No especificado';
        if (!empty($id_rol) && is_array($roles)) {
            foreach ($roles as $rol_item) {
                if (isset($rol_item['id_rol']) && (string)$rol_item['id_rol'] === (string)$id_rol) {
                    $nombre_p = isset($rol_item['nombre_rol']) && $rol_item['nombre_rol'] !== '' ? $rol_item['nombre_rol'] : $nombre_p;
                    break;
                }
            }
        }

        // Enviar correo en segundo plano (best-effort)
        try {
            $datosCorreo = [
                'nombre' => $nombre,
                'apellido_paterno' => $apellido_paterno,
                'apellido_materno' => $apellido_materno,
                'id_empleado' => $id_empleado,
                'nombre_p' => $nombre_p,
                'correo' => $correo
            ];
            $url = "http://localhost/puntoDeVenta/src/scripts/enviar_correo.php?" . http_build_query($datosCorreo);
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, 100);
            curl_exec($ch);
            curl_close($ch);
        } catch (Exception $e) {
            error_log("Error al ejecutar enviar_correo.php: " . $e->getMessage());
        }

        send_json_response(["success" => "Empleado registrado correctamente.", "redirect" => "index.php?view=empleados", "icon" => "success"]);
    } catch (Exception $e) {
        send_json_response(["error" => "Error al registrar al empleado: " . $e->getMessage(), "icon" => "error"]);
    }
}
?>
<html lang="<?= $_SESSION['lang'] ?? 'es' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('add_employee_title') ?></title>
    
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

body.dark-mode .btn-primary:hover {
    background-color:  #b4c24d  !important; /* verde */
    color: white !important;
    border: none !important;
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
                <h1><?= __('add_employee_title') ?></h1>
                <p><?= __('add_employee_subtitle') ?></p>
            </div>

            <!-- Form -->
            <form id="agregar" action="index.php?view=agregar_empleado" method="POST" enctype="multipart/form-data" onsubmit="return submitAgregarForm(event);">
                <div class="form-body">
                    <!-- Información Personal -->
                    <div class="section animate-in-delay-2">
                        <h2 class="section-title"><?= __('personal_information') ?></h2>
                        
                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label"><?= __('last_name_p') ?><span class="required">*</span></label>
                                <input type="text" name="apellido_p" maxlength="50" class="form-input">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?= __('last_name_m') ?></label>
                                <input type="text" name="apellido_m" maxlength="50" class="form-input">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label"><?= __('names') ?><span class="required">*</span></label>
                            <input type="text" name="nombres" maxlength="50" class="form-input">
                        </div>

                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label"><?= __('email') ?><span class="required">*</span></label>
                                <input type="email" name="correo" maxlength="100" class="form-input">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?= __('phone') ?><span class="required">*</span></label>
                                <input type="text" name="telefono" maxlength="10" class="form-input" placeholder="10 <?= __('digits') ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label"><?= __('password') ?><span class="required">*</span></label>
                            <input type="password" name="contra" maxlength="255" class="form-input" placeholder="<?= __('min_8_chars') ?>">
                        </div>
                    </div>

                    <!-- Dirección -->
                    <div class="section animate-in-delay-3">
                        <h2 class="section-title"><?= __('address') ?></h2>
                        
                        <div class="grid-3">
                            <div class="form-group" style="grid-column: span 2;">
                                <label class="form-label"><?= __('street') ?><span class="required">*</span></label>
                                <input type="text" name="calle" maxlength="100" class="form-input">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?= __('ext_num') ?><span class="required">*</span></label>
                                <input type="text" name="num_ext" maxlength="10" class="form-input">
                            </div>
                        </div>

                        <div class="grid-3">
                            <div class="form-group">
                                <label class="form-label"><?= __('int_num') ?></label>
                                <input type="text" name="num_int" maxlength="10" class="form-input">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?= __('colony') ?><span class="required">*</span></label>
                                <input type="text" name="colonia" maxlength="100" class="form-input">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?= __('zip_code') ?></label>
                                <input type="text" name="cp" maxlength="5" class="form-input" placeholder="5 <?= __('digits') ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label"><?= __('state') ?><span class="required">*</span></label>
                            <input type="text" name="estado" maxlength="100" class="form-input">
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
                                        <option value="<?= $rol['id_rol'] ?>">
                                            <?= htmlspecialchars($rol['nombre_rol']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?= __('employee_num') ?> <span style="font-size: 0.75rem; color: #6b7280; font-weight: 400;">(<?= __('auto_generated') ?>)</span></label>
                                <input id="num_empleado" type="text" name="num_empleado" value="<?php echo htmlspecialchars($id_empleado); ?>" class="form-input" readonly placeholder="<?= __('will_be_generated') ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label"><?= __('status') ?></label>
                            <div class="switch-container">
                                <label class="switch">
                                    <input type="hidden" name="estatus" value="0">
                                    <input type="checkbox" name="estatus" value="1" <?= ($estatus == 1 ? 'checked' : '') ?>>
                                    <span class="slider"></span>
                                </label>
                                <span class="switch-label"><?= __('employee_active') ?></span>
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
                        <span class="btn-text"><?= __('save_employee') ?></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Función reutilizable para enviar el formulario por AJAX
        function submitAgregarForm(e){
            if (e) e.preventDefault();
            var formEl = document.getElementById('agregar');
            if (!formEl) return false;
            var $form = window.jQuery ? window.jQuery('#agregar') : null;
               
                // Clear previous errors
                document.querySelectorAll('.form-input, .form-select').forEach(input => {
                    input.classList.remove('error');
                    input.style.animation = 'none';
                    setTimeout(() => { input.style.animation = ''; }, 10);
                });

                const requiredFields = [
                    { name: 'apellido_p', label: <?= json_encode(__('last_name_p')) ?> },
                    { name: 'nombres', label: <?= json_encode(__('names')) ?> },
                    { name: 'correo', label: <?= json_encode(__('email')) ?> },
                    { name: 'contra', label: <?= json_encode(__('password')) ?> },
                    { name: 'telefono', label: <?= json_encode(__('phone')) ?> },
                    { name: 'calle', label: <?= json_encode(__('street')) ?> },
                    { name: 'num_ext', label: <?= json_encode(__('ext_num')) ?> },
                    { name: 'colonia', label: <?= json_encode(__('colony')) ?> },
                    { name: 'estado', label: <?= json_encode(__('state')) ?> },
                    { name: 'id_rol', label: <?= json_encode(__('position')) ?> }
                ];

                let hasErrors = false;
                const errors = [];

                requiredFields.forEach(field => {
                    const input = document.querySelector(`[name="${field.name}"]`);
                    if (input && input.value.trim() === '') {
                        input.classList.add('error');
                        hasErrors = true;
                        errors.push(<?= json_encode(__('field_required')) ?> + ': ' + field.label);
                    }
                });

                if (hasErrors) {
                    Swal.fire({
                        icon: 'error',
                        title: '❌ ' + <?= json_encode(__('incomplete_form')) ?>,
                        html: errors.join('<br>'),
                        confirmButtonText: <?= json_encode(__('ok')) ?>,
                        customClass: {
                            popup: 'swal2-popup-custom',
                            confirmButton: 'swal2-confirm-custom'
                        }
                    });
                    return false;
                }

                const submitBtn = formEl.querySelector('button[type="submit"]');
                const btnText = submitBtn.querySelector('.btn-text');
                const originalText = btnText.textContent;
                
                if (window.jQuery && $form) {
                    $.ajax({
                        url: "index.php?view=agregar_empleado",
                        type: "POST",
                        data: $form.serialize(),
                        dataType: 'json',
                        headers: { 'Accept': 'application/json' },
                        success: function(res) {
                            if (res.success) {
                                Swal.fire({
                                    title: '✅ ' + res.success,
                                    icon: 'success',
                                    showConfirmButton: false,
                                    timer: 1500,
                                    customClass: {
                                        popup: 'swal2-popup-custom'
                                    }
                                }).then(() => {
                                    if (res.redirect) {
                                        window.location.href = res.redirect;
                                    }
                                });
                            } else if (res.error) {
                                Swal.fire({
                                    title: res.icon === 'warning' ? '⚠️ Advertencia' : '❌ Error',
                                    html: res.error,
                                    icon: res.icon || 'error',
                                    confirmButtonText: <?= json_encode(__('ok')) ?>,
                                    customClass: {
                                        popup: 'swal2-popup-custom',
                                        confirmButton: 'swal2-confirm-custom'
                                    }
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error("AJAX Error: ", status, error);
                            console.log("Response Text:", xhr.responseText);
                            // Intentar parsear la respuesta manualmente
                            try {
                                const res = JSON.parse(xhr.responseText);
                                if (res.error) {
                                    Swal.fire({
                                        title: res.icon === 'warning' ? '⚠️ Advertencia' : '❌ Error',
                                        html: res.error,
                                        icon: res.icon || 'error',
                                        confirmButtonText: <?= json_encode(__('ok')) ?>,
                                        customClass: {
                                            popup: 'swal2-popup-custom',
                                            confirmButton: 'swal2-confirm-custom'
                                        }
                                    });
                                }
                            } catch (e) {
                                Swal.fire({
                                    title: '❌ ' + <?= json_encode(__('connection_error_title')) ?>,
                                    text: <?= json_encode(__('connection_error_text')) ?>,
                                    icon: 'error',
                                    confirmButtonText: <?= json_encode(__('ok')) ?>,
                                    customClass: {
                                        popup: 'swal2-popup-custom',
                                        confirmButton: 'swal2-confirm-custom'
                                    }
                                });
                            }
                        }
                    });
                } else {
                    // Fallback sin jQuery: usar fetch
                    const formData = new FormData(formEl);
                    const params = new URLSearchParams();
                    formData.forEach((v, k) => params.append(k, v));
                    fetch('index.php?view=agregar_empleado', {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: params.toString()
                    }).then(async resp => {
                        let res = {};
                        try { res = await resp.json(); } catch(_) {}
                        if (res.success) {
                            Swal.fire({
                                title: '✅ ' + res.success,
                                icon: 'success',
                                showConfirmButton: false,
                                timer: 1500,
                                customClass: { popup: 'swal2-popup-custom' }
                            }).then(() => { if (res.redirect) window.location.href = res.redirect; });
                        } else {
                            Swal.fire({
                                title: res.icon === 'warning' ? '⚠️ Advertencia' : '❌ Error',
                                html: res.error || <?= json_encode(__('connection_error_text')) ?>,
                                icon: (res.icon || 'error'),
                                confirmButtonText: <?= json_encode(__('ok')) ?>,
                                customClass: { popup: 'swal2-popup-custom', confirmButton: 'swal2-confirm-custom' }
                            });
                        }
                    }).catch(() => {
                        Swal.fire({
                            title: '❌ ' + <?= json_encode(__('connection_error_title')) ?>,
                            text: <?= json_encode(__('connection_error_text')) ?>,
                            icon: 'error',
                            confirmButtonText: <?= json_encode(__('ok')) ?>,
                            customClass: { popup: 'swal2-popup-custom', confirmButton: 'swal2-confirm-custom' }
                        });
                    }).finally(() => {
                        submitBtn.disabled = false;
                        btnText.textContent = originalText;
                    });
                }
            return false;
        }

        // Envío del formulario con AJAX (delegado y robusto)
        (function($){
            if (!$ || !$.fn) return;
            $(document).on('submit', '#agregar', submitAgregarForm);
        })(jQuery);

        // Captura en fase de captura por máxima robustez
        document.addEventListener('submit', function(ev){
            const t = ev.target;
            if (t && t.id === 'agregar') {
                ev.preventDefault();
                submitAgregarForm(ev);
            }
        }, true);

        // Auto-generate employee number (ORIGINAL LOGIC PRESERVED)
        document.addEventListener('DOMContentLoaded', function () {
            const rolSelect = document.getElementById('id_rol');
            const numInput = document.getElementById('num_empleado');

            if (!rolSelect || !numInput) return;

            async function fetchNext(idRol) {
                if (!idRol) return;
                try {
                    const resp = await fetch('scripts/next_employee.php?id_rol=' + encodeURIComponent(idRol));
                    if (!resp.ok) throw new Error('Error en la petición');
                    const data = await resp.json();
                    if (data && data.next) numInput.value = data.next;
                } catch (e) {
                    console.error(<?= json_encode(__('error_fetching_employee_num')) ?> + ': ', e);
                }
            }

            rolSelect.addEventListener('change', function () {
                numInput.value = '';
                fetchNext(this.value);
            });

            if (rolSelect.value) fetchNext(rolSelect.value);
        });

        // Confirm discard (ORIGINAL LOGIC PRESERVED)
        (function(){
            function confirmDiscard(e) {
                if (e && e.preventDefault) e.preventDefault();
                Swal.fire({
                    title: <?= json_encode(__('discard_changes_title')) ?>,
                    text: <?= json_encode(__('discard_changes_text')) ?>,
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#e15871",
                    cancelButtonColor: "#6b7280",
                    confirmButtonText: <?= json_encode(__('yes_discard')) ?>,
                    cancelButtonText: <?= json_encode(__('cancel')) ?>,
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: <?= json_encode(__('discarded_title')) ?>,
                            text: <?= json_encode(__('discarded_text')) ?>,
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

