<?php
class Seccion
{
    public static function validar(array $d, bool $esEditar=false): array {
        $err = [];
        $d['curso_id']      = (int)($d['curso_id'] ?? 0);
        $d['asignatura_id'] = (int)($d['asignatura_id'] ?? 0);
        $d['profesor_rut']  = (int)($d['profesor_rut'] ?? 0);

        if ($d['curso_id'] <= 0)      $err['curso_id'] = 'Seleccione un curso';
        if ($d['asignatura_id'] <= 0) $err['asignatura_id'] = 'Seleccione una asignatura';
        if ($d['profesor_rut'] <= 0)  $err['profesor_rut'] = 'Seleccione un profesor';

        return [$d, $err];
    }

    public static function existeUnica(int $cursoId, int $asigId, ?int $excluirId=null): bool {
        $db = Database::get();
        $sql = "SELECT 1 FROM secciones_asignatura WHERE curso_id=:c AND asignatura_id=:a";
        $p = [':c'=>$cursoId, ':a'=>$asigId];
        if ($excluirId) { $sql .= " AND id<>:id"; $p[':id'] = $excluirId; }
        $sql .= " LIMIT 1";
        $st = $db->prepare($sql);
        $st->execute($p);
        return (bool)$st->fetchColumn();
    }

    public static function contar(string $q='', ?int $curso=null, ?int $asig=null, ?int $prof=null): int {
        $db = Database::get();
        $sql = "SELECT COUNT(*) FROM secciones_asignatura s
                JOIN cursos c ON c.id=s.curso_id
                JOIN niveles nv ON nv.id=c.nivel_id
                JOIN asignaturas a ON a.id=s.asignatura_id
                JOIN personas p ON p.rut=s.profesor_rut
                WHERE 1=1";
        $p = [];
        if ($q !== '') {
            $sql .= " AND (a.nombre LIKE :q OR a.codigo LIKE :q OR p.nombres LIKE :q OR p.apellidos LIKE :q
                           OR CONCAT(c.anio,' ',nv.nombre,' ',c.letra) LIKE :q)";
            $p[':q'] = "%$q%";
        }
        if ($curso) { $sql .= " AND c.id=:c"; $p[':c'] = $curso; }
        if ($asig)  { $sql .= " AND a.id=:a"; $p[':a'] = $asig; }
        if ($prof)  { $sql .= " AND p.rut=:pr"; $p[':pr'] = $prof; }
        $st = $db->prepare($sql);
        $st->execute($p);
        return (int)$st->fetchColumn();
    }

    public static function listar(string $q='', int $limit=10, int $offset=0, ?int $curso=null, ?int $asig=null, ?int $prof=null): array {
        $db = Database::get();
        $sql = "SELECT s.id,
                       c.id AS curso_id, c.anio, c.letra, nv.nombre AS nivel_nombre,
                       a.id AS asignatura_id, a.nombre AS asignatura_nombre, a.codigo AS asignatura_codigo,
                       p.rut AS profesor_rut, CONCAT_WS(' ', p.nombres, p.apellidos) AS profesor_nombre
                FROM secciones_asignatura s
                JOIN cursos c ON c.id=s.curso_id
                JOIN niveles nv ON nv.id=c.nivel_id
                JOIN asignaturas a ON a.id=s.asignatura_id
                JOIN personas p ON p.rut=s.profesor_rut
                WHERE 1=1";
        $p = [];
        if ($q !== '') {
            $sql .= " AND (a.nombre LIKE :q OR a.codigo LIKE :q OR p.nombres LIKE :q OR p.apellidos LIKE :q
                           OR CONCAT(c.anio,' ',nv.nombre,' ',c.letra) LIKE :q)";
            $p[':q'] = "%$q%";
        }
        if ($curso) { $sql .= " AND c.id=:c";  $p[':c'] = $curso; }
        if ($asig)  { $sql .= " AND a.id=:a";  $p[':a'] = $asig; }
        if ($prof)  { $sql .= " AND p.rut=:pr"; $p[':pr'] = $prof; }
        $sql .= " ORDER BY c.anio DESC, nv.orden ASC, c.letra ASC, a.nombre ASC
                  LIMIT :lim OFFSET :off";
        $st = $db->prepare($sql);
        foreach ($p as $k=>$v) $st->bindValue($k, $v, is_int($v)?PDO::PARAM_INT:PDO::PARAM_STR);
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public static function obtener(int $id): ?array {
        $db = Database::get();
        $st = $db->prepare("SELECT id, curso_id, asignatura_id, profesor_rut FROM secciones_asignatura WHERE id=:id");
        $st->execute([':id'=>$id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public static function crear(array $d, int $usuarioId): int {
        [$d, $err] = self::validar($d);
        if ($err) throw new InvalidArgumentException(json_encode($err));
        if (self::existeUnica($d['curso_id'], $d['asignatura_id'])) {
            throw new RuntimeException('Ya existe una sección para ese Curso + Asignatura.');
        }
        $db = Database::get();
        $st = $db->prepare("INSERT INTO secciones_asignatura (curso_id, asignatura_id, profesor_rut)
                            VALUES (:c,:a,:p)");
        $st->execute([':c'=>$d['curso_id'], ':a'=>$d['asignatura_id'], ':p'=>$d['profesor_rut']]);
        $id = (int)$db->lastInsertId();
        Audit::log($usuarioId, 'CREAR', 'SECCION', (string)$id, "Sección curso={$d['curso_id']} asig={$d['asignatura_id']}");
        return $id;
    }

    public static function actualizar(int $id, array $d, int $usuarioId): void {
        [$d, $err] = self::validar($d, true);
        if ($err) throw new InvalidArgumentException(json_encode($err));
        if (self::existeUnica($d['curso_id'], $d['asignatura_id'], $id)) {
            throw new RuntimeException('Ya existe una sección para ese Curso + Asignatura.');
        }
        $db = Database::get();
        $st = $db->prepare("UPDATE secciones_asignatura
                            SET curso_id=:c, asignatura_id=:a, profesor_rut=:p
                            WHERE id=:id");
        $st->execute([':c'=>$d['curso_id'], ':a'=>$d['asignatura_id'], ':p'=>$d['profesor_rut'], ':id'=>$id]);
        Audit::log($usuarioId, 'EDITAR', 'SECCION', (string)$id, "Sección editada id=$id");
    }

    public static function eliminar(int $id, int $usuarioId): void {
        $db = Database::get();
        try {
            $db->beginTransaction();
            $db->prepare("DELETE FROM secciones_asignatura WHERE id=:id")->execute([':id'=>$id]);
            $db->commit();
            Audit::log($usuarioId, 'ELIMINAR', 'SECCION', (string)$id, "Sección eliminada id=$id");
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            // Hay FKs (sesiones_clase, asistencias, evaluaciones, calificaciones)
            throw new RuntimeException('No se puede eliminar: la sección está en uso.');
        }
    }

    // --- Catálogos para selects ---
    public static function listaCursos(): array {
        $db = Database::get();
        $sql = "SELECT c.id, c.anio, c.letra, nv.nombre AS nivel
                FROM cursos c
                JOIN niveles nv ON nv.id=c.nivel_id
                ORDER BY c.anio DESC, nv.orden ASC, c.letra ASC";
        return $db->query($sql)->fetchAll();
    }

    public static function listaAsignaturas(): array {
        $db = Database::get();
        $sql = "SELECT a.id, a.nombre, a.codigo, n.nombre AS nivel
                FROM asignaturas a
                JOIN niveles n ON n.id=a.nivel_id
                WHERE a.activo=1
                ORDER BY n.orden ASC, a.nombre ASC";
        return $db->query($sql)->fetchAll();
    }

    public static function listaProfesores(): array {
        $db = Database::get();
        $sql = "SELECT pr.rut, CONCAT_WS(' ', pe.nombres, pe.apellidos) AS nombre
                FROM profesores pr
                JOIN personas pe ON pe.rut = pr.rut
                WHERE pr.activo = 1
                ORDER BY pe.apellidos ASC, pe.nombres ASC";
        return $db->query($sql)->fetchAll();
    }
}
