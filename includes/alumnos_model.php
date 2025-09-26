<?php
// includes/alumnos_model.php

/**
 * CRUD de Alumnos (con FK a cursos)
 * Esquema esperado:
 *  - cursos(id_curso PK, nombre UNIQUE)
 *  - alumnos(id_alumno PK, rut UNIQUE, nombre, apellidos, id_curso FK->cursos.id_curso, creado_en)
 */

/** Lista todos los alumnos con el nombre del curso (JOIN cursos) */
function alumnos_obtenerTodos(PDO $pdo): array {
    $sql = "SELECT a.id_alumno,
                   a.rut,
                   a.nombre,
                   a.apellidos,
                   a.id_curso,
                   c.nombre AS curso,
                   a.creado_en
            FROM alumnos a
            INNER JOIN cursos c ON a.id_curso = c.id_curso
            ORDER BY a.apellidos, a.nombre";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

/** Obtiene un alumno por ID (incluye nombre del curso) */
function alumnos_obtenerPorId(PDO $pdo, int $id_alumno): ?array {
    $st = $pdo->prepare(
        "SELECT a.id_alumno,
                a.rut,
                a.nombre,
                a.apellidos,
                a.id_curso,
                c.nombre AS curso,
                a.creado_en
         FROM alumnos a
         INNER JOIN cursos c ON a.id_curso = c.id_curso
         WHERE a.id_alumno = :id
         LIMIT 1"
    );
    $st->execute([':id' => $id_alumno]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/** Crea un alumno nuevo */
function alumnos_crear(PDO $pdo, string $rut, string $nombre, string $apellidos, int $id_curso): bool {
    $st = $pdo->prepare(
        "INSERT INTO alumnos (rut, nombre, apellidos, id_curso)
         VALUES (:rut, :nombre, :apellidos, :id_curso)"
    );
    return $st->execute([
        ':rut'       => preg_replace('/\D/', '', $rut), // solo dígitos
        ':nombre'    => $nombre,
        ':apellidos' => $apellidos,
        ':id_curso'  => $id_curso
    ]);
}

/** Actualiza un alumno existente */
function alumnos_actualizar(PDO $pdo, int $id_alumno, string $rut, string $nombre, string $apellidos, int $id_curso): bool {
    $st = $pdo->prepare(
        "UPDATE alumnos
         SET rut = :rut,
             nombre = :nombre,
             apellidos = :apellidos,
             id_curso = :id_curso
         WHERE id_alumno = :id"
    );
    return $st->execute([
        ':rut'       => preg_replace('/\D/', '', $rut),
        ':nombre'    => $nombre,
        ':apellidos' => $apellidos,
        ':id_curso'  => $id_curso,
        ':id'        => $id_alumno
    ]);
}

/** Elimina un alumno por ID */
function alumnos_eliminar(PDO $pdo, int $id_alumno): bool {
    $st = $pdo->prepare("DELETE FROM alumnos WHERE id_alumno = :id");
    return $st->execute([':id' => $id_alumno]);
}

/* =========================
 * Utilidades de Cursos
 * ========================= */

/** Lista todos los cursos (para poblar selects) */
function cursos_obtenerTodos(PDO $pdo): array {
    $sql = "SELECT id_curso, nombre FROM cursos ORDER BY nombre";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

/** Obtiene un curso por ID */
function cursos_obtenerPorId(PDO $pdo, int $id_curso): ?array {
    $st = $pdo->prepare("SELECT id_curso, nombre FROM cursos WHERE id_curso = :id LIMIT 1");
    $st->execute([':id' => $id_curso]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}
