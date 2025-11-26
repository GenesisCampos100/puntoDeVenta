<?php
// src/config/translation.php

// Cargar archivo .env
$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    $envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envLines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (!getenv($key)) {
            putenv("$key=$value");
        }
    }
}

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

// Ruta cache para traducciones dinámicas (crea carpeta si no existe)
$cacheDir = __DIR__ . '/../lang/cache';
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0777, true);
}
$cacheFile = $cacheDir . '/cache_' . $lang . '.json';
$dynamicCache = [];
if (file_exists($cacheFile)) {
    $jsonData = file_get_contents($cacheFile);
    $decoded = json_decode($jsonData, true);
    if (is_array($decoded)) {
        $dynamicCache = $decoded;
    }
}

/**
 * Guarda cache en disco de manera segura.
 */
function save_dynamic_cache($file, $cache) {
    $tmp = $file . '.tmp';
    file_put_contents($tmp, json_encode($cache, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    @rename($tmp, $file);
}

/**
 * Función de traducción.
 * Busca una clave en el array de traducciones cargado.
 *
 * @param string $key La clave de la traducción (ej. 'login_title').
 * @return string El texto traducido o la clave misma si no se encuentra.
 */
function __($key) {
    global $translations;
    return $translations[$key] ?? $key; // Mantener comportamiento original para claves
}

/**
 * Traducción dinámica de texto arbitrario que no está en el diccionario.
 * Usa Google Cloud Translation (v2) si hay API key; cachea resultados.
 *
 * @param string $text Texto fuente a traducir.
 * @param string|null $targetLang Idioma destino (null => idioma actual).
 * @return string Traducción o texto original si falla.
 */
function tr_dynamic($text, $targetLang = null) {
    global $lang, $dynamicCache, $cacheFile;
    $text = trim($text);
    // Evitar traducción de textos vacíos, muy largos, números o códigos
    if ($text === '' || mb_strlen($text) > 5000 || is_numeric($text)) {
        return $text;
    }
    // No traducir SKUs, códigos, fechas comunes
    if (preg_match('/^[A-Z0-9\-_]+$/i', $text) || preg_match('/^\d{4}-\d{2}-\d{2}/', $text)) {
        return $text;
    }
    $target = $targetLang ?: $lang;
    // Si estamos en español, no hay que traducir (el contenido ya está en español)
    if ($target === 'es') {
        return $text;
    }
    // Heurística: si el texto ya parece estar en inglés, no traducir
    $englishWords = preg_match_all('/\b(the|and|of|for|with|to|from|in|is|are|was|were|this|that|have|has|will|can|may)\b/i', $text);
    if ($englishWords > 2) {
        return $text;
    }
    $cacheKey = sha1($target . '|' . $text);
    if (isset($dynamicCache[$cacheKey])) {
        return $dynamicCache[$cacheKey];
    }
    $translated = provider_translate($text, $target);
    if ($translated !== $text) {
        $dynamicCache[$cacheKey] = $translated;
        save_dynamic_cache($cacheFile, $dynamicCache);
    }
    return $translated;
}

// ================================
// Provider Abstraction (DeepL)
// ================================
function provider_translate($text, $target) {
    // Usar DeepL si hay API_TRADUCCION configurada
    $apiKey = getenv('API_TRADUCCION');
    if ($apiKey) {
        return deepl_translate($text, $target);
    }
    return $text; // sin proveedor activo
}

function deepl_translate($text, $target) {
    $apiKey = getenv('API_TRADUCCION');
    if (!$apiKey) return $text;
    // DeepL target codes: EN, ES (may append -US / -GB optionally). Usaremos EN / ES.
    $targetCode = strtoupper($target);
    if ($targetCode === 'EN') $targetCode = 'EN';
    elseif ($targetCode === 'ES') $targetCode = 'ES';
    // Endpoint fijo para API gratuita de DeepL
    $endpoint = 'https://api-free.deepl.com/v2/translate';
    $params = [
        'auth_key' => $apiKey,
        'text' => $text,
        'target_lang' => $targetCode,
    ];
    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 6,
        CURLOPT_POSTFIELDS => http_build_query($params),
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
    ]);
    $resp = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err || !$resp) return $text;
    $data = json_decode($resp, true);
    if (!isset($data['translations'][0]['text'])) return $text;
    $translated = $data['translations'][0]['text'];
    return html_entity_decode($translated, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Helper para traducir sólo si cambia de idioma (por ejemplo contenido de BD).
 * @param string $text
 * @return string
 */
function tr_content($text) {
    return tr_dynamic($text);
}

/**
 * Traducción especializada para categorías comunes (diccionario controlado).
 * Asegura resultados consistentes cuando el proveedor devuelve texto sin cambios.
 */
function tr_category($name) {
    global $lang;
    $src = trim((string)$name);
    if ($src === '') return $src;
    // Diccionario ES -> EN
    $map_es_en = [
        'camisetas' => 'T-shirts',
        'blusas' => 'Blouses',
        'pantalones' => 'Pants',
        'jeans' => 'Jeans',
        'shorts' => 'Shorts',
        'faldas' => 'Skirts',
        'vestidos' => 'Dresses',
        'sudaderas' => 'Sweatshirts',
        'chamarras' => 'Jackets',
        'trajes' => 'Suits',
        'calcetines' => 'Socks',
    ];
    if ($lang === 'en') {
        $key = mb_strtolower($src, 'UTF-8');
        if (isset($map_es_en[$key])) {
            return $map_es_en[$key];
        }
        return tr_dynamic($src, 'en');
    }
    // Si no es inglés destino, devolver tal cual
    return $src;
}

/**
 * Obtiene el idioma actual (por conveniencia en vistas).
 */
function current_lang() {
    global $lang; return $lang;
}
