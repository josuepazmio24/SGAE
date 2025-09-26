<?php
// public/profesores.php
$TITLE = 'Profesores';
require __DIR__ . '/_layout_top.php';
require __DIR__ . '/../includes/profesores_model.php';

$flash = ['type'=>null,'msg'=>null];

// ==== Acciones (POST/GET) ====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'crear_profesor') {
      $rut=trim($_POST['rut']??''); $nombre=trim($_POST['nombre']??'');
      $apellidos=trim($_POST['apellidos']??''); $esp=trim($_POST['especialidad']??'');
      if ($rut===''||$nombre===''||$apellidos==='') throw new RuntimeException('RUT, nombre y apellidos son obligatorios.');
      $nums = preg_replace('/\D/','',$rut);
      if (!preg_match('/^\d{7,9}$/', $nums)) throw new RuntimeException('RUT inválido (7 a 9 dígitos, sin DV).');
      profesores_crear($pdo,$nums,$nombre,$apellidos,$esp);
      $flash=['type'=>'success','msg'=>'Profesor creado.'];

    } elseif ($accion === 'actualizar_profesor') {
      $id=(int)($_POST['id_profesor']??0);
      $rut=trim($_POST['rut']??''); $nombre=trim($_POST['nombre']??'');
      $apellidos=trim($_POST['apellidos']??''); $esp=trim($_POST['especialidad']??'');
      if ($id<=0) throw new RuntimeException('ID inválido.');
      if ($rut===''||$nombre===''||$apellidos==='') throw new RuntimeException('Faltan campos obligatorios.');
      $nums = preg_replace('/\D/','',$rut);
      if (!preg_match('/^\d{7,9}$/', $nums)) throw new RuntimeException('RUT inválido (7 a 9 dígitos, sin DV).');
      profesores_actualizar($pdo,$id,$nums,$nombre,$apellidos,$esp);
      $flash=['type'=>'success','msg'=>'Profesor actualizado.'];
    }
  } catch (Throwable $e) {
    $flash=['type'=>'danger','msg'=>$e->getMessage()];
  }
}

if (isset($_GET['eliminar']) && $_GET['eliminar']!=='') {
  try {
    profesores_eliminar($pdo,(int)$_GET['eliminar']);
    $flash=['type'=>'success','msg'=>'Profesor eliminado.'];
  } catch (Throwable $e) {
    $flash=['type'=>'danger','msg'=>$e->getMessage()];
  }
}

// ==== Utilidad DV para mostrar ====
function dv_chileno_php(string $rutNums): string {
  $rutNums = preg_replace('/\D/','', $rutNums);
  $sum=0; $mul=2;
  for ($i=strlen($rutNums)-1; $i>=0; $i--) { $sum += intval($rutNums[$i])*$mul; $mul = ($mul===7)?2:$mul+1; }
  $res = 11 - ($sum % 11);
  return $res===11?'0':($res===10?'K':(string)$res);
}
?>
<h4>Gestión de Profesores</h4>

<?php if ($flash['type']): ?>
  <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['msg']) ?></div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <form class="d-flex" role="search" method="get" action="">
    <input class="form-control me-2" type="search" name="q" placeholder="Buscar por nombre, apellidos, rut o especialidad" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
    <button class="btn btn-outline-secondary">Buscar</button>
  </form>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearProfesor">+ Nuevo Profesor</button>
</div>

<?php
$profes = profesores_obtenerTodos($pdo);
$q = strtolower(trim($_GET['q'] ?? ''));
if ($q !== '') {
  $profes = array_values(array_filter($profes, function($p) use ($q) {
    return str_contains(strtolower($p['nombre']), $q)
        || str_contains(strtolower($p['apellidos']), $q)
        || str_contains(strtolower((string)($p['especialidad'] ?? '')), $q)
        || str_contains($p['rut'], $q);
  }));
}
?>

<div class="table-responsive">
  <table class="table table-bordered table-hover align-middle">
    <thead class="table-light">
      <tr>
        <th>ID</th>
        <th>RUT</th>
        <th>DV</th>
        <th>Nombre</th>
        <th>Apellidos</th>
        <th>Especialidad</th>
        <th>Creado</th>
        <th style="width:180px;">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$profes): ?>
        <tr><td colspan="8" class="text-center text-muted">Sin resultados</td></tr>
      <?php else: foreach ($profes as $row): $dv = dv_chileno_php((string)$row['rut']); ?>
        <tr>
          <td><?= (int)$row['id_profesor'] ?></td>
          <td><?= htmlspecialchars($row['rut']) ?></td>
          <td><?= htmlspecialchars($dv) ?></td>
          <td><?= htmlspecialchars($row['nombre']) ?></td>
          <td><?= htmlspecialchars($row['apellidos']) ?></td>
          <td><?= htmlspecialchars((string)($row['especialidad'] ?? '')) ?></td>
          <td><small class="text-muted"><?= htmlspecialchars($row['creado_en']) ?></small></td>
          <td>
            <button class="btn btn-sm btn-warning me-1"
                    data-bs-toggle="modal" data-bs-target="#modalEditarProfesor"
                    data-id="<?= (int)$row['id_profesor'] ?>"
                    data-rut="<?= htmlspecialchars($row['rut']) ?>"
                    data-nombre="<?= htmlspecialchars($row['nombre']) ?>"
                    data-apellidos="<?= htmlspecialchars($row['apellidos']) ?>"
                    data-especialidad="<?= htmlspecialchars((string)($row['especialidad'] ?? '')) ?>">
              Editar
            </button>
            <a href="?eliminar=<?= (int)$row['id_profesor'] ?>" class="btn btn-sm btn-danger"
               onclick="return confirm('¿Eliminar este profesor?');">Eliminar</a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<!-- Modal CREAR Profesor -->
<div class="modal fade" id="modalCrearProfesor" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="post" autocomplete="off">
      <input type="hidden" name="accion" value="crear_profesor">
      <div class="modal-header"><h5 class="modal-title">Nuevo Profesor</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-8">
            <label class="form-label">RUT (sin DV)</label>
            <input type="text" name="rut" id="pf_rut_crear" class="form-control" placeholder="12345678" required pattern="\d{7,9}">
          </div>
          <div class="col-4">
            <label class="form-label">DV (auto)</label>
            <input type="text" id="pf_dv_crear" class="form-control" placeholder="K/0-9" readonly>
          </div>
          <div class="col-12">
            <label class="form-label">Nombre</label>
            <input type="text" name="nombre" class="form-control" required>
          </div>
          <div class="col-12">
            <label class="form-label">Apellidos</label>
            <input type="text" name="apellidos" class="form-control" required>
          </div>
          <div class="col-12">
            <label class="form-label">Especialidad (opcional)</label>
            <input type="text" name="especialidad" class="form-control" placeholder="ej: Matemáticas">
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

<!-- Modal EDITAR Profesor -->
<div class="modal fade" id="modalEditarProfesor" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="post" autocomplete="off">
      <input type="hidden" name="accion" value="actualizar_profesor">
      <input type="hidden" name="id_profesor" id="pf_id_editar">
      <div class="modal-header"><h5 class="modal-title">Editar Profesor</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-8">
            <label class="form-label">RUT (sin DV)</label>
            <input type="text" name="rut" id="pf_rut_editar" class="form-control" required pattern="\d{7,9}">
          </div>
          <div class="col-4">
            <label class="form-label">DV (auto)</label>
            <input type="text" id="pf_dv_editar" class="form-control" readonly>
          </div>
          <div class="col-12">
            <label class="form-label">Nombre</label>
            <input type="text" name="nombre" id="pf_nombre_editar" class="form-control" required>
          </div>
          <div class="col-12">
            <label class="form-label">Apellidos</label>
            <input type="text" name="apellidos" id="pf_apellidos_editar" class="form-control" required>
          </div>
          <div class="col-12">
            <label class="form-label">Especialidad (opcional)</label>
            <input type="text" name="especialidad" id="pf_especialidad_editar" class="form-control">
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
// Cálculo DV chileno (para visualizar)
function calcularDV(r){let s=0,m=2;for(let i=r.length-1;i>=0;i--){s+=parseInt(r[i],10)*m;m=(m===7)?2:m+1}const v=11-(s%11);return v===11?'0':v===10?'K':String(v)}

// Crear: autocompletar DV
const pfRutCrear=document.getElementById('pf_rut_crear'), pfDvCrear=document.getElementById('pf_dv_crear');
if(pfRutCrear && pfDvCrear){
  pfRutCrear.addEventListener('input',()=>{const n=pfRutCrear.value.replace(/\D/g,''); pfDvCrear.value=(n.length>=7&&n.length<=9)?calcularDV(n):'';});
}

// Editar: precargar datos + DV auto
document.getElementById('modalEditarProfesor')?.addEventListener('show.bs.modal',e=>{
  const b=e.relatedTarget;
  document.getElementById('pf_id_editar').value = b.getAttribute('data-id');
  const rut = b.getAttribute('data-rut') || '';
  const ri  = document.getElementById('pf_rut_editar');
  const di  = document.getElementById('pf_dv_editar');
  ri.value  = rut;
  const n   = rut.replace(/\D/g,'');
  di.value  = (n.length>=7 && n.length<=9) ? calcularDV(n) : '';
  document.getElementById('pf_nombre_editar').value       = b.getAttribute('data-nombre') || '';
  document.getElementById('pf_apellidos_editar').value    = b.getAttribute('data-apellidos') || '';
  document.getElementById('pf_especialidad_editar').value = b.getAttribute('data-especialidad') || '';
});

// Editar: actualizar DV al tipear
document.getElementById('pf_rut_editar')?.addEventListener('input',e=>{
  const n=e.target.value.replace(/\D/g,'');
  document.getElementById('pf_dv_editar').value=(n.length>=7&&n.length<=9)?calcularDV(n):'';
});
</script>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
