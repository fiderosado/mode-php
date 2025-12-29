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
/* 

 // Estado inicial
        let state = typeof createState === 'function' 
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
            */
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

        function subscribe(listener) {
            listeners.add(listener);
            // Retornar función de cleanup
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
            subscribe,
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

        // Registrar store
        storesRegistry.set(name, api);

        return api;
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
                    combinedState[storeName] = store.getState();
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
