<?php
// includes/auth.php
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

/**
 * Exige sesión iniciada. Si no, redirige a login.
 */
function require_login(): void {
  if (empty($_SESSION['usuario'])) {
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    header('Location: ' . ($base ?: '') . '/login.php');
    exit;
  }
}

/**
 * Usuario actual (o null si no hay).
 */
function current_user(): ?array {
  return $_SESSION['usuario'] ?? null;
}

/**
 * Rol del usuario actual (string vacío si no hay).
 */
function user_role(): string {
  return strtolower((string)($_SESSION['usuario']['rol'] ?? ''));
}

/**
 * Llamar DESPUÉS de autenticar en el login.
 * $row debe venir de la DB (usuarios).
 */
function login_set_user(array $row): void {
  $_SESSION['usuario'] = [
    'rut'    => $row['rut']    ?? '',
    'nombre' => $row['nombre'] ?? '',
    'correo' => $row['correo'] ?? '',
    'rol'    => strtolower((string)($row['rol'] ?? '')),
  ];
}

/**
 * Cerrar sesión (opcional usar en logout.php)
 */
function logout_and_redirect(): void {
  $_SESSION = [];
  if (session_id()) { session_destroy(); }
  $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
  header('Location: ' . ($base ?: '') . '/login.php');
  exit;
}
