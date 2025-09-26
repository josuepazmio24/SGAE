<?php
// public/procesar_registro.php
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
$nombre = trim($_POST['nombre'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$pass = $_POST['password'] ?? '';
$pass2 = $_POST['password2'] ?? '';

if ($rut === '' || $dv === '' || $nombre === '' || $correo === '' || $pass === '' || $pass2 === '') {
    header('Location: register.php?err=' . urlencode('Completa todos los campos.'));
    exit;
}
if (strlen($rut) < 6 || strlen($rut) > 8) {
    header('Location: register.php?err=' . urlencode('RUT inválido.'));
    exit;
}
$dvCalc = calcularDV($rut);
if ($dv !== $dvCalc) {
    header('Location: register.php?err=' . urlencode('El DV no coincide con el RUT.'));
    exit;
}
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    header('Location: register.php?err=' . urlencode('Correo inválido.'));
    exit;
}
if (strlen($pass) < 6) {
    header('Location: register.php?err=' . urlencode('La contraseña debe tener al menos 6 caracteres.'));
    exit;
}
if ($pass !== $pass2) {
    header('Location: register.php?err=' . urlencode('Las contraseñas no coinciden.'));
    exit;
}

// Validar unicidad de RUT y correo
$stmt = $pdo->prepare("SELECT 1 FROM usuarios WHERE rut=:rut AND dv=:dv LIMIT 1");
$stmt->execute([':rut'=>$rut, ':dv'=>$dv]);
if ($stmt->fetch()) {
    header('Location: register.php?err=' . urlencode('El RUT ya está registrado.'));
    exit;
}
$stmt = $pdo->prepare("SELECT 1 FROM usuarios WHERE correo=:correo LIMIT 1");
$stmt->execute([':correo'=>$correo]);
if ($stmt->fetch()) {
    header('Location: register.php?err=' . urlencode('El correo ya está registrado.'));
    exit;
}

// Crear hash y guardar
$hash = password_hash($pass, PASSWORD_DEFAULT);
$ins = $pdo->prepare("INSERT INTO usuarios (rut, dv, nombre, correo, password) VALUES (:rut, :dv, :nombre, :correo, :password)");
$ins->execute([':rut'=>$rut, ':dv'=>$dv, ':nombre'=>$nombre, ':correo'=>$correo, ':password'=>$hash]);

header('Location: login.php?err=' . urlencode('Cuenta creada. Inicia sesión.'));
exit;
