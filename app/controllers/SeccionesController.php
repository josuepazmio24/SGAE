<?php
require_once __DIR__ . '/../core/Controller.php';

// Para que Intelephense encuentre los tipos (y en runtime estén cargados)
require_once __DIR__ . '/../libs/Auth.php';
require_once __DIR__ . '/../models/Seccion.php';
require_once __DIR__ . '/../models/Evaluacion.php';
require_once __DIR__ . '/../models/Calificacion.php';

class SeccionesController extends Controller
{
    /** LISTADO */
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

    /** CREAR */
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

    /** EDITAR */
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

    /** ELIMINAR */
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

    /** PLANILLA: vista alumno × evaluaciones + promedio */
   public function planillaNotas(): void {
    $this->requireLogin();

    $id = (int)($_GET['id'] ?? 0);
    $sec = Seccion::obtener($id);
    if (!$sec) { http_response_code(404); echo 'Sección no encontrada'; return; }

    // --- Guard de seguridad (puedes comentar estas 2 líneas para probar) ---
    // if (class_exists('Auth') && method_exists('Auth','rol') && Auth::rol()==='PROFESOR' && (int)$sec['profesor_rut'] !== Auth::rutPersona()) {
    //     http_response_code(403); echo 'Permiso denegado'; return;
    // }

    $alumnos = Seccion::alumnos($id);            // <= método del modelo (abajo)
    $evals   = Evaluacion::listarPorSeccion($id);// <= método del modelo (abajo)

    // mapa calificaciones
    $mapCal = Calificacion::mapaPorSeccion($id); // <= método del modelo (abajo)

    // suma ponderaciones para promedio ponderado
    $sumPond = 0.0; foreach ($evals as $e) $sumPond += (float)$e['ponderacion'];

    $this->render('secciones/planilla_notas', compact('sec','alumnos','evals','mapCal','sumPond'));
}


    /** EXPORT CSV de la planilla */
    public function exportPlanillaCsv(): void {
        $this->requireLogin();

        $id = (int)($_GET['id'] ?? 0);
        $sec = Seccion::obtener($id);
        if (!$sec) { http_response_code(404); echo 'Sección no encontrada'; return; }

        if (Auth::rol()==='PROFESOR' && (int)$sec['profesor_rut'] !== Auth::rutPersona()) {
            http_response_code(403); echo 'Permiso denegado'; return;
        }

        $alumnos = Seccion::alumnos($id);
        $evals   = Evaluacion::listarPorSeccion($id);

        if (method_exists('Calificacion','mapaPorSeccion')) {
            $mapCal = Calificacion::mapaPorSeccion($id);
        } else {
            $db = Database::get();
            $st = $db->prepare("SELECT c.evaluacion_id, c.alumno_rut, c.nota, c.observacion
                                FROM calificaciones c
                                JOIN evaluaciones e ON e.id=c.evaluacion_id
                                WHERE e.seccion_id=:sid");
            $st->execute([':sid'=>$id]);
            $mapCal = [];
            foreach ($st->fetchAll() as $r) {
                $mapCal[$r['evaluacion_id']][$r['alumno_rut']] = ['nota'=>$r['nota'], 'observacion'=>$r['observacion']];
            }
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="planilla_seccion_'.$id.'_'.date('Ymd_His').'.csv"');
        $out = fopen('php://output', 'w');

        // Header
        $hdr = ['RUT','Alumno'];
        foreach ($evals as $e) $hdr[] = $e['nombre'];
        $hdr[] = 'Promedio';
        fputcsv($out, $hdr);

        // Suma ponderaciones
        $sumPond = 0; foreach ($evals as $e) $sumPond += (float)$e['ponderacion'];

        foreach ($alumnos as $a) {
            $row = [ $a['rut'], $a['apellidos'].' '.$a['nombres'] ];
            $sumNotas=0; $sumPesos=0; $count=0;

            foreach ($evals as $e) {
                $nota = $mapCal[$e['id']][$a['rut']]['nota'] ?? '';
                $row[] = $nota;
                if ($nota !== '') {
                    $notaF = (float)$nota;
                    if ($sumPond>0 && (float)$e['ponderacion']>0) { $sumNotas += $notaF*(float)$e['ponderacion']; $sumPesos += (float)$e['ponderacion']; }
                    else { $sumNotas += $notaF; $count++; }
                }
            }
            $prom = null;
            if ($sumPond>0) $prom = ($sumPesos>0) ? round($sumNotas/$sumPesos,1) : null;
            else            $prom = ($count>0)    ? round($sumNotas/$count,1)    : null;

            $row[] = $prom !== null ? number_format($prom,1,'.','') : '';
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }
}
