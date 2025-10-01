<?php
require_once __DIR__ . '/../core/Controller.php';

class EvaluacionesController extends Controller
{
    public function index(): void {
        $this->requireLogin();

        $q       = trim($_GET['q'] ?? '');
        $sec     = isset($_GET['seccion']) && $_GET['seccion'] !== '' ? (int)$_GET['seccion'] : null;
        $per     = isset($_GET['periodo']) && $_GET['periodo'] !== '' ? (int)$_GET['periodo'] : null;
        $tipo    = trim($_GET['tipo'] ?? '');
        $desde   = trim($_GET['desde'] ?? '');
        $hasta   = trim($_GET['hasta'] ?? '');
        if ($tipo === '')  $tipo = null;
        if ($desde === '') $desde = null;
        if ($hasta === '') $hasta = null;

        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 10;
        $total  = Evaluacion::contar($q, $sec, $per, $tipo, $desde, $hasta);
        $pages  = max(1, (int)ceil($total / $limit));
        $page   = min($page, $pages);
        $offset = ($page - 1) * $limit;

        $rows      = Evaluacion::listar($q, $limit, $offset, $sec, $per, $tipo, $desde, $hasta);
        $secciones = Evaluacion::listaSecciones();
        $periodos  = Evaluacion::listaPeriodos();
        $tipos     = Evaluacion::tipos();

        $data = compact('rows','q','page','pages','total','limit','secciones','periodos','tipos','sec','per','tipo','desde','hasta');
        $this->render('evaluaciones/index', $data);
    }

    public function crear(): void {
        $this->requireLogin();

        $secciones = Evaluacion::listaSecciones();
        $periodos  = Evaluacion::listaPeriodos();
        $tipos     = Evaluacion::tipos();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $id = Evaluacion::crear($_POST, (int)$_SESSION['user']['id']);
                header('Location: ' . BASE_URL . '/index.php?r=evaluaciones/index&ok=1');
                exit;
            } catch (InvalidArgumentException $e) {
                $errores = json_decode($e->getMessage(), true) ?: ['general'=>'Datos inválidos'];
                $old = $_POST;
                $this->render('evaluaciones/crear', compact('errores','old','secciones','periodos','tipos'));
                return;
            } catch (Throwable $e) {
                $errores = ['general'=>$e->getMessage()];
                $old = $_POST;
                $this->render('evaluaciones/crear', compact('errores','old','secciones','periodos','tipos'));
                return;
            }
        }

        $this->render('evaluaciones/crear', compact('secciones','periodos','tipos'));
    }

    public function editar(): void {
        $this->requireLogin();

        $id   = (int)($_GET['id'] ?? 0);
        $row  = Evaluacion::obtener($id);
        if (!$row) { http_response_code(404); echo 'Evaluación no encontrada'; return; }

        $secciones = Evaluacion::listaSecciones();
        $periodos  = Evaluacion::listaPeriodos();
        $tipos     = Evaluacion::tipos();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Evaluacion::actualizar($id, $_POST, (int)$_SESSION['user']['id']);
                header('Location: ' . BASE_URL . '/index.php?r=evaluaciones/index&ok=1');
                exit;
            } catch (InvalidArgumentException $e) {
                $errores = json_decode($e->getMessage(), true) ?: ['general'=>'Datos inválidos'];
                $old = array_merge($row, $_POST);
                $this->render('evaluaciones/editar', compact('errores','old','id','secciones','periodos','tipos'));
                return;
            } catch (Throwable $e) {
                $errores = ['general'=>$e->getMessage()];
                $old = array_merge($row, $_POST);
                $this->render('evaluaciones/editar', compact('errores','old','id','secciones','periodos','tipos'));
                return;
            }
        }

        $this->render('evaluaciones/editar', ['old'=>$row,'id'=>$id,'secciones'=>$secciones,'periodos'=>$periodos,'tipos'=>$tipos]);
    }

    public function eliminar(): void {
        $this->requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Método no permitido'; return; }

        $id = (int)($_POST['id'] ?? 0);
        try {
            Evaluacion::eliminar($id, (int)$_SESSION['user']['id']);
            header('Location: ' . BASE_URL . '/index.php?r=evaluaciones/index&ok=1');
            exit;
        } catch (Throwable $e) {
            $msg = urlencode($e->getMessage());
            header('Location: ' . BASE_URL . "/index.php?r=evaluaciones/index&error=$msg");
            exit;
        }
    }
}
