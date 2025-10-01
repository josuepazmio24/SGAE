<?php
class Curso
{
    private static function normalizaLetra(?string $letra): string {
        $letra = strtoupper(trim((string)$letra));
        return $letra !== '' ? $letra[0] : '';
    }

    public static function validar(array $d, bool $esEditar = false): array {
        $err = [];
        $d['anio']   = (int)($d['anio'] ?? 0);
        $d['nivel_id'] = (int)($d['nivel_id'] ?? 0);
        $d['letra']  = self::normalizaLetra($d['letra'] ?? null);
        $d['jornada'] = $d['jornada'] ?? 'MAÑANA';
        $d['jefe_rut_profesor'] = isset($d['jefe_rut_profesor']) && $d['jefe_rut_profesor'] !== '' ? (int)$d['jefe_rut_profesor'] : null;

        if ($d['anio'] < 2000 || $d['anio'] > 2100) $err['anio'] = 'Año inválido';
        if ($d['nivel_id'] <= 0) $err['nivel_id'] = 'Seleccione un nivel';
        if ($d['letra'] === '') $err['letra'] = 'Ingrese letra (A-Z)';

        // jornada válida
        $validJ = ['MAÑANA','TARDE','COMPLETA'];
        if (!in_array($d['jornada'], $validJ, true)) $d['jornada'] = 'MAÑANA';

        return [$d, $err];
    }

    public static function contar(string $q = '', ?int $anio = null, ?int $nivel = null): int {
        $db = Database::get();
        $sql = "SELECT COUNT(*) FROM cursos c
                JOIN niveles n ON n.id = c.nivel_id
                LEFT JOIN personas p ON p.rut = c.jefe_rut_profesor
                WHERE 1=1";
        $params = [];
        if ($q !== '') {
            $sql .= " AND (n.nombre LIKE :q OR p.nombres LIKE :q OR p.apellidos LIKE :q)";
            $params[':q'] = "%$q%";
        }
        if ($anio) { $sql .= " AND c.anio = :anio"; $params[':anio'] = $anio; }
        if ($nivel) { $sql .= " AND c.nivel_id = :nivel"; $params[':nivel'] = $nivel; }

        $st = $db->prepare($sql);
        $st->execute($params);
        return (int)$st->fetchColumn();
    }

    public static function listar(string $q = '', int $limit = 10, int $offset = 0, ?int $anio = null, ?int $nivel = null): array {
        $db = Database::get();
        $sql = "SELECT c.id, c.anio, c.letra, c.jornada, c.nivel_id, c.jefe_rut_profesor,
                       n.nombre AS nivel_nombre,
                       CONCAT_WS(' ', p.nombres, p.apellidos) AS jefe_nombre
                FROM cursos c
                JOIN niveles n ON n.id = c.nivel_id
                LEFT JOIN personas p ON p.rut = c.jefe_rut_profesor
                WHERE 1=1";
        $params = [];
        if ($q !== '') {
            $sql .= " AND (n.nombre LIKE :q OR p.nombres LIKE :q OR p.apellidos LIKE :q)";
            $params[':q'] = "%$q%";
        }
        if ($anio)  { $sql .= " AND c.anio = :anio";   $params[':anio'] = $anio; }
        if ($nivel) { $sql .= " AND c.nivel_id = :nivel"; $params[':nivel'] = $nivel; }
        $sql .= " ORDER BY c.anio DESC, n.orden ASC, c.letra ASC
                  LIMIT :lim OFFSET :off";
        $st = $db->prepare($sql);
        foreach ($params as $k=>$v) $st->bindValue($k, $v, is_int($v)?PDO::PARAM_INT:PDO::PARAM_STR);
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public static function obtener(int $id): ?array {
        $db = Database::get();
        $st = $db->prepare("SELECT id, anio, nivel_id, letra, jornada, jefe_rut_profesor
                            FROM cursos WHERE id = :id");
        $st->execute([':id'=>$id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public static function existeUnico(int $anio, int $nivelId, string $letra, ?int $excluirId = null): bool {
        $db = Database::get();
        $sql = "SELECT 1 FROM cursos WHERE anio=:a AND nivel_id=:n AND letra=:l";
        $params = [':a'=>$anio, ':n'=>$nivelId, ':l'=>$letra];
        if ($excluirId) { $sql .= " AND id <> :id"; $params[':id'] = $excluirId; }
        $sql .= " LIMIT 1";
        $st = $db->prepare($sql);
        $st->execute($params);
        return (bool)$st->fetchColumn();
    }

    public static function crear(array $d, int $usuarioId): int {
        [$d, $err] = self::validar($d);
        if ($err) throw new InvalidArgumentException(json_encode($err));
        if (self::existeUnico($d['anio'], $d['nivel_id'], $d['letra'])) {
            throw new RuntimeException('Ya existe un curso con ese Año + Nivel + Letra.');
        }
        $db = Database::get();
        $st = $db->prepare("INSERT INTO cursos (anio, nivel_id, letra, jornada, jefe_rut_profesor)
                            VALUES (:a,:n,:l,:j,:jefe)");
        $st->execute([
            ':a'=>$d['anio'], ':n'=>$d['nivel_id'], ':l'=>$d['letra'],
            ':j'=>$d['jornada'], ':jefe'=>$d['jefe_rut_profesor']
        ]);
        $id = (int)$db->lastInsertId();
        Audit::log($usuarioId, 'CREAR', 'CURSO', (string)$id, "Curso {$d['anio']} {$d['letra']}");
        return $id;
    }

    public static function actualizar(int $id, array $d, int $usuarioId): void {
        [$d, $err] = self::validar($d, true);
        if ($err) throw new InvalidArgumentException(json_encode($err));
        if (self::existeUnico($d['anio'], $d['nivel_id'], $d['letra'], $id)) {
            throw new RuntimeException('Ya existe un curso con ese Año + Nivel + Letra.');
        }
        $db = Database::get();
        $st = $db->prepare("UPDATE cursos
                            SET anio=:a, nivel_id=:n, letra=:l, jornada=:j, jefe_rut_profesor=:jefe
                            WHERE id=:id");
        $st->execute([
            ':a'=>$d['anio'], ':n'=>$d['nivel_id'], ':l'=>$d['letra'],
            ':j'=>$d['jornada'], ':jefe'=>$d['jefe_rut_profesor'], ':id'=>$id
        ]);
        Audit::log($usuarioId, 'EDITAR', 'CURSO', (string)$id, "Curso {$d['anio']} {$d['letra']}");
    }

    public static function eliminar(int $id, int $usuarioId): void {
        $db = Database::get();
        try {
            $db->beginTransaction();
            $db->prepare("DELETE FROM cursos WHERE id=:id")->execute([':id'=>$id]);
            $db->commit();
            Audit::log($usuarioId, 'ELIMINAR', 'CURSO', (string)$id, "Eliminado curso $id");
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            // Hay FKs (secciones_asignatura, matriculas, etc.)
            throw new RuntimeException('No se puede eliminar: el curso está en uso.');
        }
    }

    // --- Catálogos para selects ---

    public static function listaNiveles(): array {
        $db = Database::get();
        return $db->query("SELECT id, nombre FROM niveles ORDER BY orden ASC, nombre ASC")->fetchAll();
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

    public static function aniosDisponibles(): array {
        $db = Database::get();
        $rows = $db->query("SELECT DISTINCT anio FROM cursos ORDER BY anio DESC")->fetchAll();
        return array_map(fn($r)=>(int)$r['anio'], $rows);
    }
}
