<?php
class UsuarioRol {
    public static function rolesDeUsuario(int $uid): array {
        $st=Database::get()->prepare("SELECT r.id, r.nombre FROM usuario_rol ur JOIN roles r ON r.id=ur.rol_id WHERE ur.usuario_id=:u");
        $st->execute([':u'=>$uid]); return $st->fetchAll();
    }
    public static function setRoles(int $uid, array $rol_ids): void {
        $db=Database::get(); $db->beginTransaction();
        try{
            $db->prepare("DELETE FROM usuario_rol WHERE usuario_id=:u")->execute([':u'=>$uid]);
            if ($rol_ids){
                $ins=$db->prepare("INSERT INTO usuario_rol (usuario_id, rol_id) VALUES (:u,:r)");
                foreach($rol_ids as $rid){ $ins->execute([':u'=>$uid, ':r'=>(int)$rid]); }
            }
            $db->commit();
        }catch(Throwable $e){ if($db->inTransaction())$db->rollBack(); throw $e; }
    }
}
