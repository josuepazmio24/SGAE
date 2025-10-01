<?php
$seccion_id  = $old['seccion_id']  ?? '';
$periodo_id  = $old['periodo_id']  ?? '';
$nombre      = $old['nombre']      ?? '';
$tipo        = $old['tipo']        ?? 'PRUEBA';
$fecha       = $old['fecha']       ?? date('Y-m-d');
$ponderacion = $old['ponderacion'] ?? '0';
$publicado   = (int)($old['publicado'] ?? 0);
$tipos       = $tipos ?? Evaluacion::tipos();
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

  <div class="col-12 col-md-6">
    <label class="form-label">Periodo <span class="text-muted">(opcional)</span></label>
    <select name="periodo_id" class="form-select">
      <option value="">— Sin periodo —</option>
      <?php foreach (($periodos ?? []) as $p): ?>
        <option value="<?= (int)$p['id'] ?>" <?= ((string)$periodo_id === (string)$p['id'])?'selected':'' ?>>
          <?= View::e($p['nombre']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-12 col-md-6">
    <label class="form-label">Nombre</label>
    <input type="text" name="nombre" class="form-control <?= !empty($errores['nombre'])?'is-invalid':'' ?>" value="<?= View::e($nombre) ?>" required>
    <?php if (!empty($errores['nombre'])): ?><div class="invalid-feedback"><?= View::e($errores['nombre']) ?></div><?php endif; ?>
  </div>

  <div class="col-6 col-md-3">
    <label class="form-label">Tipo</label>
    <select name="tipo" class="form-select <?= !empty($errores['tipo'])?'is-invalid':'' ?>" required>
      <?php foreach ($tipos as $t): ?>
        <option value="<?= $t ?>" <?= ($tipo===$t)?'selected':'' ?>><?= $t ?></option>
      <?php endforeach; ?>
    </select>
    <?php if (!empty($errores['tipo'])): ?><div class="invalid-feedback"><?= View::e($errores['tipo']) ?></div><?php endif; ?>
  </div>

  <div class="col-6 col-md-3">
    <label class="form-label">Fecha</label>
    <input type="date" name="fecha" class="form-control <?= !empty($errores['fecha'])?'is-invalid':'' ?>" value="<?= View::e($fecha) ?>" required>
    <?php if (!empty($errores['fecha'])): ?><div class="invalid-feedback"><?= View::e($errores['fecha']) ?></div><?php endif; ?>
  </div>

  <div class="col-6 col-md-3">
    <label class="form-label">Ponderación (%)</label>
    <input type="number" step="0.01" min="0" max="100" name="ponderacion" class="form-control <?= !empty($errores['ponderacion'])?'is-invalid':'' ?>" value="<?= View::e((string)$ponderacion) ?>" required>
    <?php if (!empty($errores['ponderacion'])): ?><div class="invalid-feedback"><?= View::e($errores['ponderacion']) ?></div><?php endif; ?>
  </div>

  <div class="col-6 col-md-3">
    <label class="form-label d-block">Estado</label>
    <div class="form-check form-switch">
      <input class="form-check-input" type="checkbox" name="publicado" value="1" id="pb" <?= $publicado===1?'checked':'' ?>>
      <label class="form-check-label" for="pb">Publicado</label>
    </div>
  </div>
</div>
