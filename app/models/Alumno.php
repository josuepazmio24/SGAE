<?php
class Alumno
{
    public static function validar(array $d, bool $esEditar=false): array {
        $err = [];
        $d['rut']           = (int)($d['rut'] ?? 0);
        $d['nro_matricula'] = trim($d['nro_matricula'] ?? '');
        $d['fecha_ingreso'] = trim($d['fecha_ingreso'] ?? '');
        $d['activo']        = isset($d['activo']) ? (int)$d['activo'] : 1;

        if ($d['rut'] <= 0)                  $err['rut'] = 'Seleccione la persona';
        if ($d['nro_matricula'] === '')      $err['nro_matricula'] = 'Ingrese número de matrícula';
        if ($d['fecha_ingreso'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $d['fecha_ingreso']))
            $err['fecha_ingreso'] = 'Fecha inválida (YYYY-MM-DD)';
        if (!in_array($d['activo'], [0,1], true)) $d['activo'] = 1;

        return [$d, $err];
    }

    public static function contar(string $q='', ?int $activo=null): int {
        $db = Database::get();
        $sql = "SELECT COUNT(*) FROM alumnos al
                JOIN personas p ON p.rut = al.rut
                WHERE 1=1";
        $p = [];
        if ($q !== '')        { $sql .= " AND (p.nombres LIKE :q OR p.apellidos LIKE :q OR al.nro_matricula LIKE :q)"; $p[':q']="%$q%"; }
        if ($activo !== null) { $sql .= " AND al.activo = :ac"; $p[':ac']=$activo; }
        $st = $db->prepare($sql); $st->execute($p);
        return (int)$st->fetchColumn();
    }

    public static function listar(string $q='', int $limit=10, int $offset=0, ?int $activo=null): array {
        $db = Database::get();
        $sql = "SELECT al.rut, al.nro_matricula, al.fecha_ingreso, al.activo,
                       p.dv, p.nombres, p.apellidos, p.email
                FROM alumnos al
                JOIN personas p ON p.rut = al.rut
                WHERE 1=1";
        $p = [];
        if ($q !== '')        { $sql .= " AND (p.nombres LIKE :q OR p.apellidos LIKE :q OR al.nro_matricula LIKE :q)"; $p[':q']="%$q%"; }
        if ($activo !== null) { $sql .= " AND al.activo = :ac"; $p[':ac']=$activo; }
        $sql .= " ORDER BY p.apellidos ASC, p.nombres ASC LIMIT :lim OFFSET :off";
        $st = $db->prepare($sql);
        foreach ($p as $k=>$v) $st->bindValue($k, $v, PDO::PARAM_STR);
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

   public static function alumnos(int $seccionId): array {
    $db = Database::get();
    $sql = "SELECT a.rut, p.nombres, p.apellidos
            FROM secciones_asignatura s
            JOIN cursos c       ON c.id = s.curso_id
            JOIN matriculas m   ON m.curso_id = c.id AND m.estado='VIGENTE'
            JOIN alumnos a      ON a.rut = m.alumno_rut AND a.activo = 1
            JOIN personas p     ON p.rut = a.rut
            WHERE s.id = :sid
            ORDER BY p.apellidos ASC, p.nombres ASC";
    $st = $db->prepare($sql);
    $st->execute([':sid'=>$seccionId]);
    return $st->fetchAll();
}

    public static function crear(array $d): int {
        [$d,$err] = self::validar($d);
        if ($err) throw new InvalidArgumentException(json_encode($err));

        $db = Database::get();

        // Persona debe existir y no ser ya alumno
        $st = $db->prepare('SELECT 1 FROM personas WHERE rut=:r');
        $st->execute([':r'=>$d['rut']]);
        if (!$st->fetchColumn()) throw new RuntimeException('La persona no existe.');

        $st = $db->prepare('SELECT 1 FROM alumnos WHERE rut=:r');
        $st->execute([':r'=>$d['rut']]);
        if ($st->fetchColumn()) throw new RuntimeException('Esa persona ya es Alumno.');

        try {
            $st = $db->prepare("INSERT INTO alumnos (rut, nro_matricula, fecha_ingreso, activo)
                                VALUES (:rut, :mat, :fec, :act)");
            $st->execute([
                ':rut'=>$d['rut'],
                ':mat'=>$d['nro_matricula'],
                ':fec'=>($d['fecha_ingreso'] ?: null),
                ':act'=>$d['activo'],
            ]);
        } catch (PDOException $e) {
            if ((int)$e->errorInfo[1]===1062 && str_contains($e->getMessage(),'uq_alumnos_matricula')) {
                throw new RuntimeException('El número de matrícula ya está en uso.');
            }
            throw $e;
        }
        Audit::log($_SESSION['user']['id'] ?? 0, 'CREAR', 'ALUMNO', (string)$d['rut'], 'Alumno creado');
        return (int)$d['rut'];
    }

    public static function actualizar(int $rut, array $d): void {
        [$d,$err] = self::validar($d, true);
        if ($err) throw new InvalidArgumentException(json_encode($err));

        $db = Database::get();
        try {
            $st = $db->prepare("UPDATE alumnos
                                SET nro_matricula=:mat, fecha_ingreso=:fec, activo=:act
                                WHERE rut=:rut");
            $st->execute([
                ':mat'=>$d['nro_matricula'],
                ':fec'=>($d['fecha_ingreso'] ?: null),
                ':act'=>$d['activo'],
                ':rut'=>$rut
            ]);
        } catch (PDOException $e) {
            if ((int)$e->errorInfo[1]===1062 && str_contains($e->getMessage(),'uq_alumnos_matricula')) {
                throw new RuntimeException('El número de matrícula ya está en uso.');
            }
            throw $e;
        }
        Audit::log($_SESSION['user']['id'] ?? 0, 'EDITAR', 'ALUMNO', (string)$rut, 'Alumno editado');
    }

    public static function eliminar(int $rut): void {
        $db = Database::get();
        try {
            $db->beginTransaction();
            $db->prepare("DELETE FROM alumnos WHERE rut=:rut")->execute([':rut'=>$rut]);
            $db->commit();
            Audit::log($_SESSION['user']['id'] ?? 0, 'ELIMINAR', 'ALUMNO', (string)$rut, 'Alumno eliminado');
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            // Ojo: hay FKs (matriculas, asistencias, calificaciones, etc.). Con tu esquema muchas están en CASCADE.
            throw new RuntimeException('No se pudo eliminar el alumno.');
        }
    }

    // Catálogo: personas que aún no son alumnos
    public static function personasSinAlumno(): array {
        $db = Database::get();
        $sql = "SELECT p.rut, CONCAT_WS(' ', p.nombres, p.apellidos) AS nombre, p.email, p.dv
                FROM personas p
                LEFT JOIN alumnos al ON al.rut = p.rut
                WHERE al.rut IS NULL
                ORDER BY p.apellidos ASC, p.nombres ASC";
        return $db->query($sql)->fetchAll();
    }
}
