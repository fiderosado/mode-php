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

        useSearchParams() {
            return new URLSearchParams(navigationState.currentSearch);
        },

        useParams() {
            return window.__ROUTE_PARAMS__ || {};
        },

        useRouter() {
            return {
                pathname: navigationState.currentPath,
                query: Object.fromEntries(new URLSearchParams(navigationState.currentSearch)),
                params: this.useParams(),
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
    // COMPONENTES
    // ====================================

    const components = {
        Link(props) {
            const {
                href,
                children,
                className = '',
                activeClassName = 'active',
                exact = false,
                prefetch: shouldPrefetch = false,
                replace: shouldReplace = false,
                scroll = true,
                target,
                ...attrs
            } = props;

            const link = document.createElement('a');
            link.href = href;
            link.className = className;
            
            if (target) {
                link.target = target;
            }
            
            // Active class
            if (utilities.isActive(href, exact)) {
                link.classList.add(activeClassName);
            }

            // Children
            if (typeof children === 'string') {
                link.textContent = children;
            } else if (children instanceof Node) {
                link.appendChild(children);
            }

            // Additional attributes
            Object.entries(attrs).forEach(([key, value]) => {
                link.setAttribute(key, String(value));
            });

            // Prefetch on hover
            if (shouldPrefetch) {
                link.addEventListener('mouseenter', () => {
                    navigation.prefetch(href);
                }, { once: true });
            }

            // Intercept clicks
            link.addEventListener('click', (e) => {
                // Allow special clicks
                if (
                    target === '_blank' ||
                    e.ctrlKey || 
                    e.shiftKey || 
                    e.altKey || 
                    e.metaKey ||
                    e.button !== 0
                ) {
                    return;
                }

                e.preventDefault();
                
                if (shouldReplace) {
                    navigation.replace(href, { scroll });
                } else {
                    navigation.push(href, { scroll });
                }
            });

            return link;
        },

        createNavLinks(items) {
            const fragment = document.createDocumentFragment();
            
            items.forEach(item => {
                const link = this.Link(item);
                fragment.appendChild(link);
            });
            
            return fragment;
        },

        NavBar(config) {
            const { items, className = '', activeClassName = 'active' } = config;
            const nav = document.createElement('nav');
            nav.className = className;
            
            items.forEach(item => {
                const link = this.Link({
                    ...item,
                    activeClassName
                });
                nav.appendChild(link);
            });
            
            return nav;
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
            
            // Componentes
            if (prop in components) {
                return components[prop].bind(components);
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

    // Alias corto
    window.SerNav = NavigationProxy;

})(window, document);
