<div class="d-flex align-items-center justify-content-between mb-3">
  <h5 class="mb-0">Calificaciones</h5>
  <a class="btn btn-primary" href="<?= View::e(BASE_URL) ?>/index.php?r=calificaciones/crear">Nueva calificación</a>
</div>

<?php if (!empty($_GET['ok'])): ?>
  <div class="alert alert-success">Operación realizada con éxito.</div>
<?php endif; ?>
<?php if (!empty($_GET['error'])): ?>
  <div class="alert alert-danger"><?= View::e($_GET['error']) ?></div>
<?php endif; ?>

<form class="row g-2 mb-3" method="get" action="<?= View::e(BASE_URL) ?>/index.php">
  <input type="hidden" name="r" value="calificaciones/index">
  <div class="col-12 col-lg-3">
    <input type="text" name="q" value="<?= View::e($q ?? '') ?>" class="form-control" placeholder="Buscar por alumno">
  </div>
  <div class="col-12 col-sm-6 col-lg-3">
    <select class="form-select" name="seccion" onchange="this.form.submit()">
      <option value="">Sección</option>
      <?php foreach (($secciones ?? []) as $s): ?>
        <option value="<?= (int)$s['id'] ?>" <?= (isset($sec) && (int)$sec===(int)$s['id'])?'selected':'' ?>>
          <?= View::e($s['label']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-12 col-sm-6 col-lg-3">
    <select class="form-select" name="evaluacion">
      <option value="">Evaluación</option>
      <?php foreach (($evals ?? []) as $e): ?>
        <option value="<?= (int)$e['id'] ?>" <?= (isset($ev) && (int)$ev===(int)$e['id'])?'selected':'' ?>>
          <?= View::e($e['nombre']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-sm-3 col-lg-1">
    <input type="number" step="0.1" min="1.0" max="7.0" name="min" value="<?= View::e((string)($min ?? '')) ?>" class="form-control" placeholder="Min">
  </div>
  <div class="col-6 col-sm-3 col-lg-1">
    <input type="number" step="0.1" min="1.0" max="7.0" name="max" value="<?= View::e((string)($max ?? '')) ?>" class="form-control" placeholder="Max">
  </div>
  <div class="col-12 col-lg-1">
    <button class="btn btn-outline-secondary w-100">Filtrar</button>
  </div>
</form>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th>Fecha</th>
          <th>Sección</th>
          <th>Evaluación</th>
          <th>Alumno</th>
          <th>Nota</th>
          <th>Obs.</th>
          <th style="width: 180px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($rows)): ?>
          <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= View::e($r['fecha']) ?></td>
            <td><?= View::e($r['anio'].' '.$r['nivel_nombre'].' '.$r['letra'].' · '.$r['asignatura_nombre'].' ('.$r['asignatura_codigo'].')') ?></td>
            <td><?= View::e($r['evaluacion_nombre']).' ('.$r['tipo'].')' ?></td>
            <td><?= View::e($r['alumno_nombre']).' — RUT '.View::e((string)$r['alumno_rut']) ?></td>
            <td><strong><?= View::e((string)$r['nota']) ?></strong></td>
            <td><?= View::e($r['observacion'] ?? '') ?></td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="<?= View::e(BASE_URL) ?>/index.php?r=calificaciones/editar&id=<?= View::e((string)$r['id']) ?>">Editar</a>
              <form class="d-inline" method="post" action="<?= View::e(BASE_URL) ?>/index.php?r=calificaciones/eliminar" onsubmit="return confirm('¿Eliminar calificación?');">
                <input type="hidden" name="id" value="<?= View::e((string)$r['id']) ?>">
                <button class="btn btn-sm btn-outline-danger">Eliminar</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="7" class="text-center text-muted py-4">Sin resultados</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if (($pages ?? 1) > 1): ?>
<nav class="mt-3">
  <ul class="pagination">
    <?php for ($i=1; $i<=$pages; $i++): ?>
      <?php $active = ($i === (int)$page) ? ' active' : ''; ?>
      <li class="page-item<?= $active ?>">
        <a class="page-link" href="<?= View::e(BASE_URL) ?>/index.php?r=calificaciones/index&q=<?= urlencode($q ?? '') ?>&seccion=<?= urlencode((string)($sec ?? '')) ?>&evaluacion=<?= urlencode((string)($ev ?? '')) ?>&min=<?= urlencode((string)($min ?? '')) ?>&max=<?= urlencode((string)($max ?? '')) ?>&page=<?= $i ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>
