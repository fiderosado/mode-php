<?php

use function Core\Url\searchParams;

$searchParams = searchParams();

$slug = $searchParams->params->slug;
$query = $searchParams->query;

var_dump($searchParams);
?>

<h1>Post deleted: <?= htmlspecialchars($slug) ?></h1>