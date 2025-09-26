<?php
// public/login.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!empty($_SESSION['usuario'])) {
    header('Location: dashboard.php'); exit;
}
$err = $_GET['err'] ?? null;
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>SGAE</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center vh-100">
  <div class="card shadow p-4" style="max-width: 420px; width: 100%;">
    <h4 class="mb-3 text-center">SGAE</h4>

    <?php if ($err): ?>
      <div class="alert alert-danger py-2"><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <form action="procesar_login.php" method="post" id="loginForm" novalidate>
      <!-- RUT -->
      <div class="mb-3">
        <label for="rut" class="form-label">RUT (sin DV)</label>
        <input type="text" class="form-control" id="rut" name="rut" inputmode="numeric" maxlength="8" required>
        <div class="form-text">Solo números, sin puntos ni guion. Ej: 12345678</div>
      </div>

      <!-- DV -->
      <div class="mb-3">
        <label for="dv" class="form-label">Dígito Verificador</label>
        <input type="text" class="form-control" id="dv" name="dv" maxlength="1" required readonly>
      </div>

      <!-- Password -->
      <div class="mb-3">
        <label for="password" class="form-label">Contraseña</label>
        <input type="password" class="form-control" id="password" name="password" required>
      </div>

      <button type="submit" class="btn btn-primary w-100">Ingresar</button>
      <div class="text-center mt-2">
  
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

rutInput.addEventListener('input', () => {
  const limpio = rutInput.value.replace(/\D/g, '').slice(0, 8);
  rutInput.value = limpio;
  dvInput.value = limpio ? calcularDV(limpio) : '';
});

// Validación simple
document.getElementById('loginForm').addEventListener('submit', (e) => {
  if (!rutInput.value || !dvInput.value || !document.getElementById('password').value) {
    e.preventDefault();
    alert('Completa RUT, DV y contraseña.');
  }
});
</script>
</body>
</html>
