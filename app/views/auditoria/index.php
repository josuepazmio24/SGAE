<div class="d-flex align-items-center justify-content-between mb-3">
  <h5 class="mb-0">Auditoría</h5>
  <div>
    <a class="btn btn-outline-secondary btn-sm"
       href="<?= View::e(BASE_URL) ?>/index.php?r=auditoria/export&<?= http_build_query($f) ?>">
      Exportar CSV
    </a>
  </div>
</div>

<div class="card shadow-sm mb-3">
  <div class="card-body">
    <form method="get" class="row g-2 align-items-end">
      <input type="hidden" name="r" value="auditoria/index">
      <div class="col-md-3">
        <label class="form-label">Buscar (texto / IP / entidad_id)</label>
        <input type="text" name="q" class="form-control" value="<?= View::e($f['q']) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">Usuario</label>
        <input type="text" name="usuario" class="form-control" placeholder="username" value="<?= View::e($f['usuario']) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">Acción</label>
        <select name="accion" class="form-select">
          <option value="">(todas)</option>
          <?php foreach ($acciones as $a): ?>
            <option value="<?= View::e($a) ?>" <?= $f['accion']===$a?'selected':'' ?>><?= View::e($a) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Entidad</label>
        <select name="entidad" class="form-select">
          <option value="">(todas)</option>
          <?php foreach ($entidades as $e): ?>
            <option value="<?= View::e($e) ?>" <?= $f['entidad']===$e?'selected':'' ?>><?= View::e($e) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-1">
        <label class="form-label">Desde</label>
        <input type="date" name="desde" class="form-control" value="<?= View::e($f['desde']) ?>">
      </div>
      <div class="col-md-1">
        <label class="form-label">Hasta</label>
        <input type="date" name="hasta" class="form-control" value="<?= View::e($f['hasta']) ?>">
      </div>
      <div class="col-md-1 d-grid">
        <button class="btn btn-primary">Filtrar</button>
      </div>
    </form>
  </div>
</div>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th style="width: 100px;">Fecha</th>
          <th>Usuario</th>
          <th>Acción</th>
          <th>Entidad</th>
          <th>Entidad ID</th>
          <th>Descripción</th>
          <th style="width: 90px;">IP</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($rows)): foreach ($rows as $r): ?>
          <tr>
            <td class="text-nowrap"><?= View::e(date('Y-m-d H:i', strtotime($r['creado_en']))) ?></td>
            <td>
              <div><strong><?= View::e($r['username']) ?></strong> <span class="badge bg-light text-dark"><?= View::e($r['rol']) ?></span></div>
              <?php if (!empty($r['persona'])): ?>
                <div class="small text-muted"><?= View::e($r['persona']) ?></div>
              <?php endif; ?>
            </td>
            <td><span class="badge bg-secondary"><?= View::e($r['accion']) ?></span></td>
            <td><?= View::e($r['entidad']) ?></td>
            <td><?= View::e($r['entidad_id']) ?></td>
            <td><?= View::e($r['descripcion']) ?></td>
            <td class="text-muted"><?= View::e($r['ip']) ?></td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="7" class="text-center text-muted py-4">Sin movimientos</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php
    $pages = (int)ceil($total / $limit);
    if ($pages < 1) $pages = 1;
    $baseQuery = $f; unset($baseQuery['page']);
  ?>
  <div class="card-footer d-flex align-items-center justify-content-between">
    <div class="small text-muted">
      Total: <?= (int)$total ?> registros
    </div>
    <nav>
      <ul class="pagination pagination-sm mb-0">
        <?php
          $prev = max(1, $page-1);
          $next = min($pages, $page+1);
        ?>
        <li class="page-item <?= $page<=1?'disabled':'' ?>">
          <a class="page-link" href="<?= View::e(BASE_URL) ?>/index.php?<?= http_build_query(['r'=>'auditoria/index','page'=>$prev]+$baseQuery) ?>">«</a>
        </li>
        <?php for ($i=max(1,$page-2); $i<=min($pages,$page+2); $i++): ?>
          <li class="page-item <?= $i===$page?'active':'' ?>">
            <a class="page-link" href="<?= View::e(BASE_URL) ?>/index.php?<?= http_build_query(['r'=>'auditoria/index','page'=>$i]+$baseQuery) ?>"><?= $i ?></a>
          </li>
        <?php endfor; ?>
        <li class="page-item <?= $page>=$pages?'disabled':'' ?>">
          <a class="page-link" href="<?= View::e(BASE_URL) ?>/index.php?<?= http_build_query(['r'=>'auditoria/index','page'=>$next]+$baseQuery) ?>">»</a>
        </li>
      </ul>
    </nav>
  </div>
</div>
