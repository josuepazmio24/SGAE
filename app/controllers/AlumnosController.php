<?php
require_once __DIR__ . '/../core/Controller.php';

class AlumnosController extends Controller
{
    public function index(): void {
        $this->requireLogin();

        $q = trim($_GET['q'] ?? '');
        $act = $_GET['activo'] ?? '';
        $activo = ($act === '' ? null : (int)$act);

        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 10;
        $total  = Alumno::contar($q, $activo);
        $pages  = max(1, (int)ceil($total / $limit));
        $page   = min($page, $pages);
        $offset = ($page - 1) * $limit;

        $rows = Alumno::listar($q, $limit, $offset, $activo);

        $this->render('alumnos/index', compact('rows','q','act','page','pages','total','limit'));
    }

    public function crear(): void {
        $this->requireLogin();

        $personas = Alumno::personasSinAlumno();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $rut = Alumno::crear($_POST);
                header('Location: ' . BASE_URL . '/index.php?r=alumnos/index&ok=1');
                exit;
            } catch (InvalidArgumentException $e) {
                $errores = json_decode($e->getMessage(), true) ?: ['general'=>'Datos inválidos'];
                $old = $_POST;
                $this->render('alumnos/crear', compact('errores','old','personas'));
                return;
            } catch (Throwable $e) {
                $errores = ['general'=>$e->getMessage()];
                $old = $_POST;
                $this->render('alumnos/crear', compact('errores','old','personas'));
                return;
            }
        }

        $this->render('alumnos/crear', compact('personas'));
    }

    public function editar(): void {
        $this->requireLogin();

        $rut = (int)($_GET['rut'] ?? 0);
        $row = Alumno::obtener($rut);
        if (!$row) { http_response_code(404); echo 'Alumno no encontrado'; return; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // Rut es PK: no cambiamos la persona vinculada desde aquí
                $_POST['rut'] = $rut;
                Alumno::actualizar($rut, $_POST);
                header('Location: ' . BASE_URL . '/index.php?r=alumnos/index&ok=1');
                exit;
            } catch (InvalidArgumentException $e) {
                $errores = json_decode($e->getMessage(), true) ?: ['general'=>'Datos inválidos'];
                $old = array_merge($row, $_POST);
                $this->render('alumnos/editar', compact('errores','old','rut'));
                return;
            } catch (Throwable $e) {
                $errores = ['general'=>$e->getMessage()];
                $old = array_merge($row, $_POST);
                $this->render('alumnos/editar', compact('errores','old','rut'));
                return;
            }
        }

        $this->render('alumnos/editar', ['old'=>$row, 'rut'=>$rut]);
    }

    public function eliminar(): void {
        $this->requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Método no permitido'; return; }

        $rut = (int)($_POST['rut'] ?? 0);
        try {
            Alumno::eliminar($rut);
            header('Location: ' . BASE_URL . '/index.php?r=alumnos/index&ok=1');
            exit;
        } catch (Throwable $e) {
            $msg = urlencode($e->getMessage());
            header('Location: ' . BASE_URL . "/index.php?r=alumnos/index&error=$msg");
            exit;
        }
    }
}
