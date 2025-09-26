<?php
// includes/profesores_model.php

/**
 * Listado de profesores (orden por apellidos, nombre)
 */
function profesores_obtenerTodos(PDO $pdo): array {
    $sql = "SELECT id_profesor, rut, nombre, apellidos, especialidad, creado_en
            FROM profesores
            ORDER BY apellidos, nombre";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Crear profesor
 */
function profesores_crear(PDO $pdo, string $rut, string $nombre, string $apellidos, ?string $especialidad = null): bool {
    $st = $pdo->prepare("INSERT INTO profesores (rut, nombre, apellidos, especialidad)
                         VALUES (:rut,:nombre,:apellidos,:especialidad)");
    return $st->execute([
        ':rut'          => preg_replace('/\D/','', $rut),
        ':nombre'       => $nombre,
        ':apellidos'    => $apellidos,
        ':especialidad' => ($especialidad === '') ? null : $especialidad,
    ]);
}

/**
 * Actualizar profesor
 */
function profesores_actualizar(PDO $pdo, int $id_profesor, string $rut, string $nombre, string $apellidos, ?string $especialidad = null): bool {
    $st = $pdo->prepare("UPDATE profesores
                         SET rut=:rut, nombre=:nombre, apellidos=:apellidos, especialidad=:especialidad
                         WHERE id_profesor=:id");
    return $st->execute([
        ':rut'          => preg_replace('/\D/','', $rut),
        ':nombre'       => $nombre,
        ':apellidos'    => $apellidos,
        ':especialidad' => ($especialidad === '') ? null : $especialidad,
        ':id'           => $id_profesor,
    ]);
}

/**
 * Eliminar profesor
 */
function profesores_eliminar(PDO $pdo, int $id_profesor): bool {
    $st = $pdo->prepare("DELETE FROM profesores WHERE id_profesor=:id");
    return $st->execute([':id'=>$id_profesor]);
}
