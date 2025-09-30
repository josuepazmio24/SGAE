<?php
class Nivel {
    public static function validar(array $d, bool $esEditar = false): array {
        $err = [];
        $d['nombre'] = trim($d['nombre'] ?? '');
        $d['orden']  = (int)($d['orden'] ?? 0);
        $d['descripcion'] = trim($d['descripcion'] ?? '');
        if ($d['nombre'] === '') $err['nombre'] = 'Ingrese nombre';
        if ($d['orden'] <= 0)    $err['orden']  = 'Orden debe ser > 0';
        return [$d, $err];
    }

    public static function contar(string $q = ''): int {
        $db = Database::get();
        if ($q !== '') {
            $st = $db->prepare("SELECT COUNT(*) FROM niveles WHERE nombre LIKE :q OR descripcion LIKE :q");
            $st->execute([':q' => "%$q%"]);
            return (int)$st->fetchColumn();
        }
        return (int)$db->query("SELECT COUNT(*) FROM niveles")->fetchColumn();
    }

    public static function listar(string $q = '', int $limit = 10, int $offset = 0): array {
        $db = Database::get();
        if ($q !== '') {
            $st = $db->prepare("SELECT id, nombre, descripcion, orden
                                FROM niveles
                                WHERE nombre LIKE :q OR descripcion LIKE :q
                                ORDER BY orden ASC, nombre ASC
                                LIMIT :lim OFFSET :off");
            $st->bindValue(':q', "%$q%", PDO::PARAM_STR);
        } else {
            $st = $db->prepare("SELECT id, nombre, descripcion, orden
                                FROM niveles
                                ORDER BY orden ASC, nombre ASC
                                LIMIT :lim OFFSET :off");
        }
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public static function obtener(int $id): ?array {
        $db = Database::get();
        $st = $db->prepare("SELECT id, nombre, descripcion, orden FROM niveles WHERE id = :id");
        $st->execute([':id'=>$id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public static function crear(array $d, int $usuarioId): int {
        [$d, $err] = self::validar($d);
        if ($err) throw new InvalidArgumentException(json_encode($err));

        $db = Database::get();
        $st = $db->prepare("INSERT INTO niveles (nombre, descripcion, orden) VALUES (:n,:d,:o)");
        $st->execute([':n'=>$d['nombre'], ':d'=>$d['descripcion'] ?: null, ':o'=>$d['orden']]);
        $id = (int)$db->lastInsertId();
        Audit::log($usuarioId, 'CREAR', 'NIVEL', (string)$id, "Creado nivel {$d['nombre']}");
        return $id;
    }

    public static function actualizar(int $id, array $d, int $usuarioId): void {
        [$d, $err] = self::validar($d, true);
        if ($err) throw new InvalidArgumentException(json_encode($err));
        $db = Database::get();
        $st = $db->prepare("UPDATE niveles SET nombre=:n, descripcion=:d, orden=:o WHERE id=:id");
        $st->execute([':n'=>$d['nombre'], ':d'=>$d['descripcion'] ?: null, ':o'=>$d['orden'], ':id'=>$id]);
        Audit::log($usuarioId, 'EDITAR', 'NIVEL', (string)$id, "Editado nivel {$d['nombre']}");
    }

    public static function eliminar(int $id, int $usuarioId): void {
        $db = Database::get();
        try {
            $db->beginTransaction();
            $st = $db->prepare("DELETE FROM niveles WHERE id = :id");
            $st->execute([':id'=>$id]);
            $db->commit();
            Audit::log($usuarioId, 'ELIMINAR', 'NIVEL', (string)$id, "Eliminado nivel $id");
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            // Si hay FKs (p.ej. cursos.nivel_id) MySQL lanzará error → lo traducimos:
            throw new RuntimeException('No se puede eliminar: el nivel está en uso.');
        }
    }
}
