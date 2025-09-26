
CREATE TABLE `usuarios` (
  `rut` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `dv` char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `correo` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `rol` enum('admin','docente','alumno','apoderado') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'docente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`rut`, `dv`, `nombre`, `correo`, `password`, `rol`) VALUES
('17451229', '9', 'carla', 'carlesflor@gmail.com', '$2y$10$S4Ysak..8m4AyBY.V2jJy.2hCk7AiefDL3DTiY3pUzEXus0F02IlC', 'docente'),
('18804911', '7', 'josue', 'josuepazmio@gmail.com', '$2y$10$d3rVcqUrNh76.jBnSTuxKeLm4WJUP0WtMkiC4fzXp90B8mLoFpUhK', 'admin');


-- Tabla: cursos
CREATE TABLE cursos (
    id_curso INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE
);

-- Tabla: alumnos
CREATE TABLE alumnos (
    id_alumno INT AUTO_INCREMENT PRIMARY KEY,
    rut VARCHAR(12) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(150) NOT NULL,
    id_curso INT NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_curso) REFERENCES cursos(id_curso)
);

-- Tabla: profesores
CREATE TABLE profesores (
    id_profesor INT AUTO_INCREMENT PRIMARY KEY,
    rut VARCHAR(12) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(150) NOT NULL,
    especialidad VARCHAR(100),
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla: asignaturas
CREATE TABLE asignaturas (
    id_asignatura INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    id_curso INT NOT NULL,
    id_profesor INT,
    FOREIGN KEY (id_profesor) REFERENCES profesores(id_profesor),
    FOREIGN KEY (id_curso) REFERENCES cursos(id_curso)
);

-- Tabla: asistencias
CREATE TABLE asistencias (
    id_asistencia INT AUTO_INCREMENT PRIMARY KEY,
    id_alumno INT NOT NULL,
    id_asignatura INT NOT NULL,
    fecha DATE NOT NULL,
    estado ENUM('presente','ausente','atraso') NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_alumno) REFERENCES alumnos(id_alumno),
    FOREIGN KEY (id_asignatura) REFERENCES asignaturas(id_asignatura),
    UNIQUE (id_alumno, id_asignatura, fecha) -- evita duplicados
);

-- Tabla: notas
CREATE TABLE notas (
    id_nota INT AUTO_INCREMENT PRIMARY KEY,
    id_alumno INT NOT NULL,
    id_asignatura INT NOT NULL,
    nota DECIMAL(3,1) CHECK (nota >= 1.0 AND nota <= 7.0),
    fecha DATE NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_alumno) REFERENCES alumnos(id_alumno),
    FOREIGN KEY (id_asignatura) REFERENCES asignaturas(id_asignatura)
);
<<<<<<< HEAD


-- Tabla de permisos atómicos (recurso + acción)
CREATE TABLE IF NOT EXISTS permisos (
  id_permiso INT AUTO_INCREMENT PRIMARY KEY,
  recurso    VARCHAR(64) NOT NULL,
  accion     VARCHAR(32) NOT NULL,
  etiqueta   VARCHAR(128) NOT NULL, -- texto visible (ej: "Usuarios: ver")
  UNIQUE KEY uq_perm (recurso, accion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Mapeo rol -> permiso
CREATE TABLE IF NOT EXISTS rol_permiso (
  id_rol_permiso INT AUTO_INCREMENT PRIMARY KEY,
  rol        VARCHAR(32) NOT NULL,            -- ej: admin, docente, alumno, apoderado
  id_permiso INT NOT NULL,
  UNIQUE KEY uq_rol_perm (rol, id_permiso),
  CONSTRAINT fk_rolperm_perm FOREIGN KEY (id_permiso) REFERENCES permisos(id_permiso) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- (Opcional) semillas de permisos comunes
INSERT IGNORE INTO permisos (recurso,accion,etiqueta) VALUES
('dashboard','view','Dashboard: ver'),
('usuarios','view','Usuarios: ver'),
('usuarios','manage','Usuarios: crear/editar/eliminar'),
('alumnos','view','Alumnos: ver'),
('alumnos','manage','Alumnos: crear/editar/eliminar'),
('cursos','view','Cursos: ver'),
('cursos','manage','Cursos: crear/editar/eliminar'),
('profesores','view','Profesores: ver'),
('profesores','manage','Profesores: crear/editar/eliminar'),
('asignaturas','view','Asignaturas: ver'),
('asignaturas','manage','Asignaturas: crear/editar/eliminar'),
('config','view','Configuración: ver'),
('config','manage','Configuración: modificar');
=======

