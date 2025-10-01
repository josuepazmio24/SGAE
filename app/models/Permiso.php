<?php
class Permiso {
    public static function listar(): array {
        return Database::get()->query("SELECT id, codigo, descripcion FROM permisos ORDER BY codigo")->fetchAll();
    }
    public static function obtener(int $id): ?array {
        $st=Database::get()->prepare("SELECT id, codigo, descripcion FROM permisos WHERE id=:id");
        $st->execute([':id'=>$id]); $r=$st->fetch(); return $r?:null;
    }
    public static function crear(array $d): int {
        $st=Database::get()->prepare("INSERT INTO permisos (codigo, descripcion) VALUES (:c,:d)");
        $st->execute([':c'=>trim($d['codigo']??''), ':d'=>trim($d['descripcion']??'')]);
        return (int)Database::get()->lastInsertId();
    }
    public static function actualizar(int $id, array $d): void {
        $st=Database::get()->prepare("UPDATE permisos SET codigo=:c, descripcion=:d WHERE id=:id");
        $st->execute([':c'=>trim($d['codigo']??''), ':d'=>trim($d['descripcion']??''), ':id'=>$id]);
    }
    public static function eliminar(int $id): void {
        $db=Database::get(); $db->beginTransaction();
        try{ $db->prepare("DELETE FROM permisos WHERE id=:id")->execute([':id'=>$id]); $db->commit(); }
        catch(Throwable $e){ if($db->inTransaction())$db->rollBack(); throw $e; }
    }
}
