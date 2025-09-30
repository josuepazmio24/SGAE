<div class="row justify-content-center">
<div class="col-md-7">
<div class="card shadow-sm">
<div class="card-body">
<h5 class="mb-3">Setup inicial — Crear ADMIN</h5>
<?php if (!empty($error)): ?>
<div class="alert alert-danger"><?= View::e($error) ?></div>
<?php endif; ?>
<form method="post" action="<?= View::e(BASE_URL) ?>/index.php?r=auth/setup">
<div class="row g-3">
<div class="col-md-4">
<label class="form-label">RUT</label>
<input type="number" name="rut" class="form-control" required>
</div>
<div class="col-md-2">
<label class="form-label">DV</label>
<input type="text" name="dv" maxlength="1" class="form-control" required>
</div>
<div class="col-md-6">
<label class="form-label">Email</label>
<input type="email" name="email" class="form-control">
</div>
<div class="col-md-6">
<label class="form-label">Nombres</label>
<input type="text" name="nombres" class="form-control" required>
</div>
<div class="col-md-6">
<label class="form-label">Apellidos</label>
<input type="text" name="apellidos" class="form-control" required>
</div>
<div class="col-md-6">
<label class="form-label">Usuario</label>
<input type="text" name="username" class="form-control" required>
</div>
<div class="col-md-6">
<label class="form-label">Contraseña</label>
<input type="password" name="password" class="form-control" required>
</div>
</div>
<div class="mt-3 d-grid">
<button class="btn btn-primary">Crear ADMIN</button>
</div>
</form>
</div>
</div>
</div>
</div>