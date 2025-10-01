<?php
$alumno_rut      = $old['alumno_rut']      ?? '';
$curso_id        = $old['curso_id']        ?? '';
$fecha_matricula = $old['fecha_matricula'] ?? date('Y-m-d');
$estado          = $old['estado']          ?? 'VIGENTE';

$anios   = $anios   ?? [];
$anioSel = $anioSel ?? (int)date('Y');
$cursos  = $cursos  ?? [];
$cursoId = $cursoId ?? $curso_id;
$alumnos = $alumnos ?? [];
?>
<div class="row g-3">
  <div class="col-12 col-md-3">
    <label class="form-label">Año</label>
    <select class="form-select" name="__anio_hint" disabled>
      <?php foreach ($anios as $a): ?>
        <option value="<?= (int)$a ?>" <?= ((int)$a===(int)$anioSel)?'selected':'' ?>><?= (int)$a ?></option>
      <?php endforeach; ?>
    </select>
    <div class="form-text">Se usa para filtrar cursos.</div>
  </div>

  <div class="col-12 col-md-5">
    <label class="form-label">Curso</label>
    <select name="curso_id" class="form-select <?= !empty($errores['curso_id'])?'is-invalid':'' ?>" required>
      <option value="">Seleccione…</option>
      <?php foreach ($cursos as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= ((string)$cursoId===(string)$c['id'])?'selected':'' ?>>
          <?= (int)$c['anio'] ?> · <?= View::e($c['nivel']) ?> <?= View::e($c['letra']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?php if (!empty($errores['curso_id'])): ?><div class="invalid-feedback"><?= View::e($errores['curso_id']) ?></div><?php endif; ?>
  </div>

  <div class="col-12 col-md-4">
    <label class="form-label">Alumno</label>
    <select name="alumno_rut" class="form-select <?= !empty($errores['alumno_rut'])?'is-invalid':'' ?>" required>
      <option value="">Seleccione…</option>
      <?php foreach ($alumnos as $a): ?>
        <option value="<?= (int)$a['rut'] ?>" <?= ((string)$alumno_rut===(string)$a['rut'])?'selected':'' ?>>
          <?= View::e($a['nombre']) ?> — RUT <?= View::e((string)$a['rut']) ?> <?= !empty($a['email'])?' · '.View::e($a['email']):'' ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?php if (!empty($errores['alumno_rut'])): ?><div class="invalid-feedback"><?= View::e($errores['alumno_rut']) ?></div><?php endif; ?>
    <div class="form-text">La lista intenta excluir alumnos ya matriculados en ese curso.</div>
  </div>

  <div class="col-12 col-md-4">
    <label class="form-label">Fecha matrícula</label>
    <input type="date" name="fecha_matricula" class="form-control <?= !empty($errores['fecha_matricula'])?'is-invalid':'' ?>" value="<?= View::e($fecha_matricula) ?>">
    <?php if (!empty($errores['fecha_matricula'])): ?><div class="invalid-feedback"><?= View::e($errores['fecha_matricula']) ?></div><?php endif; ?>
  </div>

  <div class="col-12 col-md-4">
    <label class="form-label">Estado</label>
    <select name="estado" class="form-select <?= !empty($errores['estado'])?'is-invalid':'' ?>">
      <?php foreach (['VIGENTE','RETIRADO','EGRESADO'] as $e): ?>
        <option value="<?= $e ?>" <?= ($estado===$e)?'selected':'' ?>><?= $e ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</div>
