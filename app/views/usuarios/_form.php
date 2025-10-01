<?php
$username    = $old['username']    ?? '';
$rol         = $old['rol']         ?? 'ALUMNO';
$rut_persona = $old['rut_persona'] ?? '';
$estado      = $old['estado']      ?? 'ACTIVO';
$roles       = $roles ?? ['ADMIN','PROFESOR','ALUMNO'];
$estados     = $estados ?? ['ACTIVO','SUSPENDIDO'];
$personas    = $personas ?? [];
$showPass    = $showPass ?? false;
?>
<div class="row g-3">
  <div class="col-12 col-md-6">
    <label class="form-label">Usuario</label>
    <input type="text" name="username" class="form-control <?= !empty($errores['username'])?'is-invalid':'' ?>" value="<?= View::e($username) ?>" required>
    <?php if (!empty($errores['username'])): ?><div class="invalid-feedback"><?= View::e($errores['username']) ?></div><?php endif; ?>
  </div>
  <div class="col-12 col-md-6">
    <label class="form-label">Rol</label>
    <select name="rol" class="form-select <?= !empty($errores['rol'])?'is-invalid':'' ?>" required>
      <?php foreach ($roles as $r): ?>
        <option value="<?= $r ?>" <?= ($rol===$r)?'selected':'' ?>><?= $r ?></option>
      <?php endforeach; ?>
    </select>
    <?php if (!empty($errores['rol'])): ?><div class="invalid-feedback"><?= View::e($errores['rol']) ?></div><?php endif; ?>
  </div>

  <div class="col-12 col-md-6">
    <label class="form-label">Persona vinculada</label>
    <select name="rut_persona" class="form-select <?= !empty($errores['rut_persona'])?'is-invalid':'' ?>" required>
      <option value="">Seleccione…</option>
      <?php foreach ($personas as $p): ?>
        <option value="<?= (int)$p['rut'] ?>" <?= ((string)$rut_persona===(string)$p['rut'])?'selected':'' ?>>
          <?= View::e($p['nombre']) ?> — RUT <?= View::e((string)$p['rut']) ?> <?= !empty($p['email'])?' · '.View::e($p['email']):'' ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?php if (!empty($errores['rut_persona'])): ?><div class="invalid-feedback"><?= View::e($errores['rut_persona']) ?></div><?php endif; ?>
  </div>

  <div class="col-12 col-md-6">
    <label class="form-label">Estado</label>
    <select name="estado" class="form-select <?= !empty($errores['estado'])?'is-invalid':'' ?>" required>
      <?php foreach ($estados as $e): ?>
        <option value="<?= $e ?>" <?= ($estado===$e)?'selected':'' ?>><?= $e ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <?php if ($showPass): ?>
  <div class="col-12">
    <label class="form-label">Contraseña</label>
    <input type="password" name="password" class="form-control <?= !empty($errores['password'])?'is-invalid':'' ?>" required>
    <?php if (!empty($errores['password'])): ?><div class="invalid-feedback"><?= View::e($errores['password']) ?></div><?php endif; ?>
    <div class="form-text">Mínimo 4 caracteres.</div>
  </div>
  <?php endif; ?>
</div>
