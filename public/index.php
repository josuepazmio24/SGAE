<?php
session_start();
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require __DIR__ . '/../app/config/config.php';
require __DIR__ . '/../app/libs/Database.php';
require __DIR__ . '/../app/libs/Audit.php';
require __DIR__ . '/../app/core/View.php';


// Autoload manual simple para models y controllers
spl_autoload_register(function($class){
$paths = [
__DIR__ . '/../app/models/' . $class . '.php',
__DIR__ . '/../app/controllers/' . $class . '.php',
__DIR__ . '/../app/core/' . $class . '.php',
__DIR__ . '/../app/libs/' . $class . '.php',
];
foreach ($paths as $p) if (is_file($p)) { require_once $p; return; }
});


$route = $_GET['r'] ?? '';
if ($route === '') {
$route = (!empty($_SESSION['user'])) ? 'dashboard/index' : 'auth/login';
}
list($controller, $action) = array_pad(explode('/', $route, 2), 2, 'index');
$controllerClass = ucfirst($controller) . 'Controller';


if (!class_exists($controllerClass) || !method_exists($controllerClass, $action)) {
http_response_code(404);
echo '<h1>404</h1><p>Ruta no encontrada.</p>';
exit;
}


$instance = new $controllerClass();
$instance->$action();