<?php
// Configuración de la conexión a la base de datos
$host = "localhost";   
$dbname = "dbpuntodeventa"; 
$username = "root";   
$password = "";       

// Conexión PDO
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Re-throw exception so it can be caught by the API
    throw $e;
}

// Conexión MySQLi (para compatibilidad con código legacy)
$conexion = new mysqli($host, $username, $password, $dbname);

if ($conexion->connect_error) {
    die("Error de conexión MySQLi: " . $conexion->connect_error);
}

$conexion->set_charset("utf8");
?>