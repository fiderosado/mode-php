(function (window, document) {

    let isReady = false;
    const queue = [];

    document.addEventListener('DOMContentLoaded', () => {
        isReady = true;
        queue.forEach(fn => fn());
        queue.length = 0;
    });

    const statesMap = new Map();
    let stateIdCounter = 0;
    const memoStore = new Map();

    function generateId() {
        return Date.now().toString(36) + Math.random().toString(36).substring(2, 8);
    }

    function useState(initialValue) {
        const stateId = stateIdCounter++;
        statesMap.set(stateId, {
            value: initialValue,
            subscribers: new Set()
        });
        const state = statesMap.get(stateId);
        function setState(newValue) {
            const nextValue = typeof newValue === 'function'
                ? newValue(state.value)
                : newValue;

            if (Object.is(state.value, nextValue)) {
                return;
            }

            state.value = nextValue;

            // Notificar a todos los suscriptores (useEffect)
            state.subscribers.forEach(callback => callback(nextValue));
        }
        const getter = {
            get current() {
                return state.value;
            },

            // Método interno para suscripciones
            subscribe(callback) {
                state.subscribers.add(callback);
                return () => state.subscribers.delete(callback);
            },

            // ID interno para identificar el estado
            _stateId: stateId,

            // Conversión a primitivo
            [Symbol.toPrimitive](hint) {
                if (hint === 'number') return Number(state.value);
                if (hint === 'string') return String(state.value);
                return state.value;
            },

            valueOf() {
                return state.value;
            },

            toString() {
                return String(state.value);
            }
        };
        return [getter, setState];
    }

    function useEffect(callback, deps = []) {
        let cleanup;
        const unsubscribers = [];
        const run = () => {

            // Limpiar efecto anterior
            if (cleanup && typeof cleanup === 'function') {
                cleanup();
                // cleanup = null;
            }

            // Ejecutar el efecto
            const result = callback();
            cleanup = typeof result === 'function' ? result : null;
        };

        // Sin dependencias: ejecutar solo una vez
        if (deps.length === 0) {
            if (isReady) {
                run();
            } else {
                queue.push(run);
            }

            return () => {
                if (cleanup) cleanup();
            };
        }

        // Con dependencias: suscribirse a los estados
        deps.forEach(dep => {

            if (dep && typeof dep.subscribe === 'function') {
                const unsub = dep.subscribe(() => {
                    run();
                });
                unsubscribers.push(unsub);
            } else {
                console.warn(
                    '[SerJS] useEffect: dependencia inválida. ' +
                    'Debes pasar estados creados con useState.',
                    dep
                );
            }
        });

        // Retornar función de limpieza
        return () => {
            unsubscribers.forEach(unsub => unsub());
            if (cleanup && typeof cleanup === 'function') {
                cleanup();
            }
        };
    }

    function useMemo(factory, deps = [], key = null) {
        const memoKey = key || generateId();
        if (!memoStore.has(memoKey)) {
            const record = {
                current: factory(),
                deps: Array.isArray(deps) ? deps.map(d => d.current) : null,
                unsubscribers: []
            };
            if (Array.isArray(deps) && deps.length > 0) {
                const compute = () => {
                    const next = deps.map(d => d.current);
                    const same = next.length === record.deps.length && next.every((v, i) => v === record.deps[i]);
                    if (!same) {
                        record.deps = next;
                        record.current = factory();
                    }
                };
                deps.forEach(dep => {
                    if (dep && typeof dep.subscribe === 'function') {
                        record.unsubscribers.push(dep.subscribe(compute));
                    }
                });
            }
            memoStore.set(memoKey, record);
        }
        return memoStore.get(memoKey);
    }

    function useRef(id = null) {
        const ref = { current: null, effects: [] };

        const addEvent = (eventName, callback) => {
            const attach = () => {
                if (ref.current) ref.current.addEventListener(eventName, callback);
            };
            if (isReady && ref.current) attach();
            else queue.push(attach);
        };

        ref.onClick = (cb) => addEvent('click', cb);
        ref.onChange = (cb) => addEvent('change', cb);
        ref.onHover = (cb) => addEvent('mouseenter', cb);
        ref.on = (eventName, cb) => addEvent(eventName, cb);

        const assign = () => {
            if (id) ref.current = document.getElementById(id);
            ref.effects.forEach(cb => cb(ref.current));
        };

        if (isReady) assign();
        else queue.push(assign);

        return ref;
    }

    function resolve(ref) {
        if (!ref || !ref.current) return [];
        if (ref.current instanceof Element) return [ref.current];
        if (ref.current instanceof NodeList || Array.isArray(ref.current)) return [...ref.current];
        return [];
    }

    const methods = {
        setText(ref, text) {
            resolve(ref).forEach(el => {
                el.textContent = String(text);
            });
        },
        setHTML(ref, html) {
            resolve(ref).forEach(el => {
                el.innerHTML = String(html);
            });
        },
        reRender(ref, state = {}) {
            resolve(ref).forEach(el => {

                if (!el.__template) {
                    el.__template = el.innerHTML;
                }

                let html = el.__template;

                html = html.replace(/\$\{(\w+)\}/g, (_, key) =>
                    key in state ? state[key] ?? '' : ''
                );

                el.innerHTML = html;
            });
        },
        addClass(ref, className) {
            resolve(ref).forEach(el => {
                el.classList.add(String(className));
            });
        },
        removeClass(ref, className) {
            resolve(ref).forEach(el => {
                el.classList.remove(String(className));
            });
        },
        setAttr(ref, name, value) {
            resolve(ref).forEach(el => {
                el.setAttribute(name, String(value));
            });
        },
        setStyle(ref, property, value) {
            resolve(ref).forEach(el => {
                el.style[property] = String(value);
            });
        },
        onClick(ref, callback) {
            resolve(ref).forEach(el => {
                el.addEventListener('click', callback);
            });
        },
        onChange(ref, callback) {
            resolve(ref).forEach(el => {
                el.addEventListener('change', callback);
            });
        },
        onHover(ref, callback) {
            resolve(ref).forEach(el => {
                el.addEventListener('mouseenter', callback);
            });
        },
        on(eventName, ref, callback) {
            resolve(ref).forEach(el => {
                el.addEventListener(eventName, callback);
            });
        }
    };

    const moduleCache = useMemo(() => new Map(), []);

    // Función genérica para cargar módulos de SerJS dinámicamente
    function loadSerJSModule(moduleName, scriptPath) {

        if (window[moduleName]) {
            return Promise.resolve(window[moduleName]);
        }
    
        if (moduleCache.current.has(moduleName)) {
            return moduleCache.current.get(moduleName);
        }

        const promise = new Promise((resolve, reject) => {

            const script = document.createElement('script');
            script.src = scriptPath;
            script.type = 'module';
            script.async = true;

            script.onload = () => {
                if (window[moduleName]) {
                    resolve(window[moduleName]);
                } else {
                    reject(new Error(`El módulo ${moduleName} no se cargó correctamente`));
                }
            };

            script.onerror = () => {
                moduleCache.current.delete(moduleName);
                reject(new Error(`Error al cargar ${scriptPath}`));
            };

            document.head.appendChild(script);
        });

        moduleCache.current.set(moduleName, promise);
        return promise;
    };

    function getModuleCache() { return moduleCache.current; }

    window.SerJS = new Proxy(methods, {
        get(target, prop) {

            if (prop === 'useRef') return useRef;
            if (prop === 'useState') return useState;
            if (prop === 'useEffect') return useEffect;
            if (prop === 'useMemo') return useMemo;
            if (prop === 'getModuleCache') return getModuleCache;

            // Método para importar módulos dinámicamente
            if (prop === 'importModule') return async (moduleName, modulePath) => {
                const normalizedPath = modulePath.startsWith('./') || modulePath.startsWith('../') ? modulePath : `./${modulePath}`;
                if (!window[moduleName]) {
                    await loadSerJSModule(moduleName, normalizedPath);
                }
                return new Proxy(window[moduleName], {
                    get(target, method) {
                        const value = target[method];
                        if (typeof value === 'function') {
                            return value.bind(target);
                        }
                        return value;
                    }
                });
            }

            // Navigation methods
            if (prop === 'navigation') {
                return new Proxy({}, {
                    get(target, method) {
                        return async (...args) => {
                            if (!window.SerJSNavigation) {
                                await loadSerJSModule('SerJSNavigation', '../../SerJS/core/SerJSNavigation.js')
                            }
                            const value = window.SerJSNavigation[method];
                            if (typeof value === 'function') {
                                return value(...args);
                            }
                            return value;
                        };
                    }
                });
            }

            if (prop === 'Actions') {
                return async (...args) => {

                    if (!window.SerJSActions) {
                        await loadSerJSModule(
                            'SerJSActions',
                            '../../SerJS/core/SerJSActions.js'
                        );
                    }
            
                    if (typeof window.SerJSActions !== 'function') {
                        throw new Error('SerJSActions no es una función');
                    }

                   // console.log("window.SerJSActions", window.SerJSActions);
            
                    return window.SerJSActions(...args);
                };
            }
            

            if (prop === 'store') {
                // Crear un proxy que intercepte el acceso al método create
                return new Proxy({}, {
                    get(target, method) {
                        return async (...args) => {
                            // Cargar SerJSStore si no está disponible
                            if (!window.SerJSStore) {
                                await loadSerJSModule('SerJSStore', '../../SerJS/core/SerJSStore.js')
                            }
                            // Llamar al método en SerJSStore
                            if (typeof window.SerJSStore[method] === 'function') {
                                return window.SerJSStore[method](...args);
                            }
                            throw new Error(`SerJSStore.${method} no es una función`);
                        };
                    }
                });
            }

            const value = target[prop];
            if (typeof value !== 'function') return value;

            return (...args) => {
                if (isReady) return value(...args);
                else queue.push(() => value(...args));
                return proxy;
            };
        }
    });

})(window, document);
