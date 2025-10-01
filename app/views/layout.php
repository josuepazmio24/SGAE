<?php $u = $_SESSION['user'] ?? null; ?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SGAE</title>
<link rel="icon" type="image/png" href="<?= View::e(BASE_URL) ?>/assets/img/logo.png">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= View::e(BASE_URL) ?>/assets/css/custom.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
<?php if ($u): include __DIR__ . '/partials/sidebar.php'; endif; ?>
<div class="flex-grow-1">
<?php include __DIR__ . '/partials/topbar.php'; ?>
<main class="container py-4">
<?= $content ?>
</main>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>