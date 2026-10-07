{{-- Asistente MediTrack: widget flotante (se inyecta en todas las páginas con InyectarChatbot) --}}
@php
    $mtUsuario = Auth::user();
    $mtNombre = trim($mtUsuario->nombre ?? '') ?: trim($mtUsuario->name ?? '');
    $mtNombre = $mtNombre ? explode(' ', $mtNombre)[0] : '';
@endphp

<style>
    #mt-chat {
        --mt-teal: #0f9488;
        --mt-teal-oscuro: #0b7a70;
        --mt-teal-suave: #e6f5f3;
        --mt-borde: #e3ecea;
        --mt-texto: #16302c;
        --mt-gris: #6b807c;
        --mt-fondo: #f6f9f8;
        --mt-rojo: #c2410c;
        --mt-rojo-suave: #fff4ed;
        font-family: inherit;
        color: var(--mt-texto);
    }
    #mt-chat *, #mt-chat *::before, #mt-chat *::after { box-sizing: border-box; }

    /* ---------- Botón flotante ---------- */
    #mt-chat-boton {
        position: fixed; right: 28px; bottom: 28px; z-index: 9999;
        width: 60px; height: 60px; border-radius: 18px; border: none;
        background: var(--mt-teal); color: #fff; cursor: pointer;
        box-shadow: 0 8px 24px rgba(15, 148, 136, .35);
        display: flex; align-items: center; justify-content: center;
        transition: transform .2s ease, background .2s ease;
    }
    #mt-chat-boton:hover { background: var(--mt-teal-oscuro); transform: translateY(-2px); }
    #mt-chat-boton .mt-icono-cerrar { display: none; }
    #mt-chat.abierto #mt-chat-boton .mt-icono-abrir { display: none; }
    #mt-chat.abierto #mt-chat-boton .mt-icono-cerrar { display: block; }
    #mt-chat-boton .mt-punto {
        position: absolute; top: -3px; right: -3px; width: 14px; height: 14px;
        background: #22c55e; border: 3px solid #fff; border-radius: 50%;
    }
    #mt-chat.abierto #mt-chat-boton .mt-punto { display: none; }

    /* ---------- Panel ---------- */
    #mt-chat-panel {
        position: fixed; right: 28px; bottom: 102px; z-index: 9999;
        width: 400px; max-width: calc(100vw - 32px);
        height: 600px; max-height: calc(100vh - 130px);
        background: #fff; border: 1px solid var(--mt-borde); border-radius: 22px;
        box-shadow: 0 20px 50px rgba(22, 48, 44, .16);
        display: flex; flex-direction: column; overflow: hidden;
        opacity: 0; transform: translateY(16px) scale(.98); pointer-events: none;
        transition: opacity .2s ease, transform .2s ease;
    }
    #mt-chat.abierto #mt-chat-panel { opacity: 1; transform: none; pointer-events: auto; }

    .mt-encabezado {
        display: flex; align-items: center; gap: 12px;
        padding: 16px 16px 14px; border-bottom: 1px solid var(--mt-borde); background: #fff;
    }
    .mt-logo {
        width: 44px; height: 44px; border-radius: 14px; flex-shrink: 0;
        background: var(--mt-teal); color: #fff;
        display: flex; align-items: center; justify-content: center;
    }
    .mt-titulo { flex: 1; min-width: 0; }
    .mt-titulo strong { display: block; font-size: 16px; font-weight: 800; line-height: 1.2; }
    .mt-estado { display: flex; align-items: center; gap: 6px; font-size: 12.5px; color: var(--mt-gris); margin-top: 2px; }
    .mt-estado::before { content: ''; width: 8px; height: 8px; border-radius: 50%; background: #22c55e; }
    .mt-icono-btn {
        width: 36px; height: 36px; border-radius: 11px; border: 1px solid var(--mt-borde);
        background: #fff; color: var(--mt-gris); cursor: pointer;
        display: flex; align-items: center; justify-content: center; transition: all .15s ease;
    }
    .mt-icono-btn:hover { background: var(--mt-teal-suave); color: var(--mt-teal); border-color: var(--mt-teal-suave); }

    /* ---------- Mensajes ---------- */
    #mt-chat-mensajes {
        flex: 1; overflow-y: auto; padding: 18px 16px 8px; background: var(--mt-fondo);
        scroll-behavior: smooth;
    }
    #mt-chat-mensajes::-webkit-scrollbar { width: 6px; }
    #mt-chat-mensajes::-webkit-scrollbar-thumb { background: #cfdcd9; border-radius: 3px; }

    .mt-fila { display: flex; gap: 8px; margin-bottom: 14px; align-items: flex-end; animation: mt-entrar .25s ease; }
    .mt-fila.usuario { justify-content: flex-end; }
    .mt-avatar {
        width: 30px; height: 30px; border-radius: 10px; flex-shrink: 0;
        background: var(--mt-teal-suave); color: var(--mt-teal);
        display: flex; align-items: center; justify-content: center;
    }
    .mt-burbuja {
        max-width: 80%; padding: 10px 14px; border-radius: 16px;
        font-size: 14px; line-height: 1.5; word-wrap: break-word;
    }
    .mt-fila.bot .mt-burbuja {
        background: #fff; border: 1px solid var(--mt-borde); border-bottom-left-radius: 6px;
        box-shadow: 0 1px 2px rgba(22, 48, 44, .04);
    }
    .mt-fila.usuario .mt-burbuja {
        background: var(--mt-teal); color: #fff; border-bottom-right-radius: 6px; font-weight: 600;
    }
    .mt-fila.error .mt-burbuja { background: var(--mt-rojo-suave); border-color: #fed7c3; color: var(--mt-rojo); }
    .mt-fila.error .mt-avatar { background: var(--mt-rojo-suave); color: var(--mt-rojo); }
    .mt-burbuja ul, .mt-burbuja ol { margin: 6px 0; padding-left: 20px; }
    .mt-burbuja li { margin: 2px 0; }
    .mt-burbuja p { margin: 0 0 6px; }
    .mt-burbuja p:last-child { margin-bottom: 0; }
    .mt-detalle { display: block; margin-top: 6px; font-size: 12px; opacity: .8; font-family: monospace; }

    .mt-escribiendo { display: flex; gap: 4px; padding: 4px 2px; }
    .mt-escribiendo span {
        width: 7px; height: 7px; border-radius: 50%; background: #9fb8b3;
        animation: mt-rebote 1.2s infinite ease-in-out;
    }
    .mt-escribiendo span:nth-child(2) { animation-delay: .15s; }
    .mt-escribiendo span:nth-child(3) { animation-delay: .3s; }

    /* ---------- Sugerencias ---------- */
    .mt-sugerencias { display: grid; gap: 8px; margin: 4px 0 14px 38px; }
    .mt-sugerencias button {
        display: flex; align-items: center; gap: 10px; width: 100%;
        background: #fff; border: 1px solid var(--mt-borde); border-radius: 14px;
        padding: 10px 12px; font: inherit; font-size: 13.5px; font-weight: 600; color: var(--mt-texto);
        text-align: left; cursor: pointer; transition: all .15s ease;
    }
    .mt-sugerencias button:hover { border-color: var(--mt-teal); background: var(--mt-teal-suave); }
    .mt-sugerencias .mt-chip-icono {
        width: 28px; height: 28px; border-radius: 9px; flex-shrink: 0;
        background: var(--mt-teal-suave); color: var(--mt-teal);
        display: flex; align-items: center; justify-content: center;
    }

    /* ---------- Entrada ---------- */
    .mt-entrada { padding: 12px 14px 10px; background: #fff; border-top: 1px solid var(--mt-borde); }
    .mt-caja {
        display: flex; align-items: flex-end; gap: 8px;
        background: var(--mt-fondo); border: 1px solid var(--mt-borde); border-radius: 16px;
        padding: 6px 6px 6px 14px; transition: border-color .15s ease, background .15s ease;
    }
    .mt-caja:focus-within { border-color: var(--mt-teal); background: #fff; }
    .mt-caja textarea {
        flex: 1; border: none; background: transparent; resize: none; outline: none;
        font: inherit; font-size: 14px; color: var(--mt-texto);
        padding: 8px 0; height: 38px; max-height: 110px; line-height: 1.4;
    }
    .mt-caja textarea::placeholder { color: #93a6a2; }
    #mt-chat-enviar {
        width: 38px; height: 38px; border-radius: 12px; border: none; flex-shrink: 0;
        background: var(--mt-teal); color: #fff; cursor: pointer;
        display: flex; align-items: center; justify-content: center; transition: background .15s ease;
    }
    #mt-chat-enviar:hover { background: var(--mt-teal-oscuro); }
    #mt-chat-enviar:disabled { background: #b6d6d2; cursor: default; }
    .mt-pie { text-align: center; font-size: 11px; color: #93a6a2; margin-top: 8px; }

    @keyframes mt-entrar { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
    @keyframes mt-rebote { 0%, 80%, 100% { transform: translateY(0); opacity: .5; } 40% { transform: translateY(-5px); opacity: 1; } }

    @media (max-width: 480px) {
        #mt-chat-panel { right: 16px; bottom: 90px; height: calc(100vh - 110px); }
        #mt-chat-boton { right: 16px; bottom: 16px; }
    }
</style>

<div id="mt-chat">
    <button id="mt-chat-boton" type="button" aria-label="Abrir Asistente MediTrack" title="Asistente MediTrack">
        <span class="mt-punto"></span>
        <svg class="mt-icono-abrir" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            <path d="M8 10h.01M12 10h.01M16 10h.01"/>
        </svg>
        <svg class="mt-icono-cerrar" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
            <path d="M18 6 6 18M6 6l12 12"/>
        </svg>
    </button>

    <div id="mt-chat-panel" role="dialog" aria-label="Asistente MediTrack">
        <div class="mt-encabezado">
            <div class="mt-logo">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                    <path d="M3.2 12h4.3l1.5-3 2 6 1.5-3h8.3"/>
                </svg>
            </div>
            <div class="mt-titulo">
                <strong>Asistente MediTrack</strong>
                <span class="mt-estado">En línea</span>
            </div>
            <button type="button" class="mt-icono-btn" id="mt-chat-reiniciar" title="Nueva conversación" aria-label="Nueva conversación">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/>
                </svg>
            </button>
            <button type="button" class="mt-icono-btn" id="mt-chat-cerrar" title="Cerrar" aria-label="Cerrar">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div id="mt-chat-mensajes"></div>

        <form class="mt-entrada" id="mt-chat-form">
            <div class="mt-caja">
                <textarea id="mt-chat-texto" placeholder="Escribe tu pregunta…" maxlength="1000" rows="1"></textarea>
                <button type="submit" id="mt-chat-enviar" aria-label="Enviar">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14M13 6l6 6-6 6"/>
                    </svg>
                </button>
            </div>
            <div class="mt-pie">Solo consulta información · No muestra datos clínicos</div>
        </form>
    </div>
</div>

<script>
(function () {
    const raiz = document.getElementById('mt-chat');
    const lista = document.getElementById('mt-chat-mensajes');
    const form = document.getElementById('mt-chat-form');
    const texto = document.getElementById('mt-chat-texto');
    const enviar = document.getElementById('mt-chat-enviar');

    const token = @json(csrf_token());
    const URL_MENSAJE = @json(route('chatbot.mensaje'));
    const URL_REINICIAR = @json(route('chatbot.reiniciar'));
    const NOMBRE = @json($mtNombre);

    const ICONO_BOT = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>';
    const ICONO_ALERTA = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>';

    const SUGERENCIAS = [
        { texto: '¿Qué citas hay hoy?', icono: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>' },
        { texto: '¿Qué medicamentos tienen stock bajo?', icono: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linejoin="round"><path d="M21 8 12 3 3 8v8l9 5 9-5V8Z"/><path d="m3 8 9 5 9-5M12 13v8"/></svg>' },
        { texto: '¿Cómo registro un paciente?', icono: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg>' },
    ];

    /* ---------- Abrir / cerrar ---------- */
    function alternar(abrir) {
        raiz.classList.toggle('abierto', abrir);
        if (raiz.classList.contains('abierto')) setTimeout(() => texto.focus(), 150);
    }
    document.getElementById('mt-chat-boton').addEventListener('click', () => alternar());
    document.getElementById('mt-chat-cerrar').addEventListener('click', () => alternar(false));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') alternar(false); });

    /* ---------- Formato seguro (escapa HTML, soporta **negritas** y listas) ---------- */
    function escapar(s) {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }
    function formatear(str) {
        const lineas = escapar(str).replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>').split('\n');
        let html = '', lista = null;
        for (const l of lineas) {
            const vineta = l.match(/^\s*[-*•]\s+(.*)/);
            const numero = l.match(/^\s*\d+[.)]\s+(.*)/);
            const tipo = vineta ? 'ul' : numero ? 'ol' : null;
            if (tipo) {
                if (lista !== tipo) { if (lista) html += `</${lista}>`; html += `<${tipo}>`; lista = tipo; }
                html += `<li>${(vineta || numero)[1]}</li>`;
            } else {
                if (lista) { html += `</${lista}>`; lista = null; }
                if (l.trim()) html += `<p>${l}</p>`;
            }
        }
        if (lista) html += `</${lista}>`;
        return html;
    }

    function agregar(contenido, tipo, detalle) {
        const fila = document.createElement('div');
        fila.className = 'mt-fila ' + tipo;
        if (tipo !== 'usuario') {
            fila.innerHTML = `<div class="mt-avatar">${tipo === 'error' ? ICONO_ALERTA : ICONO_BOT}</div>`;
        }
        const burbuja = document.createElement('div');
        burbuja.className = 'mt-burbuja';
        burbuja.innerHTML = tipo === 'usuario' ? escapar(contenido) : formatear(contenido);
        if (detalle) burbuja.innerHTML += `<span class="mt-detalle">${escapar(detalle)}</span>`;
        fila.appendChild(burbuja);
        lista.appendChild(fila);
        lista.scrollTop = lista.scrollHeight;
        return fila;
    }

    function escribiendo() {
        const fila = document.createElement('div');
        fila.className = 'mt-fila bot';
        fila.innerHTML = `<div class="mt-avatar">${ICONO_BOT}</div><div class="mt-burbuja"><div class="mt-escribiendo"><span></span><span></span><span></span></div></div>`;
        lista.appendChild(fila);
        lista.scrollTop = lista.scrollHeight;
        return fila;
    }

    function bienvenida() {
        lista.innerHTML = '';
        agregar(`¡Hola${NOMBRE ? ', ' + NOMBRE : ''}! Soy tu asistente. Puedo explicarte cómo usar MediTrack o consultar información de la clínica. ¿En qué te ayudo?`, 'bot');
        const caja = document.createElement('div');
        caja.className = 'mt-sugerencias';
        SUGERENCIAS.forEach(s => {
            const b = document.createElement('button');
            b.type = 'button';
            b.innerHTML = `<span class="mt-chip-icono">${s.icono}</span>${escapar(s.texto)}`;
            b.addEventListener('click', () => mandar(s.texto));
            caja.appendChild(b);
        });
        lista.appendChild(caja);
    }

    /* ---------- Enviar ---------- */
    async function mandar(pregunta) {
        pregunta = pregunta.trim();
        if (!pregunta || enviar.disabled) return;

        const sugerencias = lista.querySelector('.mt-sugerencias');
        if (sugerencias) sugerencias.remove();

        agregar(pregunta, 'usuario');
        texto.value = '';
        ajustarAltura();
        enviar.disabled = true;
        const espera = escribiendo();

        try {
            const res = await fetch(URL_MENSAJE, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                body: JSON.stringify({ mensaje: pregunta })
            });
            const datos = await res.json().catch(() => ({}));
            espera.remove();

            if (res.status === 429) {
                agregar('Has enviado muchos mensajes seguidos. Espera un minuto e intenta de nuevo.', 'error');
            } else if (res.status === 419 || res.status === 401) {
                agregar('Tu sesión expiró. Recarga la página e inicia sesión de nuevo.', 'error');
            } else if (!res.ok || datos.error) {
                agregar(datos.respuesta || 'Ocurrió un error inesperado.', 'error', datos.detalle || ('Código ' + res.status + (datos.message ? ': ' + datos.message : '') + ' · revisa storage/logs/laravel.log'));
            } else {
                agregar(datos.respuesta, 'bot');
            }
        } catch (e) {
            espera.remove();
            agregar('No se pudo conectar con el servidor.', 'error');
        } finally {
            enviar.disabled = false;
            texto.focus();
        }
    }

    function ajustarAltura() {
        texto.style.height = '38px';
        texto.style.height = Math.min(texto.scrollHeight, 110) + 'px';
    }

    form.addEventListener('submit', (e) => { e.preventDefault(); mandar(texto.value); });
    texto.addEventListener('input', ajustarAltura);
    texto.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); mandar(texto.value); }
    });

    document.getElementById('mt-chat-reiniciar').addEventListener('click', async () => {
        await fetch(URL_REINICIAR, { method: 'POST', headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' } });
        bienvenida();
    });

    bienvenida();
})();
</script>