<?php
// src/includes/language_switcher.php

/**
 * Componente reutilizable para el selector de idioma.
 *
 * Este script genera los enlaces para cambiar entre 'es' e 'en',
 * manteniendo cualquier otro parámetro existente en la URL (como tokens, IDs, etc.).
 *
 * Requiere que la variable `$lang` esté definida antes de incluir este archivo.
 * La variable `$lang` se define en `src/config/translation.php`.
 */

// 1. Obtener todos los parámetros actuales de la URL.
$queryParams = $_GET;

// 2. Construir el enlace para 'Español' (es).
// Se añade o sobreescribe el parámetro 'lang'.
$queryParams['lang'] = 'es';
// http_build_query() convierte el array en una cadena de consulta URL-encoded.
$es_link = '?' . http_build_query($queryParams);

// 3. Construir el enlace para 'Inglés' (en).
$queryParams['lang'] = 'en';
$en_link = '?' . http_build_query($queryParams);
?>

<!-- HTML del selector de idioma -->
<div class="language-selector">
    <a href="<?php echo htmlspecialchars($es_link); ?>" class="<?php echo ($lang === 'es') ? 'active' : ''; ?>">ES</a>
    <a href="<?php echo htmlspecialchars($en_link); ?>" class="<?php echo ($lang === 'en') ? 'active' : ''; ?>">EN</a>
</div>
