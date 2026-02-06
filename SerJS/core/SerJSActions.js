(function (window, document) {
    'use strict';
    
    /**
     * Factory function que crea una instancia de Actions con un token CSRF específico
     * @param {string} csrfToken - Token CSRF para usar en las peticiones
     * @returns {Proxy} Proxy con método call para ejecutar acciones
     */
    function createActions(csrfToken) {
 
        async function call(name, data = {}) {
            const formData = new FormData();
            
            // Agregar CSRF token
            if (csrfToken) {
                formData.append('_token', csrfToken);
            } else {
                throw new Error("CSRF token no proporcionado..");
            }

            // Agregar datos al FormData
            for (const key in data) {
                if (data.hasOwnProperty(key)) {
                    const value = data[key];
                    
                    // Manejar objetos y arrays convirtiéndolos a JSON
                    if (typeof value === 'object' && value !== null && !(value instanceof File) && !(value instanceof Blob)) {
                        formData.append(key, JSON.stringify(value));
                    } else {
                        formData.append(key, value);
                    }
                }
            }

            try {
                
                const response = await fetch(`?__action=${encodeURIComponent(name)}`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                // Verificar si la respuesta es exitosa
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({ 
                        error: `HTTP error! status: ${response.status}` 
                    }));
                    throw new Error(errorData.error || `Error ${response.status}`);
                }

                // Parsear y retornar la respuesta JSON
                const result = await response.json();
                return result;
                
            } catch (error) {
                // Manejar errores de red o parsing
                console.error(`[SerJSActions] Error al ejecutar acción "${name}":`, error);
                throw error;
            }
        }

        // Crear un Proxy que expone el método call
        return new Proxy({}, {
            get(target, prop) {
                // Si se accede a 'call', retornar la función call
                if (prop === 'call') {
                    return call;
                }
                // Para cualquier otra propiedad, retornar undefined o lanzar error
                // Esto mantiene el comportamiento estricto
                return undefined;
            },
            has(target, prop) {
                // Solo 'call' existe en el proxy
                return prop === 'call';
            },
            ownKeys(target) {
                // Solo retornar 'call' como propiedad propia
                return ['call'];
            }
        });
    }

    // Exportar la función factory
    window.SerJSActions = createActions;

})(window, document);