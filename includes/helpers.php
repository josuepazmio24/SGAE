<?php
// includes/helpers.php
declare(strict_types=1);

// Evita redefinir si ya existen
if (!function_exists('tableExists')) {
    function tableExists(PDO $pdo, string $table): bool {
        $sql = "SELECT COUNT(*) FROM information_schema.tables 
                WHERE table_schema = DATABASE() AND table_name = :t LIMIT 1";
        $st = $pdo->prepare($sql);
        $st->execute([':t' => $table]);
        return $st->fetchColumn() > 0;
    }
}

if (!function_exists('tableCount')) {
    function tableCount(PDO $pdo, string $table): int {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) return 0;
        $sql = "SELECT COUNT(*) AS c FROM $table";
        $st = $pdo->query($sql);
        $row = $st->fetch();
        return (int)($row['c'] ?? 0);
    }
}

if (!function_exists('limpiar_ident')) {
    function limpiar_ident(string $s): string {
        $s = strtolower(trim($s));
        $s = preg_replace('/[^a-z0-9_]/', '_', $s);
        $s = preg_replace('/_+/', '_', $s);
        return trim($s, '_');
    }
}

if (!function_exists('map_tipo_sql')) {
    function map_tipo_sql(string $tipo, ?int $len = null): string {
        $tipo = strtolower($tipo);
        switch ($tipo) {
            case 'int': return 'INT';
            case 'bigint': return 'BIGINT';
            case 'decimal': return 'DECIMAL(10,2)';
            case 'date': return 'DATE';
            case 'datetime': return 'DATETIME';
            case 'text': return 'TEXT';
            case 'varchar':
            default:
                $len = $len && $len>0 && $len<=1000 ? $len : 255;
                return "VARCHAR($len)";
        }
    }
}
