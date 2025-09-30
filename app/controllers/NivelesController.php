<?php
require_once __DIR__ . '/../core/Controller.php';

class NivelesController extends Controller
{
    public function index(): void {
        $this->requireLogin();

        $q      = trim($_GET['q'] ?? '');
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 10;
        $total  = Nivel::contar($q);
        $pages  = max(1, (int)ceil($total / $limit));
        $page   = min($page, $pages);
        $offset = ($page - 1) * $limit;

        $niveles = Nivel::listar($q, $limit, $offset);
        $data = compact('niveles','q','page','pages','total','limit');
        $this->render('niveles/index', $data);
    }

    public function crear(): void {
        $this->requireLogin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $id = Nivel::crear($_POST, (int)$_SESSION['user']['id']);
                header('Location: ' . BASE_URL . '/index.php?r=niveles/index&ok=1');
                exit;
            } catch (InvalidArgumentException $e) {
                $errores = json_decode($e->getMessage(), true) ?: ['general' => 'Datos inválidos'];
                $old = $_POST;
                $this->render('niveles/crear', compact('errores','old'));
                return;
            } catch (Throwable $e) {
                $errores = ['general' => $e->getMessage()];
                $old = $_POST;
                $this->render('niveles/crear', compact('errores','old'));
                return;
            }
        }
        $this->render('niveles/crear');
    }

    public function editar(): void {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $nivel = Nivel::obtener($id);
        if (!$nivel) { http_response_code(404); echo 'Nivel no encontrado'; return; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Nivel::actualizar($id, $_POST, (int)$_SESSION['user']['id']);
                header('Location: ' . BASE_URL . '/index.php?r=niveles/index&ok=1');
                exit;
            } catch (InvalidArgumentException $e) {
                $errores = json_decode($e->getMessage(), true) ?: ['general' => 'Datos inválidos'];
                $old = array_merge($nivel, $_POST);
                $this->render('niveles/editar', compact('errores','old','id'));
                return;
            } catch (Throwable $e) {
                $errores = ['general' => $e->getMessage()];
                $old = array_merge($nivel, $_POST);
                $this->render('niveles/editar', compact('errores','old','id'));
                return;
            }
        }

        $this->render('niveles/editar', ['old'=>$nivel, 'id'=>$id]);
    }

    public function eliminar(): void {
        $this->requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); echo 'Método no permitido'; return;
        }
        $id = (int)($_POST['id'] ?? 0);
        try {
            Nivel::eliminar($id, (int)$_SESSION['user']['id']);
            header('Location: ' . BASE_URL . '/index.php?r=niveles/index&ok=1');
            exit;
        } catch (Throwable $e) {
            $msg = urlencode($e->getMessage());
            header('Location: ' . BASE_URL . "/index.php?r=niveles/index&error=$msg");
            exit;
        }
    }
}
