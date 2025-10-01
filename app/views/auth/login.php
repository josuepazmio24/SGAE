<style>
  body {
    background: url("<?= View::e(BASE_URL) ?>/assets/img/fondo.jpg") no-repeat center center fixed;
    background-size: cover;
  }
  .login-card {
    background: rgba(255,255,255,0.9);
    border-radius: 12px;
  }
  .login-logo {
    display: block;
    margin: 0 auto 15px auto;
    max-width: 120px;
  }
</style>

<div class="row justify-content-center">
  <div class="col-md-5">
    <div class="card shadow-sm login-card">
      <div class="card-body">
        <!-- Logo -->
        <img src="<?= View::e(BASE_URL) ?>/assets/img/logo.png" alt="SGAE" class="login-logo">

        <h5 class="mb-3 text-center">Inicio de Sesión - SGAE</h5>

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
      </div>
    </div>
  </div>
</div>
