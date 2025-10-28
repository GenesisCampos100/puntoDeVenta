<?php
/**********************************************************************
 * validar_login.php
 * ------------------------------------------------------------
 * Este archivo valida las credenciales del usuario desde un formulario
 * de inicio de sesión (login.php). 
 * Si las credenciales son correctas, inicia la sesión y redirige
 * al panel principal (index.php?view=nueva_venta).
 * ------------------------------------------------------------
 **********************************************************************/

// 🔧 Mostrar errores (solo durante desarrollo; quítalo en producción)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 📦 Iniciar el buffer de salida (evita errores con header)
ob_start();

// 🔐 Iniciar sesión
session_start();

// 🧩 Conexión a la base de datos (usa PDO)
require_once __DIR__ . '/../config/db.php';

/**********************************************************************
 * 1️⃣  Verificar que el formulario fue enviado con método POST
 **********************************************************************/
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // 🧹 Limpiar y capturar los datos del formulario
    $usuario = trim($_POST['usuario']);     // puede ser nombre o correo
    $password = $_POST['password'];         // contraseña ingresada por el usuario

    /******************************************************************
     * 2️⃣  Buscar al usuario en la base de datos
     * --------------------------------------------------------------
     * La consulta une tres tablas:
     *   - usuarios (tiene el correo y contraseña)
     *   - empleados (nombre, apellidos)
     *   - roles (nombre del rol)
     ******************************************************************/
    $stmt = $pdo->prepare("
        SELECT 
            u.id_usuario,
            e.nombre,
            e.apellido_paterno,
            e.apellido_materno,
            u.correo,
            u.contrasena AS hash_contrasena,          -- ⚠️ Campo tal cual en tu base (con “ñ”)
            r.nombre_rol
        FROM usuarios u
        INNER JOIN empleados e ON u.id_empleado = e.id_empleado
        INNER JOIN roles r ON e.id_rol = r.id_rol
        WHERE e.nombre = :usuario OR u.correo = :usuario
        LIMIT 1
    ");

    // Ejecutar la consulta con el parámetro
    $stmt->execute(['usuario' => $usuario]);

    // Obtener el resultado en un arreglo asociativo
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    /******************************************************************
     * 3️⃣  Validar usuario y contraseña
     ******************************************************************/
    if ($user) {
        // Comparar la contraseña ingresada con el hash almacenado
        if (password_verify($password, $user['hash_contrasena'])) {

            /**********************************************************
             * 4️⃣  Credenciales correctas → Iniciar sesión
             **********************************************************/
            $_SESSION['usuario_id'] = $user['id_usuario'];
            $_SESSION['correo'] = $user['correo'];
            $_SESSION['rol'] = $user['nombre_rol'];

            // 🧹 Limpiar el buffer antes de enviar headers
            ob_end_clean();

            // 🚀 Redirigir al panel principal (caja / ventas)
            header("Location: ../index.php?view=nueva_venta");
            exit;

        } else {
            // ❌ Contraseña incorrecta
            $_SESSION['error'] = "Usuario o contraseña incorrectos.";
            ob_end_clean();
            header("Location: ../pages/login.php");
            exit;
        }

    } else {
        // ❌ No se encontró ningún usuario con ese nombre o correo
        $_SESSION['error'] = "Usuario o contraseña incorrectos.";
        ob_end_clean();
        header("Location: ../pages/login.php");
        exit;
    }
}

/**********************************************************************
 * 🧱  NOTAS IMPORTANTES
 * --------------------------------------------------------------
 *  - El campo de contraseña en la base debe llamarse exactamente `contraseña`.
 *  - Asegúrate de que tu conexión PDO use UTF8:
 *      PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
 *  - Los usuarios deben tener contraseñas hasheadas con password_hash().
 *  - No debe haber espacios ni saltos de línea antes del <?php
 **********************************************************************/
?>
