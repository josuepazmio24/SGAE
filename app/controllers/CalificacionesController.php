<?php
require_once __DIR__ . '/../core/Controller.php';

class CalificacionesController extends Controller
{
    public function index(): void {
        $this->requireLogin();

        $q    = trim($_GET['q'] ?? '');
        $sec  = isset($_GET['seccion']) && $_GET['seccion'] !== '' ? (int)$_GET['seccion'] : null;
        $ev   = isset($_GET['evaluacion']) && $_GET['evaluacion'] !== '' ? (int)$_GET['evaluacion'] : null;
        $min  = trim($_GET['min'] ?? '');
        $max  = trim($_GET['max'] ?? '');
        $min  = ($min === '' ? null : (float)$min);
        $max  = ($max === '' ? null : (float)$max);

        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 10;
        $total  = Calificacion::contar($q, $sec, $ev, $min, $max);
        $pages  = max(1, (int)ceil($total / $limit));
        $page   = min($page, $pages);
        $offset = ($page - 1) * $limit;

        $rows       = Calificacion::listar($q, $limit, $offset, $sec, $ev, $min, $max);
        $secciones  = Calificacion::listaSecciones();
        $evals      = $sec ? Calificacion::listaEvaluacionesPorSeccion((int)$sec) : [];

        $data = compact('rows','q','page','pages','total','limit','secciones','evals','sec','ev','min','max');
        $this->render('calificaciones/index', $data);
    }

    public function crear(): void {
        $this->requireLogin();

        $secciones = Calificacion::listaSecciones();
        $seccionSel = (int)($_GET['seccion'] ?? ($_POST['seccion_id'] ?? 0));
        $evals   = $seccionSel ? Calificacion::listaEvaluacionesPorSeccion($seccionSel) : [];
        $alumnos = $seccionSel ? Calificacion::listaAlumnosPorSeccion($seccionSel) : [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $id = Calificacion::crear($_POST, (int)$_SESSION['user']['id']);
                header('Location: ' . BASE_URL . '/index.php?r=calificaciones/index&ok=1');
                exit;
            } catch (InvalidArgumentException $e) {
                $errores = json_decode($e->getMessage(), true) ?: ['general'=>'Datos inválidos'];
                $old = $_POST;
                $this->render('calificaciones/crear', compact('errores','old','secciones','evals','alumnos','seccionSel'));
                return;
            } catch (Throwable $e) {
                $errores = ['general'=>$e->getMessage()];
                $old = $_POST;
                $this->render('calificaciones/crear', compact('errores','old','secciones','evals','alumnos','seccionSel'));
                return;
            }
        }

        $this->render('calificaciones/crear', compact('secciones','evals','alumnos','seccionSel'));
    }

    public function editar(): void {
        $this->requireLogin();

        $id = (int)($_GET['id'] ?? 0);
        $row = Calificacion::obtener($id);
        if (!$row) { http_response_code(404); echo 'Calificación no encontrada'; return; }

        $secciones = Calificacion::listaSecciones();
        $evals   = Calificacion::listaEvaluacionesPorSeccion((int)$row['seccion_id']);
        $alumnos = Calificacion::listaAlumnosPorSeccion((int)$row['seccion_id']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Calificacion::actualizar($id, $_POST, (int)$_SESSION['user']['id']);
                header('Location: ' . BASE_URL . '/index.php?r=calificaciones/index&ok=1');
                exit;
            } catch (InvalidArgumentException $e) {
                $errores = json_decode($e->getMessage(), true) ?: ['general'=>'Datos inválidos'];
                $old = array_merge($row, $_POST);
                $this->render('calificaciones/editar', compact('errores','old','id','secciones','evals','alumnos'));
                return;
            } catch (Throwable $e) {
                $errores = ['general'=>$e->getMessage()];
                $old = array_merge($row, $_POST);
                $this->render('calificaciones/editar', compact('errores','old','id','secciones','evals','alumnos'));
                return;
            }
        }

        $this->render('calificaciones/editar', ['old'=>$row,'id'=>$id,'secciones'=>$secciones,'evals'=>$evals,'alumnos'=>$alumnos]);
    }

    public function eliminar(): void {
        $this->requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Método no permitido'; return; }

        $id = (int)($_POST['id'] ?? 0);
        try {
            Calificacion::eliminar($id, (int)$_SESSION['user']['id']);
            header('Location: ' . BASE_URL . '/index.php?r=calificaciones/index&ok=1');
            exit;
        } catch (Throwable $e) {
            $msg = urlencode($e->getMessage());
            header('Location: ' . BASE_URL . "/index.php?r=calificaciones/index&error=$msg");
            exit;
        }
    }
}
