<?php
require __DIR__ . '/includes/db.php';

$rut = '12345678';
$dv  = '5'; // DV correcto para 12345678
$nombre = 'Juan Pérez';
$correo = 'juan@example.com';
$passPlano = '123456';
$hash = password_hash($passPlano, PASSWORD_BCRYPT);

$sql = "INSERT INTO usuarios (rut, dv, nombre, correo, password)
        VALUES (:rut, :dv, :nombre, :correo, :password)
        ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), correo=VALUES(correo), password=VALUES(password)";

$stmt = $pdo->prepare($sql);
$stmt->execute([
  ':rut' => $rut,
  ':dv' => strtoupper($dv),
  ':nombre' => $nombre,
  ':correo' => $correo,
  ':password' => $hash
]);

echo "Usuario de prueba insertado/actualizado con DV correcto (5).\n";
