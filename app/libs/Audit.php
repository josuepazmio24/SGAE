<?php
class Audit {
public static function log(int $usuarioId, string $accion, string $entidad, string $entidadId, ?string $descripcion = null): void {
// Tabla: auditoria_logs (usuario_id NOT NULL, ON DELETE RESTRICT)
$sql = "INSERT INTO auditoria_logs (usuario_id, accion, entidad, entidad_id, descripcion, ip) VALUES
(:uid, :acc, :ent, :eid, :desc, :ip)";
$ip = $_SERVER['REMOTE_ADDR'] ?? null;
$db = Database::get();
$stmt = $db->prepare($sql);
$stmt->execute([
':uid' => $usuarioId,
':acc' => $accion,
':ent' => $entidad,
':eid' => $entidadId,
':desc' => $descripcion,
':ip' => $ip,
]);
}
}