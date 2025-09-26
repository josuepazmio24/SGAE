<?php
// includes/bootstrap.php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_login();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php'; // solo se cargará una vez

$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
$u = $_SESSION['usuario'] ?? ['nombre' => 'Usuario'];
