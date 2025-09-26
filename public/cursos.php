<?php
// public/cursos.php (vista)
// Requiere: includes/bootstrap.php (arranca sesión, $pdo, $BASE) y
//           incluye funciones de cursos en alumnos_model.php o un cursos_model.php

$TITLE = 'Cursos';
require __DIR__ . '/_layout_top.php';
require __DIR__ . '/../includes/alumnos_model.php'; // aquí están cursos_obtenerTodos y cursos_obtenerPorId

$flash = ['type'=>null,'msg'=>null];

// ====== POST (crear / actualizar) ======
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'crear_curso') {
      $nombre = trim($_POST['nombre'] ?? '');
      if ($nombre === '') throw new RuntimeException('El nombre del curso es obligatorio.');
      $st = $pdo->prepare("INSERT INTO cursos (nombre) VALUES (:n)");
      $st->execute([':n'=>$nombre]);
      $flash = ['type'=>'success','msg'=>'Curso creado.'];
    } elseif ($accion === 'actualizar_curso') {
      $id = (int)($_POST['id_curso'] ?? 0);
      $nombre = trim($_POST['nombre'] ?? '');
      if ($id<=0) throw new RuntimeException('ID inválido.');
      if ($nombre==='') throw new RuntimeException('El nombre no puede estar vacío.');
      $st = $pdo->prepare("UPDATE cursos SET nombre=:n WHERE id_curso=:id");
      $st->execute([':n'=>$nombre, ':id'=>$id]);
      $flash = ['type'=>'success','msg'=>'Curso actualizado.'];
    }
  } catch (Throwable $e) {
    $flash = ['type'=>'danger','msg'=>$e->getMessage()];
  }
}

// ====== GET (eliminar) ======
if (isset($_GET['eliminar']) && $_GET['eliminar']!=='') {
  try {
    $id = (int)$_GET['eliminar'];
    $st = $pdo->prepare("DELETE FROM cursos WHERE id_curso=:id");
    $st->execute([':id'=>$id]);
    $flash = ['type'=>'success','msg'=>'Curso eliminado.'];
  } catch (Throwable $e) {
    $flash = ['type'=>'danger','msg'=>'No se pudo eliminar: '.$e->getMessage()];
  }
}

// ====== Datos ======
$cursos = cursos_obtenerTodos($pdo);

$q = strtolower(trim($_GET['q'] ?? ''));
if ($q !== '') {
  $cursos = array_values(array_filter($cursos, fn($c)=> str_contains(strtolower($c['nombre']), $q)));
}
?>
<h4>Gestión de Cursos</h4>

<?php if ($flash['type']): ?>
  <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>">
    <?= htmlspecialchars($flash['msg']) ?>
  </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <form class="d-flex" role="search" method="get" action="">
    <input class="form-control me-2" type="search" name="q"
           placeholder="Buscar por nombre de curso"
           value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
    <button class="btn btn-outline-secondary">Buscar</button>
  </form>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearCurso">
    + Nuevo Curso
  </button>
</div>

<div class="table-responsive">
  <table class="table table-bordered table-hover align-middle">
    <thead class="table-light">
      <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th style="width:180px;">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$cursos): ?>
        <tr><td colspan="3" class="text-center text-muted">Sin resultados</td></tr>
      <?php else: foreach ($cursos as $row): ?>
        <tr>
          <td><?= (int)$row['id_curso'] ?></td>
          <td><?= htmlspecialchars($row['nombre']) ?></td>
          <td>
            <button class="btn btn-sm btn-warning me-1"
                    data-bs-toggle="modal" data-bs-target="#modalEditarCurso"
                    data-id="<?= (int)$row['id_curso'] ?>"
                    data-nombre="<?= htmlspecialchars($row['nombre']) ?>">Editar</button>
            <a href="?eliminar=<?= (int)$row['id_curso'] ?>"
               class="btn btn-sm btn-danger"
               onclick="return confirm('¿Eliminar este curso?');">Eliminar</a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<!-- ===== Modal CREAR ===== -->
<div class="modal fade" id="modalCrearCurso" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="post" autocomplete="off">
      <input type="hidden" name="accion" value="crear_curso">
      <div class="modal-header">
        <h5 class="modal-title">Nuevo Curso</h5>
        <button class="btn-close" data-bs-dismiss="modal" type="button" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Nombre</label>
          <input name="nombre" class="form-control" required placeholder="Ej: 1°A, 2°B">
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
<div class="modal fade" id="modalEditarCurso" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="post" autocomplete="off">
      <input type="hidden" name="accion" value="actualizar_curso">
      <input type="hidden" name="id_curso" id="curso_id_editar">
      <div class="modal-header">
        <h5 class="modal-title">Editar Curso</h5>
        <button class="btn-close" data-bs-dismiss="modal" type="button" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Nombre</label>
          <input name="nombre" id="curso_nombre_editar" class="form-control" required>
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
// Precarga datos en modal Editar
document.getElementById('modalEditarCurso')?.addEventListener('show.bs.modal', e=>{
  const b = e.relatedTarget;
  document.getElementById('curso_id_editar').value   = b.getAttribute('data-id');
  document.getElementById('curso_nombre_editar').value = b.getAttribute('data-nombre') || '';
});
</script>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
