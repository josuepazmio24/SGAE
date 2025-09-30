<?php
require_once __DIR__ . '/../core/Controller.php';

class AuthController extends Controller
{
    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = (string)($_POST['password'] ?? '');

            // DEBUG opcional (temporal): usa SIEMPRE dentro del método
            // error_log('LOGIN intenta user=' . $username);

            $user = Usuario::buscarPorUsername($username);
            if ($user && $user['estado'] === 'ACTIVO' && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'id'          => (int)$user['id'],
                    'username'    => $user['username'],
                    'rol'         => $user['rol'],
                    'rut_persona' => (int)$user['rut_persona'],
                ];
                // Auditoría segura (si falla, no rompe login)
                try {
                    Audit::log((int)$user['id'], 'LOGIN', 'USUARIO', (string)$user['id'], 'Inicio de sesión exitoso');
                } catch (Throwable $e) { error_log('WARN audit: ' . $e->getMessage()); }

                header('Location: ' . BASE_URL . '/index.php?r=dashboard/index');
                exit;
            }

            $error = 'Credenciales inválidas o usuario inactivo.';
            $this->render('auth/login', compact('error'));
            return;
        }

        // Si no hay usuarios, ir a setup
        if (Usuario::total() === 0) {
            header('Location: ' . BASE_URL . '/index.php?r=auth/setup');
            exit;
        }

        $this->render('auth/login');
    }

    public function setup(): void
    {
        if (Usuario::total() > 0) {
            header('Location: ' . BASE_URL . '/index.php?r=auth/login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $id = Usuario::crearAdmin([
                    'rut'       => (int)($_POST['rut'] ?? 0),
                    'dv'        => strtoupper(trim($_POST['dv'] ?? '')),
                    'nombres'   => trim($_POST['nombres'] ?? ''),
                    'apellidos' => trim($_POST['apellidos'] ?? ''),
                    'email'     => trim($_POST['email'] ?? ''),
                    'username'  => trim($_POST['username'] ?? ''),
                    'password'  => (string)($_POST['password'] ?? ''),
                ]);
                header('Location: ' . BASE_URL . '/index.php?r=auth/login');
                exit;
            } catch (Throwable $e) {
                $error = 'No se pudo crear el ADMIN: ' . $e->getMessage();
                $this->render('auth/setup', compact('error'));
                return;
            }
        }

        $this->render('auth/setup');
    }

    public function logout(): void
    {
        if (!empty($_SESSION['user']['id'])) {
            try {
                Audit::log(
                    (int)$_SESSION['user']['id'],
                    'LOGOUT',
                    'USUARIO',
                    (string)$_SESSION['user']['id'],
                    'Cierre de sesión'
                );
            } catch (Throwable $e) { error_log('WARN audit: ' . $e->getMessage()); }
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();

        header('Location: ' . BASE_URL . '/index.php?r=auth/login');
        exit;
    }
}
