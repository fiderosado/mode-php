/**
 * Todo Store - Configuración del store de tareas
 * Este archivo contiene solo la lógica del store (state y acciones)
 */

const { store } = SerJS;

(function (window) {
    'use strict';
    window.useTodoStore = store.create((set, get) => ({
        // Estado inicial
        todos: [],
        filter: 'all',

        // Acciones
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

        // Selectores
        getFilteredTodos: () => {
            const { todos, filter } = get();
            switch (filter) {
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
    }).then(instance => {
        window.useTodoStore = instance;
        return instance;
    });

})(window);
