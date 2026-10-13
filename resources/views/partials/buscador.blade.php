{{-- Búsqueda que también registra (si el paciente no existe, se da de alta desde aquí). --}}
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