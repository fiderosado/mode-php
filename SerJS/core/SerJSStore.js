/**
 * SerJS Store Module v1.0
 * Sistema de gestión de estado persistente inspirado en Zustand
 * Almacenamiento automático en localStorage con API simple y reactiva
 */

(function (window, document) {

    'use strict';

    let isReady = false;

    document.addEventListener('DOMContentLoaded', () => {
        isReady = true;
    });

    // ====================================
    // STORES REGISTRY
    // ====================================

    const storesRegistry = new Map();

    // ====================================
    // STORE CREATOR
    // ====================================

    function create(createState, options = {}) {

        const {
            name = `store_${Date.now()}`,
            persist = true,
            storage = window.localStorage,
            version = 1,
            migrate = (state) => state,
            partialize = (state) => state,
            merge = (persistedState, currentState) => ({ ...currentState, ...persistedState })
        } = options;

        // Definir estado (inicialmente undefined)
        let state;
        const listeners = new Set();

        // ====================================
        // API CORE METHODS
        // ====================================

        function get() {
            return state;
        }

        function set(partial, replace = false) {
            const nextState = typeof partial === 'function'
                ? partial(state)
                : partial;

            if (!Object.is(nextState, state)) {
                const previousState = state;
                state = replace ? nextState : { ...state, ...nextState };

                // Persistir si está habilitado
                if (persist && storage) {
                    try {
                        const dataToPersist = {
                            state: partialize(state),
                            version
                        };
                        storage.setItem(name, JSON.stringify(dataToPersist));
                    } catch (error) {
                        console.error(`[SerJSStore] Error persisting state for "${name}":`, error);
                    }
                }

                // Notificar a los listeners
                listeners.forEach(listener => {
                    listener(state, previousState);
                });
            }
        }

        function subscribeStore(listener) {
            listeners.add(listener);

            return () => {
                listeners.delete(listener);
            };
        }

        function destroy() {
            listeners.clear();
            if (persist && storage) {
                try {
                    storage.removeItem(name);
                } catch (error) {
                    console.error(`[SerJSStore] Error removing persisted state for "${name}":`, error);
                }
            }
            storesRegistry.delete(name);
        }

        // ====================================
        // ADDITIONAL METHODS
        // ====================================

        function reset() {
            const initialState = typeof createState === 'function'
                ? createState(set, get, api)
                : createState;
            set(initialState, true);
        }

        function getInitialState() {
            return typeof createState === 'function'
                ? createState(set, get, api)
                : createState;
        }

        function persistStore() {
            if (storage) {
                try {
                    const dataToPersist = {
                        state: partialize(state),
                        version
                    };
                    storage.setItem(name, JSON.stringify(dataToPersist));
                } catch (error) {
                    console.error(`[SerJSStore] Error persisting state for "${name}":`, error);
                }
            }
        }

        function rehydrate() {
            if (storage) {
                try {
                    const persistedData = storage.getItem(name);
                    if (persistedData) {
                        const parsed = JSON.parse(persistedData);
                        const rehydratedState = parsed.state || parsed;
                        set(rehydratedState, false);
                    }
                } catch (error) {
                    console.error(`[SerJSStore] Error rehydrating state for "${name}":`, error);
                }
            }
        }

        // ====================================
        // API OBJECT
        // ====================================

        const api = {
            getState: get,
            setState: set,
            subscribe: subscribeStore,
            destroy,
            reset,
            getInitialState,
            persist: persistStore,
            rehydrate
        };

        // ====================================
        // INITIALIZATION
        // ====================================

        // Inicializar estado
        state = typeof createState === 'function'
            ? createState(set, get, api)
            : createState;

        // Cargar estado persistido si existe
        if (persist && storage) {
            try {
                const persistedData = storage.getItem(name);
                if (persistedData) {
                    const parsed = JSON.parse(persistedData);

                    // Verificar versión y migrar si es necesario
                    if (parsed.version !== version) {
                        state = migrate(parsed.state || parsed);
                    } else {
                        state = merge(parsed.state || parsed, state);
                    }
                }
            } catch (error) {
                console.error(`[SerJSStore] Error loading persisted state for "${name}":`, error);
            }
        }

        // ====================================
        // PROXY PARA API STATE-FIRST
        // ====================================
        // El Proxy permite acceder directamente a métodos y propiedades del estado
        // sin tener que usar getState(), mientras mantiene los métodos administrativos
        // Las propiedades del estado se convierten en objetos reactivos compatibles con useEffect

        // Cache de objetos reactivos por propiedad
        const reactiveProps = new Map();

        // Función para crear un objeto reactivo para una propiedad del estado
        function createReactiveProp(propName) {

            if (reactiveProps.has(propName)) {
                return reactiveProps.get(propName);
            }

            const subscribers = new Set();

            // Función helper para obtener el valor actual
            function getCurrentValue() {
                return state && typeof state === 'object' && propName in state
                    ? state[propName]
                    : undefined;
            }

            // Objeto base con métodos especiales
            const reactiveBase = {
                get current() {
                    return getCurrentValue();
                },

                subscribe(callback) {
                    subscribers.add(callback);
                    // También suscribirse a cambios del store completo
                    const storeUnsub = subscribeStore((newState, previousState) => {
                        // Obtener el valor anterior y nuevo de esta propiedad
                        const prevValue = previousState && typeof previousState === 'object' && propName in previousState
                            ? previousState[propName]
                            : undefined;
                        const newValue = newState && typeof newState === 'object' && propName in newState
                            ? newState[propName]
                            : undefined;

                        // Notificar solo si esta propiedad específica cambió
                        if (!Object.is(prevValue, newValue)) {
                            try {
                                callback(newValue);
                            } catch (error) {
                                console.error(`[SerJSStore] Error en callback de suscripción para "${propName}":`, error);
                            }
                        }
                    });
                    return () => {
                        subscribers.delete(callback);
                        if (storeUnsub && typeof storeUnsub === 'function') {
                            storeUnsub();
                        }
                    };
                },

                toJson() {
                    return getCurrentValue();
                },

                valueOf() {
                    return getCurrentValue();
                },

                toString() {
                    return String(getCurrentValue());
                },

                // ID interno para identificar la propiedad
                propName: propName
            };

            // Crear un Proxy que envuelva el valor actual y delegue todas las operaciones
            const reactiveProxy = new Proxy(reactiveBase, {
                get(target, prop) {

                    if(prop in target){
                        return target[prop];
                    }

                    // Si es una propiedad especial del objeto reactivo, retornarla
                   /*  if (prop === 'current' || prop === 'subscribe' || prop === 'propName' || prop === 'toString') {
                        return target[prop];
                    }
 */
                    // Obtener el valor actual
                    const currentValue = getCurrentValue();

                   // console.log("currentValue:", { target, prop, currentValue })

                    // Si el valor es null o undefined, retornar undefined
                    if (currentValue == null) {
                        return undefined;
                    }

                    // Si es un objeto/array, delegar al valor actual
                    if (typeof currentValue === 'object') {
                        const value = currentValue[prop];
                        // Si es una función, bindearla al contexto del valor original
                        if (typeof value === 'function') {
                            return value.bind(currentValue);
                        }
                        return value;
                    }

                    // Para primitivos, retornar undefined (no tienen propiedades), cambie a currentValue
                    return undefined; 
                },

                set(target, prop, value) {
                    // No permitir modificar directamente, debe hacerse a través del store
                    console.warn(`[SerJSStore] No se puede modificar directamente la propiedad "${propName}". Usa los métodos del store para actualizar el estado.`);
                    return false;
                },

                has(target, prop) {
                    // Verificar si existe en el objeto base o en el valor actual
                    if (prop === 'current' || prop === 'subscribe' || prop === 'propName') {
                        return true;
                    }

                    const currentValue = getCurrentValue();
                    if (currentValue != null && typeof currentValue === 'object') {
                        return prop in currentValue;
                    }

                    return false;
                },

                ownKeys(target) {
                    const currentValue = getCurrentValue();
                    const baseKeys = ['current', 'subscribe', 'propName'];

                    if (currentValue != null && typeof currentValue === 'object') {
                        if (Array.isArray(currentValue)) {
                            // Para arrays, retornar índices y métodos especiales
                            return [...baseKeys, ...Object.keys(currentValue), 'length'];
                        }
                        return [...baseKeys, ...Object.keys(currentValue)];
                    }

                    return baseKeys;
                },

                getOwnPropertyDescriptor(target, prop) {
                    if (prop === 'current' || prop === 'subscribe' || prop === 'propName') {
                        return Object.getOwnPropertyDescriptor(target, prop);
                    }

                    const currentValue = getCurrentValue();
                    if (currentValue != null && typeof currentValue === 'object' && prop in currentValue) {
                        return Object.getOwnPropertyDescriptor(currentValue, prop);
                    }

                    return undefined;
                },

                // Conversión a primitivo - permite usar el objeto directamente como valor
                [Symbol.toPrimitive](hint) {
                    const val = getCurrentValue();
                    if (hint === 'number') return Number(val);
                    if (hint === 'string') return String(val);
                    return val;
                },

                // Para iteración (arrays)
                [Symbol.iterator]() {
                    const currentValue = getCurrentValue();
                    if (Array.isArray(currentValue)) {
                        return currentValue[Symbol.iterator]();
                    }
                    if (currentValue != null && typeof currentValue[Symbol.iterator] === 'function') {
                        return currentValue[Symbol.iterator]();
                    }
                    // Si no es iterable, retornar un iterador vacío
                    return [][Symbol.iterator]();
                },

                // Para JSON.stringify
                toJson() {
                    return getCurrentValue();
                },

                valueOf() {
                    return getCurrentValue();
                },

                toString() {
                    return String(getCurrentValue());
                }
            });

            reactiveProps.set(propName, reactiveProxy);
            return reactiveProxy;
        }

        const storeProxy = new Proxy(api, {
            get(target, prop) {
                // Primero intentar métodos administrativos
                if (prop in target) {
                    const value = target[prop];
                    // Si es una función, bindearla al contexto correcto
                    if (typeof value === 'function') {
                        return value.bind(target);
                    }
                    return value;
                }

                // Si no es un método administrativo, delegar al estado actual
                if (state && typeof state === 'object' && prop in state) {
                    const value = state[prop];

                    // Si es una función del estado, retornarla directamente
                    if (typeof value === 'function') {
                        return value;
                    }

                    // Si es una propiedad del estado, retornar objeto reactivo
                    // Esto permite usar propiedades del store como dependencias en useEffect
                    return createReactiveProp(prop);
                }

                // Si no se encuentra, devolver undefined
                return undefined;
            },

            set(target, prop, value) {
                // Permitir modificar propiedades del estado directamente
                if (state && typeof state === 'object' && prop in state) {
                    set({ [prop]: value });
                    return true;
                }

                // Permitir modificar métodos administrativos (aunque no es recomendado)
                target[prop] = value;
                return true;
            },

            has(target, prop) {
                // Verificar si existe en métodos administrativos o en el estado
                return prop in target || (state && typeof state === 'object' && prop in state);
            },

            ownKeys(target) {
                // Combinar keys de métodos administrativos y del estado
                const adminKeys = Object.keys(target);
                const stateKeys = state && typeof state === 'object' ? Object.keys(state) : [];
                return [...new Set([...adminKeys, ...stateKeys])];
            },

            getOwnPropertyDescriptor(target, prop) {
                // Devolver descriptor de métodos administrativos o del estado
                if (prop in target) {
                    return Object.getOwnPropertyDescriptor(target, prop);
                }
                if (state && typeof state === 'object' && prop in state) {
                    return Object.getOwnPropertyDescriptor(state, prop);
                }
                return undefined;
            }
        });

        // Registrar store (usar el proxy en lugar del api directo)
        storesRegistry.set(name, storeProxy);

        return storeProxy;
    }

    // ====================================
    // ZUSTAND-LIKE HOOKS
    // ====================================

    function useStore(store, selector, equalityFn) {
        const state = store.getState();

        if (selector) {
            return selector(state);
        }

        return state;
    }

    function createHook(store) {
        return function (selector, equalityFn) {
            return useStore(store, selector, equalityFn);
        };
    }

    // ====================================
    // MIDDLEWARE FUNCTIONS
    // ====================================

    const middleware = {
        /**
         * Middleware para logging de cambios de estado
         */
        devtools(config, devtoolsOptions = {}) {
            return (set, get, api) => {
                const { name = 'store', enabled = true } = devtoolsOptions;

                const setState = (partial, replace) => {
                    const prevState = get();
                    set(partial, replace);
                    const nextState = get();

                    if (enabled && console.groupCollapsed) {
                        console.groupCollapsed(`[${name}] State Update`);
                        console.log('Previous:', prevState);
                        console.log('Action:', partial);
                        console.log('Next:', nextState);
                        console.groupEnd();
                    }
                };

                return typeof config === 'function'
                    ? config(setState, get, api)
                    : config;
            };
        },

        /**
         * Middleware para immer (immutable state updates)
         */
        immer(config) {
            return (set, get, api) => {
                const setState = (updater, replace) => {
                    const nextState = typeof updater === 'function'
                        ? updater(get())
                        : updater;
                    set(nextState, replace);
                };

                return typeof config === 'function'
                    ? config(setState, get, api)
                    : config;
            };
        },

        /**
         * Middleware para temporal state (undo/redo)
         */
        temporal(config, temporalOptions = {}) {
            const { limit = 10 } = temporalOptions;
            const past = [];
            const future = [];

            return (set, get, api) => {
                const setState = (partial, replace) => {
                    const currentState = get();
                    past.push(currentState);

                    if (past.length > limit) {
                        past.shift();
                    }

                    future.length = 0;
                    set(partial, replace);
                };

                const undo = () => {
                    if (past.length > 0) {
                        const currentState = get();
                        const previousState = past.pop();
                        future.push(currentState);
                        set(previousState, true);
                    }
                };

                const redo = () => {
                    if (future.length > 0) {
                        const currentState = get();
                        const nextState = future.pop();
                        past.push(currentState);
                        set(nextState, true);
                    }
                };

                const canUndo = () => past.length > 0;
                const canRedo = () => future.length > 0;
                const clearHistory = () => {
                    past.length = 0;
                    future.length = 0;
                };

                const state = typeof config === 'function'
                    ? config(setState, get, api)
                    : config;

                return {
                    ...state,
                    undo,
                    redo,
                    canUndo,
                    canRedo,
                    clearHistory
                };
            };
        }
    };

    // ====================================
    // UTILITIES
    // ====================================

    const utilities = {
        /**
         * Obtiene un store del registry
         */
        getStore(name) {
            return storesRegistry.get(name);
        },

        /**
         * Lista todos los stores registrados
         */
        getAllStores() {
            return Array.from(storesRegistry.keys());
        },

        /**
         * Limpia todos los stores
         */
        destroyAll() {
            storesRegistry.forEach(store => store.destroy());
            storesRegistry.clear();
        },

        /**
         * Selector shallow compare
         */
        shallow(objA, objB) {
            if (Object.is(objA, objB)) {
                return true;
            }

            if (
                typeof objA !== 'object' ||
                objA === null ||
                typeof objB !== 'object' ||
                objB === null
            ) {
                return false;
            }

            const keysA = Object.keys(objA);
            const keysB = Object.keys(objB);

            if (keysA.length !== keysB.length) {
                return false;
            }

            for (let i = 0; i < keysA.length; i++) {
                if (
                    !Object.prototype.hasOwnProperty.call(objB, keysA[i]) ||
                    !Object.is(objA[keysA[i]], objB[keysA[i]])
                ) {
                    return false;
                }
            }

            return true;
        },

        /**
         * Combina múltiples stores
         */
        combine(...stores) {
            const combinedState = {};
            const combinedMethods = {};

            stores.forEach((storeConfig, index) => {
                const storeName = storeConfig.name || `store${index}`;
                const store = storeConfig.store || storeConfig;

                if (typeof store.getState === 'function') {
                    const state = store.getState();

                    // Validar que no existan conflictos de claves de estado entre stores
                    if (combinedState[storeName]) {
                        throw new Error(`[SerJSStore] Conflicto al combinar stores: el nombre "${storeName}" ya existe en el estado combinado.`);
                    }

                    combinedState[storeName] = state;

                    // Validar métodos/propiedades duplicadas entre stores combinados
                    Object.keys(store).forEach((key) => {
                        if (combinedMethods[key] && combinedMethods[key] !== store) {
                            throw new Error(
                                `[SerJSStore] Conflicto al combinar stores: el método/propiedad "${key}" ` +
                                `ya existe en otro store combinado.`
                            );
                        }
                    });

                    combinedMethods[storeName] = store;
                }
            });

            return {
                state: combinedState,
                stores: combinedMethods
            };
        }
    };

    // ====================================
    // STORAGE ADAPTERS
    // ====================================

    const storage = {
        /**
         * Adaptador para localStorage
         */
        localStorage: {
            getItem: (name) => window.localStorage.getItem(name),
            setItem: (name, value) => window.localStorage.setItem(name, value),
            removeItem: (name) => window.localStorage.removeItem(name)
        },

        /**
         * Adaptador para sessionStorage
         */
        sessionStorage: {
            getItem: (name) => window.sessionStorage.getItem(name),
            setItem: (name, value) => window.sessionStorage.setItem(name, value),
            removeItem: (name) => window.sessionStorage.removeItem(name)
        },

        /**
         * Adaptador en memoria (no persistente)
         */
        memory: (() => {
            const store = new Map();
            return {
                getItem: (name) => store.get(name) || null,
                setItem: (name, value) => store.set(name, value),
                removeItem: (name) => store.delete(name)
            };
        })()
    };

    // ====================================
    // PROXY PARA API UNIFICADA
    // ====================================

    const SerJSStoreProxy = new Proxy({}, {
        get(target, prop) {
            // Core API
            if (prop === 'create') {
                return create;
            }

            if (prop === 'useStore') {
                return useStore;
            }

            if (prop === 'createHook') {
                return createHook;
            }

            // Middleware
            if (prop === 'middleware') {
                return middleware;
            }

            // Utilities
            if (prop in utilities) {
                return utilities[prop];
            }

            // Storage adapters
            if (prop === 'storage') {
                return storage;
            }

            // Registry access
            if (prop === 'stores') {
                return storesRegistry;
            }

            const value = target[prop];
            if (typeof value !== 'function') return value;

            return (...args) => {
                if (isReady) return value(...args);
                return SerJSStoreProxy;
            };
        }
    });

    // ====================================
    // EXPORTAR API
    // ====================================

    window.SerJSStore = SerJSStoreProxy;

})(window, document);
