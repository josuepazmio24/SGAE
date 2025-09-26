<?php
// public/_layout_top.php
require_once __DIR__ . '/../includes/bootstrap.php'; // debe hacer require_login() y $pdo
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/acl.php';

// Datos de usuario/rol desde la sesión
$u           = current_user() ?? ['nombre' => 'Usuario', 'rol' => ''];
$nombreUser  = htmlspecialchars($u['nombre'] ?? 'Usuario');
$rolUsuario  = user_role(); // siempre string ('' si no hay)

// Si por algún motivo no hay rol pero sí sesión, puedes forzar un fallback o bloquear.
// Lo más seguro: sin rol => no mostrar nada especial; acl_can devolverá false para todo excepto admin por convención.

// Chequeos de permisos (menu visible según ACL)
$canDashboard    = true; // dashboard visible para todos logeados
$canAsignaturas  = ($rolUsuario !== '') && acl_can($pdo, $rolUsuario, 'asignaturas', 'view');
$canAlumnos      = ($rolUsuario !== '') && acl_can($pdo, $rolUsuario, 'alumnos',     'view');
$canProfesores   = ($rolUsuario !== '') && acl_can($pdo, $rolUsuario, 'profesores',  'view');
$canCursos       = ($rolUsuario !== '') && acl_can($pdo, $rolUsuario, 'cursos',      'view');
$canUsuarios     = ($rolUsuario !== '') && acl_can($pdo, $rolUsuario, 'usuarios',    'view');
$canPerfil       = true; // Mi Perfil siempre visible
$canPermisos     = ($rolUsuario !== '') && acl_can($pdo, $rolUsuario, 'permisos',    'manage');

// Base URL
$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($TITLE ?? 'SGAE') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color:#f8f9fa; }
    .sidebar { width:240px; min-height:100vh; background:#fff; border-right:1px solid #ddd; position:fixed; top:0; left:0; padding:1rem; }
    .content { margin-left:240px; padding:1.5rem; }
    .sidebar a { display:block; padding:.5rem 0; color:#000; text-decoration:none; }
    .sidebar a:hover { font-weight:600; }
    .topbar { position:fixed; left:240px; right:0; top:0; height:56px; background:#fff; border-bottom:1px solid #ddd; display:flex; justify-content:flex-end; align-items:center; padding:0 1rem; z-index:1000; }
    .content-body { margin-top:70px; }
    .mod-frame { width:100%; height:76vh; border:0; background:#fff; }
  </style>
</head>
<body>

<div class="sidebar">
  <h5 class="mb-3">SGAE<br><small class="text-muted">Gestión Académica</small></h5>
  <a class="navbar-brand" href="#">
    <img src="asset/img/logo.jpg" alt="Logo de la empresa" width="30" height="30" class="d-inline-block align-top">
    <!-- Tu logo estará aquí -->
  </a>
  <?php if ($canDashboard): ?><a href="<?= $BASE ?>/dashboard.php">Dashboard</a><?php endif; ?>

  <?php if ($canAsignaturas): ?><a href="<?= $BASE ?>/asignaturas.php">Asignaturas</a><?php endif; ?>
  <?php if ($canAlumnos):     ?><a href="<?= $BASE ?>/alumnos.php">Alumnos</a><?php endif; ?>
  <?php if ($canProfesores):  ?><a href="<?= $BASE ?>/profesores.php">Profesores</a><?php endif; ?>
  <?php if ($canCursos):      ?><a href="<?= $BASE ?>/cursos.php">Cursos</a><?php endif; ?>
  <?php if ($canUsuarios):    ?><a href="<?= $BASE ?>/usuarios.php">Usuarios</a><?php endif; ?>
  <?php if ($canPerfil):      ?><a href="<?= $BASE ?>/configuracion.php">Mi Perfil</a><?php endif; ?>
  <?php if ($canPermisos):    ?><a href="<?= $BASE ?>/permisos.php">Permisos (ACL)</a><?php endif; ?>

  <hr>
  <div class="text-muted small mb-2">Módulos</div>
  <?php
    try {
      if (tableExists($pdo,'sgae_modulos')) {
        $mods = $pdo->query("SELECT etiqueta, ruta FROM sgae_modulos ORDER BY etiqueta")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($mods as $m) {
          $etq = htmlspecialchars($m['etiqueta']);
          $rt  = htmlspecialchars($m['ruta']);
          echo '<a href="'.$BASE.'/mod.php?ruta='.$rt.'">'.$etq.'</a>';
        }
      }
    } catch (Throwable $e) { /* silencio */ }
  ?>
</div>

<div class="topbar">
  <span class="me-3">
    <?= $nombreUser ?>
    <?php if ($rolUsuario !== ''): ?>
      <span class="text-muted small">(<?= htmlspecialchars($rolUsuario) ?>)</span>
    <?php endif; ?>
  </span>
  <a href="<?= $BASE ?>/logout.php" class="btn btn-sm btn-outline-danger">Salir</a>
</div>

<div class="content">
  <div class="content-body">
