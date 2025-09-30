<?php
class Usuario {
public static function total(): int {
$db = Database::get();
return (int)$db->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
}


public static function crearAdmin(array $u): int {
$db = Database::get();
$db->beginTransaction();
try {
if (!Persona::existeRut((int)$u['rut'])) {
Persona::crear([
'rut' => (int)$u['rut'],
'dv' => $u['dv'],
'nombres' => $u['nombres'],
'apellidos'=> $u['apellidos'],
'email' => $u['email'] ?? null,
'tipo_persona' => 'ADMIN',
]);
}
$sql = "INSERT INTO usuarios (username, password_hash, rol, rut_persona, estado)
VALUES (:user, :hash, 'ADMIN', :rut, 'ACTIVO')";
$stmt = $db->prepare($sql);
$stmt->execute([
':user' => $u['username'],
':hash' => password_hash($u['password'], PASSWORD_BCRYPT),
':rut' => (int)$u['rut'],
]);
$id = (int)$db->lastInsertId();
$db->commit();
Audit::log($id, 'CREAR', 'USUARIO', (string)$id, 'Setup inicial ADMIN');
return $id;
} catch (Throwable $e) {
$db->rollBack();
throw $e;
}
}


public static function buscarPorUsername(string $username): ?array {
$db = Database::get();
$stmt = $db->prepare('SELECT id, username, password_hash, rol, rut_persona, estado FROM usuarios WHERE username = :u LIMIT 1');
$stmt->execute([':u' => $username]);
$row = $stmt->fetch();
return $row ?: null;
}


public static function obtenerPorId(int $id): ?array {
$db = Database::get();
$stmt = $db->prepare('SELECT id, username, rol, rut_persona FROM usuarios WHERE id = :id');
$stmt->execute([':id' => $id]);
$row = $stmt->fetch();
return $row ?: null;
}
}