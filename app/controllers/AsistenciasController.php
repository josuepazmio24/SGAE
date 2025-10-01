<?php
require_once __DIR__ . '/../core/Controller.php';

class AsistenciasController extends Controller
{
    public function index(): void {
        $this->requireLogin();

        $q      = trim($_GET['q'] ?? '');
        $sec    = isset($_GET['seccion']) && $_GET['seccion'] !== '' ? (int)$_GET['seccion'] : null;
        $estado = trim($_GET['estado'] ?? '');
        $desde  = trim($_GET['desde'] ?? '');
        $hasta  = trim($_GET['hasta'] ?? '');
        if ($estado === '') $estado = null;
        if ($desde === '')  $desde  = null;
        if ($hasta === '')  $hasta  = null;

        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 10;
        $total  = Asistencia::contar($q, $sec, $desde, $hasta, $estado);
        $pages  = max(1, (int)ceil($total / $limit));
        $page   = min($page, $pages);
        $offset = ($page - 1) * $limit;

        $rows       = Asistencia::listar($q, $limit, $offset, $sec, $desde, $hasta, $estado);
        $secciones  = Asistencia::listaSecciones();
        $estados    = Asistencia::estados();

        $data = compact('rows','q','page','pages','total','limit','secciones','sec','desde','hasta','estado','estados');
        $this->render('asistencias/index', $data);
    }

    public function crear(): void {
        $this->requireLogin();

        $secciones = Asistencia::listaSecciones();
        $seccionSel = (int)($_GET['seccion'] ?? ($_POST['seccion_id'] ?? 0));
        $alumnos = $seccionSel ? Asistencia::listaAlumnosPorSeccion($seccionSel) : [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $id = Asistencia::crear($_POST, (int)$_SESSION['user']['id']);
                header('Location: ' . BASE_URL . '/index.php?r=asistencias/index&ok=1');
                exit;
            } catch (InvalidArgumentException $e) {
                $errores = json_decode($e->getMessage(), true) ?: ['general'=>'Datos inválidos'];
                $old = $_POST;
                $this->render('asistencias/crear', compact('errores','old','secciones','alumnos','seccionSel'));
                return;
            } catch (Throwable $e) {
                $errores = ['general'=>$e->getMessage()];
                $old = $_POST;
                $this->render('asistencias/crear', compact('errores','old','secciones','alumnos','seccionSel'));
                return;
            }
        }

        $this->render('asistencias/crear', compact('secciones','alumnos','seccionSel'));
    }

    public function editar(): void {
        $this->requireLogin();

        $id = (int)($_GET['id'] ?? 0);
        $row = Asistencia::obtener($id);
        if (!$row) { http_response_code(404); echo 'Asistencia no encontrada'; return; }

        $secciones = Asistencia::listaSecciones();
        $alumnos   = Asistencia::listaAlumnosPorSeccion((int)$row['seccion_id']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Asistencia::actualizar($id, $_POST, (int)$_SESSION['user']['id']);
                header('Location: ' . BASE_URL . '/index.php?r=asistencias/index&ok=1');
                exit;
            } catch (InvalidArgumentException $e) {
                $errores = json_decode($e->getMessage(), true) ?: ['general'=>'Datos inválidos'];
                $old = array_merge($row, $_POST);
                $this->render('asistencias/editar', compact('errores','old','id','secciones','alumnos'));
                return;
            } catch (Throwable $e) {
                $errores = ['general'=>$e->getMessage()];
                $old = array_merge($row, $_POST);
                $this->render('asistencias/editar', compact('errores','old','id','secciones','alumnos'));
                return;
            }
        }

        $this->render('asistencias/editar', ['old'=>$row,'id'=>$id,'secciones'=>$secciones,'alumnos'=>$alumnos]);
    }

    public function eliminar(): void {
        $this->requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Método no permitido'; return; }

        $id = (int)($_POST['id'] ?? 0);
        try {
            Asistencia::eliminar($id, (int)$_SESSION['user']['id']);
            header('Location: ' . BASE_URL . '/index.php?r=asistencias/index&ok=1');
            exit;
        } catch (Throwable $e) {
            $msg = urlencode($e->getMessage());
            header('Location: ' . BASE_URL . "/index.php?r=asistencias/index&error=$msg");
            exit;
        }
    }
}
