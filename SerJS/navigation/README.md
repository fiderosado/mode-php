# SerJS Navigation

Sistema de navegación reactivo para SerJS, inspirado en Next.js y React Router, totalmente compatible con el sistema de routing file-based de mode-php.

## 🚀 Importación

### En HTML

```html
<!-- Importar después de SerJS -->
<script src="/SerJS/SerJS.js"></script>
<script src="/SerJS/navigation/navigation.js"></script>
```

### Uso Básico

```javascript
// Forma completa
const { push, back, useRouter } = SerJSNavigation;

// Alias corto
const { push, back, useRouter } = Nav;
```

---

## 📚 API Completa

### 🧭 Navegación Programática

#### `push(path, options)`
Navega a una nueva ruta agregando al historial.

```javascript
// Navegación simple
Nav.push('/blog');

// Con opciones
Nav.push('/blog/post-1', {
    scroll: true,      // Scroll al top (default: true)
    replace: false,    // No reemplazar historial (default: false)
    state: { from: 'home' },  // Estado adicional
    reload: true       // Recargar página (default: true)
});
```

#### `replace(path, options)`
Reemplaza la ruta actual sin agregar al historial.

```javascript
Nav.replace('/login');

Nav.replace('/dashboard', { 
    scroll: false 
});
```

#### `back()`
Navega hacia atrás en el historial.

```javascript
Nav.back();
```

#### `forward()`
Navega hacia adelante en el historial.

```javascript
Nav.forward();
```

#### `go(delta)`
Navega a una posición específica del historial.

```javascript
Nav.go(-2);  // Retrocede 2 páginas
Nav.go(1);   // Avanza 1 página
```

#### `refresh()`
Recarga la página actual.

```javascript
Nav.refresh();
```

#### `prefetch(path)`
Precarga una ruta para navegación más rápida.

```javascript
Nav.prefetch('/blog');
```

---

### 🔀 Redirecciones

#### `redirect(path, statusCode)`
Redirige a una ruta con código de estado.

```javascript
Nav.redirect('/login', 302);
```

#### `permanentRedirect(path)`
Redirección permanente (301).

```javascript
Nav.permanentRedirect('/new-url');
```

#### `temporaryRedirect(path)`
Redirección temporal (307).

```javascript
Nav.temporaryRedirect('/maintenance');
```

---

### 🎣 Hooks

#### `usePathname()`
Obtiene el pathname actual.

```javascript
const pathname = Nav.usePathname();
console.log(pathname); // "/blog/post-1"
```

#### `useSearchParams()`
Obtiene los search params como URLSearchParams.

```javascript
const searchParams = Nav.useSearchParams();
console.log(searchParams.get('id')); // "123"
console.log(searchParams.get('filter')); // "active"

// Iterar
for (const [key, value] of searchParams) {
    console.log(`${key}: ${value}`);
}
```

#### `useParams()`
Obtiene los parámetros dinámicos de la ruta (inyectados desde PHP).

```javascript
// Ruta: /blog/[slug]
// URL: /blog/mi-post

const params = Nav.useParams();
console.log(params.slug); // "mi-post"
```

#### `useRouter()`
Obtiene el router completo con toda la información y métodos.

```javascript
const router = Nav.useRouter();

console.log(router.pathname);  // "/blog/post-1"
console.log(router.query);     // { id: "123", filter: "active" }
console.log(router.params);    // { slug: "post-1" }
console.log(router.asPath);    // "/blog/post-1?id=123#comments"

// Métodos
router.push('/about');
router.back();
router.isActive('/blog');
```

#### `useQuery()`
Obtiene los query params como objeto.

```javascript
const query = Nav.useQuery();
console.log(query); // { id: "123", filter: "active" }
```

#### `useHash()`
Obtiene el hash actual.

```javascript
const hash = Nav.useHash();
console.log(hash); // "#comments"
```

---

### 🛠️ Utilidades

#### `isActive(path, exact)`
Verifica si una ruta está activa.

```javascript
// Coincidencia parcial
Nav.isActive('/blog'); // true si está en /blog o /blog/post-1

// Coincidencia exacta
Nav.isActive('/blog', true); // solo true si está en /blog exactamente
```

#### `buildUrl(path, params)`
Construye una URL con query params.

```javascript
const url = Nav.buildUrl('/blog', { 
    category: 'tech',
    page: 2 
});
console.log(url); // "/blog?category=tech&page=2"
```

#### `getQueryParam(key)`
Obtiene un query param específico.

```javascript
// URL: /blog?id=123&filter=active
const id = Nav.getQueryParam('id');
console.log(id); // "123"
```

#### `getAllQueryParams()`
Obtiene todos los query params como objeto.

```javascript
const params = Nav.getAllQueryParams();
console.log(params); // { id: "123", filter: "active" }
```

#### `setQueryParams(params, merge)`
Establece query params sin recargar la página.

```javascript
// Agregar/actualizar params
Nav.setQueryParams({ 
    page: 2,
    sort: 'date' 
}, true);

// Reemplazar todos los params
Nav.setQueryParams({ 
    view: 'grid' 
}, false);

// Eliminar un param (establecer como null)
Nav.setQueryParams({ 
    filter: null 
});
```

#### `matchPath(pattern, path)`
Verifica si una ruta coincide con un patrón.

```javascript
Nav.matchPath('/blog/:slug'); // true si la ruta actual es /blog/algo
Nav.matchPath('/users/:id/posts/:postId');
```

---

### 🎨 Componentes

#### `Link(props)`
Crea un elemento `<a>` con navegación interceptada.

```javascript
const link = Nav.Link({
    href: '/blog',
    children: 'Ir al Blog',
    className: 'nav-link',
    activeClassName: 'active',
    exact: false,
    prefetch: true,
    replace: false,
    scroll: true,
    target: '_self'
});

document.body.appendChild(link);
```

**Propiedades:**
- `href`: Ruta destino (requerido)
- `children`: Contenido del link (string o Node)
- `className`: Clase CSS
- `activeClassName`: Clase cuando está activo
- `exact`: Coincidencia exacta para active
- `prefetch`: Precargar en hover
- `replace`: Usar replace en vez de push
- `scroll`: Scroll al top al navegar
- `target`: Target del link
- `...attrs`: Cualquier otro atributo HTML

#### `createNavLinks(items)`
Crea múltiples links de navegación.

```javascript
const navFragment = Nav.createNavLinks([
    { href: '/', children: 'Home' },
    { href: '/blog', children: 'Blog' },
    { href: '/about', children: 'About', exact: true }
]);

document.querySelector('nav').appendChild(navFragment);
```

#### `NavBar(config)`
Crea una barra de navegación completa.

```javascript
const navbar = Nav.NavBar({
    className: 'navbar',
    activeClassName: 'nav-active',
    items: [
        { href: '/', children: 'Home', exact: true },
        { href: '/blog', children: 'Blog' },
        { href: '/portfolio', children: 'Portfolio' },
        { href: '/contact', children: 'Contacto' }
    ]
});

document.body.appendChild(navbar);
```

---

### 📡 Sistema de Eventos

#### `events.on(eventName, callback)`
Escucha eventos de navegación.

```javascript
// Cuando la ruta cambia
const unsubscribe = Nav.events.on('routeChange', (data) => {
    console.log('Nueva ruta:', data.pathname);
    console.log('Query:', data.query);
});

// Cuando los query params cambian
Nav.events.on('queryChange', (data) => {
    console.log('Nuevos params:', data.query);
});

// Cuando el sistema está listo
Nav.events.on('ready', () => {
    console.log('Navigation ready!');
});

// Desuscribirse
unsubscribe();
```

#### `events.off(eventName, callback)`
Deja de escuchar un evento.

```javascript
const handler = (data) => console.log(data);
Nav.events.on('routeChange', handler);
Nav.events.off('routeChange', handler);
```

#### `events.once(eventName, callback)`
Escucha un evento una sola vez.

```javascript
Nav.events.once('routeChange', (data) => {
    console.log('Primera navegación:', data.pathname);
});
```

#### `events.emit(eventName, data)`
Emite un evento personalizado.

```javascript
Nav.events.emit('customEvent', { foo: 'bar' });
```

---

### 📜 Historial

#### `history.get()`
Obtiene todo el historial.

```javascript
const history = Nav.history.get();
console.log(history);
// [
//   { path: '/', search: '', hash: '', timestamp: 1234567890 },
//   { path: '/blog', search: '?page=1', hash: '', timestamp: 1234567891 }
// ]
```

#### `history.clear()`
Limpia el historial guardado.

```javascript
Nav.history.clear();
```

#### `history.getLength()`
Obtiene la cantidad de entradas.

```javascript
const count = Nav.history.getLength();
console.log(count); // 5
```

#### `history.getLast(n)`
Obtiene las últimas N entradas.

```javascript
const last3 = Nav.history.getLast(3);
```

---

### 📊 Propiedades del Estado

Acceso directo al estado actual:

```javascript
console.log(Nav.pathname);  // "/blog/post-1"
console.log(Nav.search);    // "?id=123"
console.log(Nav.hash);      // "#comments"
console.log(Nav.query);     // { id: "123" }
console.log(Nav.params);    // { slug: "post-1" }
console.log(Nav.isReady);   // true
```

---

## 💡 Ejemplos de Uso

### Ejemplo 1: Navegación Básica

```html
<button id="goToBlog">Ir al Blog</button>
<button id="goBack">Volver</button>

<script>
    const btnBlog = document.getElementById('goToBlog');
    const btnBack = document.getElementById('goBack');
    
    btnBlog.addEventListener('click', () => {
        Nav.push('/blog');
    });
    
    btnBack.addEventListener('click', () => {
        Nav.back();
    });
</script>
```

### Ejemplo 2: Links Dinámicos

```javascript
const posts = [
    { id: 1, title: 'Post 1', slug: 'post-1' },
    { id: 2, title: 'Post 2', slug: 'post-2' },
    { id: 3, title: 'Post 3', slug: 'post-3' }
];

const container = document.getElementById('posts');

posts.forEach(post => {
    const link = Nav.Link({
        href: `/blog/${post.slug}`,
        children: post.title,
        className: 'post-link',
        activeClassName: 'active-post',
        prefetch: true
    });
    
    container.appendChild(link);
});
```

### Ejemplo 3: Navegación con Query Params

```javascript
// Establecer filtros
function setFilters(category, sort) {
    Nav.setQueryParams({
        category,
        sort,
        page: 1  // Reset page
    });
}

// Leer filtros actuales
const query = Nav.useQuery();
console.log('Categoría:', query.category);
console.log('Ordenar por:', query.sort);
console.log('Página:', query.page);

// Cambiar solo la página (merge: true)
Nav.setQueryParams({ page: 2 }, true);
```

### Ejemplo 4: Navbar Reactiva

```javascript
const navbar = Nav.NavBar({
    className: 'flex gap-4 p-4 bg-gray-800',
    activeClassName: 'text-blue-500 font-bold',
    items: [
        { 
            href: '/', 
            children: 'Home', 
            exact: true,
            className: 'text-white hover:text-blue-400'
        },
        { 
            href: '/blog', 
            children: 'Blog',
            className: 'text-white hover:text-blue-400',
            prefetch: true
        },
        { 
            href: '/about', 
            children: 'About',
            className: 'text-white hover:text-blue-400'
        }
    ]
});

document.querySelector('header').appendChild(navbar);
```

### Ejemplo 5: Router con useEffect

```javascript
const { useRef, useEffect, useState } = SerJS;
const { useRouter, events } = Nav;

const contentRef = useRef('content');
const [route, setRoute] = useState(Nav.pathname);

// Escuchar cambios de ruta
events.on('routeChange', (data) => {
    setRoute(data.pathname);
});

// Reaccionar a cambios
useEffect(() => {
    console.log('Ruta cambió a:', route);
    
    // Actualizar contenido basado en la ruta
    if (route === '/') {
        Ser.setText(contentRef, 'Estás en Home');
    } else if (route.startsWith('/blog')) {
        Ser.setText(contentRef, 'Estás en el Blog');
    }
}, [route]);
```

### Ejemplo 6: Paginación

```javascript
function Pagination({ currentPage, totalPages }) {
    const container = document.createElement('div');
    container.className = 'pagination flex gap-2';
    
    // Botón anterior
    const prevBtn = Nav.Link({
        href: Nav.buildUrl(Nav.pathname, { 
            ...Nav.query, 
            page: Math.max(1, currentPage - 1) 
        }),
        children: '← Anterior',
        className: currentPage === 1 ? 'disabled' : 'enabled',
        scroll: false
    });
    
    // Botones de páginas
    for (let i = 1; i <= totalPages; i++) {
        const pageBtn = Nav.Link({
            href: Nav.buildUrl(Nav.pathname, { 
                ...Nav.query, 
                page: i 
            }),
            children: String(i),
            className: 'page-btn',
            activeClassName: 'active-page',
            exact: false,
            scroll: false
        });
        container.appendChild(pageBtn);
    }
    
    // Botón siguiente
    const nextBtn = Nav.Link({
        href: Nav.buildUrl(Nav.pathname, { 
            ...Nav.query, 
            page: Math.min(totalPages, currentPage + 1) 
        }),
        children: 'Siguiente →',
        className: currentPage === totalPages ? 'disabled' : 'enabled',
        scroll: false
    });
    
    container.appendChild(prevBtn);
    container.appendChild(nextBtn);
    
    return container;
}
```

### Ejemplo 7: Protección de Rutas

```javascript
const protectedRoutes = ['/dashboard', '/profile', '/settings'];

Nav.events.on('routeChange', (data) => {
    const isProtected = protectedRoutes.some(route => 
        data.pathname.startsWith(route)
    );
    
    if (isProtected && !isUserLoggedIn()) {
        Nav.redirect('/login');
    }
});
```

---

## 🔗 Integración con mode-php

### Inyectar Parámetros desde PHP

```php
<!-- app/blog/[slug]/page.php -->
<?php
$slug = $params['slug'] ?? '';
?>

<script>
    // Inyectar params en el cliente
    window.__ROUTE_PARAMS__ = <?= json_encode($params) ?>;
</script>

<script>
    // Usar los params en el cliente
    const params = Nav.useParams();
    console.log('Slug:', params.slug);
</script>
```

### Navegación desde PHP a JavaScript

```php
<!-- app/layout.php -->
<script>
    // Estado inicial desde PHP
    window.__INITIAL_STATE__ = {
        pathname: '<?= $_SERVER['REQUEST_URI'] ?>',
        user: <?= json_encode($user ?? null) ?>
    };
</script>

<script src="/SerJS/navigation/navigation.js"></script>
<script>
    console.log('Ruta actual:', window.__INITIAL_STATE__.pathname);
</script>
```

---

## ⚡ Características Destacadas

### ✅ Ventajas del Proxy

- **Acceso unificado**: Todos los métodos y propiedades en un solo objeto
- **Auto-binding**: Los métodos mantienen su contexto automáticamente
- **Intellisense amigable**: Mejor autocompletado en editores
- **Separación lógica**: El código interno está organizado por módulos

### ✅ Sistema de Eventos Robusto

- Soporte para múltiples listeners
- Desuscripción automática con `once`
- Return de función de limpieza
- Eventos personalizados

### ✅ Optimizaciones

- Historial limitado a 100 entradas (previene memory leaks)
- Prefetch inteligente (no duplica links)
- Active class automático
- Lazy event listeners

---

## 🎯 Mejores Prácticas

1. **Usa `useRouter()` para acceso completo**
```javascript
const router = Nav.useRouter();
// En lugar de múltiples llamadas
```

2. **Prefetch en hover para mejor UX**
```javascript
Nav.Link({ href: '/blog', prefetch: true })
```

3. **Limpia listeners cuando no los necesites**
```javascript
const unsubscribe = Nav.events.on('routeChange', handler);
// Cuando termines:
unsubscribe();
```

4. **Usa `exact: true` para rutas específicas**
```javascript
Nav.Link({ href: '/', exact: true })
```

5. **Combina con SerJS para reactividad total**
```javascript
const [route] = SerJS.useState(Nav.pathname);
Nav.events.on('routeChange', (data) => {
    setRoute(data.pathname);
});
```

---

## 📝 Notas Importantes

- ⚠️ Por defecto, `push()` recarga la página para que mode-php maneje el routing
- ⚠️ Para SPA puro, usa `{ reload: false }` en las opciones
- ⚠️ Los parámetros dinámicos deben ser inyectados desde PHP en `window.__ROUTE_PARAMS__`
- ⚠️ El historial se limita a 100 entradas para evitar memory leaks

---

## 🚀 Performance

- **Bundle size**: ~8KB minificado
- **Zero dependencies**: Solo requiere SerJS
- **Lazy loading**: Prefetch opcional
- **Memory efficient**: Historial limitado

---

## 📄 Licencia

MIT License - Compatible con mode-php y SerJS
