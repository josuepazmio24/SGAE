<?php
// public/register.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!empty($_SESSION['usuario'])) {
    header('Location: dashboard.php'); exit;
}
$err = $_GET['err'] ?? null;
$ok  = $_GET['ok']  ?? null;
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Crear cuenta</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center py-5">
  <div class="card shadow p-4" style="max-width: 520px; width: 100%;">
    <h4 class="mb-3 text-center">Registro de usuario</h4>

    <?php if ($err): ?>
      <div class="alert alert-danger py-2"><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>
    <?php if ($ok): ?>
      <div class="alert alert-success py-2"><?= htmlspecialchars($ok) ?></div>
    <?php endif; ?>

    <form action="procesar_registro.php" method="post" id="regForm" novalidate>
      <div class="row g-2">
        <div class="col-md-8">
          <label for="rut" class="form-label">RUT (sin DV)</label>
          <input type="text" class="form-control" id="rut" name="rut" maxlength="8" inputmode="numeric" required>
          <div class="form-text">Ej: 12345678</div>
        </div>
        <div class="col-md-4">
          <label for="dv" class="form-label">DV</label>
          <input type="text" class="form-control" id="dv" name="dv" maxlength="1" readonly required>
        </div>
      </div>

      <div class="row g-2 mt-1">
        <div class="col-md-6">
          <label for="nombre" class="form-label">Nombre</label>
          <input type="text" class="form-control" id="nombre" name="nombre" required>
        </div>
        <div class="col-md-6">
          <label for="correo" class="form-label">Correo</label>
          <input type="email" class="form-control" id="correo" name="correo" required>
        </div>
      </div>

      <div class="row g-2 mt-1">
        <div class="col-md-6">
          <label for="password" class="form-label">Contraseña</label>
          <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <div class="col-md-6">
          <label for="password2" class="form-label">Repite contraseña</label>
          <input type="password" class="form-control" id="password2" name="password2" required>
        </div>
      </div>

      <button type="submit" class="btn btn-success w-100 mt-3">Crear cuenta</button>
      <div class="text-center mt-2">
        <a href="login.php">¿Ya tienes cuenta? Inicia sesión</a>
      </div>
    </form>
  </div>
</div>

<script>
function calcularDV(rutNumeros) {
  let suma = 0, multiplo = 2;
  for (let i = rutNumeros.length - 1; i >= 0; i--) {
    suma += parseInt(rutNumeros[i], 10) * multiplo;
    multiplo = multiplo < 7 ? multiplo + 1 : 2;
  }
  const resto = 11 - (suma % 11);
  if (resto === 11) return "0";
  if (resto === 10) return "K";
  return String(resto);
}
const rutInput = document.getElementById('rut');
const dvInput  = document.getElementById('dv');
rutInput.addEventListener('input', () => {
  const limpio = rutInput.value.replace(/\D/g, '').slice(0, 8);
  rutInput.value = limpio;
  dvInput.value = limpio ? calcularDV(limpio) : '';
});
document.getElementById('regForm').addEventListener('submit', (e) => {
  const p1 = document.getElementById('password').value;
  const p2 = document.getElementById('password2').value;
  if (p1.length < 6) { e.preventDefault(); alert('La contraseña debe tener al menos 6 caracteres.'); return; }
  if (p1 !== p2) { e.preventDefault(); alert('Las contraseñas no coinciden.'); return; }
});
</script>
</body>
</html>
