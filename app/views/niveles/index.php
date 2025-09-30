<div class="d-flex align-items-center justify-content-between mb-3">
  <h5 class="mb-0">Niveles</h5>
  <a class="btn btn-primary" href="<?= View::e(BASE_URL) ?>/index.php?r=niveles/crear">Nuevo nivel</a>
</div>

<?php if (!empty($_GET['ok'])): ?>
  <div class="alert alert-success">Operación realizada con éxito.</div>
<?php endif; ?>
<?php if (!empty($_GET['error'])): ?>
  <div class="alert alert-danger"><?= View::e($_GET['error']) ?></div>
<?php endif; ?>

<form class="row g-2 mb-3" method="get" action="<?= View::e(BASE_URL) ?>/index.php">
  <input type="hidden" name="r" value="niveles/index">
  <div class="col-sm-6 col-lg-4">
    <input type="text" name="q" value="<?= View::e($q ?? '') ?>" class="form-control" placeholder="Buscar por nombre o descripción">
  </div>
  <div class="col-auto">
    <button class="btn btn-outline-secondary">Buscar</button>
  </div>
</form>

<div class="card shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle">
        <thead class="table-light">
          <tr>
            <th style="width:80px;">ID</th>
            <th>Nombre</th>
            <th>Descripción</th>
            <th style="width:120px;">Orden</th>
            <th style="width:160px;"></th>
          </tr>
        </thead>
        <tbody>
        <?php if (!empty($niveles)): ?>
          <?php foreach ($niveles as $n): ?>
            <tr>
              <td><?= View::e((string)$n['id']) ?></td>
              <td><?= View::e($n['nombre']) ?></td>
              <td><?= View::e($n['descripcion'] ?? '') ?></td>
              <td><?= View::e((string)$n['orden']) ?></td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-primary" href="<?= View::e(BASE_URL) ?>/index.php?r=niveles/editar&id=<?= View::e((string)$n['id']) ?>">Editar</a>
                <form class="d-inline" method="post" action="<?= View::e(BASE_URL) ?>/index.php?r=niveles/eliminar" onsubmit="return confirm('¿Eliminar nivel?');">
                  <input type="hidden" name="id" value="<?= View::e((string)$n['id']) ?>">
                  <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="5" class="text-center text-muted py-4">Sin resultados</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php if (($pages ?? 1) > 1): ?>
<nav class="mt-3">
  <ul class="pagination">
    <?php for ($i=1; $i<=$pages; $i++): ?>
      <?php $active = ($i === (int)$page) ? ' active' : ''; ?>
      <li class="page-item<?= $active ?>">
        <a class="page-link" href="<?= View::e(BASE_URL) ?>/index.php?r=niveles/index&q=<?= urlencode($q ?? '') ?>&page=<?= $i ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>
