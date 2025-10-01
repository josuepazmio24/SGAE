<?php
class Calificacion
{
    public static function validar(array $d, bool $esEditar=false): array {
        $err = [];
        $d['seccion_id']    = (int)($d['seccion_id'] ?? 0);           // solo para el form
        $d['evaluacion_id'] = (int)($d['evaluacion_id'] ?? 0);
        $d['alumno_rut']    = (int)($d['alumno_rut'] ?? 0);
        $d['nota']          = (string)($d['nota'] ?? '');
        $d['observacion']   = trim($d['observacion'] ?? '');

        if ($d['seccion_id'] <= 0)       $err['seccion_id'] = 'Seleccione una sección';
        if ($d['evaluacion_id'] <= 0)    $err['evaluacion_id'] = 'Seleccione una evaluación';
        if ($d['alumno_rut'] <= 0)       $err['alumno_rut'] = 'Seleccione un alumno';

        // nota: número con un decimal, entre 1.0 y 7.0
        if (!is_numeric($d['nota'])) {
            $err['nota'] = 'Nota inválida';
        } else {
            $nota = round((float)$d['nota'], 1);
            if ($nota < 1.0 || $nota > 7.0) $err['nota'] = 'La nota debe estar entre 1.0 y 7.0';
            $d['nota'] = number_format($nota, 1, '.', '');
        }

        if (strlen($d['observacion']) > 200) {
            $err['observacion'] = 'Observación máx. 200 caracteres';
        }
        return [$d, $err];
    }

    public static function existeUnica(int $evaluacionId, int $alumnoRut, ?int $excluirId=null): bool {
        $db = Database::get();
        $sql = "SELECT 1 FROM calificaciones WHERE evaluacion_id=:e AND alumno_rut=:r";
        $p = [':e'=>$evaluacionId, ':r'=>$alumnoRut];
        if ($excluirId) { $sql .= " AND id<>:id"; $p[':id']=$excluirId; }
        $sql .= " LIMIT 1";
        $st = $db->prepare($sql); $st->execute($p);
        return (bool)$st->fetchColumn();
    }

    public static function contar(string $q='', ?int $seccion=null, ?int $evaluacion=null, ?float $min=null, ?float $max=null): int {
        $db = Database::get();
        $sql = "SELECT COUNT(*) FROM calificaciones cal
                JOIN evaluaciones e ON e.id=cal.evaluacion_id
                JOIN secciones_asignatura s ON s.id=e.seccion_id
                JOIN alumnos a ON a.rut=cal.alumno_rut
                JOIN personas p ON p.rut=a.rut
                JOIN cursos c ON c.id=s.curso_id
                JOIN niveles nv ON nv.id=c.nivel_id
                JOIN asignaturas asig ON asig.id=s.asignatura_id
                WHERE 1=1";
        $p = [];
        if ($q !== '')      { $sql.=" AND (p.nombres LIKE :q OR p.apellidos LIKE :q)"; $p[':q']="%$q%"; }
        if ($seccion)       { $sql.=" AND s.id=:sec"; $p[':sec']=$seccion; }
        if ($evaluacion)    { $sql.=" AND e.id=:ev"; $p[':ev']=$evaluacion; }
        if ($min !== null)  { $sql.=" AND cal.nota>=:min"; $p[':min']=$min; }
        if ($max !== null)  { $sql.=" AND cal.nota<=:max"; $p[':max']=$max; }
        $st = $db->prepare($sql); $st->execute($p);
        return (int)$st->fetchColumn();
    }

    public static function listar(string $q='', int $limit=10, int $offset=0, ?int $seccion=null, ?int $evaluacion=null, ?float $min=null, ?float $max=null): array {
        $db = Database::get();
        $sql = "SELECT cal.id, cal.nota, cal.observacion, cal.creado_en,
                       e.id AS evaluacion_id, e.nombre AS evaluacion_nombre, e.tipo, e.fecha, e.ponderacion,
                       s.id AS seccion_id,
                       c.anio, c.letra, nv.nombre AS nivel_nombre,
                       asig.nombre AS asignatura_nombre, asig.codigo AS asignatura_codigo,
                       p.rut AS alumno_rut, CONCAT_WS(' ', p.nombres, p.apellidos) AS alumno_nombre
                FROM calificaciones cal
                JOIN evaluaciones e ON e.id=cal.evaluacion_id
                JOIN secciones_asignatura s ON s.id=e.seccion_id
                JOIN cursos c ON c.id=s.curso_id
                JOIN niveles nv ON nv.id=c.nivel_id
                JOIN asignaturas asig ON asig.id=s.asignatura_id
                JOIN alumnos a ON a.rut=cal.alumno_rut
                JOIN personas p ON p.rut=a.rut
                WHERE 1=1";
        $p = [];
        if ($q !== '')      { $sql.=" AND (p.nombres LIKE :q OR p.apellidos LIKE :q)"; $p[':q']="%$q%"; }
        if ($seccion)       { $sql.=" AND s.id=:sec"; $p[':sec']=$seccion; }
        if ($evaluacion)    { $sql.=" AND e.id=:ev"; $p[':ev']=$evaluacion; }
        if ($min !== null)  { $sql.=" AND cal.nota>=:min"; $p[':min']=$min; }
        if ($max !== null)  { $sql.=" AND cal.nota<=:max"; $p[':max']=$max; }
        $sql .= " ORDER BY e.fecha DESC, e.id DESC, alumno_nombre ASC
                  LIMIT :lim OFFSET :off";
        $st = $db->prepare($sql);
        foreach ($p as $k=>$v) $st->bindValue($k, $v, is_int($v)||is_float($v)?PDO::PARAM_STR:PDO::PARAM_STR);
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public static function obtener(int $id): ?array {
        $db = Database::get();
        $st = $db->prepare("SELECT id, evaluacion_id, alumno_rut, nota, observacion FROM calificaciones WHERE id=:id");
        $st->execute([':id'=>$id]);
        $r = $st->fetch();
        if (!$r) return null;

        // hallar seccion por la evaluacion (para poblar combos)
        $st2 = $db->prepare("SELECT seccion_id FROM evaluaciones WHERE id=:id");
        $st2->execute([':id'=>$r['evaluacion_id']]);
        $r['seccion_id'] = (int)($st2->fetchColumn() ?: 0);
        return $r;
    }

    public static function crear(array $d, int $usuarioId): int {
        [$d,$err] = self::validar($d);
        if ($err) throw new InvalidArgumentException(json_encode($err));
        if (self::existeUnica($d['evaluacion_id'], $d['alumno_rut'])) {
            throw new RuntimeException('Ya existe calificación para ese alumno en esa evaluación.');
        }
        $db = Database::get();
        $st = $db->prepare("INSERT INTO calificaciones (evaluacion_id, alumno_rut, nota, observacion, registrado_por)
                            VALUES (:e,:r,:n,:o,:u)");
        $st->execute([
            ':e'=>$d['evaluacion_id'],
            ':r'=>$d['alumno_rut'],
            ':n'=>$d['nota'],
            ':o'=>($d['observacion']!==''?$d['observacion']:null),
            ':u'=>$usuarioId
        ]);
        $id = (int)$db->lastInsertId();
        Audit::log($usuarioId, 'CREAR', 'CALIFICACION', (string)$id, "Nota {$d['nota']}");
        return $id;
    }

    public static function actualizar(int $id, array $d, int $usuarioId): void {
        [$d,$err] = self::validar($d, true);
        if ($err) throw new InvalidArgumentException(json_encode($err));
        if (self::existeUnica($d['evaluacion_id'], $d['alumno_rut'], $id)) {
            throw new RuntimeException('Ya existe calificación para ese alumno en esa evaluación.');
        }
        $db = Database::get();
        $st = $db->prepare("UPDATE calificaciones
                            SET evaluacion_id=:e, alumno_rut=:r, nota=:n, observacion=:o
                            WHERE id=:id");
        $st->execute([
            ':e'=>$d['evaluacion_id'],
            ':r'=>$d['alumno_rut'],
            ':n'=>$d['nota'],
            ':o'=>($d['observacion']!==''?$d['observacion']:null),
            ':id'=>$id
        ]);
        Audit::log($usuarioId, 'EDITAR', 'CALIFICACION', (string)$id, "Actualizada a {$d['nota']}");
    }

    public static function eliminar(int $id, int $usuarioId): void {
        $db = Database::get();
        try {
            $db->beginTransaction();
            $db->prepare("DELETE FROM calificaciones WHERE id=:id")->execute([':id'=>$id]);
            $db->commit();
            Audit::log($usuarioId, 'ELIMINAR', 'CALIFICACION', (string)$id, "Eliminada calificación id=$id");
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw new RuntimeException('No se pudo eliminar la calificación.');
        }
    }

    // --- Catálogos para selects ---
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

    public static function listaEvaluacionesPorSeccion(int $seccionId): array {
        $db = Database::get();
        $st = $db->prepare("SELECT id, CONCAT(fecha,' · ',nombre,' (',tipo,')') AS nombre
                            FROM evaluaciones
                            WHERE seccion_id=:sid
                            ORDER BY fecha DESC, id DESC");
        $st->execute([':sid'=>$seccionId]);
        return $st->fetchAll();
    }

    public static function listaAlumnosPorSeccion(int $seccionId): array {
        $db = Database::get();
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
