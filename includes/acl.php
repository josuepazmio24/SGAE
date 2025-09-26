<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

if (!function_exists('acl_can')) {
    function acl_can(PDO $pdo, string $rol, string $recurso, string $accion): bool {
        $rol = strtolower(trim($rol));
        if ($rol === 'admin') return true;

        $recurso = strtolower(trim($recurso));
        $accion  = strtolower(trim($accion));

        $st = $pdo->prepare("SELECT id_permiso FROM permisos WHERE recurso=:r AND accion=:a");
        $st->execute([':r'=>$recurso, ':a'=>$accion]);
        $pid = (int)($st->fetchColumn() ?: 0);
        if ($pid <= 0) return false;

        $st2 = $pdo->prepare("SELECT 1 FROM rol_permiso WHERE rol=:rol AND id_permiso=:pid LIMIT 1");
        $st2->execute([':rol'=>$rol, ':pid'=>$pid]);
        return (bool)$st2->fetchColumn();
    }
}

if (!function_exists('acl_require')) {
    function acl_require(PDO $pdo, string $rol, string $recurso, string $accion): void {
        if (!acl_can($pdo, $rol, $recurso, $accion)) {
            http_response_code(403);
            die('Acceso denegado');
        }
    }
}

if (!function_exists('acl_seed_defaults')) {
    function acl_seed_defaults(PDO $pdo): void {
        $count = (int)$pdo->query("SELECT COUNT(*) FROM permisos")->fetchColumn();
        if ($count > 0) return;

        $defaults = [
            ['usuarios','view','Usuarios: ver'],
            ['usuarios','manage','Usuarios: administrar'],
            ['alumnos','view','Alumnos: ver'],
            ['alumnos','manage','Alumnos: administrar'],
            ['cursos','view','Cursos: ver'],
            ['cursos','manage','Cursos: administrar'],
            ['profesores','view','Profesores: ver'],
            ['profesores','manage','Profesores: administrar'],
            ['asignaturas','view','Asignaturas: ver'],
            ['asignaturas','manage','Asignaturas: administrar'],
            ['config','manage','Configuración: administrar'],
            ['permisos','manage','Permisos: administrar'],
        ];

        $ins = $pdo->prepare("INSERT INTO permisos (recurso, accion, etiqueta) VALUES (:r,:a,:e)");
        foreach ($defaults as [$r,$a,$e]) {
            $ins->execute([':r'=>$r, ':a'=>$a, ':e'=>$e]);
        }
    }
}
