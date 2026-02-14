<?php
session_start();
use Core\Http\CSRF;

?>

<main class="flex min-h-screen flex-col items-center justify-center bg-[#f8f9fa] px-4">

    <div id="login-content" class="w-full max-w-[420px] rounded-2xl bg-white px-10 py-12">
        <h1 class="mb-2 text-center text-2xl font-normal text-[#202124]">
            Iniciar sesion
        </h1>
        <p class="mb-8 text-center text-sm text-[#5f6368]">
            para continuar con tu cuenta
        </p>
        <a id="google-login-button" href="https://accounts.google.com/signin"
            class="mb-6 flex w-full items-center justify-center gap-3 rounded-lg border border-[#dadce0] bg-white px-6 py-3 text-sm font-medium text-[#3c4043] no-underline transition-all hover:border-[#d2e3fc] hover:bg-[#f8fbff] hover:shadow-sm">
            <svg class="h-5 w-5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"
                    fill="#4285F4"></path>
                <path
                    d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                    fill="#34A853"></path>
                <path
                    d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                    fill="#FBBC05"></path>
                <path
                    d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                    fill="#EA4335"></path>
            </svg>
            Iniciar sesion con Google
        </a>
        <p class="text-center text-sm text-[#5f6368]">
            ¿No tienes cuenta?
            <a href="#" class="font-medium text-[#1a73e8] no-underline hover:underline">
                Crear una cuenta
            </a>
        </p>
    </div>

    <div id="logued-content" class="w-full hidden max-w-[420px] rounded-2xl bg-white px-10 py-12">
        <h1 id="user-label" class="text-5xl font-bold">
            Welcome ${user}
        </h1>
        <a id="google-logout-button" href="https://accounts.google.com/signin"
            class="mb-6 mt-10 flex w-full items-center justify-center gap-3 rounded-lg border border-[#dadce0] bg-white px-6 py-3 text-sm font-medium text-[#3c4043] no-underline transition-all hover:border-[#d2e3fc] hover:bg-[#f8fbff] hover:shadow-sm">
            <svg class="h-5 w-5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"
                    fill="#4285F4"></path>
                <path
                    d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                    fill="#34A853"></path>
                <path
                    d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                    fill="#FBBC05"></path>
                <path
                    d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                    fill="#EA4335"></path>
            </svg>
            Cerrar session
        </a>
    </div>

</main>
<script type="module">
    const {
        useRef,
        useState,
        useEffect,
        Actions,
        navigation,
        cookies,
        reRender
    } = SerJS;

    const [session, setSession] = useState(null);

    const loginContentRef = useRef('login-content');
    const loguedContentRef = useRef('logued-content');
    const userLabelRef = useRef('user-label');

    useEffect(() => {
        if (session.current !== null) {
            console.log("oculto el login..")
            loginContentRef.addClass("hidden");// si hay session ocultar
            loguedContentRef.removeClass("hidden");
            reRender(userLabelRef, { user: session.current?.given_name ?? "" });
        } else {
            console.log("muestro el login..")
            reRender(userLabelRef, { user: "" });
            loginContentRef.removeClass("hidden");// si no hay mostrar
            loguedContentRef.addClass("hidden");
        }

    }, [session, session.current])

    async function getAuthTokenFromCookies() {
        try {
            // Obtener todas las cookies para debug
            const allCookies = await cookies.getCookies();
            //console.log("Todas las cookies:", allCookies);

            // Obtener el token de autenticación
            const token = await cookies.getCookie('auth.session-token');
            console.log("Token de autenticación:", token);

            if (token) {
                // También podemos llamar al endpoint para obtener la sesión completa
                const response = await fetch('/api/auth/session', {
                    method: 'GET',
                    credentials: 'include'
                });

                const data = await response.json();
                console.log("Sesión completa:", data);

                if (data.authenticated && data?.session) {
                    console.log("Usuario autenticado:", data?.session.user);
                    setSession(data?.session.user)
                } else {
                    setSession(null)
                }
            } else {
                console.log("No hay token de autenticación");
                setSession(null);
            }
        } catch (error) {
            console.error("Error al obtener token:", error);
            setSession(null);
        }
    }

    useEffect(() => {
        getAuthTokenFromCookies();
    }, [])

    async function handleGoogleLogin() {
        await navigation.push('/api/auth/google?callbackUrl=/example/google-auth');
    }

    const googleLoginButtonRef = useRef('google-login-button');
    googleLoginButtonRef.onClick((e) => {
        e.preventDefault();
        handleGoogleLogin();
    })

    const googleLogoutButtonRef = useRef('google-logout-button');
    googleLogoutButtonRef.onClick((e) => {
        e.preventDefault();
        console.log("cerrar session");
        handleLogout();
    })

    async function handleLogout(params) {
        const resonse_logout = await Actions(`<?= CSRF::token(); ?>`);
        const response = await resonse_logout.call('logout-session', { enabled: true });
        getAuthTokenFromCookies();
    }

</script>