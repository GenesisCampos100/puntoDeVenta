<?php 
    require_once __DIR__ . '/../config/db.php';


    $busqueda = $_GET['busqueda'] ?? '';
    $puesto = $_GET['puesto'] ?? '';
    $orden = $_GET['orden'] ?? 'u.nombre_completo ASC';
    $vista_actual = $_GET['view'] ?? 'empleados_contenido';

    $sql = "SELECT
                u.id AS numero,
                u.nombre_completo AS nombre,
                u.correo AS correo,
                u.estatus AS estatus,
                u.fecha AS fecha_ingreso,
                r.nombre AS puesto
            FROM usuarios u LEFT JOIN roles r ON u.rol_id = r.id
            WHERE 1=1";

    if(!empty($busqueda)) $sql .= " AND (u.nombre_completo LIKE :busqueda OR u.correo LIKE :busqueda)";
    if(!empty($puesto)) $sql .= " AND u.rol_id = :puesto";

    $sql .= " ORDER BY $orden";

    $stmt = $pdo->prepare($sql);

    $params = [];

    if(!empty($busqueda)) {
        $params[':busqueda'] = "%$busqueda%";
    }

    if(!empty($puesto)) {
        $params[':puesto'] = $puesto;
    }

    $stmt->execute($params);
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $pdo->query("SELECT id AS id_rol, nombre FROM roles");
    $puestos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empleados</title>
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

        /* --- BARRA DE HERRAMIENTAS (TOOLBAR) --- */
        .toolbar {
            display: flex;
            justify-content: center; 
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
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            transition: background-color 0.2s;
        }

        .toolbar .btn-accion:hover {
            background-color: #f3f4f6;
        }

        /* Botón "Agregar producto" */
        .btn-agregar {
            background: #f43f5e; 
            color: white;
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: background-color 0.2s;
        }

        .btn-agregar:hover {
            background-color: #e11d48;
        }

        /* Ocultar/Mostrar los selects nativos */
        .toolbar form select {
            display: none; /* Oculto por defecto */
            position: absolute;
            z-index: 50;
            margin-top: 5px; 
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            min-width: 180px;
        }
        .toolbar form button[type="submit"] {
            display: none;
        }
        .select-visible {
            display: block !important;
        }

        /* --- CONTENEDOR DE PRODUCTOS (LA TABLA) --- */
        .productos-container {
            width: 90%; 
            max-width: 1000px; 
            margin: 0 auto 40px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1); 
            overflow: hidden;
            border: 1px solid #e5e7eb; 
        }

        table { 
            width: 100%; 
            border-collapse: collapse; 
        }

        /* Cabecera de la tabla */
        thead { 
            background: #2f455c; 
            color: white; 
        }
        thead th {
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 14px 16px; 
        }

        th, td { 
            padding: 16px; 
            text-align: left; 
            border-bottom: none; 
        }

        tr {
            border-bottom: 1px solid #eee;
        }
        tbody tr:last-child {
            border-bottom: none;
        }

        .btn-editar, .btn-eliminar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            border: none;
            color: white;
            cursor: pointer;
        }

        .btn-editar {
            background: #f43f5e;
        }

        .btn-editar:hover {
            background: #e11d48;
        }

        .btn-eliminar {
            background: #b6c649;
        }

        .btn-eliminar:hover {
            background: #b4c24d;
        }

        .btn-editar {
            margin-right: 8px;
            margin-bottom: 3px;
        }
    </style>
</head>
<body>
    <h2>EMPLEADOS</h2>

    <div class="toolbar">
        <form method="GET" id="toolbar-form" action="index.php">
            <input type="hidden" name="view" value="empleados">

            <div class="search-container">
                <span class="search-icon" onclick="document.getElementById('toolbar-form').submit()">🔍</span>
                <input type="text" id="busqueda-input" name="busqueda" placeholder="Buscar empleados..."
                       value="<?= htmlspecialchars($busqueda) ?>"
                       onkeydown="if(event.key === 'Enter') document.getElementById('toolbar-form').submit();">
                <span class="clear-icon" onclick="document.getElementById('busqueda-input').value=''; document.getElementById('toolbar-form').submit();">✖</span>
            </div>

            <div style="position: relative;">
                <button type="button" class="btn-accion" onclick="toggleSelect(event, 'puesto-select')">
                    <span class="icon">⚙</span> Filtrar
                </button>

                <select name="puesto" id="puesto-select" onchange="document.getElementById('toolbar-form').submit()">
                    <option value="">-- Todos los puestos --</option>
                    <?php foreach ($puestos as $pu): ?>
                        <option value="<?= $pu['id_rol']?>" <?= ($puesto == $pu['id_rol']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pu['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="position: relative;">
                <button type="button" class="btn-accion" onclick="toggleSelect(event, 'orden-select')">
                    <span class="icon">⇅</span> Ordenar
                </button>
                <select name="orden" id="orden-select" onchange="document.getElementById('toolbar-form').submit()">
                    <option value="u.nombre_completo ASC" <?= ($orden == 'u.nombre_completo ASC') ? 'selected' : '' ?>>Nombre A-Z</option>
                    <option value="u.nombre_completo DESC" <?= ($orden == 'u.nombre_completo DESC') ? 'selected' : '' ?>>Nombre Z-A</option>
                    <option value="u.correo ASC" <?= ($orden == 'u.correo ASC') ? 'selected' : '' ?>>Correo A-Z</option>
                    <option value="u.correo DESC" <?= ($orden == 'u.correo DESC') ? 'selected' : '' ?>>Correo Z-A</option>
                </select>
            </div>
        </form>

        <a href="index.php?view=agregar_empleado" class="btn-agregar">
            <span class="icon">➕</span> Agregar empleado
        </a>
    </div>

    <div class="productos-container">
        <table>
            <thead>
                <tr>
                    <th style="width: 10%;">No.</th>
                    <th style="width: 45%">Nombre</th>
                    <th style="width: 20%">Correo</th>
                    <th style="width: 30%">Estatus</th>
                    <th style="width: 30%">Fecha de Ingreso</th>
                    <th style="width: 10%"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($empleados)): ?>
                    <?php $isFirst = true; ?>
                    <?php foreach ($empleados as $emp): ?>
                        <tr class="<?= $isFirst ? 'first-row' : '' ?>">
                            <td><?= htmlspecialchars($emp['numero']) ?></td>
                            <td><?= htmlspecialchars($emp['nombre']) ?></td>
                            <td><?= htmlspecialchars($emp['correo']) ?></td>
                            <td>
                                <span style="color: <?= $emp['estatus'] == 1 ? 'green' : 'red' ?>;"><?= $emp['estatus'] == 1 ? 'Activo' : 'Inactivo' ?></span>
                            </td>
                            <td><?= htmlspecialchars($emp['fecha_ingreso']) ?></td>
                            <td>
                                <a href="index.php?view=editar_empleado&id=<?= $emp['numero'] ?>" class="btn-editar">✎</a>
                                <a href="index.php?view=eliminar_empleado&id=<?= $emp['numero'] ?>" class="btn-eliminar" onclick="return confirm('¿Estás seguro de eliminar este empleado?')">🗑︎</a>
                            </td>
                        </tr>
                        <?php $isFirst = false; ?>

                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 20px;">No se encontraron empleados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        function toggleSelect(event, selectId) {
            event.stopPropagation();
            const select = document.getElementById(selectId);
            const button = event.currentTarget;
            
            // Ocultar todos los demás selects
            document.querySelectorAll('.toolbar form select').forEach(s => {
                if (s.id !== selectId) {
                    s.classList.remove('select-visible');
                    s.style.display = 'none';
                }
            });

            // Toggle del select actual
            if (select.classList.contains('select-visible')) {
                select.classList.remove('select-visible');
                select.style.display = 'none';
            } else {
                select.classList.add('select-visible');
                select.style.top = `${button.offsetHeight + 5}px`;
                select.style.left = '0';
                select.style.display = 'block';

                // Listener para cerrar al hacer clic fuera
                document.addEventListener('click', function closeSelect(e) {
                    if (!select.contains(e.target) && e.target !== button) {
                        select.classList.remove('select-visible');
                        select.style.display = 'none';
                        document.removeEventListener('click', closeSelect);
                    }
                });
            }
        }
    </script>
</body>
</html>