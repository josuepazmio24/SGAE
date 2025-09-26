<?php
// public/procesar_login.php
require __DIR__ . '/../includes/db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

function calcularDV($rutNumeros) {
    $suma = 0; $multiplo = 2;
    for ($i = strlen($rutNumeros) - 1; $i >= 0; $i--) {
        $suma += intval($rutNumeros[$i]) * $multiplo;
        $multiplo = $multiplo < 7 ? $multiplo + 1 : 2;
    }
    $resto = 11 - ($suma % 11);
    if ($resto === 11) return "0";
    if ($resto === 10) return "K";
    return strval($resto);
}

$rut = isset($_POST['rut']) ? preg_replace('/\D/', '', $_POST['rut']) : '';
$dv  = isset($_POST['dv'])  ? strtoupper(trim($_POST['dv'])) : '';
$pass = $_POST['password'] ?? '';

if ($rut === '' || $dv === '' || $pass === '') {
    header('Location: login.php?err=' . urlencode('Completa todos los campos.'));
    exit;
}
if (strlen($rut) < 6 || strlen($rut) > 8) {
    header('Location: login.php?err=' . urlencode('RUT inválido.'));
    exit;
}

$dvCalc = calcularDV($rut);
if ($dv !== $dvCalc) {
    header('Location: login.php?err=' . urlencode('El DV no coincide con el RUT.'));
    exit;
}

// Buscar usuario
$sql = "SELECT rut, dv, nombre, correo, password FROM usuarios WHERE rut = :rut AND dv = :dv LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([':rut' => $rut, ':dv' => $dv]);

$user = $stmt->fetch();
if (!$user || !password_verify($pass, $user['password'])) {
    header('Location: login.php?err=' . urlencode('Credenciales inválidas.'));
    exit;
}

// OK: crear sesión
$_SESSION['usuario'] = [
    'rut' => $user['rut'],
    'dv' => $user['dv'],
    'nombre' => $user['nombre'],
    'correo' => $user['correo'],
];

header('Location: dashboard.php');
exit;
