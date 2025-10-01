<div class="d-flex align-items-center justify-content-between mb-3">
  <h5 class="mb-0">Matrículas</h5>
  <a class="btn btn-primary" href="<?= View::e(BASE_URL) ?>/index.php?r=matriculas/crear">Nueva matrícula</a>
</div>

<?php if (!empty($_GET['ok'])): ?>
  <div class="alert alert-success">Operación realizada con éxito.</div>
<?php endif; ?>
<?php if (!empty($_GET['error'])): ?>
  <div class="alert alert-danger"><?= View::e($_GET['error']) ?></div>
<?php endif; ?>

<form class="row g-2 mb-3" method="get" action="<?= View::e(BASE_URL) ?>/index.php">
  <input type="hidden" name="r" value="matriculas/index">
  <div class="col-12 col-lg-4">
    <input type="text" name="q" value="<?= View::e($q ?? '') ?>" class="form-control" placeholder="Buscar por nombre o RUT">
  </div>
  <div class="col-6 col-lg-2">
    <select class="form-select" name="anio" onchange="this.form.submit()">
      <option value="">Año</option>
      <?php foreach (($anios ?? []) as $a): ?>
        <option value="<?= (int)$a ?>" <?= ((string)($anio ?? '')===(string)$a)?'selected':'' ?>><?= (int)$a ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-lg-3">
    <select class="form-select" name="curso_id">
      <option value="">Curso</option>
      <?php foreach (($cursos ?? []) as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= ((int)($cursoId ?? 0)===(int)$c['id'])?'selected':'' ?>>
          <?= (int)$c['anio'] ?> · <?= View::e($c['nivel']) ?> <?= View::e($c['letra']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-lg-2">
    <select class="form-select" name="estado">
      <option value="">Estado</option>
      <?php foreach (['VIGENTE','RETIRADO','EGRESADO'] as $e): ?>
        <option value="<?= $e ?>" <?= ($estado===$e)?'selected':'' ?>><?= $e ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-lg-1">
    <button class="btn btn-outline-secondary w-100">Filtrar</button>
  </div>
</form>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th>Alumno</th>
          <th>Curso</th>
          <th>Fecha</th>
          <th>Estado</th>
          <th style="width: 200px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($rows)): foreach ($rows as $r): ?>
          <tr>
            <td>
              <?= View::e($r['apellidos'].', '.$r['nombres']) ?><br>
              <span class="text-muted small">RUT <?= View::e((string)$r['alumno_rut']) ?>-<?= View::e($r['dv']) ?></span>
            </td>
            <td><?= (int)$r['anio'] ?> · <?= View::e($r['nivel']) ?> <?= View::e($r['letra']) ?></td>
            <td><?= View::e($r['fecha_matricula']) ?></td>
            <td>
              <?php if ($r['estado']==='VIGENTE'): ?>
                <span class="badge bg-success">VIGENTE</span>
              <?php elseif ($r['estado']==='RETIRADO'): ?>
                <span class="badge bg-warning text-dark">RETIRADO</span>
              <?php else: ?>
                <span class="badge bg-secondary">EGRESADO</span>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="<?= View::e(BASE_URL) ?>/index.php?r=matriculas/editar&id=<?= (int)$r['id'] ?>">Editar</a>
              <form class="d-inline" method="post" action="<?= View::e(BASE_URL) ?>/index.php?r=matriculas/eliminar" onsubmit="return confirm('¿Eliminar matrícula?');">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="btn btn-sm btn-outline-danger">Eliminar</button>
              </form>
            </td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="5" class="text-center text-muted py-4">Sin resultados</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if (($pages ?? 1) > 1): ?>
<nav class="mt-3">
  <ul class="pagination">
    <?php for ($i=1; $i<=$pages; $i++): $active = ($i===(int)$page)?' active':''; ?>
      <li class="page-item<?= $active ?>">
        <a class="page-link" href="<?= View::e(BASE_URL) ?>/index.php?r=matriculas/index&q=<?= urlencode($q ?? '') ?>&anio=<?= urlencode((string)($anio ?? '')) ?>&curso_id=<?= urlencode((string)($cursoId ?? '')) ?>&estado=<?= urlencode((string)($estado ?? '')) ?>&page=<?= $i ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>
