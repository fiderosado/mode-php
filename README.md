# mode-php

Un framework PHP moderno y minimalista enfocado en la **máxima natividad**, **reactividad** y **velocidad**. Mode-PHP elimina las abstracciones innecesarias para ofrecer una experiencia de desarrollo fluida, directa y altamente performante.

## 🎯 Filosofía del Proyecto

Mode-PHP nace de la necesidad de un framework que sea:

- **🔥 Nativo**: Sin abstracciones pesadas. PHP puro y directo donde más importa.
- **⚡ Rápido**: Arquitectura ligera optimizada para velocidad máxima.
- **♻️ Reactivo**: Integración perfecta con SerJS para UI reactivas sin complejidad.
- **🧩 Modular**: Componentes reutilizables y estructura clara basada en Next.js.
- **🎨 Moderno**: Tailwind CSS v4 integrado para estilos modernos y eficientes.

## 🏗️ Arquitectura

### Sistema de Routing File-Based

Inspirado en Next.js, mode-php utiliza un sistema de routing basado en la estructura de archivos. Simple, predecible y poderoso:

```
app/
├── page.php                 # Ruta: /
├── blog/
│   ├── page.php            # Ruta: /blog
│   └── [slug]/
│       └── page.php        # Ruta: /blog/:slug (dinámico)
├── api/
│   └── auth/
│       └── page.php        # Ruta: /api/auth
└── layout.php              # Layout compartido
```

### Características Clave

**📁 File-System Routing**
- Cada `page.php` define una ruta automáticamente
- Soporte para rutas dinámicas con `[param]`
- Layouts anidados con cascada automática
- Sin configuración manual de rutas

**🎨 Componentes Nativos**
- Sistema de componentes PHP en `components/`
- Reutilización sin overhead
- Props y composición simple
- HTML semántico generado

**⚛️ Reactividad con SerJS**
- Estados reactivos (`useState`)
- Efectos secundarios (`useEffect`)
- Referencias al DOM (`useRef`)
- Memoización (`useMemo`)
- Render dinámico sin Virtual DOM

**🎯 Zero Config**
- Sin archivos de configuración complejos
- Convención sobre configuración
- Auto-discovery de rutas y layouts
- Tailwind CSS pre-configurado

## 🚀 Inicio Rápido

### Instalación

```bash
git clone https://github.com/tu-usuario/mode-php.git
cd mode-php
composer install
```

### Configuración

1. Copia el archivo `.env`:
```bash
cp .env.example .env
```

2. Configura tu servidor web (Apache/Nginx) apuntando a `index.php`

3. ¡Listo! Accede a `http://localhost`

## 📖 Guía de Uso

### Crear una Página Simple

```php
<!-- app/page.php -->
<div class="container mx-auto p-8">
    <h1 class="text-4xl font-bold">¡Hola Mode-PHP!</h1>
    <p class="text-gray-600">Framework nativo y reactivo</p>
</div>
```

### Crear una Ruta Dinámica

```php
<!-- app/blog/[slug]/page.php -->
<?php
// Los parámetros están disponibles en $params
$slug = $params['slug'] ?? 'default';
?>

<article class="prose lg:prose-xl">
    <h1>Post: <?= htmlspecialchars($slug) ?></h1>
    <p>Contenido del post...</p>
</article>
```

### Usar Layouts

```php
<!-- app/layout.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mode-PHP</title>
    <link href="/app/css/tailwind.css" rel="stylesheet">
    <script src="/SerJS/SerJS.js"></script>
</head>
<body class="bg-gray-50">
    <nav class="bg-white shadow">
        <!-- Navegación -->
    </nav>
    
    <main>
        <?php require $GLOBALS['page']; ?>
    </main>
    
    <footer class="mt-auto">
        <!-- Footer -->
    </footer>
</body>
</html>
```

### Componentes Reutilizables

```php
<!-- components/header/StandardHeader.php -->
<?php
namespace Components\Header;

class StandardHeader {
    public static function render($title, $subtitle = '') {
        ?>
        <header class="bg-gradient-to-r from-blue-500 to-purple-600 text-white p-8">
            <h1 class="text-5xl font-bold"><?= htmlspecialchars($title) ?></h1>
            <?php if ($subtitle): ?>
                <p class="text-xl mt-2"><?= htmlspecialchars($subtitle) ?></p>
            <?php endif; ?>
        </header>
        <?php
    }
}
```

**Uso:**
```php
<?php
use Components\Header\StandardHeader;

StandardHeader::render('Mi Título', 'Subtítulo opcional');
?>
```

### Reactividad con SerJS

```php
<!-- app/dashboard/page.php -->
<script src="/SerJS/SerJS.js"></script>

<div id="counter" class="p-8">
    Contador: ${count}
</div>

<button id="btnIncrement" class="bg-blue-500 text-white px-4 py-2 rounded">
    Incrementar
</button>

<script>
    const { useRef, useState, useEffect, reRender } = SerJS;
    
    // Estado reactivo
    const [count, setCount] = useState(0);
    
    // Referencias
    const counterRef = useRef('counter');
    const btnRef = useRef('btnIncrement');
    
    // Efecto reactivo
    useEffect(() => {
        reRender(counterRef, { count: count.current });
    }, [count]);
    
    // Evento
    btnRef.onClick(() => {
        setCount(prev => prev + 1);
    });
</script>
```

## 🛠️ Estructura del Proyecto

```
mode-php/
├── app/                    # Aplicación (file-based routing)
│   ├── page.php           # Página principal
│   ├── layout.php         # Layout raíz
│   ├── blog/              # Ruta /blog
│   │   ├── page.php
│   │   └── [slug]/
│   │       └── page.php   # Ruta dinámica
│   ├── api/               # API routes
│   └── css/               # CSS compilado
│
├── components/            # Componentes reutilizables
│   ├── header/
│   │   └── StandardHeader.php
│   └── navbar/
│
├── core/                  # Núcleo del framework
│   ├── App.php           # Clase App principal
│   ├── Router.php        # Sistema de routing
│   ├── Resolver.php      # Resolución de rutas
│   ├── Render.php        # Sistema de renderizado
│   ├── Html/             # Generadores HTML nativos
│   ├── Http/             # Utilidades HTTP
│   ├── Security/         # JWT y seguridad
│   └── Tailwindcss/      # Integración Tailwind
│
├── SerJS/                 # Framework reactivo JS
│   ├── SerJS.js          # Librería principal
│   ├── README.md         # Documentación SerJS
│   └── core/
│
├── vendor/                # Dependencias Composer
├── .env                   # Variables de entorno
├── .htaccess             # Configuración Apache
├── composer.json         # Dependencias PHP
├── index.php             # Entry point
└── README.md             # Este archivo
```

## 📦 Dependencias

### PHP Dependencies (Composer)
- **nesbot/carbon**: Manejo avanzado de fechas
- **vlucas/phpdotenv**: Variables de entorno
- **firebase/php-jwt**: JSON Web Tokens
- **tailwindphp/tailwindphp**: Compilador Tailwind CSS

### JavaScript Dependencies
- **SerJS**: Framework reactivo nativo (incluido)

## 🎨 Tailwind CSS v4

Mode-PHP incluye Tailwind CSS v4 pre-configurado:

```bash
# Compilar CSS (modo desarrollo)
./vendor/bin/tailwindphp --input=input.css --output=app/css/tailwind.css --watch

# Compilar CSS (producción)
./vendor/bin/tailwindphp --input=input.css --output=app/css/tailwind.css --minify
```

## 🔐 Seguridad

- **JWT**: Autenticación basada en tokens
- **CSRF**: Protección incluida
- **XSS**: Escapado automático en componentes
- **SQL Injection**: Preparación de consultas
- **Environment Variables**: Configuración sensible en `.env`

## 🌟 Ventajas vs Otros Frameworks

| Característica | Mode-PHP | Laravel | Symfony |
|---------------|----------|---------|---------|
| **Velocidad** | ⚡ Ultra rápido | Medio | Medio |
| **Curva de aprendizaje** | 📉 Baja | Alta | Muy Alta |
| **File-based routing** | ✅ | ❌ | ❌ |
| **Reactividad nativa** | ✅ (SerJS) | ❌ | ❌ |
| **Zero config** | ✅ | ⚠️ Parcial | ❌ |
| **Modularidad** | ✅ | ✅ | ✅ |
| **Overhead** | 🪶 Mínimo | Medio | Alto |

## 🚦 Roadmap

- [x] Sistema de routing file-based
- [x] Integración SerJS
- [x] Tailwind CSS v4
- [x] Componentes reutilizables
- [x] Layouts anidados
- [ ] Middleware system
- [ ] API REST automática
- [ ] Database ORM ligero
- [ ] Hot reload en desarrollo
- [ ] CLI tools
- [ ] Testing integrado
- [ ] Deploy automation

## 🤝 Contribuir

Las contribuciones son bienvenidas. Por favor:

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add: Amazing Feature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

## 👨‍💻 Autor

**Fidel Remedios Rosado**
- Email: fiderosado@gmail.com
- GitHub: [@fiderosado](https://github.com/fiderosado)

## 🙏 Agradecimientos

- Next.js por la inspiración en el routing
- Tailwind CSS por el sistema de utilidades
- React Hooks por los conceptos de reactividad
- La comunidad PHP por su continuo apoyo

---

**Mode-PHP**: *Nativo. Rápido. Reactivo. Sin complicaciones.*

⭐ Si te gusta el proyecto, ¡deja una estrella en GitHub!
