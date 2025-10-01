<?php
$rut            = $old['rut']            ?? '';
$nro_matricula  = $old['nro_matricula']  ?? '';
$fecha_ingreso  = $old['fecha_ingreso']  ?? date('Y-m-d');
$activo         = isset($old['activo']) ? (int)$old['activo'] : 1;
$personas       = $personas ?? [];
$modoEdicion    = !empty($rut);
?>
<div class="row g-3">
  <div class="col-12">
    <label class="form-label">Persona</label>
    <?php if ($modoEdicion): ?>
      <input type="text" class="form-control" value="RUT <?= View::e((string)$rut) ?>" disabled>
      <input type="hidden" name="rut" value="<?= View::e((string)$rut) ?>">
    <?php else: ?>
      <select name="rut" class="form-select <?= !empty($errores['rut'])?'is-invalid':'' ?>" required>
        <option value="">Seleccione…</option>
        <?php foreach ($personas as $p): ?>
          <option value="<?= (int)$p['rut'] ?>" <?= ((string)$rut===(string)$p['rut'])?'selected':'' ?>>
            <?= View::e($p['nombre']) ?> — RUT <?= View::e((string)$p['rut']) ?> <?= !empty($p['email'])?' · '.View::e($p['email']):'' ?>
          </option>
        <?php endforeach; ?>
      </select>
      <?php if (!empty($errores['rut'])): ?><div class="invalid-feedback"><?= View::e($errores['rut']) ?></div><?php endif; ?>
    <?php endif; ?>
  </div>

  <div class="col-12 col-md-6">
    <label class="form-label">N° Matrícula</label>
    <input type="text" name="nro_matricula" class="form-control <?= !empty($errores['nro_matricula'])?'is-invalid':'' ?>" value="<?= View::e($nro_matricula) ?>" required>
    <?php if (!empty($errores['nro_matricula'])): ?><div class="invalid-feedback"><?= View::e($errores['nro_matricula']) ?></div><?php endif; ?>
  </div>

  <div class="col-12 col-md-3">
    <label class="form-label">Fecha ingreso</label>
    <input type="date" name="fecha_ingreso" class="form-control <?= !empty($errores['fecha_ingreso'])?'is-invalid':'' ?>" value="<?= View::e($fecha_ingreso) ?>">
    <?php if (!empty($errores['fecha_ingreso'])): ?><div class="invalid-feedback"><?= View::e($errores['fecha_ingreso']) ?></div><?php endif; ?>
  </div>

  <div class="col-12 col-md-3">
    <label class="form-label d-block">Estado</label>
    <div class="form-check form-switch">
      <input class="form-check-input" type="checkbox" name="activo" value="1" id="act" <?= ($activo===1)?'checked':'' ?>>
      <label class="form-check-label" for="act"><?= ($activo===1)?'ACTIVO':'INACTIVO' ?></label>
    </div>
  </div>
</div>
