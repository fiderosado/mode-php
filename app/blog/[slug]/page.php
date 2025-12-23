<?php

use function Core\Url\SearchParams;

$searchParams = searchParams();

$slug = $searchParams->params->slug;
$query = $searchParams->query;

?>

<h1>Post: <?= htmlspecialchars($slug) ?></h1>
<!-- Imprimir una lista de parametros que vienen en query -->

<ul>
    <?php foreach ($query as $key => $value) : ?>
        <li><?= htmlspecialchars($key) ?>: <?= htmlspecialchars($value) ?></li>
    <?php endforeach; ?>
</ul>


