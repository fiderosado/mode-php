# Suspense (PHP) + SerJSSuspense (JavaScript)

Componente de carga progresiva que integra:
- Lado servidor (PHP): genera el contenedor y define las Server Actions que devuelven HTML.
- Lado cliente (JS): detecta los contenedores, ejecuta la acción correspondiente y actualiza el DOM.

Este patrón permite mostrar un fallback inmediato y reemplazarlo con contenido dinámico cuando el servidor responde, similar a React Suspense pero pensado para PHP.

---

## Flujo General
- PHP renderiza un contenedor `<div>` con atributos `data-suspense`, `data-action` y `data-target`.
- Al cargar la página, SerJS inicializa SerJSSuspense y registra todos los nodos suspense.
- Cada instancia realiza una primera llamada automática a su Server Action, renderizando el resultado.
- En cualquier momento, desde JS se puede invocar manualmente `instance.call(payload)` para refrescar el contenido.

---

## Lado Servidor (PHP)

### Contenedor HTML con Suspense
Se usa `Core\Html\Suspense` junto a `Core\SuspenseAction` para renderizar el contenedor con fallback y metadatos necesarios:

```php
use Core\Html\Elements\Div;
use Core\Html\Suspense;
use Core\SuspenseAction;

Suspense::in(
    Div::in("editor"),
    SuspenseAction::in("editor-suspense")
)->class('p-2')->render();

Suspense::in(
    Div::in("preparando un hola"),
    SuspenseAction::in("hola-suspense")
)->class('p-2')->render();
```

Código de referencia:
- [page.php:L27-35](/example/blog/%5Bslug%5D/editor/page.php#L27-L35)

El componente agrega atributos `data-*` para que el cliente identifique y opere cada instancia:
- `data-suspense`: hash único de la acción generado por el servidor. Identifica de forma única la acción y se usa para cachear el proxy en `window.__SerActions__`.
- `data-action`: nombre de la Server Action (ej: `"editor-suspense"`). Usado para identificar qué método llamar cuando se invoca la acción.
- `data-target`: id interno del elemento objetivo. Referencia única del contenedor que será actualizado con el contenido dinámico.

**Ejemplo HTML generado:**
```html
<div data-suspense="abc123xyz" data-action="hola-suspense" data-target="suspense-001" class="p-2">
    preparando un hola
</div>
```

Implementación:
- [Suspense.php](file:///d:/GitHub/mode-php/core/Html/Suspense.php)
- [SuspenseAction.php](file:///d:/GitHub/mode-php/core/SuspenseAction.php)

### Definir Server Actions
Cada acción devuelve un fragmento HTML. Se define con `Core\Http\ServerAction::define` y retorna `Core\Http\HttpResponse::html(...)`:

```php
use Core\Html\Elements\Div;
use Core\Http\HttpResponse;
use Core\Http\ServerAction;

ServerAction::define("editor-suspense", function ($data, $params) {
    return HttpResponse::html(
        Div::in("El edito form ->", $data["id"])->class("p-4 bg-green-500 text-white")
    );
});

ServerAction::define("hola-suspense", function ($data, $params) {
    return HttpResponse::html(
        Div::in("Hola Mundo ->", $data["id"])->class("p-4 bg-indigo-500 text-white")
    );
});
```

Código de referencia:
- [actions.php:L7-17](file:///d:/GitHub/mode-php/app/example/blog/%5Bslug%5D/editor/actions.php#L7-L17)

ServerAction valida existencia y alcance de la ruta al ejecutar:
- [ServerAction.php](file:///d:/GitHub/mode-php/core/Http/ServerAction.php)

#### Parámetros de la Función Callback

La función callback de `ServerAction::define` recibe dos parámetros:

**`$data` (array) - Datos del Cliente**
- Contiene el payload enviado desde JavaScript mediante `instance.call(payload)`
- Estructura: Los datos se pasan tal como se envían desde el cliente
- Ejemplo desde JS:
  ```javascript
  await s.call({ id: "user-123", name: "Juan" })
  ```
- En PHP accesible como:
  ```php
  $data["id"]    // "user-123"
  $data["name"]  // "Juan"
  ```

**`$params` (array) - Contexto de la Solicitud**
- Contiene metadatos y contexto de la solicitud del servidor
- Típicamente incluye:
  - Parámetros de ruta (slug, id dinámicos)
  - Información del contexto de ejecución
  - Metadatos de la solicitud HTTP
- Ejemplo de uso:
  ```php
  ServerAction::define("load-post", function ($data, $params) {
      // $params podría contener: ["slug" => "mi-post"]
      $postId = $data["id"];
      $slug = $params["slug"] ?? null;
      
      // Cargar post del DB...
      return HttpResponse::html(...);
  });
  ```

#### Mejores Prácticas
- Valida siempre `$data` antes de usarlo
- Usa `$data` para datos enviados por el usuario
- Usa `$params` para contexto de rutas y configuración
- Evita lógica pesada dentro de la acción; delégala a servicios o modelos
- Retorna siempre HTML válido y autocontenido

---

## Lado Cliente (JavaScript)

### Inicialización Automática
SerJS carga el módulo y registra todos los nodos suspense al iniciar:
- [SerJS.js:L362-L383](file:///d:/GitHub/mode-php/SerJS/SerJS.js#L362-L383)

El módulo cliente gestiona cada instancia:
- [SerJSSuspense.js](file:///d:/GitHub/mode-php/SerJS/core/SerJSSuspense.js)

**Flujo de inicialización:**
1. Cuando SerJS carga, automáticamente llama a `SerJSSuspense.registerAll()`
2. Se buscan todos los elementos con `[data-suspense][data-action]`
3. Para cada elemento, se crea una `SuspenseInstance`
4. Se obtiene el proxy de acciones del servidor (cacheado en `window.__SerActions__`)
5. Se realiza la **primera carga automática** con `instance.call()`

**Nota sobre timing:** El registro es **asíncrono**. Si necesitas acceder a una instancia inmediatamente después del DOM estar listo, usa `await`:

```javascript
// Correcto - esperar a que se registre
const instance = SerJS.suspense.action("mi-accion");

// O mejor - en un evento que ocurra después del registro
botonRef.onClick(async () => {
    const instance = SerJS.suspense.action("mi-accion");
    // ...
});
```

### API de SerJSSuspense

| Método | Parámetro | Retorna | Descripción |
|--------|-----------|---------|-------------|
| `registerAll()` | — | `Promise<void>` | Detecta `[data-suspense][data-action]`, crea instancias y realiza primera carga automática. Llamado automáticamente por SerJS. |
| `action(name)` | `string` nombre de acción | `SuspenseInstance \| null` | Obtiene la instancia registrada por nombre. Retorna `null` si no existe. |
| `has(name)` | `string` nombre de acción | `boolean` | Verifica si una acción está registrada. |
| `remove(name)` | `string` nombre de acción | `boolean` | Elimina una instancia del registro. Útil para limpiar Suspense dinámicos. |
| `getAll()` | — | `Map<string, SuspenseInstance>` | Retorna el mapa completo de instancias registradas. |

**Métodos de SuspenseInstance:**

| Método | Parámetro | Retorna | Descripción |
|--------|-----------|---------|-------------|
| `call(payload)` | `object` datos opcionales | `Promise<void>` | Invoca la Server Action con los datos. Reemplaza el DOM con la respuesta. |
| `reset()` | — | `void` | Restaura el contenido al fallback original. |
| `setFallback(html)` | `string` HTML | `void` | Cambia el contenido de fallback. |
| `getFallback()` | — | `string` | Retorna el HTML del fallback actual. |
| `getElement()` | — | `HTMLElement` | Retorna el elemento DOM asociado. |

### Uso desde la página

Ejemplo de interacción manual para refrescar el contenido de una acción:

```html
<button type="button" id="obtener-fallback" class="bg-black px-4 py-2 text-white">
    Obtener Fallback
</button>
<script>
    const { useRef, suspense } = SerJS;

    const botonRef = useRef("obtener-fallback");
    botonRef.onClick(async () => {
        // Obtener instancia de suspense
        const s = suspense.action("hola-suspense");
        
        // Verificar que exista
        if (!s) {
            console.warn("Suspense 'hola-suspense' no encontrado");
            return;
        }
        
        // Llamar con payload
        await s.call({
            id: Math.random().toString(36).substring(2, 10)
        });
    });
</script>
```

Código de referencia:
- [page.php:L39-54](file:///d:/GitHub/mode-php/app/example/blog/%5Bslug%5D/editor/page.php#L39-L54)

#### Ejemplos Adicionales

**Refrescar con datos específicos:**
```javascript
const instance = suspense.action("mi-accion");
await instance.call({
    userId: 123,
    filter: "activos",
    page: 2
});
```

**Limpiar y resetear:**
```javascript
const instance = suspense.action("mi-accion");
instance.reset();  // Vuelve al fallback original
```

**Cambiar fallback dinámicamente:**
```javascript
const instance = suspense.action("mi-accion");
instance.setFallback("<p>Nuevo fallback...</p>");
await instance.call();
```

**Eliminar una acción del registro:**
```javascript
const removed = suspense.remove("mi-accion");
if (removed) {
    console.log("Acción eliminada");
}
```

---

## Ejemplo Completo
- Contenedor y acciones en: [page.php](file:///d:/GitHub/mode-php/app/example/blog/%5Bslug%5D/editor/page.php)
- Acciones de servidor: [actions.php](file:///d:/GitHub/mode-php/app/example/blog/%5Bslug%5D/editor/actions.php)
- Cliente: [SerJSSuspense.js](file:///d:/GitHub/mode-php/SerJS/core/SerJSSuspense.js)

Este ejemplo renderiza dos instancias:
- `editor-suspense`: muestra un fragmento dinámico con estilo verde.
- `hola-suspense`: muestra un fragmento dinámico con estilo índigo y se puede refrescar desde el botón.

---

## Manejo de Errores

### Errores en el Cliente
Si ocurre un error durante `instance.call()`, se mostrará automáticamente:
```
Error cargando contenido
```

Para obtener detalles, revisa la consola del navegador (F12 > Console).

### Errores Comunes

**"Suspense no encontrado"**
- Verificar que el nombre de la acción es correcto
- Asegurar que `registerAll()` se haya completado
- Revisar que el elemento HTML tenga los atributos `data-suspense` y `data-action`

**Timeout o sin respuesta del servidor**
- Revisar la consola del servidor para errores en la acción PHP
- Verificar que `ServerAction::define()` está definido en `actions.php`
- Asegurar que la ruta está correctamente configurada

**DOM no se actualiza**
- Verificar que `HttpResponse::html()` retorna HTML válido
- Revisar que no hay conflictos con otros scripts que modifiquen el DOM
- Usar `getElement()` para inspeccionar el estado del contenedor

---

## Notas y Mejores Prácticas

### En el Lado Servidor (PHP)
- **Fragmentos pequeños:** Mantén los fragmentos HTML pequeños y autocontenidos. Evita HTML muy complejo.
- **Contenido dinámico:** Usa `$data` para personalizar el contenido según la solicitud del cliente.
- **Validación:** Valida siempre `$data` antes de usarlo en consultas o lógica crítica.
- **Estilos rápidos:** Usa clases utilitarias (como Tailwind) para estilos dentro del fragmento.
- **Sin lógica pesada:** Evita operaciones costosas (DB queries complejas) dentro de la acción. Delega a servicios.
- **Context awareness:** Usa `$params` para acceder a contexto de ruta y metadatos de la solicitud.

### En el Lado Cliente (JavaScript)
- **Timing:** Si necesitas acceder a instancias inmediatamente, espera a que `registerAll()` se complete (usualmente ocurre en milisegundos).
- **Errores:** Siempre verifica que `action()` retorna una instancia válida antes de llamar `call()`.
- **Payload:** Aprovecha `payload` en `instance.call(payload)` para pasar datos contextuales de forma eficiente.
- **Limpieza:** Usa `remove()` cuando destruyas dinámicamente contenedores Suspense para evitar memory leaks.
- **Fallback:** El fallback se restaura automáticamente antes de cada carga. No necesitas manejarlo manualmente.

### Consideraciones Generales
- **Caching:** Las acciones se cachean en `window.__SerActions__` automáticamente. Una solicitud HTTP por hash único de acción.
- **Múltiples instancias:** Puedes tener múltiples instancias de la misma acción. Cada una mantiene su propio estado.
- **Streaming:** Si tu servidor limita el streaming HTTP, puedes operar en modo completamente asíncrono sin problemas.
- **SEO:** Los fallbacks se renderizan en HTML inicial. El contenido dinámico se carga después. Ten esto en cuenta para SEO.

