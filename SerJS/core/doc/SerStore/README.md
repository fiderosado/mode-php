# SerJSStore - Gestión de Estado Persistente

Sistema de gestión de estado persistente inspirado en Zustand, con almacenamiento automático en localStorage y API simple y reactiva.

## Características

- ✅ API simple similar a Zustand
- ✅ Persistencia automática en localStorage
- ✅ Sistema de suscripciones reactivo
- ✅ Soporte para múltiples adaptadores de storage
- ✅ Middleware incorporados (devtools, temporal, immer)
- ✅ Control de versiones con migración
- ✅ Undo/Redo integrado
- ✅ TypeScript-friendly (aunque escrito en JS puro)

## Instalación

```html
<script src="SerJS/core/SerJSStore.js"></script>
```

## Uso Básico

### Crear un Store Simple

```javascript
// Store básico sin persistencia
const useCounterStore = SerJSStore.create({
    count: 0,
    increment: function() {
        this.setState({ count: this.getState().count + 1 });
    },
    decrement: function() {
        this.setState({ count: this.getState().count - 1 });
    }
}, { 
    name: 'counter',
    persist: false // Sin persistencia
});

// Usar el store
const state = useCounterStore.getState();
console.log(state.count); // 0

state.increment();
console.log(useCounterStore.getState().count); // 1
```

### Store con Función de Inicialización

```javascript
const useUSerJSStore = SerJSStore.create((set, get, api) => ({
    user: null,
    token: null,
    
    login: (username, password) => {
        // Simular login
        set({
            user: { name: username, id: 1 },
            token: 'abc123'
        });
    },
    
    logout: () => {
        set({ user: null, token: null });
    },
    
    updateProfile: (data) => {
        const currentUser = get().user;
        set({
            user: { ...currentUser, ...data }
        });
    }
}), {
    name: 'user-session',
    persist: true // Se guardará en localStorage
});
```

## Persistencia

### Persistencia con localStorage (por defecto)

```javascript
const useSettingsStore = SerJSStore.create({
    theme: 'light',
    language: 'es',
    notifications: true,
    
    setTheme: function(theme) {
        this.setState({ theme });
    },
    
    toggleNotifications: function() {
        this.setState({ notifications: !this.getState().notifications });
    }
}, {
    name: 'app-settings',
    persist: true, // true por defecto
    storage: SerJSStore.storage.localStorage // Por defecto
});

// El estado se guarda automáticamente en cada cambio
useSettingsStore.getState().setTheme('dark');
// Recarga la página y el theme seguirá siendo 'dark'
```

### Persistencia con sessionStorage

```javascript
const useTemporaryStore = SerJSStore.create({
    tempData: null
}, {
    name: 'temp-session',
    persist: true,
    storage: SerJSStore.storage.sessionStorage
});
```

### Sin Persistencia (Solo Memoria)

```javascript
const useRuntimeStore = SerJSStore.create({
    cache: {}
}, {
    name: 'runtime-cache',
    persist: false,
    storage: SerJSStore.storage.memory
});
```

## Persistencia Parcial

Puedes elegir qué partes del estado persistir:

```javascript
const useAppStore = SerJSStore.create({
    user: { name: 'John', email: 'john@example.com' },
    sessionData: { timestamp: Date.now() },
    uiState: { sidebarOpen: true }
}, {
    name: 'app-store',
    persist: true,
    // Solo persistir user y uiState, excluir sessionData
    partialize: (state) => ({
        user: state.user,
        uiState: state.uiState
    })
});
```

## Suscripciones

```javascript
const useCartStore = SerJSStore.create({
    items: [],
    total: 0,
    
    addItem: function(item) {
        const state = this.getState();
        this.setState({
            items: [...state.items, item],
            total: state.total + item.price
        });
    },
    
    removeItem: function(itemId) {
        const state = this.getState();
        const items = state.items.filter(i => i.id !== itemId);
        const total = items.reduce((sum, i) => sum + i.price, 0);
        this.setState({ items, total });
    }
}, {
    name: 'shopping-cart',
    persist: true
});

// Suscribirse a cambios
const unsubscribe = useCartStore.subscribe((state, previousState) => {
    console.log('Estado anterior:', previousState);
    console.log('Estado nuevo:', state);
    
    // Actualizar UI
    document.getElementById('cart-count').textContent = state.items.length;
    document.getElementById('cart-total').textContent = `$${state.total}`;
});

// Cancelar suscripción
// unsubscribe();
```

## Control de Versiones y Migración

```javascript
const useDataStore = SerJSStore.create({
    data: [],
    version: 2
}, {
    name: 'my-data',
    persist: true,
    version: 2,
    
    // Migrar datos de versión anterior
    migrate: (persistedState) => {
        // Si el estado guardado es de la versión 1
        if (!persistedState.version || persistedState.version === 1) {
            return {
                data: persistedState.items || [], // Cambio de estructura
                version: 2
            };
        }
        return persistedState;
    }
});
```

## Métodos del Store

### getState()
Obtiene el estado actual del store:

```javascript
const state = useUSerJSStore.getState();
console.log(state.user);
```

### setState(partial, replace)
Actualiza el estado (merge por defecto):

```javascript
// Merge (por defecto)
useUSerJSStore.setState({ user: { name: 'Jane' } });

// Replace completo
useUSerJSStore.setState({ user: { name: 'Jane' } }, true);

// Con función
useUSerJSStore.setState((state) => ({
    count: state.count + 1
}));
```

### subscribe(listener)
Suscribirse a cambios de estado:

```javascript
const unsubscribe = useUSerJSStore.subscribe((newState, prevState) => {
    console.log('Changed!', newState);
});
```

### reset()
Resetear al estado inicial:

```javascript
useUSerJSStore.reset();
```

### destroy()
Destruir el store y limpiar persistencia:

```javascript
useUSerJSStore.destroy();
```

### persist()
Forzar guardado inmediato:

```javascript
useUSerJSStore.persist();
```

### rehydrate()
Recargar desde storage:

```javascript
useUSerJSStore.rehydrate();
```

## Middleware

### Devtools (Logging)

```javascript
const useStore = SerJSStore.create(
    SerJSStore.middleware.devtools(
        (set, get, api) => ({
            count: 0,
            increment: () => set({ count: get().count + 1 })
        }),
        { name: 'Counter Store', enabled: true }
    ),
    { name: 'counter' }
);

// Cada cambio se logueará en consola con formato agrupado
```

### Temporal (Undo/Redo)

```javascript
const useEditorStore = SerJSStore.create(
    SerJSStore.middleware.temporal(
        (set, get, api) => ({
            content: '',
            
            updateContent: (newContent) => {
                set({ content: newContent });
            }
        }),
        { limit: 20 } // Máximo 20 estados en historial
    ),
    { name: 'editor' }
);

// Usar undo/redo
const state = useEditorStore.getState();
state.updateContent('Hola mundo');
state.updateContent('Hola SerJSStore');

state.undo(); // Vuelve a 'Hola mundo'
state.redo(); // Vuelve a 'Hola SerJSStore'

console.log(state.canUndo()); // true/false
console.log(state.canRedo()); // true/false
state.clearHistory(); // Limpiar historial
```

### Immer (Actualizaciones Inmutables)

```javascript
const useListStore = SerJSStore.create(
    SerJSStore.middleware.immer((set, get, api) => ({
        todos: [],
        
        addTodo: (todo) => {
            set((state) => {
                state.todos.push(todo);
                return state;
            });
        },
        
        toggleTodo: (id) => {
            set((state) => {
                const todo = state.todos.find(t => t.id === id);
                if (todo) {
                    todo.completed = !todo.completed;
                }
                return state;
            });
        }
    })),
    { name: 'todos' }
);
```

## Utilidades

### getStore(name)
Obtener un store por nombre:

```javascript
const store = SerJSStore.getStore('user-session');
if (store) {
    console.log(store.getState());
}
```

### getAllStores()
Listar todos los stores registrados:

```javascript
const storeNames = SerJSStore.getAllStores();
console.log('Stores activos:', storeNames);
```

### destroyAll()
Destruir todos los stores:

```javascript
SerJSStore.destroyAll();
```

### shallow(objA, objB)
Comparación superficial para optimización:

```javascript
const isEqual = SerJSStore.shallow(
    { a: 1, b: 2 },
    { a: 1, b: 2 }
);
console.log(isEqual); // true
```

### combine(...stores)
Combinar múltiples stores:

```javascript
const combined = SerJSStore.combine(
    { name: 'user', store: useUSerJSStore },
    { name: 'settings', store: useSettingsStore }
);

console.log(combined.state.user);
console.log(combined.state.settings);
```

## Ejemplos Completos

### Todo List con Persistencia

```javascript
const useTodoStore = SerJSStore.create((set, get) => ({
    todos: [],
    filter: 'all', // 'all', 'active', 'completed'
    
    addTodo: (text) => {
        const newTodo = {
            id: Date.now(),
            text,
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
        if (filter === 'active') return todos.filter(t => !t.completed);
        if (filter === 'completed') return todos.filter(t => t.completed);
        return todos;
    },
    
    clearCompleted: () => {
        set({ todos: get().todos.filter(t => !t.completed) });
    }
}), {
    name: 'todo-list',
    persist: true
});

// Usar en la aplicación
useTodoStore.subscribe((state) => {
    renderTodos(state.todos);
});

function renderTodos(todos) {
    const list = document.getElementById('todo-list');
    list.innerHTML = todos.map(todo => `
        <li>
            <input type="checkbox" 
                   ${todo.completed ? 'checked' : ''} 
                   onchange="useTodoStore.getState().toggleTodo(${todo.id})">
            <span>${todo.text}</span>
            <button onclick="useTodoStore.getState().deleteTodo(${todo.id})">×</button>
        </li>
    `).join('');
}
```

### Carrito de Compras

```javascript
const useCartStore = SerJSStore.create((set, get) => ({
    items: [],
    
    addToCart: (product, quantity = 1) => {
        const { items } = get();
        const existingItem = items.find(i => i.id === product.id);
        
        if (existingItem) {
            set({
                items: items.map(i =>
                    i.id === product.id
                        ? { ...i, quantity: i.quantity + quantity }
                        : i
                )
            });
        } else {
            set({
                items: [...items, { ...product, quantity }]
            });
        }
    },
    
    removeFromCart: (productId) => {
        set({
            items: get().items.filter(i => i.id !== productId)
        });
    },
    
    updateQuantity: (productId, quantity) => {
        if (quantity <= 0) {
            get().removeFromCart(productId);
        } else {
            set({
                items: get().items.map(i =>
                    i.id === productId ? { ...i, quantity } : i
                )
            });
        }
    },
    
    getTotal: () => {
        return get().items.reduce((total, item) => {
            return total + (item.price * item.quantity);
        }, 0);
    },
    
    getItemCount: () => {
        return get().items.reduce((count, item) => count + item.quantity, 0);
    },
    
    clearCart: () => {
        set({ items: [] });
    }
}), {
    name: 'shopping-cart',
    persist: true,
    version: 1
});

// Actualizar UI automáticamente
useCartStore.subscribe((state) => {
    document.getElementById('cart-count').textContent = state.getItemCount();
    document.getElementById('cart-total').textContent = `$${state.getTotal().toFixed(2)}`;
});
```

### Autenticación de Usuario

```javascript
const useAuthStore = SerJSStore.create((set, get) => ({
    user: null,
    token: null,
    isAuthenticated: false,
    isLoading: false,
    error: null,
    
    login: async (credentials) => {
        set({ isLoading: true, error: null });
        
        try {
            const response = await fetch('/api/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(credentials)
            });
            
            const data = await response.json();
            
            if (response.ok) {
                set({
                    user: data.user,
                    token: data.token,
                    isAuthenticated: true,
                    isLoading: false
                });
            } else {
                set({
                    error: data.message,
                    isLoading: false
                });
            }
        } catch (error) {
            set({
                error: 'Error de conexión',
                isLoading: false
            });
        }
    },
    
    logout: () => {
        set({
            user: null,
            token: null,
            isAuthenticated: false,
            error: null
        });
    },
    
    updateUser: (userData) => {
        set({ user: { ...get().user, ...userData } });
    },
    
    checkAuth: () => {
        const { token } = get();
        return !!token;
    }
}), {
    name: 'auth',
    persist: true,
    // Solo persistir user y token, no isLoading ni error
    partialize: (state) => ({
        user: state.user,
        token: state.token,
        isAuthenticated: state.isAuthenticated
    })
});
```

### Configuración de Aplicación

```javascript
const useConfigStore = SerJSStore.create((set, get) => ({
    theme: 'light',
    language: 'es',
    fontSize: 'medium',
    notifications: {
        email: true,
        push: true,
        sound: false
    },
    
    setTheme: (theme) => set({ theme }),
    
    setLanguage: (language) => set({ language }),
    
    setFontSize: (fontSize) => set({ fontSize }),
    
    updateNotifications: (notificationSettings) => {
        set({
            notifications: { ...get().notifications, ...notificationSettings }
        });
    },
    
    resetToDefaults: () => {
        set({
            theme: 'light',
            language: 'es',
            fontSize: 'medium',
            notifications: { email: true, push: true, sound: false }
        });
    }
}), {
    name: 'app-config',
    persist: true,
    version: 1
});

// Aplicar configuración al cargar
const applyConfig = (state) => {
    document.documentElement.setAttribute('data-theme', state.theme);
    document.documentElement.setAttribute('data-font-size', state.fontSize);
    document.documentElement.lang = state.language;
};

useConfigStore.subscribe(applyConfig);
applyConfig(useConfigStore.getState());
```

## Mejores Prácticas

### 1. Nombra tus stores

```javascript
// ✅ Bueno
SerJSStore.create(state, { name: 'user-profile' });

// ❌ Evitar (se generará nombre automático)
SerJSStore.create(state);
```

### 2. Usa funciones para inicialización compleja

```javascript
// ✅ Bueno - acceso a set, get, api
SerJSStore.create((set, get, api) => ({
    count: 0,
    increment: () => set({ count: get().count + 1 })
}));

// ⚠️ Limitado - no tienes acceso a set/get
SerJSStore.create({ count: 0 });
```

### 3. Persistencia parcial para datos sensibles

```javascript
// ✅ Bueno - excluir datos temporales
SerJSStore.create(state, {
    name: 'app',
    partialize: (state) => ({
        user: state.user,
        preferences: state.preferences
        // No persistir sessionData, cache, etc.
    })
});
```

### 4. Usa suscripciones con cleanup

```javascript
// ✅ Bueno
const unsubscribe = store.subscribe(listener);
// ... más tarde
unsubscribe();

// ❌ Evitar - memory leak
store.subscribe(listener);
// Nunca se cancela
```

### 5. Control de versiones para cambios de estructura

```javascript
// ✅ Bueno - migración explícita
SerJSStore.create(state, {
    name: 'data',
    version: 2,
    migrate: (oldState) => {
        if (oldState.version === 1) {
            return { newStructure: transformOldData(oldState) };
        }
        return oldState;
    }
});
```

## API Completa

```javascript
// Crear store
const store = SerJSStore.create(initialState, options);

// Métodos del store
store.getState()
store.setState(partial, replace?)
store.subscribe(listener)
store.destroy()
store.reset()
store.getInitialState()
store.persist()
store.rehydrate()

// API global
SerJSStore.create(state, options)
SerJSStore.useStore(store, selector?, equalityFn?)
SerJSStore.createHook(store)
SerJSStore.getStore(name)
SerJSStore.getAllStores()
SerJSStore.destroyAll()
SerJSStore.shallow(objA, objB)
SerJSStore.combine(...stores)

// Middleware
SerJSStore.middleware.devtools(config, options)
SerJSStore.middleware.immer(config)
SerJSStore.middleware.temporal(config, options)

// Storage adapters
SerJSStore.storage.localStorage
SerJSStore.storage.sessionStorage
SerJSStore.storage.memory
```

## Licencia

Parte del proyecto SerJS - mode-php framework
