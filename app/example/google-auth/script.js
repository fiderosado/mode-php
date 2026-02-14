const {
        useRef,
        useState,
        useEffect,
        Actions,
        navigation,
        cookies,
        csrfToken,
        reRender
    } = SerJS;

    const [session, setSession] = useState(null);

    const loginContentRef = useRef('login-content');
    const loguedContentRef = useRef('logued-content');
    const userLabelRef = useRef('user-label');

    useEffect(() => {
        if (session.current !== null) {
            loginContentRef.addClass("hidden");// si hay session ocultar
            loguedContentRef.removeClass("hidden");
            reRender(userLabelRef, { user: session.current?.given_name ?? "" });
        } else {
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
        const token = await csrfToken.getToken();
        const resonse_logout = await Actions(token);
        const response = await resonse_logout.call('logout-session', { enabled: true });
        getAuthTokenFromCookies();
    }