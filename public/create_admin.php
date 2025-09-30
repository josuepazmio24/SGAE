<?php
require __DIR__ . '/../app/config/config.php';
require __DIR__ . '/../app/libs/Database.php';

header('Content-Type: text/plain; charset=utf-8');

try {
  $db = Database::get();
  $db->beginTransaction();

  // Persona ADMIN
  $db->prepare("INSERT INTO personas (rut, dv, nombres, apellidos, email, tipo_persona)
                VALUES (11111111, '1', 'Admin', 'General', 'admin@sgae.local', 'ADMIN')
                ON DUPLICATE KEY UPDATE email=VALUES(email), tipo_persona=VALUES(tipo_persona)")
     ->execute();

  // Hash bcrypt para 123456
  $hash = password_hash('123456', PASSWORD_BCRYPT);

  // Usuario ADMIN
  $db->prepare("INSERT INTO usuarios (username, password_hash, rol, rut_persona, estado)
                VALUES ('admin', :hash, 'ADMIN', 11111111, 'ACTIVO')
                ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash), rol='ADMIN', estado='ACTIVO'")
     ->execute([':hash' => $hash]);

  $db->commit();
  echo "ADMIN creado/reparado. Usuario: admin / Clave: 123456\n";
} catch (Throwable $e) {
  if ($db && $db->inTransaction()) $db->rollBack();
  http_response_code(500);
  echo "ERROR: " . $e->getMessage();
}
