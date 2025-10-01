<?php
$seccion_id = $seccionSel ?: ($old['seccion_id'] ?? '');
$alumno_rut = $old['alumno_rut'] ?? '';
$fecha      = $old['fecha'] ?? date('Y-m-d');
$estado     = $old['estado'] ?? 'PRESENTE';
$observacion= $old['observacion'] ?? '';
$estados    = Asistencia::estados();
?>
<div class="row g-3">
  <div class="col-12">
    <label class="form-label">Sección</label>
    <select id="seccionSel" name="seccion_id" class="form-select <?= !empty($errores['seccion_id'])?'is-invalid':'' ?>" required
            onchange="if(this.value){ window.location='<?= View::e(BASE_URL) ?>/index.php?r=asistencias/crear&seccion='+this.value; }">
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
    <div class="form-text">La lista se carga según la sección seleccionada.</div>
  </div>

  <div class="col-6 col-md-3">
    <label class="form-label">Fecha</label>
    <input type="date" name="fecha" class="form-control <?= !empty($errores['fecha'])?'is-invalid':'' ?>" value="<?= View::e($fecha) ?>" required>
    <?php if (!empty($errores['fecha'])): ?><div class="invalid-feedback"><?= View::e($errores['fecha']) ?></div><?php endif; ?>
  </div>

  <div class="col-6 col-md-3">
    <label class="form-label">Estado</label>
    <select name="estado" class="form-select <?= !empty($errores['estado'])?'is-invalid':'' ?>" required>
      <?php foreach ($estados as $e): ?>
        <option value="<?= $e ?>" <?= ($estado===$e)?'selected':'' ?>><?= $e ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-12">
    <label class="form-label">Observación (opcional)</label>
    <input type="text" name="observacion" class="form-control" maxlength="200" value="<?= View::e($observacion) ?>">
  </div>
</div>
