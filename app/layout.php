<!DOCTYPE html>
<html>
<head>
  <title>App Router PHP</title>
  <link rel="stylesheet" href="app/css/tailwind.css">
</head>
<body class="min-h-screen !flex !flex-col">
  <header class="block">Header</header>
  <main class="flex-1 w-full !grow"> 
    <?php require $page; ?>
  </main>
  <footer class="block">Footer</footer>
</body>
</html>