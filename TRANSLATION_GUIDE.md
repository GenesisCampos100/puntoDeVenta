# 📘 Guía del Sistema de Traducción - Rama Genesis

## 🎯 Descripción
Sistema de traducción bilingüe **Español/Inglés** integrado desde la rama Zinedine a Genesis.

## 📦 Archivos Agregados

### 1. **src/config/translation.php** 
Sistema principal de traducción con:
- Función `__($key)` para obtener traducciones
- Manejo automático de sesión de idioma
- Idioma por defecto: Español (ES)
- Cambio de idioma mediante `$_SESSION['lang']`

### 2. **src/lang/es.php**
Archivo de traducciones en **Español** con 800+ claves de traducción.

### 3. **src/lang/en.php**
Archivo de traducciones en **Inglés** con 800+ claves de traducción.

### 4. **src/scripts/tema.js** (actualizado)
Script del tema con soporte de idioma integrado.

---

## 🚀 Cómo Usar

### **Paso 1: Incluir el sistema de traducción**
En cualquier archivo PHP donde quieras usar traducciones:

```php
<?php
require_once __DIR__ . '/../config/translation.php';
?>
```

### **Paso 2: Usar la función de traducción**
Reemplaza textos estáticos con llamadas a `__()`:

**Antes:**
```php
<h1>Bienvenido</h1>
<button>Guardar</button>
```

**Después:**
```php
<h1><?= __('welcome') ?></h1>
<button><?= __('save') ?></button>
```

### **Paso 3: Cambiar idioma**
Para cambiar el idioma del sistema:

```php
// Cambiar a Inglés
$_SESSION['lang'] = 'en';

// Cambiar a Español
$_SESSION['lang'] = 'es';
```

### **Paso 4: Selector de idioma en la interfaz**
Agregar un selector de idioma (ejemplo en el navbar):

```html
<div class="language-selector">
    <a href="?lang=es" class="<?= ($_SESSION['lang'] ?? 'es') === 'es' ? 'active' : '' ?>">
        🇪🇸 ES
    </a>
    <a href="?lang=en" class="<?= ($_SESSION['lang'] ?? 'es') === 'en' ? 'active' : '' ?>">
        🇬🇧 EN
    </a>
</div>
```

En tu archivo `index.php` o `layout.php`:
```php
// Capturar cambio de idioma
if (isset($_GET['lang']) && in_array($_GET['lang'], ['es', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
```

---

## 📚 Claves de Traducción Disponibles

### Ejemplos de claves comunes:

| Clave | Español | Inglés |
|-------|---------|--------|
| `welcome` | Bienvenido | Welcome |
| `save` | Guardar | Save |
| `cancel` | Cancelar | Cancel |
| `delete` | Eliminar | Delete |
| `edit` | Editar | Edit |
| `add` | Agregar | Add |
| `search` | Buscar | Search |
| `products` | Productos | Products |
| `sales` | Ventas | Sales |
| `employees` | Empleados | Employees |
| `clients` | Clientes | Clients |
| `logout` | Cerrar sesión | Logout |

### Ver todas las claves disponibles:
- **Español:** `src/lang/es.php`
- **Inglés:** `src/lang/en.php`

---

## ✨ Agregar Nuevas Traducciones

### 1. Editar `src/lang/es.php`:
```php
return [
    // ... traducciones existentes ...
    'nueva_clave' => 'Nuevo Texto en Español',
];
```

### 2. Editar `src/lang/en.php`:
```php
return [
    // ... traducciones existentes ...
    'nueva_clave' => 'New Text in English',
];
```

### 3. Usar la nueva clave:
```php
<?= __('nueva_clave') ?>
```

---

## 🔧 Traducciones en JavaScript

Para usar traducciones en JavaScript, usa `json_encode()`:

```php
<script>
const messages = {
    confirm_delete: <?= json_encode(__('confirm_delete')) ?>,
    success: <?= json_encode(__('success')) ?>,
    error: <?= json_encode(__('error')) ?>
};

Swal.fire({
    title: messages.confirm_delete,
    icon: 'warning'
});
</script>
```

---

## 🎨 Modo Oscuro + Traducción

El archivo `tema.js` ahora soporta ambas funcionalidades:
- Cambio de tema (claro/oscuro)
- Persistencia de idioma en localStorage

```javascript
// El tema.js actualizado maneja automáticamente:
- localStorage para tema oscuro
- Sincronización con $_SESSION['lang']
```

---

## ⚠️ Notas Importantes

### ✅ **Ventajas:**
- No afecta la lógica existente de Genesis
- Fácil de implementar página por página
- 800+ traducciones listas para usar
- Soporte completo para formularios, alertas, modales

### 🚨 **Consideraciones:**
- **No traduzcas** nombres de columnas de base de datos
- **No traduzcas** valores dinámicos del usuario (nombres, direcciones, etc.)
- **Solo traduce** elementos de interfaz estáticos

### 📝 **Buenas prácticas:**
```php
// ✅ CORRECTO
<label><?= __('name') ?>:</label>
<input value="<?= htmlspecialchars($usuario['nombre']) ?>">

// ❌ INCORRECTO (no traducir valores de BD)
<input value="<?= __($usuario['nombre']) ?>">
```

---

## 🔄 Integración Gradual

Puedes integrar las traducciones **página por página** sin afectar el resto:

1. **Elige una página** (ej: `productos_contenido.php`)
2. **Agrega** `require_once __DIR__ . '/../config/translation.php';`
3. **Reemplaza** textos estáticos con `__('clave')`
4. **Prueba** cambiando el idioma
5. **Repite** con otras páginas

---

## 📞 Soporte

- **Archivos principales:** `src/config/translation.php`, `src/lang/`
- **Commits relacionados:** Ver historial de `genesis-translation`
- **Basado en:** Sistema de traducción de rama Zinedine

---

## 📄 Licencia
Este sistema de traducción está integrado como parte del proyecto PrismaMK2C.

---

**¡Ahora Genesis tiene soporte completo de traducción ES/EN! 🎉**
