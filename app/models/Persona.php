<?php
class Persona {
public static function crear(array $p): int {
$sql = "INSERT INTO personas (rut, dv, nombres, apellidos, email, tipo_persona)
VALUES (:rut, :dv, :nom, :ape, :email, :tipo)";
$db = Database::get();
$stmt = $db->prepare($sql);
$stmt->execute([
':rut' => $p['rut'],
':dv' => $p['dv'],
':nom' => $p['nombres'],
':ape' => $p['apellidos'],
':email'=> $p['email'] ?? null,
':tipo' => $p['tipo_persona'] ?? 'ADMIN',
]);
return (int)$p['rut'];
}


public static function existeRut(int $rut): bool {
$db = Database::get();
$stmt = $db->prepare('SELECT 1 FROM personas WHERE rut = :rut');
$stmt->execute([':rut' => $rut]);
return (bool)$stmt->fetchColumn();
}
}