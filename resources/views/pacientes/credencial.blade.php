
<section class="cv-stage" aria-label="Credencial del paciente">
  <div class="cv-cifra">
    <h1>Pacientes</h1>
    <p class="cv-n t" id="cvTotal">0<small>expedientes en la clínica</small></p>
    <div class="cv-ind" role="group" aria-label="Filtrar la lista">
      <button type="button" class="g" data-f="hoy" aria-pressed="false"><span>Altas de hoy</span><b class="t" id="cvCHoy">0</b><i></i></button>
      <button type="button" class="w" data-f="inc" aria-pressed="false"><span>Por completar</span><b class="t" id="cvCInc">0</b><i></i></button>
      <button type="button" class="r" data-f="alg" aria-pressed="false"><span>Con alergias</span><b class="t" id="cvCAlg">0</b><i></i></button>
    </div>
  </div>

  <div class="cv-col">
    <div class="cv-cardrow">
      <div class="cv-cardwrap" id="cvCardwrap">
        <div class="cv-tilt" id="cvTilt">
          <div class="cv-inner">
            <article class="cv-face cv-front" id="cvFront" aria-live="polite"></article>

            @include('pacientes.formulario')
          </div>
        </div>
      </div>

      <div class="cv-acts" id="cvActs">
        @if ($puedeEditar)
          <button class="cv-btn primary" id="cvEditar" type="button">Editar credencial <kbd>E</kbd></button>
        @endif
        @if (\Illuminate\Support\Facades\Route::has('citas.index'))
          <a class="cv-btn ghost" href="{{ route('citas.index') }}">Agendar cita</a>
        @endif
        <button class="cv-btn ghost" id="cvImprimir" type="button">Imprimir credencial</button>
        @if ($puedeEliminar)
          <button class="cv-btn danger" id="cvEliminar" type="button">Eliminar expediente</button>
        @endif
      </div>
    </div>

    <div class="cv-deck" id="cvDeck" role="group" aria-label="Altas recientes"></div>
  </div>
</section>
