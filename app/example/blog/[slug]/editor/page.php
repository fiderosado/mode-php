<?php

use Core\SuspenseAction;
use Core\Html\Elements\Div;
use Core\Suspense;

use function Core\Url\searchParams;

$searchParams = searchParams();

$slug = $searchParams->params->slug;
$query = $searchParams->query;

//var_dump($searchParams);
?>
<html lang="en">
    <!-- <script>
        SerJS.add("app-head","<title>este es mi titulo</title>");
    </script> -->
    <div id="editor">esto es un editor para el post con slug: <?php echo $slug; ?></div>
    <!-- meter un suspense para el editor -->
    <?php
    Suspense::in(
        Div::in("editor"),
        SuspenseAction::in("editor-suspense")
    )->build()
    ?>
</html>