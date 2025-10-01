<div class="d-flex align-items-center justify-content-between mb-3">
  <h5 class="mb-0">Asignaturas</h5>
  <a class="btn btn-primary" href="<?= View::e(BASE_URL) ?>/index.php?r=asignaturas/crear">Nueva asignatura</a>
</div>

<?php if (!empty($_GET['ok'])): ?>
  <div class="alert alert-success">Operación realizada con éxito.</div>
<?php endif; ?>
<?php if (!empty($_GET['error'])): ?>
  <div class="alert alert-danger"><?= View::e($_GET['error']) ?></div>
<?php endif; ?>

<form class="row g-2 mb-3" method="get" action="<?= View::e(BASE_URL) ?>/index.php">
  <input type="hidden" name="r" value="asignaturas/index">
  <div class="col-12 col-md-4">
    <input type="text" name="q" value="<?= View::e($q ?? '') ?>" class="form-control" placeholder="Buscar por nombre o código">
  </div>
  <div class="col-6 col-md-3">
    <select class="form-select" name="nivel">
      <option value="">Nivel</option>
      <?php foreach (($niveles ?? []) as $n): ?>
        <option value="<?= (int)$n['id'] ?>" <?= (isset($nivel) && (int)$nivel===(int)$n['id'])?'selected':'' ?>>
          <?= View::e($n['nombre']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-md-3">
    <select class="form-select" name="activo">
      <option value="">Estado</option>
      <option value="1" <?= ($activo==='1' || $activo===1)?'selected':'' ?>>Activas</option>
      <option value="0" <?= ($activo==='0' || $activo===0)?'selected':'' ?>>Inactivas</option>
    </select>
  </div>
  <div class="col-12 col-md-2">
    <button class="btn btn-outline-secondary w-100">Filtrar</button>
  </div>
</form>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th style="width:90px;">ID</th>
          <th>Nivel</th>
          <th>Código</th>
          <th>Nombre</th>
          <th style="width:110px;">Estado</th>
          <th style="width:160px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($asigs)): ?>
          <?php foreach ($asigs as $a): ?>
          <tr>
            <td><?= View::e((string)$a['id']) ?></td>
            <td><?= View::e($a['nivel_nombre']) ?></td>
            <td><code><?= View::e($a['codigo']) ?></code></td>
            <td><?= View::e($a['nombre']) ?></td>
            <td>
              <?php if ((int)$a['activo'] === 1): ?>
                <span class="badge bg-success">Activa</span>
              <?php else: ?>
                <span class="badge bg-secondary">Inactiva</span>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="<?= View::e(BASE_URL) ?>/index.php?r=asignaturas/editar&id=<?= View::e((string)$a['id']) ?>">Editar</a>
              <form class="d-inline" method="post" action="<?= View::e(BASE_URL) ?>/index.php?r=asignaturas/eliminar" onsubmit="return confirm('¿Eliminar asignatura?');">
                <input type="hidden" name="id" value="<?= View::e((string)$a['id']) ?>">
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
        <a class="page-link" href="<?= View::e(BASE_URL) ?>/index.php?r=asignaturas/index&q=<?= urlencode($q ?? '') ?>&nivel=<?= urlencode((string)($nivel ?? '')) ?>&activo=<?= urlencode((string)($activo ?? '')) ?>&page=<?= $i ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>
