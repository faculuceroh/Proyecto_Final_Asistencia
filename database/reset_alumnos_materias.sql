-- ============================================================
--  Reset completo: borra TODOS los alumnos, TODAS las materias
--  y sus clases (y todo lo que depende de ellas).
--
--  Se borra: usuarios (rol='alumno'), materias, clases,
--            materia_horarios, inscripciones, asistencias,
--            qr_tokens, qr_sesiones.
--  NO se toca: profesores/secretaría/admin, aulas, cursos,
--              configuración.
--
--  Uso: mysql -u root -p asistencia_qr < reset_alumnos_materias.sql
--  (o pegarlo en la pestaña SQL de phpMyAdmin)
-- ============================================================

USE asistencia_qr;

SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM asistencias;
DELETE FROM qr_tokens;
DELETE FROM qr_sesiones;
DELETE FROM materia_horarios;
DELETE FROM inscripciones;
DELETE FROM clases;
DELETE FROM materias;
DELETE FROM usuarios WHERE rol = 'alumno';

ALTER TABLE asistencias      AUTO_INCREMENT = 1;
ALTER TABLE qr_tokens        AUTO_INCREMENT = 1;
ALTER TABLE qr_sesiones      AUTO_INCREMENT = 1;
ALTER TABLE materia_horarios AUTO_INCREMENT = 1;
ALTER TABLE inscripciones    AUTO_INCREMENT = 1;
ALTER TABLE clases           AUTO_INCREMENT = 1;
ALTER TABLE materias         AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;
