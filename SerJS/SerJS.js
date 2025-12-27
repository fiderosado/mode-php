// crear una clase
(function (window, document) {

    /* =============================
       Estado interno
    ============================== */
    let isReady = false;
    const queue = [];

    document.addEventListener('DOMContentLoaded', () => {
        isReady = true;
        queue.forEach(fn => fn());
        queue.length = 0;
    });

    /* =============================
       useRef automático
    ============================== */
    function useRef(id = null) {
        const ref = { current: null };

        // Si se pasa un id, asigna automáticamente al cargar el DOM
        const assign = () => {
            if (!id) return;
            ref.current = document.getElementById(id);
        };

        if (isReady) {
            assign();
        } else {
            queue.push(assign);
        }

        return ref;
    }

    /* =============================
       Resolver elementos
    ============================== */
    function resolve(ref) {
        if (!ref || !ref.current) return [];
        if (ref.current instanceof Element) return [ref.current];
        if (ref.current instanceof NodeList || Array.isArray(ref.current)) {
            return [...ref.current];
        }
        return [];
    }

    /* =============================
       Métodos reales
    ============================== */
    const methods = {
        slideUp(ref, duration = 400) {
            resolve(ref).forEach(el => {
                el.style.height = el.scrollHeight + 'px';
                el.style.overflow = 'hidden';
                el.style.transition = `height ${duration}ms ease`;

                requestAnimationFrame(() => {
                    el.style.height = '0';
                });

                setTimeout(() => {
                    el.style.display = 'none';
                    el.style.removeProperty('height');
                    el.style.removeProperty('overflow');
                    el.style.removeProperty('transition');
                }, duration);
            });
        },

        slideDown(ref, duration = 400) {
            resolve(ref).forEach(el => {
                el.style.display = 'block';
                const h = el.scrollHeight;

                el.style.height = '0';
                el.style.overflow = 'hidden';
                el.style.transition = `height ${duration}ms ease`;

                requestAnimationFrame(() => {
                    el.style.height = h + 'px';
                });

                setTimeout(() => {
                    el.style.removeProperty('height');
                    el.style.removeProperty('overflow');
                    el.style.removeProperty('transition');
                }, duration);
            });
        }
    };

    /* =============================
       Proxy protector
    ============================== */
    const Ser = new Proxy(methods, {
        get(target, prop) {

            if (prop === 'useRef') return useRef;

            const value = target[prop];
            if (typeof value !== 'function') return value;

            return (...args) => {
                if (isReady) {
                    value(...args);
                } else {
                    queue.push(() => value(...args));
                }
            };
        }
    });

    /* =============================
       Exponer global
    ============================== */
    window.Ser = Ser;

})(window, document);
