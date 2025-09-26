<?php
// public/login.php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

// Si ya hay sesión iniciada, redirige al dashboard
if (!empty($_SESSION['usuario'])) {
  $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
  header('Location: ' . ($base ?: '') . '/dashboard.php');
  exit;
}

// Base URL (maneja vhost con /public o sin él)
$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

// Mensaje de error opcional por GET
$err = isset($_GET['err']) ? (string)$_GET['err'] : null;

// CSRF token para el POST (guárdalo en sesión y verifica en procesar_login.php)
if (empty($_SESSION['csrf_login'])) {
  $_SESSION['csrf_login'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf_login'];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>SGAE • Iniciar sesión</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background:#f8f9fa; }
  </style>
</head>
<body>

<div class="container d-flex justify-content-center align-items-center vh-100">
  <div class="card shadow p-4" style="max-width: 420px; width: 100%;">
    <h4 class="mb-3 text-center">SGAE</h4>

    <?php if ($err): ?>
      <div class="alert alert-danger py-2"><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <form action="<?= $BASE ?>/procesar_login.php" method="post" id="loginForm" novalidate autocomplete="off">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">

      <!-- RUT -->
      <div class="mb-3">
        <label for="rut" class="form-label">RUT (sin DV)</label>
        <input
          type="text"
          class="form-control"
          id="rut"
          name="rut"
          inputmode="numeric"
          maxlength="9"
          pattern="\d{7,9}"
          required
          placeholder="12345678">
        <div class="form-text">Solo números, sin puntos ni guion (7 a 9 dígitos).</div>
      </div>

      <!-- DV -->
      <div class="mb-3">
        <label for="dv" class="form-label">Dígito Verificador</label>
        <input
          type="text"
          class="form-control"
          id="dv"
          name="dv"
          maxlength="1"
          pattern="[0-9Kk]{1}"
          required
          readonly>
      </div>

      <!-- Password -->
      <div class="mb-3">
        <label for="password" class="form-label">Contraseña</label>
        <div class="input-group">
          <input type="password" class="form-control" id="password" name="password" required>
          <button class="btn btn-outline-secondary" type="button" id="btnShow">👁</button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary w-100">Ingresar</button>
      <div class="text-center mt-3">
        <small class="text-muted">
          ¿Olvidaste tu contraseña? Contacta al administrador.
        </small>
      </div>
    </form>
  </div>
</div>

<script>
// Calcula DV en cliente
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
const btnShow  = document.getElementById('btnShow');
const passInp  = document.getElementById('password');

rutInput.addEventListener('input', () => {
  // Limpiar y limitar a 9 dígitos (aceptamos 7-9)
  const limpio = rutInput.value.replace(/\D/g, '').slice(0, 9);
  rutInput.value = limpio;
  // Autocompletar DV si hay 7-9 dígitos
  dvInput.value = (limpio.length >= 7 && limpio.length <= 9) ? calcularDV(limpio) : '';
});

// Toggle mostrar contraseña
btnShow.addEventListener('click', () => {
  if (passInp.type === 'password') {
    passInp.type = 'text';
    btnShow.textContent = '🙈';
  } else {
    passInp.type = 'password';
    btnShow.textContent = '👁';
  }
});

// Validación básica
document.getElementById('loginForm').addEventListener('submit', (e) => {
  const rutOk = /^\d{7,9}$/.test(rutInput.value);
  const dvOk  = /^[0-9Kk]{1}$/.test(dvInput.value);
  if (!rutOk || !dvOk || !passInp.value) {
    e.preventDefault();
    alert('Completa correctamente RUT, DV y contraseña.');
  }
});
</script>
</body>
</html>
