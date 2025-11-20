<?php
// Configuración SMTP para PHPMailer
// Configuración para SendGrid
return [
    'host' => 'smtp.sendgrid.net',
    'username' => 'apikey', // Este valor es siempre 'apikey' para SendGrid
    'password' => 'G.Bv6jLrdMTQKCE8b_P6ekKQ.OOfW1rvJjixFR4a_2kLYX90dZidhON3u5JmUQn7a558', // Tu clave de API de SendGrid
    'port' => 587,
    'secure' => 'tls', // 'tls' o 'ssl'
    'from_email' => 'prisma_pos@outlook.com', // El correo que verificaste en SendGrid
    'from_name' => 'Soporte Prisma',
];
