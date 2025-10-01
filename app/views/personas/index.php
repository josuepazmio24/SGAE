<div class="d-flex align-items-center justify-content-between mb-3">
  <h5 class="mb-0">Personas</h5>
  <a class="btn btn-primary" href="<?= View::e(BASE_URL) ?>/index.php?r=personas/crear">Nueva persona</a>
</div>

<?php if (!empty($_GET['ok'])): ?>
  <div class="alert alert-success">Operación realizada con éxito.</div>
<?php endif; ?>
<?php if (!empty($_GET['error'])): ?>
  <div class="alert alert-danger"><?= View::e($_GET['error']) ?></div>
<?php endif; ?>

<form class="row g-2 mb-3" method="get" action="<?= View::e(BASE_URL) ?>/index.php">
  <input type="hidden" name="r" value="personas/index">
  <div class="col-12 col-lg-4">
    <input type="text" name="q" value="<?= View::e($q ?? '') ?>" class="form-control" placeholder="Buscar por nombre o email">
  </div>
  <div class="col-6 col-lg-3">
    <select class="form-select" name="tipo">
      <option value="">Tipo</option>
      <?php foreach (($tipos ?? []) as $t): ?>
        <option value="<?= $t ?>" <?= ($tipo===$t)?'selected':'' ?>><?= $t ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-lg-2">
    <select class="form-select" name="sexo">
      <option value="">Sexo</option>
      <?php foreach (($sexos ?? []) as $s): ?>
        <option value="<?= $s ?>" <?= ($sexo===$s)?'selected':'' ?>><?= $s ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-12 col-lg-2">
    <button class="btn btn-outline-secondary w-100">Filtrar</button>
  </div>
</form>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th>RUT</th>
          <th>Nombre</th>
          <th>Email</th>
          <th>Sexo</th>
          <th>Tipo</th>
          <th>Nacimiento</th>
          <th style="width: 180px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($rows)): ?>
          <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= View::e((string)$r['rut']).'-'.View::e($r['dv']) ?></td>
            <td><?= View::e($r['apellidos'].', '.$r['nombres']) ?></td>
            <td><?= View::e($r['email'] ?? '') ?></td>
            <td><?= View::e($r['sexo'] ?? '—') ?></td>
            <td><?= View::e($r['tipo_persona'] ?? '—') ?></td>
            <td><?= View::e($r['fecha_nac'] ?? '—') ?></td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="<?= View::e(BASE_URL) ?>/index.php?r=personas/editar&rut=<?= View::e((string)$r['rut']) ?>">Editar</a>
              <form class="d-inline" method="post" action="<?= View::e(BASE_URL) ?>/index.php?r=personas/eliminar" onsubmit="return confirm('Esta acción puede eliminar en CASCADA registros asociados (usuario/alumno/profesor/apoderado). ¿Desea continuar?');">
                <input type="hidden" name="rut" value="<?= View::e((string)$r['rut']) ?>">
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
        <a class="page-link" href="<?= View::e(BASE_URL) ?>/index.php?r=personas/index&q=<?= urlencode($q ?? '') ?>&tipo=<?= urlencode((string)($tipo ?? '')) ?>&sexo=<?= urlencode((string)($sexo ?? '')) ?>&page=<?= $i ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>
