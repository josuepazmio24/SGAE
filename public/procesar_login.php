<?php
// public/procesar_login.php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php'; // para login_set_user() y helpers de sesión

// Base URL (maneja vhost con /public o sin él)
$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

// === CSRF ===
$csrf_post = $_POST['csrf'] ?? '';
$csrf_sess = $_SESSION['csrf_login'] ?? '';
if ($csrf_post === '' || $csrf_sess === '' || !hash_equals($csrf_sess, $csrf_post)) {
  header('Location: ' . ($BASE ?: '') . '/login.php?err=' . urlencode('Token inválido, vuelve a intentar.'));
  exit;
}

// === Helpers ===
function calcularDV(string $rutNumeros): string {
  $suma = 0; $multiplo = 2;
  for ($i = strlen($rutNumeros) - 1; $i >= 0; $i--) {
    $suma += intval($rutNumeros[$i]) * $multiplo;
    $multiplo = $multiplo < 7 ? $multiplo + 1 : 2;
  }
  $resto = 11 - ($suma % 11);
  if ($resto === 11) return '0';
  if ($resto === 10) return 'K';
  return (string)$resto;
}

// === Inputs ===
$rut  = isset($_POST['rut']) ? preg_replace('/\D/', '', $_POST['rut']) : '';
$dv   = isset($_POST['dv'])  ? strtoupper(trim($_POST['dv'])) : '';
$pass = $_POST['password'] ?? '';

// Validaciones mínimas
if ($rut === '' || $dv === '' || $pass === '') {
  header('Location: ' . ($BASE ?: '') . '/login.php?err=' . urlencode('Completa todos los campos.'));
  exit;
}
if (!preg_match('/^\d{7,9}$/', $rut)) {
  header('Location: ' . ($BASE ?: '') . '/login.php?err=' . urlencode('RUT inválido (7 a 9 dígitos).'));
  exit;
}
if (!preg_match('/^[0-9K]$/', $dv)) {
  header('Location: ' . ($BASE ?: '') . '/login.php?err=' . urlencode('DV inválido.'));
  exit;
}

// Validar DV contra el RUT
if ($dv !== calcularDV($rut)) {
  header('Location: ' . ($BASE ?: '') . '/login.php?err=' . urlencode('El DV no coincide con el RUT.'));
  exit;
}

// === Buscar usuario ===
// Asegúrate de que tu tabla `usuarios` tiene las columnas: rut, dv, nombre, correo, password, rol
$sql = "SELECT rut, dv, nombre, correo, password, rol
        FROM usuarios
        WHERE rut = :rut AND dv = :dv
        LIMIT 1";
$st = $pdo->prepare($sql);
$st->execute([':rut' => $rut, ':dv' => $dv]);
$user = $st->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($pass, $user['password'])) {
  header('Location: ' . ($BASE ?: '') . '/login.php?err=' . urlencode('Credenciales inválidas.'));
  exit;
}

// === OK: crear sesión segura ===
session_regenerate_id(true);

// Si tienes la función helper en includes/auth.php:
if (function_exists('login_set_user')) {
  login_set_user($user); // Debe guardar rut, dv, nombre, correo, rol en $_SESSION['usuario']
} else {
  // Fallback por si no existe el helper:
  $_SESSION['usuario'] = [
    'rut'    => $user['rut'],
    'dv'     => $user['dv'],
    'nombre' => $user['nombre'],
    'correo' => $user['correo'],
    'rol'    => strtolower($user['rol'] ?? 'alumno'),
  ];
}

// Consumir el token CSRF de login (para que no se pueda reusar)
unset($_SESSION['csrf_login']);

// Redirigir al dashboard
header('Location: ' . ($BASE ?: '') . '/dashboard.php');
exit;
