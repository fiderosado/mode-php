<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SerJS - Ejemplo de Uso</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            padding: 20px; 
            background: #f5f5f5;
        }
        .container { 
            max-width: 800px; 
            margin: 0 auto; 
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { 
            color: #333; 
            margin-bottom: 20px;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
        }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input { 
            padding: 10px; 
            width: 100%; 
            border: 2px solid #ddd; 
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        input:focus { 
            outline: none; 
            border-color: #007bff; 
        }
        input.error { border-color: #dc3545; }
        input.success { border-color: #28a745; }
        button { 
            padding: 12px 24px; 
            background: #007bff; 
            color: white; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer;
            font-size: 14px;
            margin-right: 10px;
            transition: background 0.3s;
        }
        button:hover { background: #0056b3; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .user-item { 
            padding: 15px; 
            margin: 10px 0; 
            background: #f8f9fa; 
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 4px solid #007bff;
            transition: transform 0.2s;
        }
        .user-item:hover {
            transform: translateX(5px);
        }
        .box { 
            padding: 20px; 
            margin: 20px 0; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            text-align: center;
            font-size: 18px;
        }
        .message { 
            padding: 15px; 
            margin: 15px 0; 
            border-radius: 5px;
            display: none;
            animation: slideIn 0.3s;
        }
        @keyframes slideIn {
            from { transform: translateY(-10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .message.success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .message.error { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        .stats {
            display: flex;
            gap: 20px;
            margin: 20px 0;
        }
        .stat-card {
            flex: 1;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            text-align: center;
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #007bff;
        }
        .stat-label {
            color: #6c757d;
            margin-top: 5px;
        }
    </style>
    <script src="../../SerJS/SerJS.js"></script>
</head>
<body>
    <div class="container">
        <h1>🚀 SerJS - Demo Completo</h1>
        
        <!-- Mensajes -->
        <div id="message" class="message"></div>
        
        <!-- Estadísticas -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number" id="totalUsers">0</div>
                <div class="stat-label">Usuarios</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="lastUpdate">--:--</div>
                <div class="stat-label">Última Actualización</div>
            </div>
        </div>
        
        <!-- Formulario -->
        <h2>Crear Usuario</h2>
        <form id="userForm">
            <div class="form-group">
                <label>Nombre completo:</label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    placeholder="Ingresa tu nombre" 
                    required
                >
            </div>
            
            <div class="form-group">
                <label>Email:</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    placeholder="tu@email.com" 
                    required
                >
            </div>
            
            <button type="submit">✓ Crear Usuario</button>
            <button type="button" id="clearStorage" class="btn-danger">🗑️ Limpiar Todo</button>
        </form>
        
        <!-- Botones de prueba -->

        <h2 style="margin-top: 30px;">Animaciones</h2>
        <button id="toggleBtn" class="btn-secondary">Toggle Box</button>
        <!-- <button id="fadeBtn" class="btn-secondary">Fade Toggle</button>
        <button id="slideBtn" class="btn-secondary">Slide Toggle</button> -->

        <script>
        
                const { useRef, useEffect, useState , useMemo , setText , reRender } = SerJS;

                const boxRef = useRef('animatedBox');
                const countUsersRef = useRef('countUsers');
 
                const [count, setCount] = useState(1);

                const double = useMemo(() => {
                    console.log('recalculando...');
                    return count.current * 2;
                }, [count]);

                useEffect(() => {
                    console.log("el estado cambio solo al inicio --> ", count , count.current );
                    console.log("estado del memo",double, double.current);
                    reRender(boxRef, { count : count.current });
                    reRender(countUsersRef, { count : count.current });
                }, []);

                useEffect(() => {
                    console.log("el estado cambio --> ", count , count.current );
                    reRender(boxRef, { count : count.current });
                    reRender(countUsersRef, { count : count.current });
                    console.log("estado del memo",double, double.current);
                }, [count]);

                const btnRef = useRef('toggleBtn');
                btnRef.onClick(() => {
                    setCount(prev => prev + 1);
                });

        </script>

        <div class="box" id="animatedBox">
            ¡Hola! Soy una caja animada 🎨 ${count}
        </div>
        
        <!-- Lista de usuarios -->
        <h2 id="countUsers">Lista de Usuarios  ${count}</h2>
        <div id="userList"></div>
    </div>
</body>
</html>
