<?php
class SesionClase
{
    private static function normBloque(?string $b): string {
        $b = trim((string)$b);
        return $b === '' ? '' : $b;
    }

    public static function validar(array $d, bool $esEditar=false): array {
        $err = [];
        $d['seccion_id'] = (int)($d['seccion_id'] ?? 0);
        $d['fecha']      = trim($d['fecha'] ?? '');
        $d['bloque']     = trim($d['bloque'] ?? '');
        $d['tema']       = trim($d['tema'] ?? '');

        if ($d['seccion_id'] <= 0) $err['seccion_id'] = 'Seleccione una sección';
        if ($d['fecha'] === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $d['fecha'])) {
            $err['fecha'] = 'Fecha inválida (YYYY-MM-DD)';
        }
        // bloque puede ir vacío → significa bloque_norm=''
        return [$d, $err];
    }

    public static function existeUnica(int $seccionId, string $fecha, string $bloqueNorm, ?int $excluirId=null): bool {
        $db = Database::get();
        $sql = "SELECT 1
                FROM sesiones_clase
                WHERE seccion_id=:s AND fecha=:f AND IFNULL(bloque,'')=:bn";
        $p = [':s'=>$seccionId, ':f'=>$fecha, ':bn'=>$bloqueNorm];
        if ($excluirId) { $sql .= " AND id<>:id"; $p[':id'] = $excluirId; }
        $sql .= " LIMIT 1";
        $st = $db->prepare($sql);
        $st->execute($p);
        return (bool)$st->fetchColumn();
    }

    public static function contar(string $q='', ?int $seccion=null, ?string $desde=null, ?string $hasta=null): int {
        $db = Database::get();
        $sql = "SELECT COUNT(*) FROM sesiones_clase sc
                JOIN secciones_asignatura s ON s.id=sc.seccion_id
                JOIN cursos c ON c.id=s.curso_id
                JOIN niveles nv ON nv.id=c.nivel_id
                JOIN asignaturas a ON a.id=s.asignatura_id
                JOIN personas p ON p.rut=s.profesor_rut
                WHERE 1=1";
        $p = [];
        if ($q !== '')      { $sql .= " AND (sc.tema LIKE :q OR a.nombre LIKE :q OR a.codigo LIKE :q OR p.nombres LIKE :q OR p.apellidos LIKE :q)"; $p[':q']="%$q%"; }
        if ($seccion)       { $sql .= " AND sc.seccion_id=:sec"; $p[':sec']=$seccion; }
        if ($desde)         { $sql .= " AND sc.fecha>=:d"; $p[':d']=$desde; }
        if ($hasta)         { $sql .= " AND sc.fecha<=:h"; $p[':h']=$hasta; }
        $st = $db->prepare($sql);
        $st->execute($p);
        return (int)$st->fetchColumn();
    }

    public static function listar(string $q='', int $limit=10, int $offset=0, ?int $seccion=null, ?string $desde=null, ?string $hasta=null): array {
        $db = Database::get();
        $sql = "SELECT sc.id, sc.seccion_id, sc.fecha, sc.bloque, sc.tema, sc.creado_en,
                       c.anio, c.letra, nv.nombre AS nivel_nombre,
                       a.nombre AS asignatura_nombre, a.codigo AS asignatura_codigo,
                       CONCAT_WS(' ', p.nombres, p.apellidos) AS profesor_nombre, p.rut AS profesor_rut
                FROM sesiones_clase sc
                JOIN secciones_asignatura s ON s.id=sc.seccion_id
                JOIN cursos c ON c.id=s.curso_id
                JOIN niveles nv ON nv.id=c.nivel_id
                JOIN asignaturas a ON a.id=s.asignatura_id
                JOIN personas p ON p.rut=s.profesor_rut
                WHERE 1=1";
        $p = [];
        if ($q !== '')      { $sql .= " AND (sc.tema LIKE :q OR a.nombre LIKE :q OR a.codigo LIKE :q OR p.nombres LIKE :q OR p.apellidos LIKE :q)"; $p[':q']="%$q%"; }
        if ($seccion)       { $sql .= " AND sc.seccion_id=:sec"; $p[':sec']=$seccion; }
        if ($desde)         { $sql .= " AND sc.fecha>=:d"; $p[':d']=$desde; }
        if ($hasta)         { $sql .= " AND sc.fecha<=:h"; $p[':h']=$hasta; }
        $sql .= " ORDER BY sc.fecha DESC, sc.bloque IS NULL, sc.bloque ASC, sc.id DESC
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
        $st = $db->prepare("SELECT id, seccion_id, fecha, bloque, tema FROM sesiones_clase WHERE id=:id");
        $st->execute([':id'=>$id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public static function crear(array $d, int $usuarioId): int {
        [$d,$err] = self::validar($d);
        if ($err) throw new InvalidArgumentException(json_encode($err));

        $bn = self::normBloque($d['bloque']);
        if (self::existeUnica((int)$d['seccion_id'], $d['fecha'], $bn)) {
            throw new RuntimeException('Ya existe una sesión para esa sección, fecha y bloque.');
        }

        $db = Database::get();
        $st = $db->prepare("INSERT INTO sesiones_clase (seccion_id, fecha, bloque, tema, creado_por)
                            VALUES (:s, :f, :b, :t, :u)");
        $st->execute([
            ':s'=>(int)$d['seccion_id'],
            ':f'=>$d['fecha'],
            ':b'=>($bn === '' ? null : $bn),
            ':t'=>($d['tema'] !== '' ? $d['tema'] : null),
            ':u'=>$usuarioId
        ]);
        $id = (int)$db->lastInsertId();
        Audit::log($usuarioId, 'CREAR', 'SESION', (string)$id, "Sesión seccion={$d['seccion_id']} {$d['fecha']} bloque={$bn}");
        return $id;
    }

    public static function actualizar(int $id, array $d, int $usuarioId): void {
        [$d,$err] = self::validar($d, true);
        if ($err) throw new InvalidArgumentException(json_encode($err));

        $bn = self::normBloque($d['bloque']);
        if (self::existeUnica((int)$d['seccion_id'], $d['fecha'], $bn, $id)) {
            throw new RuntimeException('Ya existe una sesión para esa sección, fecha y bloque.');
        }

        $db = Database::get();
        $st = $db->prepare("UPDATE sesiones_clase
                            SET seccion_id=:s, fecha=:f, bloque=:b, tema=:t
                            WHERE id=:id");
        $st->execute([
            ':s'=>(int)$d['seccion_id'],
            ':f'=>$d['fecha'],
            ':b'=>($bn === '' ? null : $bn),
            ':t'=>($d['tema'] !== '' ? $d['tema'] : null),
            ':id'=>$id
        ]);
        Audit::log($usuarioId, 'EDITAR', 'SESION', (string)$id, "Sesión editada id=$id");
    }

    public static function eliminar(int $id, int $usuarioId): void {
        $db = Database::get();
        try {
            $db->beginTransaction();
            $db->prepare("DELETE FROM sesiones_clase WHERE id=:id")->execute([':id'=>$id]);
            $db->commit();
            Audit::log($usuarioId, 'ELIMINAR', 'SESION', (string)$id, "Sesión eliminada id=$id");
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            // Si en tu flujo agregas dependencias futuras, se verán aquí.
            throw new RuntimeException('No se puede eliminar la sesión.');
        }
    }

    // Catálogo para selects (label legible)
    public static function listaSecciones(): array {
        $db = Database::get();
        $sql = "SELECT s.id,
                       CONCAT(c.anio,' ', nv.nombre,' ', c.letra,' · ', a.nombre,' (',a.codigo,') · ', p.nombres,' ',p.apellidos) AS label
                FROM secciones_asignatura s
                JOIN cursos c ON c.id=s.curso_id
                JOIN niveles nv ON nv.id=c.nivel_id
                JOIN asignaturas a ON a.id=s.asignatura_id
                JOIN personas p ON p.rut=s.profesor_rut
                ORDER BY c.anio DESC, nv.orden ASC, c.letra ASC, a.nombre ASC";
        return Database::get()->query($sql)->fetchAll();
    }
}
