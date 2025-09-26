<?php
// public/alumnos.php (vista)
// Requiere: includes/bootstrap.php (arranca sesión, $pdo, $BASE) y
//           includes/alumnos_model.php (funciones alumnos_* y cursos_*)

$TITLE = 'Alumnos';
require __DIR__ . '/_layout_top.php';
require __DIR__ . '/../includes/alumnos_model.php';

$flash = ['type'=>null,'msg'=>null];

// ====== POST (crear / actualizar) ======
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'crear_alumno') {
      $rut       = trim($_POST['rut'] ?? '');
      $nombre    = trim($_POST['nombre'] ?? '');
      $apellidos = trim($_POST['apellidos'] ?? '');
      $id_curso  = (int)($_POST['id_curso'] ?? 0);

      if ($rut==='' || $nombre==='' || $apellidos==='' || $id_curso<=0) {
        throw new RuntimeException('Todos los campos son obligatorios.');
      }
      $nums = preg_replace('/\D/','',$rut);
      if (!preg_match('/^\d{7,9}$/', $nums)) throw new RuntimeException('RUT inválido (solo números, 7 a 9 dígitos).');

      alumnos_crear($pdo, $nums, $nombre, $apellidos, $id_curso);
      $flash = ['type'=>'success','msg'=>'Alumno creado.'];

    } elseif ($accion === 'actualizar_alumno') {
      $id_alumno = (int)($_POST['id_alumno'] ?? 0);
      $rut       = trim($_POST['rut'] ?? '');
      $nombre    = trim($_POST['nombre'] ?? '');
      $apellidos = trim($_POST['apellidos'] ?? '');
      $id_curso  = (int)($_POST['id_curso'] ?? 0);

      if ($id_alumno<=0) throw new RuntimeException('ID inválido.');
      if ($rut==='' || $nombre==='' || $apellidos==='' || $id_curso<=0) {
        throw new RuntimeException('Faltan campos.');
      }
      $nums = preg_replace('/\D/','',$rut);
      if (!preg_match('/^\d{7,9}$/', $nums)) throw new RuntimeException('RUT inválido (solo números, 7 a 9 dígitos).');

      alumnos_actualizar($pdo, $id_alumno, $nums, $nombre, $apellidos, $id_curso);
      $flash = ['type'=>'success','msg'=>'Alumno actualizado.'];
    }
  } catch (Throwable $e) {
    $flash = ['type'=>'danger','msg'=>$e->getMessage()];
  }
}

// ====== GET (eliminar) ======
if (isset($_GET['eliminar']) && $_GET['eliminar']!=='') {
  try {
    alumnos_eliminar($pdo, (int)$_GET['eliminar']);
    $flash = ['type'=>'success','msg'=>'Alumno eliminado.'];
  } catch (Throwable $e) {
    $flash = ['type'=>'danger','msg'=>$e->getMessage()];
  }
}

// ====== Util: DV chileno ======
function dv_chileno_php(string $rutNums): string {
  $rutNums = preg_replace('/\D/','', $rutNums);
  $sum=0; $mul=2;
  for ($i=strlen($rutNums)-1; $i>=0; $i--) { $sum += intval($rutNums[$i])*$mul; $mul = ($mul===7)?2:$mul+1; }
  $res = 11 - ($sum % 11);
  return $res===11?'0':($res===10?'K':(string)$res);
}

// ====== Datos para pantalla ======
$alumnos = alumnos_obtenerTodos($pdo);   // trae c.nombre AS curso
$cursos  = cursos_obtenerTodos($pdo);    // para los <select>

$q = strtolower(trim($_GET['q'] ?? ''));
if ($q !== '') {
  $alumnos = array_values(array_filter(
    $alumnos,
    fn($a) =>
      str_contains(strtolower($a['nombre']), $q) ||
      str_contains(strtolower($a['apellidos']), $q) ||
      str_contains(strtolower($a['curso']), $q) ||
      str_contains((string)$a['rut'], $q)
  ));
}
?>
<h4>Gestión de Alumnos</h4>

<?php if ($flash['type']): ?>
  <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>">
    <?= htmlspecialchars($flash['msg']) ?>
  </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <form class="d-flex" role="search" method="get" action="">
    <input class="form-control me-2" type="search" name="q"
           placeholder="Buscar por nombre, apellidos, rut o curso"
           value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
    <button class="btn btn-outline-secondary">Buscar</button>
  </form>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearAlumno">
    + Nuevo Alumno
  </button>
</div>

<div class="table-responsive">
  <table class="table table-bordered table-hover align-middle">
    <thead class="table-light">
      <tr>
        <th>ID</th>
        <th>RUT</th>
        <th>DV</th>
        <th>Nombre</th>
        <th>Apellidos</th>
        <th>Curso</th>
        <th>Creado</th>
        <th style="width:180px;">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$alumnos): ?>
        <tr><td colspan="8" class="text-center text-muted">Sin resultados</td></tr>
      <?php else: foreach ($alumnos as $row): $dv = dv_chileno_php((string)$row['rut']); ?>
        <tr>
          <td><?= (int)$row['id_alumno'] ?></td>
          <td><?= htmlspecialchars($row['rut']) ?></td>
          <td><?= htmlspecialchars($dv) ?></td>
          <td><?= htmlspecialchars($row['nombre']) ?></td>
          <td><?= htmlspecialchars($row['apellidos']) ?></td>
          <td><?= htmlspecialchars($row['curso']) /* nombre de curso */ ?></td>
          <td><small class="text-muted"><?= htmlspecialchars($row['creado_en']) ?></small></td>
          <td>
            <button class="btn btn-sm btn-warning me-1"
                    data-bs-toggle="modal" data-bs-target="#modalEditarAlumno"
                    data-id="<?= (int)$row['id_alumno'] ?>"
                    data-rut="<?= htmlspecialchars($row['rut']) ?>"
                    data-nombre="<?= htmlspecialchars($row['nombre']) ?>"
                    data-apellidos="<?= htmlspecialchars($row['apellidos']) ?>"
                    data-id_curso="<?= (int)$row['id_curso'] ?>">
              Editar
            </button>
            <a href="?eliminar=<?= (int)$row['id_alumno'] ?>"
               class="btn btn-sm btn-danger"
               onclick="return confirm('¿Eliminar este alumno?');">Eliminar</a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<!-- ===== Modal CREAR ===== -->
<div class="modal fade" id="modalCrearAlumno" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="post" autocomplete="off">
      <input type="hidden" name="accion" value="crear_alumno">
      <div class="modal-header">
        <h5 class="modal-title">Nuevo Alumno</h5>
        <button class="btn-close" data-bs-dismiss="modal" type="button" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-8">
            <label class="form-label">RUT (sin DV)</label>
            <input name="rut" id="al_rut_crear" class="form-control" required pattern="\d{7,9}" placeholder="12345678">
          </div>
          <div class="col-4">
            <label class="form-label">DV (auto)</label>
            <input id="al_dv_crear" class="form-control" readonly>
          </div>
          <div class="col-12">
            <label class="form-label">Nombre</label>
            <input name="nombre" class="form-control" required>
          </div>
          <div class="col-12">
            <label class="form-label">Apellidos</label>
            <input name="apellidos" class="form-control" required>
          </div>
          <div class="col-12">
            <label class="form-label">Curso</label>
            <select name="id_curso" class="form-select" required>
              <option value="">— Selecciona un curso —</option>
              <?php foreach ($cursos as $c): ?>
                <option value="<?= (int)$c['id_curso'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary">Guardar</button>
      </div>
    </form>
  </div>
</div>

<!-- ===== Modal EDITAR ===== -->
<div class="modal fade" id="modalEditarAlumno" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="post" autocomplete="off">
      <input type="hidden" name="accion" value="actualizar_alumno">
      <input type="hidden" name="id_alumno" id="al_id_editar">
      <div class="modal-header">
        <h5 class="modal-title">Editar Alumno</h5>
        <button class="btn-close" data-bs-dismiss="modal" type="button" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-8">
            <label class="form-label">RUT (sin DV)</label>
            <input name="rut" id="al_rut_editar" class="form-control" required pattern="\d{7,9}">
          </div>
          <div class="col-4">
            <label class="form-label">DV (auto)</label>
            <input id="al_dv_editar" class="form-control" readonly>
          </div>
          <div class="col-12">
            <label class="form-label">Nombre</label>
            <input name="nombre" id="al_nombre_editar" class="form-control" required>
          </div>
          <div class="col-12">
            <label class="form-label">Apellidos</label>
            <input name="apellidos" id="al_apellidos_editar" class="form-control" required>
          </div>
          <div class="col-12">
            <label class="form-label">Curso</label>
            <select name="id_curso" id="al_idcurso_editar" class="form-select" required>
              <option value="">— Selecciona un curso —</option>
              <?php foreach ($cursos as $c): ?>
                <option value="<?= (int)$c['id_curso'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-warning" type="submit">Actualizar</button>
      </div>
    </form>
  </div>
</div>

<script>
// ==== Cálculo DV ====
function calcularDV(r){
  let s=0,m=2; for(let i=r.length-1;i>=0;i--){ s+=parseInt(r[i],10)*m; m=(m===7)?2:m+1 }
  const v = 11 - (s%11); return v===11?'0':(v===10?'K':String(v));
}
const alRutCrear=document.getElementById('al_rut_crear'), alDvCrear=document.getElementById('al_dv_crear');
if(alRutCrear&&alDvCrear){
  alRutCrear.addEventListener('input',()=>{
    const n=alRutCrear.value.replace(/\D/g,'');
    alDvCrear.value=(n.length>=7&&n.length<=9)?calcularDV(n):'';
  });
}

// ==== Precarga modal Editar ====
document.getElementById('modalEditarAlumno')?.addEventListener('show.bs.modal',e=>{
  const b=e.relatedTarget;
  const id   = b.getAttribute('data-id');
  const rut  = b.getAttribute('data-rut') || '';
  const nom  = b.getAttribute('data-nombre') || '';
  const ape  = b.getAttribute('data-apellidos') || '';
  const idc  = parseInt(b.getAttribute('data-id_curso')||'0',10) || 0;

  document.getElementById('al_id_editar').value = id;

  const ri=document.getElementById('al_rut_editar');
  const di=document.getElementById('al_dv_editar');
  ri.value = rut;
  const n=rut.replace(/\D/g,'');
  di.value = (n.length>=7&&n.length<=9)?calcularDV(n):'';

  document.getElementById('al_nombre_editar').value    = nom;
  document.getElementById('al_apellidos_editar').value = ape;

  const sel = document.getElementById('al_idcurso_editar');
  if (sel) sel.value = idc > 0 ? String(idc) : '';
});

// Recalcular DV si editan el RUT en el modal
document.getElementById('al_rut_editar')?.addEventListener('input',e=>{
  const n=e.target.value.replace(/\D/g,'');
  document.getElementById('al_dv_editar').value=(n.length>=7&&n.length<=9)?calcularDV(n):'';
});
</script>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
