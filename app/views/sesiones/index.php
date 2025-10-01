<div class="d-flex align-items-center justify-content-between mb-3">
  <h5 class="mb-0">Leccionario</h5>
  <a class="btn btn-primary" href="<?= View::e(BASE_URL) ?>/index.php?r=sesiones/crear">Nueva Lección</a>
</div>

<?php if (!empty($_GET['ok'])): ?>
  <div class="alert alert-success">Operación realizada con éxito.</div>
<?php endif; ?>
<?php if (!empty($_GET['error'])): ?>
  <div class="alert alert-danger"><?= View::e($_GET['error']) ?></div>
<?php endif; ?>

<form class="row g-2 mb-3" method="get" action="<?= View::e(BASE_URL) ?>/index.php">
  <input type="hidden" name="r" value="sesiones/index">
  <div class="col-12 col-md-4">
    <input type="text" name="q" value="<?= View::e($q ?? '') ?>" class="form-control" placeholder="Buscar por tema/asignatura/profesor">
  </div>
  <div class="col-12 col-sm-4 col-md-3">
    <select class="form-select" name="seccion">
      <option value="">Sección</option>
      <?php foreach (($secciones ?? []) as $s): ?>
        <option value="<?= (int)$s['id'] ?>" <?= (isset($sec) && (int)$sec===(int)$s['id'])?'selected':'' ?>>
          <?= View::e($s['label']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-sm-2 col-md-2">
    <input type="date" name="desde" value="<?= View::e($desde ?? '') ?>" class="form-control" placeholder="Desde">
  </div>
  <div class="col-6 col-sm-2 col-md-2">
    <input type="date" name="hasta" value="<?= View::e($hasta ?? '') ?>" class="form-control" placeholder="Hasta">
  </div>
  <div class="col-12 col-sm-2 col-md-1">
    <button class="btn btn-outline-secondary w-100">Filtrar</button>
  </div>
</form>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th>Fecha</th>
          <th>Bloque</th>
          <th>Sección</th>
          <th>Tema</th>
          <th style="width: 160px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($sesiones)): ?>
          <?php foreach ($sesiones as $s): ?>
          <tr>
            <td><?= View::e($s['fecha']) ?></td>
            <td><?= $s['bloque'] ? View::e($s['bloque']) : '—' ?></td>
            <td><?= View::e($s['anio'].' '.$s['nivel_nombre'].' '.$s['letra'].' · '.$s['asignatura_nombre'].' ('.$s['asignatura_codigo'].') · '.$s['profesor_nombre']) ?></td>
            <td><?= View::e($s['tema'] ?? '') ?></td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="<?= View::e(BASE_URL) ?>/index.php?r=sesiones/editar&id=<?= View::e((string)$s['id']) ?>">Editar</a>
              <form class="d-inline" method="post" action="<?= View::e(BASE_URL) ?>/index.php?r=sesiones/eliminar" onsubmit="return confirm('¿Eliminar sesión?');">
                <input type="hidden" name="id" value="<?= View::e((string)$s['id']) ?>">
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

<?php if (($pages ?? 1) > 1): ?>
<nav class="mt-3">
  <ul class="pagination">
    <?php for ($i=1; $i<=$pages; $i++): ?>
      <?php $active = ($i === (int)$page) ? ' active' : ''; ?>
      <li class="page-item<?= $active ?>">
        <a class="page-link" href="<?= View::e(BASE_URL) ?>/index.php?r=sesiones/index&q=<?= urlencode($q ?? '') ?>&seccion=<?= urlencode((string)($sec ?? '')) ?>&desde=<?= urlencode((string)($desde ?? '')) ?>&hasta=<?= urlencode((string)($hasta ?? '')) ?>&page=<?= $i ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>
