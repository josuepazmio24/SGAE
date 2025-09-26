<?php
// public/_layout_top.php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/helpers.php';
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
    .sidebar { width: 240px; min-height: 100vh; background:#fff; border-right:1px solid #ddd; position:fixed; top:0; left:0; padding:1rem; }
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
  <a href="<?= $BASE ?>/dashboard.php">Dashboard</a>
  <a href="<?= $BASE ?>/usuarios.php">Usuarios</a>
  <a href="<?= $BASE ?>/alumnos.php">Alumnos</a>
  <a href="<?= $BASE ?>/cursos.php">Cursos</a>
  <a href="<?= $BASE ?>/configuracion.php">Configuración</a>
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
    } catch (Throwable $e) { /* silencioso */ }
  ?>
</div>

<div class="topbar">
  <span class="me-3"><?= htmlspecialchars($u['nombre']) ?> - Administrador</span>
  <a href="<?= $BASE ?>/logout.php" class="btn btn-sm btn-outline-danger">Salir</a>
</div>

<div class="content">
  <div class="content-body">
