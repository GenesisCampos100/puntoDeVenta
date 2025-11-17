<?php
// src/config/translation.php

// Iniciar la sesión si aún no está activa.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Definir los idiomas disponibles y el idioma por defecto.
$available_langs = ['es', 'en'];
$default_lang = 'es';

// 2. Determinar el idioma a usar.
$lang = $default_lang;

// Si se pasa un idioma por la URL y está en la lista de disponibles, se usa y se guarda en la sesión.
if (isset($_GET['lang']) && in_array($_GET['lang'], $available_langs)) {
    $lang = $_GET['lang'];
    $_SESSION['lang'] = $lang;
} 
// Si no, se comprueba si ya hay un idioma guardado en la sesión.
elseif (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $available_langs)) {
    $lang = $_SESSION['lang'];
}

// 3. Cargar el archivo de idioma correspondiente.
$lang_file = __DIR__ . '/../lang/' . $lang . '.php';

// Se carga un array vacío si el archivo no existe para evitar errores.
$translations = file_exists($lang_file) ? require $lang_file : [];

/**
 * Función de traducción.
 * Busca una clave en el array de traducciones cargado.
 *
 * @param string $key La clave de la traducción (ej. 'login_title').
 * @return string El texto traducido o la clave misma si no se encuentra.
 */
function __($key) {
    global $translations;
    return $translations[$key] ?? $key;
}
