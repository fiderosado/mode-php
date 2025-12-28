// ============================================
// core/reactive/state.js
// ============================================

/* =============================
   useState estilo React
   Devuelve [valorActual, setValor]
============================== */
export function useState(initialValue) {
    let value = initialValue;
    const subscribers = new Set();

    const notify = () => {
        subscribers.forEach(fn => fn(value));
    };

    // Objeto estado con métodos especiales
    const state = {
        // Método para suscribirse a cambios
        subscribe(callback) {
            subscribers.add(callback);
            // Retornar función para desuscribirse
            return () => subscribers.delete(callback);
        },

        // Obtener el valor actual
        get value() {
            return value;
        },

        // Permitir usar el estado como primitivo
        valueOf() {
            return value;
        },

        toString() {
            return String(value);
        },

        // Symbol para conversión primitiva
        [Symbol.toPrimitive](hint) {
            if (hint === 'number') return Number(value);
            if (hint === 'string') return String(value);
            return value;
        }
    };

    // Función para actualizar el estado
    function setState(nextValue) {
        // Soportar funciones updater: setState(prev => prev + 1)
        const newValue = typeof nextValue === 'function' 
            ? nextValue(value) 
            : nextValue;

        // Solo actualizar si el valor cambió
        if (Object.is(value, newValue)) {
            return;
        }

        value = newValue;
        notify();
    }

    return [state, setState];
}

/* =============================
   useComputed - Valor derivado
============================== */
export function useComputed(computeFn, dependencies = []) {
    let cachedValue;
    let isInitialized = false;

    const [computed, setComputed] = useState(null);

    const recompute = () => {
        try {
            const newValue = computeFn();
            if (!Object.is(cachedValue, newValue)) {
                cachedValue = newValue;
                setComputed(newValue);
            }
        } catch (error) {
            console.error('[SerJS] Error en useComputed:', error);
        }
    };

    // Suscribirse a dependencias
    const unsubscribers = dependencies.map(dep => {
        if (dep && typeof dep.subscribe === 'function') {
            return dep.subscribe(recompute);
        }
        console.warn('[SerJS] useComputed: dependencia inválida', dep);
        return () => {};
    });

    // Calcular valor inicial
    if (!isInitialized) {
        recompute();
        isInitialized = true;
    }

    // Retornar estado computado y función de limpieza
    const cleanup = () => {
        unsubscribers.forEach(unsub => unsub());
    };

    return [computed, cleanup];
}

/* =============================
   createSignal - Señal reactiva simple
============================== */
export function createSignal(initialValue) {
    const [state, setState] = useState(initialValue);

    // Getter
    const get = () => state.value;

    // Setter
    const set = (newValue) => setState(newValue);

    // Update con función
    const update = (updater) => setState(updater);

    return [get, set, update];
}
