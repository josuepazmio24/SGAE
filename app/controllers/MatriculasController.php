<?php
require_once __DIR__ . '/../core/Controller.php';

class MatriculasController extends Controller
{
    public function index(): void {
        $this->requireLogin();

        $q = trim($_GET['q'] ?? '');

        // ✅ Evitar warnings cuando no vienen en la URL
        $anio    = (isset($_GET['anio'])     && $_GET['anio']     !== '') ? (int)$_GET['anio']     : null;
        $cursoId = (isset($_GET['curso_id']) && $_GET['curso_id'] !== '') ? (int)$_GET['curso_id'] : null;
        $estado  = (isset($_GET['estado'])   && $_GET['estado']   !== '') ?        $_GET['estado'] : null;

        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 10;
        $total  = Matricula::contar($q, $anio, $cursoId, $estado);
        $pages  = max(1, (int)ceil($total / $limit));
        $page   = min($page, $pages);
        $offset = ($page - 1) * $limit;

        $rows   = Matricula::listar($q, $limit, $offset, $anio, $cursoId, $estado);
        $anios  = Matricula::aniosDisponibles();
        $cursos = Matricula::cursos($anio); // acepta null → trae todos

        $this->render('matriculas/index', compact('rows','q','anio','cursoId','estado','anios','cursos','page','pages','total','limit'));
    }

    public function crear(): void {
        $this->requireLogin();

        $anios   = Matricula::aniosDisponibles();
        // ✅ Tomar año de la URL si viene, si no el más reciente o el actual
        $anioSel = (isset($_GET['anio']) && $_GET['anio'] !== '')
            ? (int)$_GET['anio']
            : (int)($anios[0] ?? date('Y'));

        $cursos  = Matricula::cursos($anioSel);

        // ✅ Tomar curso de la URL si viene, si no el primero de la lista (o 0)
        $cursoId = (isset($_GET['curso_id']) && $_GET['curso_id'] !== '')
            ? (int)$_GET['curso_id']
            : (int)($cursos[0]['id'] ?? 0);

        // Si hay curso seleccionado, mostrar sólo alumnos no matriculados en ese curso; si no, lista general
        $alumnos = $cursoId ? Matricula::alumnosDisponiblesParaCurso($cursoId) : Matricula::alumnos();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Matricula::crear($_POST);
                header('Location: ' . BASE_URL . '/index.php?r=matriculas/index&ok=1');
                exit;
            } catch (InvalidArgumentException $e) {
                $errores = json_decode($e->getMessage(), true) ?: ['general'=>'Datos inválidos'];
                $old = $_POST;
                $this->render('matriculas/crear', compact('errores','old','anios','anioSel','cursos','cursoId','alumnos'));
                return;
            } catch (Throwable $e) {
                $errores = ['general'=>$e->getMessage()];
                $old = $_POST;
                $this->render('matriculas/crear', compact('errores','old','anios','anioSel','cursos','cursoId','alumnos'));
                return;
            }
        }

        $this->render('matriculas/crear', compact('anios','anioSel','cursos','cursoId','alumnos'));
    }

    public function editar(): void {
        $this->requireLogin();

        $id  = (int)($_GET['id'] ?? 0);
        $row = Matricula::obtener($id);
        if (!$row) { http_response_code(404); echo 'Matrícula no encontrada'; return; }

        $anios  = Matricula::aniosDisponibles();
        $cursos = Matricula::cursos((int)$row['anio']);
        $alums  = Matricula::alumnos();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Matricula::actualizar($id, $_POST);
                header('Location: ' . BASE_URL . '/index.php?r=matriculas/index&ok=1');
                exit;
            } catch (InvalidArgumentException $e) {
                $errores = json_decode($e->getMessage(), true) ?: ['general'=>'Datos inválidos'];
                $old = array_merge($row, $_POST);
                $this->render('matriculas/editar', compact('errores','old','id','anios','cursos','alums'));
                return;
            } catch (Throwable $e) {
                $errores = ['general'=>$e->getMessage()];
                $old = array_merge($row, $_POST);
                $this->render('matriculas/editar', compact('errores','old','id','anios','cursos','alums'));
                return;
            }
        }

        $this->render('matriculas/editar', ['old'=>$row,'id'=>$id,'anios'=>$anios,'cursos'=>$cursos,'alums'=>$alums]);
    }

    public function eliminar(): void {
        $this->requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Método no permitido'; return; }

        $id = (int)($_POST['id'] ?? 0);
        try {
            Matricula::eliminar($id);
            header('Location: ' . BASE_URL . '/index.php?r=matriculas/index&ok=1');
            exit;
        } catch (Throwable $e) {
            $msg = urlencode($e->getMessage());
            header('Location: ' . BASE_URL . "/index.php?r=matriculas/index&error=$msg");
            exit;
        }
    }
}
