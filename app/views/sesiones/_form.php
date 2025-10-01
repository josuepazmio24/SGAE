<?php
$seccion_id = $old['seccion_id'] ?? '';
$fecha      = $old['fecha'] ?? date('Y-m-d');
$bloque     = $old['bloque'] ?? '';
$tema       = $old['tema'] ?? '';
?>
<div class="row g-3">
  <div class="col-12">
    <label class="form-label">Sección</label>
    <select name="seccion_id" class="form-select <?= !empty($errores['seccion_id'])?'is-invalid':'' ?>" required>
      <option value="">Seleccione…</option>
      <?php foreach (($secciones ?? []) as $s): ?>
        <option value="<?= (int)$s['id'] ?>" <?= ((int)$seccion_id === (int)$s['id'])?'selected':'' ?>>
          <?= View::e($s['label']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?php if (!empty($errores['seccion_id'])): ?><div class="invalid-feedback"><?= View::e($errores['seccion_id']) ?></div><?php endif; ?>
  </div>
  <div class="col-6 col-md-3">
    <label class="form-label">Fecha</label>
    <input type="date" name="fecha" class="form-control <?= !empty($errores['fecha'])?'is-invalid':'' ?>" value="<?= View::e($fecha) ?>" required>
    <?php if (!empty($errores['fecha'])): ?><div class="invalid-feedback"><?= View::e($errores['fecha']) ?></div><?php endif; ?>
  </div>
  <div class="col-6 col-md-3">
    <label class="form-label">Bloque <span class="text-muted">(opcional)</span></label>
    <input type="text" name="bloque" class="form-control" value="<?= View::e($bloque) ?>" placeholder="Ej: 1, 2, A, B…">
    <div class="form-text">Si dejas vacío, se normaliza a “sin bloque”.</div>
  </div>
  <div class="col-12">
    <label class="form-label">Tema <span class="text-muted">(opcional)</span></label>
    <input type="text" name="tema" class="form-control" value="<?= View::e($tema) ?>" maxlength="200" placeholder="Contenido de la clase">
  </div>
</div>
