/**
 * SerJS Navigation Module v2.0
 * Sistema de navegación inspirado en Next.js/React Router
 * Compatible con el sistema de routing file-based de mode-php
 * Mejorado con Proxy para mayor eficiencia
 */

(function (window, document) {
    
    'use strict';

    // ====================================
    // ESTADO GLOBAL DE NAVEGACIÓN
    // ====================================
    
    const navigationState = {
        currentPath: window.location.pathname,
        currentSearch: window.location.search,
        currentHash: window.location.hash,
        history: [],
        listeners: new Map(),
        isReady: false
    };

    // ====================================
    // FUNCIONES DE NAVEGACIÓN BÁSICAS
    // ====================================

    const navigation = {
        /**
         * Navega a una nueva ruta
         */
        push(path, options = {}) {
            const { 
                scroll = true, 
                replace = false,
                state = null,
                reload = true
            } = options;

            if (replace) {
                window.history.replaceState(state, '', path);
            } else {
                window.history.pushState(state, '', path);
            }

            updateNavigationState();

            if (scroll) {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }

            notifyListeners('routeChange');
            
            if (reload) {
                window.location.href = path;
            }
        },

        /**
         * Reemplaza la ruta actual
         */
        replace(path, options = {}) {
            this.push(path, { ...options, replace: true });
        },

        /**
         * Navega hacia atrás
         */
        back() {
            window.history.back();
        },

        /**
         * Navega hacia adelante
         */
        forward() {
            window.history.forward();
        },

        /**
         * Refresca la página
         */
        refresh() {
            window.location.reload();
        },

        /**
         * Navega a posición específica
         */
        go(delta) {
            window.history.go(delta);
        },

        /**
         * Prefetch de una ruta
         */
        prefetch(path) {
            if (!document.querySelector(`link[href="${path}"]`)) {
                const link = document.createElement('link');
                link.rel = 'prefetch';
                link.href = path;
                document.head.appendChild(link);
            }
        }
    };

    // ====================================
    // SISTEMA DE REDIRECCIONES
    // ====================================

    const redirects = {
        redirect(path, statusCode = 302) {
            console.log(`[Navigation] Redirecting to ${path} (${statusCode})`);
            window.location.href = path;
        },

        permanentRedirect(path) {
            this.redirect(path, 301);
        },

        temporaryRedirect(path) {
            this.redirect(path, 307);
        }
    };

    // ====================================
    // HOOKS DE NAVEGACIÓN
    // ====================================

    const hooks = {
        usePathname() {
            return navigationState.currentPath;
        },

        /**
         * Ejemplos de conversión de URLSearchParams a diferentes formatos:
         * 
         * 1. Convertir a array de pares [key, value]:
         *    const searchParams = Nav.useSearchParams();
         *    const arrayPares = Array.from(searchParams.entries());
         *    // Resultado: [ ['id', '123'], ['filter', 'active'], ['sort', 'date'] ]
         * 
         * 2. Convertir a array solo de valores:
         *    const searchParams = Nav.useSearchParams();
         *    const arrayValores = Array.from(searchParams.values());
         *    // Resultado: [ '123', 'active', 'date' ]
         * 
         * 3. Convertir a array solo de claves:
         *    const searchParams = Nav.useSearchParams();
         *    const arrayKeys = Array.from(searchParams.keys());
         *    // Resultado: [ 'id', 'filter', 'sort' ]
         * 
         * 4. Convertir a objeto (más común):
         *    const searchParams = Nav.useSearchParams();
         *    const objeto = Object.fromEntries(searchParams);
         *    // Resultado: { id: '123', filter: 'active', sort: 'date' }
         * 
         * 5. Iterar directamente:
         *    const searchParams = Nav.useSearchParams();
         *    const array = [];
         *    for (const [key, value] of searchParams) {
         *        array.push({ key, value });
         *    }
         *    // Resultado: [ {key: 'id', value: '123'}, {key: 'filter', value: 'active'} ]
         * 
         * 6. Usar spread operator:
         *    const searchParams = Nav.useSearchParams();
         *    const array = [...searchParams];
         *    // Resultado: [ ['id', '123'], ['filter', 'active'] ]
         */
        useSearchParams() {
            return new URLSearchParams(navigationState.currentSearch);
        },

        useParams(newParams) {
            return {
                params : newParams || {},
                route :  window.__ROUTE_PARAMS__ || {}
            }
        },

        useRouter(params = {}) {
            return {
                pathname: navigationState.currentPath,
                query: Object.fromEntries(new URLSearchParams(navigationState.currentSearch)),
                params: this.useParams( params?.params ),
                asPath: navigationState.currentPath + navigationState.currentSearch + navigationState.currentHash,
                push: navigation.push.bind(navigation),
                replace: navigation.replace.bind(navigation),
                back: navigation.back,
                forward: navigation.forward,
                refresh: navigation.refresh,
                prefetch: navigation.prefetch,
                isActive: utilities.isActive,
                events: events
            };
        },

        useQuery() {
            return Object.fromEntries(new URLSearchParams(navigationState.currentSearch));
        },

        useHash() {
            return navigationState.currentHash;
        }
    };

    // ====================================
    // UTILIDADES
    // ====================================

    const utilities = {
        isActive(path, exact = false) {
            const currentPath = navigationState.currentPath;
            
            if (exact) {
                return currentPath === path;
            }
            
            return currentPath.startsWith(path);
        },

        buildUrl(path, params = {}) {
            const url = new URL(path, window.location.origin);
            
            Object.entries(params).forEach(([key, value]) => {
                if (value !== null && value !== undefined) {
                    url.searchParams.set(key, String(value));
                }
            });
            
            return url.pathname + url.search;
        },

        getQueryParam(key) {
            const params = new URLSearchParams(navigationState.currentSearch);
            return params.get(key);
        },

        getAllQueryParams() {
            return Object.fromEntries(new URLSearchParams(navigationState.currentSearch));
        },

        setQueryParams(params, merge = true) {
            const searchParams = new URLSearchParams(
                merge ? navigationState.currentSearch : ''
            );
            
            Object.entries(params).forEach(([key, value]) => {
                if (value === null || value === undefined) {
                    searchParams.delete(key);
                } else {
                    searchParams.set(key, String(value));
                }
            });
            
            const newSearch = searchParams.toString();
            const newUrl = navigationState.currentPath + (newSearch ? '?' + newSearch : '');
            
            window.history.replaceState(null, '', newUrl);
            updateNavigationState();
            notifyListeners('queryChange');
        },

        matchPath(pattern, path = navigationState.currentPath) {
            const regex = new RegExp(
                '^' + pattern.replace(/:\w+/g, '([^/]+)') + '$'
            );
            return regex.test(path);
        }
    };

    // ====================================
    // SISTEMA DE EVENTOS
    // ====================================

    const events = {
        on(eventName, callback) {
            if (!navigationState.listeners.has(eventName)) {
                navigationState.listeners.set(eventName, new Set());
            }
            navigationState.listeners.get(eventName).add(callback);
            
            // También escuchar popstate para navegación del navegador
            if (eventName === 'routeChange') {
                window.addEventListener('popstate', callback);
            }
            
            return () => this.off(eventName, callback);
        },

        off(eventName, callback) {
            const listeners = navigationState.listeners.get(eventName);
            if (listeners) {
                listeners.delete(callback);
            }
            window.removeEventListener('popstate', callback);
        },

        once(eventName, callback) {
            const wrappedCallback = (...args) => {
                callback(...args);
                this.off(eventName, wrappedCallback);
            };
            this.on(eventName, wrappedCallback);
        },

        emit(eventName, data) {
            const listeners = navigationState.listeners.get(eventName);
            if (listeners) {
                listeners.forEach(callback => callback(data));
            }
        }
    };

    // ====================================
    // HISTORIAL
    // ====================================

    const history = {
        get() {
            return [...navigationState.history];
        },

        clear() {
            navigationState.history = [];
        },

        getLength() {
            return navigationState.history.length;
        },

        getLast(n = 1) {
            return navigationState.history.slice(-n);
        }
    };

    // ====================================
    // FUNCIONES INTERNAS
    // ====================================

    function updateNavigationState() {
        navigationState.currentPath = window.location.pathname;
        navigationState.currentSearch = window.location.search;
        navigationState.currentHash = window.location.hash;
        
        navigationState.history.push({
            path: navigationState.currentPath,
            search: navigationState.currentSearch,
            hash: navigationState.currentHash,
            timestamp: Date.now()
        });

        // Limitar historial a 100 entradas
        if (navigationState.history.length > 100) {
            navigationState.history.shift();
        }
    }

    function notifyListeners(eventName) {
        const data = {
            pathname: navigationState.currentPath,
            search: navigationState.currentSearch,
            hash: navigationState.currentHash,
            query: Object.fromEntries(new URLSearchParams(navigationState.currentSearch))
        };
        
        events.emit(eventName, data);
    }

    // Escuchar cambios del navegador
    window.addEventListener('popstate', () => {
        updateNavigationState();
        notifyListeners('routeChange');
    });

    // Inicializar
    document.addEventListener('DOMContentLoaded', () => {
        navigationState.isReady = true;
        updateNavigationState();
        notifyListeners('ready');
    });

    // ====================================
    // PROXY PARA API UNIFICADA
    // ====================================

    const NavigationProxy = new Proxy({}, {
        get(target, prop) {
            // Navegación
            if (prop in navigation) {
                return navigation[prop].bind(navigation);
            }
            
            // Hooks
            if (prop in hooks) {
                return hooks[prop].bind(hooks);
            }
            
            // Utilidades
            if (prop in utilities) {
                return utilities[prop].bind(utilities);
            }
            
            // Redirecciones
            if (prop in redirects) {
                return redirects[prop].bind(redirects);
            }
            
            // Eventos
            if (prop === 'events') {
                return events;
            }
            
            // Historial
            if (prop === 'history') {
                return history;
            }
            
            // Estado actual
            if (prop === 'pathname') {
                return navigationState.currentPath;
            }
            if (prop === 'search') {
                return navigationState.currentSearch;
            }
            if (prop === 'hash') {
                return navigationState.currentHash;
            }
            if (prop === 'query') {
                return Object.fromEntries(new URLSearchParams(navigationState.currentSearch));
            }
            if (prop === 'params') {
                return window.__ROUTE_PARAMS__ || {};
            }
            if (prop === 'isReady') {
                return navigationState.isReady;
            }
            
            return undefined;
        }
    });

    // ====================================
    // EXPORTAR API
    // ====================================

    window.SerJSNavigation = NavigationProxy;

})(window, document);
