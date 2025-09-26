<?php
// public/permisos.php
declare(strict_types=1);

$TITLE = 'Permisos por Rol';
require __DIR__ . '/_layout_top.php';              // abre layout
require __DIR__ . '/../includes/acl.php';          // acl_can / acl_require

// (Opcional) sembrar por primera vez
// acl_seed_defaults($pdo);

// === Roles del sistema
$ROLES = ['admin','docente','apoderado','alumno'];

// ¿Puede editar? => config.manage
$rolActual   = strtolower($_SESSION['usuario']['rol'] ?? 'alumno');
$puedeEditar = acl_can($pdo, $rolActual, 'config', 'manage');

$flash = ['type'=>null,'msg'=>null];

// --------- Alta permiso ---------
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['accion'] ?? '') === 'crear_permiso') {
  try {
    if (!$puedeEditar) throw new RuntimeException('No autorizado.');
    $recurso = strtolower(trim($_POST['recurso'] ?? ''));
    $accion  = strtolower(trim($_POST['accion_perm'] ?? ''));
    $etiqueta = trim($_POST['etiqueta'] ?? '');
    if ($recurso==='' || $accion==='' || $etiqueta==='') throw new RuntimeException('Todos los campos son obligatorios.');
    if (!preg_match('/^[a-z0-9_\.]+$/',$recurso)) throw new RuntimeException('Recurso inválido (minúsculas/números/_/.).');
    if (!preg_match('/^[a-z0-9_]+$/',$accion)) throw new RuntimeException('Acción inválida (minúsculas/números/_).');

    $st = $pdo->prepare("INSERT INTO permisos (recurso, accion, etiqueta) VALUES (:r,:a,:e)");
    $st->execute([':r'=>$recurso, ':a'=>$accion, ':e'=>$etiqueta]);
    $flash = ['type'=>'success','msg'=>'Permiso creado.'];
  } catch (Throwable $e) { $flash=['type'=>'danger','msg'=>$e->getMessage()]; }
}

// --------- Update permiso ---------
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['accion'] ?? '') === 'actualizar_permiso') {
  try {
    if (!$puedeEditar) throw new RuntimeException('No autorizado.');
    $id = (int)($_POST['id_permiso'] ?? 0);
    $recurso = strtolower(trim($_POST['recurso'] ?? ''));
    $accion  = strtolower(trim($_POST['accion_perm'] ?? ''));
    $etiqueta = trim($_POST['etiqueta'] ?? '');
    if ($id<=0) throw new RuntimeException('ID inválido.');
    if ($recurso==='' || $accion==='' || $etiqueta==='') throw new RuntimeException('Todos los campos son obligatorios.');
    if (!preg_match('/^[a-z0-9_\.]+$/',$recurso)) throw new RuntimeException('Recurso inválido.');
    if (!preg_match('/^[a-z0-9_]+$/',$accion)) throw new RuntimeException('Acción inválida.');

    $st = $pdo->prepare("UPDATE permisos SET recurso=:r, accion=:a, etiqueta=:e WHERE id_permiso=:id");
    $st->execute([':r'=>$recurso, ':a'=>$accion, ':e'=>$etiqueta, ':id'=>$id]);
    $flash = ['type'=>'success','msg'=>'Permiso actualizado.'];
  } catch (Throwable $e) { $flash=['type'=>'danger','msg'=>$e->getMessage()]; }
}

// --------- Delete permiso ---------
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['accion'] ?? '') === 'eliminar_permiso') {
  try {
    if (!$puedeEditar) throw new RuntimeException('No autorizado.');
    $id = (int)($_POST['id_permiso'] ?? 0);
    if ($id<=0) throw new RuntimeException('ID inválido.');

    // gracias al ON DELETE CASCADE es suficiente borrar en permisos,
    // pero si tu FK no tiene cascade, descomenta la limpieza:
    // $pdo->prepare("DELETE FROM rol_permiso WHERE id_permiso=:id")->execute([':id'=>$id]);

    $pdo->prepare("DELETE FROM permisos WHERE id_permiso=:id")->execute([':id'=>$id]);
    $flash = ['type'=>'success','msg'=>'Permiso eliminado.'];
  } catch (Throwable $e) { $flash=['type'=>'danger','msg'=>$e->getMessage()]; }
}

// --------- Guardar matriz grants ---------
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['accion'] ?? '') === 'guardar_matriz') {
  try {
    if (!$puedeEditar) throw new RuntimeException('No autorizado.');

    $ids = $pdo->query("SELECT id_permiso FROM permisos")->fetchAll(PDO::FETCH_COLUMN);
    if ($ids) {
      $in = implode(',', array_map('intval', $ids));
      $pdo->exec("DELETE FROM rol_permiso WHERE id_permiso IN ($in) AND rol <> 'admin'");
    }
    $ins = $pdo->prepare("INSERT IGNORE INTO rol_permiso (rol, id_permiso) VALUES (:rol, :pid)");
    foreach ($ROLES as $rol) {
      if ($rol === 'admin') continue; // admin todo
      $checkeds = array_map('intval', $_POST['perm_'.$rol] ?? []);
      foreach ($checkeds as $pid) $ins->execute([':rol'=>$rol, ':pid'=>$pid]);
    }
    $flash = ['type'=>'success','msg'=>'Permisos actualizados.'];
  } catch (Throwable $e) { $flash=['type'=>'danger','msg'=>$e->getMessage()]; }
}

// --------- Datos + búsqueda ---------
$q = strtolower(trim($_GET['q'] ?? ''));
$permisos = $pdo->query("SELECT id_permiso, recurso, accion, etiqueta FROM permisos ORDER BY recurso, accion")->fetchAll(PDO::FETCH_ASSOC);
$grants = []; foreach ($ROLES as $r) $grants[$r] = [];
$rows = $pdo->query("SELECT rol, id_permiso FROM rol_permiso")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) { $grants[$r['rol']][] = (int)$r['id_permiso']; }

if ($q!=='') {
  $permisos = array_values(array_filter($permisos, function($p) use ($q) {
    return str_contains(strtolower($p['recurso']), $q)
        || str_contains(strtolower($p['accion']),  $q)
        || str_contains(strtolower($p['etiqueta']), $q);
  }));
}
?>
<h4>Permisos por Rol</h4>
<?php if ($flash['type']): ?>
  <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['msg']) ?></div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-8">
    <form class="d-flex mb-3" method="get">
      <input class="form-control me-2" type="search" name="q" placeholder="Buscar por recurso, acción o etiqueta" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
      <button class="btn btn-outline-secondary">Buscar</button>
    </form>

    <form method="post">
      <input type="hidden" name="accion" value="guardar_matriz">
      <div class="table-responsive">
        <table class="table table-bordered align-middle">
          <thead class="table-light">
            <tr>
              <th style="white-space:nowrap">Permiso</th>
              <?php foreach ($ROLES as $rol): ?>
                <th class="text-center" style="width:130px"><?= htmlspecialchars(ucfirst($rol)) ?></th>
              <?php endforeach; ?>
              <th class="text-center" style="width:140px">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$permisos): ?>
              <tr><td colspan="<?= 2+count($ROLES) ?>" class="text-center text-muted">No hay permisos definidos.</td></tr>
            <?php else: foreach ($permisos as $p): ?>
              <tr>
                <td>
                  <div class="fw-semibold"><?= htmlspecialchars($p['etiqueta']) ?></div>
                  <div class="text-muted small"><code><?= htmlspecialchars($p['recurso']) ?>.<?= htmlspecialchars($p['accion']) ?></code></div>
                </td>

                <?php foreach ($ROLES as $rol): ?>
                  <td class="text-center">
                    <?php if ($rol === 'admin'): ?>
                      <span class="badge bg-success">Siempre</span>
                    <?php else:
                      $checked = in_array((int)$p['id_permiso'], $grants[$rol], true);
                    ?>
                      <input type="checkbox" name="perm_<?= htmlspecialchars($rol) ?>[]" value="<?= (int)$p['id_permiso'] ?>"
                             <?= $checked ? 'checked' : '' ?> <?= $puedeEditar ? '' : 'disabled' ?>>
                    <?php endif; ?>
                  </td>
                <?php endforeach; ?>

                <td class="text-center">
                  <button type="button" class="btn btn-sm btn-warning me-1"
                          data-bs-toggle="modal" data-bs-target="#modalEditarPermiso"
                          data-id="<?= (int)$p['id_permiso'] ?>"
                          data-recurso="<?= htmlspecialchars($p['recurso']) ?>"
                          data-accion="<?= htmlspecialchars($p['accion']) ?>"
                          data-etiqueta="<?= htmlspecialchars($p['etiqueta']) ?>"
                          <?= $puedeEditar ? '' : 'disabled' ?>>Editar</button>

                  <button type="button" class="btn btn-sm btn-danger"
                          data-bs-toggle="modal" data-bs-target="#modalEliminarPermiso"
                          data-id="<?= (int)$p['id_permiso'] ?>"
                          data-etiqueta="<?= htmlspecialchars($p['etiqueta']) ?>"
                          <?= $puedeEditar ? '' : 'disabled' ?>>Eliminar</button>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
      <?php if ($puedeEditar): ?>
        <button class="btn btn-primary">Guardar cambios</button>
      <?php else: ?>
        <div class="text-muted small mt-2">No tienes permisos para editar esta matriz.</div>
      <?php endif; ?>
    </form>
  </div>

  <div class="col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h6 class="mb-3">Agregar permiso</h6>
        <form method="post" autocomplete="off">
          <input type="hidden" name="accion" value="crear_permiso">
          <div class="mb-2">
            <label class="form-label">Recurso</label>
            <input class="form-control" name="recurso" placeholder="usuarios, alumnos, cursos..." required>
            <div class="form-text">minúsculas, números, guión bajo</div>
          </div>
          <div class="mb-2">
            <label class="form-label">Acción</label>
            <input class="form-control" name="accion_perm" placeholder="view, manage, create, update, delete..." required>
          </div>
          <div class="mb-3">
            <label class="form-label">Etiqueta</label>
            <input class="form-control" name="etiqueta" placeholder="Usuarios: ver" required>
          </div>
          <button class="btn btn-outline-primary" <?= $puedeEditar ? '' : 'disabled' ?>>Crear permiso</button>
          <?php if (!$puedeEditar): ?><div class="text-muted small mt-2">No autorizado.</div><?php endif; ?>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal EDITAR permiso -->
<div class="modal fade" id="modalEditarPermiso" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="post" autocomplete="off">
      <input type="hidden" name="accion" value="actualizar_permiso">
      <input type="hidden" name="id_permiso" id="edit_id_permiso">
      <div class="modal-header">
        <h5 class="modal-title">Editar Permiso</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label">Recurso</label>
          <input class="form-control" name="recurso" id="edit_recurso" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Acción</label>
          <input class="form-control" name="accion_perm" id="edit_accion" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Etiqueta</label>
          <input class="form-control" name="etiqueta" id="edit_etiqueta" required>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-warning" <?= $puedeEditar ? '' : 'disabled' ?>>Actualizar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal ELIMINAR permiso -->
<div class="modal fade" id="modalEliminarPermiso" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="post" autocomplete="off">
      <input type="hidden" name="accion" value="eliminar_permiso">
      <input type="hidden" name="id_permiso" id="del_id_permiso">
      <div class="modal-header">
        <h5 class="modal-title">Eliminar Permiso</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>¿Deseas eliminar el permiso <strong id="del_perm_label"></strong>?</p>
        <p class="text-muted small">Se quitarán también sus asignaciones a roles.</p>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-danger" <?= $puedeEditar ? '' : 'disabled' ?>>Eliminar</button>
      </div>
    </form>
  </div>
</div>

<script>
// Precarga modal editar
document.getElementById('modalEditarPermiso')?.addEventListener('show.bs.modal', ev => {
  const btn = ev.relatedTarget; if (!btn) return;
  document.getElementById('edit_id_permiso').value = btn.getAttribute('data-id') || '';
  document.getElementById('edit_recurso').value    = btn.getAttribute('data-recurso') || '';
  document.getElementById('edit_accion').value     = btn.getAttribute('data-accion') || '';
  document.getElementById('edit_etiqueta').value   = btn.getAttribute('data-etiqueta') || '';
});

// Precarga modal eliminar
document.getElementById('modalEliminarPermiso')?.addEventListener('show.bs.modal', ev => {
  const btn = ev.relatedTarget; if (!btn) return;
  document.getElementById('del_id_permiso').value = btn.getAttribute('data-id') || '';
  document.getElementById('del_perm_label').textContent = btn.getAttribute('data-etiqueta') || '';
});
</script>

<?php require __DIR__ . '/_layout_bottom.php'; // << AHORA SÍ, al final ?>
