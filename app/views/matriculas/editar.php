<div class="d-flex align-items-center justify-content-between mb-3">
  <h5 class="mb-0">Editar matrícula #<?= View::e((string)$id) ?></h5>
  <a class="btn btn-outline-secondary" href="<?= View::e(BASE_URL) ?>/index.php?r=matriculas/index">Volver</a>
</div>

<?php if (!empty($errores['general'])): ?>
  <div class="alert alert-danger"><?= View::e($errores['general']) ?></div>
<?php endif; ?>

<form method="post" action="<?= View::e(BASE_URL) ?>/index.php?r=matriculas/editar&id=<?= View::e((string)$id) ?>">
  <?php
    // Preparamos variables para _form a partir de $old
    $anios   = $anios ?? [];
    $anioSel = (int)($old['anio'] ?? date('Y'));
    $cursos  = $cursos ?? [];
    $cursoId = $old['curso_id'] ?? '';
    $alumnos = $alums ?? [];
    $old = [
      'alumno_rut'      => $old['alumno_rut'] ?? '',
      'curso_id'        => $old['curso_id'] ?? '',
      'fecha_matricula' => $old['fecha_matricula'] ?? date('Y-m-d'),
      'estado'          => $old['estado'] ?? 'VIGENTE',
    ];
    include __DIR__.'/_form.php';
  ?>
  <div class="d-grid gap-2 d-sm-flex justify-content-sm-end mt-3">
    <button class="btn btn-primary">Actualizar</button>
  </div>
</form>
