<?php
$baseUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
?>
<!DOCTYPE html>
<html>
<head id="app-head">
  <title>App Router PHP</title>
  <link rel="stylesheet" href="<?= $baseUrl ?>/app/css/tailwind.css">
  <script src="/SerJS/SerJS.js"></script>
  <!-- end head layout -->
</head>
<body class="min-h-screen !flex !flex-col">
  <header class="block">Header 2026</header>
  <main class="flex-1 w-full !grow"> 
    <?php require $page; ?>
  </main>
  <footer class="block">Footer 2026</footer>
</body>
</html>