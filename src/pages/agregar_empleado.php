<?php 
    require_once __DIR__ . "/../config/db.php";
    require_once __DIR__ . "/../config/translation.php";

    // Calcular un id_empleado por defecto
    $id_empleado = '';

    $stmt = $pdo->query("SELECT * FROM roles");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $estatus = 1;
    if($_SERVER['REQUEST_METHOD'] === 'POST') {
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

            $regexNombre = "/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/u";
            if (!preg_match($regexNombre, $nombre) || !preg_match($regexNombre, $apellido_paterno) || !preg_match($regexNombre, $apellido_materno)) {
                echo json_encode(["error" => "Los nombres y apellidos solo deben contener letras.", "icon" => "warning"]);
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

            $regexLetras = "/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/u";
            $regexAlfanumerico = "/^[A-Za-z0-9\s]+$/u";  
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
<html lang="<?= $_SESSION['lang'] ?? 'es' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('add_employee_title') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.tailwindcss.com"></script>
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
    <script>
        $(document).ready(function() {
            $('#agregar').on('submit', function(e) {
               e.preventDefault();
               
                $.ajax({
                // Post back to the front controller so the page's POST handler runs
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
                            }
                        } catch (e) {
                            console.error("<?= __('json_processing_error') ?>: ", e, response);
                            Swal.fire({
                                title: '<?= __('error_title') ?>',
                                text: '<?= __('error_processing_response') ?>',
                                icon: 'error',
                                showConfirmButton: true
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error: ", status, error);
                        Swal.fire({
                            title: '<?= __('connection_error_title') ?>',
                            text: '<?= __('connection_error_text') ?>',
                            icon: 'error',
                            showConfirmButton: true
                        })
                    }
                });
            });
        });
    </script>
</head>
<body>
    <div>
        <h2><?= __('add_employee_title') ?></h2>
    </div>
    
    <div>
        <div>
            <span><?= __('basic_data') ?></span>
            <span id="btnClose">&#10005;</span>
        </div>
        
        <form id="agregar" action="index.php?view=agregar_empleado" method="POST" enctype="multipart/form-data">
            <div>
                <div>
                    <div>
                        <label><?= __('last_name_p') ?>: *</label>
                        <input type="text" name="apellido_p" maxlength="50">
                    </div>
                    <div>
                        <label><?= __('last_name_m') ?>: </label>
                        <input type="text" name="apellido_m" maxlength="50">
                    </div>
                </div>
                
                <label><?= __('names') ?>: *</label>
                <input type="text" name="nombres" maxlength = "50">

                <label><?= __('email') ?>: *</label>
                <input type="text" name="correo" maxlength="100">

                <div>
                    <div>
                        <label><?= __('password') ?>: *</label>
                        <input type="password" name="contra" maxlength="255">
                    </div>
                    <div>
                        <label><?= __('phone') ?>: *</label>
                        <input type="text" name="telefono" maxlength="20">
                    </div>
                </div>

                <div>
                    <div>
                        <label><?= __('street') ?>: *</label>
                        <input type="text" name="calle" maxlength="100">
                    </div>
                    <div>
                        <label><?= __('ext_num') ?>: *</label>
                        <input type="text" name="num_ext" maxlength="10">
                    </div>
                    <div>
                        <label><?= __('int_num') ?>: </label>
                        <input type="text" name="num_int" maxlength="10">
                    </div>
                </div>

                <div>
                    <div>
                        <label><?= __('colony') ?>: *</label>
                        <input type="text" name="colonia" maxlength="100">
                    </div>
                    <div>
                        <label><?= __('zip_code') ?>: </label>
                        <input type="text" name="cp" maxlength="10">
                    </div>
                </div>

                <div>
                    <div>
                        <label><?= __('state') ?>: *</label>
                        <input type="text" name="estado" maxlength="100">
                    </div>
                    <div>
                        <label><?= __('status') ?>: *</label>
                        <label class="switch">
                            <input type="hidden" name="estatus" value="0">
                            <input type="checkbox" name="estatus" value="1" <?= ($estatus == 1 ? 'checked' : '') ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

                <div>
                    <div>
                        <label><?= __('position') ?>: *</label><br>
                        <select id="id_rol" name="id_rol">
                            <option value="0"><?= __('select_position') ?></option>
                            <?php foreach ($roles as $rol): ?>
                                <option value="<?= $rol['id_rol'] ?>">
                                    <?= htmlspecialchars($rol['nombre_rol']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label><?= __('employee_num') ?>: *</label>
                        <input id="num_empleado" type="text" name="num_empleado" value="<?php echo htmlspecialchars($id_empleado); ?>" readonly>
                    </div>
                </div>
                
            </div>
            <div>
                <button type="submit"><?= __('save') ?></button>
                <button type="button" id="btnCancelar"><?= __('cancel') ?></button>
            </div>
        </form>
    </div>
    <script>
        // Cuando cambie el select de rol, pedir el siguiente id_empleado
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

            // Si hay un valor seleccionado al cargar, pedir el siguiente
            if (rolSelect.value) fetchNext(rolSelect.value);
        });

        // Confirmar antes de descartar el borrador y volver atrás
        (function(){
            function confirmDiscard(e) {
                if (e && e.preventDefault) e.preventDefault();
                Swal.fire({
                    title: "<?= __('discard_changes_title') ?>",
                    text: "<?= __('discard_changes_text') ?>",
                    icon: "info",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "<?= __('yes_discard') ?>",
                    cancelButtonText: "<?= __('cancel') ?>"
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: "<?= __('discarded_title') ?>",
                            text: "<?= __('discarded_text') ?>",
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
            const btnClose = document.getElementById('btnClose');
            if (btnCancel) btnCancel.addEventListener('click', confirmDiscard);
            if (btnClose) btnClose.addEventListener('click', confirmDiscard);
        })();
    </script>
</body>
</html>