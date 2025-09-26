<?php
// includes/usuarios_model.php

function usuarios_obtenerTodos(PDO $pdo): array {
    $sql = "SELECT rut, dv, nombre, correo, rol FROM usuarios ORDER BY nombre";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}
function usuarios_crear(PDO $pdo, string $rut, string $dv, string $nombre, string $correo, string $password, string $rol): bool {
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $st = $pdo->prepare("INSERT INTO usuarios (rut, dv, nombre, correo, password, rol)
                         VALUES (:rut,:dv,:nombre,:correo,:pass,:rol)");
    return $st->execute([
        ':rut'=>$rut, ':dv'=>strtoupper($dv), ':nombre'=>$nombre, ':correo'=>$correo,
        ':pass'=>$hash, ':rol'=>strtolower($rol)
    ]);
}
function usuarios_actualizar(PDO $pdo, string $rut, string $dv, string $nombre, string $correo, ?string $password=null, ?string $rol=null): bool {
    $set = "dv=:dv, nombre=:nombre, correo=:correo";
    $params = [':dv'=>strtoupper($dv), ':nombre'=>$nombre, ':correo'=>$correo, ':rut'=>$rut];
    if ($rol !== null && $rol !== '') { $set .= ", rol=:rol"; $params[':rol'] = strtolower($rol); }
    if ($password !== null && $password !== '') { $set .= ", password=:pass"; $params[':pass'] = password_hash($password, PASSWORD_BCRYPT); }
    $st = $pdo->prepare("UPDATE usuarios SET $set WHERE rut=:rut");
    return $st->execute($params);
}
function usuarios_eliminar(PDO $pdo, string $rut): bool {
    $st = $pdo->prepare("DELETE FROM usuarios WHERE rut=:rut");
    return $st->execute([':rut'=>$rut]);
}
