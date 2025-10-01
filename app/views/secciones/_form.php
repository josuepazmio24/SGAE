<?php
$curso_id      = $old['curso_id'] ?? '';
$asignatura_id = $old['asignatura_id'] ?? '';
$profesor_rut  = $old['profesor_rut'] ?? '';
?>
<div class="row g-3">
  <div class="col-12 col-md-4">
    <label class="form-label">Curso</label>
    <select name="curso_id" class="form-select <?= !empty($errores['curso_id'])?'is-invalid':'' ?>" required>
      <option value="">Seleccione…</option>
      <?php foreach (($cursos ?? []) as $c): ?>
        <?php $label = $c['anio'].' '.$c['nivel'].' '.$c['letra']; ?>
        <option value="<?= (int)$c['id'] ?>" <?= ((int)$curso_id === (int)$c['id'])?'selected':'' ?>>
          <?= View::e($label) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?php if (!empty($errores['curso_id'])): ?><div class="invalid-feedback"><?= View::e($errores['curso_id']) ?></div><?php endif; ?>
  </div>
  <div class="col-12 col-md-4">
    <label class="form-label">Asignatura</label>
    <select name="asignatura_id" class="form-select <?= !empty($errores['asignatura_id'])?'is-invalid':'' ?>" required>
      <option value="">Seleccione…</option>
      <?php foreach (($asigs ?? []) as $a): ?>
        <option value="<?= (int)$a['id'] ?>" <?= ((int)$asignatura_id === (int)$a['id'])?'selected':'' ?>>
          <?= View::e($a['nivel'].' · '.$a['nombre'].' ('.$a['codigo'].')') ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?php if (!empty($errores['asignatura_id'])): ?><div class="invalid-feedback"><?= View::e($errores['asignatura_id']) ?></div><?php endif; ?>
  </div>
  <div class="col-12 col-md-4">
    <label class="form-label">Profesor</label>
    <select name="profesor_rut" class="form-select <?= !empty($errores['profesor_rut'])?'is-invalid':'' ?>" required>
      <option value="">Seleccione…</option>
      <?php foreach (($profs ?? []) as $p): ?>
        <option value="<?= (int)$p['rut'] ?>" <?= ((int)$profesor_rut === (int)$p['rut'])?'selected':'' ?>>
          <?= View::e($p['nombre']).' (RUT '.View::e((string)$p['rut']).')' ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?php if (!empty($errores['profesor_rut'])): ?><div class="invalid-feedback"><?= View::e($errores['profesor_rut']) ?></div><?php endif; ?>
  </div>
</div>
