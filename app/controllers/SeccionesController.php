<?php
require_once __DIR__ . '/../core/Controller.php';

class SeccionesController extends Controller
{
    public function index(): void {
        $this->requireLogin();

        $q     = trim($_GET['q'] ?? '');
        $curso = isset($_GET['curso']) && $_GET['curso'] !== '' ? (int)$_GET['curso'] : null;
        $asig  = isset($_GET['asig'])  && $_GET['asig']  !== '' ? (int)$_GET['asig']  : null;
        $prof  = isset($_GET['prof'])  && $_GET['prof']  !== '' ? (int)$_GET['prof']  : null;

        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 10;
        $total  = Seccion::contar($q, $curso, $asig, $prof);
        $pages  = max(1, (int)ceil($total / $limit));
        $page   = min($page, $pages);
        $offset = ($page - 1) * $limit;

        $secciones = Seccion::listar($q, $limit, $offset, $curso, $asig, $prof);
        $cursos    = Seccion::listaCursos();
        $asigs     = Seccion::listaAsignaturas();
        $profs     = Seccion::listaProfesores();

        $data = compact('secciones','q','page','pages','total','limit','cursos','asigs','profs','curso','asig','prof');
        $this->render('secciones/index', $data);
    }

    public function crear(): void {
        $this->requireLogin();

        $cursos = Seccion::listaCursos();
        $asigs  = Seccion::listaAsignaturas();
        $profs  = Seccion::listaProfesores();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $id = Seccion::crear($_POST, (int)$_SESSION['user']['id']);
                header('Location: ' . BASE_URL . '/index.php?r=secciones/index&ok=1');
                exit;
            } catch (InvalidArgumentException $e) {
                $errores = json_decode($e->getMessage(), true) ?: ['general'=>'Datos inválidos'];
                $old = $_POST;
                $this->render('secciones/crear', compact('errores','old','cursos','asigs','profs'));
                return;
            } catch (Throwable $e) {
                $errores = ['general'=>$e->getMessage()];
                $old = $_POST;
                $this->render('secciones/crear', compact('errores','old','cursos','asigs','profs'));
                return;
            }
        }

        $this->render('secciones/crear', compact('cursos','asigs','profs'));
    }

    public function editar(): void {
        $this->requireLogin();

        $id = (int)($_GET['id'] ?? 0);
        $sec = Seccion::obtener($id);
        if (!$sec) { http_response_code(404); echo 'Sección no encontrada'; return; }

        $cursos = Seccion::listaCursos();
        $asigs  = Seccion::listaAsignaturas();
        $profs  = Seccion::listaProfesores();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Seccion::actualizar($id, $_POST, (int)$_SESSION['user']['id']);
                header('Location: ' . BASE_URL . '/index.php?r=secciones/index&ok=1');
                exit;
            } catch (InvalidArgumentException $e) {
                $errores = json_decode($e->getMessage(), true) ?: ['general'=>'Datos inválidos'];
                $old = array_merge($sec, $_POST);
                $this->render('secciones/editar', compact('errores','old','id','cursos','asigs','profs'));
                return;
            } catch (Throwable $e) {
                $errores = ['general'=>$e->getMessage()];
                $old = array_merge($sec, $_POST);
                $this->render('secciones/editar', compact('errores','old','id','cursos','asigs','profs'));
                return;
            }
        }

        $this->render('secciones/editar', ['old'=>$sec, 'id'=>$id, 'cursos'=>$cursos, 'asigs'=>$asigs, 'profs'=>$profs]);
    }

    public function eliminar(): void {
        $this->requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Método no permitido'; return; }

        $id = (int)($_POST['id'] ?? 0);
        try {
            Seccion::eliminar($id, (int)$_SESSION['user']['id']);
            header('Location: ' . BASE_URL . '/index.php?r=secciones/index&ok=1');
            exit;
        } catch (Throwable $e) {
            $msg = urlencode($e->getMessage());
            header('Location: ' . BASE_URL . "/index.php?r=secciones/index&error=$msg");
            exit;
        }
    }
}
