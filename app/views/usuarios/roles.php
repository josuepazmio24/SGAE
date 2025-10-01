<div class="d-flex align-items-center justify-content-between mb-3">
  <h5 class="mb-0">Roles del usuario: <?= View::e($user['username']) ?></h5>
  <a class="btn btn-outline-secondary" href="<?= View::e(BASE_URL) ?>/index.php?r=usuarios/index">Volver</a>
</div>

<form method="post" action="<?= View::e(BASE_URL) ?>/index.php?r=usuarioroles/guardar">
  <input type="hidden" name="usuario_id" value="<?= (int)$uid ?>">

  <div class="card shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:60px;">Asignar</th>
            <th>Rol</th>
            <th>Descripción</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($roles as $r): $checked = !empty($asigMap[(int)$r['id']]); ?>
            <tr>
              <td>
                <input type="checkbox" name="rol_ids[]" value="<?= (int)$r['id'] ?>" <?= $checked?'checked':'' ?>>
              </td>
              <td><strong><?= View::e($r['nombre']) ?></strong></td>
              <td><?= View::e($r['descripcion'] ?? '') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="alert alert-info mt-3">
    Si modificas tus propios roles, tus permisos se actualizarán automáticamente.
  </div>

  <div class="d-grid gap-2 d-sm-flex justify-content-sm-end mt-3">
    <button class="btn btn-primary">Guardar cambios</button>
  </div>
</form>
