(function (window, document) {

    let isReady = false;
    const queue = [];

    document.addEventListener('DOMContentLoaded', () => {
        isReady = true;
        queue.forEach(fn => fn());
        queue.length = 0;
    });

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

    function useState(initialValue) {
        let value = initialValue;
        const subscribers = new Set();

        const notify = () => {
            subscribers.forEach(fn => fn(value));
        };

        // Crear un Proxy que se comporta como el valor
        const stateProxy = new Proxy({}, {
            get(target, prop) {
                // Método interno para suscripciones (usado por useEffect)
                if (prop === 'subscribe') {
                    return (callback) => {
                        subscribers.add(callback);
                        return () => subscribers.delete(callback);
                    };
                }

                // Conversión a primitivo
                if (prop === Symbol.toPrimitive) {
                    return (hint) => {
                        if (hint === 'number') return Number(value);
                        if (hint === 'string') return String(value);
                        return value;
                    };
                }

                if (prop === 'valueOf') return () => value;
                if (prop === 'toString') return () => String(value);

                // Permitir acceso a propiedades del valor (para arrays/objetos)
                const val = value;
                if (val != null && typeof val === 'object' && prop in val) {
                    const propValue = val[prop];
                    if (typeof propValue === 'function') {
                        return propValue.bind(val);
                    }
                    return propValue;
                }

                return undefined;
            }
        });

        // Función para actualizar el estado
        function setState(nextValue) {
            const newValue = typeof nextValue === 'function'
                ? nextValue(value)
                : nextValue;

            if (Object.is(value, newValue)) {
                return;
            }

            value = newValue;
            notify();
        }

        // Convertir el Proxy a primitivo antes de retornar
        const primitiveState = typeof stateProxy === 'object' && Symbol.toPrimitive in stateProxy
            ? stateProxy[Symbol.toPrimitive]()
            : stateProxy;

        return [primitiveState, setState];
    }

    function useEffect(effect, deps = []) {
        let cleanup;

        const run = () => {
            if (cleanup && typeof cleanup === 'function') {
                cleanup();
                cleanup = null;
            }

            const result = effect();
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
                if (cleanup && typeof cleanup === 'function') {
                    cleanup();
                }
            };
        }

        // Validar y suscribirse a dependencias
        const unsubscribers = deps.map(dep => {
            if (!dep || typeof dep.subscribe !== 'function') {
                console.warn(
                    '[SerJS] useEffect: dependencia inválida. ' +
                    'Debes pasar estados creados con useState.',
                    dep
                );
                return () => {};
            }
            return dep.subscribe(run);
        });

        // Ejecutar una vez al inicio
        if (isReady) {
            run();
        } else {
            queue.push(run);
        }

        // Retornar función de limpieza
        return () => {
            unsubscribers.forEach(unsub => unsub());
            if (cleanup && typeof cleanup === 'function') {
                cleanup();
            }
        };
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
        }
    };

    const events = {
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

    const proxy = new Proxy(methods, {
        get(target, prop) {
            if (prop === 'useRef') return useRef;
            if (prop === 'useState') return useState;
            if (prop === 'useEffect') return useEffect;
            if (prop === 'events') return events;

            const value = target[prop];
            if (typeof value !== 'function') return value;

            return (...args) => {
                if (isReady) return value(...args);
                else queue.push(() => value(...args));
                return proxy;
            };
        }
    });

    window.Ser = proxy;

})(window, document);