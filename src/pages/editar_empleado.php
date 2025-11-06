<?php 
    require_once __DIR__ . "/../config/db.php";

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

            $regexNombre = "/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/u";
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editor de Empleados</title>
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
            $('#editar').on('submit', function(e) {
               e.preventDefault();
               
                $.ajax({
                // Post back to the front controller so the page's POST handler runs
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
                            }
                        } catch (e) {
                            console.error("<?= "Error al procesar JSON" ?>: ", e, response);
                            Swal.fire({
                                title: '<?= "Error" ?>',
                                text: '<?= "Ocurrio un error al procesar la respuesta" ?>',
                                icon: 'error',
                                showConfirmButton: true
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error: ", status, error);
                        Swal.fire({
                            title: '<?= "Error de conexión" ?>',
                            text: '<?= "No se pudo conectar con el servidor" ?>',
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
        <h2>Editor de Empleados</h2>
    </div>
    
    <div>
        <div>
            <span>Datos Básicos</span>
            <span id="btnClose">&#10005;</span>
        </div>

        <form id="editar" action="index.php?view=editar_empleado" method="POST" enctype="multipart/form-data">
            <div>
                <div>
                    <div>
                        <label>Apellido Paterno: </label>
                        <input type="text" name="apellido_p" maxlength="50" value="<?= htmlspecialchars($empleado['apellido_paterno'] ?? '') ?>">
                    </div>
                    <div>
                        <label>Apellido Materno: </label>
                        <input type="text" name="apellido_m" maxlength="50" value="<?= htmlspecialchars($empleado['apellido_materno'] ?? '') ?>">
                    </div>
                </div>

                <label>Nombre(s): </label>
                <input type="text" name="nombres" maxlength="50" value="<?= htmlspecialchars($empleado['nombre'] ?? '') ?>">

                <label>Correo: </label>
                <input type="text" name="correo" maxlength="100" value="<?= htmlspecialchars($empleado['correo'] ?? '') ?>">

                <div>
                    <div>
                        <label>Contraseña: </label>
                        <!-- Deshabilitado por defecto; se habilita al pulsar el botón "Cambiar Contraseña" -->
                        <input id="contra" type="password" name="contra" maxlength="255" disabled>
                    </div>
                    <div>
                        <label>Teléfono: </label>
                        <input type="text" name="telefono" maxlength="20" value="<?= htmlspecialchars($empleado['celular'] ?? '') ?>">
                    </div>
                    <div>
                        <button type="button" id="btnCambiarContra">Cambiar Contraseña</button>
                    </div>
                </div>

                <div>
                    <div>
                        <label>Calle: </label>
                        <input type="text" name="calle" maxlength="100" value="<?= htmlspecialchars($empleado['calle'] ?? '') ?>">
                    </div>
                    <div>
                        <label>No. Ext: </label>
                        <input type="text" name="num_ext" maxlength="10" value="<?= htmlspecialchars($empleado['num_ext'] ?? '') ?>">
                    </div>
                    <div>
                        <label>No. Int: </label>
                        <input type="text" name="num_int" maxlength="10" value="<?= htmlspecialchars($empleado['num_int'] ?? '') ?>">
                    </div>
                </div>

                <div>
                    <div>
                        <label>Colonia: </label>
                        <input type="text" name="colonia" maxlength="100" value="<?= htmlspecialchars($empleado['colonia'] ?? '') ?>">
                    </div>
                    <div>
                        <label>Código Postal: </label>
                        <input type="text" name="cp" maxlength="10" value="<?= htmlspecialchars($empleado['cp'] ?? '') ?>">
                    </div>
                </div>

                <div>
                    <div>
                        <label>Estado: </label>
                        <input type="text" name="estado" maxlength="100" value="<?= htmlspecialchars($empleado['estado'] ?? '') ?>">
                    </div>
                    <div>
                        <label>Estatus:</label>
                        <label class="switch">
                            <input type="hidden" name="estatus" value="0">
                            <input type="checkbox" name="estatus" value="1" <?= ($estatus == 1 ? 'checked' : '') ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

                <div>
                    <div>
                        <label>Puesto:</label><br>
                        <select id="id_rol" name="id_rol">
                            <option value="">Seleccionar el puesto</option>
                            <?php foreach ($roles as $rol): ?>
                                <?php if ($rol['id_rol'] != $rol_actual_id): // Oculta el rol actual ?>
                                    <option value="<?= $rol['id_rol'] ?>">
                                        <?= htmlspecialchars($rol['nombre_rol']) ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <div>
                        <label>Actual No. Empleado: </label>
                        <input id="actual_empleado" type="text" name="actual_empleado" value="<?= htmlspecialchars($empleado['id_empleado'] ?? '') ?>" readonly>
                    </div>
                    <div>
                        <label>Nuevo No. Empleado: </label>
                        <input id="num_empleado" type="text" name="num_empleado" value="<?php echo htmlspecialchars($id_empleado); ?>" readonly>
                    </div>
                </div>
            </div>
            <div>
                <button type="submit">Actualizar</button>
                <button type="button" id="btnCancelar">Cancelar</button>
            </div>
        </form>
    </div>
    <script>
        // Cuando cambie el select de rol, pedir el siguiente id_empleado
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

            // Lógica para pedir la contraseña del usuario que autoriza (SweetAlert) y, si es correcta, habilitar el input
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

                    // Pedir la contraseña del usuario que autoriza usando el patrón sugerido
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

                    if (!password) return; // canceló o no ingresó

                    // Enviar al servidor para verificar (verify_password.php)
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

        // Confirmar antes de descartar el borrador y volver atrás
        (function(){
            function confirmDiscard(e) {
                if (e && e.preventDefault) e.preventDefault();
                Swal.fire({
                    title: "¿Descartar cambios?",
                    text: "Se eliminarán los datos ingresados para este empleado. ¿Desea continuar?",
                    icon: "info",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Sí, descartar",
                    cancelButtonText: "Cancelar"
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
            const btnClose = document.getElementById('btnClose');
            if (btnCancel) btnCancel.addEventListener('click', confirmDiscard);
            if (btnClose) btnClose.addEventListener('click', confirmDiscard);
        })();
    </script>
</body> 
</html>