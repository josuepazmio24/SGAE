<?php
class Usuario
{
    public static function total(): int {
        $db = Database::get();
        return (int)$db->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
    }

    public static function buscarPorUsername(string $username): ?array {
        $db = Database::get();
        $st = $db->prepare('SELECT id, username, password_hash, rol, rut_persona, estado FROM usuarios WHERE username=:u LIMIT 1');
        $st->execute([':u'=>$username]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public static function obtener(int $id): ?array {
        $db = Database::get();
        $st = $db->prepare('SELECT id, username, rol, rut_persona, estado, ultimo_login FROM usuarios WHERE id=:id');
        $st->execute([':id'=>$id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    /** ✅ Alias requerido por tus controladores (fix Intelephense) */
    public static function obtenerPorId(int $id): ?array {
        return self::obtener($id);
    }

    public static function contar(string $q='', ?string $rol=null, ?string $estado=null): int {
        $db = Database::get();
        $sql = "SELECT COUNT(*) FROM usuarios u
                JOIN personas p ON p.rut=u.rut_persona
                WHERE 1=1";
        $p = [];
        if ($q !== '')   { $sql.=" AND (u.username LIKE :q OR p.nombres LIKE :q OR p.apellidos LIKE :q OR p.email LIKE :q)"; $p[':q']="%$q%"; }
        if ($rol)        { $sql.=" AND u.rol=:r"; $p[':r']=$rol; }
        if ($estado)     { $sql.=" AND u.estado=:e"; $p[':e']=$estado; }
        $st = $db->prepare($sql); $st->execute($p);
        return (int)$st->fetchColumn();
    }

    public static function listar(string $q='', int $limit=10, int $offset=0, ?string $rol=null, ?string $estado=null): array {
        $db = Database::get();
        $sql = "SELECT u.id, u.username, u.rol, u.estado, u.rut_persona, u.ultimo_login,
                       CONCAT_WS(' ', p.nombres, p.apellidos) AS persona_nombre, p.email
                FROM usuarios u
                JOIN personas p ON p.rut=u.rut_persona
                WHERE 1=1";
        $p = [];
        if ($q !== '')   { $sql.=" AND (u.username LIKE :q OR p.nombres LIKE :q OR p.apellidos LIKE :q OR p.email LIKE :q)"; $p[':q']="%$q%"; }
        if ($rol)        { $sql.=" AND u.rol=:r"; $p[':r']=$rol; }
        if ($estado)     { $sql.=" AND u.estado=:e"; $p[':e']=$estado; }
        $sql .= " ORDER BY u.id DESC LIMIT :lim OFFSET :off";
        $st = $db->prepare($sql);
        foreach ($p as $k=>$v) $st->bindValue($k, $v, PDO::PARAM_STR);
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public static function crear(array $d): int {
        $err = [];
        $username    = trim($d['username'] ?? '');
        $password    = (string)($d['password'] ?? '');
        $rol         = trim($d['rol'] ?? '');
        $rut_persona = (int)($d['rut_persona'] ?? 0);
        $estado      = trim($d['estado'] ?? 'ACTIVO');

        if ($username==='')               $err['username']='Ingrese username';
        if (strlen($password)<4)          $err['password']='Mínimo 4 caracteres';
        if (!in_array($rol,['ADMIN','PROFESOR','ALUMNO'],true)) $err['rol']='Rol inválido';
        if ($rut_persona<=0)              $err['rut_persona']='Seleccione persona';
        if (!in_array($estado,['ACTIVO','SUSPENDIDO'],true)) $err['estado']='Estado inválido';
        if ($err) throw new InvalidArgumentException(json_encode($err));

        $db = Database::get();
        $st = $db->prepare('SELECT 1 FROM personas WHERE rut=:rut');
        $st->execute([':rut'=>$rut_persona]);
        if (!$st->fetchColumn()) throw new RuntimeException('La persona no existe.');
        $st = $db->prepare('SELECT 1 FROM usuarios WHERE rut_persona=:rut LIMIT 1');
        $st->execute([':rut'=>$rut_persona]);
        if ($st->fetchColumn()) throw new RuntimeException('Esa persona ya tiene usuario.');

        try {
            $st = $db->prepare("INSERT INTO usuarios (username, password_hash, rol, rut_persona, estado)
                                VALUES (:u, :h, :r, :rut, :e)");
            $st->execute([
                ':u'=>$username,
                ':h'=>password_hash($password, PASSWORD_BCRYPT),
                ':r'=>$rol,
                ':rut'=>$rut_persona,
                ':e'=>$estado
            ]);
        } catch (PDOException $e) {
            if ((int)$e->errorInfo[1]===1062) {
                if (str_contains($e->getMessage(),'uq_usuarios_username')) throw new RuntimeException('El username ya existe.');
                if (str_contains($e->getMessage(),'uq_usuarios_rut'))      throw new RuntimeException('La persona ya tiene usuario.');
            }
            throw $e;
        }

        $id = (int)$db->lastInsertId();
        Audit::log($_SESSION['user']['id'] ?? 0, 'CREAR', 'USUARIO', (string)$id, 'Usuario creado');
        return $id;
    }

    public static function actualizar(int $id, array $d): void {
        $err = [];
        $username    = trim($d['username'] ?? '');
        $rol         = trim($d['rol'] ?? '');
        $rut_persona = (int)($d['rut_persona'] ?? 0);
        $estado      = trim($d['estado'] ?? 'ACTIVO');

        if ($username==='')               $err['username']='Ingrese username';
        if (!in_array($rol,['ADMIN','PROFESOR','ALUMNO'],true)) $err['rol']='Rol inválido';
        if ($rut_persona<=0)              $err['rut_persona']='Seleccione persona';
        if (!in_array($estado,['ACTIVO','SUSPENDIDO'],true)) $err['estado']='Estado inválido';
        if ($err) throw new InvalidArgumentException(json_encode($err));

        $db = Database::get();
        try {
            $st = $db->prepare("UPDATE usuarios
                                SET username=:u, rol=:r, rut_persona=:rut, estado=:e
                                WHERE id=:id");
            $st->execute([
                ':u'=>$username, ':r'=>$rol, ':rut'=>$rut_persona, ':e'=>$estado, ':id'=>$id
            ]);
        } catch (PDOException $e) {
            if ((int)$e->errorInfo[1]===1062) {
                if (str_contains($e->getMessage(),'uq_usuarios_username')) throw new RuntimeException('El username ya existe.');
                if (str_contains($e->getMessage(),'uq_usuarios_rut'))      throw new RuntimeException('La persona ya tiene usuario.');
            }
            throw $e;
        }
        Audit::log($_SESSION['user']['id'] ?? 0, 'EDITAR', 'USUARIO', (string)$id, 'Usuario editado');
    }

    public static function actualizarPassword(int $id, string $password): void {
        if (strlen($password) < 4) throw new InvalidArgumentException(json_encode(['password'=>'Mínimo 4 caracteres']));
        $db = Database::get();
        $st = $db->prepare("UPDATE usuarios SET password_hash=:h WHERE id=:id");
        $st->execute([':h'=>password_hash($password, PASSWORD_BCRYPT), ':id'=>$id]);
        Audit::log($_SESSION['user']['id'] ?? 0, 'EDITAR', 'USUARIO', (string)$id, 'Cambio de contraseña');
    }

    public static function eliminar(int $id): void {
        $db = Database::get();
        try {
            $db->beginTransaction();
            $db->prepare("DELETE FROM usuarios WHERE id=:id")->execute([':id'=>$id]);
            $db->commit();
            Audit::log($_SESSION['user']['id'] ?? 0, 'ELIMINAR', 'USUARIO', (string)$id, 'Usuario eliminado');
        } catch (PDOException $e) {
            if ($db->inTransaction()) $db->rollBack();
            // auditoria_logs.usuario_id ON DELETE RESTRICT puede bloquear:
            throw new RuntimeException('No se pudo eliminar (existen auditorías asociadas).');
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    public static function personasSinUsuario(): array {
        $db = Database::get();
        $sql = "SELECT p.rut, CONCAT_WS(' ', p.nombres, p.apellidos) AS nombre, p.email
                FROM personas p
                LEFT JOIN usuarios u ON u.rut_persona = p.rut
                WHERE u.id IS NULL
                ORDER BY p.apellidos ASC, p.nombres ASC";
        return $db->query($sql)->fetchAll();
    }

    public static function personasTodas(): array {
        $db = Database::get();
        return $db->query("SELECT rut, CONCAT_WS(' ',nombres,apellidos) AS nombre, email
                           FROM personas ORDER BY apellidos ASC, nombres ASC")->fetchAll();
    }

    public static function roles(): array { return ['ADMIN','PROFESOR','ALUMNO']; }
    public static function estados(): array { return ['ACTIVO','SUSPENDIDO']; }

    /** 🔹 Opcional: marcar último login */
    public static function marcarUltimoLogin(int $id): void {
        $db = Database::get();
        $db->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = :id")->execute([':id'=>$id]);
    }
}
