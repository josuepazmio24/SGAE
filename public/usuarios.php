<?php
$TITLE = 'Usuarios';
require __DIR__ . '/_layout_top.php';
require __DIR__ . '/../includes/usuarios_model.php';

$flash = ['type'=>null,'msg'=>null];
$ROLES_PERMITIDOS = ['admin','docente','alumno','apoderado'];

if ($_SERVER['REQUEST_METHOD']==='POST') {
  try {
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'crear_usuario') {
      $rut = trim($_POST['rut'] ?? ''); $dv = trim($_POST['dv'] ?? '');
      $nombre = trim($_POST['nombre'] ?? ''); $correo = trim($_POST['correo'] ?? '');
      $password = $_POST['password'] ?? ''; $rol = strtolower(trim($_POST['rol'] ?? 'alumno'));
      if ($rut===''||$dv===''||$nombre===''||$correo===''||$password==='') throw new RuntimeException('Todos los campos son obligatorios.');
      if (!preg_match('/^\d{7,9}$/', $rut)) throw new RuntimeException('RUT inválido.');
      if (!preg_match('/^[0-9Kk]{1}$/', $dv)) throw new RuntimeException('DV inválido.');
      if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Correo inválido.');
      if (!in_array($rol, $ROLES_PERMITIDOS, true)) throw new RuntimeException('Rol inválido.');
      usuarios_crear($pdo, $rut, $dv, $nombre, $correo, $password, $rol);
      $flash = ['type'=>'success','msg'=>'Usuario creado.'];
    } elseif ($accion === 'actualizar_usuario') {
      $rut = trim($_POST['rut'] ?? ''); $dv = trim($_POST['dv'] ?? '');
      $nombre = trim($_POST['nombre'] ?? ''); $correo = trim($_POST['correo'] ?? '');
      $password = $_POST['password'] ?? ''; $rol = strtolower(trim($_POST['rol'] ?? ''));
      if ($rut===''||$dv===''||$nombre===''||$correo==='') throw new RuntimeException('Faltan campos.');
      if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Correo inválido.');
      if ($rol !== '' && !in_array($rol,$ROLES_PERMITIDOS,true)) throw new RuntimeException('Rol inválido.');
      usuarios_actualizar($pdo, $rut, $dv, $nombre, $correo, $password !== '' ? $password : null, $rol !== '' ? $rol : null);
      $flash = ['type'=>'success','msg'=>'Usuario actualizado.'];
    }
  } catch (Throwable $e) {
    $flash = ['type'=>'danger','msg'=>$e->getMessage()];
  }
}
if (isset($_GET['eliminar']) && $_GET['eliminar']!=='') {
  try { usuarios_eliminar($pdo, $_GET['eliminar']); $flash=['type'=>'success','msg'=>'Usuario eliminado.']; }
  catch (Throwable $e) { $flash=['type'=>'danger','msg'=>$e->getMessage()]; }
}

?>
<h4>Gestión de Usuarios</h4>
<?php if ($flash['type']): ?><div class="alert alert-<?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['msg']) ?></div><?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <form class="d-flex" role="search" method="get" action="">
    <input class="form-control me-2" type="search" name="q" placeholder="Buscar por nombre, correo, rut o rol" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
    <button class="btn btn-outline-secondary">Buscar</button>
  </form>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearUsuario">+ Nuevo Usuario</button>
</div>
<?php
$usuarios = usuarios_obtenerTodos($pdo);
$q = strtolower(trim($_GET['q'] ?? ''));
if ($q !== '') {
  $usuarios = array_values(array_filter($usuarios, fn($u)=> str_contains(strtolower($u['nombre']),$q)
    || str_contains(strtolower($u['correo']),$q) || str_contains(strtolower($u['rol']??''),$q) || str_contains($u['rut'],$q)));
}
?>
<div class="table-responsive">
  <table class="table table-bordered table-hover align-middle">
    <thead class="table-light"><tr>
      <th>RUT</th><th>DV</th><th>Nombre</th><th>Correo</th><th>Rol</th><th style="width:180px;">Acciones</th></tr></thead>
    <tbody>
      <?php if (!$usuarios): ?><tr><td colspan="6" class="text-center text-muted">Sin resultados</td></tr>
      <?php else: foreach ($usuarios as $row): ?>
        <tr>
          <td><?= htmlspecialchars($row['rut']) ?></td>
          <td><?= htmlspecialchars($row['dv']) ?></td>
          <td><?= htmlspecialchars($row['nombre']) ?></td>
          <td><?= htmlspecialchars($row['correo']) ?></td>
          <td><?= htmlspecialchars($row['rol'] ?? 'alumno') ?></td>
          <td>
            <button class="btn btn-sm btn-warning me-1" data-bs-toggle="modal" data-bs-target="#modalEditarUsuario"
              data-rut="<?= htmlspecialchars($row['rut']) ?>" data-dv="<?= htmlspecialchars($row['dv']) ?>"
              data-nombre="<?= htmlspecialchars($row['nombre']) ?>" data-correo="<?= htmlspecialchars($row['correo']) ?>"
              data-rol="<?= htmlspecialchars($row['rol'] ?? 'alumno') ?>">Editar</button>
            <a href="?eliminar=<?= urlencode($row['rut']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este usuario?');">Eliminar</a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<!-- Modales -->
<div class="modal fade" id="modalCrearUsuario" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="post" autocomplete="off">
  <input type="hidden" name="accion" value="crear_usuario">
  <div class="modal-header"><h5 class="modal-title">Nuevo Usuario</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><div class="row g-3">
    <div class="col-8"><label class="form-label">RUT (sin DV)</label><input name="rut" id="rutCrear" class="form-control" required pattern="\d{7,9}"></div>
    <div class="col-4"><label class="form-label">DV</label><input name="dv" id="dvCrear" class="form-control" required pattern="[0-9Kk]{1}"></div>
    <div class="col-12"><label class="form-label">Nombre</label><input name="nombre" class="form-control" required></div>
    <div class="col-12"><label class="form-label">Correo</label><input name="correo" type="email" class="form-control" required></div>
    <div class="col-12"><label class="form-label">Rol</label>
      <select name="rol" class="form-select" required>
        <option value="alumno" selected>Alumno</option><option value="docente">Docente</option>
        <option value="apoderado">Apoderado</option><option value="admin">Administrador</option>
      </select></div>
    <div class="col-12"><label class="form-label">Password</label><input name="password" type="password" class="form-control" required minlength="6"></div>
  </div></div>
  <div class="modal-footer"><button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary">Guardar</button></div>
</form></div></div>

<div class="modal fade" id="modalEditarUsuario" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="post" autocomplete="off">
  <input type="hidden" name="accion" value="actualizar_usuario">
  <div class="modal-header"><h5 class="modal-title">Editar Usuario</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><div class="row g-3">
    <div class="col-8"><label class="form-label">RUT</label><input name="rut" id="rutEditar" class="form-control" readonly></div>
    <div class="col-4"><label class="form-label">DV</label><input name="dv" id="dvEditar" class="form-control" required pattern="[0-9Kk]{1}"></div>
    <div class="col-12"><label class="form-label">Nombre</label><input name="nombre" id="nombreEditar" class="form-control" required></div>
    <div class="col-12"><label class="form-label">Correo</label><input name="correo" id="correoEditar" type="email" class="form-control" required></div>
    <div class="col-12"><label class="form-label">Rol</label>
      <select name="rol" id="rolEditar" class="form-select">
        <option value="alumno">Alumno</option><option value="docente">Docente</option>
        <option value="apoderado">Apoderado</option><option value="admin">Administrador</option>
      </select></div>
    <div class="col-12"><label class="form-label">Password (opcional)</label><input name="password" type="password" class="form-control" minlength="6" placeholder="Dejar en blanco para no cambiar"></div>
  </div></div>
  <div class="modal-footer"><button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-warning">Actualizar</button></div>
</form></div></div>

<script>
function calcularDV(rutNums){let s=0,m=2;for(let i=rutNums.length-1;i>=0;i--){s+=parseInt(rutNums[i],10)*m;m=(m===7)?2:m+1}const r=11-(s%11);return r===11?'0':r===10?'K':String(r)}
const rutCrear=document.getElementById('rutCrear'),dvCrear=document.getElementById('dvCrear');
if(rutCrear&&dvCrear){rutCrear.addEventListener('input',()=>{const n=rutCrear.value.replace(/\D/g,'');dvCrear.value=(n.length>=7&&n.length<=9)?calcularDV(n):'';});}
document.getElementById('modalEditarUsuario')?.addEventListener('show.bs.modal',e=>{
  const b=e.relatedTarget;document.getElementById('rutEditar').value=b.getAttribute('data-rut');
  document.getElementById('dvEditar').value=b.getAttribute('data-dv');
  document.getElementById('nombreEditar').value=b.getAttribute('data-nombre');
  document.getElementById('correoEditar').value=b.getAttribute('data-correo');
  const rol=(b.getAttribute('data-rol')||'alumno').toLowerCase();const sel=document.getElementById('rolEditar');
  if(sel){const ok=['admin','docente','alumno','apoderado']; sel.value=ok.includes(rol)?rol:'alumno';}
});
</script>
<?php require __DIR__ . '/_layout_bottom.php'; ?>
