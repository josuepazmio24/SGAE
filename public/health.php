<?php
require __DIR__ . '/../app/config/config.php';
require __DIR__ . '/../app/libs/Database.php';
try {
  $db = Database::get();
  echo "DB OK<br>";
  $db->query("SELECT 1")->fetch();
  echo "Query OK<br>";
} catch (Throwable $e) {
  http_response_code(500);
  echo "DB FAIL: " . $e->getMessage();
}
