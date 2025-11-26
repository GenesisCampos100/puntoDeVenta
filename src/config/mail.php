<?php
require_once __DIR__ . '/../../vendor/autoload.php'; // Ajusta la ruta si estás en src/config/
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../'); // Ruta al .env
$dotenv->load();
// Configuración SMTP para PHPMailer
// Configuración para SendGrid
return [
    'host' => 'smtp.sendgrid.net',
    'username' => 'apikey', // Este valor es siempre 'apikey' para SendGrid
    'password' => $_ENV['API_KEY'], // Cargado desde .env
    'port' => 587,
    'secure' => 'tls', // 'tls' o 'ssl'
    'from_email' => 'prisma_pos@outlook.com', // El correo que verificaste en SendGrid
    'from_name' => 'Soporte Prisma',
];
