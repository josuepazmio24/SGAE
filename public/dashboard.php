<?php
// public/dashboard.php
require __DIR__ . '/../includes/auth.php';
require_login();
require __DIR__ . '/../includes/db.php';

// === Base URL auto-detect (soporta vhost con o sin /public) ===
$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'); // "" o "/public"

// ================== Utilidades Dashboard ==================
function tableExists(PDO $pdo, string $table): bool {
    $sql = "SELECT COUNT(*) FROM information_schema.tables 
            WHERE table_schema = DATABASE() AND table_name = :t LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute([':t' => $table]);
    return $st->fetchColumn() > 0;
}
function tableCount(PDO $pdo, string $table): int {
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) return 0;
    $sql = "SELECT COUNT(*) AS c FROM $table";
    $st = $pdo->query($sql);
    $row = $st->fetch();
    return (int)($row['c'] ?? 0);
}

// ================== CRUD Usuarios ==================
function usuarios_obtenerTodos(PDO $pdo): array {
    $sql = "SELECT rut, dv, nombre, correo FROM usuarios ORDER BY nombre";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}
function usuarios_crear(PDO $pdo, string $rut, string $dv, string $nombre, string $correo, string $password): bool {
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $st = $pdo->prepare("INSERT INTO usuarios (rut, dv, nombre, correo, password)
                         VALUES (:rut,:dv,:nombre,:correo,:pass)");
    return $st->execute([
        ':rut'=>$rut, ':dv'=>strtoupper($dv), ':nombre'=>$nombre, ':correo'=>$correo, ':pass'=>$hash
    ]);
}
function usuarios_actualizar(PDO $pdo, string $rut, string $dv, string $nombre, string $correo, ?string $password=null): bool {
    if ($password !== null && $password !== '') {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $st = $pdo->prepare("UPDATE usuarios SET dv=:dv, nombre=:nombre, correo=:correo, password=:pass WHERE rut=:rut");
        return $st->execute([':dv'=>strtoupper($dv), ':nombre'=>$nombre, ':correo'=>$correo, ':pass'=>$hash, ':rut'=>$rut]);
    } else {
        $st = $pdo->prepare("UPDATE usuarios SET dv=:dv, nombre=:nombre, correo=:correo WHERE rut=:rut");
        return $st->execute([':dv'=>strtoupper($dv), ':nombre'=>$nombre, ':correo'=>$correo, ':rut'=>$rut]);
    }
}
function usuarios_eliminar(PDO $pdo, string $rut): bool {
    $st = $pdo->prepare("DELETE FROM usuarios WHERE rut=:rut");
    return $st->execute([':rut'=>$rut]);
}

// ================== Generador de Módulos (1 archivo con modals) ==================
function limpiar_ident(string $s): string {
  $s = strtolower(trim($s));
  $s = preg_replace('/[^a-z0-9_]/', '_', $s);
  $s = preg_replace('/_+/', '_', $s);
  return trim($s, '_');
}
function map_tipo_sql(string $tipo, ?int $len = null): string {
  $tipo = strtolower($tipo);
  switch ($tipo) {
    case 'int': return 'INT';
    case 'bigint': return 'BIGINT';
    case 'decimal': return 'DECIMAL(10,2)';
    case 'date': return 'DATE';
    case 'datetime': return 'DATETIME';
    case 'text': return 'TEXT';
    case 'varchar':
    default:
      $len = $len && $len>0 && $len<=1000 ? $len : 255;
      return "VARCHAR($len)";
  }
}

// Plantilla de módulo de **1 sola página** con CRUD por modals
function tpl_modulo_unico(string $ruta, string $tabla, string $etiqueta, array $campos): string {
  // construimos el literal PHP del array $CAMPOS
  $arr = [];
  foreach ($campos as $c) {
    $n = $c['nombre'];
    $t = $c['tipo'];
    $arr[] = "  ['nombre'=>'{$n}','tipo'=>'{$t}']";
  }
  $camposPhp = "[\n" . implode(",\n", $arr) . "\n]";

  return <<<PHP
<?php
// Módulo de una sola página con modals
\$RUTA = '$ruta';
\$TABLA = '$tabla';
\$ETIQUETA = '$etiqueta';
\$CAMPOS = $camposPhp;

require __DIR__ . '/../../includes/auth.php';
require_login();
require __DIR__ . '/../../includes/db.php';

\$BASE = rtrim(dirname(\$_SERVER['SCRIPT_NAME']), '/');

// Si se abre fuera del dashboard, redirige para embebido
if (empty(\$_GET['_inframe']) && (string)\$_SERVER['HTTP_SEC_FETCH_DEST'] !== 'iframe') {
  \$qs = \$_SERVER['QUERY_STRING'] ? ('&'.\$_SERVER['QUERY_STRING']) : '';
  header('Location: ' . \$BASE . '/dashboard.php?page=mod&ruta='.\$RUTA.\$qs);
  exit;
}

// Helpers SQL dinámico
function campos_cols(array \$C): array { return array_map(fn(\$c)=>"`{\$c['nombre']}`", \$C); }
function campos_vals(array \$C): array { return array_map(fn(\$c)=>":{\$c['nombre']}", \$C); }
function campos_set(array \$C): array  { return array_map(fn(\$c)=>"`{\$c['nombre']}` = :{\$c['nombre']}", \$C); }

\$flash = null;

// ===== Acciones CRUD (POST) =====
if (\$_SERVER['REQUEST_METHOD']==='POST') {
  try {
    \$accion = \$_POST['accion'] ?? '';
    if (\$accion === 'crear') {
      \$cols = implode(', ', campos_cols(\$CAMPOS));
      \$vals = implode(', ', campos_vals(\$CAMPOS));
      \$st = \$pdo->prepare("INSERT INTO `\$TABLA` (\$cols) VALUES (\$vals)");
      \$params = [];
      foreach (\$CAMPOS as \$c) {
        \$n = \$c['nombre'];
        \$params[":\$n"] = \$_POST[\$n] ?? null;
      }
      \$st->execute(\$params);
      \$flash = ['type'=>'success','msg'=>'Registro creado correctamente.'];

    } elseif (\$accion === 'actualizar') {
      \$id = (int)(\$_POST['id'] ?? 0);
      if (!\$id) throw new RuntimeException('ID inválido.');
      \$set = implode(', ', campos_set(\$CAMPOS));
      \$st = \$pdo->prepare("UPDATE `\$TABLA` SET \$set WHERE id=:id");
      \$params = [':id'=>\$id];
      foreach (\$CAMPOS as \$c) {
        \$n = \$c['nombre'];
        \$params[":\$n"] = \$_POST[\$n] ?? null;
      }
      \$st->execute(\$params);
      \$flash = ['type'=>'success','msg'=>'Registro actualizado.'];

    } elseif (\$accion === 'eliminar') {
      \$id = (int)(\$_POST['id'] ?? 0);
      if (!\$id) throw new RuntimeException('ID inválido.');
      \$st = \$pdo->prepare("DELETE FROM `\$TABLA` WHERE id=:id");
      \$st->execute([':id'=>\$id]);
      \$flash = ['type'=>'success','msg'=>'Registro eliminado.'];
    }
  } catch (Throwable \$e) {
    \$flash = ['type'=>'danger','msg'=>'Error: '.\$e->getMessage()];
  }
}

// ===== Listado / búsqueda =====
\$q = trim(\$_GET['q'] ?? '');
\$concat = implode(', ', array_map(fn(\$c)=>"`{\$c['nombre']}`", \$CAMPOS));
if (\$q !== '') {
  \$st = \$pdo->prepare("SELECT * FROM `\$TABLA` WHERE CONCAT_WS(' ', id, \$concat) LIKE :q ORDER BY id DESC");
  \$st->execute([':q'=>"%\$q%"]);
  \$lista = \$st->fetchAll(PDO::FETCH_ASSOC);
} else {
  \$lista = \$pdo->query("SELECT * FROM `\$TABLA` ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars(\$ETIQUETA) ?> - Módulo</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><?= htmlspecialchars(\$ETIQUETA) ?></h4>
    <div>
      <a href="<?= \$BASE ?>/dashboard.php?page=configuracion" class="btn btn-outline-secondary me-2">⚙ Configuración</a>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrear">+ Nuevo</button>
    </div>
  </div>

  <?php if (\$flash): ?>
    <div class="alert alert-<?= htmlspecialchars(\$flash['type']) ?>"><?= htmlspecialchars(\$flash['msg']) ?></div>
  <?php endif; ?>

  <form class="d-flex mb-3" method="get" action="<?= \$BASE ?>/dashboard.php">
    <input type="hidden" name="page" value="mod">
    <input type="hidden" name="ruta" value="<?= htmlspecialchars(\$RUTA) ?>">
    <input type="hidden" name="_inframe" value="1">
    <input class="form-control me-2" type="search" name="q" placeholder="Buscar..." value="<?= htmlspecialchars(\$q) ?>">
    <button class="btn btn-outline-secondary">Buscar</button>
  </form>

  <div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th>ID</th>
<?php foreach (\$CAMPOS as \$c): ?>
          <th><?= ucfirst(htmlspecialchars(\$c['nombre'])) ?></th>
<?php endforeach; ?>
          <th style="width:180px;">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!\$lista): ?>
          <tr><td colspan="999" class="text-center text-muted">Sin registros</td></tr>
        <?php else: foreach (\$lista as \$row): ?>
          <tr>
            <td><?= (int)\$row['id'] ?></td>
<?php foreach (\$CAMPOS as \$c): ?>
            <td><?= htmlspecialchars(\$row[\$c['nombre']] ?? '') ?></td>
<?php endforeach; ?>
            <td>
              <button class="btn btn-sm btn-warning me-1 btn-edit"
                      data-bs-toggle="modal" data-bs-target="#modalEditar"
                      data-id="<?= (int)\$row['id'] ?>"
<?php foreach (\$CAMPOS as \$c): \$n=\$c['nombre']; ?>
                      data-<?= htmlspecialchars(\$n) ?>="<?= htmlspecialchars(\$row[\$n] ?? '') ?>"
<?php endforeach; ?>
              >Editar</button>
              <button class="btn btn-sm btn-danger btn-del"
                      data-bs-toggle="modal" data-bs-target="#modalEliminar"
                      data-id="<?= (int)\$row['id'] ?>">
                Eliminar
              </button>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal CREAR -->
<div class="modal fade" id="modalCrear" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" method="post" autocomplete="off">
      <input type="hidden" name="accion" value="crear">
      <div class="modal-header">
        <h5 class="modal-title">Nuevo registro - <?= htmlspecialchars(\$ETIQUETA) ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
<?php foreach (\$CAMPOS as \$c): \$tipo=\$c['tipo']; \$n=\$c['nombre']; ?>
          <div class="col-md-6">
            <label class="form-label"><?= ucfirst(htmlspecialchars(\$n)) ?></label>
            <?php
              \$htmlType = 'text';
              if (in_array(\$tipo, ['int','bigint','decimal'])) \$htmlType='number';
              if (\$tipo==='date') \$htmlType='date';
              if (\$tipo==='datetime') \$htmlType='datetime-local';
              if (\$tipo==='text') {
                echo '<textarea name="'.htmlspecialchars(\$n).'" class="form-control" rows="3" required></textarea>';
              } else {
                echo '<input type="'.\$htmlType.'" name="'.htmlspecialchars(\$n).'" class="form-control" required>';
              }
            ?>
          </div>
<?php endforeach; ?>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary">Guardar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal EDITAR -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" method="post" autocomplete="off">
      <input type="hidden" name="accion" value="actualizar">
      <input type="hidden" name="id" id="edit_id">
      <div class="modal-header">
        <h5 class="modal-title">Editar registro - <?= htmlspecialchars(\$ETIQUETA) ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
<?php foreach (\$CAMPOS as \$c): \$tipo=\$c['tipo']; \$n=\$c['nombre']; ?>
          <div class="col-md-6">
            <label class="form-label"><?= ucfirst(htmlspecialchars(\$n)) ?></label>
            <?php
              \$htmlType = 'text';
              if (in_array(\$tipo, ['int','bigint','decimal'])) \$htmlType='number';
              if (\$tipo==='date') \$htmlType='date';
              if (\$tipo==='datetime') \$htmlType='datetime-local';
              if (\$tipo==='text') {
                echo '<textarea name="'.htmlspecialchars(\$n).'" id="edit_'.htmlspecialchars(\$n).'" class="form-control" rows="3" required></textarea>';
              } else {
                echo '<input type="'.\$htmlType.'" name="'.htmlspecialchars(\$n).'" id="edit_'.htmlspecialchars(\$n).'" class="form-control" required>';
              }
            ?>
          </div>
<?php endforeach; ?>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-warning">Actualizar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal ELIMINAR -->
<div class="modal fade" id="modalEliminar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="post" autocomplete="off">
      <input type="hidden" name="accion" value="eliminar">
      <input type="hidden" name="id" id="del_id">
      <div class="modal-header">
        <h5 class="modal-title">Eliminar registro</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        ¿Deseas eliminar este registro definitivamente?
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-danger">Eliminar</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Precargar datos en modal Editar
document.querySelectorAll('.btn-edit').forEach(btn => {
  btn.addEventListener('click', () => {
    const id = btn.getAttribute('data-id');
    document.getElementById('edit_id').value = id;
<?php foreach (/* inline php for fields at runtime */ \$CAMPOS as \$c): \$n=\$c['nombre']; ?>
    const v_<?= \$n ?> = btn.getAttribute('data-<?= htmlspecialchars(\$n) ?>') || '';
    const el_<?= \$n ?> = document.getElementById('edit_<?= htmlspecialchars(\$n) ?>');
    if (el_<?= \$n ?>) el_<?= \$n ?>.value = v_<?= \$n ?>;
<?php endforeach; ?>
  });
});

// Precargar id en modal Eliminar
document.querySelectorAll('.btn-del').forEach(btn => {
  btn.addEventListener('click', () => {
    const id = btn.getAttribute('data-id');
    document.getElementById('del_id').value = id;
  });
});
</script>
</body>
</html>
PHP;
}

// ================== Routing simple ==================
$page = $_GET['page'] ?? 'dashboard';

// ================== Acciones Usuarios ==================
$flash = ['type'=>null,'msg'=>null];
if ($page === 'usuarios') {
    if (($_POST['accion'] ?? '') === 'crear_usuario') {
        $rut = trim($_POST['rut'] ?? '');
        $dv = trim($_POST['dv'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $password = $_POST['password'] ?? '';
        try {
            if ($rut === '' || $dv === '' || $nombre === '' || $correo === '' || $password === '') {
                throw new RuntimeException('Todos los campos son obligatorios.');
            }
            if (!preg_match('/^\d{7,9}$/', $rut)) throw new RuntimeException('RUT inválido (solo números, 7 a 9 dígitos).');
            if (!preg_match('/^[0-9Kk]{1}$/', $dv)) throw new RuntimeException('DV inválido.');
            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Correo inválido.');
            usuarios_crear($pdo, $rut, $dv, $nombre, $correo, $password);
            $flash = ['type'=>'success', 'msg'=>'Usuario creado correctamente.'];
        } catch (Throwable $e) {
            $flash = ['type'=>'danger', 'msg'=>'Error al crear usuario: '.$e->getMessage()];
        }
    }
    if (($_POST['accion'] ?? '') === 'actualizar_usuario') {
        $rut = trim($_POST['rut'] ?? '');
        $dv = trim($_POST['dv'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $password = $_POST['password'] ?? '';
        try {
            if ($rut === '' || $dv === '' || $nombre === '' || $correo === '') {
                throw new RuntimeException('Faltan campos obligatorios.');
            }
            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Correo inválido.');
            $pwdToSet = ($password !== '') ? $password : null;
            usuarios_actualizar($pdo, $rut, $dv, $nombre, $correo, $pwdToSet);
            $flash = ['type'=>'success', 'msg'=>'Usuario actualizado correctamente.'];
        } catch (Throwable $e) {
            $flash = ['type'=>'danger', 'msg'=>'Error al actualizar: '.$e->getMessage()];
        }
    }
    if (isset($_GET['eliminar']) && $_GET['eliminar'] !== '') {
        $rutDel = $_GET['eliminar'];
        try {
            usuarios_eliminar($pdo, $rutDel);
            $flash = ['type'=>'success', 'msg'=>'Usuario eliminado.'];
        } catch (Throwable $e) {
            $flash = ['type'=>'danger', 'msg'=>'No se pudo eliminar: '.$e->getMessage()];
        }
    }
}

// ================== Acciones Configuración (generador) ==================
$configFlash = null;
if ($page === 'configuracion' && $_SERVER['REQUEST_METHOD']==='POST' && ($_POST['accion'] ?? '') === 'crear_modulo') {
  try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS sgae_modulos (
      id INT AUTO_INCREMENT PRIMARY KEY,
      nombre VARCHAR(64) NOT NULL,
      etiqueta VARCHAR(100) NOT NULL,
      ruta VARCHAR(128) NOT NULL,
      tabla VARCHAR(64) NOT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY uq_sgae_modulos_ruta (ruta),
      UNIQUE KEY uq_sgae_modulos_tabla (tabla)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    $etiqueta = trim($_POST['etiqueta'] ?? '');
    $ruta     = limpiar_ident($_POST['ruta'] ?? '');
    $tabla    = limpiar_ident($_POST['tabla'] ?? '');
    $campos   = $_POST['campos'] ?? [];

    if ($etiqueta==='' || $ruta==='' || $tabla==='') throw new RuntimeException('Etiqueta, ruta y tabla son obligatorios.');
    if (!$campos || !is_array($campos)) throw new RuntimeException('Debes definir al menos un campo.');

    // DDL tabla
    $cols = ["id INT AUTO_INCREMENT PRIMARY KEY"];
    $metaCampos = [];
    foreach ($campos as $c) {
      $nombre = limpiar_ident($c['nombre'] ?? '');
      if ($nombre==='') throw new RuntimeException('Nombre de campo inválido.');
      $tipo   = map_tipo_sql($c['tipo'] ?? 'varchar', isset($c['len']) ? (int)$c['len'] : null);
      $nulo   = (isset($c['nulo']) && $c['nulo']==='1') ? 'NULL' : 'NOT NULL';
      $unico  = isset($c['unico']) && $c['unico']==='1';
      $cols[] = "`$nombre` $tipo $nulo";
      if ($unico) $cols[] = "UNIQUE KEY uq_{$tabla}_{$nombre} (`$nombre`)";
      $metaCampos[] = ['nombre'=>$nombre, 'tipo'=>strtolower($c['tipo'] ?? 'varchar')];
    }
    $cols[] = "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
    $ddl = "CREATE TABLE IF NOT EXISTS `$tabla` (\n  ".implode(",\n  ", $cols)."\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $pdo->exec($ddl);

    // Registrar módulo
    $st = $pdo->prepare("INSERT INTO sgae_modulos (nombre, etiqueta, ruta, tabla) VALUES (:n,:e,:r,:t)");
    $st->execute([':n'=>$tabla, ':e'=>$etiqueta, ':r'=>$ruta, ':t'=>$tabla]);

    // Carpeta y archivo único
    $base = __DIR__ . "/{$ruta}";
    if (!is_dir($base)) {
      if (!mkdir($base, 0775, true) && !is_dir($base)) {
        throw new RuntimeException("No se pudo crear la carpeta $base");
      }
    }
    $tpl = tpl_modulo_unico($ruta, $tabla, $etiqueta, $metaCampos);
    file_put_contents("$base/{$ruta}_index.php", $tpl);

    if (!file_exists("$base/{$ruta}_index.php")) {
      throw new RuntimeException("Se creó la tabla pero no se pudo escribir $base/{$ruta}_index.php. Revisa permisos de escritura.");
    }

    $configFlash = ['type'=>'success', 'msg'=>"Módulo generado. Ábrelo desde el menú; se mostrará embebido en el dashboard."];
  } catch (Throwable $e) {
    $configFlash = ['type'=>'danger', 'msg'=>'Error: '.$e->getMessage()];
  }
}

// ====== Inspector de tablas (Config > Ver estructura) ======
$inspect = null;
$colsInfo = [];
$idxInfo  = [];
$ddlCreate = '';
if ($page === 'configuracion' && isset($_GET['inspect'])) {
  $inspect = preg_replace('/[^a-z0-9_]/i','', $_GET['inspect']);
  try {
    $st = $pdo->prepare("
      SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_KEY, EXTRA
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t
      ORDER BY ORDINAL_POSITION
    ");
    $st->execute([':t'=>$inspect]);
    $colsInfo = $st->fetchAll(PDO::FETCH_ASSOC);

    $st2 = $pdo->prepare("
      SELECT INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX, COLUMN_NAME, COLLATION, CARDINALITY
      FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t
      ORDER BY INDEX_NAME, SEQ_IN_INDEX
    ");
    $st2->execute([':t'=>$inspect]);
    $idxInfo = $st2->fetchAll(PDO::FETCH_ASSOC);

    $ddl = $pdo->query("SHOW CREATE TABLE `{$inspect}`")->fetch(PDO::FETCH_ASSOC);
    if ($ddl && isset($ddl['Create Table'])) { $ddlCreate = $ddl['Create Table']; }
  } catch (Throwable $e) { /* silencio */ }
}

$u = $_SESSION['usuario'];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>SGAE - Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color:#f8f9fa; }
    .sidebar { width: 240px; min-height: 100vh; background: #fff; border-right:1px solid #ddd; position: fixed; top:0; left:0; padding:1rem; }
    .content { margin-left: 240px; padding: 1.5rem; }
    .sidebar a { display:block; padding:.5rem 0; color:#000; text-decoration:none; }
    .sidebar a:hover { font-weight: 600; }
    .topbar { position: fixed; left:240px; right:0; top:0; height:56px; background:#fff; border-bottom:1px solid #ddd; display:flex; justify-content: flex-end; align-items:center; padding:0 1rem; z-index:1000; }
    .content-body { margin-top:70px; }
    .mod-frame { width:100%; height:76vh; border:0; background:#fff; }
    .field-row{display:grid;grid-template-columns:2fr 1.2fr 1fr .8fr .8fr auto;gap:.5rem;align-items:end}
  </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
  <h5 class="mb-3">SGAE<br><small class="text-muted">Gestión Académica</small></h5>
  <a href="?page=dashboard">Dashboard</a>
  <a href="?page=usuarios">Usuarios</a>
  <a href="?page=configuracion">Configuración</a>
  <hr>
  <div class="text-muted small mb-2">Módulos</div>
  <?php
    try {
      if (tableExists($pdo,'sgae_modulos')) {
        $mods = $pdo->query("SELECT etiqueta, ruta FROM sgae_modulos ORDER BY etiqueta")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($mods as $m) {
          $etq = htmlspecialchars($m['etiqueta']);
          $rt  = htmlspecialchars($m['ruta']);
          echo '<a href="?page=mod&ruta='.$rt.'">'.$etq.'</a>';
        }
      }
    } catch (Throwable $e) { /* silencioso */ }
  ?>
</div>

<!-- Topbar -->
<div class="topbar">
  <span class="me-3"><?= htmlspecialchars($u['nombre']) ?> - Administrador</span>
  <a href="logout.php" class="btn btn-sm btn-outline-danger">Salir</a>
</div>

<!-- Contenido -->
<div class="content">
  <div class="content-body">
    <?php if ($page === 'dashboard'): ?>
      <h4>SGAE - Dashboard</h4>
      <div class="row g-3 mt-1">
        <?php
        $tarjetas = [];
        if (tableExists($pdo, 'usuarios')) $tarjetas[] = ['titulo'=>'Usuarios totales','valor'=>number_format(tableCount($pdo,'usuarios')),'clase'=>'bg-primary text-white'];
        if (tableExists($pdo, 'alumnos'))  $tarjetas[] = ['titulo'=>'Alumnos totales','valor'=>number_format(tableCount($pdo,'alumnos')),'clase'=>'bg-success text-white'];
        if (tableExists($pdo, 'reportes')) $tarjetas[] = ['titulo'=>'Reportes','valor'=>number_format(tableCount($pdo,'reportes')),'clase'=>'bg-warning text-dark'];
        if (tableExists($pdo, 'control_ingreso')) $tarjetas[] = ['titulo'=>'Registros de ingreso','valor'=>number_format(tableCount($pdo,'control_ingreso')),'clase'=>'bg-info text-dark'];
        foreach ($tarjetas as $t): ?>
          <div class="col-sm-6 col-lg-4">
            <div class="card shadow-sm <?= $t['clase'] ?>"><div class="card-body">
              <h6 class="mb-1"><?= htmlspecialchars($t['titulo']) ?></h6>
              <div class="display-6 fw-bold"><?= $t['valor'] ?></div>
            </div></div>
          </div>
        <?php endforeach; ?>
      </div>

    <?php elseif ($page === 'usuarios'): ?>
      <h4>Gestión de Usuarios</h4>
      <?php if ($flash['type']): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['msg']) ?></div>
      <?php endif; ?>
      <div class="d-flex justify-content-between align-items-center mb-3">
        <form class="d-flex" role="search" method="get">
          <input type="hidden" name="page" value="usuarios">
          <input class="form-control me-2" type="search" name="q" placeholder="Buscar por nombre o correo" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
          <button class="btn btn-outline-secondary" type="submit">Buscar</button>
        </form>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearUsuario">+ Nuevo Usuario</button>
      </div>
      <?php
        $usuarios = usuarios_obtenerTodos($pdo);
        $q = strtolower(trim($_GET['q'] ?? ''));
        if ($q !== '') {
            $usuarios = array_values(array_filter($usuarios, function($u) use ($q) {
                return str_contains(strtolower($u['nombre']), $q) || str_contains(strtolower($u['correo']), $q) || str_contains($u['rut'], $q);
            }));
        }
      ?>
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th style="white-space:nowrap;">RUT</th><th>DV</th><th>Nombre</th><th>Correo</th><th style="width:180px;">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($usuarios) === 0): ?>
              <tr><td colspan="5" class="text-center text-muted">Sin resultados</td></tr>
            <?php else: foreach ($usuarios as $row): ?>
              <tr>
                <td><?= htmlspecialchars($row['rut']) ?></td>
                <td><?= htmlspecialchars($row['dv']) ?></td>
                <td><?= htmlspecialchars($row['nombre']) ?></td>
                <td><?= htmlspecialchars($row['correo']) ?></td>
                <td>
                  <button class="btn btn-sm btn-warning me-1"
                          data-bs-toggle="modal" data-bs-target="#modalEditarUsuario"
                          data-rut="<?= htmlspecialchars($row['rut']) ?>"
                          data-dv="<?= htmlspecialchars($row['dv']) ?>"
                          data-nombre="<?= htmlspecialchars($row['nombre']) ?>"
                          data-correo="<?= htmlspecialchars($row['correo']) ?>">Editar</button>
                  <a href="?page=usuarios&eliminar=<?= urlencode($row['rut']) ?>" class="btn btn-sm btn-danger"
                     onclick="return confirm('¿Seguro que deseas eliminar este usuario?');">Eliminar</a>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Modal Crear -->
      <div class="modal fade" id="modalCrearUsuario" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
          <form class="modal-content" method="post" autocomplete="off">
            <input type="hidden" name="accion" value="crear_usuario">
            <div class="modal-header"><h5 class="modal-title">Nuevo Usuario</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
              <div class="row g-3">
                <div class="col-8"><label class="form-label">RUT (sin puntos, sin DV)</label>
                  <input type="text" name="rut" id="rutCrear" class="form-control" placeholder="12345678" required pattern="\d{7,9}"></div>
                <div class="col-4"><label class="form-label">DV</label>
                  <input type="text" name="dv" id="dvCrear" class="form-control" placeholder="K/0-9" required pattern="[0-9Kk]{1}"></div>
                <div class="col-12"><label class="form-label">Nombre</label><input type="text" name="nombre" class="form-control" required></div>
                <div class="col-12"><label class="form-label">Correo</label><input type="email" name="correo" class="form-control" required></div>
                <div class="col-12"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required minlength="6">
                  <div class="form-text">Se almacenará con hash (bcrypt).</div></div>
              </div>
            </div>
            <div class="modal-footer"><button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
              <button class="btn btn-primary">Guardar</button></div>
          </form>
        </div>
      </div>

      <!-- Modal Editar -->
      <div class="modal fade" id="modalEditarUsuario" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
          <form class="modal-content" method="post" autocomplete="off">
            <input type="hidden" name="accion" value="actualizar_usuario">
            <div class="modal-header"><h5 class="modal-title">Editar Usuario</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
              <div class="row g-3">
                <div class="col-8"><label class="form-label">RUT (solo lectura)</label>
                  <input type="text" name="rut" id="rutEditar" class="form-control" readonly></div>
                <div class="col-4"><label class="form-label">DV</label>
                  <input type="text" name="dv" id="dvEditar" class="form-control" required pattern="[0-9Kk]{1}"></div>
                <div class="col-12"><label class="form-label">Nombre</label>
                  <input type="text" name="nombre" id="nombreEditar" class="form-control" required></div>
                <div class="col-12"><label class="form-label">Correo</label>
                  <input type="email" name="correo" id="correoEditar" class="form-control" required></div>
                <div class="col-12"><label class="form-label">Password (opcional)</label>
                  <input type="password" name="password" class="form-control" minlength="6" placeholder="Dejar en blanco para no cambiar"></div>
              </div>
            </div>
            <div class="modal-footer"><button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
              <button class="btn btn-warning" type="submit">Actualizar</button></div>
          </form>
        </div>
      </div>

    <?php elseif ($page === 'configuracion'): ?>
      <h4>Configuración • Generador de Módulos</h4>
      <?php if ($configFlash): ?><div class="alert alert-<?= htmlspecialchars($configFlash['type']) ?>"><?= htmlspecialchars($configFlash['msg']) ?></div><?php endif; ?>

      <div class="card shadow-sm"><div class="card-body">
        <form method="post" id="formModulo" autocomplete="off">
          <input type="hidden" name="accion" value="crear_modulo">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Etiqueta (para menú)</label>
              <input class="form-control" name="etiqueta" required placeholder="Libros">
            </div>
            <div class="col-md-4">
              <label class="form-label">Ruta base (carpeta)</label>
              <input class="form-control" name="ruta" required placeholder="libros">
              <div class="form-text">Se creará <code>/public/<b>ruta</b></code> y un archivo <code>ruta_index.php</code>.</div>
            </div>
            <div class="col-md-4">
              <label class="form-label">Nombre de tabla</label>
              <input class="form-control" name="tabla" required placeholder="libros">
            </div>
          </div>

          <hr>
          <div class="mb-2 d-flex justify-content-between align-items-center">
            <strong>Campos de la tabla</strong>
            <button class="btn btn-sm btn-outline-primary" type="button" id="btnAdd">+ Agregar campo</button>
          </div>

          <div id="camposWrap" class="vstack gap-2">
            <div class="field-row">
              <div><label class="form-label">Nombre</label><input class="form-control" name="campos[0][nombre]" placeholder="titulo" required></div>
              <div><label class="form-label">Tipo</label>
                <select class="form-select" name="campos[0][tipo]">
                  <option value="varchar">VARCHAR</option>
                  <option value="int">INT</option>
                  <option value="bigint">BIGINT</option>
                  <option value="decimal">DECIMAL(10,2)</option>
                  <option value="date">DATE</option>
                  <option value="datetime">DATETIME</option>
                  <option value="text">TEXT</option>
                </select></div>
              <div><label class="form-label">Longitud</label><input class="form-control" name="campos[0][len]" type="number" min="1" max="1000" placeholder="ej: 255"></div>
              <div><label class="form-label">Nulo</label><select class="form-select" name="campos[0][nulo]"><option value="0">NO</option><option value="1">SÍ</option></select></div>
              <div><label class="form-label">Único</label><select class="form-select" name="campos[0][unico]"><option value="0">NO</option><option value="1">SÍ</option></select></div>
              <div><label class="form-label d-block">&nbsp;</label><button class="btn btn-outline-danger" type="button" onclick="this.closest('.field-row').remove()">Eliminar</button></div>
            </div>
          </div>

          <div class="mt-4 d-flex gap-2">
            <a href="<?= htmlspecialchars($BASE) ?>/dashboard.php" class="btn btn-secondary">Cancelar</a>
            <button class="btn btn-primary">Generar módulo</button>
          </div>
        </form>
      </div></div>

      <!-- Listado de módulos + inspector -->
      <div class="card shadow-sm mt-4"><div class="card-body">
        <h5 class="mb-3">Módulos existentes</h5>
        <?php
          $modsList = [];
          if (tableExists($pdo,'sgae_modulos')) {
            $modsList = $pdo->query("SELECT etiqueta, ruta, tabla FROM sgae_modulos ORDER BY etiqueta")->fetchAll(PDO::FETCH_ASSOC);
          }
        ?>
        <?php if (!$modsList): ?>
          <div class="text-muted">Aún no hay módulos creados.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-bordered align-middle">
              <thead class="table-light"><tr><th>Etiqueta</th><th>Ruta</th><th>Tabla</th><th style="width:260px;">Acciones</th></tr></thead>
              <tbody>
                <?php foreach ($modsList as $m): ?>
                  <tr>
                    <td><?= htmlspecialchars($m['etiqueta']) ?></td>
                    <td><code><?= htmlspecialchars($m['ruta']) ?></code></td>
                    <td><code><?= htmlspecialchars($m['tabla']) ?></code></td>
                    <td>
                      <a class="btn btn-sm btn-outline-primary me-1" href="?page=configuracion&inspect=<?= urlencode($m['tabla']) ?>">Ver estructura</a>
                      <a class="btn btn-sm btn-outline-secondary me-1" href="?page=mod&ruta=<?= urlencode($m['ruta']) ?>">Abrir</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div></div>

      <?php if (!empty($inspect)): ?>
      <div class="card shadow-sm mt-4"><div class="card-body">
        <h5 class="mb-3">Estructura de la tabla: <code><?= htmlspecialchars($inspect) ?></code></h5>
        <h6>Columnas</h6>
        <div class="table-responsive mb-3">
          <table class="table table-sm table-striped align-middle">
            <thead class="table-light"><tr><th>Columna</th><th>Tipo</th><th>Nulo</th><th>Default</th><th>Clave</th><th>Extra</th></tr></thead>
            <tbody>
              <?php if (!$colsInfo): ?>
                <tr><td colspan="6" class="text-muted text-center">No se encontraron columnas.</td></tr>
              <?php else: foreach ($colsInfo as $c): ?>
                <tr>
                  <td><code><?= htmlspecialchars($c['COLUMN_NAME']) ?></code></td>
                  <td><?= htmlspecialchars($c['COLUMN_TYPE']) ?></td>
                  <td><?= htmlspecialchars($c['IS_NULLABLE']) ?></td>
                  <td><?= htmlspecialchars($c['COLUMN_DEFAULT'] ?? '') ?></td>
                  <td><?= htmlspecialchars($c['COLUMN_KEY'] ?? '') ?></td>
                  <td><?= htmlspecialchars($c['EXTRA'] ?? '') ?></td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
        <h6>Índices</h6>
        <div class="table-responsive mb-3">
          <table class="table table-sm table-striped align-middle">
            <thead class="table-light"><tr><th>Índice</th><th>Único</th><th>#</th><th>Columna</th><th>Collation</th><th>Cardinality</th></tr></thead>
            <tbody>
              <?php if (!$idxInfo): ?>
                <tr><td colspan="6" class="text-muted text-center">Sin índices.</td></tr>
              <?php else: foreach ($idxInfo as $i): ?>
                <tr>
                  <td><code><?= htmlspecialchars($i['INDEX_NAME']) ?></code></td>
                  <td><?= $i['NON_UNIQUE'] ? 'No' : 'Sí' ?></td>
                  <td><?= (int)$i['SEQ_IN_INDEX'] ?></td>
                  <td><code><?= htmlspecialchars($i['COLUMN_NAME']) ?></code></td>
                  <td><?= htmlspecialchars($i['COLLATION'] ?? '') ?></td>
                  <td><?= htmlspecialchars((string)($i['CARDINALITY'] ?? '')) ?></td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
        <h6 class="mb-2">DDL (SHOW CREATE TABLE)</h6>
        <pre class="bg-light p-3 border rounded small" style="white-space:pre-wrap"><?= htmlspecialchars($ddlCreate ?: '-- Sin DDL disponible --') ?></pre>
        <a class="btn btn-sm btn-secondary mt-2" href="?page=configuracion">← Volver</a>
      </div></div>
      <?php endif; ?>

    <?php elseif ($page === 'mod'): ?>
      <?php
        // Abrir módulo (archivo único) dentro del dashboard
        $ruta = $_GET['ruta'] ?? '';
        if (!preg_match('/^[a-z0-9_]+$/', $ruta)) { $ruta = ''; }
        $file = $ruta ? "{$ruta}_index.php" : '';
        $fsPath = $ruta ? (__DIR__ . "/{$ruta}/{$file}") : '';
        $query = $_GET; $query['_inframe'] = '1';
        $iframeSrc = ($ruta && file_exists($fsPath)) ? "{$BASE}/{$ruta}/{$file}?" . http_build_query($query) : '';
      ?>
      <h4 class="mb-3">Módulo: <?= htmlspecialchars($ruta ?: '—') ?></h4>
      <?php if ($iframeSrc): ?>
        <iframe class="mod-frame" src="<?= htmlspecialchars($iframeSrc) ?>" title="Módulo <?= htmlspecialchars($ruta) ?>"></iframe>
      <?php elseif ($ruta): ?>
        <div class="alert alert-danger">
          No se encontró <code><?= htmlspecialchars($fsPath) ?></code>.<br>Genera el módulo desde Configuración o revisa permisos de escritura.
        </div>
      <?php else: ?>
        <div class="alert alert-warning">Selecciona un módulo desde el menú izquierdo.</div>
      <?php endif; ?>

    <?php else: ?>
      <h4><?= ucfirst(htmlspecialchars($page)) ?></h4>
      <p>Contenido de la sección <?= htmlspecialchars($page) ?>.</p>
    <?php endif; ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ===== Usuarios: cálculo DV (ayuda) =====
function calcularDV(rutNums) {
  let sum = 0, mul = 2;
  for (let i = rutNums.length - 1; i >= 0; i--) {
    sum += parseInt(rutNums[i], 10) * mul;
    mul = (mul === 7) ? 2 : mul + 1;
  }
  const res = 11 - (sum % 11);
  if (res === 11) return '0';
  if (res === 10) return 'K';
  return String(res);
}
const rutCrear = document.getElementById('rutCrear');
const dvCrear = document.getElementById('dvCrear');
if (rutCrear && dvCrear) {
  rutCrear.addEventListener('input', () => {
    const nums = rutCrear.value.replace(/\D/g,'');
    if (nums.length >= 7 && nums.length <= 9) { dvCrear.value = calcularDV(nums); }
  });
}
const modalEditar = document.getElementById('modalEditarUsuario');
if (modalEditar) {
  modalEditar.addEventListener('show.bs.modal', event => {
    const btn = event.relatedTarget;
    document.getElementById('rutEditar').value    = btn.getAttribute('data-rut');
    document.getElementById('dvEditar').value     = btn.getAttribute('data-dv');
    document.getElementById('nombreEditar').value = btn.getAttribute('data-nombre');
    document.getElementById('correoEditar').value = btn.getAttribute('data-correo');
  });
}

// ===== Configuración: agregar filas de campos =====
let idx = 1;
const btnAdd = document.getElementById('btnAdd');
if (btnAdd) {
  btnAdd.addEventListener('click', () => {
    const w = document.getElementById('camposWrap');
    const row = document.createElement('div');
    row.className = 'field-row';
    row.innerHTML = `
      <div>
        <label class="form-label">Nombre</label>
        <input class="form-control" name="campos[\${idx}][nombre]" required placeholder="campo_\${idx}">
      </div>
      <div>
        <label class="form-label">Tipo</label>
        <select class="form-select" name="campos[\${idx}][tipo]">
          <option value="varchar">VARCHAR</option>
          <option value="int">INT</option>
          <option value="bigint">BIGINT</option>
          <option value="decimal">DECIMAL(10,2)</option>
          <option value="date">DATE</option>
          <option value="datetime">DATETIME</option>
          <option value="text">TEXT</option>
        </select>
      </div>
      <div>
        <label class="form-label">Longitud</label>
        <input class="form-control" name="campos[\${idx}][len]" type="number" min="1" max="1000" placeholder="ej: 255">
      </div>
      <div>
        <label class="form-label">Nulo</label>
        <select class="form-select" name="campos[\${idx}][nulo]">
          <option value="0">NO</option>
          <option value="1">SÍ</option>
        </select>
      </div>
      <div>
        <label class="form-label">Único</label>
        <select class="form-select" name="campos[\${idx}][unico]">
          <option value="0">NO</option>
          <option value="1">SÍ</option>
        </select>
      </div>
      <div>
        <label class="form-label d-block">&nbsp;</label>
        <button class="btn btn-outline-danger" type="button" onclick="this.closest('.field-row').remove()">Eliminar</button>
      </div>`;
    w.appendChild(row);
    idx++;
  });
}
</script>
</body>
</html>
