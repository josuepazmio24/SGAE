<?php
class Asignatura
{
    public static function validar(array $d, bool $esEditar = false): array {
        $err = [];
        $d['nombre']    = trim($d['nombre'] ?? '');
        $d['codigo']    = strtoupper(trim($d['codigo'] ?? ''));
        $d['nivel_id']  = (int)($d['nivel_id'] ?? 0);
        $d['activo']    = isset($d['activo']) ? (int)$d['activo'] : 1;

        if ($d['nombre'] === '')  $err['nombre'] = 'Ingrese el nombre';
        if ($d['codigo'] === '')  $err['codigo'] = 'Ingrese el código';
        if ($d['nivel_id'] <= 0)  $err['nivel_id'] = 'Seleccione un nivel';
        if (!in_array($d['activo'], [0,1], true)) $d['activo'] = 1;

        return [$d, $err];
    }

    public static function contar(string $q = '', ?int $nivel = null, ?int $activo = null): int {
        $db = Database::get();
        $sql = "SELECT COUNT(*) FROM asignaturas a
                JOIN niveles n ON n.id=a.nivel_id
                WHERE 1=1";
        $p = [];
        if ($q !== '')      { $sql .= " AND (a.nombre LIKE :q OR a.codigo LIKE :q)"; $p[':q'] = "%$q%"; }
        if ($nivel)         { $sql .= " AND a.nivel_id=:niv"; $p[':niv'] = $nivel; }
        if ($activo !== null){ $sql .= " AND a.activo=:act"; $p[':act'] = $activo; }
        $st = $db->prepare($sql);
        $st->execute($p);
        return (int)$st->fetchColumn();
    }

    public static function listar(string $q = '', int $limit = 10, int $offset = 0, ?int $nivel = null, ?int $activo = null): array {
        $db = Database::get();
        $sql = "SELECT a.id, a.nombre, a.codigo, a.nivel_id, a.activo, n.nombre AS nivel_nombre
                FROM asignaturas a
                JOIN niveles n ON n.id=a.nivel_id
                WHERE 1=1";
        $p = [];
        if ($q !== '')       { $sql .= " AND (a.nombre LIKE :q OR a.codigo LIKE :q)"; $p[':q'] = "%$q%"; }
        if ($nivel)          { $sql .= " AND a.nivel_id=:niv"; $p[':niv'] = $nivel; }
        if ($activo !== null){ $sql .= " AND a.activo=:act"; $p[':act'] = $activo; }
        $sql .= " ORDER BY n.orden ASC, a.nombre ASC LIMIT :lim OFFSET :off";
        $st = $db->prepare($sql);
        foreach ($p as $k=>$v) $st->bindValue($k, $v, is_int($v)?PDO::PARAM_INT:PDO::PARAM_STR);
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public static function obtener(int $id): ?array {
        $db = Database::get();
        $st = $db->prepare("SELECT id, nombre, codigo, nivel_id, activo FROM asignaturas WHERE id=:id");
        $st->execute([':id'=>$id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public static function crear(array $d, int $usuarioId): int {
        [$d, $err] = self::validar($d);
        if ($err) throw new InvalidArgumentException(json_encode($err));
        $db = Database::get();
        try {
            $st = $db->prepare("INSERT INTO asignaturas (nombre, codigo, nivel_id, activo)
                                VALUES (:n,:c,:niv,:a)");
            $st->execute([':n'=>$d['nombre'], ':c'=>$d['codigo'], ':niv'=>$d['nivel_id'], ':a'=>$d['activo']]);
            $id = (int)$db->lastInsertId();
            Audit::log($usuarioId, 'CREAR', 'ASIGNATURA', (string)$id, "Creada {$d['nombre']} ({$d['codigo']})");
            return $id;
        } catch (PDOException $e) {
            if ($e->getCode()==='23000') {
                // colisiones de UNIQUE (nivel_id,codigo) o (nivel_id,nombre)
                throw new RuntimeException('Ya existe una asignatura con ese Código o Nombre en el mismo nivel.');
            }
            throw $e;
        }
    }

    public static function actualizar(int $id, array $d, int $usuarioId): void {
        [$d, $err] = self::validar($d, true);
        if ($err) throw new InvalidArgumentException(json_encode($err));
        $db = Database::get();
        try {
            $st = $db->prepare("UPDATE asignaturas
                                SET nombre=:n, codigo=:c, nivel_id=:niv, activo=:a
                                WHERE id=:id");
            $st->execute([':n'=>$d['nombre'], ':c'=>$d['codigo'], ':niv'=>$d['nivel_id'], ':a'=>$d['activo'], ':id'=>$id]);
            Audit::log($usuarioId, 'EDITAR', 'ASIGNATURA', (string)$id, "Editada {$d['nombre']} ({$d['codigo']})");
        } catch (PDOException $e) {
            if ($e->getCode()==='23000') {
                throw new RuntimeException('Ya existe una asignatura con ese Código o Nombre en el mismo nivel.');
            }
            throw $e;
        }
    }

    public static function eliminar(int $id, int $usuarioId): void {
        $db = Database::get();
        try {
            $db->beginTransaction();
            $db->prepare("DELETE FROM asignaturas WHERE id=:id")->execute([':id'=>$id]);
            $db->commit();
            Audit::log($usuarioId, 'ELIMINAR', 'ASIGNATURA', (string)$id, "Eliminada asignatura $id");
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            // Hay FKs (secciones_asignatura) → RESTRICT
            throw new RuntimeException('No se puede eliminar: la asignatura está en uso.');
        }
    }

    // Catálogo
    public static function listaNiveles(): array {
        $db = Database::get();
        return $db->query("SELECT id, nombre FROM niveles ORDER BY orden ASC, nombre ASC")->fetchAll();
    }
}
