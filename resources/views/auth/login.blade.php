@extends('layouts.guest')

@section('content')
<style>
    /* ================= ANIMACIONES ================= */
    @keyframes kenBurns    { from { transform: scale(1); } to { transform: scale(1.1); } }
    @keyframes cardIn      { from { opacity: 0; transform: translateY(40px) scale(.96); filter: blur(6px); } to { opacity: 1; transform: none; filter: none; } }
    @keyframes slideLeft   { from { opacity: 0; transform: translateX(-30px); } to { opacity: 1; transform: none; } }
    @keyframes rise        { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: none; } }
    @keyframes orbFloat    { 0% { transform: translate(0,0) scale(1); } 50% { transform: translate(40px,-30px) scale(1.15); } 100% { transform: translate(-25px,25px) scale(.95); } }
    @keyframes ecgRun      { from { stroke-dashoffset: 700; } to { stroke-dashoffset: 0; } }
    @keyframes ecgDraw     { from { stroke-dashoffset: 700; } to { stroke-dashoffset: 0; } }
    @keyframes blink       { 0%, 49% { opacity: 1; } 50%, 100% { opacity: .25; } }
    @keyframes shimmer     { 0% { transform: translateX(-120%); } 45%, 100% { transform: translateX(120%); } }
    @keyframes ripple      { from { transform: scale(0); opacity: .45; } to { transform: scale(4); opacity: 0; } }
    @keyframes shake       { 0%,100% { transform: translateX(0); } 20%,60% { transform: translateX(-8px); } 40%,80% { transform: translateX(8px); } }
    @keyframes pop         { 0% { transform: scale(.6); opacity: 0; } 70% { transform: scale(1.08); } 100% { transform: scale(1); opacity: 1; } }
    @keyframes heartbeat   { 0%,100% { transform: scale(1); } 15% { transform: scale(1.18); } 30% { transform: scale(1); } 45% { transform: scale(1.12); } }

    .mt-bg        { animation: kenBurns 25s ease-in-out infinite alternate; will-change: transform; }
    .mt-card      { animation: cardIn .9s cubic-bezier(.16,1,.3,1) both; }
    .mt-slide-l   { animation: slideLeft .9s cubic-bezier(.16,1,.3,1) .25s both; }
    .mt-rise      { animation: rise .7s cubic-bezier(.16,1,.3,1) both; }
    .mt-pop       { animation: pop .6s cubic-bezier(.16,1,.3,1) both; }
    .mt-heart     { animation: heartbeat 1.6s ease-in-out infinite; }
    .mt-shake     { animation: shake .4s ease-in-out; }
    .mt-blink     { animation: blink 1s steps(1) infinite; }
    .d1 { animation-delay: .15s; } .d2 { animation-delay: .25s; } .d3 { animation-delay: .35s; }
    .d4 { animation-delay: .45s; } .d5 { animation-delay: .55s; } .d6 { animation-delay: .65s; }

    /* ================= PANEL IZQUIERDO ================= */
    .mt-orb {
        position: absolute; border-radius: 9999px; filter: blur(60px); opacity: .55;
        animation: orbFloat 16s ease-in-out infinite alternate; pointer-events: none;
    }
    .mt-dots {
        background-image: radial-gradient(rgba(255,255,255,.14) 1px, transparent 1px);
        background-size: 22px 22px;
        -webkit-mask-image: linear-gradient(to bottom, #000 20%, transparent 90%);
                mask-image: linear-gradient(to bottom, #000 20%, transparent 90%);
    }
    .mt-ecg-run {
        stroke-dasharray: 90 610;
        animation: ecgRun 2.6s linear infinite;
        filter: drop-shadow(0 0 6px rgba(94,234,212,.9));
    }
    .mt-progress { transition: width 1s linear; }

    /* ================= FORMULARIO ================= */
    .mt-spot { position: relative; }
    .mt-spot::before {
        content: ''; position: absolute; inset: 0; pointer-events: none; transition: opacity .3s;
        background: radial-gradient(420px circle at var(--mx, 50%) var(--my, -10%), rgba(20,184,166,.13), transparent 60%);
    }

    .fl-field { position: relative; }
    .fl-icon {
        position: absolute; left: 1rem; top: 50%; transform: translateY(-50%);
        color: #94a3b8; transition: color .2s, transform .3s cubic-bezier(.16,1,.3,1); pointer-events: none;
    }
    .fl-field:focus-within .fl-icon { color: #0d9488; transform: translateY(-50%) scale(1.15); }
    .fl-label {
        position: absolute; left: 2.75rem; top: 50%; transform: translateY(-50%);
        font-size: .875rem; font-weight: 600; color: #64748b; pointer-events: none;
        transition: all .22s cubic-bezier(.16,1,.3,1);
    }
    .fl-input:focus ~ .fl-label,
    .fl-input:not(:placeholder-shown) ~ .fl-label,
    .fl-input:-webkit-autofill ~ .fl-label {
        top: .9rem; font-size: .66rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #0f766e;
    }
    .fl-bar {
        position: absolute; left: 14px; right: 14px; bottom: 0; height: 2px; border-radius: 2px;
        background: linear-gradient(90deg, #14b8a6, #10b981);
        transform: scaleX(0); transition: transform .35s cubic-bezier(.16,1,.3,1);
    }
    .fl-field:focus-within .fl-bar { transform: scaleX(1); }
    .fl-error .fl-input { border-color: #f87171; background: #fef2f2; }
    .fl-error .fl-label, .fl-error .fl-icon { color: #dc2626 !important; }
    .fl-error .fl-bar { background: #ef4444; }

    .mt-btn {
        position: relative; overflow: hidden; isolation: isolate;
        background: linear-gradient(135deg, #0d9488 0%, #0f766e 50%, #059669 100%);
        background-size: 200% 200%; background-position: 0% 50%;
        transition: background-position .5s, transform .15s, box-shadow .3s;
    }
    .mt-btn:hover { background-position: 100% 50%; box-shadow: 0 14px 30px -8px rgba(13,148,136,.55); transform: translateY(-1px); }
    .mt-btn:active { transform: translateY(0) scale(.98); }
    .mt-btn::after {
        content: ''; position: absolute; inset: 0; z-index: -1;
        background: linear-gradient(115deg, transparent 30%, rgba(255,255,255,.35) 50%, transparent 70%);
        animation: shimmer 4s ease-in-out 1.5s infinite;
    }
    .mt-btn .mt-ripple {
        position: absolute; border-radius: 9999px; background: rgba(255,255,255,.6);
        width: 100px; height: 100px; margin: -50px 0 0 -50px; pointer-events: none;
        animation: ripple .6s ease-out forwards;
    }

    /* ================= SPLASH ================= */
    #splash-screen { transition: opacity .5s ease, transform .5s ease; }
    #splash-screen.is-leaving { opacity: 0; transform: scale(1.04); }
    .mt-ecg-draw { stroke-dasharray: 700; animation: ecgDraw 1.1s ease-out forwards; }

    @media (prefers-reduced-motion: reduce) {
        *, *::before, *::after { animation: none !important; transition: none !important; }
        #splash-screen { display: none !important; }
    }
</style>

{{-- ================= SPLASH (solo la 1.ª vez por sesión) ================= --}}
<div id="splash-screen" aria-hidden="true"
     class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-gradient-to-br from-teal-800 via-teal-900 to-slate-900 text-white pointer-events-none">
    <svg viewBox="0 0 400 60" class="w-72 h-14 mb-4" fill="none">
        <path class="mt-ecg-draw" d="M0 30 H130 L142 30 L152 12 L164 50 L176 4 L190 56 L200 30 H262 L272 21 L284 30 H400"
              stroke="#5eead4" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <div class="flex items-center gap-3 mt-pop d5">
        <div class="p-3 bg-white/10 rounded-2xl border border-white/20 shadow-lg">
            <i class="bi bi-heart-pulse-fill text-3xl text-teal-300 mt-heart inline-block"></i>
        </div>
        <span class="text-3xl font-extrabold tracking-wider">MediTrack</span>
    </div>
    <p class="text-[11px] text-teal-200/80 mt-3 tracking-[.3em] uppercase font-semibold mt-rise d6">Sistema de Gestión Médica</p>
</div>
<script>
    (function () {
        try {
            if (sessionStorage.getItem('mt_splash_seen')) document.getElementById('splash-screen').remove();
            else sessionStorage.setItem('mt_splash_seen', '1');
        } catch (e) {}
    })();
</script>

<main class="min-h-screen w-full flex items-center justify-center p-4 sm:p-6 lg:p-10 relative overflow-hidden font-sans bg-slate-900">

    {{-- Fondo --}}
    <div class="absolute inset-0 bg-cover bg-center mt-bg" aria-hidden="true"
         style="background-image: url('https://images.unsplash.com/photo-1629909613654-28e377c37b09?q=80&w=1920&auto=format&fit=crop');"></div>
    <div class="absolute inset-0 bg-gradient-to-br from-teal-950/70 via-slate-900/40 to-teal-900/60" aria-hidden="true"></div>

    {{-- ================= TARJETA ================= --}}
    <div id="login-card"
         class="mt-card w-full max-w-5xl rounded-[28px] overflow-hidden grid grid-cols-1 md:grid-cols-2 relative z-10 shadow-[0_30px_80px_-20px_rgba(0,0,0,.55)] ring-1 ring-white/20">

        {{-- ================= PANEL IZQUIERDO ================= --}}
        <aside class="hidden md:flex relative flex-col justify-between p-10 text-white bg-gradient-to-br from-teal-600 via-teal-800 to-slate-900 overflow-hidden"
               aria-label="Fecha y hora actual">

            {{-- Decoración animada --}}
            <div class="mt-orb w-72 h-72 bg-teal-300 -top-20 -left-16" aria-hidden="true"></div>
            <div class="mt-orb w-64 h-64 bg-emerald-400 bottom-0 -right-20" style="animation-delay:-6s; opacity:.35" aria-hidden="true"></div>
            <div class="absolute inset-0 mt-dots" aria-hidden="true"></div>

            {{-- Marca --}}
            <div class="relative flex items-center justify-between mt-slide-l">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 grid place-items-center rounded-2xl bg-white/15 border border-white/25 backdrop-blur-sm shadow-lg">
                        <i class="bi bi-heart-pulse-fill text-xl text-teal-200 mt-heart inline-block"></i>
                    </div>
                    <div class="leading-tight">
                        <p class="text-lg font-extrabold tracking-wide">MediTrack</p>
                        <p class="text-[10px] uppercase tracking-[.25em] text-teal-100/70 font-semibold">Gestión médica</p>
                    </div>
                </div>
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-[11px] font-semibold backdrop-blur-sm">
                    <span class="relative flex w-2 h-2">
                        <span class="absolute inline-flex h-full w-full rounded-full bg-emerald-300 opacity-75 animate-ping"></span>
                        <span class="relative inline-flex w-2 h-2 rounded-full bg-emerald-400"></span>
                    </span>
                    En línea
                </span>
            </div>

            {{-- Reloj + ECG --}}
            <div class="relative my-8 mt-slide-l" style="animation-delay:.4s">
                <p id="live-date" class="text-sm font-semibold text-teal-100/80 capitalize">&nbsp;</p>
                <time id="live-time" class="flex items-baseline font-black tracking-tight tabular-nums leading-none mt-1">
                    <span id="clock-hm" class="text-6xl lg:text-7xl">--<span class="mt-blink">:</span>--</span>
                    <span id="clock-s" class="text-2xl lg:text-3xl text-teal-200/80 ml-2">--</span>
                </time>
                <div class="mt-4 h-1 w-full rounded-full bg-white/10 overflow-hidden">
                    <div id="sec-progress" class="mt-progress h-full rounded-full bg-gradient-to-r from-teal-300 to-emerald-300" style="width:0%"></div>
                </div>

                <svg viewBox="0 0 700 60" preserveAspectRatio="none" class="w-full h-12 mt-5" fill="none" aria-hidden="true">
                    <path d="M0 30 H230 L245 30 L257 12 L270 50 L283 4 L298 56 L310 30 H400 L412 21 L425 30 H700"
                          stroke="rgba(255,255,255,.15)" stroke-width="2" stroke-linejoin="round"/>
                    <path class="mt-ecg-run" d="M0 30 H230 L245 30 L257 12 L270 50 L283 4 L298 56 L310 30 H400 L412 21 L425 30 H700"
                          stroke="#5eead4" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>

            {{-- Mini calendario --}}
            <div class="relative rounded-2xl bg-white/10 border border-white/15 backdrop-blur-md p-5 mt-slide-l" style="animation-delay:.55s">
                <div class="flex items-center justify-between mb-3">
                    <span id="cal-month-year" class="text-sm font-extrabold uppercase tracking-wider">&nbsp;</span>
                    <i class="bi bi-calendar-heart text-teal-200" aria-hidden="true"></i>
                </div>
                <div class="grid grid-cols-7 gap-1 text-center text-[10px] font-bold uppercase text-teal-100/60 mb-1.5" aria-hidden="true">
                    <div>D</div><div>L</div><div>M</div><div>M</div><div>J</div><div>V</div><div>S</div>
                </div>
                <div id="cal-days-grid" class="grid grid-cols-7 gap-1 text-center text-xs font-semibold"></div>
            </div>
        </aside>

        {{-- ================= PANEL DERECHO: FORMULARIO ================= --}}
        <section id="form-panel" class="mt-spot bg-white/95 backdrop-blur-xl p-8 sm:p-12 lg:p-14 flex flex-col justify-center">

            {{-- Logo solo en móvil --}}
            <div class="md:hidden flex items-center justify-center gap-2 mb-6 mt-rise">
                <div class="w-10 h-10 grid place-items-center rounded-xl bg-gradient-to-br from-teal-500 to-emerald-600 text-white shadow-lg shadow-teal-600/30">
                    <i class="bi bi-heart-pulse-fill mt-heart inline-block"></i>
                </div>
                <span class="text-xl font-extrabold text-slate-900">MediTrack</span>
            </div>

            <header class="mt-rise d1">
                <p id="greeting" class="text-sm font-bold text-teal-600">Hola</p>
                <h1 class="text-3xl font-black text-slate-900 tracking-tight mt-1">Bienvenido de nuevo</h1>
                <p class="text-sm text-slate-500 mt-2">Ingresa tus credenciales para acceder al sistema.</p>
            </header>

            @if (session('status'))
                <div role="status" class="mt-6 p-3.5 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-sm font-semibold flex items-center gap-2 mt-rise">
                    <i class="bi bi-check-circle-fill shrink-0" aria-hidden="true"></i>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div role="alert" class="mt-6 p-3.5 bg-red-50 border border-red-200 rounded-2xl text-red-800 text-sm space-y-1 mt-shake">
                    @foreach ($errors->all() as $error)
                        <p class="flex items-center gap-2 font-semibold">
                            <i class="bi bi-exclamation-octagon-fill shrink-0 text-red-600" aria-hidden="true"></i>
                            <span>{{ $error }}</span>
                        </p>
                    @endforeach
                </div>
            @endif

            <form id="login-form" method="POST" action="{{ route('login') }}" class="mt-8 space-y-5" novalidate>
                @csrf

                {{-- Usuario --}}
                <div class="mt-rise d2">
                    <div class="fl-field @error('email') fl-error @enderror">
                        <i class="bi bi-person fl-icon text-lg" aria-hidden="true"></i>
                        <input type="text" name="email" id="email" value="{{ old('email') }}" placeholder=" "
                               required autofocus autocomplete="username" autocapitalize="none" spellcheck="false"
                               @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                               class="fl-input w-full h-14 pl-11 pr-4 pt-5 pb-1.5 rounded-2xl border border-slate-200 bg-slate-50 text-sm font-semibold text-slate-900 outline-none transition focus:bg-white focus:border-teal-400 focus:shadow-[0_0_0_4px_rgba(20,184,166,.12)]">
                        <label for="email" class="fl-label">Correo o usuario</label>
                        <span class="fl-bar" aria-hidden="true"></span>
                    </div>
                    @error('email')
                        <p id="email-error" class="mt-1.5 ml-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Contraseña --}}
                <div class="mt-rise d3">
                    <div class="fl-field @error('password') fl-error @enderror">
                        <i class="bi bi-shield-lock fl-icon text-lg" aria-hidden="true"></i>
                        <input type="password" name="password" id="password" placeholder=" " required autocomplete="current-password"
                               aria-describedby="caps-warning"
                               class="fl-input w-full h-14 pl-11 pr-12 pt-5 pb-1.5 rounded-2xl border border-slate-200 bg-slate-50 text-sm font-semibold text-slate-900 outline-none transition focus:bg-white focus:border-teal-400 focus:shadow-[0_0_0_4px_rgba(20,184,166,.12)]">
                        <label for="password" class="fl-label">Contraseña</label>
                        <span class="fl-bar" aria-hidden="true"></span>
                        <button type="button" id="toggle-password" aria-label="Mostrar contraseña" aria-controls="password" aria-pressed="false"
                                class="absolute right-2 top-1/2 -translate-y-1/2 w-10 h-10 grid place-items-center rounded-xl text-slate-400 hover:text-teal-700 hover:bg-teal-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500/40 transition">
                            <i id="password-icon" class="bi bi-eye text-lg transition-transform duration-300" aria-hidden="true"></i>
                        </button>
                    </div>
                    <p id="caps-warning" class="hidden mt-1.5 ml-1 text-xs font-semibold text-amber-600 items-center gap-1.5" aria-live="polite">
                        <i class="bi bi-capslock-fill" aria-hidden="true"></i> Bloq Mayús está activado
                    </p>
                </div>

                <div class="flex items-center justify-between text-sm mt-rise d4">
                    <label for="remember" class="flex items-center gap-2 cursor-pointer select-none text-slate-600 font-semibold hover:text-slate-900 transition">
                        <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}
                               class="w-4 h-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500" style="accent-color:#0d9488">
                        Recordar sesión
                    </label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="font-semibold text-teal-700 hover:text-teal-900 relative after:absolute after:left-0 after:-bottom-0.5 after:h-px after:w-full after:bg-current after:scale-x-0 hover:after:scale-x-100 after:origin-left after:transition-transform">¿Olvidaste tu contraseña?</a>
                    @endif
                </div>

                <div class="pt-2 mt-rise d5">
                    <button type="submit" id="submit-btn"
                            class="mt-btn w-full h-14 rounded-2xl text-white font-bold text-sm tracking-wide flex items-center justify-center gap-2 shadow-lg shadow-teal-700/30 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-teal-500/40 disabled:cursor-wait disabled:opacity-80 group">
                        <span id="submit-label">Acceder al sistema</span>
                        <i id="submit-icon" class="bi bi-arrow-right text-lg transition-transform duration-300 group-hover:translate-x-1.5" aria-hidden="true"></i>
                        <svg id="submit-spinner" class="hidden w-5 h-5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" opacity=".25"/>
                            <path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                        </svg>
                    </button>
                </div>
            </form>

            <footer class="mt-10 pt-6 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400 font-semibold mt-rise d6">
                <span>MediTrack &copy; {{ date('Y') }}</span>
                <span class="inline-flex items-center gap-1"><i class="bi bi-lock-fill" aria-hidden="true"></i> Conexión segura</span>
            </footer>
        </section>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const $ = id => document.getElementById(id);

    /* ---------- Splash ---------- */
    const splash = $('splash-screen');
    if (splash) setTimeout(() => {
        splash.classList.add('is-leaving');
        setTimeout(() => splash.remove(), 550);
    }, 1400);

    /* ---------- Saludo según la hora ---------- */
    const h = new Date().getHours();
    $('greeting').textContent = h < 12 ? 'Buenos días' : h < 19 ? 'Buenas tardes' : 'Buenas noches';

    /* ---------- Reloj + calendario ---------- */
    const DAYS   = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
    const MONTHS = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    const pad = n => String(n).padStart(2, '0');
    const $hm = $('clock-hm'), $s = $('clock-s'), $bar = $('sec-progress');
    let renderedDay = null;

    function renderCalendar(now) {
        const y = now.getFullYear(), m = now.getMonth(), d = now.getDate();
        $('live-date').textContent = `${DAYS[now.getDay()]}, ${d} de ${MONTHS[m]}`;
        $('cal-month-year').textContent = `${MONTHS[m]} ${y}`;
        const first = new Date(y, m, 1).getDay(), total = new Date(y, m + 1, 0).getDate();
        const frag = document.createDocumentFragment();
        for (let i = 0; i < first; i++) frag.appendChild(document.createElement('div'));
        for (let day = 1; day <= total; day++) {
            const c = document.createElement('div');
            c.textContent = day;
            if (day === d) {
                c.className = 'py-1 rounded-lg bg-white text-teal-800 font-black shadow-lg shadow-teal-950/30 mt-pop';
                c.setAttribute('aria-current', 'date');
            } else {
                c.className = 'py-1 rounded-lg transition hover:bg-white/15 ' + (day < d ? 'text-white/35' : 'text-white/90');
            }
            frag.appendChild(c);
        }
        $('cal-days-grid').replaceChildren(frag);
        renderedDay = now.toDateString();
    }

    function tick() {
        const now = new Date(), sec = now.getSeconds();
        $hm.innerHTML = `${pad(now.getHours())}<span class="mt-blink">:</span>${pad(now.getMinutes())}`;
        $s.textContent = pad(sec);
        // barra de segundos: se reinicia sin animación al llegar a 0
        if (sec === 0) { $bar.style.transition = 'none'; $bar.style.width = '0%'; void $bar.offsetWidth; $bar.style.transition = ''; }
        $bar.style.width = `${((sec + 1) / 60) * 100}%`;
        if (now.toDateString() !== renderedDay) renderCalendar(now);
        setTimeout(tick, 1000 - now.getMilliseconds());
    }
    if ($hm) tick();

    /* ---------- Luz que sigue al cursor en el formulario ---------- */
    const panel = $('form-panel');
    panel.addEventListener('pointermove', e => {
        const r = panel.getBoundingClientRect();
        panel.style.setProperty('--mx', `${e.clientX - r.left}px`);
        panel.style.setProperty('--my', `${e.clientY - r.top}px`);
    });

    /* ---------- Mostrar / ocultar contraseña ---------- */
    const $pwd = $('password'), $toggle = $('toggle-password'), $icon = $('password-icon');
    $toggle.addEventListener('click', () => {
        const show = $pwd.type === 'password';
        $pwd.type = show ? 'text' : 'password';
        $icon.style.transform = 'rotateY(90deg)';
        setTimeout(() => {
            $icon.classList.toggle('bi-eye', !show);
            $icon.classList.toggle('bi-eye-slash', show);
            $icon.style.transform = '';
        }, 150);
        $toggle.setAttribute('aria-pressed', String(show));
        $toggle.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
        $pwd.focus();
    });

    /* ---------- Bloq Mayús ---------- */
    const $caps = $('caps-warning');
    const setCaps = on => { $caps.classList.toggle('hidden', !on); $caps.classList.toggle('flex', on); };
    ['keydown', 'keyup'].forEach(ev => $pwd.addEventListener(ev, e => e.getModifierState && setCaps(e.getModifierState('CapsLock'))));
    $pwd.addEventListener('blur', () => setCaps(false));

    /* ---------- Botón: ripple + carga + validación ---------- */
    const $form = $('login-form'), $btn = $('submit-btn'), $card = $('login-card');

    $btn.addEventListener('pointerdown', e => {
        const r = $btn.getBoundingClientRect(), dot = document.createElement('span');
        dot.className = 'mt-ripple';
        dot.style.left = `${e.clientX - r.left}px`;
        dot.style.top  = `${e.clientY - r.top}px`;
        $btn.appendChild(dot);
        dot.addEventListener('animationend', () => dot.remove());
    });

    const setLoading = on => {
        $btn.disabled = on;
        $('submit-label').textContent = on ? 'Verificando...' : 'Acceder al sistema';
        $('submit-icon').classList.toggle('hidden', on);
        $('submit-spinner').classList.toggle('hidden', !on);
    };

    $form.addEventListener('submit', e => {
        if (!$form.checkValidity()) {
            e.preventDefault();
            const bad = $form.querySelector(':invalid');
            bad?.closest('.fl-field')?.classList.add('fl-error');
            bad?.focus();
            $card.classList.remove('mt-shake', 'mt-card'); void $card.offsetWidth; $card.classList.add('mt-shake');
            return;
        }
        setLoading(true);
    });

    // Quita el estado de error al escribir
    $form.querySelectorAll('.fl-input').forEach(inp =>
        inp.addEventListener('input', () => inp.closest('.fl-field').classList.remove('fl-error')));

    // Si regresa con "Atrás", reactiva el botón
    window.addEventListener('pageshow', e => { if (e.persisted) setLoading(false); });
});
</script>
@endsection