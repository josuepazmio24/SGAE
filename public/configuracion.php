<?php
$TITLE = 'Configuración • Generador';
require __DIR__ . '/_layout_top.php';
require __DIR__ . '/../includes/helpers.php';

$configFlash = null;
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['accion'] ?? '') === 'crear_modulo') {
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

    if ($etiqueta===''||$ruta===''||$tabla==='') throw new RuntimeException('Etiqueta, ruta y tabla son obligatorios.');
    if (!$campos || !is_array($campos)) throw new RuntimeException('Debes definir al menos un campo.');

    $cols = ["id INT AUTO_INCREMENT PRIMARY KEY"];
    $meta = [];
    foreach ($campos as $c) {
      $nombre = limpiar_ident($c['nombre'] ?? '');
      if ($nombre==='') throw new RuntimeException('Nombre de campo inválido.');
      $tipo   = map_tipo_sql($c['tipo'] ?? 'varchar', isset($c['len']) ? (int)$c['len'] : null);
      $nulo   = (isset($c['nulo']) && $c['nulo']==='1') ? 'NULL' : 'NOT NULL';
      $unico  = isset($c['unico']) && $c['unico']==='1';
      $cols[] = "`$nombre` $tipo $nulo";
      if ($unico) $cols[] = "UNIQUE KEY uq_{$tabla}_{$nombre} (`$nombre`)";
      $meta[] = ['nombre'=>$nombre, 'tipo'=>strtolower($c['tipo'] ?? 'varchar')];
    }
    $cols[] = "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
    $ddl = "CREATE TABLE IF NOT EXISTS `$tabla` (\n  ".implode(",\n  ", $cols)."\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $pdo->exec($ddl);

    $st = $pdo->prepare("INSERT INTO sgae_modulos (nombre, etiqueta, ruta, tabla) VALUES (:n,:e,:r,:t)");
    $st->execute([':n'=>$tabla, ':e'=>$etiqueta, ':r'=>$ruta, ':t'=>$tabla]);

    $base = __DIR__ . "/{$ruta}";
    if (!is_dir($base) && !mkdir($base, 0775, true) && !is_dir($base)) {
      throw new RuntimeException("No se pudo crear la carpeta $base");
    }
    $tpl = tpl_modulo_unico($ruta, $tabla, $etiqueta, $meta);
    file_put_contents("$base/{$ruta}_index.php", $tpl);
    if (!file_exists("$base/{$ruta}_index.php")) throw new RuntimeException("No se pudo escribir $base/{$ruta}_index.php");

    $configFlash = ['type'=>'success', 'msg'=>"Módulo generado correctamente."];
  } catch (Throwable $e) { $configFlash = ['type'=>'danger','msg'=>$e->getMessage()]; }
}
?>
<h4>Configuración • Generador de Módulos</h4>
<?php if ($configFlash): ?><div class="alert alert-<?= htmlspecialchars($configFlash['type']) ?>"><?= htmlspecialchars($configFlash['msg']) ?></div><?php endif; ?>

<!-- (form generador) => igual al que tenías, abreviado por espacio: -->
<?php /* Puedes pegar aquí exactamente el formulario que ya usabas.
       Para ahorrar espacio, lo omití; tu formulario original funciona igual
       apuntando a esta misma página. */ ?>

<hr class="my-4">
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
            <a class="btn btn-sm btn-outline-secondary me-1" href="<?= $BASE ?>/mod.php?ruta=<?= urlencode($m['ruta']) ?>">Abrir</a>
            <a class="btn btn-sm btn-outline-primary" href="<?= $BASE ?>/configuracion.php?inspect=<?= urlencode($m['tabla']) ?>">Ver estructura</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php
// Inspector
$inspect = isset($_GET['inspect']) ? preg_replace('/[^a-z0-9_]/i','', $_GET['inspect']) : null;
if ($inspect) {
  $colsInfo = $idxInfo = []; $ddlCreate = '';
  try {
    $st = $pdo->prepare("SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_KEY, EXTRA
                         FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t
                         ORDER BY ORDINAL_POSITION");
    $st->execute([':t'=>$inspect]); $colsInfo = $st->fetchAll(PDO::FETCH_ASSOC);

    $st2 = $pdo->prepare("SELECT INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX, COLUMN_NAME, COLLATION, CARDINALITY
                          FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t
                          ORDER BY INDEX_NAME, SEQ_IN_INDEX");
    $st2->execute([':t'=>$inspect]); $idxInfo = $st2->fetchAll(PDO::FETCH_ASSOC);

    $ddl = $pdo->query("SHOW CREATE TABLE `{$inspect}`")->fetch(PDO::FETCH_ASSOC);
    if ($ddl && isset($ddl['Create Table'])) $ddlCreate = $ddl['Create Table'];
  } catch (Throwable $e) {}
  ?>
  <div class="card shadow-sm mt-4"><div class="card-body">
    <h5 class="mb-3">Estructura de <code><?= htmlspecialchars($inspect) ?></code></h5>
    <div class="table-responsive mb-3">
      <table class="table table-sm table-striped align-middle">
        <thead class="table-light"><tr><th>Columna</th><th>Tipo</th><th>Nulo</th><th>Default</th><th>Clave</th><th>Extra</th></tr></thead>
        <tbody>
          <?php if (!$colsInfo): ?><tr><td colspan="6" class="text-muted text-center">No se encontraron columnas.</td></tr>
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
    <div class="table-responsive mb-3">
      <table class="table table-sm table-striped align-middle">
        <thead class="table-light"><tr><th>Índice</th><th>Único</th><th>#</th><th>Columna</th><th>Collation</th><th>Cardinality</th></tr></thead>
        <tbody>
          <?php if (!$idxInfo): ?><tr><td colspan="6" class="text-muted text-center">Sin índices.</td></tr>
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
    <h6>DDL</h6>
    <pre class="bg-light p-3 border rounded small" style="white-space:pre-wrap"><?= htmlspecialchars($ddlCreate ?: '-- Sin DDL --') ?></pre>
    <a class="btn btn-sm btn-secondary mt-2" href="<?= $BASE ?>/configuracion.php">← Volver</a>
  </div></div>
<?php } ?>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
