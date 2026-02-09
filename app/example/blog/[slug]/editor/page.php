<?php

use Core\SuspenseAction;
use Core\Html\Elements\Div;
use Core\Html\Suspense;

use function Core\Url\searchParams;

$searchParams = searchParams();

$slug = $searchParams->params->slug;
$query = $searchParams->query;

//var_dump($searchParams);
?>
<html lang="en">
<div id="editor">esto es un editor para el post con slug: <?php echo $slug; ?></div>
<!-- meter un suspense para el editor -->

<div>
    <!-- agregar un boton con el texto obtener fallback -->
    <button type="button" id="obtener-fallback" class="bg-black px-4 py-2 text-white">Obtener Fallback</button>
</div>

<?php

Suspense::in(
    Div::in("editor"),
    SuspenseAction::in("editor-suspense")
)->class('p-2')->render();

Suspense::in(
    Div::in("preparando un hola"),
    SuspenseAction::in("hola-suspense")
)->class('p-2')->render();

?>

<script>
    const {
        useRef,
        suspense
    } = SerJS;

    const botonRef = useRef("obtener-fallback");
    botonRef.onClick(async () => {
        // Obtiene la instancia de suspense
        const s = await suspense.action("hola-suspense");
        if (!s) return console.warn("Suspense no encontrado");
        await s.call({
            id: Math.random().toString(36).substring(2, 10) // generar aleatorio
        });
    });
</script>



</html>