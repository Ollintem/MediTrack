{{--
    Actualización automática: cada 20 s la pantalla pregunta si cambió algo en su módulo (GET /cambios).
    - Si cambió y NO estás capturando nada → se recarga sola y conserva tu posición en la página.
    - Si estás capturando (escribiendo, ventana abierta, recibos marcados, carrito con productos…) → no muestra
      nada encima: deja el cambio pendiente y recarga en cuanto termines (al guardar, pagar o cerrar la ventana).
    - Solo revisa mientras la pestaña está a la vista.
    Los módulos y sus tablas están en App\Services\Cambios.
--}}
@php
    $moduloCambios = \App\Services\Cambios::moduloDeRuta(\Illuminate\Support\Facades\Route::currentRouteName());
    $puedeVigilar  = $moduloCambios && auth()->user()?->tienePermiso(\App\Services\Cambios::MODULOS[$moduloCambios][0], 'ver');
@endphp

@if ($puedeVigilar)
    <script>
    (() => {
        const MODULO = @js($moduloCambios);
        const URL_CAMBIOS = @js(route('cambios')) + '?modulo=' + encodeURIComponent(MODULO);
        const CADA_MS = 20000;            // revisión de cambios
        const ESPERA_MS = 3000;           // con un cambio pendiente: cada cuánto se revisa si ya terminaste
        const CLAVE_SCROLL = 'mt-scroll:' + location.pathname + location.search;
        let firma = null, modificado = false, pendiente = false;

        // ¿Quién hace el scroll de la página? (el layout puede desplazar un contenedor y no la ventana)
        const desplazador = () => {
            let el = document.getElementById('main-content');
            while (el && el !== document.body) {
                const o = getComputedStyle(el).overflowY;
                if ((o === 'auto' || o === 'scroll') && el.scrollHeight > el.clientHeight) return el;
                el = el.parentElement;
            }
            return document.scrollingElement || document.documentElement;
        };

        // Captura sin guardar: lo que escribes o marcas en el contenido de la página.
        // No cuentan los buscadores ni los filtros sueltos (no se pierde nada importante al recargar).
        const esCaptura = el => {
            if (!el.closest || !el.closest('#main-content') || el.closest('[data-sin-cambios]')) return false;
            if (el.matches('input[type="search"]')) return false;
            if (el.matches('select') && !el.closest('form, [role="dialog"], .fixed')) return false;
            return el.matches('input, textarea, select, [contenteditable="true"]');
        };
        ['input', 'change'].forEach(ev => document.addEventListener(ev, e => {
            if (e.isTrusted && esCaptura(e.target)) modificado = true;
        }, true));

        // getClientRects y no offsetParent: las ventanas usan position:fixed y ahí offsetParent siempre es null;
        // un panel oculto con transparencia (como el chatbot cerrado: opacity 0, sin clics) tampoco cuenta
        const visible = el => {
            if (!el || !el.getClientRects().length) return false;
            const cs = getComputedStyle(el);
            return cs.visibility !== 'hidden' && Number(cs.opacity) > 0 && cs.pointerEvents !== 'none';
        };
        const ocupado = () => {
            const a = document.activeElement;
            if (a && a.closest('#main-content, [role="dialog"], .fixed') && a.matches('input:not([type="search"]), textarea, select, [contenteditable="true"]')) return true; // escribiendo
            // alerta que pide respuesta (los avisos pequeños tipo "Periodo creado" no cuentan)
            if (window.Swal && Swal.isVisible && Swal.isVisible() && !(Swal.getPopup() && Swal.getPopup().classList.contains('swal2-toast'))) return true;
            if ([...document.querySelectorAll('.fixed.inset-0, [role="dialog"], [role="menu"]')].some(visible)) return true; // ventana o menú abierto
            if (typeof window.mtOcupado === 'function' && window.mtOcupado()) return true;                       // la pantalla dice que está ocupada
            return modificado;
        };

        const recargar = () => {
            try { sessionStorage.setItem(CLAVE_SCROLL, String(desplazador().scrollTop)); } catch (e) {}
            location.reload();
        };

        // Si quedó un cambio pendiente, recarga en cuanto la pantalla deja de estar ocupada
        const intentarRecargar = () => { if (pendiente && document.visibilityState === 'visible' && !ocupado()) recargar(); };
        setInterval(intentarRecargar, ESPERA_MS);

        // Lo que guardas tú (POST/PUT/DELETE por fetch) también cambia la firma: después de cada guardado
        // propio se toma la firma nueva como punto de partida (no recarga por tus propios cambios),
        // y lo capturado ya quedó guardado, así que deja de contar como captura pendiente.
        const fetchOriginal = window.fetch.bind(window);
        const firmaActual = async () => {
            const r = await fetchOriginal(URL_CAMBIOS, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store' });
            return r.ok ? (await r.json()).firma : null;
        };
        let rebase = null;
        window.fetch = async (...args) => {
            const respuesta = await fetchOriginal(...args);
            const metodo = String((args[1] && args[1].method) || (args[0] instanceof Request ? args[0].method : 'GET')).toUpperCase();
            if (metodo !== 'GET' && respuesta.ok) {
                modificado = false;
                clearTimeout(rebase);
                rebase = setTimeout(async () => { try { const f = await firmaActual(); if (f) firma = f; } catch (e) {} }, 300);
            }
            return respuesta;
        };

        const revisar = async () => {
            if (document.visibilityState !== 'visible') return;
            try {
                const nueva = await firmaActual();
                if (!nueva) return;
                if (firma === null) { firma = nueva; return; }
                if (nueva === firma) return;
                firma = nueva;
                pendiente = true;
                intentarRecargar();
            } catch (e) { /* sin conexión: se vuelve a intentar en la siguiente vuelta */ }
        };

        // Diagnóstico (solo lectura): en la consola, mtCambios() dice qué ve la actualización automática
        window.mtCambios = () => ({ modulo: MODULO, firma, pendiente, modificado, ocupado: ocupado() });

        // Al volver a la pestaña, se revisa de inmediato
        document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible') revisar(); });

        // Tras una recarga automática se regresa a donde estabas
        window.addEventListener('load', () => {
            let y = null;
            try { y = sessionStorage.getItem(CLAVE_SCROLL); sessionStorage.removeItem(CLAVE_SCROLL); } catch (e) {}
            if (y !== null) setTimeout(() => { desplazador().scrollTop = Number(y) || 0; }, 350);
        });

        revisar();
        setInterval(revisar, CADA_MS);
    })();
    </script>
@endif
