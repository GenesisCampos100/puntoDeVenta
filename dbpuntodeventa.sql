-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 27-11-2025 a las 13:37:22
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `dbpuntodeventa`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `caja_movimientos`
--

CREATE TABLE `caja_movimientos` (
  `id_movimiento` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `metodo` enum('EFECTIVO','TARJETA') NOT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `id_empleado` varchar(20) DEFAULT NULL,
  `fecha_movimiento` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `caja_movimientos`
--

INSERT INTO `caja_movimientos` (`id_movimiento`, `monto`, `metodo`, `motivo`, `id_empleado`, `fecha_movimiento`) VALUES
(1, 200.00, 'EFECTIVO', 'cambio', 'A0001', '2025-11-27 04:28:36');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id_categoria` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id_categoria`, `nombre`) VALUES
(1, 'Camisetas'),
(2, 'Blusas'),
(3, 'Pantalones'),
(4, 'Jeans'),
(5, 'Shorts'),
(6, 'Faldas'),
(7, 'Vestidos'),
(8, 'Sudaderas'),
(9, 'Chamarras'),
(10, 'Abrigos'),
(11, 'Ropa deportiva'),
(12, 'Trajes de baño');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id_cliente` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `apellido_paterno` varchar(100) DEFAULT NULL,
  `apellido_materno` varchar(100) DEFAULT NULL,
  `celular` varchar(15) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `calle` varchar(100) DEFAULT NULL,
  `num_ext` varchar(10) DEFAULT NULL,
  `num_int` varchar(10) DEFAULT NULL,
  `colonia` varchar(100) DEFAULT NULL,
  `cp` varchar(10) DEFAULT NULL,
  `estado` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id_cliente`, `nombre`, `apellido_paterno`, `apellido_materno`, `celular`, `correo`, `calle`, `num_ext`, `num_int`, `colonia`, `cp`, `estado`) VALUES
(11, 'Nadya', 'Campos', '', '3124567890', 'genesis@gmail.com', 'JOEL MONTES CAMARENA', '660', '', 'CENTRO MANZANILLO', '28279', 'COLIMA'),
(12, 'Sofía', 'Hernández', 'Díaz', '5511223344', 'sofia.hdez@mail.com', 'Avenida de la Moda', '105', 'A', 'Centro', '06000', 'Ciudad de México'),
(13, 'Ricardo', 'Pérez', 'López', '3344556677', 'ricardo.p@corpmail.com', NULL, NULL, NULL, NULL, NULL, NULL),
(14, 'Ana María', 'Gómez', NULL, '8177889900', 'anamaria_g@gmail.com', 'Calle del Sol', '24', NULL, 'El Pedregal', '64000', 'Nuevo León'),
(15, 'Carlos', 'Mendoza', NULL, '2299887766', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(16, 'Valeria', 'Cruz', 'Santos', '4410203040', 'valeria.cruz@hotmail.com', 'Bulevar Principal', '2005', 'B-1', 'Los Álamos', '45020', 'Jalisco');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cortes_caja`
--

CREATE TABLE `cortes_caja` (
  `id_corte` int(11) NOT NULL,
  `id_empleado` varchar(20) DEFAULT NULL,
  `efectivo_esperado` decimal(10,2) NOT NULL,
  `tarjeta_esperado` decimal(10,2) NOT NULL,
  `efectivo_contado` decimal(10,2) NOT NULL,
  `tarjeta_contado` decimal(10,2) NOT NULL,
  `diferencia` decimal(10,2) NOT NULL,
  `comentarios` text DEFAULT NULL,
  `fecha_corte` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_ventas`
--

CREATE TABLE `detalle_ventas` (
  `id_detalle` int(11) NOT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `precio_unitario` decimal(10,2) DEFAULT NULL,
  `descuento` decimal(10,2) DEFAULT 0.00,
  `id_venta` int(11) DEFAULT NULL,
  `cod_barras` bigint(20) DEFAULT NULL,
  `id_variante` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleados`
--

CREATE TABLE `empleados` (
  `id_empleado` varchar(20) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `apellido_paterno` varchar(100) DEFAULT NULL,
  `apellido_materno` varchar(100) DEFAULT NULL,
  `celular` varchar(15) DEFAULT NULL,
  `calle` varchar(100) DEFAULT NULL,
  `num_ext` varchar(10) DEFAULT NULL,
  `num_int` varchar(10) DEFAULT NULL,
  `colonia` varchar(100) DEFAULT NULL,
  `cp` varchar(10) DEFAULT NULL,
  `estado` varchar(100) DEFAULT NULL,
  `estatus` tinyint(1) DEFAULT 1,
  `fecha` datetime DEFAULT current_timestamp(),
  `id_rol` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `empleados`
--

INSERT INTO `empleados` (`id_empleado`, `nombre`, `apellido_paterno`, `apellido_materno`, `celular`, `calle`, `num_ext`, `num_int`, `colonia`, `cp`, `estado`, `estatus`, `fecha`, `id_rol`) VALUES
('A0001', 'Luz', 'Campos', 'Fajardo', '3141001010', 'JOEL MONTES CAMARENA', '660', '', 'CENTRO MANZANILLO', '28279', 'COLIMA', 1, '2025-11-26 22:36:21', 1),
('C0001', 'Vanessa Yamile', 'SIbaja', 'Barragan', '3131342958', 'JOEL MONTES CAMARENA', '660', '', 'CENTRO MANZANILLO', '28279', 'COLIMA', 1, '2025-11-26 13:42:22', 3),
('G0001', 'Genesis Jocelyn', 'Campos', 'Fajardo', '3131342958', 'JOEL MONTES CAMARENA', '660', '', 'CENTRO MANZANILLO', '28279', 'COLIMA', 1, '2025-11-26 13:43:04', 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario_movimientos`
--

CREATE TABLE `inventario_movimientos` (
  `id_movimiento` int(11) NOT NULL,
  `cod_barras` bigint(20) DEFAULT NULL,
  `id_variante` bigint(20) DEFAULT NULL,
  `tipo_movimiento` enum('ENTRADA','SALIDA') NOT NULL,
  `cantidad_impactada` int(11) NOT NULL,
  `motivo` varchar(255) NOT NULL,
  `referencia` varchar(100) DEFAULT NULL,
  `fecha_movimiento` timestamp NOT NULL DEFAULT current_timestamp(),
  `id_usuario` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos_venta`
--

CREATE TABLE `pagos_venta` (
  `id_pago` int(11) NOT NULL,
  `metodo` enum('EFECTIVO','TARJETA') NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `referencia` varchar(100) DEFAULT NULL,
  `fecha_pago` datetime DEFAULT current_timestamp(),
  `id_venta` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `cod_barras` bigint(20) NOT NULL,
  `nom_producto` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `talla` varchar(20) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `sku` varchar(10) DEFAULT NULL,
  `cantidad` int(11) DEFAULT 0,
  `cantidad_min` int(11) NOT NULL DEFAULT 0,
  `costo` decimal(10,2) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `id_categoria` int(11) DEFAULT NULL,
  `id_proveedor` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`cod_barras`, `nom_producto`, `descripcion`, `marca`, `imagen`, `talla`, `color`, `sku`, `cantidad`, `cantidad_min`, `costo`, `precio`, `id_categoria`, `id_proveedor`, `is_active`) VALUES
(23456787562, 'Blusa', '', '', NULL, NULL, NULL, NULL, 0, 0, 0.00, 0.00, 10, 2, 0),
(7501002000018, 'Chamarra', 'Chamarra de mezclilla de corte tradicional unisex.', 'DenimCo', 'img_69282a1eb6c70_Chamarra puffer Armani Exchange de corte regular….jpeg', 'M', 'Azul', 'DC101', 20, 3, 450.00, 899.00, 9, NULL, 1),
(7501002000019, 'Shorts Running Dry-Fit', 'Shorts ligeros para correr con tecnología de secado rápido.', 'SportMax', 'img_69282ac182d78_Nike Shorts_  Women\'s, all black, Nike running….jpeg', 'L', 'Negro', 'SM005', 30, 10, 120.00, 269.00, 11, NULL, 1),
(7501111111111, 'Blusa de seda', 'Blusa elegante de seda para ocasiones especiales', 'Zara', 'img_692822884feb6_Blusa corta de cuello halter, escote fluido….jpeg', 'M', 'Rojo', 'BLU001', 30, 5, 350.00, 599.00, 2, NULL, 1),
(7501234567890, 'Camiseta básica blanca', 'Camiseta de algodón 100% cómoda y ligera', 'H&M', 'img_692829f90604b_ai generado blanco blanco camiseta Bosquejo….jpeg', 'M', 'Blanco', 'CAM001', 70, 5, 120.00, 199.00, 1, NULL, 1),
(7502222222222, 'Jeans ajustados', 'Jeans de mezclilla azul corte skinny', 'Levi\'s', 'img_69282a3508787_Celeste  Collar  Mezclilla Liso ajustado….jpeg', '32', 'Azul', 'JEA001', 40, 5, 450.00, 799.00, 4, NULL, 1),
(7503333333333, 'Vestido floral', 'Vestido ligero con estampado floral ideal para verano', 'Forever 21', 'img_69282ae82110b_Rebecca Taylor Francine Floral-Print Cotton Dress.jpeg', 'S', 'Multicolor', 'VES001', 25, 3, 300.00, 549.00, 7, NULL, 1),
(7504444444444, 'Camiseta deportiva', 'Camiseta transpirable ideal para entrenamiento', 'Nike', 'img_69282a11b3cae_Camiseta ajustada de manga larga informal para….jpeg', NULL, '', 'CAMDEP001', 0, 0, 200.00, 349.00, 11, NULL, 1),
(7505555555555, 'Sudadera con capucha', 'Sudadera cómoda con capucha y bolsillo frontal', 'Adidas', 'img_69282acddd594_Sudadera con capucha para hombres de bolsillo….jpeg', NULL, '', 'SUDCAP001', 0, 0, 400.00, 699.00, 8, NULL, 1),
(7509876543210, 'Sudadera deportiva', 'Sudadera ligera ideal para entrenamiento', 'Nike', 'img_69282adb72c23_SHEIN MOD Capucha con costura bajo curvo con… (1).jpeg', NULL, '', 'SUD001', 0, 0, 450.00, 699.00, 11, NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `id_proveedor` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido_paterno` varchar(100) NOT NULL,
  `apellido_materno` varchar(100) DEFAULT NULL,
  `empresa` varchar(100) NOT NULL,
  `celular` varchar(15) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `estatus` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `proveedores`
--

INSERT INTO `proveedores` (`id_proveedor`, `nombre`, `apellido_paterno`, `apellido_materno`, `empresa`, `celular`, `correo`, `estatus`) VALUES
(2, 'Vanessa Yamile', 'García', 'Fajardo', 'Nike', '3141001010', 'ballatomario1105@gmail.com', 1),
(3, 'Javier', 'Sánchez', 'Pérez', 'Denim Style Fabricantes', '5523456789', 'ventas@denimstyle.com', 1),
(4, 'Andrea', 'Molina', NULL, 'Moda Elegante Mayorista', '3398765432', 'andrea.m@modaelegante.com', 1),
(5, 'Chang', 'Wong', NULL, 'Silk & Co Imports', '8145678901', 'chang.w@silkco.cn', 1),
(6, 'Roberto', 'Vargas', 'Reyes', 'SportFlex Textiles', '2256789012', 'roberto.vargas@sportflex.mx', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL,
  `nombre_rol` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id_rol`, `nombre_rol`) VALUES
(1, 'super_admin'),
(2, 'gerente'),
(3, 'cajero');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_verifications`
--

CREATE TABLE `user_verifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `id_empleado` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `correo`, `contrasena`, `imagen`, `id_empleado`) VALUES
(33, 'admin@prisma.com', '$2y$10$w25qpLG7URvOMOWErnYZc.2dGzPpkdEQ8ebXs2bomddqBXdDaLn6i', NULL, 'A0001'),
(34, 'cajero@prisma.com', '$2y$10$kr/e.eRwDwe7lGD85.PVi.fy.im8QxlTUTgEUhS/lZyrhSmv7sEuS', NULL, 'C0001'),
(35, 'gerente@prisma.com', '$2y$10$.k99EZjeiO9UJUsR742Lh.pxBGX2xtuiwzzquN840rtzYGfGUFOt6', NULL, 'G0001');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `variantes`
--

CREATE TABLE `variantes` (
  `id_variante` bigint(20) NOT NULL,
  `color` varchar(50) DEFAULT NULL,
  `talla` varchar(20) DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `sku` varchar(10) DEFAULT NULL,
  `cantidad` int(11) DEFAULT 0,
  `cantidad_min` int(11) NOT NULL DEFAULT 0,
  `costo` decimal(10,2) DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `cod_barras` bigint(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `variantes`
--

INSERT INTO `variantes` (`id_variante`, `color`, `talla`, `imagen`, `sku`, `cantidad`, `cantidad_min`, `costo`, `precio`, `cod_barras`, `is_active`) VALUES
(3, 'Negro', 'M', 'https://images.unsplash.com/photo-1602810318383-9c3d3a2a3f7f', 'SUD001-M', 20, 3, 450.00, 699.00, 7509876543210, 1),
(4, 'Gris', 'L', 'https://images.unsplash.com/photo-1602810318383-9c3d3a2a3f7f', 'SUD001-L', 15, 3, 450.00, 699.00, 7509876543210, 1),
(5, 'Azul', 'S', 'https://images.unsplash.com/photo-1602810318383-9c3d3a2a3f7f', 'SUD001-S', 10, 2, 450.00, 699.00, 7509876543210, 1),
(6, 'Negro', 'M', 'var_6928352b28acf_Sudadera con capucha para hombres de bolsillo….jpeg', 'CAMDEP001-', 20, 3, 200.00, 349.00, 7504444444444, 1),
(7, 'Blanco', 'L', 'https://images.unsplash.com/photo-1602810318383-9c3d3a2a3f7f', 'CAMDEP001-', 15, 3, 200.00, 349.00, 7504444444444, 1),
(8, 'Morado', 'S', 'https://images.unsplash.com/photo-1602810318383-9c3d3a2a3f7f', 'CAMDEP001-', 10, 2, 200.00, 349.00, 7504444444444, 1),
(9, 'Gris', 'M', 'https://images.unsplash.com/photo-1602810318383-9c3d3a2a3f7f', 'SUDCAP001-', 18, 3, 400.00, 699.00, 7505555555555, 1),
(10, 'Negro', 'L', 'https://images.unsplash.com/photo-1602810318383-9c3d3a2a3f7f', 'SUDCAP001-', 12, 2, 400.00, 699.00, 7505555555555, 1),
(11, 'Azul', 'S', 'https://images.unsplash.com/photo-1602810318383-9c3d3a2a3f7f', 'SUDCAP001-', 10, 2, 400.00, 699.00, 7505555555555, 1),
(12, NULL, 'G', NULL, 'BLUSA-G', 100, 5, 100.00, 200.00, 23456787562, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

CREATE TABLE `ventas` (
  `id_venta` int(11) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `subtotal` decimal(10,2) NOT NULL,
  `descuento_general` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `id_cliente` int(11) DEFAULT NULL,
  `id_empleado` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `caja_movimientos`
--
ALTER TABLE `caja_movimientos`
  ADD PRIMARY KEY (`id_movimiento`),
  ADD KEY `id_empleado` (`id_empleado`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id_categoria`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id_cliente`);

--
-- Indices de la tabla `cortes_caja`
--
ALTER TABLE `cortes_caja`
  ADD PRIMARY KEY (`id_corte`),
  ADD KEY `id_empleado` (`id_empleado`);

--
-- Indices de la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `id_venta` (`id_venta`),
  ADD KEY `cod_barras` (`cod_barras`),
  ADD KEY `id_variante` (`id_variante`);

--
-- Indices de la tabla `empleados`
--
ALTER TABLE `empleados`
  ADD PRIMARY KEY (`id_empleado`),
  ADD KEY `id_rol` (`id_rol`);

--
-- Indices de la tabla `inventario_movimientos`
--
ALTER TABLE `inventario_movimientos`
  ADD PRIMARY KEY (`id_movimiento`),
  ADD KEY `cod_barras` (`cod_barras`),
  ADD KEY `id_variante` (`id_variante`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `pagos_venta`
--
ALTER TABLE `pagos_venta`
  ADD PRIMARY KEY (`id_pago`),
  ADD KEY `id_venta` (`id_venta`);

--
-- Indices de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`cod_barras`),
  ADD KEY `id_categoria` (`id_categoria`),
  ADD KEY `id_proveedor` (`id_proveedor`);

--
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`id_proveedor`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_rol`);

--
-- Indices de la tabla `user_verifications`
--
ALTER TABLE `user_verifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD KEY `id_empleado` (`id_empleado`);

--
-- Indices de la tabla `variantes`
--
ALTER TABLE `variantes`
  ADD PRIMARY KEY (`id_variante`),
  ADD KEY `cod_barras` (`cod_barras`);

--
-- Indices de la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`id_venta`),
  ADD KEY `id_cliente` (`id_cliente`),
  ADD KEY `id_empleado` (`id_empleado`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `caja_movimientos`
--
ALTER TABLE `caja_movimientos`
  MODIFY `id_movimiento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `cortes_caja`
--
ALTER TABLE `cortes_caja`
  MODIFY `id_corte` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT de la tabla `inventario_movimientos`
--
ALTER TABLE `inventario_movimientos`
  MODIFY `id_movimiento` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pagos_venta`
--
ALTER TABLE `pagos_venta`
  MODIFY `id_pago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id_proveedor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `user_verifications`
--
ALTER TABLE `user_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT de la tabla `variantes`
--
ALTER TABLE `variantes`
  MODIFY `id_variante` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id_venta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `caja_movimientos`
--
ALTER TABLE `caja_movimientos`
  ADD CONSTRAINT `caja_movimientos_ibfk_1` FOREIGN KEY (`id_empleado`) REFERENCES `empleados` (`id_empleado`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `cortes_caja`
--
ALTER TABLE `cortes_caja`
  ADD CONSTRAINT `cortes_caja_ibfk_1` FOREIGN KEY (`id_empleado`) REFERENCES `empleados` (`id_empleado`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  ADD CONSTRAINT `detalle_ventas_ibfk_1` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id_venta`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `detalle_ventas_ibfk_2` FOREIGN KEY (`cod_barras`) REFERENCES `productos` (`cod_barras`) ON UPDATE CASCADE,
  ADD CONSTRAINT `detalle_ventas_ibfk_3` FOREIGN KEY (`id_variante`) REFERENCES `variantes` (`id_variante`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `empleados`
--
ALTER TABLE `empleados`
  ADD CONSTRAINT `empleados_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `inventario_movimientos`
--
ALTER TABLE `inventario_movimientos`
  ADD CONSTRAINT `inventario_movimientos_ibfk_1` FOREIGN KEY (`cod_barras`) REFERENCES `productos` (`cod_barras`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `inventario_movimientos_ibfk_2` FOREIGN KEY (`id_variante`) REFERENCES `variantes` (`id_variante`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `inventario_movimientos_ibfk_3` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `pagos_venta`
--
ALTER TABLE `pagos_venta`
  ADD CONSTRAINT `pagos_venta_ibfk_1` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id_venta`) ON DELETE CASCADE;

--
-- Filtros para la tabla `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `productos_ibfk_2` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `user_verifications`
--
ALTER TABLE `user_verifications`
  ADD CONSTRAINT `user_verifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`id_empleado`) REFERENCES `empleados` (`id_empleado`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `variantes`
--
ALTER TABLE `variantes`
  ADD CONSTRAINT `variantes_ibfk_1` FOREIGN KEY (`cod_barras`) REFERENCES `productos` (`cod_barras`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD CONSTRAINT `ventas_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `ventas_ibfk_2` FOREIGN KEY (`id_empleado`) REFERENCES `empleados` (`id_empleado`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
