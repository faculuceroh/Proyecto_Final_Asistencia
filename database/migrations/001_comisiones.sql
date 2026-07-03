-- ── MIGRACIÓN A MODELO DE COMISIONES ──
-- Ejecutar en phpMyAdmin o consola MySQL de asistencia_qr

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Crear la tabla de comisiones
CREATE TABLE IF NOT EXISTS `comisiones` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `materia_id` int(10) unsigned NOT NULL,
  `periodo_lectivo` varchar(30) NOT NULL,
  `curso` varchar(20) NOT NULL,
  `turno` enum('mañana','tarde','noche') NOT NULL DEFAULT 'noche',
  `modalidad` enum('presencial','virtual','hibrida') NOT NULL DEFAULT 'presencial',
  `profesor_id` int(10) unsigned DEFAULT NULL,
  `profesor_2_id` int(10) unsigned DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_comision_periodo` (`materia_id`, `periodo_lectivo`, `curso`),
  CONSTRAINT `fk_comisiones_materia` FOREIGN KEY (`materia_id`) REFERENCES `materias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comisiones_profesor` FOREIGN KEY (`profesor_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_comisiones_profesor2` FOREIGN KEY (`profesor_2_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Migrar los datos actuales de materias a comisiones
-- Mantenemos el mismo ID para no tener que reasignar registros de otras tablas
INSERT INTO `comisiones` (`id`, `materia_id`, `periodo_lectivo`, `curso`, `turno`, `modalidad`, `profesor_id`, `profesor_2_id`, `activo`, `created_at`)
SELECT 
  `id`, 
  `id` AS `materia_id`, 
  '2026-1' AS `periodo_lectivo`, 
  `curso`, 
  IF(`curso` LIKE 'N%', 'noche', IF(`curso` LIKE 'T%', 'tarde', 'mañana')) AS `turno`, 
  `modalidad`, 
  `profesor_id`, 
  `profesor_2_id`, 
  `activo`, 
  `created_at`
FROM `materias`
ON DUPLICATE KEY UPDATE `materia_id` = `materia_id`;

-- 3. Actualizar la tabla clases
ALTER TABLE `clases` DROP FOREIGN KEY `clases_ibfk_1`;
ALTER TABLE `clases` CHANGE COLUMN `materia_id` `comision_id` int(10) unsigned NOT NULL;
ALTER TABLE `clases` ADD CONSTRAINT `fk_clases_comision` FOREIGN KEY (`comision_id`) REFERENCES `comisiones` (`id`) ON DELETE CASCADE;

-- 4. Actualizar la tabla inscripciones
-- Dropeamos primero ambas FKs para que MySQL permita borrar el índice único uq_inscripcion sin dependencias
ALTER TABLE `inscripciones` DROP FOREIGN KEY `inscripciones_ibfk_1`;
ALTER TABLE `inscripciones` DROP FOREIGN KEY `inscripciones_ibfk_2`;
ALTER TABLE `inscripciones` DROP INDEX `uq_inscripcion`;
ALTER TABLE `inscripciones` CHANGE COLUMN `materia_id` `comision_id` int(10) unsigned NOT NULL;
ALTER TABLE `inscripciones` ADD CONSTRAINT `inscripciones_ibfk_1` FOREIGN KEY (`alumno_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
ALTER TABLE `inscripciones` ADD CONSTRAINT `fk_inscripciones_comision` FOREIGN KEY (`comision_id`) REFERENCES `comisiones` (`id`) ON DELETE CASCADE;
ALTER TABLE `inscripciones` ADD UNIQUE KEY `uq_inscripcion_comision` (`alumno_id`, `comision_id`);

-- 5. Renombrar y actualizar la tabla de horarios
RENAME TABLE `materia_horarios` TO `comision_horarios`;
ALTER TABLE `comision_horarios` DROP FOREIGN KEY `materia_horarios_ibfk_1`;
ALTER TABLE `comision_horarios` DROP INDEX `uq_materia_dia`;
ALTER TABLE `comision_horarios` CHANGE COLUMN `materia_id` `comision_id` int(10) unsigned NOT NULL;
ALTER TABLE `comision_horarios` ADD CONSTRAINT `fk_horarios_comision` FOREIGN KEY (`comision_id`) REFERENCES `comisiones` (`id`) ON DELETE CASCADE;
ALTER TABLE `comision_horarios` ADD UNIQUE KEY `uq_comision_dia` (`comision_id`, `dia_semana`);

-- 6. Eliminar columnas que pasaron a comisiones de la tabla materias
ALTER TABLE `materias` DROP FOREIGN KEY `materias_ibfk_1`;
ALTER TABLE `materias` DROP COLUMN `curso`;
ALTER TABLE `materias` DROP COLUMN `modalidad`;
ALTER TABLE `materias` DROP COLUMN `profesor_id`;
ALTER TABLE `materias` DROP COLUMN `profesor_2_id`;

SET FOREIGN_KEY_CHECKS = 1;
