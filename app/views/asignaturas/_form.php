<?php
$nombre   = $old['nombre'] ?? '';
$codigo   = $old['codigo'] ?? '';
$nivel_id = $old['nivel_id'] ?? '';
$activo   = (int)($old['activo'] ?? 1);
?>
<div class="row g-3">
  <div class="col-12 col-md-6">
    <label class="form-label">Nombre</label>
    <input type="text" name="nombre" class="form-control <?= !empty($errores['nombre'])?'is-invalid':'' ?>" value="<?= View::e($nombre) ?>" required>
    <?php if (!empty($errores['nombre'])): ?><div class="invalid-feedback"><?= View::e($errores['nombre']) ?></div><?php endif; ?>
  </div>
  <div class="col-12 col-md-3">
    <label class="form-label">Código</label>
    <input type="text" name="codigo" maxlength="20" class="form-control <?= !empty($errores['codigo'])?'is-invalid':'' ?>" value="<?= View::e($codigo) ?>" required>
    <?php if (!empty($errores['codigo'])): ?><div class="invalid-feedback"><?= View::e($errores['codigo']) ?></div><?php endif; ?>
  </div>
  <div class="col-12 col-md-3">
    <label class="form-label">Nivel</label>
    <select name="nivel_id" class="form-select <?= !empty($errores['nivel_id'])?'is-invalid':'' ?>" required>
      <option value="">Seleccione…</option>
      <?php foreach (($niveles ?? []) as $n): ?>
        <option value="<?= (int)$n['id'] ?>" <?= ((int)$nivel_id === (int)$n['id'])?'selected':'' ?>>
          <?= View::e($n['nombre']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?php if (!empty($errores['nivel_id'])): ?><div class="invalid-feedback"><?= View::e($errores['nivel_id']) ?></div><?php endif; ?>
  </div>
  <div class="col-12 col-md-3">
    <label class="form-label">Estado</label>
    <select name="activo" class="form-select">
      <option value="1" <?= $activo===1?'selected':'' ?>>Activa</option>
      <option value="0" <?= $activo===0?'selected':'' ?>>Inactiva</option>
    </select>
  </div>
</div>
