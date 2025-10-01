<?php
require_once __DIR__ . '/../core/Controller.php';
class AuditoriaController extends Controller
{
    /** Guard RBAC: ADMIN (enum), '*' o 'rbac.manage' */
    private function requireAudit(): void {
        $this->requireLogin();
        if (!(Auth::rol()==='ADMIN' || Auth::can('*') || Auth::can('rbac.manage'))) {
            http_response_code(403);
            echo 'Permiso denegado';
            exit;
        }
    }

    public function index(): void {
        $this->requireAudit();

        // Filtros
        $f = [
            'q'       => trim($_GET['q'] ?? ''),
            'usuario' => trim($_GET['usuario'] ?? ''),
            'accion'  => trim($_GET['accion'] ?? ''),
            'entidad' => trim($_GET['entidad'] ?? ''),
            'desde'   => trim($_GET['desde'] ?? ''),
            'hasta'   => trim($_GET['hasta'] ?? ''),
        ];

        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 20;
        $offset = ($page - 1) * $limit;

        $total  = Auditoria::contar($f);
        $rows   = Auditoria::listar($f, $limit, $offset);

        // combos
        $acciones  = Auditoria::acciones();
        $entidades = Auditoria::entidades();

        $this->render('auditoria/index', compact('rows','total','page','limit','f','acciones','entidades'));
    }

    /** Export CSV según filtros */
    public function export(): void {
        $this->requireAudit();

        $f = [
            'q'       => trim($_GET['q'] ?? ''),
            'usuario' => trim($_GET['usuario'] ?? ''),
            'accion'  => trim($_GET['accion'] ?? ''),
            'entidad' => trim($_GET['entidad'] ?? ''),
            'desde'   => trim($_GET['desde'] ?? ''),
            'hasta'   => trim($_GET['hasta'] ?? ''),
        ];

        $data = Auditoria::export($f);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="auditoria_'.date('Ymd_His').'.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID','Fecha','Usuario','Rol','Persona','Acción','Entidad','Entidad ID','Descripción','IP']);
        foreach ($data as $r) {
            fputcsv($out, [
                $r['id'], $r['fecha'], $r['username'], $r['rol'], $r['persona'],
                $r['accion'], $r['entidad'], $r['entidad_id'], $r['descripcion'], $r['ip']
            ]);
        }
        fclose($out);
        exit;
    }
}
