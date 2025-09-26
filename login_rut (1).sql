-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:8889
-- Tiempo de generación: 26-09-2025 a las 02:08:03
-- Versión del servidor: 8.0.40
-- Versión de PHP: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `login_rut`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sgae_modulos`
--

CREATE TABLE `sgae_modulos` (
  `id` int NOT NULL,
  `nombre` varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
  `etiqueta` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `ruta` varchar(128) COLLATE utf8mb4_general_ci NOT NULL,
  `tabla` varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `rut` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `dv` char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `correo` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`rut`, `dv`, `nombre`, `correo`, `password`) VALUES
('18804911', '7', 'josue', 'josuepazmio@gmail.com', '$2y$10$d3rVcqUrNh76.jBnSTuxKeLm4WJUP0WtMkiC4fzXp90B8mLoFpUhK');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `sgae_modulos`
--
ALTER TABLE `sgae_modulos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sgae_modulos_ruta` (`ruta`),
  ADD UNIQUE KEY `uq_sgae_modulos_tabla` (`tabla`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `sgae_modulos`
--
ALTER TABLE `sgae_modulos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
