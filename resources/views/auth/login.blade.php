@extends('layouts.guest')

{{--
    INICIO DE SESIÓN · MediTrack («La credencial viva»)
    A la izquierda, el gafete de acceso se arma mientras se escribe el usuario (roseta, nombre y correo). A la derecha, el formulario. Al entrar, cubre la pantalla la misma cortina del panel.
--}}

@section('content')
@php
    // Todos los usuarios usan el mismo dominio: solo se escribe el usuario
    $dominioLogin = '@meditrack.com';
    $emailViejo   = (string) old('email', '');
    $usuarioViejo = \Illuminate\Support\Str::endsWith(strtolower($emailViejo), $dominioLogin)
        ? substr($emailViejo, 0, -strlen($dominioLogin))
        : $emailViejo;
    try { $nombreClinica = optional(\App\Models\Clinica::query()->first())->nombre; } catch (\Throwable $e) { $nombreClinica = null; }
    $nombreClinica = $nombreClinica ?: 'Sistema clínico';
    $hayError = $errors->any();
@endphp

<main class="lg-pag" id="lg-pag">

    <!-- ESCENARIO: el gafete de acceso -->
    <section class="lg-esc" aria-label="Gafete de acceso">
        <span class="lg-gl" id="lg-gl" aria-hidden="true"></span>
        <span class="lg-luz" aria-hidden="true"></span>

        <header class="lg-marca">
            <svg class="lg-cruz" viewBox="0 0 44 44" aria-hidden="true">
                <path d="M16 4h12v12h12v12H28v12H16V28H4V16h12z" fill="#a6efe2"/>
                <path class="lg-latido" d="M7 23h8l3-6 4 11 3-7 2 2h10" fill="none" stroke="#073f3b" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <div>
                <b>MediTrack</b>
                <small>{{ $nombreClinica }}</small>
            </div>
        </header>

        <p class="lg-reloj" aria-hidden="true"><b id="lg-hora">--:--</b><span id="lg-fecha">&nbsp;</span></p>

        <div class="lg-gafete-zona" id="lg-zona">
            <div class="lg-colgante" id="lg-colgante">
            <span class="lg-cordon" aria-hidden="true"></span>
            <div class="lg-gafete {{ $hayError ? 'denegado' : '' }}" id="lg-gafete" role="img" tabindex="0"
                 aria-label="Gafete de acceso. Arrástralo o usa las flechas para columpiarlo.">
                <!-- Frente -->
                <div class="lg-g-cara frente">
                    <i class="lg-ranura" aria-hidden="true"></i>
                    <div class="lg-g-cab">
                        <span class="lg-g-gl" id="lg-g-gl" aria-hidden="true"></span>
                        <p><b>MediTrack</b><span>Acceso al sistema</span></p>
                        <svg viewBox="0 0 44 44" aria-hidden="true"><path d="M16 4h12v12h12v12H28v12H16V28H4V16h12z" fill="currentColor"/></svg>
                    </div>
                    <div class="lg-g-foto">
                        <span class="lg-g-roseta" id="lg-roseta" aria-hidden="true"></span>
                        <b id="lg-ini">MT</b>
                        <span class="lg-persiana" aria-hidden="true"><i></i><i></i><em class="bi bi-lock-fill"></em></span>
                    </div>
                    <p class="lg-g-nom" id="lg-nom">Tu usuario</p>
                    <p class="lg-g-mail mono" id="lg-mail">usuario{{ $dominioLogin }}</p>
                    <p class="lg-g-rol">Personal autorizado</p>
                    <p class="lg-g-est" id="lg-est"><i></i><span>{{ $hayError ? 'Acceso denegado' : 'Esperando credenciales' }}</span></p>
                    <i class="lg-escaneo" aria-hidden="true"></i>
                    <i class="lg-brillo" aria-hidden="true"></i>
                </div>
            </div>
            </div>
        </div>

        <p class="lg-pie" id="lg-pie">Arrastra el gafete para columpiarlo</p>
    </section>

    <!-- FORMULARIO -->
    <section class="lg-form" aria-labelledby="lg-titulo">
        <div class="lg-form-in">
            <p class="lg-saludo" id="lg-saludo">Hola</p>
            <h1 id="lg-titulo">Bienvenido de nuevo</h1>
            <p class="lg-sub">Entra con tu usuario de MediTrack.</p>

            @if (session('status'))
                <div class="lg-aviso bien" role="status"><i class="bi bi-check2-circle"></i><span>{{ session('status') }}</span></div>
            @endif

            @if ($hayError)
                <div class="lg-aviso mal" role="alert">
                    <i class="bi bi-shield-exclamation"></i>
                    <div>
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                </div>
            @endif

            <form id="login-form" method="POST" action="{{ route('login') }}" novalidate>
                @csrf
                {{-- El servidor recibe el correo completo: usuario + @meditrack.com --}}
                <input type="hidden" name="email" id="email" value="{{ $emailViejo }}">

                <div class="lg-campo">
                    <label for="usuario">Usuario</label>
                    <div class="lg-in @error('email') mal @enderror">
                        <i class="bi bi-person-badge" aria-hidden="true"></i>
                        <input type="text" id="usuario" value="{{ $usuarioViejo }}" maxlength="60" placeholder="tu.usuario"
                               data-dominio="{{ $dominioLogin }}" required autofocus autocomplete="username" autocapitalize="none" spellcheck="false"
                               @error('email') aria-invalid="true" @enderror>
                        <span class="lg-dominio mono" id="dominio-fijo" aria-hidden="true">{{ $dominioLogin }}</span>
                    </div>
                </div>

                <div class="lg-campo">
                    <label for="password">Contraseña</label>
                    <div class="lg-in @error('password') mal @enderror">
                        <i class="bi bi-key" aria-hidden="true"></i>
                        <input type="password" name="password" id="password" required autocomplete="current-password" placeholder="Mínimo 8 caracteres" aria-describedby="caps-warning">
                        <button type="button" class="lg-ver" id="toggle-password" aria-label="Mostrar contraseña" aria-controls="password" aria-pressed="false"><i class="bi bi-eye" id="password-icon"></i></button>
                    </div>
                    <p class="lg-ayuda ambar" id="caps-warning" hidden aria-live="polite"><i class="bi bi-capslock"></i> Bloq Mayús está activado</p>
                    @error('password')<p class="lg-ayuda mal">{{ $message }}</p>@enderror
                </div>

                <div class="lg-fila">
                    <label class="lg-sw" for="remember">
                        <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                        <i aria-hidden="true"></i><span>Recordar sesión en este equipo</span>
                    </label>
                </div>

                <button type="submit" class="lg-entrar" id="submit-btn">
                    <span class="lg-gira" id="submit-spinner" hidden></span>
                    <span id="submit-label">Entrar</span>
                    <kbd id="submit-kbd" aria-hidden="true">↵</kbd>
                </button>

                <p class="lg-olvido">
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
                    @else
                        ¿Olvidaste tu contraseña? Pide a un administrador que la restablezca en <b>Personal</b>.
                    @endif
                </p>
            </form>

            <footer class="lg-legal">
                <span>MediTrack © {{ date('Y') }}</span>
                <span><i class="bi bi-lock"></i> Conexión segura</span>
            </footer>
        </div>
    </section>
</main>

<!-- Cortina al entrar: la misma del panel (el panel la retira al cargar) -->
<div id="lg-cortina" aria-hidden="true">
    <div class="lg-cortina-in">
        <svg viewBox="0 0 44 44"><path d="M16 4h12v12h12v12H28v12H16V28H4V16h12z" fill="#a6efe2"/><path class="lg-latido" d="M7 23h8l3-6 4 11 3-7 2 2h10" fill="none" stroke="#073f3b" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <b>MediTrack</b>
        <small>{{ $nombreClinica }}</small>
        <i class="lg-cortina-barra"></i>
    </div>
</div>

@verbatim
<style>
:root {
    --tinta: #0b1e1c; --texto: #2c3d3a; --media: #56625f; --linea: #dbe4e1; --linea2: #edf2f1; --niebla: #eef3f1; --papel: #fff;
    --cred: #0b5a54; --cred2: #073f3b; --foil: #a6efe2; --teal: #0e7c72; --velo: #e2efec;
    --rosa: #c8102e; --rosav: #fdecef; --ambar: #9a5800; --ambarv: #fdf3e1; --verde: #08734f; --verdev: #e5f4ee;
    --e: cubic-bezier(.2, .8, .2, 1);
    --disp: 'Bricolage Grotesque', ui-sans-serif, system-ui, sans-serif; --ui: 'Geist', ui-sans-serif, system-ui, sans-serif; --mono: 'Geist Mono', ui-monospace, monospace;
}
* { box-sizing: border-box; }
body { margin: 0; background: #040d0c; color: var(--texto); font: 450 14px/1.45 var(--ui); }
.mono { font-family: var(--mono); letter-spacing: .03em; }
:focus-visible { outline: 2px solid var(--teal); outline-offset: 2px; }
button { cursor: pointer; }

.lg-pag { min-height: 100vh; display: grid; grid-template-columns: minmax(0, 1fr) minmax(400px, 480px); }

/* ---------- Escenario ---------- */
.lg-esc { position: relative; overflow: hidden; display: grid; grid-template-rows: auto 1fr auto; padding: 32px 40px; color: #eafaf6;
    background: radial-gradient(80% 70% at 50% 34%, #134a45 0%, #0a2826 48%, #040d0c 100%); }
.lg-gl { position: absolute; inset: 0; opacity: .16; pointer-events: none; }
.lg-gl svg { width: 100%; height: 100%; }
.lg-luz { position: absolute; left: 50%; top: -40px; width: 620px; height: 82%; margin-left: -310px; pointer-events: none;
    background: linear-gradient(180deg, rgba(196, 255, 244, .26), rgba(166, 239, 226, .04) 78%, transparent); clip-path: polygon(41% 0, 59% 0, 100% 100%, 0 100%); filter: blur(18px);
    animation: lgLuz .9s var(--e) both; }
@keyframes lgLuz { from { opacity: 0; } }
.lg-marca { position: relative; display: flex; align-items: center; gap: 12px; }
.lg-cruz { width: 40px; height: 40px; filter: drop-shadow(0 10px 18px rgba(0, 0, 0, .35)); }
.lg-latido { stroke-dasharray: 40; stroke-dashoffset: 40; animation: lgLatido 1.1s var(--e) .2s forwards; }
@keyframes lgLatido { to { stroke-dashoffset: 0; } }
.lg-marca b { display: block; font: 750 24px/1 var(--disp); color: #fff; }
.lg-marca small { display: block; margin-top: 4px; font: 500 11.5px var(--mono); letter-spacing: .12em; text-transform: uppercase; color: var(--foil); opacity: .85; }
.lg-reloj { position: absolute; top: 34px; right: 40px; text-align: right; }
.lg-reloj b { display: block; font: 700 30px/1 var(--disp); font-variant-numeric: tabular-nums; color: #fff; }
.lg-reloj span { font-size: 12.5px; color: rgba(234, 250, 246, .7); }
.lg-pie { position: relative; text-align: center; font-size: 12.5px; color: rgba(234, 250, 246, .55); }

/* Gafete */
.lg-gafete-zona { position: relative; display: grid; place-items: center; perspective: 1100px; padding: 30px 0 10px; }
.lg-colgante { display: flex; flex-direction: column; align-items: center; margin-top: -32px; }
.lg-cordon { position: relative; z-index: 2; width: 20px; height: 130px; margin-bottom: -18px; border-radius: 0 0 5px 5px;
    -webkit-mask-image: linear-gradient(180deg, transparent, #000 45%); mask-image: linear-gradient(180deg, transparent, #000 45%);
    background: repeating-linear-gradient(180deg, #0e7c72 0 10px, #0b5a54 10px 20px); box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .08); }
.lg-colgante { transform-style: preserve-3d; transform-origin: 50% 0; will-change: transform; pointer-events: none; }
.lg-colgante > .lg-gafete { pointer-events: auto; }
.lg-gafete { position: relative; width: 264px; transform-style: preserve-3d; cursor: grab; touch-action: none; user-select: none; -webkit-user-select: none; outline-offset: 6px; border-radius: 18px;
    transform: rotateX(var(--rx, 6deg)) rotateY(var(--ry, -10deg)); transition: transform .6s var(--e);
    animation: lgCuelga .9s cubic-bezier(.3, 1.4, .5, 1) .1s backwards; }
.lg-gafete.arrastrando { cursor: grabbing; transition: transform .2s var(--e); }
@keyframes lgCuelga { from { opacity: 0; translate: 0 -60px; rotate: -8deg; } }
.lg-g-cara { position: relative; padding-bottom: 14px; border-radius: 18px; background: #fbfdfc; color: var(--tinta); overflow: hidden;
    backface-visibility: hidden; -webkit-backface-visibility: hidden;
    box-shadow: 0 40px 70px -30px rgba(0, 0, 0, .75), 0 0 0 1px rgba(255, 255, 255, .6) inset; }
.lg-brillo { position: absolute; inset: 0; pointer-events: none; z-index: 4;
    background: linear-gradient(115deg, transparent 30%, rgba(255, 255, 255, .6) 46%, transparent 62%); background-size: 260% 100%; background-position: var(--brillo, 70%) 0; mix-blend-mode: soft-light; }
/* Persiana de privacidad: el gafete se tapa mientras escribes la contraseña */
.lg-persiana { position: absolute; inset: 0; border-radius: 50%; overflow: hidden; pointer-events: none; }
.lg-persiana i { position: absolute; left: 0; right: 0; height: 50%; background: repeating-linear-gradient(180deg, #0b5a54 0 7px, #0a4f4a 7px 9px); transition: transform .35s var(--e); }
.lg-persiana i:first-child { top: 0; transform: translateY(-101%); }
.lg-persiana i:nth-child(2) { bottom: 0; transform: translateY(101%); }
.lg-persiana em { position: absolute; left: 50%; top: 50%; translate: -50% -50%; font-size: 22px; color: var(--foil); opacity: 0; scale: .5; transition: opacity .2s, scale .3s var(--e); }
.lg-gafete.privado .lg-persiana i { transform: none; }
.lg-gafete.privado .lg-persiana em { opacity: 1; scale: 1; transition-delay: .15s; }
.lg-gafete.espia .lg-persiana i:first-child { transform: translateY(-62%); }
.lg-gafete.espia .lg-persiana i:nth-child(2) { transform: translateY(62%); }
.lg-gafete.espia .lg-persiana em { opacity: 0; }
.lg-ranura { position: absolute; z-index: 3; top: 10px; left: 50%; width: 46px; height: 8px; margin-left: -23px; border-radius: 6px; background: #0a2826; box-shadow: inset 0 2px 3px rgba(0, 0, 0, .5); }
.lg-g-cab { position: relative; height: 112px; padding: 28px 18px 0; display: flex; justify-content: space-between; align-items: flex-start; overflow: hidden;
    background: radial-gradient(120% 160% at 85% 0%, #13756d 0%, #0b5a54 46%, #073f3b 100%); color: #fff; }
.lg-g-gl { position: absolute; inset: 0; opacity: .5; }
.lg-g-gl svg { width: 100%; height: 100%; }
.lg-g-cab p { position: relative; }
.lg-g-cab b { display: block; font: 750 17px/1 var(--disp); }
.lg-g-cab span { display: block; margin-top: 4px; font: 500 9.5px var(--mono); letter-spacing: .14em; text-transform: uppercase; color: var(--foil); }
.lg-g-cab svg { position: relative; width: 24px; height: 24px; color: var(--foil); }
.lg-g-foto { position: relative; width: 108px; height: 108px; margin: -42px auto 0; border-radius: 50%; display: grid; place-items: center;
    background: #fff; box-shadow: 0 0 0 5px #fbfdfc, 0 10px 22px -10px rgba(11, 30, 28, .5); color: #0e7c72; }
.lg-g-roseta { position: absolute; inset: 6px; }
.lg-g-roseta svg { width: 100%; height: 100%; }
.lg-g-foto b { position: relative; font: 800 34px/1 var(--disp); font-stretch: 85%; color: var(--cred); text-transform: uppercase; background: #fff; padding: 4px 6px; border-radius: 10px; }
.lg-g-nom { margin: 12px 16px 0; text-align: center; font: 750 24px/1.05 var(--disp); font-stretch: 82%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-transform: capitalize; }
.lg-g-nom.vacio { color: #a5b2ae; }
.lg-g-mail { margin: 4px 16px 0; text-align: center; font-size: 11px; color: var(--media); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.lg-g-rol { width: max-content; margin: 10px auto 0; padding: 3px 12px; border-radius: 999px; background: var(--velo); color: var(--cred); font: 600 11.5px var(--ui); }
.lg-g-est { margin: 16px 14px 0; padding: 7px 10px; border-radius: 10px; display: flex; align-items: center; justify-content: center; gap: 7px; font: 600 12px var(--ui); background: var(--niebla); color: var(--media); transition: background .25s, color .25s; }
.lg-g-est i { width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
.lg-gafete.listo .lg-g-est { background: var(--verdev); color: var(--verde); }
.lg-gafete.verifica .lg-g-est { background: var(--velo); color: var(--cred); }
.lg-gafete.verifica .lg-g-est i { animation: lgPunto .8s ease-in-out infinite; }
@keyframes lgPunto { 50% { opacity: .25; } }
.lg-gafete.denegado .lg-g-est { background: var(--rosav); color: var(--rosa); }
.lg-gafete.denegado .lg-g-cab { background: radial-gradient(120% 160% at 85% 0%, #b3283f 0%, #8f1a2c 50%, #5c0f1c 100%); }
.lg-escaneo { position: absolute; left: 0; right: 0; top: 0; height: 60px; pointer-events: none; opacity: 0;
    background: linear-gradient(180deg, transparent, rgba(166, 239, 226, .55) 48%, #a6efe2 50%, rgba(166, 239, 226, .55) 52%, transparent); }
.lg-gafete.verifica .lg-escaneo { animation: lgEscanea 1.1s ease-in-out infinite; }
@keyframes lgEscanea { 0% { opacity: 0; top: -60px; } 15% { opacity: 1; } 85% { opacity: 1; } 100% { opacity: 0; top: 100%; } }

/* ---------- Formulario ---------- */
.lg-form { position: relative; background: var(--papel); display: grid; align-items: center; padding: 40px 48px; }
.lg-form-in { width: 100%; max-width: 380px; margin: 0 auto; animation: lgSube .6s var(--e) .15s both; }
@keyframes lgSube { from { opacity: 0; translate: 0 14px; } }
.lg-saludo { font: 600 12px var(--mono); letter-spacing: .12em; text-transform: uppercase; color: var(--teal); }
.lg-form h1 { margin: 6px 0 0; font: 750 34px/1.05 var(--disp); letter-spacing: -.015em; color: var(--tinta); }
.lg-sub { margin-top: 6px; font-size: 14.5px; color: var(--media); }
.lg-aviso { display: flex; gap: 10px; margin-top: 18px; padding: 11px 13px; border-radius: 12px; font-size: 13.5px; font-weight: 550; }
.lg-aviso .bi { font-size: 16px; }
.lg-aviso.mal { background: var(--rosav); color: var(--rosa); }
.lg-aviso.bien { background: var(--verdev); color: var(--verde); }
#login-form { margin-top: 24px; display: grid; grid-template-columns: minmax(0, 1fr); gap: 16px; }
#login-form > *, .lg-esc, .lg-form { min-width: 0; }
.lg-campo label { display: block; margin-bottom: 6px; font-size: 13px; font-weight: 600; color: var(--tinta); }
.lg-in { display: flex; align-items: center; gap: 10px; height: 50px; padding: 0 8px 0 14px; border-radius: 12px; border: 1px solid var(--linea); background: var(--papel); transition: border-color .15s, box-shadow .15s; }
.lg-in:focus-within { border-color: var(--teal); box-shadow: 0 0 0 4px rgba(14, 124, 114, .13); }
.lg-in.mal { border-color: #e9919f; box-shadow: 0 0 0 4px rgba(200, 16, 46, .08); }
.lg-in > .bi { font-size: 17px; color: var(--cred); }
.lg-in input { flex: 1; min-width: 0; width: 100%; height: 100%; border: 0; outline: 0 !important; box-shadow: none !important; background: none; font: 500 15.5px var(--ui); color: var(--tinta); }
.lg-in input::placeholder { color: #93a19d; }
.lg-dominio { padding: 4px 8px; border-radius: 8px; background: var(--niebla); font-size: 12.5px; color: var(--media); transition: opacity .15s; }
.lg-dominio.oculto { opacity: 0; }
.lg-ver { width: 36px; height: 36px; border-radius: 9px; display: grid; place-items: center; border: 0; background: none; color: var(--media); font-size: 16px; }
.lg-ver:hover { background: var(--niebla); color: var(--tinta); }
.lg-ayuda { margin-top: 6px; font-size: 12.5px; }
.lg-ayuda.mal { color: var(--rosa); font-weight: 550; }
.lg-ayuda.ambar { color: var(--ambar); font-weight: 550; display: flex; align-items: center; gap: 6px; }
.lg-ayuda[hidden] { display: none; }
.lg-fila { display: flex; align-items: center; justify-content: space-between; }
.lg-sw { display: inline-flex; align-items: center; gap: 10px; cursor: pointer; font-size: 13.5px; font-weight: 550; color: var(--texto); user-select: none; }
.lg-sw input { position: absolute; opacity: 0; width: 1px; height: 1px; }
.lg-sw i { position: relative; width: 36px; height: 20px; border-radius: 999px; background: var(--linea); transition: background .18s; flex: none; }
.lg-sw i:after { content: ""; position: absolute; left: 2px; top: 2px; width: 16px; height: 16px; border-radius: 50%; background: #fff; box-shadow: 0 1px 3px rgba(0, 0, 0, .25); transition: translate .18s var(--e); }
.lg-sw input:checked + i { background: var(--teal); }
.lg-sw input:checked + i:after { translate: 16px 0; }
.lg-sw input:focus-visible + i { outline: 2px solid var(--teal); outline-offset: 2px; }
.lg-entrar { margin-top: 4px; height: 54px; border: 0; border-radius: 14px; display: inline-flex; align-items: center; justify-content: center; gap: 10px;
    background: var(--tinta); color: #fff; font: 650 16px var(--ui); transition: background .15s, transform .1s; }
.lg-entrar:hover:not(:disabled) { background: #163532; }
.lg-entrar:active:not(:disabled) { transform: scale(.985); }
.lg-entrar:disabled { cursor: wait; opacity: .85; }
.lg-entrar kbd { font: 600 12px var(--mono); border: 1px solid rgba(255, 255, 255, .25); border-radius: 6px; padding: 1px 7px; color: var(--foil); }
.lg-gira { width: 16px; height: 16px; border-radius: 50%; border: 2px solid rgba(255, 255, 255, .35); border-top-color: #fff; animation: lgGira .7s linear infinite; }
.lg-gira[hidden] { display: none; }
@keyframes lgGira { to { rotate: 360deg; } }
.lg-olvido { font-size: 13px; color: var(--media); text-align: center; }
.lg-olvido a { color: var(--teal); font-weight: 600; }
.lg-olvido b { color: var(--tinta); font-weight: 600; }
.lg-legal { margin-top: 34px; padding-top: 16px; border-top: 1px solid var(--linea2); display: flex; justify-content: space-between; font-size: 12px; color: var(--media); }

/* Cortina */
#lg-cortina { position: fixed; inset: 0; z-index: 100; display: grid; place-items: center; opacity: 0; visibility: hidden; pointer-events: none;
    background: radial-gradient(80% 70% at 50% 40%, #134a45 0%, #0b5a54 30%, #073f3b 70%, #04201e 100%); color: #eafaf6; transition: opacity .12s var(--e), visibility 0s linear .12s; }
#lg-cortina.on { opacity: 1; visibility: visible; pointer-events: auto; transition: opacity .12s var(--e), visibility 0s; }
.lg-cortina-in { display: flex; flex-direction: column; align-items: center; gap: 14px; }
.lg-cortina-in svg { width: 76px; height: 76px; }
.lg-cortina-in b { font: 700 34px/1 var(--disp); color: #fff; }
.lg-cortina-in small { margin-top: -8px; font: 600 12px var(--mono); letter-spacing: .14em; text-transform: uppercase; color: var(--foil); }
.lg-cortina-barra { position: relative; width: 120px; height: 3px; border-radius: 99px; overflow: hidden; background: rgba(166, 239, 226, .22); }
.lg-cortina-barra:after { content: ""; position: absolute; top: 0; bottom: 0; left: -40%; width: 40%; border-radius: 99px; background: var(--foil); animation: lgBarra .8s ease-in-out infinite; }
@keyframes lgBarra { to { left: 100%; } }

/* ---------- Pantallas chicas: el gafete arriba, más pequeño ---------- */
@media (max-width: 960px) {
    .lg-pag { grid-template-columns: minmax(0, 1fr); }
    .lg-esc { padding: 20px 20px 18px; grid-template-rows: auto auto; }
    .lg-reloj, .lg-pie, .lg-cordon { display: none; }
    .lg-colgante { margin-top: 0; width: 100%; }
    .lg-gafete-zona { padding: 14px 0 0; }
    .lg-gafete { width: 100%; max-width: 360px; transform: none !important; cursor: default; }
    .lg-g-cara.frente { display: grid; grid-template-columns: 74px minmax(0, 1fr); align-items: center; gap: 0 12px; padding: 12px 14px; border-radius: 14px; }
        .lg-ranura, .lg-g-cab, .lg-g-rol { display: none; }
    .lg-g-foto { grid-row: 1 / 4; width: 70px; height: 70px; margin: 0; box-shadow: 0 0 0 3px var(--velo); }
    .lg-g-foto b { font-size: 22px; }
    .lg-g-nom, .lg-g-mail { margin: 0; text-align: left; }
    .lg-g-nom { font-size: 20px; }
    .lg-g-est { margin: 6px 0 0; justify-content: flex-start; padding: 4px 8px; }
    .lg-form { padding: 28px 22px 32px; align-items: start; }
    .lg-form h1 { font-size: 28px; }
}
@media (prefers-reduced-motion: reduce) {
    *, *:before, *:after { animation-duration: 1ms !important; animation-iteration-count: 1 !important; transition-duration: 1ms !important; }
    .lg-gafete { transform: none; }
}
</style>

<script>
(function () {
    const $ = id => document.getElementById(id);
    try { sessionStorage.removeItem('ly-cortina'); } catch (e) {}

    /* Patrones de seguridad (mismo algoritmo que el panel) */
    const hash = s => { let h = 2166136261; for (const c of String(s)) { h ^= c.charCodeAt(0); h = Math.imul(h, 16777619); } return h >>> 0; };
    function roseta(seed, size) {
        const hv = hash(seed || 'MT'), n = 18 + hv % 9, c = size / 2; let d = '';
        for (let k = 0; k < 9; k++) {
            const A = size * .11 + k * size * .042, B = size * .03 + (k % 3) * size * .012;
            for (let i = 0; i <= 160; i++) { const t = i / 160 * 2 * Math.PI, r = A + B * Math.cos(n * t + k * .4); d += (i ? 'L' : 'M') + (c + r * Math.cos(t)).toFixed(1) + ',' + (c + r * Math.sin(t)).toFixed(1); }
            d += 'Z';
        }
        return '<svg viewBox="0 0 ' + size + ' ' + size + '"><path d="' + d + '" fill="none" stroke="currentColor" stroke-width=".6"/></svg>';
    }
    function guilloche(seed, op) {
        const hv = hash(seed), w = 300, h = 110, n = 22 + hv % 10, cx = w * .86, cy = h * .5; let d = '';
        for (let k = 0; k < 10; k++) {
            const A = 34 + k * 6, B = 5 + (k % 4) * 1.6, ph = k * .22 + (hv % 100) / 50;
            for (let i = 0; i <= 220; i++) { const t = i / 220 * 2 * Math.PI, r = A + B * Math.cos(n * t + ph); d += (i ? 'L' : 'M') + (cx + r * Math.cos(t)).toFixed(1) + ',' + (cy + r * Math.sin(t) * .9).toFixed(1); }
            d += 'Z';
        }
        for (let k = 0; k < 4; k++) { const y0 = 16 + k * 26; for (let x = 0; x <= w; x += 5) d += (x ? 'L' : 'M') + x + ',' + (y0 + 5 * Math.sin(x / 23 + k * .9 + hv % 5) + 2 * Math.sin(x / 7 + k)).toFixed(1); }
        return '<svg viewBox="0 0 ' + w + ' ' + h + '" preserveAspectRatio="xMidYMid slice"><path d="' + d + '" fill="none" stroke="#a6efe2" stroke-width=".5" opacity="' + op + '"/></svg>';
    }
    document.addEventListener('DOMContentLoaded', () => {
        $('lg-gl').innerHTML = guilloche('MEDITRACK', '.28');
        $('lg-g-gl').innerHTML = guilloche('ACCESO', '.4');

        /* Saludo y reloj */
        const DIAS = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
        const MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        const pad = n => String(n).padStart(2, '0');
        const reloj = () => {
            const a = new Date();
            $('lg-hora').textContent = pad(a.getHours()) + ':' + pad(a.getMinutes());
            $('lg-fecha').textContent = DIAS[a.getDay()].replace(/^./, c => c.toUpperCase()) + ', ' + a.getDate() + ' de ' + MESES[a.getMonth()];
            const h = a.getHours();
            $('lg-saludo').textContent = h < 12 ? 'Buenos días' : h < 19 ? 'Buenas tardes' : 'Buenas noches';
        };
        reloj(); setInterval(reloj, 15000);

        /* Usuario + dominio fijo (@meditrack.com) y gafete en vivo */
        const $usuario = $('usuario'), $email = $('email'), $dominio = $('dominio-fijo'), $pwd = $('password'), $gafete = $('lg-gafete');
        const DOMINIO = $usuario.dataset.dominio;
        let ultimaSemilla = null;
        const pintarGafete = () => {
            const v = $usuario.value.trim(), correo = $email.value;
            const nombre = v.includes('@') ? v.split('@')[0] : v;
            $('lg-nom').textContent = nombre ? nombre.replace(/[._-]+/g, ' ') : 'Tu usuario';
            $('lg-nom').classList.toggle('vacio', !nombre);
            $('lg-mail').textContent = correo || ('usuario' + DOMINIO);
            const ini = (nombre.replace(/[^a-záéíóúñ]/gi, ' ').trim().split(/\s+/).map(p => p[0]).join('').slice(0, 2) || nombre.slice(0, 2) || 'MT');
            $('lg-ini').textContent = ini;
            const semilla = correo || 'MT';
            if (semilla !== ultimaSemilla) {
                ultimaSemilla = semilla;
                $('lg-roseta').innerHTML = roseta(semilla, 100);
            }
            const listo = !!nombre && $pwd.value.length >= 8;
            $gafete.classList.toggle('listo', listo);
            if (!$gafete.classList.contains('verifica') && !$gafete.classList.contains('denegado')) {
                $('lg-est').lastElementChild.textContent = listo ? 'Listo para entrar' : (nombre ? 'Escribe tu contraseña' : 'Esperando credenciales');
            }
        };
        const sincronizarCorreo = () => {
            let v = $usuario.value.replace(/\s+/g, '');
            // Si pegan el correo completo, se deja solo el usuario
            if (v.toLowerCase().endsWith(DOMINIO)) v = v.slice(0, -DOMINIO.length);
            if (v !== $usuario.value) $usuario.value = v;
            // Si escriben otro dominio (una cuenta externa), se respeta tal cual
            const tieneArroba = v.includes('@');
            $dominio.classList.toggle('oculto', tieneArroba);
            $email.value = v ? (tieneArroba ? v : v + DOMINIO) : '';
            pintarGafete();
        };
        const quitarDenegado = () => {
            if (!$gafete.classList.contains('denegado')) return;
            $gafete.classList.remove('denegado');
            document.querySelectorAll('.lg-in.mal').forEach(el => el.classList.remove('mal'));
        };
        $usuario.addEventListener('input', () => { quitarDenegado(); sincronizarCorreo(); empujon((Math.random() < .5 ? -1 : 1) * .35); });
        $usuario.addEventListener('change', sincronizarCorreo);   // autocompletado del navegador
        $pwd.addEventListener('input', () => { quitarDenegado(); pintarGafete(); });
        sincronizarCorreo();
        if ($usuario.value && $gafete.classList.contains('denegado')) $pwd.focus();

        /* ---------- Gafete interactivo: se inclina y se columpia del cordón ---------- */
        const zona = document.querySelector('.lg-esc'), $colgante = $('lg-colgante');
        const quieto = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const compacto = () => window.matchMedia('(max-width: 960px)').matches;
        const fis = { a: 0, v: 0, corre: false, mano: false, ultimo: 0 };
        const pintarColumpio = () => { $colgante.style.transform = 'rotate(' + fis.a.toFixed(2) + 'deg)'; };
        const paso = t => {
            const dt = Math.min(3, (t - (fis.ultimo || t)) / 16.67 || 1); fis.ultimo = t;
            if (!fis.mano) {
                // Péndulo amortiguado: regresa al centro con unos vaivenes
                fis.v += (-.022 * fis.a - .055 * fis.v) * dt;
                fis.a += fis.v * dt;
            }
            pintarColumpio();
            if (fis.mano || Math.abs(fis.a) > .04 || Math.abs(fis.v) > .02) requestAnimationFrame(paso);
            else { fis.a = 0; fis.v = 0; fis.corre = false; fis.ultimo = 0; pintarColumpio(); }
        };
        const mover = () => { if (!fis.corre && !quieto && !compacto()) { fis.corre = true; requestAnimationFrame(paso); } };
        // Empujón: al teclear, al negar el acceso o al fallar la validación
        const empujon = f => { if (quieto || compacto()) return; fis.v += f; mover(); };

        // Se inclina hacia el cursor y el brillo lo sigue
        if (window.matchMedia('(pointer: fine)').matches && !quieto) {
            zona.addEventListener('pointermove', e => {
                if (fis.mano) return;
                const r = zona.getBoundingClientRect(), x = (e.clientX - r.left) / r.width - .5, y = (e.clientY - r.top) / r.height - .5;
                $gafete.style.setProperty('--ry', (x * 22).toFixed(1) + 'deg');
                $gafete.style.setProperty('--rx', (-y * 16).toFixed(1) + 'deg');
                $gafete.style.setProperty('--brillo', (50 + x * 80).toFixed(0) + '%');
            });
            zona.addEventListener('pointerleave', () => ['--ry', '--rx', '--brillo'].forEach(k => $gafete.style.removeProperty(k)));
        }

        // Arrastrar: se columpia siguiendo la mano; un toque le da un empujón
        $gafete.addEventListener('pointerdown', e => {
            if (e.button !== 0 || compacto()) return;
            const r = $colgante.getBoundingClientRect(), px = r.left + r.width / 2, py = r.top;
            const x0 = e.clientX, y0 = e.clientY;
            let movio = false, ant = fis.a, tAnt = performance.now();
            try { $gafete.setPointerCapture(e.pointerId); } catch (er) {}
            const mueve = ev => {
                if (!movio && Math.hypot(ev.clientX - x0, ev.clientY - y0) < 6) return;
                if (!movio) { movio = true; fis.mano = true; $gafete.classList.add('arrastrando'); mover(); }
                const ang = -Math.atan2(ev.clientX - px, Math.max(40, ev.clientY - py)) * 180 / Math.PI;
                const ahora = performance.now();
                fis.a = Math.max(-55, Math.min(55, ang));
                fis.v = (fis.a - ant) / Math.max(1, (ahora - tAnt) / 16.67);
                ant = fis.a; tAnt = ahora;
            };
            const suelta = () => {
                $gafete.removeEventListener('pointermove', mueve);
                $gafete.removeEventListener('pointerup', suelta);
                $gafete.removeEventListener('pointercancel', suelta);
                $gafete.classList.remove('arrastrando');
                fis.mano = false;
                if (!movio) empujon((e.clientX < px ? 1 : -1) * 2); else mover();
            };
            $gafete.addEventListener('pointermove', mueve);
            $gafete.addEventListener('pointerup', suelta);
            $gafete.addEventListener('pointercancel', suelta);
        });
        $gafete.addEventListener('keydown', e => {
            if (e.key === 'ArrowLeft') { e.preventDefault(); empujon(-2.5); }
            if (e.key === 'ArrowRight') { e.preventDefault(); empujon(2.5); }
        });
        // Si el acceso fue negado, el gafete se sacude al llegar
        if ($gafete.classList.contains('denegado')) setTimeout(() => empujon(3.2), 900);

        // Persiana: se tapa mientras escribes la contraseña y se asoma si la muestras
        const privacidad = () => {
            const enClave = document.activeElement === $pwd || document.activeElement === $('toggle-password');
            $gafete.classList.toggle('privado', enClave && $pwd.type === 'password');
            $gafete.classList.toggle('espia', enClave && $pwd.type === 'text');
        };
        $pwd.addEventListener('focus', privacidad);
        $pwd.addEventListener('blur', () => setTimeout(privacidad, 0));

        /* Mostrar u ocultar contraseña */
        const $toggle = $('toggle-password'), $icon = $('password-icon');
        $toggle.addEventListener('click', () => {
            const ver = $pwd.type === 'password';
            $pwd.type = ver ? 'text' : 'password';
            $icon.className = 'bi ' + (ver ? 'bi-eye-slash' : 'bi-eye');
            $toggle.setAttribute('aria-pressed', String(ver));
            $toggle.setAttribute('aria-label', ver ? 'Ocultar contraseña' : 'Mostrar contraseña');
            $pwd.focus();
            privacidad();
        });

        /* Bloq Mayús */
        const $caps = $('caps-warning');
        ['keydown', 'keyup'].forEach(ev => $pwd.addEventListener(ev, e => { if (e.getModifierState) $caps.hidden = !e.getModifierState('CapsLock'); }));
        $pwd.addEventListener('blur', () => { $caps.hidden = true; });

        /* Enviar: valida, escanea el gafete y cubre con la cortina del panel */
        const $form = $('login-form'), $btn = $('submit-btn');
        const cargando = on => {
            $btn.disabled = on;
            $('submit-label').textContent = on ? 'Verificando…' : 'Entrar';
            $('submit-spinner').hidden = !on;
            $('submit-kbd').hidden = on;
            $gafete.classList.toggle('verifica', on);
            $('lg-est').lastElementChild.textContent = on ? 'Verificando acceso…' : 'Listo para entrar';
        };
        $form.addEventListener('submit', e => {
            sincronizarCorreo();
            const faltaUsuario = !$email.value, faltaClave = !$pwd.value;
            if (faltaUsuario || faltaClave) {
                e.preventDefault();
                const campo = faltaUsuario ? $usuario : $pwd;
                campo.closest('.lg-in').classList.add('mal');
                campo.focus();
                $gafete.classList.remove('denegado'); void $gafete.offsetWidth;
                empujon(2.6);
                return;
            }
            if ($pwd.value.length < 8) {
                e.preventDefault();
                $pwd.closest('.lg-in').classList.add('mal');
                $('lg-est').lastElementChild.textContent = 'La contraseña tiene al menos 8 caracteres';
                $pwd.focus();
                return;
            }
            cargando(true);
            // Si el servidor tarda, aparece la cortina; el panel la recibe y la retira al cargar
            setTimeout(() => {
                if (!$btn.disabled) return;
                try { sessionStorage.setItem('ly-cortina', String(Date.now())); } catch (er) {}
                $('lg-cortina').classList.add('on');
            }, 450);
        });
        // Al volver con «Atrás», todo regresa a su estado
        window.addEventListener('pageshow', e => { if (e.persisted) { cargando(false); $('lg-cortina').classList.remove('on'); } });
    });
})();
</script>
@endverbatim
@endsection