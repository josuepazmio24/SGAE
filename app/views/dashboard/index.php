<?php
// Atajos (rutas) y estado
$modulos_ok = [
  ['nombre'=>'Personas',     'ruta'=>'personas/index'],
  ['nombre'=>'Usuarios',     'ruta'=>'usuarios/index'],
  ['nombre'=>'Niveles',      'ruta'=>'niveles/index'],
  ['nombre'=>'Cursos',       'ruta'=>'cursos/index'],
  ['nombre'=>'Asignaturas',  'ruta'=>'asignaturas/index'],
  ['nombre'=>'Secciones',    'ruta'=>'secciones/index'],
  ['nombre'=>'Sesiones',     'ruta'=>'sesiones/index'],         // si aún no tienes el controller, puedes ocultar esta línea
  ['nombre'=>'Asistencias',  'ruta'=>'asistencias/index'],      // ídem
  ['nombre'=>'Evaluaciones', 'ruta'=>'evaluaciones/index'],
  ['nombre'=>'Calificaciones','ruta'=>'calificaciones/index'],  // si sólo cargas planillas desde Evaluaciones, deja el acceso allí
  ['nombre'=>'Alumnos',      'ruta'=>'alumnos/index'],
  ['nombre'=>'Matrículas',   'ruta'=>'matriculas/index'],
];

$modulos_pend = [
  ['nombre'=>'Profesores',           'nota'=>'CRUD'],
  ['nombre'=>'Apoderados',           'nota'=>'CRUD'],
  ['nombre'=>'Alumno–Apoderado',     'nota'=>'Vinculación'],
  ['nombre'=>'Periodos',             'nota'=>'CRUD (opcional en Evaluaciones)'],
  ['nombre'=>'Auditoría (viewer)',   'nota'=>'Listado y filtros'],
];
?>

<div class="row g-3">
  <div class="col-12 col-lg-4">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h6 class="text-muted mb-2">Bienvenido</h6>
        <h4 class="mb-1">SGAE</h4>
        <div class="small text-muted">Plataforma de gestión escolar</div>

        <hr>
        <div class="d-flex align-items-center gap-2 small">
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

        <div class="row g-2">
          <?php foreach ($modulos_ok as $m): ?>
            <div class="col-12 col-sm-6 col-md-4">
              <a class="text-decoration-none" href="<?= View::e(BASE_URL) ?>/index.php?r=<?= View::e($m['ruta']) ?>">
                <div class="border rounded p-3 h-100 hover-shadow">
                  <div class="d-flex align-items-center justify-content-between">
                    <strong><?= View::e($m['nombre']) ?></strong>
                    <span class="badge bg-success">OK</span>
                  </div>
                  <div class="small text-muted mt-1"><?= View::e($m['ruta']) ?></div>
                </div>
              </a>
            </div>
          <?php endforeach; ?>
        </div>

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

        <div class="small text-muted">
          Tip: agrega accesos directos aquí a las vistas que uses más (por ejemplo, “Planilla por Sección” o “Tomar asistencia hoy”).
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  .hover-shadow:hover { box-shadow: 0 .25rem .5rem rgba(0,0,0,.075); transition: box-shadow .2s; }
</style>
