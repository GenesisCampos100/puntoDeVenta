# Prisma — Sistema de Punto de Venta  
**Proyecto académico — 3er semestre — Ingeniería de Software**

Prisma es un sistema de punto de venta diseñado para tiendas de ropa.  
Incluye módulos de productos, inventario, ventas, caja, usuarios y envío de correos mediante PHPMailer.  
Este proyecto se desarrolló como parte de la materia **Legislación y Derecho Informático**.

---

## Contenido
1. [Descripción general](#-descripción-general)  
2. [Características principales](#-características-principales)  
3. [Tecnologías utilizadas](#-tecnologías-utilizadas)  
4. [Requisitos previos](#-requisitos-previos)  
5. [Instalación](#-instalación)  
6. [Configuración](#-configuración)  
7. [Uso del sistema](#-uso-del-sistema)  
8. [Estructura del proyecto](#-estructura-del-proyecto)  
9. [Capturas](#-capturas)  
10. [Equipo de desarrollo](#-equipo-de-desarrollo)  
11. [Estado del proyecto](#-estado-del-proyecto)  
12. [Licencia](#-licencia)

---

## Descripción general
**Prisma Punto de Venta** es un sistema enfocado en la administración básica de una tienda de ropa.  
Permite gestionar productos, controlar inventario, realizar ventas y generar comprobantes mediante correo electrónico.

Este repositorio contiene:  
- Código fuente completo  
- Sistema funcional para entorno local  
- Dependencias del proyecto  
- Documentación y guía de instalación  
- Historial de commits que refleja el trabajo colaborativo del equipo  

---

## Características principales
✔ Registro y gestión de productos  
✔ Control de inventario (altas, bajas y ajustes)  
✔ Registro de ventas con generación de recibo  
✔ Envío de correos con **PHPMailer**  
  - Comprobantes de registro de empleados  
  - Recuperación de contraseña  
✔ Interfaz responsiva con **TailwindCSS** + SASS  
✔ Búsqueda avanzada y filtros  
✔ Administración de usuarios y empleados  

---

## Tecnologías utilizadas

### **Frontend**
- HTML  
- JavaScript  
- TailwindCSS  
- SASS  

### **Backend**
- PHP  
- PDO – consultas seguras  
- MySQL / MariaDB  

### **Herramientas**
- Node.js (para SASS y Tailwind)  
- Composer (para PHPMailer)  
- Servidor local (XAMPP / LAMP / MAMP)  

### **Correo**
- PHPMailer  
- SMTP (Outlook o SendGrid)

---

## Requisitos previos
Asegúrate de tener instalado:
- PHP 7.4 o superior  
- Composer  
- Node.js + npm  
- Servidor local compatible  
- Credenciales SMTP válidas  
- **Archivo database.sql** incluido en el repositorio para crear la base de datos  

---

## Instalación

### Clonar el repositorio  
Abrir terminal en una carpeta vacia y posteriormente  
git clone https://github.com/GenesisCampos100/puntoDeVenta.git  

# Instalar dependencias de PHP
composer install  

# Instalar dependencias de Node/SASS
npm install  

# Compilar estilos
npm run dev

## Configuración

### Configuración de correo (SendGrid)

Revisa y edita el archivo de configuración ubicado en:

Ejemplo de configuración:

```php
<?php
// Configuración para SendGrid
return [
    'host' => 'smtp.sendgrid.net',

    'username' => 'apikey',  // Siempre debe ser 'apikey' en SendGrid

    'password' => '#',  // Tu clave API de SendGrid

    'port' => 587,

    'secure' => 'tls',  // Puede ser 'tls' o 'ssl'

    'from_email' => 'prisma_pos@outlook.com',  // Correo remitente verificado en SendGrid

    'from_name' => 'Soporte Prisma',
];
```

# Uso del sistema
1- Inicia el servidor local (XAMPP/LAMP/MAMP).  
2- Accede a la aplicación en la URL configurada en tu servidor local.      
3- Inicia sesión o registra empleados.  
4- Crea productos desde el panel de administración.  
5- Realiza una venta y prueba el envío de comprobante por correo.   

# Estructura del proyecto  
<img width="776" height="1049" alt="image" src="https://github.com/user-attachments/assets/113bdc25-77c9-49c3-9461-76e4be296c7a" />  

# Capturas  
## Login
<img width="1916" height="913" alt="Captura de pantalla del login" src="https://github.com/user-attachments/assets/14ad7b00-5873-4ed6-8218-b29a0bf40bb3" />

## Inventario
<img width="1916" height="913" alt="Captura de pantalla del login" src="https://github.com/user-attachments/assets/8b5fdf1d-c186-4144-a861-62f2338a1b82" />

## Empleados
<img width="1916" height="913" alt="Captura de pantalla del login" src="https://github.com/user-attachments/assets/5e5303cf-f91c-4c33-b4fe-7fa585d34470" />
<img width="1916" height="913" alt="Captura de pantalla del login" src="https://github.com/user-attachments/assets/e1b8cd49-5297-4894-913e-762d9356f42e" /> 


# Equipo de desarrollo  
Proyecto realizado por el Equipo 3 — 3er Semestre Grupo E  
Facultad de Ingeniería Electromecánica — Universidad de Colima.  

- Ballato Román Mario Alberto  
- Campos Fajardo Génesis Joselyn  
- Jacobo Sánchez Kevin Ramón  
- Miranda Campos Zinedine Hiram  
- Sibaja Barragán Vanessa Yamile  

# Estado del proyecto  
Este proyecto continua en desarrollo.  

# Licencia  
Proyecto de uso académico.  
No se permite uso comercial sin autorización del equipo desarrollador.  

