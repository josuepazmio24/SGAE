<?php
require_once __DIR__ . '/../core/Controller.php';

class PermisosController extends Controller
{
    public function index(): void {
        $this->requireLogin();
        Auth::require('*'); // o Auth::require('rbac.manage');
        $rows = Permiso::listar();
        $this->render('permisos/index', compact('rows'));
    }

    public function crear(): void {
        $this->requireLogin();
        Auth::require('*');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $id = Permiso::crear($_POST);
                Audit::log(Auth::id(), 'CREAR', 'PERMISO', (string)$id, 'Permiso creado');
                header('Location: ' . BASE_URL . '/index.php?r=permisos/index&ok=1');
                exit;
            } catch (Throwable $e) {
                $error = $e->getMessage();
                $old = $_POST;
                $this->render('permisos/crear', compact('error', 'old'));
                return;
            }
        }
        $this->render('permisos/crear');
    }

    public function editar(): void {
        $this->requireLogin();
        Auth::require('*');
        $id  = (int)($_GET['id'] ?? 0);
        $row = Permiso::obtener($id);
        if (!$row) { http_response_code(404); echo 'Permiso no encontrado'; return; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Permiso::actualizar($id, $_POST);
                Audit::log(Auth::id(), 'EDITAR', 'PERMISO', (string)$id, 'Permiso editado');
                header('Location: ' . BASE_URL . '/index.php?r=permisos/index&ok=1');
                exit;
            } catch (Throwable $e) {
                $error = $e->getMessage();
                $this->render('permisos/editar', compact('error','row','id'));
                return;
            }
        }
        $this->render('permisos/editar', compact('row','id'));
    }

    public function eliminar(): void {
        $this->requireLogin();
        Auth::require('*');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Método no permitido'; return; }

        $id = (int)($_POST['id'] ?? 0);
        try {
            Permiso::eliminar($id);
            Audit::log(Auth::id(), 'ELIMINAR', 'PERMISO', (string)$id, 'Permiso eliminado');
            header('Location: ' . BASE_URL . '/index.php?r=permisos/index&ok=1');
            exit;
        } catch (Throwable $e) {
            $msg = urlencode($e->getMessage());
            header('Location: ' . BASE_URL . "/index.php?r=permisos/index&error=$msg");
            exit;
        }
    }
}
