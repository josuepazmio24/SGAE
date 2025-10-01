<?php
abstract class Controller {
protected function render(string $viewPath, array $data = [], ?string $layout = 'layout'): void {
extract($data, EXTR_SKIP);
ob_start();
require __DIR__ . '/../views/' . $viewPath . '.php';
$content = ob_get_clean();
if ($layout) {
require __DIR__ . '/../views/' . $layout . '.php';
} else {
echo $content;
}
}

protected function requireRole(string ...$roles): void {
    $u = $_SESSION['user'] ?? null;
    if (!$u || !in_array($u['rol'], $roles, true)) {
        header('Location: ' . BASE_URL . '/index.php?r=auth/login');
        exit;
    }
}

protected function authorize(string $perm): void {
    Auth::require($perm);
}
protected function isLogged(): bool { return !empty($_SESSION['user']); }


protected function requireLogin(): void {
if (!$this->isLogged()) {
header('Location: ' . BASE_URL . '/index.php?r=auth/login');
exit;
}
}
}