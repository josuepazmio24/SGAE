<?php
class Evaluacion
{
    public static function tipos(): array {
        return ['PRUEBA','TAREA','EXPOSICION','EXAMEN','OTRO'];
    }

    public static function validar(array $d, bool $esEditar=false): array {
        $err = [];
        $d['seccion_id']  = (int)($d['seccion_id'] ?? 0);
        $d['periodo_id']  = isset($d['periodo_id']) && $d['periodo_id'] !== '' ? (int)$d['periodo_id'] : null;
        $d['nombre']      = trim($d['nombre'] ?? '');
        $d['tipo']        = trim($d['tipo'] ?? 'PRUEBA');
        $d['fecha']       = trim($d['fecha'] ?? '');
        $d['ponderacion'] = (string)($d['ponderacion'] ?? '0');
        $d['publicado']   = isset($d['publicado']) ? (int)$d['publicado'] : 0;

        if ($d['seccion_id'] <= 0)            $err['seccion_id'] = 'Seleccione una sección';
        if ($d['nombre'] === '')              $err['nombre'] = 'Ingrese un nombre';
        if (!in_array($d['tipo'], self::tipos(), true)) $err['tipo'] = 'Tipo inválido';
        if ($d['fecha'] === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $d['fecha'])) $err['fecha'] = 'Fecha inválida (YYYY-MM-DD)';
        if (!is_numeric($d['ponderacion']))   $err['ponderacion'] = 'Ponderación inválida';
        else {
            $pond = (float)$d['ponderacion'];
            if ($pond < 0 || $pond > 100)     $err['ponderacion'] = 'Ponderación 0 a 100';
            $d['ponderacion'] = number_format($pond, 2, '.', '');
        }
        if (!in_array($d['publicado'], [0,1], true)) $d['publicado'] = 0;

        return [$d, $err];
    }

    public static function contar(string $q='', ?int $seccion=null, ?int $periodo=null, ?string $tipo=null, ?string $desde=null, ?string $hasta=null): int {
        $db = Database::get();
        $sql = "SELECT COUNT(*) FROM evaluaciones e
                JOIN secciones_asignatura s ON s.id=e.seccion_id
                JOIN cursos c ON c.id=s.curso_id
                JOIN niveles nv ON nv.id=c.nivel_id
                JOIN asignaturas a ON a.id=s.asignatura_id
                WHERE 1=1";
        $p = [];
        if ($q !== '')     { $sql.=" AND (e.nombre LIKE :q OR a.nombre LIKE :q OR a.codigo LIKE :q)"; $p[':q']="%$q%"; }
        if ($seccion)      { $sql.=" AND e.seccion_id=:sec"; $p[':sec']=$seccion; }
        if ($periodo)      { $sql.=" AND e.periodo_id=:per"; $p[':per']=$periodo; }
        if ($tipo)         { $sql.=" AND e.tipo=:t"; $p[':t']=$tipo; }
        if ($desde)        { $sql.=" AND e.fecha>=:d"; $p[':d']=$desde; }
        if ($hasta)        { $sql.=" AND e.fecha<=:h"; $p[':h']=$hasta; }
        $st = $db->prepare($sql); $st->execute($p);
        return (int)$st->fetchColumn();
    }

    public static function listar(string $q='', int $limit=10, int $offset=0, ?int $seccion=null, ?int $periodo=null, ?string $tipo=null, ?string $desde=null, ?string $hasta=null): array {
        $db = Database::get();
        $sql = "SELECT e.id, e.seccion_id, e.periodo_id, e.nombre, e.tipo, e.fecha, e.ponderacion, e.publicado,
                       c.anio, c.letra, nv.nombre AS nivel_nombre,
                       a.nombre AS asignatura_nombre, a.codigo AS asignatura_codigo,
                       p.nombre AS periodo_nombre
                FROM evaluaciones e
                JOIN secciones_asignatura s ON s.id=e.seccion_id
                JOIN cursos c ON c.id=s.curso_id
                JOIN niveles nv ON nv.id=c.nivel_id
                JOIN asignaturas a ON a.id=s.asignatura_id
                LEFT JOIN (
                    SELECT id, CONCAT(anio,' - ',nombre) AS nombre FROM periodos
                ) p ON p.id=e.periodo_id
                WHERE 1=1";
        $p = [];
        if ($q !== '')     { $sql.=" AND (e.nombre LIKE :q OR a.nombre LIKE :q OR a.codigo LIKE :q)"; $p[':q']="%$q%"; }
        if ($seccion)      { $sql.=" AND e.seccion_id=:sec"; $p[':sec']=$seccion; }
        if ($periodo)      { $sql.=" AND e.periodo_id=:per"; $p[':per']=$periodo; }
        if ($tipo)         { $sql.=" AND e.tipo=:t"; $p[':t']=$tipo; }
        if ($desde)        { $sql.=" AND e.fecha>=:d"; $p[':d']=$desde; }
        if ($hasta)        { $sql.=" AND e.fecha<=:h"; $p[':h']=$hasta; }
        $sql .= " ORDER BY e.fecha DESC, e.id DESC LIMIT :lim OFFSET :off";
        $st = $db->prepare($sql);
        foreach ($p as $k=>$v) $st->bindValue($k, $v, is_int($v)?PDO::PARAM_INT:PDO::PARAM_STR);
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public static function obtener(int $id): ?array {
        $db = Database::get();
        $st = $db->prepare("SELECT id, seccion_id, periodo_id, nombre, tipo, fecha, ponderacion, publicado FROM evaluaciones WHERE id=:id");
        $st->execute([':id'=>$id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public static function crear(array $d, int $usuarioId): int {
        [$d,$err] = self::validar($d);
        if ($err) throw new InvalidArgumentException(json_encode($err));
        $db = Database::get();
        $st = $db->prepare("INSERT INTO evaluaciones (seccion_id, periodo_id, nombre, tipo, fecha, ponderacion, publicado, creado_por)
                            VALUES (:s,:p,:n,:t,:f,:po,:pb,:u)");
        $st->execute([
            ':s'=>$d['seccion_id'],
            ':p'=>$d['periodo_id'],
            ':n'=>$d['nombre'],
            ':t'=>$d['tipo'],
            ':f'=>$d['fecha'],
            ':po'=>$d['ponderacion'],
            ':pb'=>$d['publicado'],
            ':u'=>$usuarioId
        ]);
        $id = (int)$db->lastInsertId();
        Audit::log($usuarioId, 'CREAR', 'EVALUACION', (string)$id, "Eval {$d['nombre']} ({$d['tipo']})");
        return $id;
    }

    public static function actualizar(int $id, array $d, int $usuarioId): void {
        [$d,$err] = self::validar($d, true);
        if ($err) throw new InvalidArgumentException(json_encode($err));
        $db = Database::get();
        $st = $db->prepare("UPDATE evaluaciones
                            SET seccion_id=:s, periodo_id=:p, nombre=:n, tipo=:t, fecha=:f, ponderacion=:po, publicado=:pb
                            WHERE id=:id");
        $st->execute([
            ':s'=>$d['seccion_id'],
            ':p'=>$d['periodo_id'],
            ':n'=>$d['nombre'],
            ':t'=>$d['tipo'],
            ':f'=>$d['fecha'],
            ':po'=>$d['ponderacion'],
            ':pb'=>$d['publicado'],
            ':id'=>$id
        ]);
        Audit::log($usuarioId, 'EDITAR', 'EVALUACION', (string)$id, "Eval editada {$d['nombre']}");
    }

    public static function eliminar(int $id, int $usuarioId): void {
        $db = Database::get();
        try {
            $db->beginTransaction();
            $db->prepare("DELETE FROM evaluaciones WHERE id=:id")->execute([':id'=>$id]);
            $db->commit();
            Audit::log($usuarioId, 'ELIMINAR', 'EVALUACION', (string)$id, "Eval eliminada id=$id");
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            // Tiene FK con calificaciones → CASCADE, pero si cambiaste reglas, mostramos mensaje genérico:
            throw new RuntimeException('No se pudo eliminar la evaluación.');
        }
    }

    // --- Catálogos ---
    public static function listaSecciones(): array {
        $db = Database::get();
        $sql = "SELECT s.id,
                       CONCAT(c.anio,' ',nv.nombre,' ',c.letra,' · ',a.nombre,' (',a.codigo,')') AS label
                FROM secciones_asignatura s
                JOIN cursos c ON c.id=s.curso_id
                JOIN niveles nv ON nv.id=c.nivel_id
                JOIN asignaturas a ON a.id=s.asignatura_id
                ORDER BY c.anio DESC, nv.orden ASC, c.letra ASC, a.nombre ASC";
        return $db->query($sql)->fetchAll();
    }

    public static function listaPeriodos(): array {
        $db = Database::get();
        $sql = "SELECT id, CONCAT(anio,' - ',nombre) AS nombre FROM periodos ORDER BY anio DESC, fecha_inicio ASC";
        return $db->query($sql)->fetchAll();
    }
}
