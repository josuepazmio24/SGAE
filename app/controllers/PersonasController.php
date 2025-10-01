<?php
require_once __DIR__ . '/../core/Controller.php';

class PersonasController extends Controller
{
    public function index(): void {
        $this->requireLogin();

        $q    = trim($_GET['q'] ?? '');
        $tipo = trim($_GET['tipo'] ?? '');
        $sexo = trim($_GET['sexo'] ?? '');
        if ($tipo === '') $tipo = null;
        if ($sexo === '') $sexo = null;

        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 10;
        $total  = Persona::contar($q, $tipo, $sexo);
        $pages  = max(1, (int)ceil($total / $limit));
        $page   = min($page, $pages);
        $offset = ($page - 1) * $limit;

        $rows    = Persona::listar($q, $limit, $offset, $tipo, $sexo);
        $tipos   = Persona::tipos();
        $sexos   = Persona::sexos();

        $this->render('personas/index', compact('rows','q','tipo','sexo','tipos','sexos','page','pages','total','limit'));
    }

    public function crear(): void {
        $this->requireLogin();

        $tipos = Persona::tipos();
        $sexos = Persona::sexos();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // permite ingresar RUT con puntos/guión
                if (!empty($_POST['rut_str'])) {
                    $_POST['rut'] = Persona::normalizarRut((string)$_POST['rut_str']);
                }
                $rut = Persona::crear($_POST);
                header('Location: ' . BASE_URL . '/index.php?r=personas/index&ok=1');
                exit;
            } catch (InvalidArgumentException $e) {
                $errores = json_decode($e->getMessage(), true) ?: ['general'=>'Datos inválidos'];
                $old = $_POST;
                $this->render('personas/crear', compact('errores','old','tipos','sexos'));
                return;
            } catch (Throwable $e) {
                $errores = ['general'=>$e->getMessage()];
                $old = $_POST;
                $this->render('personas/crear', compact('errores','old','tipos','sexos'));
                return;
            }
        }

        $this->render('personas/crear', compact('tipos','sexos'));
    }

    public function editar(): void {
        $this->requireLogin();

        $rut = (int)($_GET['rut'] ?? 0);
        $row = Persona::obtener($rut);
        if (!$row) { http_response_code(404); echo 'Persona no encontrada'; return; }

        $tipos = Persona::tipos();
        $sexos = Persona::sexos();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // si viene rut_str (formato con separadores) se ignora para edición (rut es PK)
                $_POST['rut'] = $rut;
                Persona::actualizar($rut, $_POST);
                header('Location: ' . BASE_URL . '/index.php?r=personas/index&ok=1');
                exit;
            } catch (InvalidArgumentException $e) {
                $errores = json_decode($e->getMessage(), true) ?: ['general'=>'Datos inválidos'];
                $old = array_merge($row, $_POST);
                $this->render('personas/editar', compact('errores','old','tipos','sexos','rut'));
                return;
            } catch (Throwable $e) {
                $errores = ['general'=>$e->getMessage()];
                $old = array_merge($row, $_POST);
                $this->render('personas/editar', compact('errores','old','tipos','sexos','rut'));
                return;
            }
        }

        $this->render('personas/editar', ['old'=>$row, 'tipos'=>$tipos, 'sexos'=>$sexos, 'rut'=>$rut]);
    }

    public function eliminar(): void {
        $this->requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Método no permitido'; return; }

        $rut = (int)($_POST['rut'] ?? 0);
        try {
            Persona::eliminar($rut);
            header('Location: ' . BASE_URL . '/index.php?r=personas/index&ok=1');
            exit;
        } catch (Throwable $e) {
            $msg = urlencode($e->getMessage());
            header('Location: ' . BASE_URL . "/index.php?r=personas/index&error=$msg");
            exit;
        }
    }
}
