<?php
// Configuración de la conexión a la base de datos
$host = "localhost";   
$dbname = "puntodeventa"; 
$username = "root";   
$password = "";       

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Re-throw exception so it can be caught by the API
    throw $e;
}
