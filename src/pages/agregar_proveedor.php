<?php 
    require_once __DIR__ . "/../config/db.php";

    $estatus = 1;
    if($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
        try {
            /* --- Validación de campos obligatorios --- */
            $campos_obligatorios = [
                'apellido_p' => 'Apellido Paterno',
                'nombres' => 'Nombre(s)',
                'correo' => 'Correo',
                'telefono' => 'Teléfono',
                'empresa' => 'Empresa'
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
                    $stmt = $pdo->prepare("SELECT * FROM proveedores WHERE correo = :correo");
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

            /* --- Validar telefono --- */
            $telefono = trim(filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_STRING));

            $regexTelefono = "/^[0-9]{10}$/";
            if (!preg_match($regexTelefono, $telefono)) { 
                echo json_encode(["error" => "El número de teléfono debe contener dígitos numéricos.", "icon" => "warning"]);
                exit;
            }

            /* --- Validar empresa --- */
            $empresa = trim(filter_input(INPUT_POST, 'empresa', FILTER_SANITIZE_STRING));

            $regexEmpresa = "/^[A-Za-z0-9ÁÉÍÓÚáéíóúÑñ\\s.,&-]+$/u";
            if (!preg_match($regexEmpresa, $empresa)) {
                echo json_encode(["error" => "El nombre de la empresa contiene caracteres inválidos.", "icon" => "warning"]);
                exit;
            }

            /* --- Validar estatus  --- */
            $estatus = isset($_POST['estatus']) ? (int)$_POST['estatus'] : 0;

            try {
                // Consulta para insertar el proveedor
                $sql = "INSERT INTO proveedores 
                    (id_proveedor, nombre, apellido_paterno, apellido_materno, empresa, celular, correo, estatus)
                    VALUES 
                    (:id_proveedor, :nombre, :apellido_paterno, :apellido_materno, :empresa, :celular, :correo, :estatus)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'id_proveedor' => NULL,
                    'nombre' => $nombre,
                    'apellido_paterno' => $apellido_paterno,
                    'apellido_materno' => $apellido_materno,
                    'empresa' => $empresa,
                    'celular' => $telefono,
                    'correo' => $correo,
                    'estatus' => $estatus
                ]);

                echo json_encode(["success" => "Proveedor registrado correctamente.", "redirect" => "index.php?view=proveedores", "icon" => "success"]);
                exit();
            } catch (Exception $e) {
                echo json_encode(["error" => "Error al registrar al proveedor: " . $e->getMessage(), "icon" => "error"]);
                exit();
            }
        } catch (Exception $e) {
            echo json_encode(["error" => "Error inesperado: " . $e->getMessage(), "icon" => "error"]);
            exit();
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Proveedor</title>
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
    </style>
</head>
<body>
    <div class="form-container animate-in">
        <div class="form-card">
            <!-- Header -->
            <div class="form-header animate-in-delay-1">
                <h1>Registro de Proveedores</h1>
                <p>Complete el formulario con los datos del nuevo proveedor</p>
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
                    </div>

                    <!-- Información Laboral -->
                    <div class="section animate-in-delay-3">
                        <h2 class="section-title">Información Laboral</h2>

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
                            <label class="form-label">Empresa<span class="required">*</span></label>
                            <input type="text" name="empresa" maxlength="50" class="form-input">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Estatus</label>
                            <div class="switch-container">
                                <label class="switch">
                                    <input type="hidden" name="estatus" value="0">
                                    <input type="checkbox" name="estatus" value="1" <?= ($estatus == 1 ? 'checked' : '') ?>>
                                    <span class="slider"></span>
                                </label>
                                <span class="switch-label">Proveedor activo</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="form-footer">
                    <button type="button" id="btnCancelar" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 1.25rem; height: 1.25rem;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span class="btn-text">Guardar Proveedor</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        $(document).ready(function() {
            $('#agregar').on('submit', function(e) {
                e.preventDefault();

                 document.querySelectorAll('.form-input, .form-select').forEach(input => {
                    input.classList.remove('error');
                    input.style.animation = 'none';
                    setTimeout(() => { input.style.animation = ''; }, 10);
                });

                const requiredFields = [
                    { name: 'apellido_p', label: 'Apellido Paterno' },
                    { name: 'nombres', label: 'Nombre(s)' },
                    { name: 'correo', label: 'Correo' },
                    { name: 'telefono', label: 'Teléfono' },
                    { name: 'empresa', label: 'Empresa' }
                ];

                let hasError = false;
                const errors = [];

                requiredFields.forEach(field => {
                    const input = document.querySelector(`[name="${field.name}"]`);
                    if (input && input.value.trim() === '') {
                        input.classList.add('error');
                        hasError = true;
                        errors.push(`El campo ${field.label} es obligatorio.`);
                    }
                });

                if (hasError) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Formulario incompleto',
                        html: errors.join('<br>'),
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#b4c24d'
                    });
                    return false;
                }

                const submitButton = this.querySelector('button[type="submit"]');
                const btnText = submitButton.querySelector('.btn-text');
                const originalText = btnText ? btnText.textContent : '';

                submitButton.disabled = true;
                if (btnText) btnText.innerHTML = '<div class="loading-spinner"></div> Guardando...';

                $.ajax({
                    url: 'index.php?view=agregar_proveedor',
                    type: 'POST',
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
                                    if(res.redirect) {
                                        window.location.href = res.redirect;
                                    }
                                });
                            } else if (res.error) {
                                Swal.fire({
                                    title: res.error,
                                    icon: res.icon || 'error',
                                    showConfirmButton: true
                                });
                                submitButton.disabled = false;
                                if (btnText) btnText.textContent = originalText;
                            }
                        } catch (e) {
                            console.error("Error al procesar JSON: ", e, response);
                            Swal.fire({
                                title: 'Error',
                                text: 'Ocurrió un error al procesar la respuesta',
                                icon: 'error',
                                showConfirmButton: true
                            });
                            submitButton.disabled = false;
                            if (btnText) btnText.textContent = originalText;
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
                        submitButton.disabled = false;
                        if (btnText) btnText.textContent = originalText;
                    }
                });
            });
        });

        (function() {
            function confirmDiscard(e) {
                if (e && e.preventDefault) e.preventDefault();
                Swal.fire({
                    title: "¿Descartar cambios?",
                    text: "Se eliminarán los datos ingresados para este proveedor. ¿Desea continuar?",
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

            const btnCancelar = document.getElementById('btnCancelar');
            if (btnCancelar) btnCancelar.addEventListener('click', confirmDiscard);
        })();
    </script>
</body>
</html>