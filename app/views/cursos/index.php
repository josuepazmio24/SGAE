<div class="d-flex align-items-center justify-content-between mb-3">
  <h5 class="mb-0">Cursos</h5>
  <a class="btn btn-primary" href="<?= View::e(BASE_URL) ?>/index.php?r=cursos/crear">Nuevo curso</a>
</div>

<?php if (!empty($_GET['ok'])): ?>
  <div class="alert alert-success">Operación realizada con éxito.</div>
<?php endif; ?>
<?php if (!empty($_GET['error'])): ?>
  <div class="alert alert-danger"><?= View::e($_GET['error']) ?></div>
<?php endif; ?>

<form class="row g-2 mb-3" method="get" action="<?= View::e(BASE_URL) ?>/index.php">
  <input type="hidden" name="r" value="cursos/index">
  <div class="col-12 col-sm-4">
    <input type="text" name="q" value="<?= View::e($q ?? '') ?>" class="form-control" placeholder="Buscar por nivel o jefe de curso">
  </div>
  <div class="col-6 col-sm-3">
    <select class="form-select" name="anio">
      <option value="">Año</option>
      <?php foreach (($aniosSel ?? []) as $a): ?>
        <option value="<?= $a ?>" <?= (isset($anio) && $anio==$a)?'selected':'' ?>><?= $a ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-sm-3">
    <select class="form-select" name="nivel">
      <option value="">Nivel</option>
      <?php foreach (($niveles ?? []) as $n): ?>
        <option value="<?= (int)$n['id'] ?>" <?= (isset($nivelF) && (int)$nivelF===(int)$n['id'])?'selected':'' ?>>
          <?= View::e($n['nombre']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-12 col-sm-2">
    <button class="btn btn-outline-secondary w-100">Filtrar</button>
  </div>
</form>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th style="width:90px;">Año</th>
          <th>Nivel</th>
          <th style="width:90px;">Letra</th>
          <th style="width:120px;">Jornada</th>
          <th>Jefe de curso</th>
          <th style="width:160px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($cursos)): ?>
          <?php foreach ($cursos as $c): ?>
          <tr>
            <td><?= View::e((string)$c['anio']) ?></td>
            <td><?= View::e($c['nivel_nombre']) ?></td>
            <td><?= View::e($c['letra']) ?></td>
            <td><?= View::e($c['jornada']) ?></td>
            <td><?= View::e($c['jefe_nombre'] ?? '—') ?></td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="<?= View::e(BASE_URL) ?>/index.php?r=cursos/editar&id=<?= View::e((string)$c['id']) ?>">Editar</a>
              <form class="d-inline" method="post" action="<?= View::e(BASE_URL) ?>/index.php?r=cursos/eliminar" onsubmit="return confirm('¿Eliminar curso?');">
                <input type="hidden" name="id" value="<?= View::e((string)$c['id']) ?>">
                <button class="btn btn-sm btn-outline-danger">Eliminar</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="6" class="text-center text-muted py-4">Sin resultados</td></tr>
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
        <a class="page-link" href="<?= View::e(BASE_URL) ?>/index.php?r=cursos/index&q=<?= urlencode($q ?? '') ?>&anio=<?= urlencode((string)($anio ?? '')) ?>&nivel=<?= urlencode((string)($nivelF ?? '')) ?>&page=<?= $i ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>
