<?php 
    require_once __DIR__ . '/../config/db.php';
    require_once __DIR__ . '/../config/translation.php';

    $busqueda = $_GET['busqueda'] ?? '';
    $puesto = $_GET['puesto'] ?? '';
    $orden = $_GET['orden'] ?? 'e.nombre ASC';
    $allowed_order = ['e.nombre ASC', 'e.nombre DESC', '.id_empleado ASC', 'e.id_empleado DESC'];
    if(!in_array($orden, $allowed_order)) $orden = 'e.nombre ASC';
    $vista_actual = $_GET['view'] ?? 'empleados_contenido';

    $sql = "SELECT
                e.id_empleado AS numero,
                CONCAT(e.nombre, ' ', e.apellido_paterno, ' ', e.apellido_materno) AS nombre_completo,
                u.correo AS correo,
                e.estatus AS estatus,
                e.fecha AS fecha
            FROM usuarios u 
            INNER JOIN empleados e ON u.id_empleado = e.id_empleado
            LEFT JOIN roles r ON e.id_rol = r.id_rol
            WHERE 1=1";

    if(!empty($busqueda)) $sql .= " AND (
                                e.id_empleado LIKE :busqueda
                                OR e.nombre LIKE :busqueda
                                OR e.apellido_paterno LIKE :busqueda
                                OR e.apellido_materno LIKE :busqueda
                                OR u.correo LIKE :busqueda)";
    if(!empty($puesto)) $sql .= " AND e.id_rol = :puesto";

    $sql .= " ORDER BY $orden";

    $stmt = $pdo->prepare($sql);

    $params = [];

    if (!empty($busqueda)) {
        $sql .= " AND (e.nombre LIKE :busqueda OR e.apellido_paterno LIKE :busqueda OR e.apellido_materno LIKE :busqueda OR u.correo LIKE :busqueda)";
        $params[':busqueda'] = "%$busqueda%";
    }

    if (!empty($puesto)) {
        $sql .= " AND e.id_rol = :puesto";
        $params[':puesto'] = $puesto;
    }

    // Validar que el orden sea uno de los permitidos para evitar inyección SQL
    $ordenes_permitidos = ['e.nombre ASC', 'e.nombre DESC', 'e.id_empleado ASC', 'e.id_empleado DESC'];
    if (in_array($orden, $ordenes_permitidos)) {
        $sql .= " ORDER BY $orden";
    } else {
        $sql .= " ORDER BY e.nombre ASC"; // Orden por defecto
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt_roles = $pdo->query("SELECT id_rol, nombre_rol FROM roles");
    $puestos = $stmt_roles->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'es' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('employees_title') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f7f6;
            color: #333;
        }
        h2 {
            text-align: center;
            color: #333;
            font-weight: 600;
        }
        /* 1. Contenedor Principal */
        .toolbar {
            display: grid;
            /* Tres columnas iguales para: Filter | Sort | Add Employee */
            grid-template-columns: 1fr 1fr 1fr; 
            gap: 1rem; /* Espacio entre elementos */
            align-items: center;
            margin: 20px auto 30px;
            width: 90%;
            max-width: 1000px; 
            gap: 10px; 
        }

        .toolbar form {
            display: flex;
            flex-grow: 1;
            gap: 10px;
            align-items: center;
        }

        .search-container {
            flex-grow: 1; 
            max-width: 500px; 
            position: relative;
        }

        .search-container input[type="text"] {
            padding: 10px 15px 10px 40px; 
            border: 1px solid #ddd;
            border-radius: 8px; 
            width: 100%;
            box-sizing: border-box;
            font-size: 15px;
        }

        /* HACER EL ÍCONO CLICKABLE PARA ENVIAR EL FORMULARIO */
        .search-container .search-icon { 
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af; 
            cursor: pointer; /* HACEMOS EL ÍCONO CLICKABLE */
            font-size: 18px;
            z-index: 10;
        }

        .search-container .clear-icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            cursor: pointer;
            font-weight: bold;
            font-size: 18px;
        }

        /* Botones de acción (Filtrar/Ordenar) */
        .toolbar .btn-accion {
            background: white; 
            color: #374151; 
            padding: 10px 18px;
            border: 1px solid #d1d5db; 
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
        }

        /* 2. LA CLAVE: Hacer el form "invisible" al grid */
        #toolbar-form {
            display: contents;
        }

        /* 3. El Buscador y sus iconos */
        .search-container {
            /* Que ocupe toda la primera fila (las 3 columnas) */
            grid-column: 1 / -1; 
            
            /* Centrado */
            position: relative; /* Vital para que los iconos se queden aquí */
            width: 50%; /* Ancho de la barra */
            min-width: 300px;
            margin: 0 auto 1rem auto; /* Centrar el bloque horizontalmente */
            display: flex;
            align-items: center;
        }

        .search-container input {
            width: 100%; /* Que llene el contenedor gris definido arriba */
            padding: 0.6rem 2.5rem; /* Espacio para los iconos */
            border: 1px solid #ccc;
            border-radius: 20px;
            box-sizing: border-box; /* Para que el padding no rompa el ancho */
        }

        /* Iconos dentro del buscador */
        .search-icon, .clear-icon {
            position: absolute;
            cursor: pointer;
            color: #777;
            /* Centrado vertical perfecto */
            top: 50%;
            transform: translateY(-50%);
        }

        .search-icon { left: 15px; }
        .clear-icon { right: 15px; }

        /* 4. Los Botones (Filter, Sort) */
        .emp_boton {
            display: flex;
            justify-content: center;
            width: 100%;
        }

        /* 5. El Botón Agregar (Add Employee) */
        .btn-agregar {
            /* No necesita grid-column explícito, caerá naturalmente en la 3ra posición */
            justify-self: center; /* Se centra en su celda */
            text-decoration: none;
            /* Mismos estilos visuales que tus otros botones */
            width: 90%; 
            display: inline-flex;
            justify-content: center;
            align-items: center;
            padding: 0.6rem 1.2rem;
            background-color: #000000ff;
            color: white;
            border-radius: 20px;
            gap: 0.5rem;
        }

        /* Estilos compartidos para botones dentro del form */
        .btn-accion {
            width: 90%;
            padding: 0.6rem 1.2rem;
            background-color: #B6C649;
            color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
        }

        /* Ajuste de iconos en botones */
        .btn-agregar .icon, .btn-accion .icon { font-size: 1.2em; }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        tr:hover { background-color: #f1f1f1; }
        .btn-editar, .btn-eliminar {
            color: #333;
            text-decoration: none;
            margin-right: 10px;
            font-size: 1.2rem;
        }
        .btn-eliminar { color: #dc3545; }
        select {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            z-index: 1000;
            background-color: white;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body>
    <h2><?= __('employees_h2') ?></h2>

    <div class="toolbar">
        <form method="GET" id="toolbar-form" action="index.php">
            <input type="hidden" name="view" value="empleados">

            <div class="search-container">
                <span class="search-icon" onclick="document.getElementById('toolbar-form').submit()">🔍</span>
                <input type="text" id="busqueda-input" name="busqueda" placeholder="<?= __('search_employees_placeholder') ?>"
                       value="<?= htmlspecialchars($busqueda) ?>"
                       onkeydown="if(event.key === 'Enter') document.getElementById('toolbar-form').submit();">
                <span class="clear-icon" onclick="document.getElementById('busqueda-input').value=''; document.getElementById('toolbar-form').submit();">✖</span>
            </div>

            <div class="emp_boton">
                <button type="button" class="btn-accion" onclick="toggleSelect(event, 'puesto-select')">
                    <span class="icon">⚙</span> <?= __('filter') ?>
                </button>

                <select name="puesto" id="puesto-select" onchange="document.getElementById('toolbar-form').submit()">
                    <option value=""><?= __('all_positions') ?></option>
                    <?php foreach ($puestos as $pu): ?>
                        <option value="<?= $pu['id_rol']?>" <?= ($puesto == $pu['id_rol']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pu['nombre_rol']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="emp_boton">
                <button type="button" class="btn-accion" onclick="toggleSelect(event, 'orden-select')">
                    <span class="icon">⇅</span> <?= __('sort') ?>
                </button>
                <select name="orden" id="orden-select" onchange="document.getElementById('toolbar-form').submit()">
                    <option value="e.nombre ASC" <?= ($orden == 'e.nombre ASC') ? 'selected' : '' ?>><?= __('name_az') ?></option>
                    <option value="e.nombre DESC" <?= ($orden == 'e.nombre DESC') ? 'selected' : '' ?>><?= __('name_za') ?></option>
                    <option value="e.id_empleado ASC" <?= ($orden == 'e.id_empleado ASC') ? 'selected' : '' ?>><?= __('employee_no_asc') ?></option>
                    <option value="e.id_empleado DESC" <?= ($orden == 'e.id_empleado DESC') ? 'selected' : '' ?>><?= __('employee_no_desc') ?></option>
                </select>
            </div>
        </form>

        <a href="index.php?view=agregar_empleado" class="btn-agregar">
            <span class="icon">➕</span> <?= __('add_employee') ?>
        </a>
    </div>

    <div class="productos-container">
        <table>
            <thead>
                <tr>
                    <th style="width: 10%;"><?= __('employee_no_col') ?></th>
                    <th style="width: 45%"><?= __('full_name_col') ?></th>
                    <th style="width: 20%"><?= __('email_col') ?></th>
                    <th style="width: 30%"><?= __('status_col') ?></th>
                    <th style="width: 30%"><?= __('hire_date_col') ?></th>
                    <th style="width: 10%"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($empleados)): ?>
                    <?php $isFirst = true; ?>
                    <?php foreach ($empleados as $emp): ?>
                        <tr class="<?= $isFirst ? 'first-row' : '' ?>">
                            <td><?= htmlspecialchars($emp['numero']) ?></td>
                            <td><?= htmlspecialchars($emp['nombre_completo']) ?></td>
                            <td><?= htmlspecialchars($emp['correo']) ?></td>
                            <td>
                                <span style="color: <?= $emp['estatus'] == 1 ? 'green' : 'red' ?>;"><?= $emp['estatus'] == 1 ? __('active') : __('inactive') ?></span>
                            </td>
                            <td><?= htmlspecialchars($emp['fecha']) ?></td>
                            <td>
                                <a href="index.php?view=editar_empleado&id=<?= $emp['numero'] ?>" class="btn-editar">✎</a>
                                <a href="index.php?view=eliminar_empleado&id=<?= $emp['numero'] ?>" class="btn-eliminar" data-id="<?= htmlspecialchars($emp['numero']) ?>">🗑︎</a>
                            </td>
                        </tr>
                        <?php $isFirst = false; ?>

                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 20px;"><?= __('no_employees_found') ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        function toggleSelect(event, selectId) {
            event.stopPropagation();
            var select = document.getElementById(selectId);
            var isVisible = select.style.display === 'block';
            
            // Ocultar todos los selects primero
            document.querySelectorAll('select').forEach(s => s.style.display = 'none');
            
            // Mostrar el select actual si estaba oculto
            if (!isVisible) {
                select.style.display = 'block';
            }
        }

        document.addEventListener('click', function() {
            document.querySelectorAll('select').forEach(s => s.style.display = 'none');
        });

        (function(){
            function attachDeleteHandlers() {
                document.querySelectorAll('.btn-eliminar').forEach(btn => {
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        const href = this.getAttribute('href');
                        Swal.fire({
                            title: '<?= __('are_you_sure') ?>',
                            html: '<?= __('confirm_delete_employee_text') ?>',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: '<?= __('yes_delete') ?>',
                            cancelButtonText: '<?= __('cancel') ?>'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Redirigir a la URL que ejecuta la eliminación en el servidor
                                window.location.href = href;
                            }
                        });
                    });
                });
            }

            // Adjuntar cuando el DOM esté listo
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', attachDeleteHandlers);
            } else {
                attachDeleteHandlers();
            }
        })();
    </script>
</body>
</html>