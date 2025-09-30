-- =========================================================
--  SGAE - Esquema Base de Datos (MySQL 8.0+) - Auditoría Opción 2
--  Cambios clave:
--   * auditoria_logs.usuario_id NOT NULL con ON DELETE RESTRICT
--   * sesiones_clase: unicidad usando columna generada bloque_norm (NULL → '')
-- =========================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS auditoria_logs;
DROP TABLE IF EXISTS calificaciones;
DROP TABLE IF EXISTS evaluaciones;
DROP TABLE IF EXISTS asistencias;
DROP TABLE IF EXISTS sesiones_clase;
DROP TABLE IF EXISTS secciones_asignatura;
DROP TABLE IF EXISTS matriculas;
DROP TABLE IF EXISTS alumno_apoderado;
DROP TABLE IF EXISTS apoderados;
DROP TABLE IF EXISTS alumnos;
DROP TABLE IF EXISTS profesores;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS asignaturas;
DROP TABLE IF EXISTS cursos;
DROP TABLE IF EXISTS niveles;
DROP TABLE IF EXISTS periodos;
DROP TABLE IF EXISTS personas;

CREATE TABLE personas (
  rut            INT UNSIGNED NOT NULL,
  dv             CHAR(1)      NOT NULL,
  nombres        VARCHAR(100) NOT NULL,
  apellidos      VARCHAR(100) NOT NULL,
  sexo           ENUM('M','F','X') NULL,
  fecha_nac      DATE NULL,
  email          VARCHAR(120) NULL,
  telefono       VARCHAR(30)  NULL,
  direccion      VARCHAR(180) NULL,
  tipo_persona   ENUM('ALUMNO','PROFESOR','APODERADO','ADMIN') NULL,
  creado_en      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (rut),
  CONSTRAINT uq_personas_email UNIQUE (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE niveles (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(60) NOT NULL,
  descripcion   VARCHAR(200) NULL,
  orden         INT NOT NULL,
  UNIQUE KEY uq_niveles_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE periodos (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  anio          YEAR NOT NULL,
  nombre        VARCHAR(40) NOT NULL,
  fecha_inicio  DATE NOT NULL,
  fecha_fin     DATE NOT NULL,
  CONSTRAINT uq_periodo UNIQUE (anio, nombre),
  CHECK (fecha_fin >= fecha_inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE cursos (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  anio           YEAR NOT NULL,
  nivel_id       INT NOT NULL,
  letra          CHAR(1) NOT NULL,
  jornada        ENUM('MAÑANA','TARDE','COMPLETA') NOT NULL DEFAULT 'MAÑANA',
  jefe_rut_profesor INT UNSIGNED NULL,
  creado_en      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_cursos_nivel
    FOREIGN KEY (nivel_id) REFERENCES niveles(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_cursos_prof_jefe
    FOREIGN KEY (jefe_rut_profesor) REFERENCES personas(rut)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT uq_curso_anio_nivel_letra UNIQUE (anio, nivel_id, letra)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE asignaturas (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(100) NOT NULL,
  codigo        VARCHAR(20)  NOT NULL,
  nivel_id      INT NOT NULL,
  activo        TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_asig_nivel
    FOREIGN KEY (nivel_id) REFERENCES niveles(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT uq_asig_nivel_codigo UNIQUE (nivel_id, codigo),
  CONSTRAINT uq_asig_nivel_nombre UNIQUE (nivel_id, nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE profesores (
  rut              INT UNSIGNED NOT NULL PRIMARY KEY,
  especialidad     VARCHAR(120) NULL,
  fecha_ingreso    DATE NULL,
  activo           TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_prof_rut
    FOREIGN KEY (rut) REFERENCES personas(rut)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE alumnos (
  rut              INT UNSIGNED NOT NULL PRIMARY KEY,
  nro_matricula    VARCHAR(30) NOT NULL,
  fecha_ingreso    DATE NULL,
  activo           TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT uq_alumnos_matricula UNIQUE (nro_matricula),
  CONSTRAINT fk_alum_rut
    FOREIGN KEY (rut) REFERENCES personas(rut)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE apoderados (
  rut              INT UNSIGNED NOT NULL PRIMARY KEY,
  ocupacion        VARCHAR(120) NULL,
  activo           TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_apod_rut
    FOREIGN KEY (rut) REFERENCES personas(rut)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE alumno_apoderado (
  alumno_rut     INT UNSIGNED NOT NULL,
  apoderado_rut  INT UNSIGNED NOT NULL,
  parentesco     VARCHAR(40) NOT NULL,
  es_titular     TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (alumno_rut, apoderado_rut),
  CONSTRAINT fk_alap_alumno
    FOREIGN KEY (alumno_rut) REFERENCES alumnos(rut)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_alap_apoderado
    FOREIGN KEY (apoderado_rut) REFERENCES apoderados(rut)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE usuarios (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  username        VARCHAR(60) NOT NULL,
  password_hash   VARCHAR(255) NOT NULL,
  rol             ENUM('ADMIN','PROFESOR','ALUMNO') NOT NULL,
  rut_persona     INT UNSIGNED NOT NULL,
  estado          ENUM('ACTIVO','SUSPENDIDO') NOT NULL DEFAULT 'ACTIVO',
  ultimo_login    DATETIME NULL,
  creado_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en  TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT uq_usuarios_username UNIQUE (username),
  CONSTRAINT uq_usuarios_rut UNIQUE (rut_persona),
  CONSTRAINT fk_usuarios_persona
    FOREIGN KEY (rut_persona) REFERENCES personas(rut)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX ix_usuarios_rol ON usuarios(rol);

CREATE TABLE matriculas (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  alumno_rut       INT UNSIGNED NOT NULL,
  curso_id         INT NOT NULL,
  fecha_matricula  DATE NOT NULL,
  estado           ENUM('VIGENTE','RETIRADO','EGRESADO') NOT NULL DEFAULT 'VIGENTE',
  CONSTRAINT fk_mat_alumno
    FOREIGN KEY (alumno_rut) REFERENCES alumnos(rut)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_mat_curso
    FOREIGN KEY (curso_id) REFERENCES cursos(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT uq_matricula_anual UNIQUE (alumno_rut, curso_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE secciones_asignatura (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  curso_id        INT NOT NULL,
  asignatura_id   INT NOT NULL,
  profesor_rut    INT UNSIGNED NOT NULL,
  UNIQUE KEY uq_seccion (curso_id, asignatura_id),
  CONSTRAINT fk_secc_curso
    FOREIGN KEY (curso_id) REFERENCES cursos(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_secc_asig
    FOREIGN KEY (asignatura_id) REFERENCES asignaturas(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_secc_prof
    FOREIGN KEY (profesor_rut) REFERENCES profesores(rut)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- sesiones_clase: unicidad con columna generada (NULL → '')
CREATE TABLE sesiones_clase (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  seccion_id      INT NOT NULL,
  fecha           DATE NOT NULL,
  bloque          VARCHAR(20) NULL,
  bloque_norm     VARCHAR(20) AS (IFNULL(bloque,'')) STORED,
  tema            VARCHAR(200) NULL,
  creado_por      INT NOT NULL,
  creado_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ses_seccion
    FOREIGN KEY (seccion_id) REFERENCES secciones_asignatura(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_ses_usuario
    FOREIGN KEY (creado_por) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  UNIQUE KEY uq_sesion_unica (seccion_id, fecha, bloque_norm)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE asistencias (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  alumno_rut      INT UNSIGNED NOT NULL,
  seccion_id      INT NOT NULL,
  fecha           DATE NOT NULL,
  estado          ENUM('PRESENTE','AUSENTE','ATRASO','JUSTIFICADO')
                  NOT NULL DEFAULT 'PRESENTE',
  observacion     VARCHAR(200) NULL,
  registrado_por  INT NOT NULL,
  creado_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_asist_alumno
    FOREIGN KEY (alumno_rut) REFERENCES alumnos(rut)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_asist_seccion
    FOREIGN KEY (seccion_id) REFERENCES secciones_asignatura(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_asist_usuario
    FOREIGN KEY (registrado_por) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT uq_asist_unica UNIQUE (alumno_rut, seccion_id, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX ix_asist_fecha ON asistencias(fecha);
CREATE INDEX ix_asist_estado ON asistencias(estado);

CREATE TABLE evaluaciones (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  seccion_id      INT NOT NULL,
  periodo_id      INT NULL,
  nombre          VARCHAR(120) NOT NULL,
  tipo            ENUM('PRUEBA','TAREA','EXPOSICION','EXAMEN','OTRO') NOT NULL DEFAULT 'PRUEBA',
  fecha           DATE NOT NULL,
  ponderacion     DECIMAL(5,2) NOT NULL DEFAULT 0,
  publicado       TINYINT(1) NOT NULL DEFAULT 0,
  creado_por      INT NOT NULL,
  creado_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_eval_seccion
    FOREIGN KEY (seccion_id) REFERENCES secciones_asignatura(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_eval_periodo
    FOREIGN KEY (periodo_id) REFERENCES periodos(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_eval_usuario
    FOREIGN KEY (creado_por) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX ix_eval_fecha ON evaluaciones(fecha);

CREATE TABLE calificaciones (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  evaluacion_id   INT NOT NULL,
  alumno_rut      INT UNSIGNED NOT NULL,
  nota            DECIMAL(3,1) NOT NULL,
  observacion     VARCHAR(200) NULL,
  registrado_por  INT NOT NULL,
  creado_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_cal_eval
    FOREIGN KEY (evaluacion_id) REFERENCES evaluaciones(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_cal_alumno
    FOREIGN KEY (alumno_rut) REFERENCES alumnos(rut)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_cal_usuario
    FOREIGN KEY (registrado_por) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT uq_cal_unica UNIQUE (evaluacion_id, alumno_rut),
  CHECK (nota >= 1.0 AND nota <= 7.0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX ix_cal_nota ON calificaciones(nota);

-- Opción 2: usuario_id NOT NULL + ON DELETE RESTRICT
CREATE TABLE auditoria_logs (
  id           BIGINT AUTO_INCREMENT PRIMARY KEY,
  usuario_id   INT NOT NULL,
  accion       VARCHAR(40) NOT NULL,
  entidad      VARCHAR(60) NOT NULL,
  entidad_id   VARCHAR(60) NOT NULL,
  descripcion  VARCHAR(255) NULL,
  ip           VARCHAR(45) NULL,
  creado_en    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_aud_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

DROP VIEW IF EXISTS vw_promedios_alumno_asignatura;
CREATE VIEW vw_promedios_alumno_asignatura AS
SELECT
  c.id             AS curso_id,
  sa.id            AS seccion_id,
  a.rut            AS alumno_rut,
  p.nombres        AS alumno_nombres,
  p.apellidos      AS alumno_apellidos,
  asig.nombre      AS asignatura,
  ROUND(
    CASE
      WHEN SUM(CASE WHEN e.ponderacion > 0 THEN e.ponderacion ELSE 0 END) > 0
        THEN SUM(cal.nota * e.ponderacion) / SUM(e.ponderacion)
      ELSE AVG(cal.nota)
    END, 1
  ) AS promedio
FROM calificaciones cal
JOIN evaluaciones e           ON e.id = cal.evaluacion_id
JOIN secciones_asignatura sa  ON sa.id = e.seccion_id
JOIN asignaturas asig         ON asig.id = sa.asignatura_id
JOIN cursos c                 ON c.id = sa.curso_id
JOIN alumnos a                ON a.rut = cal.alumno_rut
JOIN personas p               ON p.rut = a.rut
GROUP BY c.id, sa.id, a.rut, p.nombres, p.apellidos, asig.nombre;

DROP VIEW IF EXISTS vw_asistencia_resumen;
CREATE VIEW vw_asistencia_resumen AS
SELECT
  sa.id                       AS seccion_id,
  a.rut                       AS alumno_rut,
  p.nombres                   AS alumno_nombres,
  p.apellidos                 AS alumno_apellidos,
  COUNT(*)                    AS total_registros,
  SUM(estado='PRESENTE')      AS presentes,
  SUM(estado='AUSENTE')       AS ausentes,
  SUM(estado='ATRASO')        AS atrasos,
  SUM(estado='JUSTIFICADO')   AS justificados,
  ROUND(100.0 * SUM(estado='PRESENTE')/COUNT(*), 1) AS porcentaje_asistencia
FROM asistencias asis
JOIN alumnos a   ON a.rut = asis.alumno_rut
JOIN personas p  ON p.rut = a.rut
JOIN secciones_asignatura sa ON sa.id = asis.seccion_id
GROUP BY sa.id, a.rut, p.nombres, p.apellidos;
