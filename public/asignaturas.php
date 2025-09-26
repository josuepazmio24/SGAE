<?php
// public/asignaturas/index.php
$TITLE = 'Asignaturas';
require __DIR__ . '/_layout_top.php';

// =================== MODELO EN LA MISMA PÁGINA (para evitar archivos extra) ===================
function asignaturas_listar(PDO $pdo): array {
  $sql = "SELECT a.id_asignatura, a.nombre AS asignatura,
                 c.id_curso, c.nombre AS curso,
                 p.id_profesor,
                 CASE WHEN p.id_profesor IS NULL THEN NULL ELSE CONCAT(p.nombre,' ',p.apellidos) END AS profesor,
                 a.id_curso, a.id_profesor
          FROM asignaturas a
          INNER JOIN cursos c ON a.id_curso = c.id_curso
          LEFT JOIN profesores p ON a.id_profesor = p.id_profesor
          ORDER BY a.id_asignatura DESC";
  return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}
function asignaturas_crear(PDO $pdo, string $nombre, int $id_curso, ?int $id_profesor=null): bool {
  $st = $pdo->prepare("INSERT INTO asignaturas (nombre, id_curso, id_profesor) VALUES (:n,:c,:p)");
  return $st->execute([':n'=>$nombre, ':c'=>$id_curso, ':p'=>$id_profesor]);
}
function asignaturas_obtener(PDO $pdo, int $id): ?array {
  $st = $pdo->prepare("SELECT * FROM asignaturas WHERE id_asignatura=:id");
  $st->execute([':id'=>$id]);
  $row = $st->fetch(PDO::FETCH_ASSOC);
  return $row ?: null;
}
function asignaturas_actualizar(PDO $pdo, int $id, string $nombre, int $id_curso, ?int $id_profesor=null): bool {
  $st = $pdo->prepare("UPDATE asignaturas SET nombre=:n, id_curso=:c, id_profesor=:p WHERE id_asignatura=:id");
  return $st->execute([':n'=>$nombre, ':c'=>$id_curso, ':p'=>$id_profesor, ':id'=>$id]);
}
function asignaturas_eliminar(PDO $pdo, int $id): bool {
  $st = $pdo->prepare("DELETE FROM asignaturas WHERE id_asignatura=:id");
  return $st->execute([':id'=>$id]);
}
// =================================================================================================

$flash = ['type'=>null,'msg'=>null];

// ====== acciones POST ======
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'crear') {
      $nombre = trim($_POST['nombre'] ?? '');
      $id_curso = (int)($_POST['id_curso'] ?? 0);
      $id_profesor = ($_POST['id_profesor'] ?? '') !== '' ? (int)$_POST['id_profesor'] : null;
      if ($nombre === '' || $id_curso <= 0) throw new RuntimeException('Nombre e ID de curso son obligatorios.');
      asignaturas_crear($pdo, $nombre, $id_curso, $id_profesor);
      $flash = ['type'=>'success','msg'=>'Asignatura creada correctamente.'];

    } elseif ($accion === 'actualizar') {
      $id = (int)($_POST['id_asignatura'] ?? 0);
      $nombre = trim($_POST['nombre'] ?? '');
      $id_curso = (int)($_POST['id_curso'] ?? 0);
      $id_profesor = ($_POST['id_profesor'] ?? '') !== '' ? (int)$_POST['id_profesor'] : null;
      if ($id <= 0) throw new RuntimeException('ID inválido.');
      if ($nombre === '' || $id_curso <= 0) throw new RuntimeException('Nombre e ID de curso son obligatorios.');
      asignaturas_actualizar($pdo, $id, $nombre, $id_curso, $id_profesor);
      $flash = ['type'=>'success','msg'=>'Asignatura actualizada.'];
    }
  } catch (Throwable $e) {
    $flash = ['type'=>'danger','msg'=>$e->getMessage()];
  }
}

// ====== acción GET eliminar ======
if (isset($_GET['eliminar']) && $_GET['eliminar']!=='') {
  try {
    asignaturas_eliminar($pdo, (int)$_GET['eliminar']);
    $flash = ['type'=>'success','msg'=>'Asignatura eliminada.'];
  } catch (Throwable $e) {
    $flash = ['type'=>'danger','msg'=>'No se pudo eliminar: '.$e->getMessage()];
  }
}

// Data para selects
$cursos = $pdo->query("SELECT id_curso, nombre FROM cursos ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$profes = $pdo->query("SELECT id_profesor, nombre, apellidos FROM profesores ORDER BY nombre, apellidos")->fetchAll(PDO::FETCH_ASSOC);

// Listado + búsqueda
$lista = asignaturas_listar($pdo);
$q = strtolower(trim($_GET['q'] ?? ''));
if ($q !== '') {
  $lista = array_values(array_filter($lista, function($r) use ($q) {
    return str_contains(strtolower($r['asignatura']), $q)
        || str_contains(strtolower($r['curso']), $q)
        || str_contains(strtolower($r['profesor'] ?? ''), $q);
  }));
}
?>
<h4>Gestión de Asignaturas</h4>

<?php if ($flash['type']): ?>
  <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['msg']) ?></div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <form class="d-flex" role="search" method="get">
    <input class="form-control me-2" type="search" name="q" placeholder="Buscar por nombre, curso o profesor" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
    <button class="btn btn-outline-secondary" type="submit">Buscar</button>
  </form>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrear">+ Nueva Asignatura</button>
</div>

<div class="table-responsive">
  <table class="table table-bordered table-hover align-middle">
    <thead class="table-light">
      <tr>
        <th style="width:80px">ID</th>
        <th>Asignatura</th>
        <th>Curso</th>
        <th>Profesor</th>
        <th style="width:180px;">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$lista): ?>
        <tr><td colspan="5" class="text-center text-muted">Sin resultados</td></tr>
      <?php else: foreach ($lista as $row): ?>
        <tr>
          <td><?= (int)$row['id_asignatura'] ?></td>
          <td><?= htmlspecialchars($row['asignatura']) ?></td>
          <td><?= htmlspecialchars($row['curso']) ?></td>
          <td><?= $row['profesor'] ? htmlspecialchars($row['profesor']) : '<em class="text-muted">No asignado</em>' ?></td>
          <td>
            <button class="btn btn-sm btn-warning me-1"
                    data-bs-toggle="modal" data-bs-target="#modalEditar"
                    data-id="<?= (int)$row['id_asignatura'] ?>"
                    data-nombre="<?= htmlspecialchars($row['asignatura']) ?>"
                    data-idcurso="<?= (int)$row['id_curso'] ?>"
                    data-idprof="<?= $row['id_profesor'] !== null ? (int)$row['id_profesor'] : '' ?>">
              Editar
            </button>
            <a href="?eliminar=<?= (int)$row['id_asignatura'] ?>"
               class="btn btn-sm btn-danger"
               onclick="return confirm('¿Seguro que deseas eliminar esta asignatura?');">
               Eliminar
            </a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<!-- ======================= MODAL CREAR ======================= -->
<div class="modal fade" id="modalCrear" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="post" autocomplete="off">
      <input type="hidden" name="accion" value="crear">
      <div class="modal-header">
        <h5 class="modal-title">Nueva Asignatura</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Nombre</label>
            <input type="text" name="nombre" class="form-control" required>
          </div>
          <div class="col-12">
            <label class="form-label">Curso</label>
            <select name="id_curso" class="form-select" required>
              <option value="">Seleccione…</option>
              <?php foreach ($cursos as $c): ?>
                <option value="<?= (int)$c['id_curso'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Profesor (opcional)</label>
            <select name="id_profesor" class="form-select">
              <option value="">Sin asignar</option>
              <?php foreach ($profes as $p): ?>
                <option value="<?= (int)$p['id_profesor'] ?>"><?= htmlspecialchars($p['nombre'].' '.$p['apellidos']) ?></option>
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

<!-- ======================= MODAL EDITAR ======================= -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="post" autocomplete="off">
      <input type="hidden" name="accion" value="actualizar">
      <input type="hidden" name="id_asignatura" id="ed_id">
      <div class="modal-header">
        <h5 class="modal-title">Editar Asignatura</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Nombre</label>
            <input type="text" name="nombre" id="ed_nombre" class="form-control" required>
          </div>
          <div class="col-12">
            <label class="form-label">Curso</label>
            <select name="id_curso" id="ed_idcurso" class="form-select" required>
              <?php foreach ($cursos as $c): ?>
                <option value="<?= (int)$c['id_curso'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Profesor (opcional)</label>
            <select name="id_profesor" id="ed_idprof" class="form-select">
              <option value="">Sin asignar</option>
              <?php foreach ($profes as $p): ?>
                <option value="<?= (int)$p['id_profesor'] ?>"><?= htmlspecialchars($p['nombre'].' '.$p['apellidos']) ?></option>
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
const modalEditar = document.getElementById('modalEditar');
if (modalEditar) {
  modalEditar.addEventListener('show.bs.modal', evt => {
    const btn = evt.relatedTarget;
    document.getElementById('ed_id').value = btn.getAttribute('data-id') || '';
    document.getElementById('ed_nombre').value = btn.getAttribute('data-nombre') || '';

    const selCurso = document.getElementById('ed_idcurso');
    const idCurso = btn.getAttribute('data-idcurso') || '';
    if (selCurso) selCurso.value = idCurso;

    const selProf = document.getElementById('ed_idprof');
    const idProf = btn.getAttribute('data-idprof') || '';
    if (selProf) selProf.value = idProf; // '' => “Sin asignar”
  });
}
</script>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
