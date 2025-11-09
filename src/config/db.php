<?php
// Configuración de la conexión a la base de datos
$host = "localhost";   
$dbname = "bdpuntodeventa"; 
$username = "root";   
$password = "";       

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error en la conexión: " . $e->getMessage());
}  // todo tu código de subir foto y actualizar DB
  catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>