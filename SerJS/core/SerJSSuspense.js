(function (window, document) {

    class SuspenseInstance {

        constructor({ el, actionName }) {
            this.el = el;
            this.actionName = actionName;
            this.fallback = el.innerHTML;
            this.action = null;
            this.hash = null;
            this.loading = false;
            this.payload = {};
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

        async callWithInit(payload = {}) {
            if (!this.action) {
                await this.init();
            }
            return this.call(payload);
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

        getPayload() {
            return this.payload;
        }

        setPayload(data) {
            this.payload = data;
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
                const targetId = el.dataset.target;

                // Validar que no exista ya una instancia registrada con este targetId
                if (this.registry.has(targetId)) {
                    console.warn(`Instancia con targetId "${targetId}" ya existe. Saltando...`);
                    continue;
                }

                const instance = new SuspenseInstance({
                    el,
                    actionName
                });

                // Extraer y decodificar payload del atributo data-payload
                if (el.dataset.payload) {
                    try {
                        const payloadString = atob(el.dataset.payload);
                        const payloadData = JSON.parse(payloadString);
                        instance.setPayload(payloadData);
                    } catch (e) {
                        console.error("Error parsing payload:", e);
                        instance.setPayload({});
                    }
                    // Eliminar atributo data-payload después de leerlo
                    el.removeAttribute('data-payload');
                }

                // Usar targetId como clave única para cada instancia
                this.registry.set(targetId, instance);

                // Ejecutar acción (inicializa si es necesario)
                await instance.callWithInit(instance.getPayload());
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