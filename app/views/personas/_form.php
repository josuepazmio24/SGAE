<?php
$rut_str     = $old['rut_str']     ?? ($old['rut'] ?? '');
$rut         = $old['rut']         ?? '';
$dv          = $old['dv']          ?? '';
$nombres     = $old['nombres']     ?? '';
$apellidos   = $old['apellidos']   ?? '';
$sexo        = $old['sexo']        ?? '';
$fecha_nac   = $old['fecha_nac']   ?? '';
$email       = $old['email']       ?? '';
$telefono    = $old['telefono']    ?? '';
$direccion   = $old['direccion']   ?? '';
$tipo_persona= $old['tipo_persona']?? '';
$sexos       = $sexos ?? ['M','F','X'];
$tipos       = $tipos ?? ['ALUMNO','PROFESOR','APODERADO','ADMIN'];
$modoEdicion = isset($rut) && $rut !== '' && isset($dv) && $dv !== '' && basename($_SERVER['PHP_SELF']) !== 'crear.php';
?>
<div class="row g-3">
  <div class="col-8 col-md-4">
    <label class="form-label">RUT</label>
    <?php if ($modoEdicion): ?>
      <input type="text" class="form-control" value="<?= View::e((string)$rut) ?>" disabled>
      <input type="hidden" name="rut" value="<?= View::e((string)$rut) ?>">
    <?php else: ?>
      <input type="text" name="rut_str" class="form-control <?= !empty($errores['rut'])?'is-invalid':'' ?>" value="<?= View::e((string)$rut_str) ?>" placeholder="12.345.678">
      <?php if (!empty($errores['rut'])): ?><div class="invalid-feedback"><?= View::e($errores['rut']) ?></div><?php endif; ?>
    <?php endif; ?>
  </div>
  <div class="col-4 col-md-2">
    <label class="form-label">DV</label>
    <input type="text" name="dv" maxlength="1" class="form-control <?= !empty($errores['dv'])?'is-invalid':'' ?>" value="<?= View::e($dv) ?>" placeholder="K/0-9" required>
    <?php if (!empty($errores['dv'])): ?><div class="invalid-feedback"><?= View::e($errores['dv']) ?></div><?php endif; ?>
  </div>
  <div class="col-12 col-md-6">
    <label class="form-label">Email</label>
    <input type="email" name="email" class="form-control <?= !empty($errores['email'])?'is-invalid':'' ?>" value="<?= View::e($email) ?>">
    <?php if (!empty($errores['email'])): ?><div class="invalid-feedback"><?= View::e($errores['email']) ?></div><?php endif; ?>
  </div>

  <div class="col-12 col-md-6">
    <label class="form-label">Nombres</label>
    <input type="text" name="nombres" class="form-control <?= !empty($errores['nombres'])?'is-invalid':'' ?>" value="<?= View::e($nombres) ?>" required>
    <?php if (!empty($errores['nombres'])): ?><div class="invalid-feedback"><?= View::e($errores['nombres']) ?></div><?php endif; ?>
  </div>
  <div class="col-12 col-md-6">
    <label class="form-label">Apellidos</label>
    <input type="text" name="apellidos" class="form-control <?= !empty($errores['apellidos'])?'is-invalid':'' ?>" value="<?= View::e($apellidos) ?>" required>
    <?php if (!empty($errores['apellidos'])): ?><div class="invalid-feedback"><?= View::e($errores['apellidos']) ?></div><?php endif; ?>
  </div>

  <div class="col-6 col-md-3">
    <label class="form-label">Sexo</label>
    <select name="sexo" class="form-select <?= !empty($errores['sexo'])?'is-invalid':'' ?>">
      <option value="">—</option>
      <?php foreach ($sexos as $s): ?>
        <option value="<?= $s ?>" <?= ($sexo===$s)?'selected':'' ?>><?= $s ?></option>
      <?php endforeach; ?>
    </select>
    <?php if (!empty($errores['sexo'])): ?><div class="invalid-feedback"><?= View::e($errores['sexo']) ?></div><?php endif; ?>
  </div>

  <div class="col-6 col-md-3">
    <label class="form-label">Fecha nac.</label>
    <input type="date" name="fecha_nac" class="form-control <?= !empty($errores['fecha_nac'])?'is-invalid':'' ?>" value="<?= View::e($fecha_nac) ?>">
    <?php if (!empty($errores['fecha_nac'])): ?><div class="invalid-feedback"><?= View::e($errores['fecha_nac']) ?></div><?php endif; ?>
  </div>

  <div class="col-12 col-md-6">
    <label class="form-label">Tipo persona</label>
    <select name="tipo_persona" class="form-select <?= !empty($errores['tipo_persona'])?'is-invalid':'' ?>">
      <option value="">—</option>
      <?php foreach ($tipos as $t): ?>
        <option value="<?= $t ?>" <?= ($tipo_persona===$t)?'selected':'' ?>><?= $t ?></option>
      <?php endforeach; ?>
    </select>
    <?php if (!empty($errores['tipo_persona'])): ?><div class="invalid-feedback"><?= View::e($errores['tipo_persona']) ?></div><?php endif; ?>
  </div>

  <div class="col-12 col-md-6">
    <label class="form-label">Teléfono</label>
    <input type="text" name="telefono" class="form-control" value="<?= View::e($telefono) ?>" maxlength="30">
  </div>

  <div class="col-12">
    <label class="form-label">Dirección</label>
    <input type="text" name="direccion" class="form-control" value="<?= View::e($direccion) ?>" maxlength="180">
  </div>
</div>
