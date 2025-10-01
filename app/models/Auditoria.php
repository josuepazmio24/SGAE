<?php
class Auditoria
{
    public static function contar(array $filtros = []): int {
        $db = Database::get();
        [$where, $params] = self::buildWhere($filtros);
        $sql = "SELECT COUNT(*) FROM auditoria_logs l
                JOIN usuarios u ON u.id = l.usuario_id
                LEFT JOIN personas p ON p.rut = u.rut_persona
                $where";
        $st = $db->prepare($sql);
        $st->execute($params);
        return (int)$st->fetchColumn();
    }

    public static function listar(array $filtros = [], int $limit = 20, int $offset = 0): array {
        $db = Database::get();
        [$where, $params] = self::buildWhere($filtros);
        $sql = "SELECT
                    l.id, l.usuario_id, l.accion, l.entidad, l.entidad_id, l.descripcion,
                    l.ip, l.creado_en,
                    u.username, u.rol,
                    CONCAT_WS(' ', p.nombres, p.apellidos) AS persona
                FROM auditoria_logs l
                JOIN usuarios u ON u.id = l.usuario_id
                LEFT JOIN personas p ON p.rut = u.rut_persona
                $where
                ORDER BY l.id DESC
                LIMIT :lim OFFSET :off";
        $st = $db->prepare($sql);
        foreach ($params as $k=>$v) $st->bindValue($k, $v);
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    /** Export CSV (sin paginar) */
    public static function export(array $filtros = []): array {
        $db = Database::get();
        [$where, $params] = self::buildWhere($filtros);
        $sql = "SELECT
                    l.id,
                    DATE_FORMAT(l.creado_en, '%Y-%m-%d %H:%i:%s') AS fecha,
                    u.username,
                    u.rol,
                    COALESCE(CONCAT_WS(' ', p.nombres, p.apellidos),'') AS persona,
                    l.accion, l.entidad, l.entidad_id, l.descripcion, l.ip
                FROM auditoria_logs l
                JOIN usuarios u ON u.id = l.usuario_id
                LEFT JOIN personas p ON p.rut = u.rut_persona
                $where
                ORDER BY l.id DESC";
        $st = $db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    /** Opcionales para combos */
    public static function acciones(): array {
        $db = Database::get();
        return $db->query("SELECT DISTINCT accion FROM auditoria_logs ORDER BY accion")->fetchAll(PDO::FETCH_COLUMN);
    }
    public static function entidades(): array {
        $db = Database::get();
        return $db->query("SELECT DISTINCT entidad FROM auditoria_logs ORDER BY entidad")->fetchAll(PDO::FETCH_COLUMN);
    }

    private static function buildWhere(array $f): array {
        $where = "WHERE 1=1";
        $p = [];

        if (!empty($f['q'])) {
            $where .= " AND (l.descripcion LIKE :q OR l.entidad_id LIKE :q OR u.username LIKE :q OR l.ip LIKE :q)";
            $p[':q'] = "%{$f['q']}%";
        }
        if (!empty($f['usuario'])) {
            $where .= " AND u.username = :u";
            $p[':u'] = $f['usuario'];
        }
        if (!empty($f['accion'])) {
            $where .= " AND l.accion = :a";
            $p[':a'] = $f['accion'];
        }
        if (!empty($f['entidad'])) {
            $where .= " AND l.entidad = :e";
            $p[':e'] = $f['entidad'];
        }
        if (!empty($f['desde'])) {
            $where .= " AND l.creado_en >= :d";
            $p[':d'] = $f['desde'] . ' 00:00:00';
        }
        if (!empty($f['hasta'])) {
            $where .= " AND l.creado_en <= :h";
            $p[':h'] = $f['hasta'] . ' 23:59:59';
        }
        return [$where, $p];
    }
}
