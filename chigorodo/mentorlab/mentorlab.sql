-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 15-08-2025 a las 23:25:52
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
-- Base de datos: `mentorlab`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mentores`
--

CREATE TABLE `mentores` (
  `ID` int(11) NOT NULL,
  `Mentor` varchar(50) NOT NULL,
  `Modalidad` varchar(50) NOT NULL,
  `Area` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `mentores`
--

INSERT INTO `mentores` (`ID`, `Mentor`, `Modalidad`, `Area`) VALUES
(1, 'juan luis', 'presencial', 'matematicas'),
(2, 'Marta lucia', 'presencial', 'idiomas'),
(3, 'juan gabriel', 'presencial', 'administracion'),
(4, 'carolina ruiz', 'presencial', 'tecnologia y desarrollo'),
(5, 'Kevin cuadrado', 'presencial', 'historia'),
(6, 'yennifer machado', 'virtual', 'matematicas'),
(7, 'manuel flores', 'virtual', 'idiomas'),
(8, 'mariana gonzalez', 'virtual', 'tecnologia y desarrollo'),
(9, 'valeria giraldo', 'virtual', 'administracion'),
(10, 'alejandra cordoba', 'virtual', 'historia');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `paquetes`
--

CREATE TABLE `paquetes` (
  `ID` int(11) NOT NULL,
  `Nombre` varchar(50) NOT NULL,
  `precio` decimal(50,0) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `paquetes`
--

INSERT INTO `paquetes` (`ID`, `Nombre`, `precio`) VALUES
(23, 'paquete juvenil', 500000),
(24, 'paquete familiar', 300000),
(25, 'paquete escolar', 450000),
(26, 'paquete infantil', 250000);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `promociones`
--

CREATE TABLE `promociones` (
  `ID` int(11) NOT NULL,
  `Nombre` varchar(50) NOT NULL,
  `Promocion` int(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `promociones`
--

INSERT INTO `promociones` (`ID`, `Nombre`, `Promocion`) VALUES
(30, 'paquete escolar', 50),
(31, 'paquete familiar', 25);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `ID` int(11) NOT NULL,
  `Nombre` varchar(50) NOT NULL,
  `correo` varchar(50) NOT NULL,
  `contraseña` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`ID`, `Nombre`, `correo`, `contraseña`) VALUES
(40, 'andrea', 'andrea@gmail.com', 'andrea123'),
(41, 'kevin', 'kevincito@gmail.com', 'kevin123'),
(42, 'carla', 'carla@gmail.com', 'carla123'),
(43, 'andres', 'andres@gmail.com', 'andres123'),
(44, 'luisa', 'luisa@gmail.com', 'luisa123');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `mentores`
--
ALTER TABLE `mentores`
  ADD PRIMARY KEY (`ID`);

--
-- Indices de la tabla `paquetes`
--
ALTER TABLE `paquetes`
  ADD PRIMARY KEY (`ID`);

--
-- Indices de la tabla `promociones`
--
ALTER TABLE `promociones`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `Promocion` (`Promocion`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
