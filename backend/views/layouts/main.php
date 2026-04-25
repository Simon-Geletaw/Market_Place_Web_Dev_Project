<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Service Marketplace</title>
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
  <?php require __DIR__ . '/../components/navbar.php'; ?>
  <main class="container">
    <?= $content ?? '' ?>
  </main>
  <script src="/assets/js/app.js"></script>
</body>
</html>
