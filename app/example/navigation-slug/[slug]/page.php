<?php
use function Core\Url\SearchParams;
$searchParams = searchParams();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SerJS Navigation</title>
    <script src="../../SerJS/SerJS.js"></script>
    <script src="../../SerJS/core/SerJSNavigation.js"></script>
</head>
<body>
    <h1>SerJS Navigation slug</h1>
    <script>
        const { useRef, useEffect, useState , useMemo , setText , reRender } = SerJS;
      
        const { push, useRouter , usePathname , setQueryParams, useSearchParams , useQuery , useParams , useHash} = SerJSNavigation;

        setQueryParams(<?= json_encode($searchParams->params); ?>, true);

        const params = useParams(<?= json_encode($searchParams->params); ?> );

        const pathname = usePathname();

        console.log("pathname:", pathname)

        const query = useQuery();

        console.log("useQuery:", query)

        console.log("useParams:", params , params.slug); // "mi-post"

        const router = useRouter(<?= json_encode($searchParams); ?>);

        console.log('router.pathname', router.pathname);  // "/blog/post-1"
        console.log("router.query", router.query);     // { id: "123", filter: "active" }
        console.log('router.params', router.params);    // { slug: "post-1" }
        console.log('router.asPath', router.asPath);    // "/blog/post-1?id=123#comments"

        const hash = useHash();
        console.log("useHash:",hash); // "#comments"

    </script>
    <button onclick="push('/test-js')">Test JS</button>
    <button onclick="push('/test-nav')">Test Nav</button>
</body>
</html>
