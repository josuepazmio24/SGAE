<?php
$seccion_id    = $seccionSel ?? ($old['seccion_id'] ?? '');
$evaluacion_id = $old['evaluacion_id'] ?? '';
$alumno_rut    = $old['alumno_rut'] ?? '';
$nota          = $old['nota'] ?? '6.0';
$observacion   = $old['observacion'] ?? '';
?>
<div class="row g-3">
  <div class="col-12">
    <label class="form-label">Sección</label>
    <select id="secSel" name="seccion_id" class="form-select <?= !empty($errores['seccion_id'])?'is-invalid':'' ?>" required
            onchange="if(this.value){ window.location='<?= View::e(BASE_URL) ?>/index.php?r=calificaciones/crear&seccion='+this.value; }">
      <option value="">Seleccione…</option>
      <?php foreach (($secciones ?? []) as $s): ?>
        <option value="<?= (int)$s['id'] ?>" <?= ((int)$seccion_id === (int)$s['id'])?'selected':'' ?>>
          <?= View::e($s['label']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?php if (!empty($errores['seccion_id'])): ?><div class="invalid-feedback"><?= View::e($errores['seccion_id']) ?></div><?php endif; ?>
  </div>

  <div class="col-12 col-md-6">
    <label class="form-label">Evaluación</label>
    <select name="evaluacion_id" class="form-select <?= !empty($errores['evaluacion_id'])?'is-invalid':'' ?>" required>
      <option value="">Seleccione…</option>
      <?php foreach (($evals ?? []) as $e): ?>
        <option value="<?= (int)$e['id'] ?>" <?= ((string)$evaluacion_id === (string)$e['id'])?'selected':'' ?>>
          <?= View::e($e['nombre']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?php if (!empty($errores['evaluacion_id'])): ?><div class="invalid-feedback"><?= View::e($errores['evaluacion_id']) ?></div><?php endif; ?>
  </div>

  <div class="col-12 col-md-6">
    <label class="form-label">Alumno</label>
    <select name="alumno_rut" class="form-select <?= !empty($errores['alumno_rut'])?'is-invalid':'' ?>" required>
      <option value="">Seleccione…</option>
      <?php foreach (($alumnos ?? []) as $a): ?>
        <option value="<?= (int)$a['rut'] ?>" <?= ((string)$alumno_rut === (string)$a['rut'])?'selected':'' ?>>
          <?= View::e($a['nombre']) ?> — RUT <?= View::e((string)$a['rut']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?php if (!empty($errores['alumno_rut'])): ?><div class="invalid-feedback"><?= View::e($errores['alumno_rut']) ?></div><?php endif; ?>
  </div>

  <div class="col-6 col-md-3">
    <label class="form-label">Nota (1.0–7.0)</label>
    <input type="number" step="0.1" min="1.0" max="7.0" name="nota" class="form-control <?= !empty($errores['nota'])?'is-invalid':'' ?>" value="<?= View::e($nota) ?>" required>
    <?php if (!empty($errores['nota'])): ?><div class="invalid-feedback"><?= View::e($errores['nota']) ?></div><?php endif; ?>
  </div>

  <div class="col-12 col-md-9">
    <label class="form-label">Observación (opcional)</label>
    <input type="text" name="observacion" class="form-control" maxlength="200" value="<?= View::e($observacion) ?>">
  </div>
</div>
