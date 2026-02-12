<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test SerJSNavigation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 800px;
            width: 100%;
            padding: 40px;
        }

        h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 32px;
            text-align: center;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 15px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 10px;
            margin-bottom: 15px;
        }

        .info-label {
            font-weight: bold;
            color: #666;
        }

        .info-value {
            color: #333;
            word-break: break-all;
        }

        .button-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        button {
            padding: 12px 24px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        button:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        button:active {
            transform: translateY(0);
        }

        .secondary-btn {
            background: #6c757d;
        }

        .secondary-btn:hover {
            background: #5a6268;
        }

        .danger-btn {
            background: #dc3545;
        }

        .danger-btn:hover {
            background: #c82333;
        }

        input[type="text"] {
            flex: 1;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }

        input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
        }

        .event-log {
            background: white;
            padding: 15px;
            border-radius: 8px;
            max-height: 200px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }

        .event-item {
            padding: 5px;
            margin-bottom: 5px;
            border-left: 3px solid #667eea;
            padding-left: 10px;
        }

        .event-time {
            color: #999;
            margin-right: 10px;
        }

        .code-block {
            background: white;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            overflow-x: auto;
        }
    </style>

    <!-- Incluir SerJS -->
    <script src="../../SerJS/SerJS.js"></script>
</head>

<body>
    <div class="container">
        <h1>🧭 Navegación SerJS</h1>
        <p class="subtitle">Prueba del sistema de navegación con carga dinámica</p>

        <!-- Información actual -->
        <div class="section">
            <div class="section-title">📍 Información Actual</div>
            <div class="info-grid">
                <div class="info-label">Pathname:</div>
                <div class="info-value" id="current-pathname"></div>

                <div class="info-label">Search:</div>
                <div class="info-value" id="current-search"></div>

                <div class="info-label">Hash:</div>
                <div class="info-value" id="current-hash"></div>

                <div class="info-label">Query Params:</div>
                <div class="info-value" id="current-query"></div>
            </div>
        </div>

        <!-- Navegación básica -->
        <div class="section">
            <div class="section-title">🚀 Navegación Básica</div>
            <div class="button-group">
                <button id="btn-push">Push (/test)</button>
                <button id="btn-replace">Replace (/replaced)</button>
                <button id="btn-back" class="secondary-btn">← Back</button>
                <button id="btn-forward" class="secondary-btn">Forward →</button>
                <button id="btn-refresh" class="danger-btn">🔄 Refresh</button>
            </div>
        </div>

        <!-- Query params -->
        <div class="section">
            <div class="section-title">🔍 Query Parameters</div>
            <div class="button-group" style="margin-bottom: 15px;">
                <input type="text" id="param-key" placeholder="Key (ej: filter)">
                <input type="text" id="param-value" placeholder="Value (ej: active)">
                <button id="btn-add-param">Agregar</button>
                <button id="btn-clear-params" class="danger-btn">Limpiar Todo</button>
            </div>
            <div class="code-block" id="query-preview"></div>
        </div>

        <!-- Hooks -->
        <div class="section">
            <div class="section-title">🪝 Hooks de Navegación</div>
            <div class="button-group">
                <button id="btn-use-pathname">usePathname()</button>
                <button id="btn-use-search">useSearchParams()</button>
                <button id="btn-use-router">useRouter()</button>
                <button id="btn-use-query">useQuery()</button>
            </div>
            <div class="code-block" id="hooks-output" style="margin-top: 15px;"></div>
        </div>

        <!-- Utilidades -->
        <div class="section">
            <div class="section-title">🛠️ Utilidades</div>
            <div class="button-group">
                <button id="btn-is-active">isActive('/test')</button>
                <button id="btn-build-url">buildUrl()</button>
                <button id="btn-get-param">getQueryParam('id')</button>
                <button id="btn-match-path">matchPath()</button>
            </div>
            <div class="code-block" id="utils-output" style="margin-top: 15px;"></div>
        </div>

        <!-- Log de eventos -->
        <div class="section">
            <div class="section-title">📋 Log de Eventos</div>
            <div class="event-log" id="event-log">
                <div class="event-item">
                    <span class="event-time">--:--:--</span>
                    Sistema iniciado
                </div>
            </div>
        </div>
    </div>

    <script type="module">
        // ====================================
        // DESTRUCTURAR SERJS
        // ====================================
        const { useRef, setText, navigation } = SerJS;

        // ====================================
        // REFERENCIAS
        // ====================================
        const currentPathnameRef = useRef('current-pathname');
        const currentSearchRef = useRef('current-search');
        const currentHashRef = useRef('current-hash');
        const currentQueryRef = useRef('current-query');
        const queryPreviewRef = useRef('query-preview');
        const hooksOutputRef = useRef('hooks-output');
        const utilsOutputRef = useRef('utils-output');
        const eventLogRef = useRef('event-log');
        const paramKeyRef = useRef('param-key');
        const paramValueRef = useRef('param-value');

        // ====================================
        // FUNCIONES AUXILIARES
        // ====================================
        function logEvent(message) {
            const now = new Date();
            const time = now.toLocaleTimeString('es-ES');
            const eventHtml = `
                <div class="event-item">
                    <span class="event-time">${time}</span>
                    ${message}
                </div>
            `;
            if (eventLogRef.current) {
                eventLogRef.current.innerHTML = eventHtml + eventLogRef.current.innerHTML;
            }
        }

        // ====================================
        // ACTUALIZAR INFORMACIÓN
        // ====================================
        async function updateCurrentInfo() {

            const pathname = await navigation.usePathname();
            const searchParams = await navigation.useSearchParams();
            const query = await navigation.useQuery();
            const hash = await navigation.useHash();

            setText(currentPathnameRef, pathname || '(vacío)');
            setText(currentSearchRef, searchParams.toString() || '(vacío)');
            setText(currentHashRef, hash || '(vacío)');
            setText(currentQueryRef, JSON.stringify(query, null, 2));
            setText(queryPreviewRef, JSON.stringify(query, null, 2));
        }

        // ====================================
        // EVENTOS DE NAVEGACIÓN
        // ====================================
        useRef('btn-push').onClick(async () => {
            logEvent('🚀 Ejecutando push("/test")');
            await navigation.push('/test', { reload: false });
            updateCurrentInfo();
        });

        useRef('btn-replace').onClick(async () => {
            logEvent('🔄 Ejecutando replace("/replaced")');
            await navigation.replace('/replaced', { reload: false });
            updateCurrentInfo();
        });

        useRef('btn-back').onClick(async () => {
            logEvent('← Ejecutando back()');
            await navigation.back();
        });

        useRef('btn-forward').onClick(async () => {
            logEvent('→ Ejecutando forward()');
            await navigation.forward();
        });

        useRef('btn-refresh').onClick(async () => {
            logEvent('🔄 Ejecutando refresh()');
            await navigation.refresh();
        });

        // ====================================
        // QUERY PARAMS
        // ====================================
        useRef('btn-add-param').onClick(async () => {
            const key = paramKeyRef.current?.value;
            const value = paramValueRef.current?.value;

            if (key && value) {
                logEvent(`➕ Agregando param: ${key}=${value}`);
                await navigation.setQueryParams({ [key]: value });
                updateCurrentInfo();

                if (paramKeyRef.current) paramKeyRef.current.value = '';
                if (paramValueRef.current) paramValueRef.current.value = '';
            }
        });

        useRef('btn-clear-params').onClick(async () => {
            logEvent('🗑️ Limpiando todos los query params');
            await navigation.setQueryParams({}, false);
            updateCurrentInfo();
        });

        // ====================================
        // HOOKS
        // ====================================
        useRef('btn-use-pathname').onClick(async () => {
            const result = await navigation.usePathname();
            setText(hooksOutputRef, `usePathname() = "${result}"`);
            logEvent(`🪝 usePathname() = "${result}"`);
        });

        useRef('btn-use-search').onClick(async () => {
            const result = await navigation.useSearchParams();
            const obj = Object.fromEntries(result);
            setText(hooksOutputRef, `useSearchParams() = ${JSON.stringify(obj, null, 2)}`);
            logEvent(`🪝 useSearchParams() ejecutado`);
        });

        useRef('btn-use-router').onClick(async () => {
            const router = await navigation.useRouter();
            const result = {
                pathname: router.pathname,
                query: router.query,
                asPath: router.asPath
            };
            setText(hooksOutputRef, JSON.stringify(result, null, 2));
            logEvent(`🪝 useRouter() ejecutado`);
        });

        useRef('btn-use-query').onClick(async () => {
            const result = await navigation.useQuery();
            setText(hooksOutputRef, JSON.stringify(result, null, 2));
            logEvent(`🪝 useQuery() ejecutado`);
        });

        // ====================================
        // UTILIDADES
        // ====================================
        useRef('btn-is-active').onClick(async () => {
            const result = await navigation.isActive('/test');
            setText(utilsOutputRef, `isActive('/test') = ${result}`);
            logEvent(`🛠️ isActive('/test') = ${result}`);
        });

        useRef('btn-build-url').onClick(async () => {
            const result = await navigation.buildUrl('/productos', {
                categoria: 'electronica',
                sort: 'precio'
            });
            setText(utilsOutputRef, `buildUrl() = "${result}"`);
            logEvent(`🛠️ buildUrl() = "${result}"`);
        });

        useRef('btn-get-param').onClick(async () => {
            const result = await navigation.getQueryParam('id');
            setText(utilsOutputRef, `getQueryParam('id') = "${result}"`);
            logEvent(`🛠️ getQueryParam('id') = "${result}"`);
        });

        useRef('btn-match-path').onClick(async () => {
            const result = await navigation.matchPath('/test/:id');
            setText(utilsOutputRef, `matchPath('/test/:id') = ${result}`);
            logEvent(`🛠️ matchPath('/test/:id') = ${result}`);
        });

        // ====================================
        // INICIALIZACIÓN
        // ====================================
        (async () => {
            await updateCurrentInfo();
            logEvent('✅ Sistema de navegación SerJS cargado');
            console.log('✅ SerJS Navigation Test inicializado');
        })();

        // ====================================
        // ESCUCHAR EVENTOS DE NAVEGACIÓN
        // ====================================
        window.addEventListener('popstate', () => {
            logEvent('🔔 Evento popstate detectado');
            updateCurrentInfo();
        });
    </script>
</body>

</html>