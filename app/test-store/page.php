<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test SerJSStore - Lista de Tareas</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
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
            max-width: 600px;
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

        .stats {
            display: flex;
            justify-content: space-around;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .stat {
            text-align: center;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        .input-group {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        input[type="text"] {
            flex: 1;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
        }

        button {
            padding: 15px 30px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
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

        .filters {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 10px 20px;
            background: #e0e0e0;
            color: #333;
            font-size: 14px;
        }

        .filter-btn.active {
            background: #667eea;
            color: white;
        }

        .clear-btn {
            background: #ff6b6b;
            margin-left: auto;
        }

        .clear-btn:hover {
            background: #ff5252;
        }

        .todo-list {
            list-style: none;
            max-height: 400px;
            overflow-y: auto;
        }

        .todo-item {
            display: flex;
            align-items: center;
            padding: 15px;
            margin-bottom: 10px;
            background: #f8f9fa;
            border-radius: 10px;
            transition: all 0.3s;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .todo-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }

        .todo-item.completed {
            opacity: 0.6;
        }

        .todo-item.completed .todo-text {
            text-decoration: line-through;
            color: #999;
        }

        .todo-checkbox {
            width: 24px;
            height: 24px;
            margin-right: 15px;
            cursor: pointer;
            accent-color: #667eea;
        }

        .todo-text {
            flex: 1;
            font-size: 16px;
            color: #333;
            word-break: break-word;
        }

        .todo-date {
            font-size: 12px;
            color: #999;
            margin-right: 15px;
        }

        .delete-btn {
            padding: 8px 16px;
            background: #ff6b6b;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }

        .delete-btn:hover {
            background: #ff5252;
            transform: scale(1.05);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .empty-state-text {
            font-size: 18px;
            margin-bottom: 10px;
        }

        .empty-state-subtext {
            font-size: 14px;
            color: #bbb;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            color: #999;
            font-size: 12px;
        }

        .debug-panel {
            margin-top: 20px;
            padding: 15px;
            background: #f0f0f0;
            border-radius: 10px;
            font-size: 12px;
        }

        .debug-title {
            font-weight: bold;
            margin-bottom: 10px;
            color: #667eea;
        }

        .debug-content {
            background: white;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
            max-height: 200px;
        }

        /* Scrollbar personalizado */
        .todo-list::-webkit-scrollbar,
        .debug-content::-webkit-scrollbar {
            width: 8px;
        }

        .todo-list::-webkit-scrollbar-track,
        .debug-content::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .todo-list::-webkit-scrollbar-thumb,
        .debug-content::-webkit-scrollbar-thumb {
            background: #667eea;
            border-radius: 10px;
        }

        .todo-list::-webkit-scrollbar-thumb:hover,
        .debug-content::-webkit-scrollbar-thumb:hover {
            background: #5568d3;
        }
    </style>

    <!-- Incluir SerJS y SerJSStore -->
    <script src="../../SerJS/SerJS.js"></script>
   <!--  <script src="../../SerJS/core/SerJSStore.js"></script> -->
    
</head>
<body>
    <div class="container">
        <h1>📝 Lista de Tareas</h1>
        <p class="subtitle">Ejemplo de SerJSStore con persistencia en localStorage</p>

        <!-- Estadísticas -->
        <div class="stats">
            <div class="stat">
                <div class="stat-value" id="total-count">0</div>
                <div class="stat-label">Total</div>
            </div>
            <div class="stat">
                <div class="stat-value" id="active-count">0</div>
                <div class="stat-label">Activas</div>
            </div>
            <div class="stat">
                <div class="stat-value" id="completed-count">0</div>
                <div class="stat-label">Completadas</div>
            </div>
        </div>

        <!-- Input para agregar tareas -->
        <div class="input-group">
            <input 
                type="text" 
                id="todo-input" 
                placeholder="Escribe una nueva tarea..."
                autocomplete="off"
            >
            <button id="add-btn">Agregar</button>
        </div>

        <!-- Filtros -->
        <div class="filters">
            <button id="filter-all-btn" class="filter-btn active">Todas</button>
            <button id="filter-active-btn" class="filter-btn">Activas</button>
            <button id="filter-completed-btn" class="filter-btn">Completadas</button>
            <button id="clear-btn" class="clear-btn">Limpiar Completadas</button>
        </div>

        <!-- Lista de tareas -->
        <ul class="todo-list" id="todo-list">
            <!-- Las tareas se renderizarán aquí -->
        </ul>

        <!-- Estado vacío -->
        <div class="empty-state" id="empty-state" style="display: none;">
            <div class="empty-state-icon">📭</div>
            <div class="empty-state-text">No hay tareas</div>
            <div class="empty-state-subtext">Agrega una nueva tarea para comenzar</div>
        </div>

        <!-- Panel de debug (opcional) -->
        <div class="debug-panel">
            <div class="debug-title">🔍 Estado del Store (localStorage)</div>
            <div class="debug-content">
                <pre id="debug-output"></pre>
            </div>
        </div>

        <div class="footer">
            Powered by SerJS + SerJSStore • Los datos se guardan automáticamente
        </div>
    </div>



    <script type="module">
        // ====================================
        // DESTRUCTURAR SERJS
        // ====================================
        const { useRef, useState, useEffect, setText, setHTML, store } = SerJS;

        // ====================================
        // REFERENCIAS A ELEMENTOS
        // ====================================
        const todoInputRef = useRef('todo-input');
        const todoListRef = useRef('todo-list');
        const emptyStateRef = useRef('empty-state');
        const totalCountRef = useRef('total-count');
        const activeCountRef = useRef('active-count');
        const completedCountRef = useRef('completed-count');
        const debugOutputRef = useRef('debug-output');
        const addBtnRef = useRef('add-btn');
        const filterAllRef = useRef('filter-all-btn');
        const filterActiveRef = useRef('filter-active-btn');
        const filterCompletedRef = useRef('filter-completed-btn');
        const clearBtnRef = useRef('clear-btn');

        // ====================================
        // CREAR STORE DE TAREAS
        // ====================================

        console.log("que tipo es store:" , store , typeof store);

        const useTodoStore = await store.create((set, get) => ({
            todos: [],
            filter: 'all',
            
            addTodo: (text) => {
                if (!text.trim()) return;
                const newTodo = {
                    id: Date.now(),
                    text: text.trim(),
                    completed: false,
                    createdAt: new Date().toISOString()
                };
                set({ todos: [...get().todos, newTodo] });
            },
            
            toggleTodo: (id) => {
                set({
                    todos: get().todos.map(todo =>
                        todo.id === id ? { ...todo, completed: !todo.completed } : todo
                    )
                });
            },
            
            deleteTodo: (id) => {
                set({ todos: get().todos.filter(todo => todo.id !== id) });
            },
            
            setFilter: (filter) => {
                set({ filter });
            },
            
            getFilteredTodos: () => {
                const { todos, filter } = get();
                switch(filter) {
                    case 'active': return todos.filter(t => !t.completed);
                    case 'completed': return todos.filter(t => t.completed);
                    default: return todos;
                }
            },
            
            clearCompleted: () => {
                set({ todos: get().todos.filter(t => !t.completed) });
            },
            
            getStats: () => {
                const todos = get().todos;
                return {
                    total: todos.length,
                    active: todos.filter(t => !t.completed).length,
                    completed: todos.filter(t => t.completed).length
                };
            }
        }), {
            name: 'todo-list-app',
            persist: true,
            version: 1
        });

        // ====================================
        // ESTADOS LOCALES CON SERJS
        // ====================================
        const [renderTrigger, setRenderTrigger] = useState(0);

        // ====================================
        // UTILIDADES
        // ====================================
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatDate(isoString) {
            const date = new Date(isoString);
            const now = new Date();
            const diff = now - date;
            const minutes = Math.floor(diff / 60000);
            const hours = Math.floor(diff / 3600000);
            const days = Math.floor(diff / 86400000);

            if (minutes < 1) return 'Ahora';
            if (minutes < 60) return `Hace ${minutes}m`;
            if (hours < 24) return `Hace ${hours}h`;
            if (days < 7) return `Hace ${days}d`;
            
            return date.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' });
        }

        // ====================================
        // FUNCIONES DE EVENTOS
        // ====================================
        const toggleTodo = (id) => {
            useTodoStore.getState().toggleTodo(id);
        };

        const deleteTodo = (id) => {
            if (confirm('¿Estás seguro de eliminar esta tarea?')) {
                useTodoStore.getState().deleteTodo(id);
            }
        };

        const setFilter = (filter, event) => {
            useTodoStore.getState().setFilter(filter);
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            if (event && event.target) {
                event.target.classList.add('active');
            }
        };

        const clearCompleted = () => {
            const stats = useTodoStore.getState().getStats();
            if (stats.completed > 0) {
                if (confirm(`¿Eliminar ${stats.completed} tarea(s) completada(s)?`)) {
                    useTodoStore.getState().clearCompleted();
                }
            }
        };

        // ====================================
        // RENDERIZADO CON SERJS
        // ====================================
        function renderTodoList() {
            const state = useTodoStore.getState();
            const filteredTodos = state.getFilteredTodos();
            const stats = state.getStats();
            
            // Actualizar estadísticas usando setText de SerJS
            setText(totalCountRef, stats.total);
            setText(activeCountRef, stats.active);
            setText(completedCountRef, stats.completed);
            
            // Renderizar lista de tareas
            if (filteredTodos.length === 0) {
                if (todoListRef.current) todoListRef.current.style.display = 'none';
                if (emptyStateRef.current) emptyStateRef.current.style.display = 'block';
            } else {
                if (todoListRef.current) todoListRef.current.style.display = 'block';
                if (emptyStateRef.current) emptyStateRef.current.style.display = 'none';
                
                const todosHtml = filteredTodos.map(todo => `
                    <li class="todo-item ${todo.completed ? 'completed' : ''}" data-id="${todo.id}">
                        <input 
                            type="checkbox" 
                            class="todo-checkbox"
                            ${todo.completed ? 'checked' : ''}
                        >
                        <span class="todo-text">${escapeHtml(todo.text)}</span>
                        <span class="todo-date">${formatDate(todo.createdAt)}</span>
                        <button class="delete-btn">
                            Eliminar
                        </button>
                    </li>
                `).join('');
                
                setHTML(todoListRef, todosHtml);
            }
            
            // Debug panel
            const debugData = {
                filter: state.filter,
                totalTodos: state.todos.length,
                todos: state.todos.map(t => ({
                    id: t.id,
                    text: t.text.substring(0, 30) + (t.text.length > 30 ? '...' : ''),
                    completed: t.completed
                }))
            };
            setText(debugOutputRef, JSON.stringify(debugData, null, 2));
        }

        // ====================================
        // EFECTOS REACTIVOS CON SERJS
        // ====================================
        
        // Renderizar cuando cambia el trigger
        useEffect(() => {
            renderTodoList();
        }, [renderTrigger]);

        // ====================================
        // EVENTOS CON SERJS
        // ====================================
        
        // Botón agregar
        addBtnRef.onClick(() => {
            if (todoInputRef.current) {
                const text = todoInputRef.current.value;
                if (text.trim()) {
                    useTodoStore.getState().addTodo(text);
                    todoInputRef.current.value = '';
                    todoInputRef.current.focus();
                }
            }
        });

        // Enter para agregar
        todoInputRef.on('keypress', (e) => {
            if (e.key === 'Enter' && todoInputRef.current) {
                const text = todoInputRef.current.value;
                if (text.trim()) {
                    useTodoStore.getState().addTodo(text);
                    todoInputRef.current.value = '';
                    todoInputRef.current.focus();
                }
            }
        });

        // Eventos de botones estáticos
        filterAllRef.onClick((e) => setFilter('all', e));
        filterActiveRef.onClick((e) => setFilter('active', e));
        filterCompletedRef.onClick((e) => setFilter('completed', e));
        clearBtnRef.onClick(clearCompleted);

        // Delegación de eventos para la lista de tareas
        todoListRef.onClick((e) => {
            // Manejar click en checkbox
            if (e.target.classList.contains('todo-checkbox')) {
                const todoItem = e.target.closest('.todo-item');
                if (todoItem) {
                    const id = parseInt(todoItem.dataset.id);
                    toggleTodo(id);
                }
            }
            
            // Manejar click en botón eliminar
            if (e.target.classList.contains('delete-btn') || e.target.closest('.delete-btn')) {
                const todoItem = e.target.closest('.todo-item');
                if (todoItem) {
                    const id = parseInt(todoItem.dataset.id);
                    deleteTodo(id);
                }
            }
        });

        // ====================================
        // SUSCRIPCIÓN AL STORE
        // ====================================
        useTodoStore.subscribe(() => {
            setRenderTrigger(prev => prev + 1);
        });

        // ====================================
        // INICIALIZACIÓN
        // ====================================
        useEffect(() => {
            renderTodoList();
            if (todoInputRef.current) {
                todoInputRef.current.focus();
            }
            console.log('✅ SerJS + SerJSStore inicializados');
            console.log('📦 Store:', useTodoStore.getState());
        }, []);
    </script>
</body>
</html>