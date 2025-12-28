# SerJS

Una librería ligera de JavaScript para manipulación del DOM con gestión de estado reactiva, inspirada en React Hooks.

## Instalación

Incluye el archivo en tu HTML:

```html
<script src="path/to/SerJS.js"></script>
```

## Importación

Importa los métodos que necesites mediante desestructuración:

```html
<script>
    const { useRef, useEffect, useState, useMemo, setText, reRender } = SerJS;
</script>
```

---

## Métodos Disponibles

### `useState(initialValue)`

Crea un estado reactivo que puede ser observado y actualizado.

#### Importación
```javascript
const { useState } = SerJS;
```

#### Declaración
```javascript
const [state, setState] = useState(initialValue);
```

#### Parámetros
- `initialValue`: Valor inicial del estado (cualquier tipo)

#### Retorna
- `state`: Objeto getter con la propiedad `current` que contiene el valor actual
- `setState`: Función para actualizar el estado

#### Uso

```javascript
// Crear estado
const [count, setCount] = useState(0);

// Leer valor
console.log(count.current); // 0

// Actualizar valor directo
setCount(5);

// Actualizar con función (recibe valor anterior)
setCount(prev => prev + 1);
```

#### Características especiales
- El getter soporta conversión automática a primitivos
- Puede usarse directamente en operaciones: `count + 1`
- `count.current` siempre retorna el valor actualizado

---

### `useEffect(callback, deps)`

Ejecuta efectos secundarios cuando las dependencias cambian.

#### Importación
```javascript
const { useEffect } = SerJS;
```

#### Declaración
```javascript
useEffect(() => {
    // código del efecto
    return () => {
        // función de limpieza (opcional)
    };
}, [dependencies]);
```

#### Parámetros
- `callback`: Función a ejecutar. Puede retornar una función de limpieza
- `deps`: Array de dependencias (estados creados con `useState`)

#### Uso

```javascript
const [count, setCount] = useState(0);

// Se ejecuta solo una vez al montar
useEffect(() => {
    console.log('Componente montado');
}, []);

// Se ejecuta cuando count cambia
useEffect(() => {
    console.log('Count cambió:', count.current);
}, [count]);

// Con función de limpieza
useEffect(() => {
    const interval = setInterval(() => {
        console.log('Tick');
    }, 1000);
    
    return () => clearInterval(interval);
}, [count]);
```

---

### `useMemo(factory, deps, key)`

Memoriza un valor calculado y solo lo recalcula cuando las dependencias cambian.

#### Importación
```javascript
const { useMemo } = SerJS;
```

#### Declaración
```javascript
const memoizedValue = useMemo(() => {
    return expensiveCalculation();
}, [dependencies], 'optionalKey');
```

#### Parámetros
- `factory`: Función que retorna el valor a memorizar
- `deps`: Array de dependencias (estados creados con `useState`)
- `key`: (Opcional) Clave única para identificar el memo

#### Retorna
Objeto con propiedad `current` que contiene el valor memorizado

#### Uso

```javascript
const [count, setCount] = useState(1);

const double = useMemo(() => {
    console.log('Calculando doble...');
    return count.current * 2;
}, [count]);

console.log(double.current); // 2

// Solo se recalcula cuando count cambia
setCount(5);
console.log(double.current); // 10
```

---

### `useRef(id)`

Crea una referencia a un elemento del DOM y permite adjuntar eventos.

#### Importación
```javascript
const { useRef } = SerJS;
```

#### Declaración
```javascript
const ref = useRef('elementId');
```

#### Parámetros
- `id`: (Opcional) ID del elemento HTML a referenciar

#### Retorna
Objeto ref con:
- `current`: Referencia al elemento DOM
- `onClick(callback)`: Adjunta evento click
- `onChange(callback)`: Adjunta evento change
- `onHover(callback)`: Adjunta evento mouseenter
- `on(eventName, callback)`: Adjunta cualquier evento

#### Uso

```javascript
// Referenciar elemento por ID
const boxRef = useRef('animatedBox');

// Adjuntar eventos
const btnRef = useRef('toggleBtn');
btnRef.onClick(() => {
    console.log('Botón clickeado');
});

btnRef.onChange((e) => {
    console.log('Valor:', e.target.value);
});

btnRef.onHover(() => {
    console.log('Mouse sobre el elemento');
});

// Evento personalizado
btnRef.on('dblclick', () => {
    console.log('Doble click');
});
```

---

### `setText(ref, text)`

Establece el texto de un elemento (textContent).

#### Importación
```javascript
const { setText } = SerJS;
```

#### Declaración
```javascript
setText(ref, text);
```

#### Parámetros
- `ref`: Referencia al elemento (creada con `useRef`)
- `text`: Texto a establecer (se convierte a string)

#### Uso

```javascript
const titleRef = useRef('title');
setText(titleRef, 'Nuevo título');
```

---

### `setHTML(ref, html)`

Establece el HTML interno de un elemento (innerHTML).

#### Importación
```javascript
const { setHTML } = SerJS;
```

#### Declaración
```javascript
setHTML(ref, html);
```

#### Parámetros
- `ref`: Referencia al elemento
- `html`: Código HTML a establecer

#### Uso

```javascript
const { setHTML } = SerJS;
const containerRef = useRef('container');
setHTML(containerRef, '<p>Contenido <strong>HTML</strong></p>');
```

---

### `reRender(ref, state)`

Renderiza una plantilla HTML con variables interpoladas.

#### Importación
```javascript
const { reRender } = SerJS;
```

#### Declaración
```javascript
reRender(ref, stateObject);
```

#### Parámetros
- `ref`: Referencia al elemento
- `state`: Objeto con los valores a interpolar

#### Uso

```html
<!-- HTML con plantilla -->
<div id="animatedBox">
    ¡Hola! Soy una caja animada 🎨 ${count}
</div>
```

```javascript
const boxRef = useRef('animatedBox');
const [count, setCount] = useState(1);

// Renderizar con valores
reRender(boxRef, { count: count.current });

// En useEffect para actualización reactiva
useEffect(() => {
    reRender(boxRef, { count: count.current });
}, [count]);
```

#### Características
- Guarda la plantilla original automáticamente
- Soporta múltiples variables: `${var1}`, `${var2}`
- Los valores se interpolan dinámicamente

---

### `addClass(ref, className)`

Agrega una clase CSS a un elemento.

#### Importación
```javascript
const { addClass } = SerJS;
```

#### Uso

```javascript
const boxRef = useRef('box');
addClass(boxRef, 'active');
```

---

### `removeClass(ref, className)`

Remueve una clase CSS de un elemento.

#### Importación
```javascript
const { removeClass } = SerJS;
```

#### Uso

```javascript
const boxRef = useRef('box');
removeClass(boxRef, 'active');
```

---

### `setAttr(ref, name, value)`

Establece un atributo HTML en un elemento.

#### Importación
```javascript
const { setAttr } = SerJS;
```

#### Parámetros
- `ref`: Referencia al elemento
- `name`: Nombre del atributo
- `value`: Valor del atributo

#### Uso

```javascript
const imgRef = useRef('image');
setAttr(imgRef, 'src', 'image.jpg');
setAttr(imgRef, 'alt', 'Descripción');
```

---

### `setStyle(ref, property, value)`

Establece un estilo CSS inline en un elemento.

#### Importación
```javascript
const { setStyle } = SerJS;
```

#### Parámetros
- `ref`: Referencia al elemento
- `property`: Propiedad CSS (en camelCase)
- `value`: Valor del estilo

#### Uso

```javascript
const boxRef = useRef('box');
setStyle(boxRef, 'backgroundColor', 'red');
setStyle(boxRef, 'fontSize', '20px');
```

---

## Eventos

### `events.onClick(ref, callback)`

Adjunta un evento click a un elemento.

#### Importación
```javascript
const { events } = SerJS;
```

#### Uso

```javascript
const btnRef = useRef('button');
events.onClick(btnRef, () => {
    console.log('Click');
});
```

---

### `events.onChange(ref, callback)`

Adjunta un evento change a un elemento.

#### Uso

```javascript
const inputRef = useRef('input');
events.onChange(inputRef, (e) => {
    console.log('Nuevo valor:', e.target.value);
});
```

---

### `events.onHover(ref, callback)`

Adjunta un evento mouseenter a un elemento.

#### Uso

```javascript
const boxRef = useRef('box');
events.onHover(boxRef, () => {
    console.log('Mouse encima');
});
```

---

### `events.on(eventName, ref, callback)`

Adjunta cualquier evento personalizado a un elemento.

#### Uso

```javascript
const boxRef = useRef('box');
events.on('dblclick', boxRef, () => {
    console.log('Doble click');
});
```

---

## Ejemplo Completo

```html
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SerJS Example</title>
    <script src="SerJS.js"></script>
</head>
<body>
    <div class="box" id="animatedBox">
        ¡Hola! Soy una caja animada 🎨 ${count}
    </div>
    
    <h2 id="countUsers">Lista de Usuarios ${count}</h2>
    
    <button id="toggleBtn">Incrementar</button>

    <script>
        const { useRef, useEffect, useState, useMemo, reRender } = SerJS;

        // Referencias
        const boxRef = useRef('animatedBox');
        const countUsersRef = useRef('countUsers');
        const btnRef = useRef('toggleBtn');

        // Estado
        const [count, setCount] = useState(1);

        // Valor memorizado
        const double = useMemo(() => {
            console.log('Recalculando...');
            return count.current * 2;
        }, [count]);

        // Efecto al montar
        useEffect(() => {
            console.log('Estado inicial:', count.current);
            console.log('Doble:', double.current);
            reRender(boxRef, { count: count.current });
            reRender(countUsersRef, { count: count.current });
        }, []);

        // Efecto reactivo
        useEffect(() => {
            console.log('Estado cambió:', count.current);
            reRender(boxRef, { count: count.current });
            reRender(countUsersRef, { count: count.current });
            console.log('Nuevo doble:', double.current);
        }, [count]);

        // Evento
        btnRef.onClick(() => {
            setCount(prev => prev + 1);
        });
    </script>
</body>
</html>
```

---

## Características Principales

- **Reactivo**: Los estados notifican automáticamente a los efectos suscritos
- **Ligero**: Sin dependencias externas
- **Familiar**: API inspirada en React Hooks
- **Eficiente**: Sistema de cola para operaciones antes del DOM ready
- **Flexible**: Soporta múltiples elementos con una misma referencia

---

## Notas Importantes

1. Los estados creados con `useState` deben accederse mediante `.current`
2. Las dependencias en `useEffect` y `useMemo` deben ser estados de `useState`
3. `reRender` guarda automáticamente la plantilla original en la primera ejecución
4. Todos los métodos de manipulación del DOM esperan que el documento esté listo

---

## Licencia

MIT License
