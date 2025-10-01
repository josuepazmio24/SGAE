<?php
class Asistencia
{
    public static function estados(): array {
        return ['PRESENTE','AUSENTE','ATRASO','JUSTIFICADO'];
    }

    public static function validar(array $d, bool $esEditar=false): array {
        $err = [];
        $d['alumno_rut'] = (int)($d['alumno_rut'] ?? 0);
        $d['seccion_id'] = (int)($d['seccion_id'] ?? 0);
        $d['fecha']      = trim($d['fecha'] ?? '');
        $d['estado']     = trim($d['estado'] ?? 'PRESENTE');
        $d['observacion']= trim($d['observacion'] ?? '');

        if ($d['alumno_rut'] <= 0) $err['alumno_rut'] = 'Seleccione un alumno';
        if ($d['seccion_id'] <= 0) $err['seccion_id'] = 'Seleccione una sección';
        if ($d['fecha'] === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $d['fecha'])) {
            $err['fecha'] = 'Fecha inválida (YYYY-MM-DD)';
        }
        if (!in_array($d['estado'], self::estados(), true)) {
            $err['estado'] = 'Estado inválido';
        }
        if (strlen($d['observacion']) > 200) {
            $err['observacion'] = 'Observación máx. 200 caracteres';
        }
        return [$d, $err];
    }

    public static function existeUnica(int $rut, int $seccionId, string $fecha, ?int $excluirId=null): bool {
        $db = Database::get();
        $sql = "SELECT 1 FROM asistencias
                WHERE alumno_rut=:r AND seccion_id=:s AND fecha=:f";
        $p = [':r'=>$rut, ':s'=>$seccionId, ':f'=>$fecha];
        if ($excluirId) { $sql .= " AND id<>:id"; $p[':id']=$excluirId; }
        $sql .= " LIMIT 1";
        $st = $db->prepare($sql);
        $st->execute($p);
        return (bool)$st->fetchColumn();
    }

    public static function contar(string $q='', ?int $seccion=null, ?string $desde=null, ?string $hasta=null, ?string $estado=null): int {
        $db = Database::get();
        $sql = "SELECT COUNT(*) FROM asistencias a
                JOIN alumnos al ON al.rut=a.alumno_rut
                JOIN personas p ON p.rut=al.rut
                JOIN secciones_asignatura s ON s.id=a.seccion_id
                JOIN cursos c ON c.id=s.curso_id
                JOIN niveles nv ON nv.id=c.nivel_id
                JOIN asignaturas asg ON asg.id=s.asignatura_id
                WHERE 1=1";
        $p = [];
        if ($q !== '')   { $sql .= " AND (p.nombres LIKE :q OR p.apellidos LIKE :q)"; $p[':q']="%$q%"; }
        if ($seccion)    { $sql .= " AND a.seccion_id=:sec"; $p[':sec']=$seccion; }
        if ($desde)      { $sql .= " AND a.fecha>=:d"; $p[':d']=$desde; }
        if ($hasta)      { $sql .= " AND a.fecha<=:h"; $p[':h']=$hasta; }
        if ($estado)     { $sql .= " AND a.estado=:e"; $p[':e']=$estado; }
        $st = $db->prepare($sql);
        $st->execute($p);
        return (int)$st->fetchColumn();
    }

    public static function listar(string $q='', int $limit=10, int $offset=0, ?int $seccion=null, ?string $desde=null, ?string $hasta=null, ?string $estado=null): array {
        $db = Database::get();
        $sql = "SELECT a.id, a.alumno_rut, a.seccion_id, a.fecha, a.estado, a.observacion, a.creado_en,
                       CONCAT_WS(' ', p.nombres, p.apellidos) AS alumno_nombre,
                       c.anio, c.letra, nv.nombre AS nivel_nombre,
                       asg.nombre AS asignatura_nombre, asg.codigo AS asignatura_codigo
                FROM asistencias a
                JOIN alumnos al ON al.rut=a.alumno_rut
                JOIN personas p ON p.rut=al.rut
                JOIN secciones_asignatura s ON s.id=a.seccion_id
                JOIN cursos c ON c.id=s.curso_id
                JOIN niveles nv ON nv.id=c.nivel_id
                JOIN asignaturas asg ON asg.id=s.asignatura_id
                WHERE 1=1";
        $p = [];
        if ($q !== '')   { $sql .= " AND (p.nombres LIKE :q OR p.apellidos LIKE :q)"; $p[':q']="%$q%"; }
        if ($seccion)    { $sql .= " AND a.seccion_id=:sec"; $p[':sec']=$seccion; }
        if ($desde)      { $sql .= " AND a.fecha>=:d"; $p[':d']=$desde; }
        if ($hasta)      { $sql .= " AND a.fecha<=:h"; $p[':h']=$hasta; }
        if ($estado)     { $sql .= " AND a.estado=:e"; $p[':e']=$estado; }
        $sql .= " ORDER BY a.fecha DESC, alumno_nombre ASC
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
        $st = $db->prepare("SELECT id, alumno_rut, seccion_id, fecha, estado, observacion FROM asistencias WHERE id=:id");
        $st->execute([':id'=>$id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public static function crear(array $d, int $usuarioId): int {
        [$d,$err] = self::validar($d);
        if ($err) throw new InvalidArgumentException(json_encode($err));
        if (self::existeUnica($d['alumno_rut'], $d['seccion_id'], $d['fecha'])) {
            throw new RuntimeException('Ya existe asistencia para ese alumno en esa sección y fecha.');
        }
        $db = Database::get();
        $st = $db->prepare("INSERT INTO asistencias (alumno_rut, seccion_id, fecha, estado, observacion, registrado_por)
                            VALUES (:r,:s,:f,:e,:o,:u)");
        $st->execute([
            ':r'=>$d['alumno_rut'], ':s'=>$d['seccion_id'], ':f'=>$d['fecha'],
            ':e'=>$d['estado'], ':o'=>($d['observacion']!==''?$d['observacion']:null),
            ':u'=>$usuarioId
        ]);
        $id = (int)$db->lastInsertId();
        Audit::log($usuarioId, 'CREAR', 'ASISTENCIA', (string)$id, "A {$d['fecha']} {$d['estado']}");
        return $id;
    }

    public static function actualizar(int $id, array $d, int $usuarioId): void {
        [$d,$err] = self::validar($d, true);
        if ($err) throw new InvalidArgumentException(json_encode($err));
        if (self::existeUnica($d['alumno_rut'], $d['seccion_id'], $d['fecha'], $id)) {
            throw new RuntimeException('Ya existe asistencia para ese alumno en esa sección y fecha.');
        }
        $db = Database::get();
        $st = $db->prepare("UPDATE asistencias
                            SET alumno_rut=:r, seccion_id=:s, fecha=:f, estado=:e, observacion=:o
                            WHERE id=:id");
        $st->execute([
            ':r'=>$d['alumno_rut'], ':s'=>$d['seccion_id'], ':f'=>$d['fecha'],
            ':e'=>$d['estado'], ':o'=>($d['observacion']!==''?$d['observacion']:null),
            ':id'=>$id
        ]);
        Audit::log($usuarioId, 'EDITAR', 'ASISTENCIA', (string)$id, "A {$d['fecha']} {$d['estado']}");
    }

    public static function eliminar(int $id, int $usuarioId): void {
        $db = Database::get();
        try {
            $db->beginTransaction();
            $db->prepare("DELETE FROM asistencias WHERE id=:id")->execute([':id'=>$id]);
            $db->commit();
            Audit::log($usuarioId, 'ELIMINAR', 'ASISTENCIA', (string)$id, "Eliminada asistencia id=$id");
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw new RuntimeException('No se pudo eliminar la asistencia.');
        }
    }

    // Catálogos
    public static function listaSecciones(): array {
        $db = Database::get();
        $sql = "SELECT s.id,
                       CONCAT(c.anio,' ',nv.nombre,' ',c.letra,' · ',asg.nombre,' (',asg.codigo,')') AS label
                FROM secciones_asignatura s
                JOIN cursos c ON c.id=s.curso_id
                JOIN niveles nv ON nv.id=c.nivel_id
                JOIN asignaturas asg ON asg.id=s.asignatura_id
                ORDER BY c.anio DESC, nv.orden ASC, c.letra ASC, asg.nombre ASC";
        return $db->query($sql)->fetchAll();
    }

    public static function listaAlumnosPorSeccion(int $seccionId): array {
        $db = Database::get();
        // alumnos matriculados en el curso de la sección
        $sql = "SELECT a.rut, CONCAT_WS(' ', p.nombres, p.apellidos) AS nombre
                FROM secciones_asignatura s
                JOIN cursos c ON c.id=s.curso_id
                JOIN matriculas m ON m.curso_id=c.id AND m.estado='VIGENTE'
                JOIN alumnos a ON a.rut=m.alumno_rut AND a.activo=1
                JOIN personas p ON p.rut=a.rut
                WHERE s.id=:sid
                ORDER BY p.apellidos ASC, p.nombres ASC";
        $st = $db->prepare($sql);
        $st->execute([':sid'=>$seccionId]);
        return $st->fetchAll();
    }
}
