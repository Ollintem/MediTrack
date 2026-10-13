@extends('layouts.admin')

{{--
    MÓDULO DE PACIENTES · «Credencial viva»
    Cada expediente es una credencial clínica. Al editar, la credencial se voltea
    y el reverso es el formulario; al guardar regresa al frente con un sello.
    Guarda por AJAX (PacienteController responde JSON) para no cortar la animación.
--}}

@section('content')
@php
    $usuario       = auth()->user();
    $puedeCrear    = $usuario->tienePermiso('Pacientes', 'crear');
    $puedeEditar   = $usuario->tienePermiso('Pacientes', 'editar');
    $puedeEliminar = $usuario->tienePermiso('Pacientes', 'eliminar');
    $pacientesJs   = $pacientesJs ?? collect();
    $avisoTexto    = $config['aviso_privacidad'] ?? null;
    $nombreClinica = $clinica->nombre ?? ($config['nombre_clinica'] ?? 'la clínica');
    $datosJs       = [
        'url'       => url('pacientes'),
        'csrf'      => csrf_token(),
        'puede'     => ['crear' => $puedeCrear, 'editar' => $puedeEditar, 'eliminar' => $puedeEliminar],
        'pacientes' => $pacientesJs,
    ];
@endphp

@include('pacientes.estilos')

<div class="cv" id="cv">
  <div class="cv-grid">
    {{-- Búsqueda que también registra --}}
    <header class="cv-top">
      <label class="cv-cmd">
        <svg viewBox="0 0 24 24" fill="none" stroke="#56625f" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        <span class="sr">Buscar paciente</span>
        <input id="cvQ" type="search" autocomplete="off" placeholder="Busca a un paciente o pega su CURP para registrarlo">
        <kbd aria-hidden="true">/</kbd>
        @if ($puedeCrear)
          <button class="cv-dark" id="cvNuevo" type="button">Nuevo paciente <kbd>N</kbd></button>
        @endif
      </label>
    </header>

    {{-- Cifra, credencial (frente y reverso), acciones y cartera de altas --}}
    @include('pacientes.credencial')

    {{-- Pendientes: por completar y alergias registradas --}}
    <aside class="cv-side" aria-label="Pendientes">
      <div class="cv-box cv-cola" id="cvCola"></div>
      <div class="cv-box cv-atn"><h3>Alergias registradas</h3><ul id="cvAtn"></ul></div>
    </aside>

    {{-- Lista de expedientes --}}
    <section class="cv-lista" aria-labelledby="cvLt">
      <div class="cv-lh">
        <h2 id="cvLt">Expedientes <small id="cvLc" class="t" aria-live="polite"></small></h2>
        <button type="button" data-f="todos" aria-pressed="true">Todos</button>
        <button type="button" data-f="alg" aria-pressed="false">Con alergias</button>
        <button type="button" data-f="inc" aria-pressed="false">Por completar</button>
        <button type="button" data-f="hoy" aria-pressed="false">Hoy</button>
      </div>
      <div id="cvRows"></div>
    </section>
  </div>
</div>

{{-- Datos para el JavaScript del módulo (escapados para ir dentro de <script>) --}}
<script type="application/json" id="cv-datos">{!! json_encode($datosJs, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) !!}</script>
@include('pacientes.scripts')
@endsection
