<?php
$TITLE = 'Módulo';
require __DIR__ . '/_layout_top.php';

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
  <div class="alert alert-danger">No se encontró <code><?= htmlspecialchars($fsPath) ?></code>. Genera el módulo desde Configuración o revisa permisos de escritura.</div>
<?php else: ?>
  <div class="alert alert-warning">Selecciona un módulo desde el menú izquierdo.</div>
<?php endif; ?>
<?php require __DIR__ . '/_layout_bottom.php'; ?>
