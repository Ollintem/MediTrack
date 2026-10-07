@extends('layouts.admin')

@section('content')
@php
    $rolesJs = collect($roles ?? [])->map(fn ($r) => ['id' => (string) $r->id, 'nombre' => $r->nombre])->values();
    $puedeCrearRoles = auth()->user()->tienePermiso('Roles', 'crear');
    $dominio = 'meditrack.com';
@endphp

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="space-y-6 max-w-6xl mx-auto pb-12" x-data="altaPersonal()">

    {{-- ===================== ENCABEZADO ===================== --}}
    <div class="aparece relative overflow-hidden rounded-3xl bg-gradient-to-br from-teal-900 via-teal-800 to-emerald-700 text-white shadow-xl shadow-teal-900/20">
        <div class="flotar absolute -right-16 -top-20 w-64 h-64 rounded-full bg-white/5"></div>
        <div class="flotar absolute right-48 -bottom-24 w-56 h-56 rounded-full bg-emerald-400/10" style="animation-delay:-2.5s"></div>
        <svg class="absolute inset-x-0 bottom-2 w-full h-12 opacity-25 pointer-events-none" viewBox="0 0 800 40" preserveAspectRatio="none" fill="none" aria-hidden="true">
            <path class="ecg-linea" d="M0 20 H300 L315 20 L325 6 L338 34 L350 2 L364 38 L374 20 H520 L532 13 L544 20 H800" stroke="#6ee7b7" stroke-width="2" stroke-linejoin="round"/>
        </svg>
        <i class="bi bi-person-plus-fill flotar absolute right-14 top-5 text-[96px] leading-none text-white/10 pointer-events-none hidden md:block" style="animation-delay:-1s"></i>

        <div class="relative p-6 sm:p-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2 min-w-0">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/15 text-[11px] font-bold uppercase tracking-wide text-emerald-300 backdrop-blur-md">
                    <i class="bi bi-heart-pulse-fill latido-lento"></i> Alta de personal
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight flex items-center gap-3">
                    Registrar nuevo personal <i class="bi bi-heart-pulse-fill text-emerald-300 text-2xl latido-lento"></i>
                </h1>
                <p class="text-xs text-teal-100/80 font-medium max-w-xl leading-relaxed">
                    Da de alta a un miembro del equipo médico o administrativo capturando su información personal, domicilio y perfil laboral.
                </p>
                {{-- Avance --}}
                <div class="flex items-center gap-3 pt-1 max-w-sm">
                    <div class="flex-1 h-1.5 rounded-full bg-white/15 overflow-hidden">
                        <div class="h-full rounded-full bg-gradient-to-r from-emerald-300 to-teal-200 transition-all duration-500" :style="'width:' + avance + '%'"></div>
                    </div>
                    <span class="text-[10px] font-black text-teal-100 tabular-nums" x-text="listos + ' de ' + requeridos.length + ' datos obligatorios'"></span>
                </div>
            </div>
            <a href="{{ route('personal.index') }}" @click="salir($event)"
               class="shrink-0 bg-white/10 hover:bg-white/20 text-white font-bold px-4 py-2.5 rounded-2xl text-xs border border-white/20 backdrop-blur-md transition-all hover:-translate-y-0.5 flex items-center gap-2 self-start md:self-auto">
                <i class="bi bi-arrow-left"></i> Volver al directorio
            </a>
        </div>
    </div>

    {{-- ===================== ERRORES DEL SERVIDOR ===================== --}}
    @if ($errors->any())
        <div class="aparece p-4 bg-rose-50 rounded-3xl border border-rose-200 text-rose-800 flex gap-3 sacudir" style="--d:80ms">
            <i class="bi bi-exclamation-octagon-fill text-rose-500 text-xl shrink-0"></i>
            <div>
                <p class="font-black text-sm">Revisa estos datos:</p>
                <ul class="mt-1 text-xs space-y-0.5 font-semibold text-rose-700">
                    @foreach ($errors->all() as $error)
                        <li class="flex items-start gap-1.5"><i class="bi bi-dot"></i>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form action="{{ route('personal.store') }}" method="POST" @submit="enviar($event)" class="grid grid-cols-1 lg:grid-cols-[1fr_300px] gap-6 items-start" novalidate>
        @csrf

        <div class="space-y-6 min-w-0">

            {{-- ===================== 1. DATOS PERSONALES ===================== --}}
            <section class="aparece bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden" style="--d:120ms">
                <header class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                    <span class="paso" :class="pasoListo(1) && 'paso-listo'"><span x-show="!pasoListo(1)">1</span><i x-show="pasoListo(1)" class="bi bi-check-lg"></i></span>
                    <div>
                        <h3 class="text-sm font-black text-slate-800">Datos personales e identificación</h3>
                        <p class="text-[11px] font-semibold text-slate-400">Información básica del miembro del personal</p>
                    </div>
                </header>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label for="p-nombre" class="etq">Nombre(s) *</label>
                        <input id="p-nombre" type="text" name="nombre" x-model="f.nombre" x-ref="nombre" required maxlength="80" autocomplete="off"
                               @blur="f.nombre = mayus(f.nombre)"
                               class="campo" :class="error('nombre') && 'campo-error'" placeholder="ANDRÉS">
                        @error('nombre')<p class="ayuda text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="p-apellido" class="etq">Apellido(s) *</label>
                        <input id="p-apellido" type="text" name="apellido" x-model="f.apellido" required maxlength="80" autocomplete="off"
                               @blur="f.apellido = mayus(f.apellido)"
                               class="campo" :class="error('apellido') && 'campo-error'" placeholder="MORALES MIRANDA">
                        @error('apellido')<p class="ayuda text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="p-tel" class="etq">Teléfono <span class="opc">(opcional)</span></label>
                        <input id="p-tel" type="tel" name="telefono" x-model="f.telefono" inputmode="numeric" maxlength="10" autocomplete="off"
                               @input="f.telefono = f.telefono.replace(/\D/g, '').slice(0, 10)"
                               class="campo tabular-nums" :class="f.telefono && f.telefono.length !== 10 && tocado.telefono && 'campo-error'"
                               @blur="tocado.telefono = true" placeholder="5512345678">
                        <p class="ayuda" :class="f.telefono && f.telefono.length !== 10 ? 'text-amber-600' : 'text-slate-400'"
                           x-text="f.telefono && f.telefono.length !== 10 ? 'Faltan ' + (10 - f.telefono.length) + ' dígitos (10 en total)' : '10 dígitos, sin espacios ni guiones.'"></p>
                        @error('telefono')<p class="ayuda text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="p-rut" class="etq">Cédula profesional <span class="opc">(opcional)</span></label>
                        <input id="p-rut" type="text" name="rut" x-model="f.rut" maxlength="30" autocomplete="off"
                               @blur="f.rut = f.rut.toUpperCase().replace(/\s/g, '')"
                               class="campo font-mono tracking-wide" placeholder="12345678">
                        <p class="ayuda text-slate-400">Se imprime en las recetas. Obligatoria en la práctica para médicos.</p>
                        @error('rut')<p class="ayuda text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- ESPECIALIDAD MÉDICA --}}
                    <div x-show="esMedico" x-cloak class="md:col-span-2">
                        <label for="p-especialidad" class="etq">Especialidad médica <span class="opc">(opcional)</span></label>
                        <input id="p-especialidad" type="text" name="especialidad" x-model="f.especialidad" maxlength="100" autocomplete="off"
                               @blur="f.especialidad = mayus(f.especialidad)"
                               class="campo" placeholder="MEDICINA GENERAL, PEDIATRÍA...">
                        <p class="ayuda text-slate-400">Se mostrará en la agenda de citas y recetas.</p>
                        @error('especialidad')<p class="ayuda text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            {{-- ===================== 2. LUGAR DE RESIDENCIA / DOMICILIO ===================== --}}
            <section class="aparece bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden" style="--d:160ms">
                <header class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                    <span class="paso" :class="pasoListo(2) && 'paso-listo'"><span x-show="!pasoListo(2)">2</span><i x-show="pasoListo(2)" class="bi bi-check-lg"></i></span>
                    <div>
                        <h3 class="text-sm font-black text-slate-800">Lugar de residencia y domicilio</h3>
                        <p class="text-[11px] font-semibold text-slate-400">Ubicación y dirección del personal</p>
                    </div>
                </header>
                <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div class="md:col-span-2">
                        <label for="p-direccion" class="etq">Calle y número <span class="opc">(opcional)</span></label>
                        <input id="p-direccion" type="text" name="direccion" x-model="f.direccion" maxlength="150" autocomplete="off"
                               @blur="f.direccion = mayus(f.direccion)"
                               class="campo" placeholder="AV. HIDALGO #123 INT. 4B">
                    </div>

                    <div>
                        <label for="p-colonia" class="etq">Colonia / Barrio <span class="opc">(opcional)</span></label>
                        <input id="p-colonia" type="text" name="colonia" x-model="f.colonia" maxlength="100" autocomplete="off"
                               @blur="f.colonia = mayus(f.colonia)"
                               class="campo" placeholder="CENTRO">
                    </div>

                    <div>
                        <label for="p-municipio" class="etq">Municipio / Alcaldía <span class="opc">(opcional)</span></label>
                        <input id="p-municipio" type="text" name="municipio" x-model="f.municipio" maxlength="100" autocomplete="off"
                               @blur="f.municipio = mayus(f.municipio)"
                               class="campo" placeholder="CHALCO">
                    </div>

                    <div>
                        <label for="p-estado" class="etq">Estado / Entidad <span class="opc">(opcional)</span></label>
                        <input id="p-estado" type="text" name="estado" x-model="f.estado" maxlength="100" autocomplete="off"
                               @blur="f.estado = mayus(f.estado)"
                               class="campo" placeholder="ESTADO DE MÉXICO">
                    </div>

                    <div>
                        <label for="p-cp" class="etq">Código postal <span class="opc">(opcional)</span></label>
                        <input id="p-cp" type="text" name="codigo_postal" x-model="f.codigo_postal" inputmode="numeric" maxlength="5" autocomplete="off"
                               @input="f.codigo_postal = f.codigo_postal.replace(/\D/g, '').slice(0, 5)"
                               class="campo tabular-nums" placeholder="56600">
                    </div>
                </div>
            </section>

            {{-- ===================== 3. PUESTO, TURNO Y SALARIO ===================== --}}
            <section class="aparece bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden" style="--d:200ms">
                <header class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                    <span class="paso" :class="pasoListo(3) && 'paso-listo'"><span x-show="!pasoListo(3)">3</span><i x-show="pasoListo(3)" class="bi bi-check-lg"></i></span>
                    <div>
                        <h3 class="text-sm font-black text-slate-800">Puesto, turno y estatus</h3>
                        <p class="text-[11px] font-semibold text-slate-400">El rol define qué puede ver y hacer en el sistema</p>
                    </div>
                </header>
                <div class="p-6 space-y-5">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="p-rol" class="etq !mb-0">Cargo / rol *</label>
                            @if ($puedeCrearRoles)
                                <button type="button" @click="abrirRoles()" class="text-[11px] font-black text-teal-700 hover:text-teal-900 flex items-center gap-1 cursor-pointer">
                                    <i class="bi bi-plus-circle"></i> Nuevo rol
                                </button>
                            @endif
                        </div>
                        <input type="hidden" name="rol_id" :value="f.rol_id">
                        <template x-if="roles.length && roles.length <= 8">
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                <template x-for="r in roles" :key="r.id">
                                    <button type="button" @click="f.rol_id = r.id"
                                            class="relative rounded-2xl border-2 px-3 py-3 text-left transition-all cursor-pointer hover:-translate-y-0.5"
                                            :class="f.rol_id === r.id ? 'border-teal-500 bg-teal-50 shadow-md shadow-teal-600/10' : 'border-slate-200 bg-white hover:border-teal-300'">
                                        <i class="bi text-lg" :class="[iconoRol(r.nombre), f.rol_id === r.id ? 'text-teal-600' : 'text-slate-400']"></i>
                                        <p class="text-[11px] font-black mt-1 truncate" :class="f.rol_id === r.id ? 'text-teal-800' : 'text-slate-600'" x-text="r.nombre"></p>
                                        <i x-show="f.rol_id === r.id" class="bi bi-check-circle-fill absolute top-2 right-2 text-teal-600 text-sm pop"></i>
                                    </button>
                                </template>
                            </div>
                        </template>
                        <template x-if="roles.length > 8">
                            <select id="p-rol" x-model="f.rol_id" class="campo cursor-pointer" :class="error('rol_id') && 'campo-error'">
                                <option value="">Selecciona un rol…</option>
                                <template x-for="r in roles" :key="r.id"><option :value="r.id" x-text="r.nombre"></option></template>
                            </select>
                        </template>
                        <p x-show="!roles.length" class="text-xs font-bold text-amber-700 bg-amber-50 border border-amber-200 rounded-2xl px-3.5 py-3">
                            <i class="bi bi-exclamation-triangle-fill"></i> No hay roles registrados. Crea uno antes de dar de alta al personal.
                        </p>
                        <p x-show="error('rol_id')" class="ayuda text-rose-600">Elige el rol del nuevo miembro.</p>
                        @error('rol_id')<p class="ayuda text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="etq">Turno *</label>
                        <input type="hidden" name="turno" :value="f.turno">
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <template x-for="t in turnos" :key="t.valor">
                                <button type="button" @click="f.turno = t.valor"
                                        class="rounded-2xl border-2 px-3 py-3 text-center transition-all cursor-pointer hover:-translate-y-0.5"
                                        :class="f.turno === t.valor ? t.activo : 'border-slate-200 bg-white hover:border-teal-300'">
                                    <i class="bi text-xl" :class="[t.icono, f.turno === t.valor ? '' : 'text-slate-400']"></i>
                                    <p class="text-[11px] font-black mt-0.5" :class="f.turno === t.valor ? '' : 'text-slate-600'" x-text="t.valor"></p>
                                    <p class="text-[9px] font-bold opacity-70" x-text="t.horario"></p>
                                </button>
                            </template>
                        </div>
                        @error('turno')<p class="ayuda text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Nómina y estatus del empleado (DISTRIBUCIÓN ANCHA PARA NÓMINA 2/3 Y ESTATUS 1/3) --}}
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 pt-2 items-start">
                        <div class="lg:col-span-2">
                            @include('personal._campos-nomina')
                        </div>

                        <div class="lg:col-span-1">
                            <label for="p-estatus" class="etq">Estatus de empleado *</label>
                            <select id="p-estatus" name="estatus" x-model="f.estatus" class="campo cursor-pointer">
                                <option value="Activo">ACTIVO</option>
                                <option value="Inactivo">INACTIVO / PERMISO</option>
                            </select>
                            <p class="ayuda text-slate-400">Define si el usuario aparece disponible en listas activas.</p>
                            @error('estatus')<p class="ayuda text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            </section>

            {{-- ===================== 4. ACCESO AL SISTEMA ===================== --}}
            <section class="aparece bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden" style="--d:280ms">
                <header class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex flex-wrap items-center gap-3">
                    <span class="paso" :class="pasoListo(4) && 'paso-listo'"><span x-show="!pasoListo(4)">4</span><i x-show="pasoListo(4)" class="bi bi-check-lg"></i></span>
                    <div class="mr-auto">
                        <h3 class="text-sm font-black text-slate-800">Acceso al sistema</h3>
                        <p class="text-[11px] font-semibold text-slate-400">Usuario y contraseña para iniciar sesión</p>
                    </div>
                    <input type="hidden" name="requiere_acceso" :value="requiereAcceso ? 1 : 0">
                    <button type="button" @click="requiereAcceso = !requiereAcceso" role="switch" :aria-checked="requiereAcceso"
                            class="flex items-center gap-3 bg-white border border-slate-200 px-3.5 py-2 rounded-2xl cursor-pointer hover:border-teal-300 transition-all">
                        <span class="text-xs font-black" :class="requiereAcceso ? 'text-teal-700' : 'text-slate-500'" x-text="requiereAcceso ? 'Tendrá cuenta' : 'Sin cuenta'"></span>
                        <span class="relative w-11 h-6 rounded-full transition-colors duration-300" :class="requiereAcceso ? 'bg-teal-600' : 'bg-slate-300'">
                            <span class="absolute top-1 left-1 w-4 h-4 bg-white rounded-full shadow transition-transform duration-300" :class="requiereAcceso && 'translate-x-5'"></span>
                        </span>
                    </button>
                </header>

                <div class="p-6">
                    <div x-show="requiereAcceso" x-transition.opacity.duration.250ms class="space-y-5">
                        {{-- Usuario --}}
                        <div>
                            <label for="p-usuario" class="etq">Usuario de acceso *</label>
                            <div class="flex rounded-2xl border-2 overflow-hidden transition-all focus-within:border-teal-500 focus-within:ring-4 focus-within:ring-teal-500/10"
                                 :class="error('username') || (f.username && !usuarioValido) ? 'border-rose-300 bg-rose-50' : 'border-slate-200 bg-slate-50 focus-within:bg-white'">
                                <input id="p-usuario" type="text" name="username" x-model="f.username" :disabled="!requiereAcceso" maxlength="40" autocomplete="off" style="text-transform:none"
                                       @input="f.username = f.username.toLowerCase().replace(/[^a-z0-9._-]/g, ''); usuarioManual = true"
                                       class="flex-1 min-w-0 py-3 px-3.5 bg-transparent text-sm font-bold text-slate-800 outline-none" placeholder="amorales">
                                <span class="inline-flex items-center px-3.5 bg-slate-100 text-slate-500 font-bold text-xs border-l border-slate-200 shrink-0">{{ '@' . $dominio }}</span>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 mt-1.5">
                                <p class="ayuda !mt-0" :class="f.username && !usuarioValido ? 'text-rose-600' : 'text-slate-400'"
                                   x-text="f.username && !usuarioValido ? 'Solo letras minúsculas, números, punto, guion o guion bajo (mínimo 3).' : (f.username ? 'Iniciará sesión con ' + correo : 'Se sugiere a partir del nombre.')"></p>
                                <template x-for="s in sugerenciasUsuario" :key="s">
                                    <button type="button" @click="f.username = s; usuarioManual = true"
                                            class="px-2 py-0.5 rounded-full border text-[10px] font-bold transition-all cursor-pointer"
                                            :class="f.username === s ? 'bg-teal-600 text-white border-teal-600' : 'bg-white text-slate-500 border-slate-200 hover:border-teal-300 hover:text-teal-700'"
                                            x-text="s"></button>
                                </template>
                            </div>
                            @error('username')<p class="ayuda text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        {{-- Contraseñas --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label for="p-pass" class="etq !mb-0">Contraseña (8 caracteres) *</label>
                                    <button type="button" @click="generarPassword()" class="text-[11px] font-black text-teal-700 hover:text-teal-900 flex items-center gap-1 cursor-pointer">
                                        <i class="bi bi-magic"></i> Generar
                                    </button>
                                </div>
                                <div class="relative">
                                    <input id="p-pass" :type="verPass ? 'text' : 'password'" name="password" x-model="f.password" :disabled="!requiereAcceso"
                                           maxlength="8" autocomplete="new-password" style="text-transform:none"
                                           class="campo campo-con-botones font-mono tracking-wider" :class="error('password') && 'campo-error'" placeholder="••••••••">
                                    <button type="button" @click="copiarPassword()" x-show="f.password" title="Copiar contraseña"
                                            class="absolute right-10 top-1/2 -translate-y-1/2 w-8 h-8 rounded-lg text-slate-400 hover:text-teal-700 hover:bg-teal-50 flex items-center justify-center cursor-pointer">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                    <button type="button" @click="verPass = !verPass" :title="verPass ? 'Ocultar' : 'Mostrar'"
                                            class="absolute right-1.5 top-1/2 -translate-y-1/2 w-8 h-8 rounded-lg text-slate-400 hover:text-teal-700 hover:bg-teal-50 flex items-center justify-center cursor-pointer">
                                        <i class="bi" :class="verPass ? 'bi-eye-slash' : 'bi-eye'"></i>
                                    </button>
                                </div>
                                <div class="flex gap-1 mt-2">
                                    <template x-for="n in 4" :key="n">
                                        <span class="h-1.5 flex-1 rounded-full transition-colors duration-300" :class="n <= fuerza.nivel ? fuerza.color : 'bg-slate-200'"></span>
                                    </template>
                                </div>
                                <p class="ayuda" :class="f.password.length && f.password.length !== 8 ? 'text-amber-600' : 'text-slate-400'"
                                   x-text="f.password.length && f.password.length !== 8 ? 'Lleva ' + f.password.length + ' de 8 caracteres' : (f.password ? fuerza.texto : 'Combina mayúsculas, minúsculas, números y símbolos.')"></p>
                                @error('password')<p class="ayuda text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="p-pass2" class="etq">Confirmar contraseña *</label>
                                <input id="p-pass2" :type="verPass ? 'text' : 'password'" name="password_confirmation" x-model="f.password2" :disabled="!requiereAcceso"
                                       maxlength="8" autocomplete="new-password" style="text-transform:none"
                                       class="campo font-mono tracking-wider"
                                       :class="f.password2 && f.password2 !== f.password ? 'campo-error' : (f.password2 && f.password2 === f.password ? 'campo-ok' : '')" placeholder="••••••••">
                                <p class="ayuda" :class="f.password2 && f.password2 !== f.password ? 'text-rose-600' : (f.password2 ? 'text-emerald-600' : 'text-slate-400')"
                                   x-text="f.password2 ? (f.password2 === f.password ? 'Las contraseñas coinciden' : 'Las contraseñas no coinciden') : 'Escríbela otra vez para confirmar.'"></p>
                            </div>
                        </div>

                        <div x-show="passwordGenerada" x-transition.opacity class="flex items-start gap-2.5 p-3 rounded-2xl bg-sky-50 border border-sky-200 text-sky-800 text-[11px] font-bold">
                            <i class="bi bi-info-circle-fill text-sky-500 mt-0.5"></i>
                            <span>Copia la contraseña y entrégasela al nuevo miembro: después de guardar ya no se podrá ver.</span>
                        </div>
                    </div>

                    <div x-show="!requiereAcceso" x-transition.opacity.duration.250ms class="flex items-start gap-3 p-4 bg-amber-50 border border-amber-200 rounded-2xl text-amber-800 text-xs font-semibold">
                        <i class="bi bi-person-lock text-amber-600 text-xl shrink-0"></i>
                        <span>Se registrará solo como <b>expediente del personal</b>: aparecerá en el directorio y podrá asignársele citas, pero <b>no tendrá usuario ni contraseña</b> para entrar al sistema. Puedes crearle una cuenta después.</span>
                    </div>
                </div>
            </section>
        </div>

        {{-- ===================== VISTA PREVIA ===================== --}}
        <aside class="aparece lg:sticky lg:top-6 space-y-4" style="--d:340ms">
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="h-16 bg-gradient-to-r from-teal-600 to-emerald-500 relative">
                    <span class="absolute top-2 right-3 text-[9px] font-black uppercase tracking-wider text-white/80 flex items-center gap-1"><i class="bi bi-eye"></i> Vista previa</span>
                </div>
                <div class="px-5 pb-5 -mt-8">
                    <span class="relative w-16 h-16 rounded-2xl flex items-center justify-center text-lg font-black ring-4 ring-white shadow-md transition-colors duration-300"
                          :class="colorAvatar(nombreCompleto)">
                        <span x-text="iniciales"></span>
                        <span class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full ring-2 ring-white" :class="f.estatus === 'Activo' ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                    </span>
                    <h4 class="mt-3 font-black text-sm truncate uppercase" :class="nombreCompleto ? 'text-slate-900' : 'text-slate-300'" x-text="nombreCompleto || 'Nombre del personal'"></h4>
                    <p class="text-[11px] font-extrabold truncate" :class="nombreRol ? 'text-teal-700' : 'text-slate-300'" x-text="nombreRol || 'Sin rol asignado'"></p>
                    <p x-show="f.especialidad" class="text-[10px] font-bold text-teal-600 uppercase" x-text="f.especialidad"></p>
                    <p x-show="f.rut" class="text-[10px] font-bold text-slate-400 uppercase" x-text="'Céd. ' + f.rut"></p>

                    <div class="mt-4 space-y-2 text-[11px] font-semibold text-slate-600">
                        <p class="flex items-center gap-2 truncate">
                            <i class="bi text-teal-600" :class="turnoActual.icono"></i>
                            <span x-text="'Turno ' + (f.turno ? f.turno.toLowerCase() : 'completo') + ' · ' + turnoActual.horario"></span>
                        </p>
                        <p class="flex items-center gap-2 truncate">
                            <i class="bi bi-telephone text-slate-400"></i>
                            <span :class="!f.telefono && 'text-slate-300 italic'" x-text="f.telefono ? formatoTel(f.telefono) : 'Sin teléfono'"></span>
                        </p>
                        <p class="flex items-center gap-2 truncate">
                            <i class="bi bi-geo-alt text-slate-400"></i>
                            <span :class="!f.municipio && 'text-slate-300 italic'" x-text="f.municipio ? f.municipio + (f.estado ? ', ' + f.estado : '') : 'Sin ubicación'"></span>
                        </p>
                        <p class="flex items-center gap-2 truncate">
                            <i class="bi" :class="requiereAcceso ? 'bi-shield-check text-emerald-600' : 'bi-shield-x text-slate-400'"></i>
                            <span class="truncate" :class="!requiereAcceso && 'text-slate-400'" x-text="requiereAcceso ? (f.username ? correo : 'Usuario pendiente') : 'Sin acceso al sistema'"></span>
                        </p>
                    </div>
                </div>
            </div>

            {{-- Lista de pendientes --}}
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-5">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-3">Antes de guardar</p>
                <ul class="space-y-2">
                    <template x-for="r in requeridos" :key="r.texto">
                        <li class="flex items-center gap-2 text-[11px] font-bold transition-colors" :class="r.listo ? 'text-emerald-700' : 'text-slate-400'">
                            <i class="bi" :class="r.listo ? 'bi-check-circle-fill text-emerald-500' : 'bi-circle'"></i>
                            <span x-text="r.texto"></span>
                        </li>
                    </template>
                </ul>
            </div>

            <div class="flex flex-col gap-2">
                <button type="submit" :disabled="enviando || !roles.length"
                        class="w-full px-6 py-3.5 bg-teal-600 hover:bg-teal-700 text-white rounded-2xl text-sm font-black shadow-lg shadow-teal-600/25 transition-all hover:-translate-y-0.5 active:scale-95 flex items-center justify-center gap-2 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed disabled:hover:translate-y-0">
                    <span x-show="enviando" class="w-4 h-4 rounded-full border-2 border-white border-t-transparent animate-spin"></span>
                    <i x-show="!enviando" class="bi bi-person-check-fill"></i>
                    <span x-text="enviando ? 'Registrando…' : 'Dar de alta'"></span>
                </button>
                <a href="{{ route('personal.index') }}" @click="salir($event)"
                   class="w-full px-6 py-3 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 rounded-2xl text-xs font-extrabold transition-all text-center">
                    Cancelar
                </a>
            </div>
        </aside>
    </form>

    {{-- Modal para crear un rol --}}
    @include('personal.modalRoles')
</div>

<style>
    .etq  { display: block; font-size: 10px; font-weight: 900; color: #64748b; text-transform: uppercase; letter-spacing: .05em; margin-bottom: .375rem; }
    .opc  { text-transform: none; font-weight: 600; color: #94a3b8; letter-spacing: 0; }
    .campo { width: 100%; background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 1rem; padding: .75rem .9rem; font-size: .8125rem; font-weight: 700; color: #1e293b; outline: none; transition: all .15s; text-transform: uppercase; }
    .campo::placeholder { color: #cbd5e1; font-weight: 600; }
    .campo:focus { background: #fff; border-color: #14b8a6; box-shadow: 0 0 0 4px rgba(20, 184, 166, .12); }
    .campo:disabled { opacity: .5; }
    .campo.campo-con-botones { padding-right: 5rem; }
    .campo-error { border-color: #fb7185 !important; background: #fff1f2; }
    .campo-ok { border-color: #34d399; }
    .ayuda { font-size: 10px; font-weight: 700; margin-top: .35rem; }
    .paso { width: 2rem; height: 2rem; border-radius: .75rem; background: #0f766e; color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 900; flex: none; transition: background .3s; }
    .paso-listo { background: #10b981; }

    @keyframes fadeUp      { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
    @keyframes flotar      { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
    @keyframes latidoLento { 0%, 100% { transform: scale(1); } 15% { transform: scale(1.2); } 30% { transform: scale(1); } 45% { transform: scale(1.12); } }
    @keyframes ecg         { from { stroke-dashoffset: 1000; } to { stroke-dashoffset: 0; } }
    @keyframes pop         { 0% { transform: scale(0); } 70% { transform: scale(1.2); } 100% { transform: scale(1); } }
    @keyframes sacudir     { 0%, 100% { transform: translateX(0); } 20%, 60% { transform: translateX(-6px); } 40%, 80% { transform: translateX(6px); } }

    .aparece      { animation: fadeUp .55s cubic-bezier(.16, 1, .3, 1) backwards; animation-delay: var(--d, 0ms); }
    .flotar       { animation: flotar 5s ease-in-out infinite; }
    .latido-lento { display: inline-block; animation: latidoLento 1.6s ease-in-out infinite; }
    .ecg-linea    { stroke-dasharray: 160 840; animation: ecg 4s linear infinite; }
    .pop          { animation: pop .35s cubic-bezier(.34, 1.56, .64, 1); }
    .sacudir      { animation: fadeUp .55s cubic-bezier(.16, 1, .3, 1) backwards, sacudir .4s ease-in-out .6s; }

    [x-cloak] { display: none !important; }

    @media (prefers-reduced-motion: reduce) {
        .aparece, .flotar, .latido-lento, .ecg-linea, .pop, .sacudir { animation: none !important; }
    }
</style>

<script>
    function altaPersonal() {
        const ascii = s => String(s || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^a-z0-9 ]/g, '').trim();

        return {
            roles: @json($rolesJs),
            dominio: @js($dominio),
            f: {
                nombre:       @js(mb_strtoupper((string) old('nombre', ''))),
                apellido:     @js(mb_strtoupper((string) old('apellido', ''))),
                telefono:     @js((string) old('telefono', '')),
                rut:          @js(mb_strtoupper((string) old('rut', ''))),
                especialidad: @js(mb_strtoupper((string) old('especialidad', ''))),
                direccion:    @js(mb_strtoupper((string) old('direccion', ''))),
                colonia:      @js(mb_strtoupper((string) old('colonia', ''))),
                municipio:    @js(mb_strtoupper((string) old('municipio', ''))),
                estado:       @js(mb_strtoupper((string) old('estado', ''))),
                codigo_postal:@js((string) old('codigo_postal', '')),
                rol_id:       @js((string) old('rol_id', '')),
                turno:        @js((string) old('turno', 'Completo')),
                estatus:      @js((string) old('estatus', 'Activo')),
                username:     @js((string) old('username', '')),
                password:     '',
                password2:    ''
            },
            requiereAcceso: @js((bool) old('requiere_acceso', old('username') ? 1 : 0)),
            openModal: false,
            openCreateModal: false,
            intento: @js($errors->any()),
            enviando: false,
            verPass: false,
            usuarioManual: @js(old('username') !== null),
            passwordGenerada: false,
            tocado: { telefono: false },
            inicial: '',
            turnos: [
                { valor: 'Completo', icono: 'bi-clock-fill',     horario: 'Jornada completa', activo: 'border-teal-500 bg-teal-50 text-teal-700' },
                { valor: 'Mañana',   icono: 'bi-sunrise-fill',   horario: 'Matutino',         activo: 'border-amber-400 bg-amber-50 text-amber-700' },
                { valor: 'Tarde',    icono: 'bi-sunset-fill',    horario: 'Vespertino',       activo: 'border-orange-400 bg-orange-50 text-orange-700' },
                { valor: 'Noche',    icono: 'bi-moon-stars-fill', horario: 'Nocturno',        activo: 'border-indigo-400 bg-indigo-50 text-indigo-700' }
            ],

            init() {
                this.$watch('openModal', v => { if (this.openCreateModal !== v) this.openCreateModal = v; });
                this.$watch('openCreateModal', v => { if (this.openModal !== v) this.openModal = v; });

                ['f.nombre', 'f.apellido'].forEach(k => this.$watch(k, () => {
                    if (!this.usuarioManual) this.f.username = this.sugerenciasUsuario[0] || '';
                }));

                this.inicial = JSON.stringify(this.f);
                if (!this.intento) setTimeout(() => this.$refs.nombre && this.$refs.nombre.focus(), 400);
            },

            // ----- Datos derivados -----
            get nombreCompleto() { return (this.f.nombre.trim() + ' ' + this.f.apellido.trim()).trim(); },
            get iniciales() {
                const n = this.f.nombre.trim()[0] || '', a = this.f.apellido.trim()[0] || '';
                return (n + a).toUpperCase() || '?';
            },
            get nombreRol() { const r = this.roles.find(r => String(r.id) === String(this.f.rol_id)); return r ? r.nombre : ''; },
            get esMedico() { const r = this.nombreRol.toLowerCase(); return r.includes('medico') || r.includes('doctor') || r.includes('dr'); },
            get turnoActual() { return this.turnos.find(t => t.valor === this.f.turno) || this.turnos[0]; },
            get correo() { return this.f.username + '@' + this.dominio; },
            get usuarioValido() { return /^[a-z0-9._-]{3,40}$/.test(this.f.username); },
            get sugerenciasUsuario() {
                const nombres = ascii(this.f.nombre).split(/\s+/).filter(Boolean);
                const apellidos = ascii(this.f.apellido).split(/\s+/).filter(Boolean);
                if (!nombres.length || !apellidos.length) return [];
                const n = nombres[0], a = apellidos[0], a2 = apellidos[1] || '';
                return [...new Set([n[0] + a, n + '.' + a, n[0] + a + (a2 ? a2[0] : ''), n + a[0]])].filter(s => s.length >= 3).slice(0, 3);
            },

            // ----- Contraseña -----
            get fuerza() {
                const p = this.f.password;
                let puntos = 0;
                if (p.length >= 8) puntos++;
                if (/[a-z]/.test(p) && /[A-Z]/.test(p)) puntos++;
                if (/\d/.test(p)) puntos++;
                if (/[^A-Za-z0-9]/.test(p)) puntos++;
                const niveles = [
                    { texto: 'Muy débil', color: 'bg-rose-500' },
                    { texto: 'Débil: agrega mayúsculas, números o símbolos', color: 'bg-rose-400' },
                    { texto: 'Aceptable', color: 'bg-amber-400' },
                    { texto: 'Buena', color: 'bg-teal-500' },
                    { texto: 'Muy buena', color: 'bg-emerald-500' }
                ];
                return { nivel: Math.max(p ? 1 : 0, puntos), ...niveles[puntos] };
            },
            generarPassword() {
                const grupos = ['ABCDEFGHJKLMNPQRSTUVWXYZ', 'abcdefghijkmnpqrstuvwxyz', '23456789', '#$%&*@!?'];
                const azar = s => s[crypto.getRandomValues(new Uint32Array(1))[0] % s.length];
                let chars = grupos.map(azar);
                const todos = grupos.join('');
                while (chars.length < 8) chars.push(azar(todos));
                for (let i = chars.length - 1; i > 0; i--) {
                    const j = crypto.getRandomValues(new Uint32Array(1))[0] % (i + 1);
                    [chars[i], chars[j]] = [chars[j], chars[i]];
                }
                this.f.password = this.f.password2 = chars.join('');
                this.verPass = true;
                this.passwordGenerada = true;
            },
            async copiarPassword() {
                try {
                    await navigator.clipboard.writeText(this.f.password);
                    this.avisar('Contraseña copiada', 'success');
                } catch (e) { this.avisar('No se pudo copiar', 'warning'); }
            },

            // ----- Validación -----
            get requeridos() {
                const lista = [
                    { texto: 'Nombre y apellido', listo: !!(this.f.nombre.trim() && this.f.apellido.trim()), paso: 1 },
                    { texto: 'Cargo / rol', listo: !!this.f.rol_id, paso: 3 },
                    { texto: 'Turno', listo: !!this.f.turno, paso: 3 }
                ];
                if (this.requiereAcceso) {
                    lista.push(
                        { texto: 'Usuario válido', listo: this.usuarioValido, paso: 4 },
                        { texto: 'Contraseña de 8 caracteres', listo: this.f.password.length === 8, paso: 4 },
                        { texto: 'Contraseñas iguales', listo: !!this.f.password && this.f.password === this.f.password2, paso: 4 }
                    );
                }
                return lista;
            },
            get listos() { return this.requeridos.filter(r => r.listo).length; },
            get avance() { return Math.round((this.listos / (this.requeridos.length || 1)) * 100); },
            pasoListo(n) {
                if (n === 2) return true; // Domicilio es opcional
                const del = this.requeridos.filter(r => r.paso === n);
                if (n === 1) return del.every(r => r.listo) && (!this.f.telefono || this.f.telefono.length === 10);
                return del.length ? del.every(r => r.listo) : !this.requiereAcceso;
            },
            error(campo) {
                if (!this.intento) return false;
                const f = this.f;
                return {
                    nombre: !f.nombre.trim(),
                    apellido: !f.apellido.trim(),
                    rol_id: !f.rol_id,
                    username: this.requiereAcceso && !this.usuarioValido,
                    password: this.requiereAcceso && f.password.length !== 8
                }[campo] || false;
            },

            enviar(e) {
                this.intento = true;
                if (this.enviando) { e.preventDefault(); return; }
                const faltan = this.requeridos.filter(r => !r.listo).map(r => r.texto.toLowerCase());
                if (this.f.telefono && this.f.telefono.length !== 10) faltan.push('teléfono de 10 dígitos');
                if (faltan.length) {
                    e.preventDefault();
                    this.avisar('Falta: ' + faltan.join(', ') + '.', 'warning');
                    return;
                }
                this.f.nombre = this.mayus(this.f.nombre);
                this.f.apellido = this.mayus(this.f.apellido);
                this.f.especialidad = this.mayus(this.f.especialidad);
                this.f.rut = this.mayus(this.f.rut).replace(/\s/g, '');
                this.f.direccion = this.mayus(this.f.direccion);
                this.f.colonia = this.mayus(this.f.colonia);
                this.f.municipio = this.mayus(this.f.municipio);
                this.f.estado = this.mayus(this.f.estado);

                ['nombre', 'apellido', 'rut', 'especialidad', 'direccion', 'colonia', 'municipio', 'estado'].forEach(c => {
                    const el = e.target.elements[c];
                    if (el) el.value = this.f[c];
                });
                this.inicial = JSON.stringify(this.f);
                this.enviando = true;
            },

            // ----- Salir sin guardar -----
            get hayCambios() { return JSON.stringify(this.f) !== this.inicial; },
            salir(e) {
                if (!this.hayCambios || !window.Swal) return;
                e.preventDefault();
                const destino = e.currentTarget.href;
                Swal.fire({
                    title: '¿Salir sin guardar?', text: 'Se perderán los datos que capturaste.',
                    icon: 'question', showCancelButton: true, reverseButtons: true,
                    confirmButtonText: 'Salir', cancelButtonText: 'Seguir capturando',
                    confirmButtonColor: '#f43f5e', cancelButtonColor: '#0d9488',
                    customClass: { popup: 'rounded-3xl' }
                }).then(r => { if (r.isConfirmed) { this.inicial = JSON.stringify(this.f); window.location.href = destino; } });
            },

            // ----- Utilidades -----
            abrirRoles() { this.openModal = true; this.openCreateModal = true; },
            mayus(s) { return String(s || '').trim().replace(/\s+/g, ' ').toUpperCase(); },
            formatoTel(t) { return t.length === 10 ? t.slice(0, 2) + ' ' + t.slice(2, 6) + ' ' + t.slice(6) : t; },
            iconoRol(nombre) {
                const n = ascii(nombre);
                if (/medic|doctor|dr/.test(n)) return 'bi-heart-pulse-fill';
                if (/enferm/.test(n)) return 'bi-bandaid-fill';
                if (/recep/.test(n)) return 'bi-person-workspace';
                if (/admin/.test(n)) return 'bi-shield-lock-fill';
                if (/farmac/.test(n)) return 'bi-capsule';
                if (/caj|contab|factur/.test(n)) return 'bi-cash-coin';
                return 'bi-person-badge-fill';
            },
            colorAvatar(n) {
                if (!n) return 'bg-slate-100 text-slate-300';
                const colores = ['bg-teal-100 text-teal-800', 'bg-sky-100 text-sky-800', 'bg-violet-100 text-violet-800', 'bg-amber-100 text-amber-800', 'bg-rose-100 text-rose-800', 'bg-emerald-100 text-emerald-800'];
                let h = 0;
                for (let i = 0; i < n.length; i++) {
                    h = (h * 31 + n.charCodeAt(i)) >>> 0;
                }
                return colores[h % colores.length];
            },
            avisar(mensaje, icono = 'success') {
                if (typeof window.notificar === 'function') return window.notificar(mensaje, icono);
                if (window.Swal) Swal.fire({ toast: true, position: 'top-end', timer: 3000, showConfirmButton: false, icon: icono, title: mensaje });
            }
        };
    }

    window.addEventListener('beforeunload', e => {
        const el = document.querySelector('[x-data="altaPersonal()"]');
        const datos = el && window.Alpine ? Alpine.$data(el) : null;
        if (datos && datos.hayCambios && !datos.enviando) { e.preventDefault(); e.returnValue = ''; }
    });
</script>
@endsection