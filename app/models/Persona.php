<?php
class Persona
{
    // --- Utilidades RUT ---
    public static function normalizarRut(string $rut): int {
        // quita puntos y guiones; solo números
        $r = preg_replace('/[^0-9]/', '', $rut);
        return (int)$r;
    }

    public static function calcularDv(int $rut): string {
        // algoritmo módulo 11
        $s = 1; $m = 0;
        while ($rut > 0) {
            $s = ($s + $rut % 10 * (9 - $m++ % 6)) % 11;
            $rut = intdiv($rut, 10);
        }
        if ($s === 0) return 'K';
        return (string)($s - 1);
    }

    public static function validar(array $d, bool $esEditar=false): array {
        $err = [];

        // Normaliza entradas
        $d['rut']          = isset($d['rut']) ? (int)$d['rut'] : 0;
        $d['dv']           = strtoupper(trim($d['dv'] ?? ''));
        $d['nombres']      = trim($d['nombres'] ?? '');
        $d['apellidos']    = trim($d['apellidos'] ?? '');
        $d['sexo']         = trim($d['sexo'] ?? '');
        $d['fecha_nac']    = trim($d['fecha_nac'] ?? '');
        $d['email']        = trim($d['email'] ?? '');
        $d['telefono']     = trim($d['telefono'] ?? '');
        $d['direccion']    = trim($d['direccion'] ?? '');
        $d['tipo_persona'] = trim($d['tipo_persona'] ?? '');

        if ($d['rut'] <= 0)                $err['rut'] = 'RUT requerido';
        if ($d['dv'] === '')               $err['dv']  = 'DV requerido';
        if ($d['nombres'] === '')          $err['nombres'] = 'Ingrese nombres';
        if ($d['apellidos'] === '')        $err['apellidos'] = 'Ingrese apellidos';

        if ($d['sexo'] !== '' && !in_array($d['sexo'], ['M','F','X'], true))
            $err['sexo'] = 'Sexo inválido';
        if ($d['tipo_persona'] !== '' && !in_array($d['tipo_persona'], ['ALUMNO','PROFESOR','APODERADO','ADMIN'], true))
            $err['tipo_persona'] = 'Tipo inválido';

        if ($d['fecha_nac'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $d['fecha_nac']))
            $err['fecha_nac'] = 'Fecha inválida (YYYY-MM-DD)';

        if ($d['email'] !== '' && !filter_var($d['email'], FILTER_VALIDATE_EMAIL))
            $err['email'] = 'Email inválido';

        // Valida DV
        if ($d['rut'] > 0 && $d['dv'] !== '') {
            $dvOk = self::calcularDv($d['rut']);
            if ($d['dv'] !== $dvOk) {
                $err['dv'] = "DV incorrecto (esperado: $dvOk)";
            }
        }

        // Longitudes
        if (strlen($d['telefono']) > 30)   $err['telefono']  = 'Teléfono máx. 30';
        if (strlen($d['direccion']) > 180) $err['direccion'] = 'Dirección máx. 180';

        return [$d, $err];
    }

    // --- Consultas básicas ---
    public static function contar(string $q='', ?string $tipo=null, ?string $sexo=null): int {
        $db = Database::get();
        $sql = "SELECT COUNT(*) FROM personas WHERE 1=1";
        $p = [];
        if ($q !== '')     { $sql .= " AND (nombres LIKE :q OR apellidos LIKE :q OR email LIKE :q)"; $p[':q']="%$q%"; }
        if ($tipo)         { $sql .= " AND tipo_persona=:t"; $p[':t']=$tipo; }
        if ($sexo)         { $sql .= " AND sexo=:s"; $p[':s']=$sexo; }
        $st = $db->prepare($sql); $st->execute($p);
        return (int)$st->fetchColumn();
    }

    public static function listar(string $q='', int $limit=10, int $offset=0, ?string $tipo=null, ?string $sexo=null): array {
        $db = Database::get();
        $sql = "SELECT rut, dv, nombres, apellidos, sexo, fecha_nac, email, telefono, direccion, tipo_persona, creado_en
                FROM personas WHERE 1=1";
        $p = [];
        if ($q !== '')     { $sql .= " AND (nombres LIKE :q OR apellidos LIKE :q OR email LIKE :q)"; $p[':q']="%$q%"; }
        if ($tipo)         { $sql .= " AND tipo_persona=:t"; $p[':t']=$tipo; }
        if ($sexo)         { $sql .= " AND sexo=:s"; $p[':s']=$sexo; }
        $sql .= " ORDER BY apellidos ASC, nombres ASC LIMIT :lim OFFSET :off";
        $st = $db->prepare($sql);
        foreach ($p as $k=>$v) $st->bindValue($k, $v, PDO::PARAM_STR);
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public static function obtener(int $rut): ?array {
        $db = Database::get();
        $st = $db->prepare("SELECT rut, dv, nombres, apellidos, sexo, fecha_nac, email, telefono, direccion, tipo_persona
                            FROM personas WHERE rut=:rut");
        $st->execute([':rut'=>$rut]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public static function crear(array $d): int {
        [$d,$err] = self::validar($d);
        if ($err) throw new InvalidArgumentException(json_encode($err));

        $db = Database::get();
        $sql = "INSERT INTO personas (rut, dv, nombres, apellidos, sexo, fecha_nac, email, telefono, direccion, tipo_persona)
                VALUES (:rut,:dv,:nom,:ape,:sex,:fec,:email,:tel,:dir,:tipo)";
        $st = $db->prepare($sql);
        try {
            $st->execute([
                ':rut'=>$d['rut'], ':dv'=>$d['dv'], ':nom'=>$d['nombres'], ':ape'=>$d['apellidos'],
                ':sex'=>($d['sexo'] ?: null), ':fec'=>($d['fecha_nac'] ?: null),
                ':email'=>($d['email'] ?: null), ':tel'=>($d['telefono'] ?: null),
                ':dir'=>($d['direccion'] ?: null), ':tipo'=>($d['tipo_persona'] ?: null),
            ]);
        } catch (PDOException $e) {
            if ((int)$e->errorInfo[1] === 1062) {
                // clave duplicada (email único o rut PK)
                if (str_contains($e->getMessage(), 'uq_personas_email')) {
                    throw new RuntimeException('El email ya está registrado.');
                }
                throw new RuntimeException('El RUT ya existe.');
            }
            throw $e;
        }
        Audit::log($_SESSION['user']['id'] ?? 0, 'CREAR', 'PERSONA', (string)$d['rut'], 'Nueva persona');
        return (int)$d['rut'];
    }

    public static function actualizar(int $rut, array $d): void {
        [$d,$err] = self::validar($d, true);
        if ($err) throw new InvalidArgumentException(json_encode($err));

        $db = Database::get();
        $sql = "UPDATE personas SET
                  dv=:dv, nombres=:nom, apellidos=:ape, sexo=:sex, fecha_nac=:fec,
                  email=:email, telefono=:tel, direccion=:dir, tipo_persona=:tipo
                WHERE rut=:rut";
        $st = $db->prepare($sql);
        try {
            $st->execute([
                ':dv'=>$d['dv'], ':nom'=>$d['nombres'], ':ape'=>$d['apellidos'],
                ':sex'=>($d['sexo'] ?: null), ':fec'=>($d['fecha_nac'] ?: null),
                ':email'=>($d['email'] ?: null), ':tel'=>($d['telefono'] ?: null),
                ':dir'=>($d['direccion'] ?: null), ':tipo'=>($d['tipo_persona'] ?: null),
                ':rut'=>$rut
            ]);
        } catch (PDOException $e) {
            if ((int)$e->errorInfo[1] === 1062 && str_contains($e->getMessage(), 'uq_personas_email')) {
                throw new RuntimeException('El email ya está registrado.');
            }
            throw $e;
        }
        Audit::log($_SESSION['user']['id'] ?? 0, 'EDITAR', 'PERSONA', (string)$rut, 'Persona editada');
    }

    public static function eliminar(int $rut): void {
        $db = Database::get();
        try {
            $db->beginTransaction();
            $db->prepare("DELETE FROM personas WHERE rut=:rut")->execute([':rut'=>$rut]);
            $db->commit();
            Audit::log($_SESSION['user']['id'] ?? 0, 'ELIMINAR', 'PERSONA', (string)$rut, 'Persona eliminada');
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw new RuntimeException('No se pudo eliminar la persona.');
        }
    }

    // Para combos
    public static function tipos(): array { return ['ALUMNO','PROFESOR','APODERADO','ADMIN']; }
    public static function sexos(): array { return ['M','F','X']; }
}
