<?php
class Rol {
    public static function listar(): array {
        return Database::get()->query("SELECT id, nombre, descripcion FROM roles ORDER BY nombre")->fetchAll();
    }
    public static function obtener(int $id): ?array {
        $st=Database::get()->prepare("SELECT id, nombre, descripcion FROM roles WHERE id=:id");
        $st->execute([':id'=>$id]); $r=$st->fetch(); return $r?:null;
    }
    public static function crear(array $d): int {
        $st=Database::get()->prepare("INSERT INTO roles (nombre, descripcion) VALUES (:n,:d)");
        $st->execute([':n'=>trim($d['nombre']??''), ':d'=>trim($d['descripcion']??'')]);
        return (int)Database::get()->lastInsertId();
    }
    public static function actualizar(int $id, array $d): void {
        $st=Database::get()->prepare("UPDATE roles SET nombre=:n, descripcion=:d WHERE id=:id");
        $st->execute([':n'=>trim($d['nombre']??''), ':d'=>trim($d['descripcion']??''), ':id'=>$id]);
    }
    public static function eliminar(int $id): void {
        $db=Database::get(); $db->beginTransaction();
        try{ $db->prepare("DELETE FROM roles WHERE id=:id")->execute([':id'=>$id]); $db->commit(); }
        catch(Throwable $e){ if($db->inTransaction())$db->rollBack(); throw $e; }
    }
    public static function permisos(int $rol_id): array {
        $sql="SELECT p.id, p.codigo, p.descripcion, (rp.permiso_id IS NOT NULL) AS asignado
              FROM permisos p
              LEFT JOIN rol_permiso rp ON rp.permiso_id=p.id AND rp.rol_id=:rid
              ORDER BY p.codigo";
        $st=Database::get()->prepare($sql); $st->execute([':rid'=>$rol_id]); return $st->fetchAll();
    }
    public static function setPermisos(int $rol_id, array $perm_ids): void {
        $db=Database::get(); $db->beginTransaction();
        try{
            $db->prepare("DELETE FROM rol_permiso WHERE rol_id=:r")->execute([':r'=>$rol_id]);
            if ($perm_ids){
                $ins=$db->prepare("INSERT INTO rol_permiso (rol_id, permiso_id) VALUES (:r,:p)");
                foreach ($perm_ids as $pid){ $ins->execute([':r'=>$rol_id,':p'=>(int)$pid]); }
            }
            $db->commit();
        }catch(Throwable $e){ if($db->inTransaction())$db->rollBack(); throw $e; }
    }
}
