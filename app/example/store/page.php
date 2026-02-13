<?php
// crear un metodo para llamar desde la ruta actual e imortar los ficheros desde aca
$baseUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test SerJSStore - Lista de Tareas</title>

    <?php if (isset($GLOBALS['css']) && $GLOBALS['css']): ?>
        <link rel="stylesheet" href="<?php echo htmlspecialchars($GLOBALS['css']); ?>">
    <?php endif; ?>

    <!-- Incluir SerJS -->
    <script src="<?php echo $baseUrl; ?>/SerJS/SerJS.js"></script>
</head>

<body>
    <div class="container mx-auto max-w-md">
        <h1>📝 Lista de Tareas</h1>
        <p class="subtitle">Ejemplo de SerJSStore con persistencia + importModule</p>

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
                autocomplete="off">
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
            Powered by SerJS + SerJSStore + importModule • Los datos se guardan automáticamente
        </div>
    </div>

    <script type="module">
        // ====================================
        // DESTRUCTURAR SERJS
        // ====================================
        const {
            useRef,
            useState,
            useEffect,
            setText,
            setHTML,
            importModule
        } = SerJS;

        // ====================================
        // IMPORTAR EL STORE
        // ====================================

        const {
            subscribe,
            todos,
            addTodo,
            toggleTodo,
            deleteTodo,
            clearCompleted,
            getFilteredTodos,
            getStats,
            setFilter,
            filter
        } = await importModule('useTodoStore', `/store/todo.js`);

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

            return date.toLocaleDateString('es-ES', {
                day: 'numeric',
                month: 'short'
            });
        }

        // ====================================
        // FUNCIONES DE EVENTOS
        // ====================================


        function deleteTodoAction(id) {
            if (confirm('¿Estás seguro de eliminar esta tarea?')) {
                deleteTodo(id);
            }
        };

        function setFilterAction(filter, event) {
            setFilter(filter);
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            if (event && event.target) {
                event.target.classList.add('active');
            }
        };

        function clearCompletedAction() {
            const stats = getStats();
            if (stats.completed > 0) {
                if (confirm(`¿Eliminar ${stats.completed} tarea(s) completada(s)?`)) {
                    clearCompleted();
                }
            }
        };

        // ====================================
        // RENDERIZADO CON SERJS
        // ====================================
        function renderTodoList() {

            const filteredTodos = getFilteredTodos();
            const stats = getStats();

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
                filter: filter,
                totalTodos: todos.length,
                todos: todos.map(t => ({
                    id: t.id,
                    text: t.text.substring(0, 30) + (t.text.length > 30 ? '...' : ''),
                    completed: t.completed
                }))
            };
            setText(debugOutputRef, JSON.stringify(debugData, null, 2));
        }

        // ====================================
        // EVENTOS CON SERJS
        // ====================================

        // Botón agregar
        addBtnRef.onClick(() => {
            if (todoInputRef.current) {
                const text = todoInputRef.current.value;
                if (text.trim()) {
                    addTodo(text);
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
                    addTodo(text);
                    todoInputRef.current.value = '';
                    todoInputRef.current.focus();
                }
            }
        });

        // Eventos de botones estáticos
        filterAllRef.onClick((e) => setFilterAction('all', e));
        filterActiveRef.onClick((e) => setFilterAction('active', e));
        filterCompletedRef.onClick((e) => setFilterAction('completed', e));
        clearBtnRef.onClick(clearCompletedAction);

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
                    deleteTodoAction(id);
                }
            }
        });

        // ====================================
        // INICIALIZACIÓN
        // ====================================
        useEffect(() => {
            renderTodoList();
            if (todoInputRef.current) {
                todoInputRef.current.focus();
            }
        }, []);

        useEffect(() => {
            console.log("todos store:", todos.current);
            renderTodoList()
        }, [todos, filter]);
    </script>
</body>

</html>
