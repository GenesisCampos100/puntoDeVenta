-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 27-11-2025 a las 09:19:10
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
(11, 'Nadya', 'Campos', '', '3124567890', 'genesis@gmail.com', 'JOEL MONTES CAMARENA', '660', '', 'CENTRO MANZANILLO', '28279', 'COLIMA');

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

--
-- Volcado de datos para la tabla `cortes_caja`
--

INSERT INTO `cortes_caja` (`id_corte`, `id_empleado`, `efectivo_esperado`, `tarjeta_esperado`, `efectivo_contado`, `tarjeta_contado`, `diferencia`, `comentarios`, `fecha_corte`) VALUES
(1, 'A0001', 2207.00, 0.00, 2207.00, 0.00, 0.00, '', '2025-11-24 06:19:10'),
(2, 'A0004', 6153.00, 0.00, 6153.00, 0.00, 0.00, 'Genesis se la come', '2025-11-26 22:21:20');

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
  `descuento` decimal(10,2) DEFAULT 0.00,
  `id_venta` int(11) DEFAULT NULL,
  `cod_barras` bigint(20) DEFAULT NULL,
  `id_variante` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_ventas`
--

INSERT INTO `detalle_ventas` (`id_detalle`, `cantidad`, `precio_unitario`, `descuento`, `id_venta`, `cod_barras`, `id_variante`) VALUES
(17, 1, 299.00, 0.00, 17, 7501002000020, NULL),
(18, 1, 429.00, 0.00, 18, 7501002000020, NULL),
(19, 1, 429.00, 0.00, 19, 7501002000020, NULL),
(20, 1, 429.00, 0.00, 20, 7501002000020, NULL),
(21, 1, 899.00, 0.00, 20, 7501002000018, NULL),
(22, 1, 899.00, 0.00, 21, 7501002000018, NULL),
(23, 1, 200.00, 0.00, 21, 123456, NULL),
(24, 1, 429.00, 0.00, 22, 7501002000020, NULL),
(25, 2, 899.00, 0.00, 22, 7501002000018, NULL),
(26, 1, 899.00, 0.00, 23, 7501002000018, NULL),
(27, 1, 200.00, 0.00, 24, 2345, NULL),
(28, 2, 200.00, 0.00, 25, 2345, NULL),
(29, 1, 899.00, 0.00, 26, 7501002000018, NULL);

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
('A0001', 'Lucía', 'García', 'Mendoza', '3141111111', 'JOEL MONTES CAMARENA', '660', '', 'CENTRO MANZANILLO', '28279', 'COLIMA', 1, '2025-11-24 01:06:10', 1),
('A0004', 'Zinedine Hiram', 'Miranda', 'Campos', '3141643674', 'Barra de Navidad', '20', NULL, 'LAS TORRES', '28237', 'Col.', 1, '2025-11-26 14:42:44', 1),
('C0001', 'Vanessa Yamile', 'SIbaja', 'Barragan', '3141342958', 'JOEL MONTES CAMARENA', '660', '', 'CENTRO MANZANILLO', '28279', 'COLIMA', 1, '2025-11-24 10:47:47', 3),
('EMP001', 'Lucia', 'Mendoza', 'Lopez', '5512345678', 'Calle Falsa', '123', 'A', 'Colonia Centro', '01234', 'CDMX', 1, '2025-11-24 01:15:31', 1),
('EMP002', 'Carlos', 'Ramirez', 'Diaz', '5523456789', 'Av. Siempre Viva', '456', '', 'Colonia Norte', '56789', 'CDMX', 1, '2025-11-24 01:15:31', 2),
('EMP003', 'Ana', 'Torres', 'Gomez', '5534567890', 'Calle Luna', '789', 'B', 'Colonia Sur', '67890', 'CDMX', 1, '2025-11-24 01:15:31', 3);

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

--
-- Volcado de datos para la tabla `pagos_venta`
--

INSERT INTO `pagos_venta` (`id_pago`, `metodo`, `monto`, `referencia`, `fecha_pago`, `id_venta`) VALUES
(1, 'EFECTIVO', 200.00, '', '2025-11-19 22:44:03', 2),
(2, 'EFECTIVO', 400.00, '', '2025-11-19 22:45:16', 3),
(3, 'EFECTIVO', 150.00, '', '2025-11-19 22:47:09', 4),
(4, 'EFECTIVO', 100.00, '', '2025-11-20 07:41:35', 5),
(5, 'EFECTIVO', 100.00, '', '2025-11-21 06:05:06', 7),
(6, 'EFECTIVO', 100.00, '', '2025-11-21 06:05:11', 8),
(7, 'EFECTIVO', 299.00, '', '2025-11-24 01:31:22', 17),
(8, 'EFECTIVO', 429.00, '', '2025-11-24 01:56:26', 18),
(9, 'EFECTIVO', 429.00, '', '2025-11-24 01:56:30', 19),
(10, 'EFECTIVO', 1328.00, '', '2025-11-24 06:52:09', 20),
(11, 'EFECTIVO', 1099.00, '', '2025-11-24 19:17:19', 21),
(12, 'EFECTIVO', 2227.00, '', '2025-11-25 08:15:46', 22),
(13, 'EFECTIVO', 899.00, '', '2025-11-25 08:23:35', 23),
(14, 'EFECTIVO', 200.00, '', '2025-11-26 16:30:17', 24),
(15, 'EFECTIVO', 400.00, '', '2025-11-26 16:32:37', 25),
(16, 'EFECTIVO', 899.00, '', '2025-11-27 00:44:30', 26);

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
(2345, 'Blusa', '', '', NULL, 'M', 'Negro', NULL, 7, 2, 100.00, 200.00, 7, NULL, 1),
(123456, 'Pantalon', '', '', NULL, 'M', 'Blanco', NULL, 4, 5, 100.00, 200.00, 3, NULL, 0),
(7501002000018, 'Chamarra Mezclilla Clásica', 'Chamarra de mezclilla de corte tradicional unisex.', 'DenimCo', NULL, 'M', 'Azul', 'DC101', 19, 3, 450.00, 899.00, 9, NULL, 1),
(7501002000019, 'Shorts Running Dry-Fit', 'Shorts ligeros para correr con tecnología de secado rápido.', 'SportMax', NULL, 'L', 'Negro', 'SM005', 30, 10, 120.00, 269.00, 11, NULL, 1),
(7501002000020, 'Blusa de Seda Cuello V', 'Blusa elegante de seda con cuello en V.', 'Elegance', NULL, NULL, NULL, 'EL500', 91, 5, 190.00, 429.00, 2, NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `id_proveedor` int(11) NOT NULL,
  `empresa` varchar(100) NOT NULL,
  `representante` varchar(100) DEFAULT NULL,
  `celular` varchar(15) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `estatus` tinyint(1) NOT NULL DEFAULT 1,
  `nombre` varchar(100) NOT NULL,
  `apellido_paterno` varchar(100) NOT NULL,
  `apellido_materno` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `proveedores`
--

INSERT INTO `proveedores` (`id_proveedor`, `empresa`, `representante`, `celular`, `correo`, `estatus`, `nombre`, `apellido_paterno`, `apellido_materno`) VALUES
(1, 'MirCam', NULL, '3141643674', 'zinedinehirammirandacampos@gmail.com', 1, 'Manferd Von', 'Miranda', 'Sibaja'),
(2, 'Llavazo enterpraises', NULL, '3141749037', 'mballato@ucol.mx', 1, 'Mario Betito', 'Llabato', 'Roman');

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
(14, 'admin@prisma.com', '$2y$10$qu43QMsvDDH9qTeDDGOq0u3ICh/9yO9odl0DeWaoPkM.0Vs2EOjlu', '../public/uploads/fotos_perfil/user_14_1763971134.jpg', 'A0001'),
(15, 'ola@gmail.com', '$2y$10$IsmhERJa05.H4VCqsM2ZH.TeYSJfMqzTroZXN9tdPQBV7y8ScqceC', NULL, 'C0001'),
(22, 'admin@prisma.com', '$2y$10$qu43QMsvDDH9qTeDDGOq0u3ICh/9yO9odl0DeWaoPkM.0Vs2EOjlu', 'default.png', 'EMP001'),
(23, 'gerente@prisma.com', '$2y$10$PZOBR7M8KpXzYEUownc.Tuu8jCDQotn5kuh54W6xUiBMXp0cKqMeW', 'default.png', 'EMP002'),
(24, 'cajero@prisma.com', '$2y$10$hW6uh/ZkA2h1zzlK1/TgQ.94B3VQzG/jZ0xb9v5zDxSnPyxOPs22G', 'default.png', 'EMP003'),
(27, 'zinehiramc@outlook.com', '$2y$12$sfVs4cGEr8tRl2lDckTEa.IXXB1DllMtG8TeGbo0Qw9ldQQ5jbhCu', '../public/uploads/fotos_perfil/user_27_1764216397.jpg', 'A0004');

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
(0, 'Negro', 'S', NULL, 'EL500-S', 10, 2, 190.00, 429.00, NULL, 1);

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
-- Volcado de datos para la tabla `ventas`
--

INSERT INTO `ventas` (`id_venta`, `fecha`, `subtotal`, `descuento_general`, `total`, `id_cliente`, `id_empleado`) VALUES
(1, '2025-11-19 17:43:11', 1250.00, 0.00, 1250.00, NULL, NULL),
(2, '2025-11-19 22:44:03', 200.00, 0.00, 200.00, NULL, NULL),
(3, '2025-11-19 22:45:16', 400.00, 0.00, 400.00, NULL, NULL),
(4, '2025-11-19 22:47:09', 300.00, 150.00, 150.00, NULL, NULL),
(5, '2025-11-20 07:41:35', 200.00, 100.00, 100.00, NULL, NULL),
(6, '2025-11-20 07:42:03', 0.00, 0.00, 0.00, NULL, NULL),
(7, '2025-11-21 06:05:06', 200.00, 100.00, 100.00, NULL, NULL),
(8, '2025-11-21 06:05:11', 200.00, 100.00, 100.00, NULL, NULL),
(13, '2025-11-24 01:21:54', 249.00, 0.00, 249.00, NULL, 'A0001'),
(14, '2025-11-24 01:28:07', 837.00, 0.00, 837.00, NULL, 'A0001'),
(16, '2025-11-24 01:30:32', 299.00, 0.00, 299.00, NULL, 'A0001'),
(17, '2025-11-24 01:31:22', 299.00, 0.00, 299.00, NULL, 'A0001'),
(18, '2025-11-24 01:56:26', 429.00, 0.00, 429.00, NULL, 'A0001'),
(19, '2025-11-24 01:56:30', 429.00, 0.00, 429.00, NULL, 'A0001'),
(20, '2025-11-24 06:52:08', 1328.00, 0.00, 1328.00, NULL, 'A0001'),
(21, '2025-11-24 19:17:19', 1099.00, 0.00, 1099.00, 11, 'A0001'),
(22, '2025-11-25 08:15:46', 2227.00, 0.00, 2227.00, NULL, 'A0001'),
(23, '2025-11-25 08:23:34', 899.00, 0.00, 899.00, NULL, 'A0001'),
(24, '2025-11-26 16:30:17', 200.00, 0.00, 200.00, NULL, 'A0004'),
(25, '2025-11-26 16:32:37', 400.00, 0.00, 400.00, 11, 'A0004'),
(26, '2025-11-27 00:44:30', 899.00, 0.00, 899.00, 11, 'A0004');

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
-- AUTO_INCREMENT de la tabla `apartados`
--
ALTER TABLE `apartados`
  MODIFY `id_apartado` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `caja_movimientos`
--
ALTER TABLE `caja_movimientos`
  MODIFY `id_movimiento` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `cortes_caja`
--
ALTER TABLE `cortes_caja`
  MODIFY `id_corte` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `detalle_apartados`
--
ALTER TABLE `detalle_apartados`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de la tabla `inventario_movimientos`
--
ALTER TABLE `inventario_movimientos`
  MODIFY `id_movimiento` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pagos_venta`
--
ALTER TABLE `pagos_venta`
  MODIFY `id_pago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id_proveedor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id_venta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

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
