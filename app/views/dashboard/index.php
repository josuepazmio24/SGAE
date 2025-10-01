<?php
// Atajos (rutas) y estado


$modulos_pend = [
  ['nombre'=>'Profesores',           'nota'=>'CRUD'],
  ['nombre'=>'Apoderados',           'nota'=>'CRUD'],
  ['nombre'=>'Alumno–Apoderado',     'nota'=>'Vinculación'],
  ['nombre'=>'Periodos',             'nota'=>'CRUD (opcional en Evaluaciones)'],
];
?>
<div class="row g-3">
  <div class="col-12 col-lg-4">
    <div class="card shadow-sm h-100">
      <div class="card-body text-center">
        <!-- Logo -->
        <img src="<?= View::e(BASE_URL) ?>/assets/img/logo.png" 
             alt="Logo SGAE" 
             class="mb-3" 
             style="max-width:100px;">

        <h6 class="text-muted mb-2">Bienvenido</h6>
        <h4 class="mb-1">SGAE</h4>
        <div class="small text-muted">Plataforma de gestión escolar</div>

        <hr>
        <div class="d-flex align-items-center justify-content-center gap-2 small">
          <span class="badge bg-success">Operativo</span>
          <span class="badge bg-secondary">Pendiente</span>
        </div>

        <?php if (!empty($_SESSION['user'])): ?>
          <hr>
          <div class="small">
            <div class="text-muted">Sesión:</div>
            <div>👤 <strong><?= View::e($_SESSION['user']['username']) ?></strong> (<?= View::e($_SESSION['user']['rol']) ?>)</div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <h6 class="mb-3">Módulos operativos</h6>

      

        <hr class="my-4">

        <h6 class="mb-3">Próximos módulos</h6>
        <div class="row g-2">
          <?php foreach ($modulos_pend as $m): ?>
            <div class="col-12 col-sm-6 col-md-6">
              <div class="border rounded p-3 h-100">
                <div class="d-flex align-items-center justify-content-between">
                  <strong><?= View::e($m['nombre']) ?></strong>
                  <span class="badge bg-secondary">Pendiente</span>
                </div>
                <?php if (!empty($m['nota'])): ?>
                  <div class="small text-muted mt-1"><?= View::e($m['nota']) ?></div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <hr class="my-4">
<hr class="my-4">

<h6 class="mb-3">Accesos rápidos</h6>
<div class="row g-3">
  <!-- Acceso rápido: Planilla de notas por sección -->
  <div class="col-12 col-md-6">
    <div class="border rounded p-3 h-100">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <strong>Planilla de notas por sección</strong>
        <span class="badge bg-primary">Nuevo</span>
      </div>
      <form class="row g-2" method="get" action="<?= View::e(BASE_URL) ?>/index.php">
        <input type="hidden" name="r" value="secciones/planillaNotas">
        <div class="col-8">
          <label class="form-label small mb-1">ID de la sección</label>
          <input type="number" name="id" class="form-control" placeholder="Ej: 1" required>
        </div>
        <div class="col-4 d-flex align-items-end">
          <button class="btn btn-primary w-100">Abrir</button>
        </div>
      </form>
    
    </div>
  </div>

  <!-- Acceso sugerido: Ir al listado de secciones -->
  <div class="col-12 col-md-6">
    <a class="text-decoration-none" href="<?= View::e(BASE_URL) ?>/index.php?r=secciones/index">
      <div class="border rounded p-3 h-100 hover-shadow">
        <div class="d-flex align-items-center justify-content-between">
          <strong>Ver listado de Secciones</strong>
          <span class="badge bg-success">OK</span>
        </div>
        <div class="small text-muted mt-1">
          Elige una sección y usa el botón “Planilla de notas”.
        </div>
      </div>
    </a>
  </div>
</div>

        <div class="small text-muted">
          Tip: agrega accesos directos aquí a las vistas que uses más (por ejemplo, “Planilla por Sección” o “Tomar asistencia hoy”).
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  .hover-shadow:hover { 
    box-shadow: 0 .25rem .5rem rgba(0,0,0,.075); 
    transition: box-shadow .2s; 
  }
</style>
