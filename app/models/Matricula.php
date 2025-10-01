<?php
class Matricula
{
    // --------- Validación ----------
    private static function validar(array $d, bool $esEditar=false): array {
        $err = [];
        $d['alumno_rut']      = (int)($d['alumno_rut'] ?? 0);
        $d['curso_id']        = (int)($d['curso_id'] ?? 0);
        $d['fecha_matricula'] = trim($d['fecha_matricula'] ?? date('Y-m-d'));
        $d['estado']          = trim($d['estado'] ?? 'VIGENTE');

        if ($d['alumno_rut'] <= 0) $err['alumno_rut'] = 'Seleccione alumno';
        if ($d['curso_id']   <= 0) $err['curso_id']   = 'Seleccione curso';
        if ($d['fecha_matricula'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $d['fecha_matricula'])) {
            $err['fecha_matricula'] = 'Fecha inválida (YYYY-MM-DD)';
        }
        if (!in_array($d['estado'], ['VIGENTE','RETIRADO','EGRESADO'], true)) {
            $err['estado'] = 'Estado inválido';
        }
        return [$d, $err];
    }

    // --------- Listados / conteos ----------
    public static function contar(string $q='', ?int $anio=null, ?int $curso_id=null, ?string $estado=null): int {
        $db = Database::get();
        $sql = "SELECT COUNT(*)
                FROM matriculas m
                JOIN alumnos a   ON a.rut = m.alumno_rut
                JOIN personas p  ON p.rut = a.rut
                JOIN cursos c    ON c.id = m.curso_id
                JOIN niveles n   ON n.id = c.nivel_id
                WHERE 1=1";
        $p = [];
        if ($q !== '')       { $sql.=" AND (p.nombres LIKE :q OR p.apellidos LIKE :q OR a.rut LIKE :q)"; $p[':q']="%$q%"; }
        if ($anio !== null)  { $sql.=" AND c.anio = :anio"; $p[':anio']=$anio; }
        if ($curso_id)       { $sql.=" AND c.id = :cid"; $p[':cid']=$curso_id; }
        if ($estado)         { $sql.=" AND m.estado = :est"; $p[':est']=$estado; }

        $st = $db->prepare($sql); $st->execute($p);
        return (int)$st->fetchColumn();
    }

    public static function listar(string $q='', int $limit=10, int $offset=0, ?int $anio=null, ?int $curso_id=null, ?string $estado=null): array {
        $db = Database::get();
        $sql = "SELECT m.id, m.alumno_rut, m.curso_id, m.fecha_matricula, m.estado,
                       p.nombres, p.apellidos, p.dv,
                       c.anio, n.nombre AS nivel, c.letra
                FROM matriculas m
                JOIN alumnos a   ON a.rut = m.alumno_rut
                JOIN personas p  ON p.rut = a.rut
                JOIN cursos c    ON c.id = m.curso_id
                JOIN niveles n   ON n.id = c.nivel_id
                WHERE 1=1";
        $p = [];
        if ($q !== '')       { $sql.=" AND (p.nombres LIKE :q OR p.apellidos LIKE :q OR a.rut LIKE :q)"; $p[':q']="%$q%"; }
        if ($anio !== null)  { $sql.=" AND c.anio = :anio"; $p[':anio']=$anio; }
        if ($curso_id)       { $sql.=" AND c.id = :cid"; $p[':cid']=$curso_id; }
        if ($estado)         { $sql.=" AND m.estado = :est"; $p[':est']=$estado; }
        $sql .= " ORDER BY c.anio DESC, n.orden ASC, c.letra ASC, p.apellidos ASC, p.nombres ASC
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
        $st = $db->prepare("SELECT m.id, m.alumno_rut, m.curso_id, m.fecha_matricula, m.estado,
                                   p.nombres, p.apellidos, p.dv,
                                   c.anio, n.nombre AS nivel, c.letra
                            FROM matriculas m
                            JOIN alumnos a   ON a.rut = m.alumno_rut
                            JOIN personas p  ON p.rut = a.rut
                            JOIN cursos c    ON c.id = m.curso_id
                            JOIN niveles n   ON n.id = c.nivel_id
                            WHERE m.id=:id");
        $st->execute([':id'=>$id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    // --------- Mutaciones ----------
    public static function crear(array $d): int {
        [$d,$err] = self::validar($d);
        if ($err) throw new InvalidArgumentException(json_encode($err));
        $db = Database::get();

        // Existen alumno y curso
        if (!self::existeAlumno($d['alumno_rut'])) throw new RuntimeException('El alumno no existe.');
        if (!self::existeCurso($d['curso_id']))     throw new RuntimeException('El curso no existe.');

        try {
            $st = $db->prepare("INSERT INTO matriculas (alumno_rut, curso_id, fecha_matricula, estado)
                                VALUES (:rut, :cid, :fec, :est)");
            $st->execute([
                ':rut'=>$d['alumno_rut'],
                ':cid'=>$d['curso_id'],
                ':fec'=>($d['fecha_matricula'] ?: date('Y-m-d')),
                ':est'=>$d['estado'],
            ]);
        } catch (PDOException $e) {
            if ((int)$e->errorInfo[1]===1062) {
                throw new RuntimeException('Ese alumno ya está matriculado en ese curso.');
            }
            throw $e;
        }
        $id = (int)$db->lastInsertId();
        Audit::log($_SESSION['user']['id'] ?? 0, 'CREAR', 'MATRICULA', (string)$id, 'Matrícula creada');
        return $id;
    }

    public static function actualizar(int $id, array $d): void {
        [$d,$err] = self::validar($d, true);
        if ($err) throw new InvalidArgumentException(json_encode($err));
        $db = Database::get();

        // Si cambian los IDs, validar existencia
        if (!self::existeAlumno($d['alumno_rut'])) throw new RuntimeException('El alumno no existe.');
        if (!self::existeCurso($d['curso_id']))     throw new RuntimeException('El curso no existe.');

        try {
            $st = $db->prepare("UPDATE matriculas
                                SET alumno_rut=:rut, curso_id=:cid, fecha_matricula=:fec, estado=:est
                                WHERE id=:id");
            $st->execute([
                ':rut'=>$d['alumno_rut'],
                ':cid'=>$d['curso_id'],
                ':fec'=>($d['fecha_matricula'] ?: date('Y-m-d')),
                ':est'=>$d['estado'],
                ':id'=>$id
            ]);
        } catch (PDOException $e) {
            if ((int)$e->errorInfo[1]===1062) {
                throw new RuntimeException('Ese alumno ya está matriculado en ese curso.');
            }
            throw $e;
        }
        Audit::log($_SESSION['user']['id'] ?? 0, 'EDITAR', 'MATRICULA', (string)$id, 'Matrícula actualizada');
    }

    public static function eliminar(int $id): void {
        $db = Database::get();
        try {
            $db->beginTransaction();
            $db->prepare("DELETE FROM matriculas WHERE id=:id")->execute([':id'=>$id]);
            $db->commit();
            Audit::log($_SESSION['user']['id'] ?? 0, 'ELIMINAR', 'MATRICULA', (string)$id, 'Matrícula eliminada');
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            // Puede fallar si hay dependencias, aunque por tu esquema no debería (RESTRICT evita borrar curso/alumno si hay matrícula)
            throw new RuntimeException('No se pudo eliminar la matrícula.');
        }
    }

    // --------- Catálogos ----------
    public static function cursos(?int $anio=null): array {
        $db = Database::get();
        $sql = "SELECT c.id, c.anio, c.letra, n.nombre AS nivel
                FROM cursos c JOIN niveles n ON n.id = c.nivel_id";
        $p = [];
        if ($anio !== null) { $sql .= " WHERE c.anio=:a"; $p[':a']=$anio; }
        $sql .= " ORDER BY c.anio DESC, n.orden ASC, c.letra ASC";
        $st = $db->prepare($sql); $st->execute($p);
        return $st->fetchAll();
    }

    public static function aniosDisponibles(): array {
        $db = Database::get();
        $rows = $db->query("SELECT DISTINCT anio FROM cursos ORDER BY anio DESC")->fetchAll(PDO::FETCH_COLUMN);
        return array_map('intval', $rows);
    }

    public static function alumnos(): array {
        $db = Database::get();
        $sql = "SELECT a.rut, CONCAT_WS(' ', p.nombres, p.apellidos) AS nombre, p.dv, p.email
                FROM alumnos a JOIN personas p ON p.rut = a.rut
                WHERE a.activo = 1
                ORDER BY p.apellidos ASC, p.nombres ASC";
        return $db->query($sql)->fetchAll();
    }

    public static function alumnosDisponiblesParaCurso(int $curso_id): array {
        $db = Database::get();
        $sql = "SELECT a.rut, CONCAT_WS(' ', p.nombres, p.apellidos) AS nombre, p.email
                FROM alumnos a
                JOIN personas p ON p.rut = a.rut
                WHERE a.activo = 1
                  AND NOT EXISTS (
                    SELECT 1 FROM matriculas m WHERE m.alumno_rut = a.rut AND m.curso_id = :cid
                  )
                ORDER BY p.apellidos ASC, p.nombres ASC";
        $st = $db->prepare($sql); $st->execute([':cid'=>$curso_id]);
        return $st->fetchAll();
    }

    // --------- Helpers privados ----------
    private static function existeAlumno(int $rut): bool {
        $db = Database::get();
        $st = $db->prepare('SELECT 1 FROM alumnos WHERE rut=:r'); $st->execute([':r'=>$rut]);
        return (bool)$st->fetchColumn();
    }
    private static function existeCurso(int $id): bool {
        $db = Database::get();
        $st = $db->prepare('SELECT 1 FROM cursos WHERE id=:id'); $st->execute([':id'=>$id]);
        return (bool)$st->fetchColumn();
    }
}
