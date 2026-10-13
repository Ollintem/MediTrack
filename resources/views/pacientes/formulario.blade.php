
<form class="cv-face cv-back" id="cvBack" novalidate aria-labelledby="cvBkName" @if(!$puedeEditar && !$puedeCrear) hidden @endif>
  <div class="cv-glhost" aria-hidden="true"></div>
  <header class="cv-bkhd"><p id="cvBkKicker">Editando credencial</p><h2 id="cvBkName"></h2></header>
  <div class="cv-tabs" role="tablist" aria-label="Secciones del expediente">
    <button type="button" class="cv-tab" role="tab" id="cvTab-id" aria-controls="cvPanel-id" data-tab="id" aria-selected="true"><span class="cv-dot" id="cvDId">1</span>Identidad</button>
    <button type="button" class="cv-tab" role="tab" id="cvTab-ct" aria-controls="cvPanel-ct" data-tab="ct" aria-selected="false"><span class="cv-dot" id="cvDCt">2</span>Contacto</button>
    <button type="button" class="cv-tab" role="tab" id="cvTab-fm" aria-controls="cvPanel-fm" data-tab="fm" aria-selected="false"><span class="cv-dot" id="cvDFm">3</span>Ficha médica</button>
    <button type="button" class="cv-tab" role="tab" id="cvTab-av" aria-controls="cvPanel-av" data-tab="av" aria-selected="false"><span class="cv-dot" id="cvDAv">4</span>Aviso</button>
  </div>

  <div class="cv-panels">
    {{-- Identidad --}}
    <section class="cv-panel on" id="cvPanel-id" data-panel="id" role="tabpanel" aria-labelledby="cvTab-id">
      <div class="cv-g cv-g3">
        <div class="cv-fld" data-k="nombres"><label for="cvf-nombres">Nombre(s) <span class="cv-req" aria-hidden="true">*</span></label><input id="cvf-nombres" class="up" data-k="nombres" maxlength="80" aria-required="true" placeholder="Ej. María Fernanda"><p class="cv-msg"></p></div>
        <div class="cv-fld" data-k="paterno"><label for="cvf-paterno">Apellido paterno <span class="cv-req" aria-hidden="true">*</span></label><input id="cvf-paterno" class="up" data-k="paterno" maxlength="80" aria-required="true" placeholder="Ej. Cortés"><p class="cv-msg"></p></div>
        <div class="cv-fld" data-k="materno"><label for="cvf-materno">Apellido materno</label><input id="cvf-materno" class="up" data-k="materno" maxlength="80" placeholder="Ej. Moreno"><p class="cv-msg"></p></div>
      </div>
      <div class="cv-g cv-g21">
        <div class="cv-fld" data-k="curp"><label for="cvf-curp">CURP</label><input id="cvf-curp" class="up mono" data-k="curp" maxlength="18" spellcheck="false" autocomplete="off" placeholder="18 caracteres" aria-describedby="cvNote"><p class="cv-msg"></p></div>
        <div class="cv-fld" data-k="sangre"><label for="cvf-sangre">Grupo sanguíneo</label><select id="cvf-sangre" data-k="sangre"><option value="">Sin registrar</option>@foreach(['O+','O-','A+','A-','B+','B-','AB+','AB-'] as $gs)<option>{{ $gs }}</option>@endforeach</select></div>
      </div>
      <div class="cv-dec" id="cvDec" aria-hidden="true"></div>
      <p class="cv-note" id="cvNote" aria-live="polite"></p>
      <div class="cv-g cv-g4">
        <div class="cv-fld" data-k="fnac"><label for="cvf-fnac">Fecha de nacimiento</label><input id="cvf-fnac" type="date" data-k="fnac" min="1900-01-01" max="{{ now()->format('Y-m-d') }}"><p class="cv-msg"></p></div>
        <div class="cv-fld" data-k="genero"><label for="cvf-genero">Sexo</label><select id="cvf-genero" data-k="genero"><option value="">Sin registrar</option><option value="Femenino">Mujer</option><option value="Masculino">Hombre</option><option value="Otro">Otro</option></select></div>
        <div class="cv-fld"><label for="cvf-civil">Estado civil</label><select id="cvf-civil" data-k="civil"><option value="">Sin registrar</option>@foreach(['SOLTERO(A)','CASADO(A)','UNIÓN LIBRE','DIVORCIADO(A)','VIUDO(A)'] as $ec)<option>{{ $ec }}</option>@endforeach</select></div>
        <div class="cv-fld"><label for="cvf-nac">Nacionalidad</label><input id="cvf-nac" class="up" data-k="nacionalidad" maxlength="50" placeholder="Ej. Mexicana"></div>
      </div>
    </section>

    {{-- Contacto --}}
    <section class="cv-panel" id="cvPanel-ct" data-panel="ct" role="tabpanel" aria-labelledby="cvTab-ct">
      <div class="cv-g cv-g3">
        <div class="cv-fld" data-k="cel"><label for="cvf-cel">Celular</label><input id="cvf-cel" data-k="cel" type="tel" inputmode="numeric" maxlength="10" placeholder="10 dígitos"><p class="cv-msg"></p></div>
        <div class="cv-fld" data-k="tel"><label for="cvf-tel">Teléfono fijo</label><input id="cvf-tel" data-k="tel" type="tel" inputmode="numeric" maxlength="10" placeholder="10 dígitos"><p class="cv-msg"></p></div>
        <div class="cv-fld" data-k="email"><label for="cvf-email">Correo</label><input id="cvf-email" data-k="email" type="email" maxlength="150" placeholder="nombre@correo.com"><p class="cv-msg"></p></div>
      </div>
      <div class="cv-g" style="margin-top:12px">
        <div class="cv-fld"><label for="cvf-dir">Domicilio</label><input id="cvf-dir" class="up" data-k="direccion" maxlength="255" placeholder="Calle, número, colonia, ciudad"></div>
      </div>
      <p class="cv-sub">Contacto de emergencia</p>
      <div class="cv-g cv-g3">
        <div class="cv-fld"><label for="cvf-en">Nombre</label><input id="cvf-en" class="up" data-k="emNombre" maxlength="150" placeholder="Familiar o tutor"></div>
        <div class="cv-fld"><label for="cvf-ep">Parentesco</label><input id="cvf-ep" class="up" data-k="emRel" maxlength="50" placeholder="Ej. Madre"></div>
        <div class="cv-fld" data-k="emTel"><label for="cvf-et">Teléfono</label><input id="cvf-et" data-k="emTel" type="tel" inputmode="numeric" maxlength="10" placeholder="10 dígitos"><p class="cv-msg"></p></div>
      </div>
    </section>

    {{-- Ficha médica --}}
    <section class="cv-panel" id="cvPanel-fm" data-panel="fm" role="tabpanel" aria-labelledby="cvTab-fm">
      <div class="cv-lists">
        <div class="cv-lst al" data-l="alergias"><h3>Alergias <span class="t" data-n></span></h3><div class="cv-chips"></div><label class="sr" for="cvl-al">Agregar alergia</label><input id="cvl-al" maxlength="120" placeholder="Escribe y pulsa Enter"></div>
        <div class="cv-lst" data-l="condiciones"><h3>Condiciones <span class="t" data-n></span></h3><div class="cv-chips"></div><label class="sr" for="cvl-co">Agregar condición</label><input id="cvl-co" maxlength="120" placeholder="Escribe y pulsa Enter"></div>
        <div class="cv-lst" data-l="meds"><h3>Medicamentos <span class="t" data-n></span></h3><div class="cv-chips"></div><label class="sr" for="cvl-me">Agregar medicamento</label><input id="cvl-me" maxlength="120" placeholder="Escribe y pulsa Enter"></div>
      </div>
    </section>

    {{-- Aviso de privacidad --}}
    <section class="cv-panel" id="cvPanel-av" data-panel="av" role="tabpanel" aria-labelledby="cvTab-av">
      <label class="cv-aviso"><input type="checkbox" data-k="aviso"><span><b>El paciente leyó y acepta el aviso de privacidad</b><p>Autoriza el tratamiento de sus datos personales y clínicos para integrar su expediente conforme a la NOM-004.</p></span></label>
      <div class="cv-avisotx" tabindex="0" aria-label="Texto del aviso de privacidad">
        @if (!empty($avisoTexto))
          <p style="white-space:pre-line">{{ $avisoTexto }}</p>
        @else
          <p>En cumplimiento de la Ley Federal de Protección de Datos Personales en Posesión de los Particulares y de la NOM-004-SSA3-2012, del expediente clínico:</p>
          <h4>Responsable</h4><p><strong>{{ $nombreClinica }}</strong> es responsable de recabar sus datos personales, del uso que se les dé y de su protección.</p>
          <h4>Datos que recabamos</h4><p>Identificación (nombre, CURP, fecha de nacimiento, domicilio), contacto (teléfonos, correo, contacto de emergencia) y datos sensibles (historial médico, alergias, padecimientos y medicamentos).</p>
          <h4>Finalidades</h4><p>Integrar el expediente clínico, diagnosticar, prescribir tratamientos, contactar en caso de emergencia y dar seguimiento a sus citas.</p>
          <h4>Derechos ARCO</h4><p>Puede acceder, rectificar o cancelar sus datos, oponerse a su tratamiento o revocar su consentimiento solicitándolo en la recepción de la clínica.</p>
        @endif
      </div>
    </section>
  </div>

  <footer class="cv-bkft">
    <p id="cvBkMsg" aria-live="polite"></p>
    <button type="button" class="cv-btn ghost" id="cvCancel">Cancelar <kbd>Esc</kbd></button>
    <button type="submit" class="cv-btn primary" id="cvSave"><span id="cvSaveTxt">Guardar cambios</span> <kbd>Ctrl ↵</kbd></button>
  </footer>
</form>
