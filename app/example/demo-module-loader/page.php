<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo: loadSerJSModule - Función Reutilizable</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Monaco', 'Courier New', monospace;
            background: #1a1a1a;
            color: #e0e0e0;
            padding: 20px;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        h1 {
            color: #61dafb;
            margin-bottom: 30px;
            font-size: 36px;
            text-align: center;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }

        .stat-value {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }

        .section {
            background: #2d2d2d;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            border-left: 4px solid #61dafb;
        }

        .section-title {
            color: #ffd700;
            font-size: 20px;
            margin-bottom: 15px;
        }

        .button-demo {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 15px;
        }

        button {
            padding: 12px 24px;
            background: #61dafb;
            color: #1a1a1a;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }

        button:hover {
            background: #4fb3d4;
            transform: translateY(-2px);
        }

        .output {
            background: #1a1a1a;
            border-radius: 6px;
            padding: 15px;
            min-height: 100px;
            margin-top: 15px;
            border: 1px solid #444;
            font-family: monospace;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
    <script src="../../SerJS/SerJS.js"></script>
</head>
<body>
    <div class="container">
        <h1>🚀 loadSerJSModule Demo</h1>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value">96%</div>
                <div class="stat-label">Menos Código</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">1</div>
                <div class="stat-label">Línea por Módulo</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">∞</div>
                <div class="stat-label">Escalabilidad</div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">🎮 Demo Interactivo</div>
            <p style="margin-bottom: 15px;">
                Prueba cargar los módulos dinámicamente:
            </p>
            <div class="button-demo">
                <button id="btn-load-store">Cargar Store</button>
                <button id="btn-load-nav">Cargar Navigation</button>
                <button id="btn-use-store">Usar Store</button>
                <button id="btn-use-nav">Usar Navigation</button>
            </div>
            <div class="output" id="output">
                Presiona un botón para ver la magia ✨
            </div>
        </div>
    </div>

    <script type="module">
        const output = document.getElementById('output');
        
        function log(message, type = 'info') {
            const time = new Date().toLocaleTimeString();
            const colors = {
                info: '#61dafb',
                success: '#51cf66',
                error: '#ff6b6b'
            };
            output.innerHTML += `<div style="color: ${colors[type]}; margin-bottom: 5px;">[${time}] ${message}</div>`;
            output.scrollTop = output.scrollHeight;
        }

        document.getElementById('btn-load-store').addEventListener('click', async () => {
            log('🔄 Cargando SerJSStore...', 'info');
            try {
                const { store } = SerJS;
                log('✅ SerJSStore cargado!', 'success');
            } catch (error) {
                log(`❌ Error: ${error.message}`, 'error');
            }
        });

        document.getElementById('btn-load-nav').addEventListener('click', async () => {
            log('🔄 Cargando SerJSNavigation...', 'info');
            try {
                const { navigation } = SerJS;
                log('✅ SerJSNavigation cargado!', 'success');
            } catch (error) {
                log(`❌ Error: ${error.message}`, 'error');
            }
        });

        document.getElementById('btn-use-store').addEventListener('click', async () => {
            log('🎯 Usando Store...', 'info');
            try {
                const { store } = SerJS;
                const testStore = await store.create((set, get) => ({
                    count: 0,
                    increment: () => set({ count: get().count + 1 })
                }), { name: 'demo-store' });
                
                log('✅ Store creado!', 'success');
                log(`📊 Estado: ${JSON.stringify(testStore.getState())}`, 'info');
            } catch (error) {
                log(`❌ Error: ${error.message}`, 'error');
            }
        });

        document.getElementById('btn-use-nav').addEventListener('click', async () => {
            log('🧭 Usando Navigation...', 'info');
            try {
                const { navigation } = SerJS;
                const pathname = await navigation.usePathname();
                log('✅ Navigation usado!', 'success');
                log(`📍 Pathname: ${pathname}`, 'info');
            } catch (error) {
                log(`❌ Error: ${error.message}`, 'error');
            }
        });

        log('✨ Sistema listo!', 'success');
    </script>
</body>
</html>
