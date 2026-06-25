-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 22-06-2026 a las 14:09:08
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
-- Base de datos: `asistencia_qr`
--
CREATE DATABASE IF NOT EXISTS `asistencia_qr` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `asistencia_qr`;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistencias`
--

DROP TABLE IF EXISTS `asistencias`;
CREATE TABLE `asistencias` (
  `id` int(10) UNSIGNED NOT NULL,
  `alumno_id` int(10) UNSIGNED NOT NULL,
  `clase_id` int(10) UNSIGNED NOT NULL,
  `hora_entrada` time DEFAULT NULL,
  `hora_salida` time DEFAULT NULL,
  `estado` enum('presente','tardanza','ausente') NOT NULL DEFAULT 'ausente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clases`
--

DROP TABLE IF EXISTS `clases`;
CREATE TABLE `clases` (
  `id` int(10) UNSIGNED NOT NULL,
  `materia_id` int(10) UNSIGNED NOT NULL,
  `fecha` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `duracion_min` smallint(5) UNSIGNED NOT NULL DEFAULT 90,
  `aula` varchar(50) DEFAULT NULL,
  `modalidad` enum('presencial','virtual') NOT NULL DEFAULT 'presencial',
  `estado` enum('pendiente','en_curso','finalizada') NOT NULL DEFAULT 'pendiente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clases`
--

INSERT INTO `clases` (`id`, `materia_id`, `fecha`, `hora_inicio`, `duracion_min`, `aula`, `modalidad`, `estado`, `created_at`) VALUES
(1, 1, '2026-06-20', '22:55:00', 90, '101', 'presencial', 'finalizada', '2026-06-21 01:49:51'),
(2, 1, '2026-06-20', '23:04:00', 90, '101', 'presencial', 'en_curso', '2026-06-21 02:03:10');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion`
--

DROP TABLE IF EXISTS `configuracion`;
CREATE TABLE `configuracion` (
  `clave` varchar(60) NOT NULL,
  `valor` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `configuracion`
--

INSERT INTO `configuracion` (`clave`, `valor`) VALUES
('nombre_institucion', 'Instituto Técnico'),
('notificaciones_activas', '1'),
('periodo_activo', '2026-1'),
('qr_rotacion_segundos', '30'),
('tolerancia_minutos', '10');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cursos`
--

DROP TABLE IF EXISTS `cursos`;
CREATE TABLE `cursos` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cursos`
--

INSERT INTO `cursos` (`id`, `nombre`) VALUES
(1, 'M1A'),
(2, 'M1B'),
(3, 'M2'),
(4, 'M3'),
(5, 'M4'),
(6, 'N1'),
(7, 'N3');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inscripciones`
--

DROP TABLE IF EXISTS `inscripciones`;
CREATE TABLE `inscripciones` (
  `id` int(10) UNSIGNED NOT NULL,
  `alumno_id` int(10) UNSIGNED NOT NULL,
  `materia_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `inscripciones`
--

INSERT INTO `inscripciones` (`id`, `alumno_id`, `materia_id`, `created_at`) VALUES
(1, 2, 1, '2026-06-21 02:37:04'),
(2, 25, 2, '2026-06-21 23:48:53'),
(3, 23, 2, '2026-06-21 23:49:00'),
(4, 16, 2, '2026-06-21 23:49:08'),
(6, 16, 1, '2026-06-22 12:05:46');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materias`
--

DROP TABLE IF EXISTS `materias`;
CREATE TABLE `materias` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `codigo` varchar(30) NOT NULL,
  `curso` varchar(20) NOT NULL,
  `modalidad` enum('presencial','virtual') NOT NULL DEFAULT 'presencial',
  `profesor_id` int(10) UNSIGNED DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `materias`
--

INSERT INTO `materias` (`id`, `nombre`, `codigo`, `curso`, `modalidad`, `profesor_id`, `activo`, `created_at`) VALUES
(1, 'Introduccion al Analisis de Datos', 'IAADD', '1° A', 'presencial', 4, 1, '2026-06-21 00:31:33'),
(2, 'Ingles I', 'INGI', '1° B', 'presencial', 4, 1, '2026-06-21 02:38:03');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materia_horarios`
--

DROP TABLE IF EXISTS `materia_horarios`;
CREATE TABLE `materia_horarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `materia_id` int(10) UNSIGNED NOT NULL,
  `dia_semana` tinyint(3) UNSIGNED NOT NULL COMMENT '1=Lun 2=Mar 3=Mié 4=Jue 5=Vie 6=Sáb 7=Dom',
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `materia_horarios`
--

INSERT INTO `materia_horarios` (`id`, `materia_id`, `dia_semana`, `hora_inicio`, `hora_fin`) VALUES
(1, 2, 1, '23:37:00', '23:40:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `qr_tokens`
--

DROP TABLE IF EXISTS `qr_tokens`;
CREATE TABLE `qr_tokens` (
  `id` int(10) UNSIGNED NOT NULL,
  `clase_id` int(10) UNSIGNED NOT NULL,
  `token` varchar(64) NOT NULL,
  `tipo` enum('entrada','salida') NOT NULL DEFAULT 'entrada',
  `expira_en` datetime NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `qr_tokens`
--

INSERT INTO `qr_tokens` (`id`, `clase_id`, `token`, `tipo`, `expira_en`, `activo`, `created_at`) VALUES
(1, 1, 'e3676fcf', 'entrada', '2026-06-20 22:55:50', 0, '2026-06-21 01:55:15'),
(2, 1, '298727fa', 'entrada', '2026-06-20 22:56:20', 0, '2026-06-21 01:55:45'),
(3, 1, 'ec6818db', 'entrada', '2026-06-20 22:56:50', 0, '2026-06-21 01:56:15'),
(4, 1, 'eac89318', 'entrada', '2026-06-20 22:57:20', 0, '2026-06-21 01:56:45'),
(5, 1, '897bbc6b', 'entrada', '2026-06-20 22:57:50', 0, '2026-06-21 01:57:15'),
(6, 2, 'ed5c607f', 'entrada', '2026-06-20 23:04:41', 0, '2026-06-21 02:04:06'),
(7, 2, 'ec626dd1', 'entrada', '2026-06-20 23:05:11', 0, '2026-06-21 02:04:36'),
(8, 2, '7c4f103b', 'entrada', '2026-06-20 23:05:41', 0, '2026-06-21 02:05:06'),
(9, 2, '3398ca60', 'entrada', '2026-06-20 23:06:11', 0, '2026-06-21 02:05:36'),
(10, 2, 'dacfe8e6', 'entrada', '2026-06-20 23:06:49', 1, '2026-06-21 02:06:14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `legajo` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('alumno','profesor','secretaria','admin') NOT NULL,
  `curso` varchar(20) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `legajo`, `nombre`, `apellido`, `email`, `password`, `rol`, `curso`, `activo`, `created_at`) VALUES
(1, '90001', 'Admin', 'Sistema', 'admin@instituto.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, 1, '2026-06-20 15:13:02'),
(2, '29533', 'Yoel', 'Arequipa', NULL, '$2y$10$hNPsyOX1iVHwzU5fIZg8XOxs/Fr7rp8EWRlQitJ3.24TqkKJ8BAbi', 'alumno', '2° A', 1, '2026-06-20 15:53:44'),
(3, '90002', 'Maria', 'Lopez', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'secretaria', NULL, 1, '2026-06-21 00:12:53'),
(4, '19001', 'Gustavo', 'Mendez', 'gustavomendez@frh.utn.edu.ar', '$2y$10$Kkf2ZaKkUXV3reieso4fb.nzt0jbVcCn3bHit5J/lFlwXqC81EA5q', 'profesor', NULL, 1, '2026-06-21 00:20:58'),
(5, '19002', 'Ramon', 'Perez', 'ramonperez@frh.utn.edu.ar', '$2y$10$e4wQaxcCsPSZFMwutCQkjuUKchQUow2R2TtQ4BY2yiXIxc4e0q/v.', 'profesor', NULL, 1, '2026-06-21 22:35:16'),
(6, '29030', 'Valentina', 'González', 'valentina.gonzalez030@alumnos.frh.utn.edu.ar', '$2y$10$Arze5IsbGo0LTKfy5H19xuvGHodQZEP41BZlrY770A/chp89vsK1u', 'alumno', 'M4', 1, '2026-06-21 23:46:32'),
(7, '29031', 'Mateo', 'Fernández', 'mateo.fernandez031@alumnos.frh.utn.edu.ar', '$2y$10$hfZUllhwwGImHMOCVUgJg.Mzq3CTx51swBcwT/DyNPYy5YhEtunLq', 'alumno', 'M4', 1, '2026-06-21 23:46:32'),
(8, '29032', 'Luciana', 'López', 'luciana.lopez032@alumnos.frh.utn.edu.ar', '$2y$10$9.N6.TKol/BAlNaWgz/uDeho9z2vZDDiu9bf/XZyBWgoTQIhFo8tS', 'alumno', 'M4', 1, '2026-06-21 23:46:33'),
(9, '29033', 'Santiago', 'Martínez', 'santiago.martinez033@alumnos.frh.utn.edu.ar', '$2y$10$e.KaEH4wG.0vwf/5rfrMgOQBJWNa3p5zwq1GebqRSNqcyn2BUN8Hy', 'alumno', 'M4', 1, '2026-06-21 23:46:33'),
(10, '29034', 'Camila', 'Rodríguez', 'camila.rodriguez034@alumnos.frh.utn.edu.ar', '$2y$10$zuY52O6sQsYvISnX.u1E3eJOis3PjVyL6uwHVECl.sIN8MGl/.2JS', 'alumno', 'M4', 1, '2026-06-21 23:46:33'),
(11, '29035', 'Nicolás', 'Pérez', 'nicolas.perez035@alumnos.frh.utn.edu.ar', '$2y$10$4jmIxnDBUVOZ1N8ZHa8V7ODqXCKgeGcU2b01F7SwIE/FkID7uCs1q', 'alumno', 'M4', 1, '2026-06-21 23:46:33'),
(12, '29036', 'Sofía', 'Sánchez', 'sofia.sanchez036@alumnos.frh.utn.edu.ar', '$2y$10$PoU9dSwJLTl3atK1JIWs8ODlvuZB6Jkswju61YECN/DmfXIH1Akki', 'alumno', 'M4', 1, '2026-06-21 23:46:33'),
(13, '29037', 'Joaquín', 'Romero', 'joaquin.romero037@alumnos.frh.utn.edu.ar', '$2y$10$y7eXdzlnckEYU2.hJIiWT.qgicj6vTca/Vmka0iUE/T8jZ.XtcTNi', 'alumno', 'M4', 1, '2026-06-21 23:46:33'),
(14, '29038', 'Martina', 'Torres', 'martina.torres038@alumnos.frh.utn.edu.ar', '$2y$10$ImnynkytteH/bzh4bOVGHut7e1LteVbdURFjalBhFrpO0Cy32vllu', 'alumno', 'M4', 1, '2026-06-21 23:46:33'),
(15, '29039', 'Tomás', 'Díaz', 'tomas.diaz039@alumnos.frh.utn.edu.ar', '$2y$10$AMQuDkGM3CMJxsdEr74n7eoupfuyO3p2pxw9oWTkZbajn8OSbKu3W', 'alumno', 'M4', 1, '2026-06-21 23:46:33'),
(16, '29040', 'Agustina', 'Álvarez', 'agustina.alvarez040@alumnos.frh.utn.edu.ar', '$2y$10$4.2q0a8rDoLB9LkASDGCuuNoIMDyh8wq1oie88hp9HzC245VLhF3y', 'alumno', 'M4', 1, '2026-06-21 23:46:33'),
(17, '29041', 'Facundo', 'Ruiz', 'facundo.ruiz041@alumnos.frh.utn.edu.ar', '$2y$10$CFI7TQ.VKI3joUjfhihfZODg44u2MHxZ1Xv3J3zjObRL9T1FCWMIC', 'alumno', 'M4', 1, '2026-06-21 23:46:33'),
(18, '29042', 'Florencia', 'Ramírez', 'florencia.ramirez042@alumnos.frh.utn.edu.ar', '$2y$10$RJjQOy0Y.WAPVaVqFqifle9NDfhx1fT2fRIKcQgwHbeElU3waMax2', 'alumno', 'M4', 1, '2026-06-21 23:46:33'),
(19, '29043', 'Ignacio', 'Flores', 'ignacio.flores043@alumnos.frh.utn.edu.ar', '$2y$10$Ldb41lAYvSEVZQ20q/448epLHuZy.4TRoQj.LOdvLOH8j7nmBYW4e', 'alumno', 'M4', 1, '2026-06-21 23:46:33'),
(20, '29044', 'Micaela', 'Acosta', 'micaela.acosta044@alumnos.frh.utn.edu.ar', '$2y$10$dpXZhyXngNj1h.tyGqynVuEJUc1Qgr8rv3HXbLFLO.3cfmDuvz.qy', 'alumno', 'M4', 1, '2026-06-21 23:46:34'),
(21, '29045', 'Ezequiel', 'Benítez', 'ezequiel.benitez045@alumnos.frh.utn.edu.ar', '$2y$10$IVB489Exyf9mlYZRXqPOiOs.2H2oGNCbSSevwNpdRRc6avhFNJN0y', 'alumno', 'M4', 1, '2026-06-21 23:46:34'),
(22, '29046', 'Rocío', 'Castro', 'rocio.castro046@alumnos.frh.utn.edu.ar', '$2y$10$hKijaY8EpBl0rfjF4LaZoeeCbHTBNxbGjSsltSSLUTV3iIr/RXPQe', 'alumno', 'M4', 1, '2026-06-21 23:46:34'),
(23, '29047', 'Leandro', 'Herrera', 'leandro.herrera047@alumnos.frh.utn.edu.ar', '$2y$10$BE4aq.qAr09ADJVDfF6jx.lkRgdGSfBjK9Q132rbSCTbpwrmRNVG6', 'alumno', 'M4', 1, '2026-06-21 23:46:34'),
(24, '29048', 'Julieta', 'Medina', 'julieta.medina048@alumnos.frh.utn.edu.ar', '$2y$10$/ftilTazbuA2gR4ni5hVlul6clwHbSEUIV1mTR3ZisIKrI4DkNlXa', 'alumno', 'M4', 1, '2026-06-21 23:46:34'),
(25, '29049', 'Rodrigo', 'Giménez', 'rodrigo.gimenez049@alumnos.frh.utn.edu.ar', '$2y$10$DBbZGXdsEpSI8T0/fB/KLOJ4FLG5dW99wxGKdRTp1CeHuhApcEOeq', 'alumno', 'M4', 1, '2026-06-21 23:46:34'),
(26, '29050', 'Antonella', 'Suárez', 'antonella.suarez050@alumnos.frh.utn.edu.ar', '$2y$10$rpHVrg/lejU0QOwcTNqYU../QeVtbhf2YcbiiCK6K2Oc9Pl9hp/Te', 'alumno', 'M4', 1, '2026-06-21 23:46:34'),
(27, '29051', 'Federico', 'Morales', 'federico.morales051@alumnos.frh.utn.edu.ar', '$2y$10$hSiTG8jT8/WBdpDoiyU5q.K9JZX/qTFIedYKu9dJu/ku0kzIhQdwm', 'alumno', 'M4', 1, '2026-06-21 23:46:34'),
(28, '29052', 'Paula', 'Ortiz', 'paula.ortiz052@alumnos.frh.utn.edu.ar', '$2y$10$7HiNLqdBFHOkqQlcVkFNYOZfT7EVCsrVQ3//ifwMmkQYv47rYw61a', 'alumno', 'M4', 1, '2026-06-21 23:46:34'),
(29, '29053', 'Maximiliano', 'Silva', 'maximiliano.silva053@alumnos.frh.utn.edu.ar', '$2y$10$qmqfk6/Bbp0uhnq4uCboCe3LhY8RTmleyZD6COOad5WIVNGkD2GQC', 'alumno', 'M4', 1, '2026-06-21 23:46:34'),
(30, '29054', 'Daniela', 'Vargas', 'daniela.vargas054@alumnos.frh.utn.edu.ar', '$2y$10$wF8Lo2XUq1h9uBp.yZteiu3P.F7ZHR3qGr33OEtuCuHSw0nS6r8Qi', 'alumno', 'M4', 1, '2026-06-21 23:46:34'),
(31, '29055', 'Hernán', 'Cabrera', 'hernan.cabrera055@alumnos.frh.utn.edu.ar', '$2y$10$SGVuUFBUp730SWIQcoCSkuIU68fvsDb1ONkORtBzGrG6oU67wBY4K', 'alumno', 'M4', 1, '2026-06-21 23:46:34'),
(32, '29056', 'Celeste', 'Ríos', 'celeste.rios056@alumnos.frh.utn.edu.ar', '$2y$10$bx57Vu5rHd5BlXKO.zKZgeRd7R4CCYgIv6ZaXxaneO.8luux.xQvm', 'alumno', 'M4', 1, '2026-06-21 23:46:34'),
(33, '29057', 'Marcos', 'Vega', 'marcos.vega057@alumnos.frh.utn.edu.ar', '$2y$10$Nqg1heOEwl5j5LAJGj29t.hoDtp4NcDHapQn4o5.iE9UdvoVdX/ie', 'alumno', 'M4', 1, '2026-06-21 23:46:34'),
(34, '29058', 'Natalia', 'Molina', 'natalia.molina058@alumnos.frh.utn.edu.ar', '$2y$10$9WKXx99Gb/fqtJ8RegV78OkhsPbBJTN5YixfqKfNR4BAtTxDh.7hW', 'alumno', 'M4', 1, '2026-06-21 23:46:34'),
(35, '29059', 'Gustavo', 'Navarro', 'gustavo.navarro059@alumnos.frh.utn.edu.ar', '$2y$10$x3GF9cHUinEtyEu/7odVkOrCtpdf7is1Mv56JJHbj0YBj0rWhCrna', 'alumno', 'M4', 1, '2026-06-21 23:46:35');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_alumnos_en_riesgo`
-- (Véase abajo para la vista actual)
--
DROP VIEW IF EXISTS `v_alumnos_en_riesgo`;
CREATE TABLE `v_alumnos_en_riesgo` (
`alumno_id` int(10) unsigned
,`nombre` varchar(100)
,`apellido` varchar(100)
,`legajo` varchar(20)
,`curso` varchar(20)
,`materia_id` int(10) unsigned
,`materia` varchar(150)
,`total_clases` bigint(21)
,`clases_presentes` decimal(23,0)
,`porcentaje` decimal(28,1)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_asistencia_por_materia`
-- (Véase abajo para la vista actual)
--
DROP VIEW IF EXISTS `v_asistencia_por_materia`;
CREATE TABLE `v_asistencia_por_materia` (
`alumno_id` int(10) unsigned
,`nombre` varchar(100)
,`apellido` varchar(100)
,`legajo` varchar(20)
,`curso` varchar(20)
,`materia_id` int(10) unsigned
,`materia` varchar(150)
,`total_clases` bigint(21)
,`clases_presentes` decimal(23,0)
,`porcentaje` decimal(28,1)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_resumen_clases`
-- (Véase abajo para la vista actual)
--
DROP VIEW IF EXISTS `v_resumen_clases`;
CREATE TABLE `v_resumen_clases` (
`clase_id` int(10) unsigned
,`materia` varchar(150)
,`codigo` varchar(30)
,`curso` varchar(20)
,`profesor` varchar(201)
,`fecha` date
,`hora_inicio` time
,`duracion_min` smallint(5) unsigned
,`aula` varchar(50)
,`modalidad` enum('presencial','virtual')
,`estado` enum('pendiente','en_curso','finalizada')
,`total_alumnos` bigint(21)
,`presentes` decimal(23,0)
,`ausentes` decimal(24,0)
,`porcentaje` decimal(28,1)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `v_alumnos_en_riesgo`
--
DROP TABLE IF EXISTS `v_alumnos_en_riesgo`;

DROP VIEW IF EXISTS `v_alumnos_en_riesgo`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_alumnos_en_riesgo`  AS SELECT `v_asistencia_por_materia`.`alumno_id` AS `alumno_id`, `v_asistencia_por_materia`.`nombre` AS `nombre`, `v_asistencia_por_materia`.`apellido` AS `apellido`, `v_asistencia_por_materia`.`legajo` AS `legajo`, `v_asistencia_por_materia`.`curso` AS `curso`, `v_asistencia_por_materia`.`materia_id` AS `materia_id`, `v_asistencia_por_materia`.`materia` AS `materia`, `v_asistencia_por_materia`.`total_clases` AS `total_clases`, `v_asistencia_por_materia`.`clases_presentes` AS `clases_presentes`, `v_asistencia_por_materia`.`porcentaje` AS `porcentaje` FROM `v_asistencia_por_materia` WHERE `v_asistencia_por_materia`.`porcentaje` < 75 OR `v_asistencia_por_materia`.`porcentaje` is null ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_asistencia_por_materia`
--
DROP TABLE IF EXISTS `v_asistencia_por_materia`;

DROP VIEW IF EXISTS `v_asistencia_por_materia`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_asistencia_por_materia`  AS SELECT `i`.`alumno_id` AS `alumno_id`, `u`.`nombre` AS `nombre`, `u`.`apellido` AS `apellido`, `u`.`legajo` AS `legajo`, `u`.`curso` AS `curso`, `m`.`id` AS `materia_id`, `m`.`nombre` AS `materia`, count(`c`.`id`) AS `total_clases`, sum(`a`.`estado` in ('presente','tardanza')) AS `clases_presentes`, round(sum(`a`.`estado` in ('presente','tardanza')) / nullif(count(`c`.`id`),0) * 100,1) AS `porcentaje` FROM ((((`inscripciones` `i` join `usuarios` `u` on(`u`.`id` = `i`.`alumno_id`)) join `materias` `m` on(`m`.`id` = `i`.`materia_id`)) join `clases` `c` on(`c`.`materia_id` = `m`.`id` and `c`.`estado` = 'finalizada')) left join `asistencias` `a` on(`a`.`alumno_id` = `i`.`alumno_id` and `a`.`clase_id` = `c`.`id`)) GROUP BY `i`.`alumno_id`, `m`.`id` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_resumen_clases`
--
DROP TABLE IF EXISTS `v_resumen_clases`;

DROP VIEW IF EXISTS `v_resumen_clases`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_resumen_clases`  AS SELECT `c`.`id` AS `clase_id`, `m`.`nombre` AS `materia`, `m`.`codigo` AS `codigo`, `m`.`curso` AS `curso`, concat(`u`.`nombre`,' ',`u`.`apellido`) AS `profesor`, `c`.`fecha` AS `fecha`, `c`.`hora_inicio` AS `hora_inicio`, `c`.`duracion_min` AS `duracion_min`, `c`.`aula` AS `aula`, `c`.`modalidad` AS `modalidad`, `c`.`estado` AS `estado`, count(distinct `i`.`alumno_id`) AS `total_alumnos`, sum(`a`.`estado` in ('presente','tardanza')) AS `presentes`, count(distinct `i`.`alumno_id`) - coalesce(sum(`a`.`estado` in ('presente','tardanza')),0) AS `ausentes`, round(sum(`a`.`estado` in ('presente','tardanza')) / nullif(count(distinct `i`.`alumno_id`),0) * 100,1) AS `porcentaje` FROM ((((`clases` `c` join `materias` `m` on(`m`.`id` = `c`.`materia_id`)) left join `usuarios` `u` on(`u`.`id` = `m`.`profesor_id`)) left join `inscripciones` `i` on(`i`.`materia_id` = `m`.`id`)) left join `asistencias` `a` on(`a`.`clase_id` = `c`.`id` and `a`.`alumno_id` = `i`.`alumno_id`)) GROUP BY `c`.`id` ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `asistencias`
--
ALTER TABLE `asistencias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_asistencia` (`alumno_id`,`clase_id`),
  ADD KEY `idx_asistencias_clase` (`clase_id`),
  ADD KEY `idx_asistencias_alumno` (`alumno_id`);

--
-- Indices de la tabla `clases`
--
ALTER TABLE `clases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_clases_fecha` (`fecha`),
  ADD KEY `idx_clases_materia` (`materia_id`),
  ADD KEY `idx_clases_estado` (`estado`);

--
-- Indices de la tabla `configuracion`
--
ALTER TABLE `configuracion`
  ADD PRIMARY KEY (`clave`);

--
-- Indices de la tabla `cursos`
--
ALTER TABLE `cursos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_inscripcion` (`alumno_id`,`materia_id`),
  ADD KEY `materia_id` (`materia_id`);

--
-- Indices de la tabla `materias`
--
ALTER TABLE `materias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `profesor_id` (`profesor_id`);

--
-- Indices de la tabla `materia_horarios`
--
ALTER TABLE `materia_horarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_materia_dia` (`materia_id`,`dia_semana`);

--
-- Indices de la tabla `qr_tokens`
--
ALTER TABLE `qr_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `clase_id` (`clase_id`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_expira` (`expira_en`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `legajo` (`legajo`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `asistencias`
--
ALTER TABLE `asistencias`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `clases`
--
ALTER TABLE `clases`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `cursos`
--
ALTER TABLE `cursos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `materias`
--
ALTER TABLE `materias`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `materia_horarios`
--
ALTER TABLE `materia_horarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `qr_tokens`
--
ALTER TABLE `qr_tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `asistencias`
--
ALTER TABLE `asistencias`
  ADD CONSTRAINT `asistencias_ibfk_1` FOREIGN KEY (`alumno_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `asistencias_ibfk_2` FOREIGN KEY (`clase_id`) REFERENCES `clases` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `clases`
--
ALTER TABLE `clases`
  ADD CONSTRAINT `clases_ibfk_1` FOREIGN KEY (`materia_id`) REFERENCES `materias` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  ADD CONSTRAINT `inscripciones_ibfk_1` FOREIGN KEY (`alumno_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inscripciones_ibfk_2` FOREIGN KEY (`materia_id`) REFERENCES `materias` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `materias`
--
ALTER TABLE `materias`
  ADD CONSTRAINT `materias_ibfk_1` FOREIGN KEY (`profesor_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `materia_horarios`
--
ALTER TABLE `materia_horarios`
  ADD CONSTRAINT `materia_horarios_ibfk_1` FOREIGN KEY (`materia_id`) REFERENCES `materias` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `qr_tokens`
--
ALTER TABLE `qr_tokens`
  ADD CONSTRAINT `qr_tokens_ibfk_1` FOREIGN KEY (`clase_id`) REFERENCES `clases` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
