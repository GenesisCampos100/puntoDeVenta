<?php 
    require_once __DIR__ . "/../config/db.php";

    $id_empleado = '';
    
    $empleado_id = $_GET['id'] ?? null;

    if($empleado_id) {
        $stmt = $pdo->prepare("SELECT e.*, u.* FROM empleados e 
                               INNER JOIN usuarios u ON e.id_empleado = u.id_empleado 
                               WHERE e.id_empleado = ?");
        $stmt->execute([$empleado_id]);
        $empleado = $stmt->fetch(PDO::FETCH_ASSOC); 
        
        if (!$empleado) {
            die("Empleado no encontrado.");
        }
    }

    $estatus = (int)($empleado['estatus'] ?? 0);

    // Traer los roles
    $stmt = $pdo->query("SELECT * FROM roles");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Validación: comprobar que haya roles disponibles
    if (!$roles) {
        die("No hay roles disponibles.");
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

        $campos_obligatorios = [
            'num_empleado' => 'Número de empleado',
            'nombres' => 'Nombre(s)',
            'apellido_p' => 'Apellido Paterno',
            'apellido_m' => 'Apellido Materno',
            'telefono' => 'Teléfono',
            'calle' => 'Calle',
            'num_ext' => 'Número Exterior',
            'colonia' => 'Colonia',
            'estado' => 'Estado',
            'id_rol' => 'Puesto',
            'correo' => 'Correo'
        ];

        foreach ($campos_obligatorios as $campo => $etiqueta) {
            if (empty($_POST[$campo])) {
                die("Todos los campos obligatorios deben estar llenos. Falta: $etiqueta.");
            }
        }
        
        $nuevo_id_empleado = filter_input(INPUT_POST, 'num_empleado', FILTER_SANITIZE_STRING);

        $nombre = filter_input(INPUT_POST, 'nombres', FILTER_SANITIZE_STRING);
        $apellido_paterno = filter_input(INPUT_POST, 'apellido_p', FILTER_SANITIZE_STRING);
        $apellido_materno = filter_input(INPUT_POST, 'apellido_m', FILTER_SANITIZE_STRING);

        $telefono = filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_STRING);

        $calle = filter_input(INPUT_POST, 'calle', FILTER_SANITIZE_STRING);
        $num_ext = filter_input(INPUT_POST, 'num_ext', FILTER_SANITIZE_STRING);
        $num_int = filter_input(INPUT_POST, 'num_int', FILTER_SANITIZE_STRING);
        $colonia = filter_input(INPUT_POST, 'colonia', FILTER_SANITIZE_STRING);
        $cp = filter_input(INPUT_POST, 'cp', FILTER_SANITIZE_STRING);
        $estado = filter_input(INPUT_POST, 'estado', FILTER_SANITIZE_STRING);

        $estatus = isset($_POST['estatus']) ? (int)$_POST['estatus'] : 0;

        $id_rol = filter_input(INPUT_POST, 'id_rol', FILTER_SANITIZE_NUMBER_INT);

        // Validar correo
        $correo = filter_input(INPUT_POST, 'correo', FILTER_SANITIZE_EMAIL);
        if(!$correo) {
            die("Correo inválido.");
        }

        // Validar y cifrar nuestra contraseña
        $contra = $_POST['contra'] ?? '';
        // Validación de contraseña segura
        if (!empty($contra)) {
            // Patrón: al menos 8 caracteres, 1 mayúscula, 1 minúscula y 1 número
            $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/';
            if (!preg_match($pattern, $contra)) {
                die("La contraseña debe tener al menos 8 caracteres, incluyendo mayúsculas, minúsculas y números.");
            }
        }

        // Consulta para editar el empleado 
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
            'id_emp' => $empleado_id
        ]);

        if(empty($contra)) {
            $sql_2 = "UPDATE usuarios
                      SET correo = :correo
                      WHERE id_empleado = :id_empleado";
            $stmt_2 = $pdo->prepare($sql_2);
            $stmt_2->execute([
                'correo' => $correo,
                'id_empleado' => $empleado_id
            ]);
        } else {
            $hash = password_hash($contra, PASSWORD_DEFAULT);
            $sql_2 = "UPDATE usuarios
                      SET correo = :correo, contrasena = :contrasena
                      WHERE id_empleado = :id_empleado";
            $stmt_2 = $pdo->prepare($sql_2);
            $stmt_2->execute([
                'correo' => $correo,
                'contrasena' => $hash,
                'id_empleado' => $empleado_id
            ]);
        }

        header("Location: index.php?view=empleados");
        exit;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editor de Empleados</title>
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
    <h2>Editor de Empleados</h2>
    <div>
        <div>
            <span>Datos Básicos</span>
            <span onclick="window.history.back()">&#10005;</span>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <div>
                <div>
                    <div>
                        <label>Apellido Paterno: </label>
                        <input type="text" name="apellido_p" maxlength="50" required value="<?= htmlspecialchars($empleado['apellido_paterno'] ?? '') ?>">
                    </div>
                    <div>
                        <label>Apellido Materno: </label>
                        <input type="text" name="apellido_m" maxlength="50" required value="<?= htmlspecialchars($empleado['apellido_materno'] ?? '') ?>">
                    </div>
                </div>

                <label>Nombre(s): </label>
                <input type="text" name="nombres" maxlength="50" required value="<?= htmlspecialchars($empleado['nombre'] ?? '') ?>">

                <label>Correo: </label>
                <input type="email" name="correo" maxlength="100" required value="<?= htmlspecialchars($empleado['correo'] ?? '') ?>">

                <div>
                    <div>
                        <label>Contraseña: </label>
                        <input type="password" name="contra" maxlength="255" required>
                    </div>
                    <div>
                        <label>Teléfono: </label>
                        <input type="text" name="telefono" maxlength="20" required value="<?= htmlspecialchars($empleado['celular'] ?? '') ?>">
                    </div>
                    <div>
                        <button>Cambiar Contraseña</button>
                    </div>
                </div>

                <div>
                    <div>
                        <label>Calle: </label>
                        <input type="text" name="calle" maxlength="100" required value="<?= htmlspecialchars($empleado['calle'] ?? '') ?>">
                    </div>
                    <div>
                        <label>No. Ext: </label>
                        <input type="text" name="num_ext" maxlength="10" required value="<?= htmlspecialchars($empleado['num_ext'] ?? '') ?>">
                    </div>
                    <div>
                        <label>No. Int: </label>
                        <input type="text" name="num_int" maxlength="10" value="<?= htmlspecialchars($empleado['num_int'] ?? '') ?>">
                    </div>
                </div>

                <div>
                    <div>
                        <label>Colonia: </label>
                        <input type="text" name="colonia" maxlength="100" required value="<?= htmlspecialchars($empleado['colonia'] ?? '') ?>">
                    </div>
                    <div>
                        <label>Código Postal: </label>
                        <input type="text" name="cp" maxlength="10" required value="<?= htmlspecialchars($empleado['cp'] ?? '') ?>">
                    </div>
                </div>

                <div>
                    <div>
                        <label>Estado: </label>
                        <input type="text" name="estado" maxlength="100" required value="<?= htmlspecialchars($empleado['estado'] ?? '') ?>">
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
                        <select id="id_rol" name="id_rol" required>
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
                <button type="button" onclick="window.history.back()">Cancelar</button>
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
        });
    </script>
</body> 
</html>