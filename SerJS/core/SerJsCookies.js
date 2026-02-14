(function (window, document) {

    class SerJSCookiesClass {

        constructor() {
            this.isClient = typeof window !== 'undefined' && typeof document !== 'undefined';
        }

        /**
         * Verifica si estamos en el lado del cliente
         */
        ensureClientSide() {
            if (!this.isClient) {
                throw new Error('You are trying to access cookies on the server side.');
            }
        }

        /**
         * Decodifica un valor de cookie
         */
        decode(value) {
            try {
                return decodeURIComponent(value);
            } catch (e) {
                return value;
            }
        }

        /**
         * Codifica un valor para cookie
         */
        encode(value) {
            return encodeURIComponent(value);
        }

        /**
         * Convierte un valor a string para almacenar en cookie
         */
        stringify(value) {
            if (typeof value === 'string') return value;
            return JSON.stringify(value);
        }

        /**
         * Serializa una cookie con sus opciones
         */
        serialize(name, value, options = {}) {
            const opt = Object.assign({ path: '/' }, options);
            let cookie = `${this.encode(name)}=${this.encode(value)}`;

            if (opt.maxAge !== undefined) {
                cookie += `; Max-Age=${opt.maxAge}`;
            }

            if (opt.expires) {
                cookie += `; Expires=${opt.expires.toUTCString()}`;
            }

            if (opt.path) {
                cookie += `; Path=${opt.path}`;
            }

            if (opt.domain) {
                cookie += `; Domain=${opt.domain}`;
            }

            if (opt.secure) {
                cookie += '; Secure';
            }

            if (opt.httpOnly) {
                cookie += '; HttpOnly';
            }

            if (opt.sameSite) {
                cookie += `; SameSite=${opt.sameSite}`;
            }

            return cookie;
        }

        /**
         * Obtiene todas las cookies como objeto
         */
        getCookies() {
            this.ensureClientSide();

            const cookies = {};
            const documentCookies = document.cookie ? document.cookie.split('; ') : [];

            for (let i = 0, len = documentCookies.length; i < len; i++) {
                const cookieParts = documentCookies[i].split('=');
                const name = this.decode(cookieParts[0]); // Decodificar el nombre
                const value = cookieParts.slice(1).join('=');
                cookies[name] = value;
            }

            return cookies;
        }

        /**
         * Obtiene el valor de una cookie específica
         */
        getCookie(key) {
            this.ensureClientSide();

            const cookies = this.getCookies();
            const value = cookies?.[key];

            if (value === undefined) return undefined;

            return this.decode(value);
        }

        /**
         * Establece una cookie
         */
        setCookie(key, data, options = {}) {
            this.ensureClientSide();

            const cookieOptions = Object.assign({ path: '/' }, options);
            const cookieStr = this.serialize(key, this.stringify(data), cookieOptions);
            document.cookie = cookieStr;
        }

        /**
         * Elimina una cookie
         */
        deleteCookie(key, options = {}) {
            this.ensureClientSide();

            this.setCookie(key, '', Object.assign(Object.assign({}, options), { maxAge: -1 }));
        }

        /**
         * Verifica si existe una cookie
         */
        hasCookie(key) {
            this.ensureClientSide();

            if (!key) return false;

            const cookies = this.getCookies();
            if (!cookies) return false;

            return Object.prototype.hasOwnProperty.call(cookies, key);
        }

        /**
         * Obtiene todas las cookies que coincidan con un patrón
         */
        getCookiesByPattern(pattern) {
            this.ensureClientSide();

            const cookies = this.getCookies();
            const regex = new RegExp(pattern);
            const matched = {};

            for (const key in cookies) {
                if (regex.test(key)) {
                    matched[key] = this.decode(cookies[key]);
                }
            }

            return matched;
        }

        /**
         * Elimina todas las cookies que coincidan con un patrón
         */
        deleteCookiesByPattern(pattern, options = {}) {
            this.ensureClientSide();

            const cookies = this.getCookiesByPattern(pattern);

            for (const key in cookies) {
                this.deleteCookie(key, options);
            }
        }

        /**
         * Obtiene el valor de una cookie parseado como JSON
         */
        getCookieJSON(key) {
            const value = this.getCookie(key);
            if (!value) return undefined;

            try {
                return JSON.parse(value);
            } catch (e) {
                console.error(`Error parsing cookie "${key}" as JSON:`, e);
                return value;
            }
        }

        /**
         * Establece una cookie con un objeto JSON
         */0
        setCookieJSON(key, data, options = {}) {
            this.setCookie(key, JSON.stringify(data), options);
        }

    }

    window.SerJSCookies = new SerJSCookiesClass();

})(window, document);
