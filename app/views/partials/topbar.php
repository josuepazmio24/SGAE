<header class="border-bottom bg-white">
<div class="container-fluid py-2 d-flex align-items-center justify-content-between">
<div>
<?php if (!empty($_SESSION['user'])): ?>
<span class="text-muted">Panel general</span>
<?php else: ?>
<span class="text-muted">Autenticación</span>
<?php endif; ?>
</div>
<div>
<?php if (!empty($_SESSION['user'])): ?>
<span class="me-3">👤 <?= View::e($_SESSION['user']['username']) ?> (<?= View::e($_SESSION['user']['rol']) ?>)</span>
<a class="btn btn-outline-secondary btn-sm" href="<?= View::e(BASE_URL) ?>/index.php?r=auth/logout">Cerrar sesión</a>
<?php endif; ?>
</div>
</div>
</header>