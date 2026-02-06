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
    <h1>SerJS Navigation</h1>
    <script>
        const { useRef, useEffect, useState , useMemo , setText , reRender } = SerJS;
      
        const { push, useRouter , usePathname , useSearchParams , useQuery , useParams } = SerJSNavigation;

        const params = useParams();
        const pathname = usePathname();
        console.log("pathname:", pathname)

        const searchParams = useSearchParams();
        const query = useQuery();

        console.log("useQuery:", query)
        console.log(searchParams.get('id'));
        console.log(searchParams.get('filter'));

        // Iterar
        for (const [key, value] of searchParams) {
            console.log(`${key}: ${value}`);
        }

        console.log(params.slug); // "mi-post"

    </script>
    <button onclick="push('/test-js')">Test JS</button>
    <button onclick="push('/test-nav')">Test Nav</button>
</body>
</html>
