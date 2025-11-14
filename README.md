# Prisma Punto de Venta

Prisma es un sistema de punto de venta para tiendas de ropa desarrollado como proyecto académico de tercer semestre. Incluye gestión de productos, ventas, caja, y envío de correos para comprobante de registro de empleados y recuperación de contraseña mediante PHPMailer.

# Capturas
<img width="1916" height="913" alt="Captura de pantalla del login" src="https://github.com/user-attachments/assets/14ad7b00-5873-4ed6-8218-b29a0bf40bb3" />  
![Inventario](https://github.com/user-attachments/assets/8b5fdf1d-c186-4144-a861-62f2338a1b82)  
![CRUD empleados](https://github.com/user-attachments/assets/5e5303cf-f91c-4c33-b4fe-7fa585d34470)  
![Registro 2](https://github.com/user-attachments/assets/e1b8cd49-5297-4894-913e-762d9356f42e)  

# Características principales
Registrar ventas y generar recibo  
Gestión simple de inventario y productos
Búsqueda y filtro de productos
Envío de comprobantes por correo usando PHPMailer
Interfaz responsiva con Tailwind y estilos en SASS

# Tecnologías
Backend: PHP, SQL
Frontend: HTML, JavaScript, Tailwind CSS, SASS
Herramientas: Node.js para procesar SASS y Tailwind; Composer para dependencias PHP
Correo: PHPMailer

# Requisitos previos
PHP 7.4 o superior  
Composer instalado  
Node.js y npm o yarn  
Servidor local (XAMPP, MAMP, LAMP o equivalente)  
Acceso a un servidor SMTP para envío de correos (En nuestro caso es Outlook)  
Registrar en SendGrid para generar una clave de api en "Claves API", ademas de verificar un correo (puede ser personal) como remitente en la sección de "Identidad del remitente". Ambas acciones se hacen en ajustes.  

# Instalación
Abrir terminal en una carpeta vacia y posteriormente  
git clone https://github.com/GenesisCampos100/puntoDeVenta.git  

composer install  

npm install  

# Usar Sass
npm run dev

# Configurar PHP Mailer
Revisa el archivo de configuración de correo en config/mail.php  
Rellena los campos SMTP: host, puerto, usuario, contraseña(Sera la clave api) y remitente(Debe decir API KEY).   
Verifica que tu proveedor SMTP permita conexiones desde el entorno de desarrollo o usa credenciales especiales para desarrollo.  

# Uso
Accede a la aplicación en la URL configurada en tu servidor local.  
Crea productos desde el panel de administración.  
Realiza una venta y prueba el envío de comprobante por correo.  

# Estructura
<img width="776" height="1049" alt="image" src="https://github.com/user-attachments/assets/113bdc25-77c9-49c3-9461-76e4be296c7a" />

