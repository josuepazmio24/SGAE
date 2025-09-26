<?php
// includes/asignaturas_model.php

// Obtener todas las asignaturas con JOINs
function asignaturas_obtenerTodos(PDO $pdo): array {
    $sql = "SELECT a.id_asignatura, a.nombre AS asignatura,
                   c.nombre AS curso,
                   CONCAT(p.nombre, ' ', p.apellidos) AS profesor
            FROM asignaturas a
            INNER JOIN cursos c ON a.id_curso = c.id_curso
            LEFT JOIN profesores p ON a.id_profesor = p.id_profesor
            ORDER BY a.id_asignatura DESC";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

// Crear asignatura
function asignaturas_crear(PDO $pdo, string $nombre, int $id_curso, ?int $id_profesor=null): bool {
    $st = $pdo->prepare("INSERT INTO asignaturas (nombre, id_curso, id_profesor) VALUES (:n,:c,:p)");
    return $st->execute([
        ':n'=>$nombre,
        ':c'=>$id_curso,
        ':p'=>$id_profesor
    ]);
}

// Obtener una asignatura por ID
function asignaturas_obtenerPorId(PDO $pdo, int $id_asignatura): ?array {
    $st = $pdo->prepare("SELECT * FROM asignaturas WHERE id_asignatura=:id");
    $st->execute([':id'=>$id_asignatura]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

// Actualizar asignatura
function asignaturas_actualizar(PDO $pdo, int $id_asignatura, string $nombre, int $id_curso, ?int $id_profesor=null): bool {
    $st = $pdo->prepare("UPDATE asignaturas
                         SET nombre=:n, id_curso=:c, id_profesor=:p
                         WHERE id_asignatura=:id");
    return $st->execute([
        ':n'=>$nombre,
        ':c'=>$id_curso,
        ':p'=>$id_profesor,
        ':id'=>$id_asignatura
    ]);
}

// Eliminar asignatura
function asignaturas_eliminar(PDO $pdo, int $id_asignatura): bool {
    $st = $pdo->prepare("DELETE FROM asignaturas WHERE id_asignatura=:id");
    return $st->execute([':id'=>$id_asignatura]);
}
