<div class="d-flex align-items-center justify-content-between mb-3">
  <h5 class="mb-0">Evaluaciones</h5>
  <a class="btn btn-primary" href="<?= View::e(BASE_URL) ?>/index.php?r=evaluaciones/crear">Nueva evaluación</a>
</div>

<?php if (!empty($_GET['ok'])): ?>
  <div class="alert alert-success">Operación realizada con éxito.</div>
<?php endif; ?>
<?php if (!empty($_GET['error'])): ?>
  <div class="alert alert-danger"><?= View::e($_GET['error']) ?></div>
<?php endif; ?>

<form class="row g-2 mb-3" method="get" action="<?= View::e(BASE_URL) ?>/index.php">
  <input type="hidden" name="r" value="evaluaciones/index">
  <div class="col-12 col-lg-3">
    <input type="text" name="q" value="<?= View::e($q ?? '') ?>" class="form-control" placeholder="Buscar por nombre/asignatura">
  </div>
  <div class="col-12 col-sm-6 col-lg-3">
    <select class="form-select" name="seccion">
      <option value="">Sección</option>
      <?php foreach (($secciones ?? []) as $s): ?>
        <option value="<?= (int)$s['id'] ?>" <?= (isset($sec) && (int)$sec===(int)$s['id'])?'selected':'' ?>>
          <?= View::e($s['label']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-sm-3 col-lg-2">
    <select class="form-select" name="periodo">
      <option value="">Periodo</option>
      <?php foreach (($periodos ?? []) as $p): ?>
        <option value="<?= (int)$p['id'] ?>" <?= (isset($per) && (int)$per===(int)$p['id'])?'selected':'' ?>>
          <?= View::e($p['nombre']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-sm-3 col-lg-2">
    <select class="form-select" name="tipo">
      <option value="">Tipo</option>
      <?php foreach ($tipos as $t): ?>
        <option value="<?= $t ?>" <?= ($tipo===$t)?'selected':'' ?>><?= $t ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-sm-3 col-lg-1">
    <input type="date" name="desde" value="<?= View::e($desde ?? '') ?>" class="form-control" placeholder="Desde">
  </div>
  <div class="col-6 col-sm-3 col-lg-1">
    <input type="date" name="hasta" value="<?= View::e($hasta ?? '') ?>" class="form-control" placeholder="Hasta">
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
          <th>Nombre</th>
          <th>Tipo</th>
          <th>Pond. (%)</th>
          <th>Estado</th>
          <th style="width: 180px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($rows)): ?>
          <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= View::e($r['fecha']) ?></td>
            <td><?= View::e($r['anio'].' '.$r['nivel_nombre'].' '.$r['letra'].' · '.$r['asignatura_nombre'].' ('.$r['asignatura_codigo'].')') ?></td>
            <td><?= View::e($r['nombre']) ?></td>
            <td><?= View::e($r['tipo']) ?></td>
            <td><?= View::e((string)(float)$r['ponderacion']) ?></td>
            <td>
              <?php if ((int)$r['publicado'] === 1): ?>
                <span class="badge bg-success">Publicado</span>
              <?php else: ?>
                <span class="badge bg-secondary">Borrador</span>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="<?= View::e(BASE_URL) ?>/index.php?r=evaluaciones/editar&id=<?= View::e((string)$r['id']) ?>">Editar</a>
              <form class="d-inline" method="post" action="<?= View::e(BASE_URL) ?>/index.php?r=evaluaciones/eliminar" onsubmit="return confirm('¿Eliminar evaluación? También eliminará calificaciones asociadas.');">
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
        <a class="page-link" href="<?= View::e(BASE_URL) ?>/index.php?r=evaluaciones/index&q=<?= urlencode($q ?? '') ?>&seccion=<?= urlencode((string)($sec ?? '')) ?>&periodo=<?= urlencode((string)($per ?? '')) ?>&tipo=<?= urlencode((string)($tipo ?? '')) ?>&desde=<?= urlencode((string)($desde ?? '')) ?>&hasta=<?= urlencode((string)($hasta ?? '')) ?>&page=<?= $i ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>
