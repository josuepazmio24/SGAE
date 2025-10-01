<?php
require_once __DIR__ . '/../core/Controller.php';

class AsignaturasController extends Controller
{
    public function index(): void {
        $this->requireLogin();

        $q      = trim($_GET['q'] ?? '');
        $nivel  = isset($_GET['nivel']) && $_GET['nivel'] !== '' ? (int)$_GET['nivel'] : null;
        $activo = isset($_GET['activo']) && $_GET['activo'] !== '' ? (int)$_GET['activo'] : null;

        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 10;
        $total  = Asignatura::contar($q, $nivel, $activo);
        $pages  = max(1, (int)ceil($total / $limit));
        $page   = min($page, $pages);
        $offset = ($page - 1) * $limit;

        $asigs   = Asignatura::listar($q, $limit, $offset, $nivel, $activo);
        $niveles = Asignatura::listaNiveles();

        $data = compact('asigs','q','page','pages','total','limit','niveles','nivel','activo');
        $this->render('asignaturas/index', $data);
    }

    public function crear(): void {
        $this->requireLogin();
        $niveles = Asignatura::listaNiveles();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $id = Asignatura::crear($_POST, (int)$_SESSION['user']['id']);
                header('Location: ' . BASE_URL . '/index.php?r=asignaturas/index&ok=1');
                exit;
            } catch (InvalidArgumentException $e) {
                $errores = json_decode($e->getMessage(), true) ?: ['general'=>'Datos inválidos'];
                $old = $_POST;
                $this->render('asignaturas/crear', compact('errores','old','niveles'));
                return;
            } catch (Throwable $e) {
                $errores = ['general'=>$e->getMessage()];
                $old = $_POST;
                $this->render('asignaturas/crear', compact('errores','old','niveles'));
                return;
            }
        }

        $this->render('asignaturas/crear', compact('niveles'));
    }

    public function editar(): void {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $asig = Asignatura::obtener($id);
        if (!$asig) { http_response_code(404); echo 'Asignatura no encontrada'; return; }

        $niveles = Asignatura::listaNiveles();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Asignatura::actualizar($id, $_POST, (int)$_SESSION['user']['id']);
                header('Location: ' . BASE_URL . '/index.php?r=asignaturas/index&ok=1');
                exit;
            } catch (InvalidArgumentException $e) {
                $errores = json_decode($e->getMessage(), true) ?: ['general'=>'Datos inválidos'];
                $old = array_merge($asig, $_POST);
                $this->render('asignaturas/editar', compact('errores','old','id','niveles'));
                return;
            } catch (Throwable $e) {
                $errores = ['general'=>$e->getMessage()];
                $old = array_merge($asig, $_POST);
                $this->render('asignaturas/editar', compact('errores','old','id','niveles'));
                return;
            }
        }

        $this->render('asignaturas/editar', ['old'=>$asig, 'id'=>$id, 'niveles'=>$niveles]);
    }

    public function eliminar(): void {
        $this->requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Método no permitido'; return; }

        $id = (int)($_POST['id'] ?? 0);
        try {
            Asignatura::eliminar($id, (int)$_SESSION['user']['id']);
            header('Location: ' . BASE_URL . '/index.php?r=asignaturas/index&ok=1');
            exit;
        } catch (Throwable $e) {
            $msg = urlencode($e->getMessage());
            header('Location: ' . BASE_URL . "/index.php?r=asignaturas/index&error=$msg");
            exit;
        }
    }
}
