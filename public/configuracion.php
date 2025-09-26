<?php
// public/perfil.php  (o reemplaza configuracion.php si prefieres ese URL)
$TITLE = 'Perfil de Usuario';
require __DIR__ . '/_layout_top.php';
require __DIR__ . '/../includes/helpers.php'; // solo utilidades, sin redeclaraciones

// Asegurar usuario logeado (tu _layout_top suele incluir bootstrap con require_login())
$usrSession = $_SESSION['usuario'] ?? null;
if (!$usrSession || empty($usrSession['rut'])) {
  header('Location: ' . $BASE . '/login.php');
  exit;
}

$flash = ['type'=>null,'msg'=>null];

// ===== Cargar datos actuales desde BD (más fresco que sesión) =====
try {
  $st = $pdo->prepare("SELECT rut, dv, nombre, correo, rol FROM usuarios WHERE rut=:rut LIMIT 1");
  $st->execute([':rut' => $usrSession['rut']]);
  $user = $st->fetch(PDO::FETCH_ASSOC);
  if (!$user) { throw new RuntimeException('No se encontró el usuario.'); }
} catch (Throwable $e) {
  $flash = ['type'=>'danger', 'msg'=>'Error cargando perfil: '.$e->getMessage()];
  // Si falla, usa lo de sesión como fallback
  $user = [
    'rut' => $usrSession['rut'] ?? '',
    'dv' => $usrSession['dv'] ?? '',
    'nombre' => $usrSession['nombre'] ?? '',
    'correo' => $usrSession['correo'] ?? '',
    'rol' => $usrSession['rol'] ?? 'alumno',
  ];
}

// ===== Acciones =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'update_perfil') {
      $nombre = trim($_POST['nombre'] ?? '');
      $correo = trim($_POST['correo'] ?? '');
      if ($nombre === '' || $correo === '') throw new RuntimeException('Nombre y correo son obligatorios.');
      if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Correo inválido.');

      $st = $pdo->prepare("UPDATE usuarios SET nombre=:n, correo=:c WHERE rut=:r");
      $st->execute([':n'=>$nombre, ':c'=>$correo, ':r'=>$user['rut']]);

      // Refrescar datos en memoria y sesión
      $user['nombre'] = $nombre;
      $user['correo'] = $correo;
      $_SESSION['usuario']['nombre'] = $nombre;
      $_SESSION['usuario']['correo'] = $correo;

      $flash = ['type'=>'success','msg'=>'Perfil actualizado correctamente.'];

    } elseif ($accion === 'change_password') {
      $pwd_actual = $_POST['pwd_actual'] ?? '';
      $pwd_nueva  = $_POST['pwd_nueva'] ?? '';
      $pwd_conf   = $_POST['pwd_conf'] ?? '';

      if ($pwd_actual === '' || $pwd_nueva === '' || $pwd_conf === '') {
        throw new RuntimeException('Todos los campos de contraseña son obligatorios.');
      }
      if ($pwd_nueva !== $pwd_conf) throw new RuntimeException('La confirmación no coincide.');
      if (strlen($pwd_nueva) < 6) throw new RuntimeException('La nueva contraseña debe tener al menos 6 caracteres.');

      // Obtener hash actual
      $st = $pdo->prepare("SELECT password FROM usuarios WHERE rut=:r LIMIT 1");
      $st->execute([':r'=>$user['rut']]);
      $row = $st->fetch(PDO::FETCH_ASSOC);
      if (!$row || empty($row['password'])) throw new RuntimeException('No fue posible validar la contraseña actual.');

      if (!password_verify($pwd_actual, $row['password'])) {
        throw new RuntimeException('La contraseña actual es incorrecta.');
      }

      $hash = password_hash($pwd_nueva, PASSWORD_BCRYPT);
      $st = $pdo->prepare("UPDATE usuarios SET password=:p WHERE rut=:r");
      $st->execute([':p'=>$hash, ':r'=>$user['rut']]);

      $flash = ['type'=>'success','msg'=>'Contraseña actualizada correctamente.'];
    }

  } catch (Throwable $e) {
    $flash = ['type'=>'danger','msg'=>$e->getMessage()];
  }
}
?>

<h4>Configuración de Perfil</h4>
<?php if ($flash['type']): ?>
  <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['msg']) ?></div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="mb-3">Datos personales</h5>
        <form method="post" autocomplete="off">
          <input type="hidden" name="accion" value="update_perfil">
          <div class="mb-3">
            <label class="form-label">RUT (solo lectura)</label>
            <div class="input-group">
              <input type="text" class="form-control" value="<?= htmlspecialchars($user['rut']) ?>" readonly>
              <span class="input-group-text"><?= htmlspecialchars($user['dv']) ?></span>
            </div>
          </div>
         <div class="mb-3">
            <label class="form-label">Rol</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($user['rol'] ?? 'alumno') ?>" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input type="text" name="nombre" class="form-control" required value="<?= htmlspecialchars($user['nombre'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Correo</label>
            <input type="email" name="correo" class="form-control" required value="<?= htmlspecialchars($user['correo'] ?? '') ?>">
          </div>
          <div class="d-flex gap-2">
            <a href="<?= $BASE ?>/dashboard.php" class="btn btn-secondary">Cancelar</a>
            <button class="btn btn-primary">Guardar cambios</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="mb-3">Cambiar contraseña</h5>
        <form method="post" autocomplete="off">
          <input type="hidden" name="accion" value="change_password">
          <div class="mb-3">
            <label class="form-label">Contraseña actual</label>
            <input type="password" name="pwd_actual" class="form-control" required minlength="6">
          </div>
          <div class="mb-3">
            <label class="form-label">Nueva contraseña</label>
            <input type="password" name="pwd_nueva" class="form-control" required minlength="6">
          </div>
          <div class="mb-3">
            <label class="form-label">Confirmar nueva contraseña</label>
            <input type="password" name="pwd_conf" class="form-control" required minlength="6">
          </div>
          <div class="d-flex gap-2">
            <button class="btn btn-warning" type="submit">Actualizar contraseña</button>
          </div>
        </form>
        <div class="form-text mt-2">La contraseña se almacena con hash (bcrypt).</div>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
