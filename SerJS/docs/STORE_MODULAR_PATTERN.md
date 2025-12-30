# 🎯 Patrón: Store Modular con importModule

## 📋 Concepto

Separar la configuración del store (`state` y `acciones`) en un archivo independiente, mantiendo toda la lógica de UI en el componente principal.

## 🏗️ Estructura

```
proyecto/
├── store/
│   └── todo.js          ← Solo configuración del store
└── app/
    └── test-store/
        └── page.php     ← UI + lógica de renderizado
```

## 📦 Archivo del Store (`store/todo.js`)

```javascript
/**
 * Solo la configuración del store
 * NO exportamos funciones, exponemos al scope global
 */

// Configuración del store (lo que va dentro de store.create)
const todoStoreConfig = (set, get) => ({
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
    
    // ... más acciones
});

// Opciones del store
const todoStoreOptions = {
    name: 'todo-list-app',
    persist: true,
    version: 1
};

// ⚠️ IMPORTANTE: Exponer al scope global
window.todoStoreConfig = todoStoreConfig;
window.todoStoreOptions = todoStoreOptions;
```

## 🎨 Archivo del Componente (`app/test-store/page.php`)

```javascript
// ====================================
// 1. IMPORTAR DEPENDENCIAS
// ====================================
const { useRef, useState, useEffect, setText, setHTML, store, importModule } = SerJS;

// ====================================
// 2. IMPORTAR CONFIGURACIÓN DEL STORE
// ====================================
await importModule('../../store/todo.js');

// Obtener desde window
const todoStoreConfig = window.todoStoreConfig;
const todoStoreOptions = window.todoStoreOptions;

// ====================================
// 3. CREAR EL STORE
// ====================================
const useTodoStore = await store.create(todoStoreConfig, todoStoreOptions);

// ====================================
// 4. TODO LO DEMÁS SE QUEDA IGUAL
// ====================================
// - Referencias con useRef
// - Funciones de utilidad (escapeHtml, formatDate)
// - Funciones de eventos (toggleTodo, deleteTodo)
// - Función de renderizado (renderTodoList)
// - Event handlers
// - Suscripción al store
// - Inicialización
```

## ✅ Ventajas de este Patrón

### 1. **Separación de Responsabilidades**
```javascript
// store/todo.js  → Solo lógica de estado
// page.php       → Solo lógica de UI
```

### 2. **Reutilización**
```javascript
// Puedes usar el mismo store en múltiples páginas
await importModule('../../store/todo.js');
const useTodoStore = await store.create(
    window.todoStoreConfig, 
    window.todoStoreOptions
);
```

### 3. **Fácil Testing**
```javascript
// Puedes probar el store independientemente
await importModule('./store/todo.js');
const testStore = await store.create(window.todoStoreConfig);
testStore.getState().addTodo('Test');
```

### 4. **Modularidad**
```javascript
// Múltiples stores en diferentes archivos
await importModule('../../store/todo.js');
await importModule('../../store/user.js');
await importModule('../../store/settings.js');
```

## 🔄 Flujo de Funcionamiento

```
┌─────────────────────────────────────────┐
│ 1. page.php inicia                      │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│ 2. importModule('../../store/todo.js')  │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│ 3. todo.js expone a window:             │
│    - window.todoStoreConfig             │
│    - window.todoStoreOptions            │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│ 4. page.php obtiene config de window    │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│ 5. store.create(config, options)        │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│ 6. useTodoStore está listo para usar    │
└─────────────────────────────────────────┘
```

## 📝 Ejemplo Completo de Uso

### Paso 1: Crear el store (`store/user.js`)

```javascript
const userStoreConfig = (set, get) => ({
    user: null,
    isAuthenticated: false,
    
    login: (userData) => {
        set({ 
            user: userData, 
            isAuthenticated: true 
        });
    },
    
    logout: () => {
        set({ 
            user: null, 
            isAuthenticated: false 
        });
    }
});

const userStoreOptions = {
    name: 'user-store',
    persist: true
};

window.userStoreConfig = userStoreConfig;
window.userStoreOptions = userStoreOptions;
```

### Paso 2: Usar en el componente

```javascript
const { store, importModule } = SerJS;

// Importar configuración
await importModule('../../store/user.js');

// Crear store
const useUserStore = await store.create(
    window.userStoreConfig, 
    window.userStoreOptions
);

// Usar
useUserStore.getState().login({ 
    name: 'Juan', 
    email: 'juan@example.com' 
});

console.log(useUserStore.getState().isAuthenticated); // true
```

## ⚠️ Puntos Importantes

### 1. **NO usar `export`**
```javascript
// ❌ Incorrecto
export const todoStoreConfig = (set, get) => ({ ... });

// ✅ Correcto
const todoStoreConfig = (set, get) => ({ ... });
window.todoStoreConfig = todoStoreConfig;
```

### 2. **Siempre usar `await`**
```javascript
// ✅ Correcto
await importModule('../../store/todo.js');

// ❌ Incorrecto (no funcionará)
importModule('../../store/todo.js');
```

### 3. **Obtener desde `window`**
```javascript
// ✅ Correcto
await importModule('../../store/todo.js');
const config = window.todoStoreConfig;

// ❌ Incorrecto (no funcionará con este patrón)
const { todoStoreConfig } = await importModule('../../store/todo.js');
```

## 🎯 Casos de Uso

### Caso 1: Store Simple
```javascript
// store/counter.js
window.counterConfig = (set, get) => ({
    count: 0,
    increment: () => set({ count: get().count + 1 }),
    decrement: () => set({ count: get().count - 1 })
});

window.counterOptions = { name: 'counter' };
```

### Caso 2: Store con Selectores
```javascript
// store/cart.js
window.cartConfig = (set, get) => ({
    items: [],
    
    addItem: (item) => {
        set({ items: [...get().items, item] });
    },
    
    // Selector
    getTotal: () => {
        return get().items.reduce((sum, item) => sum + item.price, 0);
    }
});
```

### Caso 3: Múltiples Stores
```javascript
// En page.php
await importModule('../../store/todo.js');
await importModule('../../store/user.js');
await importModule('../../store/settings.js');

const useTodoStore = await store.create(
    window.todoStoreConfig, 
    window.todoStoreOptions
);

const useUserStore = await store.create(
    window.userStoreConfig, 
    window.userStoreOptions
);

const useSettingsStore = await store.create(
    window.settingsConfig, 
    window.settingsOptions
);
```

## 📊 Comparación

| Aspecto | Sin Separación | Con Separación |
|---------|---------------|----------------|
| **Archivo** | Todo en page.php | Store en archivo separado |
| **Líneas** | 500+ líneas | page.php: 400, store: 100 |
| **Reutilización** | Difícil | Fácil |
| **Mantenimiento** | Complejo | Simple |
| **Testing** | Difícil | Fácil |

## 🚀 Resumen

1. **Store** (`store/todo.js`):
   - Solo configuración: `(set, get) => ({ ... })`
   - Exponer a `window`
   - NO usar `export`

2. **Componente** (`page.php`):
   - Importar con `importModule`
   - Obtener de `window`
   - Crear store con `store.create`
   - Mantener toda la lógica UI

3. **Beneficios**:
   - Código más limpio
   - Mejor organización
   - Fácil reutilización
   - Testing simplificado

---

**¡Patrón implementado y listo para usar! 🎉**
