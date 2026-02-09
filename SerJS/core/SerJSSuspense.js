(function (window, document) {

    class SuspenseInstance {

        constructor({ el, actionName }) {
            this.el = el;
            this.actionName = actionName;
            this.fallback = el.innerHTML;
            this.action = null;
            this.hash = null;
            this.loading = false;
        }

        async init() {
            this.hash = this.el.dataset.suspense;

            window.__SerActions__ ??= {};

            if (!window.__SerActions__[this.hash]) {
                window.__SerActions__[this.hash] = await SerJS.Actions(this.hash);
            }

            this.action = window.__SerActions__[this.hash];

            return this;
        }

        async call(payload = {}) {
            if (this.loading) return;

            this.loading = true;

            try {
                this.el.innerHTML = this.fallback;
                const response = await this.action.call(this.actionName, payload);
                console.log("Suspense call response:", response);
                this.el.innerHTML = response ?? "Error en la acción";
            } catch (err) {
                console.error("Suspense call error:", err);
                this.el.innerHTML = "Error cargando contenido";
            }
            this.loading = false;
        }

        reset() {
            this.el.innerHTML = this.fallback;
        }

        setFallback(html) {
            this.fallback = html;
        }

        getFallback() {
            return this.fallback;
        }

        getElement() {
            return this.el;
        }
    }

    class SerJSSuspenseClass {

        constructor() {
            this.registry = new Map();
        }

        async registerAll() {

            const nodes = document.querySelectorAll("[data-suspense][data-action]");

            for (const el of nodes) {

                const actionName = el.dataset.action;

                const instance = new SuspenseInstance({
                    el,
                    actionName
                });

                await instance.init();

                this.registry.set(actionName, instance);

                // Primera carga automática
                instance.call();
            }
        }

        action(actionName) {
            return this.registry.get(actionName) || null;
        }

        has(actionName) {
            return this.registry.has(actionName);
        }

        remove(actionName) {
            this.registry.delete(actionName);
        }

        getAll() {
            return this.registry;
        }
    }

    window.SerJSSuspense = new SerJSSuspenseClass();

})(window, document);
