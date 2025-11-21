<?php 
require_once __DIR__ . "/../config/db.php";

// Calcular un id_empleado por defecto
$id_empleado = '';

$stmt = $pdo->query("SELECT * FROM roles");
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

$estatus = 1;
if($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
    try {
        /* --- Validación de campos obligatorios --- */
        $campos_obligatorios = [
            'apellido_p' => 'Apellido Paterno',
            'nombres' => 'Nombre',
            'correo' => 'Correo',
            'contra' => 'Contraseña',
            'telefono' => 'Teléfono',
            'calle' => 'Calle',
            'num_ext' => 'Número Exterior',
            'colonia' => 'Colonia',
            'estado' => 'Estado',
            'id_rol' => 'Puesto',
            'num_empleado' => 'Número de empleado'
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
        if (!preg_match($regexNombre, $nombre) || !preg_match($regexNombre, $apellido_paterno)) {
            echo json_encode(["error" => "Los nombres y apellidos solo deben contener letras.", "icon" => "warning"]);
            exit;
        }
        
        // Validar apellido materno solo si no está vacío
        if ($apellido_materno !== "" && !preg_match($regexNombre, $apellido_materno)) {
            echo json_encode(["error" => "El apellido materno solo debe contener letras.", "icon" => "warning"]);
            exit;
        }

        /* --- Validar correo --- */
        $correo = trim(filter_input(INPUT_POST, 'correo', FILTER_SANITIZE_EMAIL));
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(["error" => "Por favor, ingresa una dirección de correo electrónico valido.", "icon" => "warning"]);
            exit;
        } else {
            try {
                $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo = :correo");
                $stmt->execute(['correo' => $correo]);
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

        /* --- Validar y cifrar nuestra contraseña --- */
        $contraseña = trim($_POST['contra']);

        if (strlen($contraseña) < 8) {
            echo json_encode(["error" => "La contraseña debe tener al menos 8 caracteres.", "icon" => "warning"]);
            exit;
        }

        if (!preg_match('/[A-Z]/', $contraseña)) {
            echo json_encode(["error" => "La contraseña debe contener al menos un carácter en mayúscula.", "icon" => "warning"]);
            exit;
        } else if (!preg_match('/[a-z]/', $contraseña)) {
            echo json_encode(["error" => "La contraseña debe contener al menos un carácter en minúscula.", "icon" => "warning"]);
            exit;
        } else if (!preg_match('/[0-9)]/', $contraseña)) {
            echo json_encode(["error" => "La contraseña debe contener al menos un número.", "icon" => "warning"]);
            exit;
        }

        $hash = password_hash($contraseña, PASSWORD_DEFAULT);

        /* --- Validar telefono --- */
        $telefono = trim(filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_STRING));

        $regexTelefono = "/^[0-9]{10}$/";
        if (!preg_match($regexTelefono, $telefono)) { 
            echo json_encode(["error" => "El número de teléfono debe contener dígitos numéricos.", "icon" => "warning"]);
            exit;
        }

        /* --- Validar domicilio  --- */
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

        /* --- Validar estatus  --- */
        $estatus = isset($_POST['estatus']) ? (int)$_POST['estatus'] : 0;

        /* --- Validar puesto --- */
        $id_rol = filter_input(INPUT_POST, 'id_rol', FILTER_SANITIZE_NUMBER_INT);

        /* --- Validar numero de empleado --- */
        $id_empleado = trim(filter_input(INPUT_POST, 'num_empleado', FILTER_SANITIZE_STRING));

        try {
            $stmt = $pdo->prepare("SELECT * FROM empleados WHERE id_empleado = :id_empleado");
            $stmt->execute(['id_empleado' => $id_empleado]);
            $existeEmpleado = $stmt->fetchColumn();

            if ($existeEmpleado > 0) {
                echo json_encode(["error" => "El número de empleado ya está registrado. Por favor, utiliza otro.", "icon" => "error"]);
                exit;
            }
        } catch (PDOException $e) {
            echo json_encode(["error" => "Error al verificar el número de empleado: " . $e->getMessage(), "icon" => "error"]);
            exit;
        }

        // Consulta para insertar el empleado
        $sql = "INSERT INTO empleados 
            (id_empleado, nombre, apellido_paterno, apellido_materno, celular, calle, num_ext, num_int, colonia, cp, estado, estatus, fecha, id_rol)
            VALUES
            (:id_empleado, :nombre, :apellido_paterno, :apellido_materno, :celular, :calle, :num_ext, :num_int, :colonia, :cp, :estado, :estatus, NOW(), :id_rol)";
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

        // Consulta para insertar el usuario asociado al empleado
        $sql_2 = "INSERT INTO usuarios (id_usuario, correo, contrasena, id_empleado)
            VALUES (:id_usuario, :correo, :contrasena, :id_empleado)";
        $stmt_2 = $pdo->prepare($sql_2);
        $stmt_2->execute([
            'id_usuario' => NULL,
            'correo' => $correo,
            'contrasena' => $hash,
            'id_empleado' => $id_empleado
        ]);
        
        echo json_encode(["success" => "Empleado registrado correctamente.", "redirect" => "index.php?view=empleados", "icon" => "success"]);
        exit();
    } catch (Exception $e) {
        echo json_encode(["error" => "Error al registrar al empleado: " . $e->getMessage(), "icon" => "error"]);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Empleados</title>
    
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
            box-shadow: 0 0 0 3px rgba(180, 194, 77, 0.12);
        }

        .form-input.error {
            border-color: var(--error);
            background: #fef2f2;
            animation: shake 0.4s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
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
    </style>
</head>
<body>
    <div class="form-container animate-in">
        <div class="form-card">
            <!-- Header -->
            <div class="form-header animate-in-delay-1">
                <h1>Registro de Empleados</h1>
                <p>Complete el formulario con los datos del nuevo empleado</p>
            </div>

            <!-- Form -->
            <form id="agregar" action="index.php?view=agregar_empleado" method="POST" enctype="multipart/form-data">
                <div class="form-body">
                    <!-- Información Personal -->
                    <div class="section animate-in-delay-2">
                        <h2 class="section-title">Información Personal</h2>
                        
                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label">Apellido Paterno<span class="required">*</span></label>
                                <input type="text" name="apellido_p" maxlength="50" class="form-input">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Apellido Materno</label>
                                <input type="text" name="apellido_m" maxlength="50" class="form-input">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Nombre(s)<span class="required">*</span></label>
                            <input type="text" name="nombres" maxlength="50" class="form-input">
                        </div>

                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label">Correo Electrónico<span class="required">*</span></label>
                                <input type="email" name="correo" maxlength="100" class="form-input">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Teléfono<span class="required">*</span></label>
                                <input type="text" name="telefono" maxlength="10" class="form-input" placeholder="10 dígitos">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Contraseña<span class="required">*</span></label>
                            <input type="password" name="contra" maxlength="255" class="form-input" placeholder="Mínimo 8 caracteres">
                        </div>
                    </div>

                    <!-- Dirección -->
                    <div class="section animate-in-delay-3">
                        <h2 class="section-title">Dirección</h2>
                        
                        <div class="grid-3">
                            <div class="form-group" style="grid-column: span 2;">
                                <label class="form-label">Calle<span class="required">*</span></label>
                                <input type="text" name="calle" maxlength="100" class="form-input">
                            </div>
                            <div class="form-group">
                                <label class="form-label">No. Exterior<span class="required">*</span></label>
                                <input type="text" name="num_ext" maxlength="10" class="form-input">
                            </div>
                        </div>

                        <div class="grid-3">
                            <div class="form-group">
                                <label class="form-label">No. Interior</label>
                                <input type="text" name="num_int" maxlength="10" class="form-input">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Colonia<span class="required">*</span></label>
                                <input type="text" name="colonia" maxlength="100" class="form-input">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Código Postal</label>
                                <input type="text" name="cp" maxlength="5" class="form-input" placeholder="5 dígitos">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Estado<span class="required">*</span></label>
                            <input type="text" name="estado" maxlength="100" class="form-input">
                        </div>
                    </div>

                    <!-- Información Laboral -->
                    <div class="section animate-in-delay-3">
                        <h2 class="section-title">Información Laboral</h2>
                        
                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label">Puesto<span class="required">*</span></label>
                                <select id="id_rol" name="id_rol" class="form-select">
                                    <option value="">Seleccionar el puesto</option>
                                    <?php foreach ($roles as $rol): ?>
                                        <option value="<?= $rol['id_rol'] ?>">
                                            <?= htmlspecialchars($rol['nombre_rol']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Número de Empleado<span class="required">*</span></label>
                                <input id="num_empleado" type="text" name="num_empleado" value="<?php echo htmlspecialchars($id_empleado); ?>" class="form-input" readonly>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Estatus</label>
                            <div class="switch-container">
                                <label class="switch">
                                    <input type="hidden" name="estatus" value="0">
                                    <input type="checkbox" name="estatus" value="1" <?= ($estatus == 1 ? 'checked' : '') ?>>
                                    <span class="slider"></span>
                                </label>
                                <span class="switch-label">Empleado activo</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="form-footer">
                    <button type="button" id="btnCancelar" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 1.25rem; height: 1.25rem;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="btn-text">Guardar Empleado</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Form submission with AJAX (ORIGINAL LOGIC PRESERVED)
        $(document).ready(function() {
            $('#agregar').on('submit', function(e) {
               e.preventDefault();
               
                // Clear previous errors
                document.querySelectorAll('.form-input').forEach(input => {
                    input.classList.remove('error');
                    input.style.animation = 'none';
                    setTimeout(() => { input.style.animation = ''; }, 10);
                });

                const submitBtn = this.querySelector('button[type="submit"]');
                const btnText = submitBtn.querySelector('.btn-text');
                const originalText = btnText.textContent;
                
                submitBtn.disabled = true;
                btnText.innerHTML = '<span class="loading-spinner"></span> Guardando...';

                $.ajax({
                    url: "index.php?view=agregar_empleado",
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

            async function fetchNext(idRol) {
                if (!idRol) return;
                try {
                    const resp = await fetch('scripts/next_employee.php?id_rol=' + encodeURIComponent(idRol));
                    if (!resp.ok) throw new Error('Error en la petición');
                    const data = await resp.json();
                    if (data && data.next) numInput.value = data.next;
                } catch (e) {
                    console.error("Error al obtener el siguiente número de empleado: ", e);
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
