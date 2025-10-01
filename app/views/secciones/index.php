<div class="d-flex align-items-center justify-content-between mb-3">
  <h5 class="mb-0">Carga Horaria </h5>
  <a class="btn btn-primary" href="<?= View::e(BASE_URL) ?>/index.php?r=secciones/crear">Nueva sección</a>


</div>


<?php if (!empty($_GET['ok'])): ?>
  <div class="alert alert-success">Operación realizada con éxito.</div>
<?php endif; ?>
<?php if (!empty($_GET['error'])): ?>
  <div class="alert alert-danger"><?= View::e($_GET['error']) ?></div>
<?php endif; ?>

<form class="row g-2 mb-3" method="get" action="<?= View::e(BASE_URL) ?>/index.php">
  <input type="hidden" name="r" value="secciones/index">
  <div class="col-12 col-lg-3">
    <input type="text" name="q" value="<?= View::e($q ?? '') ?>" class="form-control" placeholder="Buscar por curso, asignatura o profesor">
  </div>
  <div class="col-12 col-sm-4 col-lg-3">
    <select class="form-select" name="curso">
      <option value="">Curso</option>
      <?php foreach (($cursos ?? []) as $c): ?>
        <?php $label = $c['anio'].' '.$c['nivel'].' '.$c['letra']; ?>
        <option value="<?= (int)$c['id'] ?>" <?= (isset($curso) && (int)$curso===(int)$c['id'])?'selected':'' ?>>
          <?= View::e($label) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-12 col-sm-4 col-lg-3">
    <select class="form-select" name="asig">
      <option value="">Asignatura</option>
      <?php foreach (($asigs ?? []) as $a): ?>
        <option value="<?= (int)$a['id'] ?>" <?= (isset($asig) && (int)$asig===(int)$a['id'])?'selected':'' ?>>
          <?= View::e($a['nivel'].' · '.$a['nombre'].' ('.$a['codigo'].')') ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-12 col-sm-4 col-lg-2">
    <select class="form-select" name="prof">
      <option value="">Profesor</option>
      <?php foreach (($profs ?? []) as $p): ?>
        <option value="<?= (int)$p['rut'] ?>" <?= (isset($prof) && (int)$prof===(int)$p['rut'])?'selected':'' ?>>
          <?= View::e($p['nombre']).' (RUT '.View::e((string)$p['rut']).')' ?>
        </option>
      <?php endforeach; ?>
    </select>
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
          <th>Curso</th>
          <th>Asignatura</th>
          <th>Profesor</th>
          <th style="width: 160px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($secciones)): ?>
          <?php foreach ($secciones as $s): ?>
          <tr>
            <td><?= View::e($s['anio'].' '.$s['nivel_nombre'].' '.$s['letra']) ?></td>
            <td><?= View::e($s['asignatura_nombre']).' ('.View::e($s['asignatura_codigo']).')' ?></td>
            <td><?= View::e($s['profesor_nombre']).' — RUT '.View::e((string)$s['profesor_rut']) ?></td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="<?= View::e(BASE_URL) ?>/index.php?r=secciones/editar&id=<?= View::e((string)$s['id']) ?>">Editar</a>
              <form class="d-inline" method="post" action="<?= View::e(BASE_URL) ?>/index.php?r=secciones/eliminar" onsubmit="return confirm('¿Eliminar sección?');">
                <input type="hidden" name="id" value="<?= View::e((string)$s['id']) ?>">
                <button class="btn btn-sm btn-outline-danger">Eliminar</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="4" class="text-center text-muted py-4">Sin resultados</td></tr>
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
        <a class="page-link" href="<?= View::e(BASE_URL) ?>/index.php?r=secciones/index&q=<?= urlencode($q ?? '') ?>&curso=<?= urlencode((string)($curso ?? '')) ?>&asig=<?= urlencode((string)($asig ?? '')) ?>&prof=<?= urlencode((string)($prof ?? '')) ?>&page=<?= $i ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>
