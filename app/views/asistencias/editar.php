<div class="d-flex align-items-center justify-content-between mb-3">
  <h5 class="mb-0">Editar asistencia #<?= View::e((string)$id) ?></h5>
  <a class="btn btn-outline-secondary" href="<?= View::e(BASE_URL) ?>/index.php?r=asistencias/index">Volver</a>
</div>

<?php if (!empty($errores['general'])): ?>
  <div class="alert alert-danger"><?= View::e($errores['general']) ?></div>
<?php endif; ?>

<form method="post" action="<?= View::e(BASE_URL) ?>/index.php?r=asistencias/editar&id=<?= View::e((string)$id) ?>">
  <?php
    // Para editar, reutilizamos el mismo formulario
    // pero ya cargamos $alumnos según la sección del registro
    include __DIR__ . '/_form.php';
  ?>
  <div class="d-grid gap-2 d-sm-flex justify-content-sm-end mt-3">
    <button class="btn btn-primary">Actualizar</button>
  </div>
</form>
