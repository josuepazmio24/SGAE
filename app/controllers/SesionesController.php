<?php
require_once __DIR__ . '/../core/Controller.php';

class SesionesController extends Controller
{
    public function index(): void {
        $this->requireLogin();

        $q       = trim($_GET['q'] ?? '');
        $sec     = isset($_GET['seccion']) && $_GET['seccion'] !== '' ? (int)$_GET['seccion'] : null;
        $desde   = trim($_GET['desde'] ?? '');
        $hasta   = trim($_GET['hasta'] ?? '');
        if ($desde === '') $desde = null;
        if ($hasta === '') $hasta = null;

        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 10;
        $total  = SesionClase::contar($q, $sec, $desde, $hasta);
        $pages  = max(1, (int)ceil($total / $limit));
        $page   = min($page, $pages);
        $offset = ($page - 1) * $limit;

        $sesiones  = SesionClase::listar($q, $limit, $offset, $sec, $desde, $hasta);
        $secciones = SesionClase::listaSecciones();

        $data = compact('sesiones','q','page','pages','total','limit','secciones','sec','desde','hasta');
        $this->render('sesiones/index', $data);
    }

    public function crear(): void {
        $this->requireLogin();

        $secciones = SesionClase::listaSecciones();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $id = SesionClase::crear($_POST, (int)$_SESSION['user']['id']);
                header('Location: ' . BASE_URL . '/index.php?r=sesiones/index&ok=1');
                exit;
            } catch (InvalidArgumentException $e) {
                $errores = json_decode($e->getMessage(), true) ?: ['general'=>'Datos inválidos'];
                $old = $_POST;
                $this->render('sesiones/crear', compact('errores','old','secciones'));
                return;
            } catch (Throwable $e) {
                $errores = ['general'=>$e->getMessage()];
                $old = $_POST;
                $this->render('sesiones/crear', compact('errores','old','secciones'));
                return;
            }
        }

        $this->render('sesiones/crear', compact('secciones'));
    }

    public function editar(): void {
        $this->requireLogin();

        $id = (int)($_GET['id'] ?? 0);
        $ses = SesionClase::obtener($id);
        if (!$ses) { http_response_code(404); echo 'Sesión no encontrada'; return; }

        $secciones = SesionClase::listaSecciones();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                SesionClase::actualizar($id, $_POST, (int)$_SESSION['user']['id']);
                header('Location: ' . BASE_URL . '/index.php?r=sesiones/index&ok=1');
                exit;
            } catch (InvalidArgumentException $e) {
                $errores = json_decode($e->getMessage(), true) ?: ['general'=>'Datos inválidos'];
                $old = array_merge($ses, $_POST);
                $this->render('sesiones/editar', compact('errores','old','id','secciones'));
                return;
            } catch (Throwable $e) {
                $errores = ['general'=>$e->getMessage()];
                $old = array_merge($ses, $_POST);
                $this->render('sesiones/editar', compact('errores','old','id','secciones'));
                return;
            }
        }

        $this->render('sesiones/editar', ['old'=>$ses, 'id'=>$id, 'secciones'=>$secciones]);
    }

    public function eliminar(): void {
        $this->requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Método no permitido'; return; }

        $id = (int)($_POST['id'] ?? 0);
        try {
            SesionClase::eliminar($id, (int)$_SESSION['user']['id']);
            header('Location: ' . BASE_URL . '/index.php?r=sesiones/index&ok=1');
            exit;
        } catch (Throwable $e) {
            $msg = urlencode($e->getMessage());
            header('Location: ' . BASE_URL . "/index.php?r=sesiones/index&error=$msg");
            exit;
        }
    }
}
