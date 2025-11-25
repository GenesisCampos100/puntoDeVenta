<?php 
    // Libreria PHP para poder mandar correo de politicas de privacidad
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;

    // Incluye los archivos necesarios de PHPHMailer
    require __DIR__ . '/../../PHPMailer/src/PHPMailer.php';
    require __DIR__ . '/../../PHPMailer/src/SMTP.php';
    require __DIR__ . '/../../PHPMailer/src/Exception.php';

    // Enviar correo de bienvenida con politicas de privacidad
    $mail = new PHPMailer(true);

    // Habilitar debug en entornos de desarrollo/local. Cambiar a false en producción.
    $mailDebug = (
        (isset($_SERVER['SERVER_NAME']) && (strpos($_SERVER['SERVER_NAME'], 'localhost') !== false || $_SERVER['SERVER_NAME'] === '127.0.0.1'))
        || php_sapi_name() === 'cli'
    );

    try {
    $nombre = $_GET['nombre'] ?? '';
    $apellido_paterno = $_GET['apellido_paterno'] ?? '';
    $apellido_materno = $_GET['apellido_materno'] ?? '';
    $correo = $_GET['correo'] ?? '';
    $id_empleado = $_GET['id_empleado'] ?? '';
    $nombre_p = $_GET['nombre_p'] ?? '';


        $mail->isSMTP();
        // Mostrar debug si estamos en desarrollo para facilitar diagnóstico
        $mail->SMTPDebug = $mailDebug ? SMTP::DEBUG_SERVER : SMTP::DEBUG_OFF;
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer: [{$level}] {$str}");
        };

        $mail->Host = 'smtp.sendgrid.net';
        $mail->SMTPAuth = true;
        $mail->Username = 'apikey';
        $mail->Password = '#'; // Reemplaza con tu clave de API de SendGrid
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ];

        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        $mail->setFrom('prisma_pos@outlook.com', 'Acceso y Aviso de Privacidad de Uso de Datos');
        $mail->addAddress($correo, $nombre . ' ' . $apellido_paterno);

        $mail->isHTML(true);
        $mail->Subject = 'Bienvenido a Prisma - Acceso y Aviso de Privacidad';
        $mail->Body = 
        "
            <html>
            <head>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        color: #333;
                    }

                    .container {
                        border: 1px solid #ddd;
                        border-radius: 10px;
                        padding: 20px;
                        background-color: #fafafa;
                    }

                    .highlight {
                        font-weight: bold;
                        color: #0056b3
                    }
                </style>
            </head>
            <body>
                <div class='container'>
                    <p>Estimado(a) <strong>{$nombre} {$apellido_paterno} {$apellido_materno}</strong>,</p>
                    <p>Nos complace darle la bienvenida a <strong>PRISMA</strong>. Su número de empleado es <strong>{$id_empleado}</strong> y desempeñará el cargo de <strong>{$nombre_p}</strong></p>
                    <p>Le deseamos mucho éxito en nuestro equipo. A continuación encontrará los datos de acceso a su cuenta en el sistema: </p>

                    <p>
                        <span class='highlight'>Usuario: </span> {$correo} <br>
                        <span class='highlight'>Contraseña: </span> La que usted proporcionó al momento de su registro.
                    </p>

                    <p>Por motivos de seguridad, le recomendamos <strong>cambiar su contraseña al primer inicio de sesión</strong>.</p>
                    <p>También se adjuntarán los documentos PDF con los <strong>Avisos de Privacidad de Uso de Datos</strong>, los cuales le invitamos a leer detenidamente.</p>
                    <p>Si tiene alguna duda o requiere asistencia, puede comunicarse con el área de soporte.</p>

                    <br>
                    <p>Atentamente,</p>
                    <p><strong>Equipo de PRISMA</strong></p>
                    <small>prisma_pos@outlook.com</small>
                </div>
            </body>
            </html>
        ";

        $mail->addAttachment(__DIR__ . '/../documents/AVISO DE PRIVACIDAD INTEGRAL.pdf', 'AVISO DE PRIVACIDAD INTEGRAL.pdf');
        $mail->addAttachment(__DIR__ . '/../documents/AVISO DE PRIVACIDAD SIMPLIFICADO.pdf', 'AVISO DE PRIVACIDAD SIMPLIFICADO.pdf');

        $mail->send();
    } catch (Exception $e) {
        $errMsg = "Error al enviar el correo de bienvenida: " . $e->getMessage();
        error_log($errMsg);
        // Si estamos en modo debug devolvemos el error al frontend para diagnóstico
        if ($mailDebug) {
            echo json_encode(["error" => $errMsg, "icon" => "error"]);
            exit();
        }
        // En producción solo logueamos y continuamos
    }
?>