<?php

use Core\Utils\Console;

use function Core\Url\searchParams;

$searchParams = searchParams();

$sections = $searchParams->params->sections;
$query = $searchParams->query;

Console::log("aver esto ke es", $sections , $query);

?>

<h1>Business Host Ads Sections</h1>
