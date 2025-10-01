<?php
$anio   = $old['anio'] ?? (int)date('Y');
$nivel  = $old['nivel_id'] ?? '';
$letra  = $old['letra'] ?? '';
$jornada= $old['jornada'] ?? 'MAÑANA';
$jefe   = $old['jefe_rut_profesor'] ?? '';
$validJ = ['MAÑANA','TARDE','COMPLETA'];
?>
<div class="row g-3">
  <div class="col-6 col-md-3">
    <label class="form-label">Año</label>
    <input type="number" name="anio" class="form-control <?= !empty($errores['anio'])?'is-invalid':'' ?>" value="<?= View::e((string)$anio) ?>" required>
    <?php if (!empty($errores['anio'])): ?><div class="invalid-feedback"><?= View::e($errores['anio']) ?></div><?php endif; ?>
  </div>
  <div class="col-6 col-md-3">
    <label class="form-label">Letra</label>
    <input type="text" name="letra" maxlength="1" class="form-control <?= !empty($errores['letra'])?'is-invalid':'' ?>" value="<?= View::e($letra) ?>" required>
    <?php if (!empty($errores['letra'])): ?><div class="invalid-feedback"><?= View::e($errores['letra']) ?></div><?php endif; ?>
  </div>
  <div class="col-12 col-md-6">
    <label class="form-label">Nivel</label>
    <select name="nivel_id" class="form-select <?= !empty($errores['nivel_id'])?'is-invalid':'' ?>" required>
      <option value="">Seleccione…</option>
      <?php foreach (($niveles ?? []) as $n): ?>
        <option value="<?= (int)$n['id'] ?>" <?= ((int)$nivel === (int)$n['id'])?'selected':'' ?>>
          <?= View::e($n['nombre']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?php if (!empty($errores['nivel_id'])): ?><div class="invalid-feedback"><?= View::e($errores['nivel_id']) ?></div><?php endif; ?>
  </div>
  <div class="col-6 col-md-4">
    <label class="form-label">Jornada</label>
    <select name="jornada" class="form-select">
      <?php foreach ($validJ as $j): ?>
        <option value="<?= $j ?>" <?= ($jornada===$j)?'selected':'' ?>><?= $j ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-md-8">
    <label class="form-label">Profesor Jefe (opcional)</label>
    <select name="jefe_rut_profesor" class="form-select">
      <option value="">— Ninguno —</option>
      <?php foreach (($profesores ?? []) as $p): ?>
        <option value="<?= (int)$p['rut'] ?>" <?= ((string)$jefe === (string)$p['rut'])?'selected':'' ?>>
          <?= View::e($p['nombre']) ?> (RUT <?= View::e((string)$p['rut']) ?>)
        </option>
      <?php endforeach; ?>
    </select>
  </div>
</div>
