<?php
require_once __DIR__ . '/../core/Controller.php';

class CursosController extends Controller
{
    public function index(): void {
        $this->requireLogin();

        $q      = trim($_GET['q'] ?? '');
        $anio   = isset($_GET['anio']) && $_GET['anio'] !== '' ? (int)$_GET['anio'] : null;
        $nivelF = isset($_GET['nivel']) && $_GET['nivel'] !== '' ? (int)$_GET['nivel'] : null;

        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 10;
        $total  = Curso::contar($q, $anio, $nivelF);
        $pages  = max(1, (int)ceil($total / $limit));
        $page   = min($page, $pages);
        $offset = ($page - 1) * $limit;

        $cursos   = Curso::listar($q, $limit, $offset, $anio, $nivelF);
        $niveles  = Curso::listaNiveles();
        $aniosSel = Curso::aniosDisponibles();

        $data = compact('cursos','q','page','pages','total','limit','niveles','anio','nivelF','aniosSel');
        $this->render('cursos/index', $data);
    }

    public function crear(): void {
        $this->requireLogin();

        $niveles    = Curso::listaNiveles();
        $profesores = Curso::listaProfesores();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $id = Curso::crear($_POST, (int)$_SESSION['user']['id']);
                header('Location: ' . BASE_URL . '/index.php?r=cursos/index&ok=1');
                exit;
            } catch (InvalidArgumentException $e) {
                $errores = json_decode($e->getMessage(), true) ?: ['general'=>'Datos inválidos'];
                $old = $_POST;
                $this->render('cursos/crear', compact('errores','old','niveles','profesores'));
                return;
            } catch (Throwable $e) {
                $errores = ['general'=>$e->getMessage()];
                $old = $_POST;
                $this->render('cursos/crear', compact('errores','old','niveles','profesores'));
                return;
            }
        }
        $this->render('cursos/crear', compact('niveles','profesores'));
    }

    public function editar(): void {
        $this->requireLogin();

        $id = (int)($_GET['id'] ?? 0);
        $curso = Curso::obtener($id);
        if (!$curso) { http_response_code(404); echo 'Curso no encontrado'; return; }

        $niveles    = Curso::listaNiveles();
        $profesores = Curso::listaProfesores();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Curso::actualizar($id, $_POST, (int)$_SESSION['user']['id']);
                header('Location: ' . BASE_URL . '/index.php?r=cursos/index&ok=1');
                exit;
            } catch (InvalidArgumentException $e) {
                $errores = json_decode($e->getMessage(), true) ?: ['general'=>'Datos inválidos'];
                $old = array_merge($curso, $_POST);
                $this->render('cursos/editar', compact('errores','old','id','niveles','profesores'));
                return;
            } catch (Throwable $e) {
                $errores = ['general'=>$e->getMessage()];
                $old = array_merge($curso, $_POST);
                $this->render('cursos/editar', compact('errores','old','id','niveles','profesores'));
                return;
            }
        }

        $this->render('cursos/editar', ['old'=>$curso, 'id'=>$id, 'niveles'=>$niveles, 'profesores'=>$profesores]);
    }

    public function eliminar(): void {
        $this->requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Método no permitido'; return; }

        $id = (int)($_POST['id'] ?? 0);
        try {
            Curso::eliminar($id, (int)$_SESSION['user']['id']);
            header('Location: ' . BASE_URL . '/index.php?r=cursos/index&ok=1');
            exit;
        } catch (Throwable $e) {
            $msg = urlencode($e->getMessage());
            header('Location: ' . BASE_URL . "/index.php?r=cursos/index&error=$msg");
            exit;
        }
    }
}
