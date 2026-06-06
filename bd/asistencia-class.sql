-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 06-06-2026 a las 13:17:48
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `asistencia-class`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `administrador`
--

CREATE TABLE `administrador` (
  `id_admin` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `area_responsabilidad` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `administrador`
--

INSERT INTO `administrador` (`id_admin`, `usuario_id`, `area_responsabilidad`) VALUES
(1, 1, 'Bedelía - Turno Noche'),
(2, 2, 'Sistemas y Soporte Técnico');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alumno`
--

CREATE TABLE `alumno` (
  `legajo` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `condicion` enum('regular','libre') NOT NULL DEFAULT 'regular'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `alumno`
--

INSERT INTO `alumno` (`legajo`, `usuario_id`, `condicion`) VALUES
(1, 5, 'regular'),
(2, 6, 'regular'),
(3, 7, 'regular'),
(4, 8, 'regular');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistencia`
--

CREATE TABLE `asistencia` (
  `id` int(11) NOT NULL,
  `alumno_legajo` int(11) NOT NULL,
  `clase_diaria_id` int(11) NOT NULL,
  `fecha_hora_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado` enum('presente','ausente') NOT NULL DEFAULT 'presente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `asistencia`
--

INSERT INTO `asistencia` (`id`, `alumno_legajo`, `clase_diaria_id`, `fecha_hora_registro`, `estado`) VALUES
(1, 1, 1, '2026-06-05 13:02:23', 'presente'),
(2, 2, 1, '2026-06-05 13:02:23', 'presente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clase_diaria`
--

CREATE TABLE `clase_diaria` (
  `id` int(11) NOT NULL,
  `comision_id` int(11) NOT NULL,
  `fecha_hora_inicio` timestamp NOT NULL DEFAULT current_timestamp(),
  `modalidad` enum('presencial','virtual') NOT NULL,
  `codigo_token` varchar(100) NOT NULL,
  `expira_en` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `clase_diaria`
--

INSERT INTO `clase_diaria` (`id`, `comision_id`, `fecha_hora_inicio`, `modalidad`, `codigo_token`, `expira_en`) VALUES
(1, 1, '2026-06-05 13:01:38', 'presencial', 'TOKEN_SECURE_QR_ABC123', '2026-06-05 13:16:38');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comision`
--

CREATE TABLE `comision` (
  `id` int(11) NOT NULL,
  `nombre_comision` varchar(30) NOT NULL,
  `profesor_legajo` int(11) NOT NULL,
  `materia_assigned` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `comision`
--

INSERT INTO `comision` (`id`, `nombre_comision`, `profesor_legajo`, `materia_assigned`) VALUES
(1, 'Comisión 3° Año - Noche', 1, 1),
(2, 'Comisión 2° Año - Tarde', 2, 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comision_alumno`
--

CREATE TABLE `comision_alumno` (
  `comision_id` int(11) NOT NULL,
  `alumno_legajo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `comision_alumno`
--

INSERT INTO `comision_alumno` (`comision_id`, `alumno_legajo`) VALUES
(1, 1),
(1, 2),
(2, 3),
(2, 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materia`
--

CREATE TABLE `materia` (
  `id` int(11) NOT NULL,
  `nombre_materia` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `materia`
--

INSERT INTO `materia` (`id`, `nombre_materia`) VALUES
(2, 'Base de Datos'),
(1, 'Programación Web Backend');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `profesor`
--

CREATE TABLE `profesor` (
  `legajo` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `titulo` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `profesor`
--

INSERT INTO `profesor` (`legajo`, `usuario_id`, `titulo`) VALUES
(1, 3, 'Ingeniero en Sistemas de Información'),
(2, 4, 'Licenciada en Ciencias de la Computación');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol`
--

CREATE TABLE `rol` (
  `id` int(11) NOT NULL,
  `nombre_rol` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `rol`
--

INSERT INTO `rol` (`id`, `nombre_rol`) VALUES
(1, 'Administrador'),
(3, 'Alumno'),
(2, 'Profesor');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `dni` int(11) NOT NULL,
  `celular` varchar(20) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol_id` int(11) NOT NULL,
  `activo` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id`, `nombre`, `apellido`, `dni`, `celular`, `email`, `password`, `rol_id`, `activo`) VALUES
(1, 'Ana', 'Gómez', 25666777, '1133445566', 'ana.gomez@admin.frh.utn.edu.ar', '$2y$10$M6vG6B76Xb.7L9fV9G8pIeuS7wZ7fFvGvH8O8xV6eY7z3QW5r8y1.', 1, 1),
(2, 'Ricardo', 'Sánchez', 28999111, '1144556677', 'ricardo.sanchez@admin.frh.utn.edu.ar', '$2y$10$M6vG6B76Xb.7L9fV9G8pIeuS7wZ7fFvGvH8O8xV6eY7z3QW5r8y1.', 1, 1),
(3, 'Carlos', 'López', 22333444, '1122334455', 'carlos.lopez@docente.frh.utn.edu.ar', '$2y$10$M6vG6B76Xb.7L9fV9G8pIeuS7wZ7fFvGvH8O8xV6eY7z3QW5r8y1.', 2, 1),
(4, 'Patricia', 'Martínez', 24555666, '1166778899', 'patricia.martinez@docente.frh.utn.edu.ar', '$2y$10$M6vG6B76Xb.7L9fV9G8pIeuS7wZ7fFvGvH8O8xV6eY7z3QW5r8y1.', 2, 1),
(5, 'Javier', 'Pérez', 34444555, '1155667788', 'javier@alumnos.frh.utn.edu.ar', 'e10adc3949ba59abbe56e057f20f883e', 3, 1),
(6, 'Mateo', 'Díaz', 40111222, '1199887766', 'mateo.diaz@alumnos.frh.utn.edu.ar', '$2y$10$M6vG6B76Xb.7L9fV9G8pIeuS7wZ7fFvGvH8O8xV6eY7z3QW5r8y1.', 3, 1),
(7, 'Lucas', 'Fernández', 42333444, '1122446688', 'lucas.fernandez@alumnos.frh.utn.edu.ar', '$2y$10$M6vG6B76Xb.7L9fV9G8pIeuS7wZ7fFvGvH8O8xV6eY7z3QW5r8y1.', 3, 1),
(8, 'Sofía', 'Rodríguez', 41555666, '1133557799', 'sofia.rodriguez@alumnos.frh.utn.edu.ar', '$2y$10$M6vG6B76Xb.7L9fV9G8pIeuS7wZ7fFvGvH8O8xV6eY7z3QW5r8y1.', 3, 1),
(11, 'admin', 'principal', 12345678, NULL, 'admin_principal@admin.frh.utn.edu.ar', '$2y$10$ybjxo7Wp2axfo42mttUCnefn38Clrf9lza8zuKHFqdh./g67DI0ni', 1, 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `administrador`
--
ALTER TABLE `administrador`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `alumno`
--
ALTER TABLE `alumno`
  ADD PRIMARY KEY (`legajo`),
  ADD UNIQUE KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `asistencia`
--
ALTER TABLE `asistencia`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unica_asistencia_por_clase` (`alumno_legajo`,`clase_diaria_id`),
  ADD KEY `clase_diaria_id` (`clase_diaria_id`);

--
-- Indices de la tabla `clase_diaria`
--
ALTER TABLE `clase_diaria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `comision_id` (`comision_id`);

--
-- Indices de la tabla `comision`
--
ALTER TABLE `comision`
  ADD PRIMARY KEY (`id`),
  ADD KEY `profesor_legajo` (`profesor_legajo`),
  ADD KEY `materia_assigned` (`materia_assigned`);

--
-- Indices de la tabla `comision_alumno`
--
ALTER TABLE `comision_alumno`
  ADD PRIMARY KEY (`comision_id`,`alumno_legajo`),
  ADD KEY `alumno_legajo` (`alumno_legajo`);

--
-- Indices de la tabla `materia`
--
ALTER TABLE `materia`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre_materia` (`nombre_materia`);

--
-- Indices de la tabla `profesor`
--
ALTER TABLE `profesor`
  ADD PRIMARY KEY (`legajo`),
  ADD UNIQUE KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `rol`
--
ALTER TABLE `rol`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre_rol` (`nombre_rol`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dni` (`dni`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `rol_id` (`rol_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `administrador`
--
ALTER TABLE `administrador`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `asistencia`
--
ALTER TABLE `asistencia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `clase_diaria`
--
ALTER TABLE `clase_diaria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `comision`
--
ALTER TABLE `comision`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `materia`
--
ALTER TABLE `materia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `rol`
--
ALTER TABLE `rol`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `administrador`
--
ALTER TABLE `administrador`
  ADD CONSTRAINT `administrador_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `alumno`
--
ALTER TABLE `alumno`
  ADD CONSTRAINT `alumno_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `asistencia`
--
ALTER TABLE `asistencia`
  ADD CONSTRAINT `asistencia_ibfk_1` FOREIGN KEY (`alumno_legajo`) REFERENCES `alumno` (`legajo`),
  ADD CONSTRAINT `asistencia_ibfk_2` FOREIGN KEY (`clase_diaria_id`) REFERENCES `clase_diaria` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `clase_diaria`
--
ALTER TABLE `clase_diaria`
  ADD CONSTRAINT `clase_diaria_ibfk_1` FOREIGN KEY (`comision_id`) REFERENCES `comision` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `comision`
--
ALTER TABLE `comision`
  ADD CONSTRAINT `comision_ibfk_1` FOREIGN KEY (`profesor_legajo`) REFERENCES `profesor` (`legajo`),
  ADD CONSTRAINT `comision_ibfk_2` FOREIGN KEY (`materia_assigned`) REFERENCES `materia` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `comision_alumno`
--
ALTER TABLE `comision_alumno`
  ADD CONSTRAINT `comision_alumno_ibfk_1` FOREIGN KEY (`comision_id`) REFERENCES `comision` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comision_alumno_ibfk_2` FOREIGN KEY (`alumno_legajo`) REFERENCES `alumno` (`legajo`);

--
-- Filtros para la tabla `profesor`
--
ALTER TABLE `profesor`
  ADD CONSTRAINT `profesor_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `rol` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
