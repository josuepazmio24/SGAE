<?php
require_once __DIR__ . '/../core/Controller.php';

class UsuariosController extends Controller
{
    public function index(): void {
        $this->requireLogin();

        $q      = trim($_GET['q'] ?? '');
        $rol    = trim($_GET['rol'] ?? '');
        $estado = trim($_GET['estado'] ?? '');
        if ($rol==='') $rol=null;
        if ($estado==='') $estado=null;

        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 10;
        $total  = Usuario::contar($q, $rol, $estado);
        $pages  = max(1, (int)ceil($total / $limit));
        $page   = min($page, $pages);
        $offset = ($page - 1) * $limit;

        $rows   = Usuario::listar($q, $limit, $offset, $rol, $estado);
        $roles  = Usuario::roles();
        $estados= Usuario::estados();

        $this->render('usuarios/index', compact('rows','q','rol','estado','roles','estados','page','pages','total','limit'));
    }

    public function crear(): void {
        $this->requireLogin();

        $roles    = Usuario::roles();
        $estados  = Usuario::estados();
        $personas = Usuario::personasSinUsuario();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $id = Usuario::crear($_POST);
                header('Location: ' . BASE_URL . '/index.php?r=usuarios/index&ok=1');
                exit;
            } catch (InvalidArgumentException $e) {
                $errores = json_decode($e->getMessage(), true) ?: ['general'=>'Datos inválidos'];
                $old = $_POST;
                $this->render('usuarios/crear', compact('errores','old','roles','estados','personas'));
                return;
            } catch (Throwable $e) {
                $errores = ['general'=>$e->getMessage()];
                $old = $_POST;
                $this->render('usuarios/crear', compact('errores','old','roles','estados','personas'));
                return;
            }
        }

        $this->render('usuarios/crear', compact('roles','estados','personas'));
    }

    public function editar(): void {
        $this->requireLogin();

        $id  = (int)($_GET['id'] ?? 0);
        $row = Usuario::obtener($id);
        if (!$row) { http_response_code(404); echo 'Usuario no encontrado'; return; }

        $roles    = Usuario::roles();
        $estados  = Usuario::estados();
        $personas = Usuario::personasTodas();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Usuario::actualizar($id, $_POST);
                header('Location: ' . BASE_URL . '/index.php?r=usuarios/index&ok=1');
                exit;
            } catch (InvalidArgumentException $e) {
                $errores = json_decode($e->getMessage(), true) ?: ['general'=>'Datos inválidos'];
                $old = array_merge($row, $_POST);
                $this->render('usuarios/editar', compact('errores','old','id','roles','estados','personas'));
                return;
            } catch (Throwable $e) {
                $errores = ['general'=>$e->getMessage()];
                $old = array_merge($row, $_POST);
                $this->render('usuarios/editar', compact('errores','old','id','roles','estados','personas'));
                return;
            }
        }

        $this->render('usuarios/editar', ['old'=>$row,'id'=>$id,'roles'=>$roles,'estados'=>$estados,'personas'=>$personas]);
    }

    public function password(): void {
        $this->requireLogin();

        $id  = (int)($_GET['id'] ?? 0);
        $row = Usuario::obtener($id);
        if (!$row) { http_response_code(404); echo 'Usuario no encontrado'; return; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $pass = (string)($_POST['password'] ?? '');
            try {
                Usuario::actualizarPassword($id, $pass);
                header('Location: ' . BASE_URL . '/index.php?r=usuarios/index&ok=1');
                exit;
            } catch (InvalidArgumentException $e) {
                $errores = json_decode($e->getMessage(), true) ?: ['general'=>'Datos inválidos'];
                $this->render('usuarios/password', compact('errores','id'));
                return;
            } catch (Throwable $e) {
                $errores = ['general'=>$e->getMessage()];
                $this->render('usuarios/password', compact('errores','id'));
                return;
            }
        }

        $this->render('usuarios/password', compact('id'));
    }

    public function eliminar(): void {
        $this->requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Método no permitido'; return; }

        $id = (int)($_POST['id'] ?? 0);
        try {
            Usuario::eliminar($id);
            header('Location: ' . BASE_URL . '/index.php?r=usuarios/index&ok=1');
            exit;
        } catch (Throwable $e) {
            $msg = urlencode($e->getMessage());
            header('Location: ' . BASE_URL . "/index.php?r=usuarios/index&error=$msg");
            exit;
        }
    }
}
