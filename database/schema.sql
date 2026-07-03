-- ============================================================
--  Sistema de Asistencia QR
--  Base de datos MySQL — schema.sql
--  Importar desde phpMyAdmin o con: mysql -u root -p < schema.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS asistencia_qr
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE asistencia_qr;


-- ─────────────────────────────────────────────────────────────
-- 1. CONFIGURACIÓN DEL SISTEMA
--    Parámetros globales editables desde admin/configuracion.php
-- ─────────────────────────────────────────────────────────────
CREATE TABLE configuracion (
  clave VARCHAR(60)  NOT NULL PRIMARY KEY,
  valor VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO configuracion (clave, valor) VALUES
  ('nombre_institucion',    'Instituto Técnico'),
  ('periodo_activo',        '2026-1'),
  ('qr_rotacion_segundos',  '30'),
  ('tolerancia_minutos',    '10'),
  ('notificaciones_activas','1'),
  -- Umbrales para asistencia virtual (importación desde Teams)
  -- porcentaje = minutos_conectado / clases.duracion_min * 100
  ('asistencia_virtual_porcentaje_minimo',   '30'),
  ('asistencia_virtual_porcentaje_tardanza', '70');


-- ─────────────────────────────────────────────────────────────
-- 2. USUARIOS
--    Un solo tabla para todos los roles (alumno, profesor,
--    secretaria, admin). El rol determina a qué panel accede.
--    El campo `curso` solo aplica a alumnos (ej: "1° B").
-- ─────────────────────────────────────────────────────────────
CREATE TABLE usuarios (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  legajo     VARCHAR(20)  NOT NULL UNIQUE,
  nombre     VARCHAR(100) NOT NULL,
  apellido   VARCHAR(100) NOT NULL,
  email      VARCHAR(150) NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,               -- hash bcrypt (password_hash)
  rol        ENUM('alumno','profesor','secretaria','admin') NOT NULL,
  curso      VARCHAR(20)  NULL,                   -- solo alumnos, ej: "1° B"
  foto       VARCHAR(255) NULL,                   -- nombre de archivo en assets/uploads/perfiles/
  activo     TINYINT(1)   NOT NULL DEFAULT 1,
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Usuario admin por defecto (password: admin1234)
INSERT INTO usuarios (legajo, nombre, apellido, email, password, rol) VALUES
  ('90001', 'Admin', 'Sistema', 'admin@instituto.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');


-- ─────────────────────────────────────────────────────────────
-- 3. MATERIAS
--    Cada materia pertenece a un curso y tiene un profesor
--    asignado. Si el profesor se elimina, el campo queda NULL.
-- ─────────────────────────────────────────────────────────────
CREATE TABLE materias (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre      VARCHAR(150) NOT NULL,
  codigo      VARCHAR(30)  NOT NULL UNIQUE,        -- ej: "PROG2-1B"
  curso       VARCHAR(20)  NOT NULL,               -- ej: "1° B"
  modalidad   ENUM('presencial','virtual','hibrida') NOT NULL DEFAULT 'presencial',
  profesor_id INT UNSIGNED NULL,
  activo      TINYINT(1)   NOT NULL DEFAULT 1,
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (profesor_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ─────────────────────────────────────────────────────────────
-- 4. INSCRIPCIONES
--    Relación muchos-a-muchos entre alumnos y materias.
--    Un alumno puede estar en varias materias; una materia
--    tiene varios alumnos.
-- ─────────────────────────────────────────────────────────────
CREATE TABLE inscripciones (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  alumno_id  INT UNSIGNED NOT NULL,
  materia_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_inscripcion (alumno_id, materia_id),
  FOREIGN KEY (alumno_id)  REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ─────────────────────────────────────────────────────────────
-- 5. CLASES
--    Cada fila es una sesión concreta de una materia
--    (el lunes 16/06 a las 10:00 hs, Aula 204, 90 min).
--    Una materia tiene muchas clases a lo largo del período.
-- ─────────────────────────────────────────────────────────────
CREATE TABLE clases (
  id           INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
  materia_id   INT UNSIGNED   NOT NULL,
  fecha        DATE           NOT NULL,
  hora_inicio  TIME           NOT NULL,
  duracion_min SMALLINT UNSIGNED NOT NULL DEFAULT 90,
  aula         VARCHAR(50)    NULL,               -- NULL si es virtual
  modalidad    ENUM('presencial','virtual','hibrida') NOT NULL DEFAULT 'presencial',
  estado       ENUM('pendiente','en_curso','finalizada') NOT NULL DEFAULT 'pendiente',
  created_at   TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE CASCADE,
  INDEX idx_clases_fecha     (fecha),
  INDEX idx_clases_materia   (materia_id),
  INDEX idx_clases_estado    (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ─────────────────────────────────────────────────────────────
-- 6. QR TOKENS
--    Tokens de corta duración generados por api/token.php.
--    Rotan cada N segundos (configuracion.qr_rotacion_segundos).
--    El tipo indica si el QR es de entrada o de salida.
--    Solo el token activo y no expirado es válido.
-- ─────────────────────────────────────────────────────────────
CREATE TABLE qr_tokens (
  id        INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  clase_id  INT UNSIGNED  NOT NULL,
  token     VARCHAR(64)   NOT NULL,
  tipo      ENUM('entrada','salida') NOT NULL DEFAULT 'entrada',
  expira_en DATETIME      NOT NULL,
  activo    TINYINT(1)    NOT NULL DEFAULT 1,
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (clase_id) REFERENCES clases(id) ON DELETE CASCADE,
  INDEX idx_token    (token),
  INDEX idx_expira   (expira_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ─────────────────────────────────────────────────────────────
-- 7. ASISTENCIAS
--    Una fila por alumno por clase. Se crea cuando el alumno
--    escanea el QR de entrada y se actualiza con la salida.
--    - estado "presente"  → escaneó entrada (dentro de la tolerancia)
--    - estado "tardanza"  → escaneó entrada tarde
--    - estado "ausente"   → no escaneó (se inserta al finalizar la clase)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE asistencias (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  alumno_id    INT UNSIGNED NOT NULL,
  clase_id     INT UNSIGNED NOT NULL,
  hora_entrada TIME         NULL,
  hora_salida  TIME         NULL,
  estado       ENUM('presente','tardanza','ausente') NOT NULL DEFAULT 'ausente',
  created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_asistencia (alumno_id, clase_id),
  FOREIGN KEY (alumno_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (clase_id)  REFERENCES clases(id)  ON DELETE CASCADE,
  INDEX idx_asistencias_clase  (clase_id),
  INDEX idx_asistencias_alumno (alumno_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
--  VISTAS ÚTILES (opcionales, simplifican las consultas PHP)
-- ============================================================

-- Porcentaje de asistencia por alumno por materia
CREATE OR REPLACE VIEW v_asistencia_por_materia AS
SELECT
  i.alumno_id,
  u.nombre,
  u.apellido,
  u.legajo,
  u.curso,
  m.id        AS materia_id,
  m.nombre    AS materia,
  COUNT(c.id)                                              AS total_clases,
  SUM(a.estado IN ('presente','tardanza'))                 AS clases_presentes,
  ROUND(SUM(a.estado IN ('presente','tardanza'))
        / NULLIF(COUNT(c.id), 0) * 100, 1)                AS porcentaje
FROM inscripciones i
JOIN usuarios  u ON u.id = i.alumno_id
JOIN materias  m ON m.id = i.materia_id
JOIN clases    c ON c.materia_id = m.id AND c.estado = 'finalizada'
LEFT JOIN asistencias a ON a.alumno_id = i.alumno_id AND a.clase_id = c.id
GROUP BY i.alumno_id, m.id;


-- Alumnos en riesgo (menos del 75% de asistencia)
CREATE OR REPLACE VIEW v_alumnos_en_riesgo AS
SELECT *
FROM v_asistencia_por_materia
WHERE porcentaje < 75 OR porcentaje IS NULL;


-- Resumen de cada clase (para secretaria/exportar.php)
CREATE OR REPLACE VIEW v_resumen_clases AS
SELECT
  c.id         AS clase_id,
  m.nombre     AS materia,
  m.codigo,
  m.curso,
  CONCAT(u.nombre, ' ', u.apellido) AS profesor,
  c.fecha,
  c.hora_inicio,
  c.duracion_min,
  c.aula,
  c.modalidad,
  c.estado,
  COUNT(DISTINCT i.alumno_id)                        AS total_alumnos,
  SUM(a.estado IN ('presente','tardanza'))            AS presentes,
  COUNT(DISTINCT i.alumno_id)
    - COALESCE(SUM(a.estado IN ('presente','tardanza')), 0) AS ausentes,
  ROUND(SUM(a.estado IN ('presente','tardanza'))
        / NULLIF(COUNT(DISTINCT i.alumno_id), 0) * 100, 1) AS porcentaje
FROM clases c
JOIN materias m ON m.id = c.materia_id
LEFT JOIN usuarios u ON u.id = m.profesor_id
LEFT JOIN inscripciones i ON i.materia_id = m.id
LEFT JOIN asistencias a ON a.clase_id = c.id AND a.alumno_id = i.alumno_id
GROUP BY c.id;
