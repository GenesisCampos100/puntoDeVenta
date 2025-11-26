-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 17-11-2025 a las 05:15:04
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
-- Base de datos: `puntodeventa`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `apartados`
--

CREATE TABLE `apartados` (
  `id_apartado` int(11) NOT NULL,
  `fecha_inicio` datetime DEFAULT current_timestamp(),
  `fecha_fin` datetime DEFAULT NULL,
  `id_cliente` int(11) DEFAULT NULL,
  `id_empleado` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `estado` varchar(100) DEFAULT NULL,
  `rfc` varchar(13) DEFAULT NULL,
  `curp` varchar(18) DEFAULT NULL,
  `razon_social` varchar(255) DEFAULT NULL,
  `regimen_fiscal` varchar(100) DEFAULT NULL,
  `uso_cfdi` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_apartados`
--

CREATE TABLE `detalle_apartados` (
  `id_detalle` int(11) NOT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `precio_unitario` decimal(10,2) DEFAULT NULL,
  `id_apartado` int(11) DEFAULT NULL,
  `cod_barras` bigint(20) DEFAULT NULL,
  `id_variante` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_ventas`
--

CREATE TABLE `detalle_ventas` (
  `id_detalle` int(11) NOT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `precio_unitario` decimal(10,2) DEFAULT NULL,
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
('EMP001', 'Lucía', 'García', 'Mendoza', '5551112233', 'Av. Central', '101', NULL, 'Centro', '06000', 'CDMX', 1, '2025-10-24 08:22:49', 1),
('EMP002', 'Carlos', 'Ramírez', 'López', '5552223344', 'Calle Norte', '202', NULL, 'Industrial', '07000', 'CDMX', 1, '2025-10-24 08:22:49', 2),
('EMP003', 'Ana', 'Hernández', 'Flores', '5553334455', 'Av. Sur', '303', NULL, 'Reforma', '08000', 'CDMX', 1, '2025-10-24 08:22:49', 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `facturas`
--

CREATE TABLE `facturas` (
  `id_factura` int(11) NOT NULL,
  `razon_social` varchar(150) DEFAULT NULL,
  `rfc` varchar(13) DEFAULT NULL,
  `curp` varchar(18) DEFAULT NULL,
  `domicilio` text DEFAULT NULL,
  `id_venta` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario_movimientos`
--

CREATE TABLE `inventario_movimientos` (
  `id_movimiento` int(11) NOT NULL,
  `cod_barras` varchar(100) NOT NULL,
  `tipo_movimiento` enum('ENTRADA','SALIDA') NOT NULL,
  `cantidad_impactada` int(11) NOT NULL,
  `motivo` varchar(50) NOT NULL,
  `fecha_movimiento` datetime NOT NULL DEFAULT current_timestamp(),
  `referencia` varchar(100) DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `inventario_movimientos`
--

INSERT INTO `inventario_movimientos` (`id_movimiento`, `cod_barras`, `tipo_movimiento`, `cantidad_impactada`, `motivo`, `fecha_movimiento`, `referencia`, `id_usuario`) VALUES
(1, '21637128', 'ENTRADA', 20, 'RECEPCION_PEDIDO', '2025-11-06 20:31:44', '', NULL),
(2, '23456', 'ENTRADA', 20, 'RECEPCION_PEDIDO', '2025-11-06 20:34:26', '', NULL),
(3, '21637128', 'ENTRADA', 20, 'RECEPCION_PEDIDO', '2025-11-06 20:38:37', '', NULL),
(4, '21637128', 'ENTRADA', 20, 'RECEPCION_PEDIDO', '2025-11-06 20:39:00', '', NULL),
(5, '21637128', 'ENTRADA', 20, 'Ajuste manual', '2025-11-11 05:35:19', NULL, 1),
(6, '21637128', 'ENTRADA', 20, 'Ajuste manual', '2025-11-11 05:35:28', NULL, 1),
(7, '21637128', 'SALIDA', 100, 'Ajuste manual', '2025-11-11 05:40:13', NULL, 1),
(8, '98737', 'ENTRADA', 100, 'Ajuste manual', '2025-11-11 09:05:36', NULL, 1),
(9, '23456', 'ENTRADA', 20, 'Ajuste manual', '2025-11-11 09:24:18', NULL, 1),
(10, '23456', 'ENTRADA', 20, 'Ajuste manual', '2025-11-11 09:24:24', NULL, 1),
(11, '9876', 'ENTRADA', 20, 'Ajuste manual', '2025-11-11 09:24:36', NULL, 1),
(12, '23456', 'SALIDA', 20, 'Ajuste manual', '2025-11-11 09:24:42', NULL, 1),
(13, '1234', 'ENTRADA', 20, 'Ajuste manual', '2025-11-11 09:24:59', NULL, 1),
(14, '21637128', 'ENTRADA', 20, 'Ajuste manual', '2025-11-11 10:53:14', NULL, 1),
(15, '23456', 'ENTRADA', 20, 'Ajuste manual', '2025-11-11 10:53:20', NULL, 1),
(16, '23456', 'ENTRADA', 20, 'Ajuste manual', '2025-11-11 10:53:26', NULL, 1),
(17, '1234', 'ENTRADA', 20, 'Ajuste manual', '2025-11-11 10:53:30', NULL, 1),
(18, '23456', 'ENTRADA', 20, 'Ajuste manual', '2025-11-11 21:28:49', NULL, 1),
(19, 'undefined', 'ENTRADA', 20, 'Ajuste manual', '2025-11-13 08:11:57', NULL, 1),
(20, 'undefined', 'ENTRADA', 100, 'Ajuste manual', '2025-11-13 08:12:05', NULL, 1),
(21, 'undefined', 'ENTRADA', 20, 'Ajuste manual', '2025-11-13 08:15:07', NULL, 1),
(22, 'undefined', 'ENTRADA', 20, 'Ajuste manual', '2025-11-13 08:30:20', NULL, 1),
(23, 'undefined', 'ENTRADA', 20, 'Ajuste manual', '2025-11-13 08:33:34', NULL, 1),
(24, 'undefined', 'ENTRADA', 20, 'Ajuste manual', '2025-11-13 08:48:11', NULL, 1),
(25, 'undefined', 'ENTRADA', 20, 'Ajuste manual', '2025-11-13 08:55:46', NULL, 1),
(26, 'undefined', 'ENTRADA', 20, 'Ajuste manual', '2025-11-13 09:00:18', NULL, 1),
(27, 'undefined', 'ENTRADA', 20, 'Ajuste manual', '2025-11-13 09:01:36', NULL, 1),
(28, 'undefined', 'ENTRADA', 30, 'Ajuste manual', '2025-11-13 11:23:19', NULL, 1),
(29, 'undefined', 'ENTRADA', 20, 'Ajuste manual', '2025-11-13 18:31:27', NULL, 1),
(30, 'undefined', 'ENTRADA', 20, 'Ajuste manual', '2025-11-14 08:06:18', NULL, 1),
(31, 'undefined', 'ENTRADA', 20, 'Ajuste manual', '2025-11-14 08:26:55', NULL, 1),
(32, 'undefined', 'ENTRADA', 20, 'Ajuste manual', '2025-11-14 08:27:28', NULL, 1),
(33, 'undefined', 'ENTRADA', 20, 'Ajuste manual', '2025-11-14 08:30:46', NULL, 1),
(34, '21637128', 'ENTRADA', 20, 'Ajuste manual', '2025-11-14 08:53:27', NULL, 1),
(35, '21637128', 'ENTRADA', 20, 'Ajuste manual', '2025-11-14 09:05:09', NULL, NULL),
(36, '21637128', 'ENTRADA', 2, 'Ajuste manual', '2025-11-14 09:05:13', NULL, NULL),
(37, '21637128', 'ENTRADA', 20, 'Ajuste manual', '2025-11-14 09:05:18', NULL, NULL),
(38, '8888888', 'ENTRADA', 20, 'Ajuste manual', '2025-11-14 09:09:26', NULL, NULL),
(39, '8888888', 'ENTRADA', 20, 'Ajuste manual', '2025-11-14 09:11:45', NULL, NULL),
(40, '3456787', 'ENTRADA', 100, 'Ajuste manual', '2025-11-14 09:11:52', NULL, NULL),
(41, '3456787', 'ENTRADA', 40, 'Ajuste manual', '2025-11-14 09:14:18', NULL, NULL),
(42, '21637128', 'ENTRADA', 20, 'Ajuste manual', '2025-11-14 09:21:07', NULL, NULL),
(43, '23456', 'ENTRADA', 20, 'Ajuste manual', '2025-11-14 09:23:18', NULL, NULL),
(44, '21637128', 'ENTRADA', 20, 'Ajuste manual', '2025-11-14 09:38:49', NULL, NULL),
(45, '12345', 'ENTRADA', 20, 'Ajuste manual', '2025-11-14 09:46:13', NULL, 1),
(46, '21637128', 'ENTRADA', 20, 'Ajuste manual', '2025-11-14 09:51:56', NULL, 1),
(47, '21637128', 'ENTRADA', 30, 'Ajuste manual', '2025-11-14 14:15:26', NULL, 1),
(48, '9876', 'ENTRADA', 20, 'Ajuste manual', '2025-11-15 02:52:35', NULL, 1),
(49, '9876', 'ENTRADA', 20, 'Ajuste manual', '2025-11-15 03:06:49', NULL, 1),
(50, '9876', 'ENTRADA', 20, 'Ajuste manual', '2025-11-15 03:08:14', NULL, 1),
(51, '9876', 'ENTRADA', 20, 'Ajuste manual', '2025-11-15 03:12:30', NULL, 1),
(52, '9876', 'ENTRADA', 20, 'Ajuste manual', '2025-11-15 03:16:56', NULL, 1),
(53, '738672', 'ENTRADA', 20, 'Ajuste manual', '2025-11-15 03:17:26', NULL, 1),
(54, '3456787', 'ENTRADA', 20, 'Ajuste manual', '2025-11-15 03:18:09', NULL, 1),
(55, '8888888', 'ENTRADA', 10, 'Ajuste manual', '2025-11-15 08:06:05', NULL, 1),
(56, '77867', 'ENTRADA', 20, 'Ajuste manual', '2025-11-15 15:34:17', NULL, 1),
(57, '21637128', 'ENTRADA', 10, 'a', '2025-11-15 16:29:18', NULL, 1);

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
(156, 'Vestido', '', '', NULL, '', '', NULL, 0, 0, 100.00, 200.00, 2, NULL, 1),
(1234, 'Vestido', '', '', 'prod_690aac434574a.jpeg', 'M', 'Negro', NULL, 100, 50, 100.00, 200.00, 7, NULL, 1),
(9876, 'Pantalon', 'Excelente', 'Marca A', 'prod_6909557fdb8ba.jpeg', NULL, NULL, 'P001', 120, 0, 0.00, 0.00, 4, NULL, 1),
(12345, 'Pantalon', 'ola', 'Shein', NULL, 'M', 'Negro', NULL, 120, 50, 199.00, 300.00, 5, NULL, 1),
(23456, 'Corset', '', 'Marca A', NULL, NULL, NULL, NULL, 20, 0, 0.00, 0.00, 6, NULL, 1),
(73281, 'Vestido', '', '', NULL, '', '', NULL, 10, 5, 100.00, 200.00, 7, NULL, 1),
(77867, 'papas', '', '', NULL, 'M', 'Azul', NULL, 30, 5, 100.00, 300.00, 5, NULL, 1),
(98737, 'Vestido', '', '', 'prod_69095f0f77f75.jpeg', NULL, NULL, 'V001', 100, 0, 0.00, 0.00, 7, NULL, 1),
(345678, 'Vestido', '', '', NULL, '', '', NULL, 0, 0, 100.00, 200.00, 8, NULL, 1),
(738672, 'Pantalon', '', '', NULL, '', '', NULL, 30, 2, 100.00, 200.00, 9, NULL, 1),
(3456787, 'Pantalon', '', '', NULL, 'M', 'Rojo', NULL, 160, 0, 10.00, 20.00, 11, NULL, 1),
(6367287, 'Pantalon - Vaquero', '', '', NULL, '', '', NULL, 10, 5, 100.00, 200.00, 4, NULL, 1),
(8888888, 'Falda Plisada', '', '', NULL, 'M', 'Verde', NULL, 60, 0, 100.00, 200.00, 6, NULL, 1),
(21637128, 'Corset', '', '', NULL, '', '', NULL, 202, 10, 100.00, 200.00, 8, NULL, 1),
(98737827, 'Short de verano', '', '', NULL, 'M', 'Negro', NULL, 0, 0, 100.00, 200.00, 5, NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `id_proveedor` int(11) NOT NULL,
  `empresa` varchar(100) NOT NULL,
  `representante` varchar(100) DEFAULT NULL,
  `celular` varchar(15) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(10, 'admin@prisma.com', '$2b$12$BI9dwcSxsGrGUa0T4r8eA.b1kDOPX5D/buJQyW6B2XCT7QqqUE80K', '../public/uploads/fotos_perfil/user_10_1762365081.jpg', 'EMP001'),
(11, 'gerente@prisma.com', '$2b$12$SUNJqwZSd5yJ54Cg0CRfheCrSlKitmWBArKsklyuGw49A4xgDrhim', NULL, 'EMP002'),
(12, 'cajero@prisma.com', '$2b$12$5h7GjFWniEkrWHBv9olNte1KqcmqqmIvdLoxlvr1.zFt5Se/8.JP6', NULL, 'EMP003'),
(13, 'zinehiramc@outlook.com\r\n', '1234567', NULL, 'EMP003');

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
(0, NULL, 'M', NULL, '1234', 160, 50, 100.00, 200.00, 23456, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

CREATE TABLE `ventas` (
  `id_venta` int(11) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `tipo_pago` enum('EFECTIVO','TARJETA') DEFAULT NULL,
  `pago_total` decimal(10,2) DEFAULT NULL,
  `id_cliente` int(11) DEFAULT NULL,
  `id_empleado` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `apartados`
--
ALTER TABLE `apartados`
  ADD PRIMARY KEY (`id_apartado`),
  ADD KEY `id_cliente` (`id_cliente`),
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
-- Indices de la tabla `detalle_apartados`
--
ALTER TABLE `detalle_apartados`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `id_apartado` (`id_apartado`),
  ADD KEY `cod_barras` (`cod_barras`),
  ADD KEY `id_variante` (`id_variante`);

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
-- Indices de la tabla `facturas`
--
ALTER TABLE `facturas`
  ADD PRIMARY KEY (`id_factura`),
  ADD KEY `id_venta` (`id_venta`);

--
-- Indices de la tabla `inventario_movimientos`
--
ALTER TABLE `inventario_movimientos`
  ADD PRIMARY KEY (`id_movimiento`),
  ADD KEY `idx_cod_barras` (`cod_barras`);

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
-- AUTO_INCREMENT de la tabla `apartados`
--
ALTER TABLE `apartados`
  MODIFY `id_apartado` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalle_apartados`
--
ALTER TABLE `detalle_apartados`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `facturas`
--
ALTER TABLE `facturas`
  MODIFY `id_factura` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inventario_movimientos`
--
ALTER TABLE `inventario_movimientos`
  MODIFY `id_movimiento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id_proveedor` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `user_verifications`
--
ALTER TABLE `user_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id_venta` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `apartados`
--
ALTER TABLE `apartados`
  ADD CONSTRAINT `apartados_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `apartados_ibfk_2` FOREIGN KEY (`id_empleado`) REFERENCES `empleados` (`id_empleado`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `detalle_apartados`
--
ALTER TABLE `detalle_apartados`
  ADD CONSTRAINT `detalle_apartados_ibfk_1` FOREIGN KEY (`id_apartado`) REFERENCES `apartados` (`id_apartado`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `detalle_apartados_ibfk_2` FOREIGN KEY (`cod_barras`) REFERENCES `productos` (`cod_barras`) ON UPDATE CASCADE,
  ADD CONSTRAINT `detalle_apartados_ibfk_3` FOREIGN KEY (`id_variante`) REFERENCES `variantes` (`id_variante`) ON UPDATE CASCADE;

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
-- Filtros para la tabla `facturas`
--
ALTER TABLE `facturas`
  ADD CONSTRAINT `facturas_ibfk_1` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id_venta`) ON DELETE CASCADE ON UPDATE CASCADE;

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
