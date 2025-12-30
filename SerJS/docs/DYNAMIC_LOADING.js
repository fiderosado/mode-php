/**
 * Sistema de Carga Dinámica de Módulos SerJS
 * ============================================
 * 
 * Este documento explica cómo funciona el sistema de carga dinámica
 * de módulos en SerJS y cómo agregar nuevos módulos.
 */

// ============================================
// FUNCIÓN GENÉRICA: loadSerJSModule
// ============================================

/**
 * Función reutilizable para cargar cualquier módulo de SerJS
 * 
 * @param {string} moduleName - Nombre del módulo en window (ej: 'SerJSStore')
 * @param {string} scriptPath - Ruta al archivo JS del módulo
 * @returns {Promise} - Promesa que resuelve con el módulo cargado
 * 
 * Ventajas:
 * - ✅ Evita duplicación de código
 * - ✅ Carga bajo demanda (lazy loading)
 * - ✅ Previene múltiples cargas del mismo módulo
 * - ✅ Manejo de errores consistente
 * - ✅ Fácil de extender para nuevos módulos
 */
const loadSerJSModule = (moduleName, scriptPath) => {
    return new Promise((resolve, reject) => {
        // 1. Verificar si el módulo ya está cargado
        if (window[moduleName]) {
            return resolve(window[moduleName]);
        }

        // 2. Crear y configurar el script tag
        const script = document.createElement('script');
        script.src = scriptPath;
        script.async = true;

        // 3. Manejar carga exitosa
        script.onload = () => {
            if (window[moduleName]) {
                resolve(window[moduleName]);
            } else {
                reject(new Error(`El módulo ${moduleName} no se cargó correctamente`));
            }
        };
        
        // 4. Manejar errores de carga
        script.onerror = () => {
            reject(new Error(`Error al cargar ${scriptPath}`));
        };

        // 5. Insertar el script en el DOM
        document.head.appendChild(script);
    });
};

// ============================================
// MÓDULOS ACTUALES
// ============================================

// SerJSStore - Sistema de gestión de estado
const loadSerJSStore = () => loadSerJSModule(
    'SerJSStore', 
    '../../SerJS/core/SerJSStore.js'
);

// SerJSNavigation - Sistema de navegación
const loadSerJSNavigation = () => loadSerJSModule(
    'SerJSNavigation', 
    '../../SerJS/core/SerJSNavigation.js'
);

// ============================================
// CÓMO AGREGAR UN NUEVO MÓDULO
// ============================================

/**
 * PASO 1: Crear la función de carga
 * 
 * Ejemplo: Agregar un módulo de formularios llamado SerJSForm
 */
const loadSerJSForm = () => loadSerJSModule(
    'SerJSForm',                          // Nombre en window
    '../../SerJS/core/SerJSForm.js'      // Ruta del archivo
);

/**
 * PASO 2: Agregar el proxy en SerJS.js
 * 
 * Dentro del Proxy de SerJS, agregar:
 */
if (prop === 'form') {
    return new Proxy({}, {
        get(target, method) {
            return async (...args) => {
                if (!window.SerJSForm) {
                    await loadSerJSForm();
                }
                const value = window.SerJSForm[method];
                if (typeof value === 'function') {
                    return value(...args);
                }
                return value;
            };
        }
    });
}

/**
 * PASO 3: Usar el nuevo módulo
 */
const { form } = SerJS;

// Ahora se puede usar de forma asíncrona
await form.validate();
await form.submit();
const errors = await form.getErrors();

// ============================================
// EJEMPLOS DE USO ACTUAL
// ============================================

// --- STORE ---
const { store } = SerJS;

const useCounterStore = await store.create((set, get) => ({
    count: 0,
    increment: () => set({ count: get().count + 1 })
}), {
    name: 'counter',
    persist: true
});

// --- NAVIGATION ---
const { navigation } = SerJS;

await navigation.push('/nueva-ruta');
const pathname = await navigation.usePathname();
const router = await navigation.useRouter();
const query = await navigation.useQuery();

// ============================================
// VENTAJAS DEL SISTEMA
// ============================================

/**
 * 1. LAZY LOADING
 *    - Los módulos solo se cargan cuando se usan
 *    - Mejora el tiempo de carga inicial
 *    - Reduce el bundle size inicial
 * 
 * 2. PREVENCIÓN DE DUPLICADOS
 *    - Verifica si el módulo ya está cargado
 *    - No vuelve a cargar el mismo script
 * 
 * 3. MANTENIBILIDAD
 *    - Una sola función para todos los módulos
 *    - Fácil agregar nuevos módulos
 *    - Código DRY (Don't Repeat Yourself)
 * 
 * 4. MANEJO DE ERRORES
 *    - Errores descriptivos
 *    - Fácil debugging
 * 
 * 5. ESCALABILIDAD
 *    - Agregar 10 módulos = 10 líneas de código
 *    - Sin la función genérica = 300+ líneas
 */

// ============================================
// FLUJO DE CARGA
// ============================================

/**
 * 1. Usuario llama: await navigation.push('/ruta')
 * 
 * 2. Proxy intercepta el acceso a 'navigation'
 * 
 * 3. Proxy verifica si window.SerJSNavigation existe
 *    ├─ SI existe → usar directamente
 *    └─ NO existe → llamar loadSerJSNavigation()
 * 
 * 4. loadSerJSNavigation() llama a loadSerJSModule()
 * 
 * 5. loadSerJSModule() crea script tag y lo inserta
 * 
 * 6. Script se carga y window.SerJSNavigation queda disponible
 * 
 * 7. Proxy ejecuta navigation.push('/ruta')
 * 
 * 8. Llamadas subsecuentes usan el módulo ya cargado
 */

// ============================================
// PATRÓN DE DISEÑO
// ============================================

/**
 * Este sistema implementa varios patrones:
 * 
 * - Proxy Pattern: Intercepta accesos a propiedades
 * - Lazy Loading: Carga bajo demanda
 * - Factory Pattern: loadSerJSModule crea loaders
 * - Singleton: Un solo módulo cargado por tipo
 * - Promise Pattern: Manejo asíncrono consistente
 */

// ============================================
// CONSIDERACIONES
// ============================================

/**
 * IMPORTANTE:
 * 
 * 1. Todos los métodos son async/await
 *    await navigation.push()  ✅
 *    navigation.push()        ❌
 * 
 * 2. El módulo debe exportarse a window
 *    window.SerJSStore = { ... }  ✅
 *    export default { ... }       ❌
 * 
 * 3. Las rutas son relativas al HTML
 *    '../../SerJS/core/Module.js' ✅
 *    '/SerJS/core/Module.js'      ⚠️ (depende del contexto)
 * 
 * 4. Los módulos se cargan solo una vez
 *    Primera llamada: carga el script
 *    Siguientes: usa el módulo cargado
 */

export { 
    loadSerJSModule, 
    loadSerJSStore, 
    loadSerJSNavigation 
};
