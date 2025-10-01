<div class="d-flex align-items-center justify-content-between mb-3">
  <h5 class="mb-0">Alumnos</h5>
  <a class="btn btn-primary" href="<?= View::e(BASE_URL) ?>/index.php?r=alumnos/crear">Nuevo alumno</a>
</div>

<?php if (!empty($_GET['ok'])): ?>
  <div class="alert alert-success">Operación realizada con éxito.</div>
<?php endif; ?>
<?php if (!empty($_GET['error'])): ?>
  <div class="alert alert-danger"><?= View::e($_GET['error']) ?></div>
<?php endif; ?>

<form class="row g-2 mb-3" method="get" action="<?= View::e(BASE_URL) ?>/index.php">
  <input type="hidden" name="r" value="alumnos/index">
  <div class="col-12 col-lg-6">
    <input type="text" name="q" value="<?= View::e($q ?? '') ?>" class="form-control" placeholder="Buscar por nombre o matrícula">
  </div>
  <div class="col-6 col-lg-3">
    <select class="form-select" name="activo">
      <option value="">Estado</option>
      <option value="1" <?= (($act ?? '')==='1')?'selected':'' ?>>ACTIVO</option>
      <option value="0" <?= (($act ?? '')==='0')?'selected':'' ?>>INACTIVO</option>
    </select>
  </div>
  <div class="col-6 col-lg-3">
    <button class="btn btn-outline-secondary w-100">Filtrar</button>
  </div>
</form>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th>RUT</th>
          <th>Alumno</th>
          <th>Matrícula</th>
          <th>Ingreso</th>
          <th>Estado</th>
          <th style="width: 180px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($rows)): ?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= View::e((string)$r['rut']).'-'.View::e($r['dv']) ?></td>
              <td>
                <?= View::e($r['apellidos'].', '.$r['nombres']) ?>
                <div class="small text-muted"><?= View::e($r['email'] ?? '') ?></div>
              </td>
              <td><?= View::e($r['nro_matricula']) ?></td>
              <td><?= View::e($r['fecha_ingreso'] ?? '—') ?></td>
              <td>
                <?php if ((int)$r['activo'] === 1): ?>
                  <span class="badge bg-success">ACTIVO</span>
                <?php else: ?>
                  <span class="badge bg-secondary">INACTIVO</span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-primary" href="<?= View::e(BASE_URL) ?>/index.php?r=alumnos/editar&rut=<?= View::e((string)$r['rut']) ?>">Editar</a>
                <form class="d-inline" method="post" action="<?= View::e(BASE_URL) ?>/index.php?r=alumnos/eliminar" onsubmit="return confirm('¿Eliminar alumno? Esto puede eliminar en CASCADA matrículas, calificaciones, etc.');">
                  <input type="hidden" name="rut" value="<?= View::e((string)$r['rut']) ?>">
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
        <a class="page-link" href="<?= View::e(BASE_URL) ?>/index.php?r=alumnos/index&q=<?= urlencode($q ?? '') ?>&activo=<?= urlencode((string)($act ?? '')) ?>&page=<?= $i ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>
