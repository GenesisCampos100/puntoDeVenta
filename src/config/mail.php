<?php
// Configuración SMTP para PHPMailer
// Configuración para SendGrid
return [
    'host' => 'smtp.sendgrid.net',
    'username' => 'apikey', // Este valor es siempre 'apikey' para SendGrid
    'password' => '#', // Tu clave de API de SendGrid
    'port' => 587,
    'secure' => 'tls', // 'tls' o 'ssl'
    'from_email' => 'prisma_pos@outlook.com', // El correo que verificaste en SendGrid
    'from_name' => 'Soporte Prisma',
];
