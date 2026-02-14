(function (window, document) {

    class SerJSCsrfTokenClass {

        constructor() {
            this.isClient = typeof window !== 'undefined' && typeof document !== 'undefined';
            this._token = null;
        }

        /**
         * Obtiene el token CSRF del atributo data-csrf del script tag
         * @returns {string} El token CSRF o cadena vacía si no se encuentra
         */
        getToken() {
            if (!this.isClient) {
                console.warn('CSRF token is only available on client side');
                return '';
            }

            // Cachear el token para evitar búsquedas repetidas en el DOM
            if (this._token !== null) {
                return this._token;
            }

            const scriptTag = document.querySelector('script[src*="script.js"]');
            this._token = scriptTag?.dataset.csrf || '';

            if (!this._token) {
                console.warn('CSRF token not found in script tag');
            }

            return this._token;
        }

        /**
         * Alias para getToken() para compatibilidad
         * @returns {string} El token CSRF
         */
        token() {
            return this.getToken();
        }

    }

    window.SerJSCsrfToken = new SerJSCsrfTokenClass();

})(window, document);
