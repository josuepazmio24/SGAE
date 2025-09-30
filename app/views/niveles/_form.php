<?php
$nombre = $old['nombre'] ?? '';
$descripcion = $old['descripcion'] ?? '';
$orden = $old['orden'] ?? '';
?>
<div class="mb-3">
  <label class="form-label">Nombre</label>
  <input type="text" name="nombre" class="form-control <?= !empty($errores['nombre'])?'is-invalid':'' ?>" value="<?= View::e($nombre) ?>" required>
  <?php if (!empty($errores['nombre'])): ?><div class="invalid-feedback"><?= View::e($errores['nombre']) ?></div><?php endif; ?>
</div>
<div class="mb-3">
  <label class="form-label">Descripción</label>
  <textarea name="descripcion" class="form-control" rows="2"><?= View::e($descripcion) ?></textarea>
</div>
<div class="mb-3">
  <label class="form-label">Orden</label>
  <input type="number" name="orden" class="form-control <?= !empty($errores['orden'])?'is-invalid':'' ?>" value="<?= View::e((string)$orden) ?>" required>
  <?php if (!empty($errores['orden'])): ?><div class="invalid-feedback"><?= View::e($errores['orden']) ?></div><?php endif; ?>
</div>
