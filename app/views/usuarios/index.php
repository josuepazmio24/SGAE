<div class="d-flex align-items-center justify-content-between mb-3">
  <h5 class="mb-0">Usuarios</h5>
  <a class="btn btn-primary" href="<?= View::e(BASE_URL) ?>/index.php?r=usuarios/crear">Nuevo usuario</a>
</div>

<?php if (!empty($_GET['ok'])): ?>
  <div class="alert alert-success">Operación realizada con éxito.</div>
<?php endif; ?>
<?php if (!empty($_GET['error'])): ?>
  <div class="alert alert-danger"><?= View::e($_GET['error']) ?></div>
<?php endif; ?>

<form class="row g-2 mb-3" method="get" action="<?= View::e(BASE_URL) ?>/index.php">
  <input type="hidden" name="r" value="usuarios/index">
  <div class="col-12 col-lg-4">
    <input type="text" name="q" value="<?= View::e($q ?? '') ?>" class="form-control" placeholder="Buscar por usuario/nombre/email">
  </div>
  <div class="col-6 col-lg-3">
    <select class="form-select" name="rol">
      <option value="">Rol</option>
      <?php foreach (($roles ?? []) as $r): ?>
        <option value="<?= $r ?>" <?= ($rol===$r)?'selected':'' ?>><?= $r ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-lg-3">
    <select class="form-select" name="estado">
      <option value="">Estado</option>
      <?php foreach (($estados ?? []) as $e): ?>
        <option value="<?= $e ?>" <?= ($estado===$e)?'selected':'' ?>><?= $e ?></option>
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
          <th>ID</th>
          <th>Usuario</th>
          <th>Persona</th>
          <th>Rol</th>
          <th>Estado</th>
          <th>Último login</th>
          <th style="width: 230px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($rows)): ?>
          <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= (int)$r['id'] ?></td>
            <td><?= View::e($r['username']) ?></td>
            <td>
              <?= View::e($r['persona_nombre']) ?>
              <div class="text-muted small"><?= View::e($r['email'] ?? '') ?></div>
            </td>
            <td><?= View::e($r['rol']) ?></td>
            <td>
              <?php if ($r['estado']==='ACTIVO'): ?>
                <span class="badge bg-success">ACTIVO</span>
              <?php else: ?>
                <span class="badge bg-secondary">SUSPENDIDO</span>
              <?php endif; ?>
            </td>
            <td><?= View::e($r['ultimo_login'] ?? '—') ?></td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="<?= View::e(BASE_URL) ?>/index.php?r=usuarios/editar&id=<?= (int)$r['id'] ?>">Editar</a>
              <a class="btn btn-sm btn-outline-warning" href="<?= View::e(BASE_URL) ?>/index.php?r=usuarios/password&id=<?= (int)$r['id'] ?>">Contraseña</a>
              <form class="d-inline" method="post" action="<?= View::e(BASE_URL) ?>/index.php?r=usuarios/eliminar" onsubmit="return confirm('¿Eliminar usuario? Si tiene auditorías, no se podrá.');">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
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
        <a class="page-link" href="<?= View::e(BASE_URL) ?>/index.php?r=usuarios/index&q=<?= urlencode($q ?? '') ?>&rol=<?= urlencode((string)($rol ?? '')) ?>&estado=<?= urlencode((string)($estado ?? '')) ?>&page=<?= $i ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>
