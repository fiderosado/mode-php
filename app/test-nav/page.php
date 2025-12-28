<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SerJS Navigation</title>
    <script src="../../SerJS/SerJS.js"></script>
    <script src="../../SerJS/navigation/navigation.js"></script>
</head>
<body>
    <h1>SerJS Navigation</h1>
    <script>
        const { useRef, useEffect, useState , useMemo , setText , reRender } = SerJS;
        const { push, useRouter } = SerJSNavigation;

    </script>
    <button onclick="push('/test-js')">Test JS</button>
    <button onclick="push('/test-nav')">Test Nav</button>
</body>
</html>
