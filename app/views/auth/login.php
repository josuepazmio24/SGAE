<div class="row justify-content-center">
<div class="col-md-5">
<div class="card shadow-sm">
<div class="card-body">
<h5 class="mb-3">Inicio de Sesion SGAE</h5>
<?php if (!empty($error)): ?>
<div class="alert alert-danger"><?= View::e($error) ?></div>
<?php endif; ?>
<form method="post" action="<?= View::e(BASE_URL) ?>/index.php?r=auth/login">
<div class="mb-3">
<label class="form-label">Usuario</label>
<input type="text" name="username" class="form-control" required>
</div>
<div class="mb-3">
<label class="form-label">Contraseña</label>
<input type="password" name="password" class="form-control" required>
</div>
<button class="btn btn-primary w-100">Entrar</button>
</form>
<hr>
</div>
</div>
</div>
</div>