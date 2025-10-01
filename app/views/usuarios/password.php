<div class="d-flex align-items-center justify-content-between mb-3">
  <h5 class="mb-0">Cambiar contraseña — Usuario #<?= View::e((string)$id) ?></h5>
  <a class="btn btn-outline-secondary" href="<?= View::e(BASE_URL) ?>/index.php?r=usuarios/index">Volver</a>
</div>

<?php if (!empty($errores['general'])): ?>
  <div class="alert alert-danger"><?= View::e($errores['general']) ?></div>
<?php endif; ?>

<form method="post" action="<?= View::e(BASE_URL) ?>/index.php?r=usuarios/password&id=<?= View::e((string)$id) ?>">
  <div class="mb-3">
    <label class="form-label">Nueva contraseña</label>
    <input type="password" name="password" class="form-control <?= !empty($errores['password'])?'is-invalid':'' ?>" required>
    <?php if (!empty($errores['password'])): ?><div class="invalid-feedback"><?= View::e($errores['password']) ?></div><?php endif; ?>
    <div class="form-text">Mínimo 4 caracteres.</div>
  </div>
  <div class="d-grid gap-2 d-sm-flex justify-content-sm-end">
    <button class="btn btn-primary">Actualizar</button>
  </div>
</form>
