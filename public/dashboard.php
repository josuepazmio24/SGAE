<?php
$TITLE = 'SGAE - Dashboard';
require __DIR__ . '/_layout_top.php';
?>
<h4>SGAE - Dashboard</h4>
<div class="row g-3 mt-1">
<?php
$cards = [];
if (tableExists($pdo, 'usuarios')) $cards[] = ['Usuarios totales', number_format(tableCount($pdo,'usuarios')), 'bg-primary text-white'];
if (tableExists($pdo, 'alumnos'))  $cards[] = ['Alumnos totales',  number_format(tableCount($pdo,'alumnos')),  'bg-success text-white'];
if (tableExists($pdo, 'reportes')) $cards[] = ['Reportes',          number_format(tableCount($pdo,'reportes')), 'bg-warning text-dark'];
if (tableExists($pdo, 'control_ingreso')) $cards[] = ['Registros de ingreso', number_format(tableCount($pdo,'control_ingreso')), 'bg-info text-dark'];
foreach ($cards as [$t,$v,$cls]): ?>
  <div class="col-sm-6 col-lg-4"><div class="card shadow-sm <?= $cls ?>"><div class="card-body">
    <h6 class="mb-1"><?= htmlspecialchars($t) ?></h6>
    <div class="display-6 fw-bold"><?= $v ?></div>
  </div></div></div>
<?php endforeach; ?>
</div>
<?php require __DIR__ . '/_layout_bottom.php'; ?>
