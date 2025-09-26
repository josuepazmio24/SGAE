-- Base de datos y tabla
CREATE DATABASE IF NOT EXISTS login_rut CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE login_rut;

DROP TABLE IF EXISTS usuarios;
CREATE TABLE usuarios (
    rut VARCHAR(10) NOT NULL,        -- solo números, sin guion
    dv CHAR(1) NOT NULL,             -- dígito verificador
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,  -- password_hash
    PRIMARY KEY (rut, dv)
);

-- Usuario de prueba (clave: 123456)
INSERT INTO usuarios (rut, dv, nombre, correo, password) VALUES
('12345678','5','Juan Pérez','juan@example.com',
'$2y$10$V4mK9NPIv1U4TwIURi3E2eZQikj4tLyt3.CPbEpQYw2uLVcFsb5wa');
