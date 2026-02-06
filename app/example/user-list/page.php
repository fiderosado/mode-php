<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SerJS - Ejemplo de Uso</title>
    <?php if (isset($GLOBALS['css']) && $GLOBALS['css']): ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($GLOBALS['css']); ?>">
    <?php endif; ?>
    <script src="../../SerJS/SerJS.js"></script>
</head>
<body>
    <div class="container mx-auto max-w-md">
        <h1>🚀 SerJS - Lista de Usuarios</h1>
        
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
