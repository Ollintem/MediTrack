{{-- Lógica del módulo de Pacientes («Credencial viva»). El JS va sin directivas Blade. --}}
@verbatim
<script>
/*
 * MediTrack · Módulo de Pacientes «Credencial viva»
 * Credencial, formulario (reverso), cartera, lista y pendientes.
 * Guarda por AJAX contra PacienteController (respuestas JSON).
 */
(() => {
  /* Datos que entrega la vista (index.blade.php, <script id="cv-datos">) */
  const DATOS = JSON.parse(document.getElementById('cv-datos').textContent);
  const URL_PACIENTES = DATOS.url;
  const CSRF = DATOS.csrf;
  const PUEDE = DATOS.puede;
  const P = DATOS.pacientes;

  const ESTADOS={AS:'Aguascalientes',BC:'Baja California',BS:'Baja California Sur',CC:'Campeche',CL:'Coahuila',CM:'Colima',CS:'Chiapas',CH:'Chihuahua',DF:'Ciudad de México',DG:'Durango',GT:'Guanajuato',GR:'Guerrero',HG:'Hidalgo',JC:'Jalisco',MC:'Edo. de México',MN:'Michoacán',MS:'Morelos',NT:'Nayarit',NL:'Nuevo León',OC:'Oaxaca',PL:'Puebla',QT:'Querétaro',QR:'Quintana Roo',SP:'San Luis Potosí',SL:'Sinaloa',SR:'Sonora',TC:'Tabasco',TS:'Tamaulipas',TL:'Tlaxcala',VZ:'Veracruz',YN:'Yucatán',ZS:'Zacatecas',NE:'Extranjero'};
  const MESES=['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
  const root=document.getElementById('cv');
  const $=s=>root.querySelector(s), $$=s=>[...root.querySelectorAll(s)];
  const reduce=matchMedia('(prefers-reduced-motion: reduce)').matches;
  const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const avisar=(m,t='success')=>{ if(window.notificar) window.notificar(m,t); };

  /* ---------- Patrones de seguridad generados por la CURP ---------- */
  function hash(s){let h=2166136261;for(const c of String(s)){h^=c.charCodeAt(0);h=Math.imul(h,16777619)}return h>>>0}
  function guilloche(seed,stroke,op){
    const hv=hash(seed||'MEDITRACK'),w=560,h=330,n=28+hv%12,cx=w*.8,cy=h*.46;let d='';
    for(let k=0;k<14;k++){const A=70+k*7.5,B=9+(k%4)*2.2,ph=k*.22+(hv%100)/50;
      for(let i=0;i<=300;i++){const t=i/300*2*Math.PI,r=A+B*Math.cos(n*t+ph);d+=(i?'L':'M')+(cx+r*Math.cos(t)).toFixed(1)+','+(cy+r*Math.sin(t)*.92).toFixed(1)}d+='Z'}
    for(let k=0;k<7;k++){const y0=26+k*44;for(let x=0;x<=w;x+=6){d+=(x?'L':'M')+x+','+(y0+7*Math.sin(x/31+k*.9+hv%5)+3*Math.sin(x/9+k)).toFixed(1)}}
    return `<svg class="gl" viewBox="0 0 ${w} ${h}" preserveAspectRatio="xMidYMid slice" aria-hidden="true"><path d="${d}" fill="none" stroke="${stroke}" stroke-width=".6" opacity="${op}"/></svg>`;
  }
  function roseta(seed,size){
    const hv=hash(seed||'MT'),n=18+hv%9,c=size/2;let d='';
    for(let k=0;k<9;k++){const A=size*.11+k*size*.042,B=size*.03+(k%3)*size*.012;
      for(let i=0;i<=200;i++){const t=i/200*2*Math.PI,r=A+B*Math.cos(n*t+k*.4);d+=(i?'L':'M')+(c+r*Math.cos(t)).toFixed(1)+','+(c+r*Math.sin(t)).toFixed(1)}d+='Z'}
    return `<svg viewBox="0 0 ${size} ${size}" aria-hidden="true"><path d="${d}" fill="none" stroke="currentColor" stroke-width=".7"/></svg>`;
  }

  /* ---------- CURP ---------- */
  const RX=/^([A-Z]{4})(\d{6})([HM])([A-Z]{2})([A-Z]{3})([A-Z0-9]\d)$/;
  function curpInfo(c){
    const m=RX.exec(String(c||'').toUpperCase());if(!m)return null;
    const yy=+m[2].slice(0,2),mm=+m[2].slice(2,4),dd=+m[2].slice(4,6);
    const yr=(yy>new Date().getFullYear()%100?1900:2000)+yy;const f=new Date(yr,mm-1,dd);
    if(f.getMonth()!==mm-1||!ESTADOS[m[4]])return null;
    return {seg:[m[1],m[2],m[3],m[4],m[5],m[6]],iso:`${yr}-${String(mm).padStart(2,'0')}-${String(dd).padStart(2,'0')}`,genero:m[3]==='H'?'Masculino':'Femenino',estado:ESTADOS[m[4]]};
  }
  function edadDe(iso){if(!iso)return null;const [y,m,d]=iso.split('-').map(Number),h=new Date();let e=h.getFullYear()-y;if(h.getMonth()+1<m||(h.getMonth()+1===m&&h.getDate()<d))e--;return e}
  function fechaCorta(iso){if(!iso)return '';const [y,m,d]=iso.split('-').map(Number);return `${d} ${MESES[m-1]} ${y}`}
  const sexoTxt=g=>({Femenino:'Mujer',Masculino:'Hombre',Otro:'Otro'}[g]||'');
  function sinAcentos(s){return String(s||'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toUpperCase().trim()}
  function inicialesEsperadas(p){
    const pat=sinAcentos(p.paterno),mat=sinAcentos(p.materno);let nom=sinAcentos(p.nombres).split(/\s+/).filter(Boolean);
    if(!pat||!nom.length)return '';
    if(['JOSE','MARIA','MA','MA.','J','J.'].includes(nom[0])&&nom.length>1)nom=nom.slice(1);
    const v=(pat.slice(1).match(/[AEIOU]/)||['X'])[0];
    return (pat[0]+v+(mat?mat[0]:'X')+nom[0][0]).replace(/Ñ/g,'X');
  }

  /* ---------- Estado ---------- */
  const faltan=p=>[!curpInfo(p.curp)&&'CURP',!(p.cel||p.tel)&&'TEL',!p.aviso&&'AVISO'].filter(Boolean);
  const ini=p=>((p.paterno||'?')[0]+(p.nombres||'?')[0]).toUpperCase();
  const nombreDe=p=>[p.paterno,p.materno,p.nombres].filter(Boolean).join(' ');
  const codigoDe=p=>p.codigo||('PAC-'+String(p.id).padStart(5,'0'));
  const URLP=new URLSearchParams(location.search);
  let actual=P.find(p=>String(p.id)===URLP.get('p'))||P[0]||null, borrador=null, filtro='todos', pestana='id', editando=false, guardando=false, limite=30;

  /* ---------- Frente de la credencial ---------- */
  function pintarFrente(p,cambios=[]){
    const front=$('#cvFront');
    if(!p){
      front.innerHTML=`${guilloche('VACIA','#a6efe2',.25)}<div class="cv-vacia"><b>Aún no hay credenciales</b><p>Registra al primer paciente: escribe su CURP y MediTrack llena la fecha de nacimiento, el sexo y el estado.</p></div>`;
      $('#cvActs').style.visibility='hidden';return;
    }
    $('#cvActs').style.visibility='';
    const ci=curpInfo(p.curp),f=faltan(p),fl=k=>cambios.includes(k)?' class="flash"':'';
    const iso=ci?ci.iso:p.fnac,edad=edadDe(iso),sexo=sexoTxt(p.genero)||(ci?sexoTxt(ci.genero):'');
    const alta=p.hoy?`alta hoy, ${p.hora}`:`alta ${p.alta}`;
    front.innerHTML=`${guilloche(p.curp||nombreDe(p),'#a6efe2',.3)}<div class="cv-shine"></div>
      <div class="cv-seal">${roseta(p.curp||p.paterno,74)}<b>MT</b></div>
      <header class="cv-hd"><b>Credencial clínica${p.estado!=='Activo'?' · '+esc(p.estado):''}</b>${esc(codigoDe(p))} · ${esc(alta)}</header>
      <p class="cv-nm"><span${fl('paterno')} style="display:inline;color:#fff;font-weight:780">${esc(p.paterno)} ${esc(p.materno)}</span><span${fl('nombres')}>${esc(p.nombres)}</span></p>
      ${ci?`<p class="cv-cu"${fl('curp')} aria-label="CURP ${esc(p.curp)}">${ci.seg.map((s,i)=>`<span class="${i>=1&&i<=3?'x':''}">${s}</span>`).join('')}</p>`:`<p class="cv-cu no">Sin CURP · captúrala para verificar identidad</p>`}
      <dl class="cv-dt"><div><dt>Nacimiento</dt><dd class="t"${fl('fnac')}>${iso?`${fechaCorta(iso)} · ${edad} años`:'—'}</dd></div><div><dt>Sexo</dt><dd>${esc(sexo)||'—'}</dd></div><div><dt>Origen</dt><dd>${ci?esc(ci.estado):'—'}</dd></div><div class="gs"><dt>Sangre</dt><dd${fl('sangre')}>${esc(p.sangre)||'—'}</dd></div></dl>
      <div class="cv-sel">${['CURP','TEL','AVISO'].map(s=>`<i class="${f.includes(s)?'no':''}">${s} ${f.includes(s)?'falta':'✓'}</i>`).join('')}</div>
      ${p.alergias.length?`<div class="cv-band"${fl('alergias')}><svg viewBox="0 0 24 24" fill="#fff" aria-hidden="true"><path d="M12 2 1 21h22L12 2Zm1 15h-2v-2h2v2Zm0-4h-2V8h2v5Z"/></svg>Alergia: ${esc(p.alergias.join(', ').toLowerCase())}</div>`:`<div class="cv-band ok">Sin alergias registradas</div>`}
      <div class="cv-stamp" id="cvStamp">${roseta('SELLO'+p.id,132)}<b id="cvStampTxt"></b></div>`;
  }

  /* ---------- Cartera, lista y pendientes ---------- */
  function pintarCartera(nuevoId){
    let lista=P.filter(p=>p.hoy),titulo='Altas de hoy';
    if(!lista.length){lista=[...P].sort((a,b)=>b.creado-a.creado).slice(0,8);titulo='Altas recientes'}
    lista=[...lista].sort((a,b)=>a.creado-b.creado);
    $('#cvDeck').innerHTML=lista.length?`<p>${titulo}</p>`+lista.map(p=>`<button type="button" class="cv-mini${faltan(p).length?' inc':''}${p.id===nuevoId?' drop':''}" data-id="${p.id}" aria-current="${p===actual}" aria-label="${esc(nombreDe(p))}, alta ${esc(p.hoy?p.hora:p.alta)}${faltan(p).length?', expediente incompleto':''}${p.alergias.length?', con alergias':''}">${guilloche(p.curp||p.paterno,'#a6efe2',.25)}<b>${esc(ini(p))}</b><span class="t">${esc(p.hoy?p.hora:(p.alta||'').split(' ').slice(0,2).join(' '))}</span>${p.alergias.length?'<i class="al"></i>':''}</button>`).join(''):'';
  }
  function filtrados(){
    const q=sinAcentos($('#cvQ').value);
    return P.filter(p=>(filtro==='todos'||(filtro==='alg'&&p.alergias.length)||(filtro==='inc'&&faltan(p).length)||(filtro==='hoy'&&p.hoy))
      &&(!q||sinAcentos(`${nombreDe(p)} ${p.curp} ${p.cel} ${p.tel} ${p.codigo} ${p.alergias.join(' ')}`).includes(q)));
  }
  function pintarLista(nuevoId){
    const lista=filtrados(),q=$('#cvQ').value.trim();
    $('#cvLc').textContent=lista.length===P.length?`${P.length}`:`${lista.length} de ${P.length}`;
    if(!lista.length){
      const qq=sinAcentos(q),esCurp=RX.test(qq);
      $('#cvRows').innerHTML=`<div class="cv-empty"><b>${q?`Nadie coincide con «${esc(q)}»`:(P.length?'No hay pacientes en este filtro':'Aún no hay pacientes registrados')}</b>${PUEDE.crear&&esCurp?`<button type="button" class="cv-btn primary" data-alta="curp">Registrar con esta CURP</button>`:PUEDE.crear&&q&&!/\d/.test(q)?`<button type="button" class="cv-btn ghost" data-alta="nombre">Registrar «${esc(q.toUpperCase())}» como paciente nuevo</button>`:''}</div>`;
      return;
    }
    const ver=lista.slice(0,limite);
    $('#cvRows').innerHTML=ver.map(p=>{const ci=curpInfo(p.curp),f=faltan(p),iso=ci?ci.iso:p.fnac,edad=edadDe(iso),sexo=sexoTxt(p.genero)||(ci?sexoTxt(ci.genero):'');
      return `<button type="button" class="cv-row${p.id===nuevoId?' nuevo':''}" data-id="${p.id}" aria-current="${p===actual}">
        <span class="cv-ro${p.alergias.length?' alg':''}">${roseta(p.curp||p.paterno,42)}<b>${esc(ini(p))}</b></span>
        <span><span class="cv-rnm">${esc(p.paterno)} ${esc(p.materno)} <span>${esc(p.nombres)}</span>${p.estado!=='Activo'?`<span class="cv-inact">${esc(p.estado)}</span>`:''}</span><span class="cv-rmt t">${edad!==null?edad+' años':'Edad sin registrar'}${sexo?' · '+esc(sexo):''} · ${esc(p.cel||p.tel)||'sin teléfono'}</span></span>
        ${ci?`<span class="cv-rcu">${ci.seg.map((s,i)=>`<span class="s${i}">${s}</span>`).join('')}</span>`:'<span class="cv-rcu no">Falta CURP</span>'}
        <span class="cv-ral">${p.alergias.map(a=>`<span>${esc(a)}</span>`).join('')||'<em>—</em>'}</span>
        <span class="cv-rse" aria-label="Expediente: ${f.length?'falta '+f.join(', ').toLowerCase():'completo'}">${['CURP','TEL','AV'].map((s,i)=>`<i class="${f.includes(['CURP','TEL','AVISO'][i])?'no':'ok'}">${s}</i>`).join('')}</span></button>`}).join('')
      +(lista.length>limite?`<div class="cv-mas"><button type="button" class="cv-btn ghost" data-mas>Mostrar 30 más (${lista.length-limite} restantes)</button></div>`:'');
  }
  function pintarResumen(tick){
    const inc=P.filter(p=>faltan(p).length),hoy=P.filter(p=>p.hoy),alg=P.filter(p=>p.alergias.length),n=P.length||1;
    $('#cvTotal').firstChild.nodeValue=P.length.toLocaleString('es-MX');
    if(tick){const el=$('#cvTotal');el.classList.remove('tick');void el.offsetWidth;el.classList.add('tick')}
    $('#cvCHoy').textContent=hoy.length;$('#cvCInc').textContent=inc.length;$('#cvCAlg').textContent=alg.length;
    const barras=$$('.cv-ind i');[hoy,inc,alg].forEach((l,i)=>barras[i].style.setProperty('--p',l.length/n*100+'%'));
    const hechos=P.length-inc.length;
    $('#cvCola').innerHTML=`<div class="h"><h3>Por completar</h3><p>${inc.length?`${inc.length} expediente${inc.length>1?'s':''} esperan datos`:'Todo al día'}</p></div>
      <div class="cv-prog"><i style="--p:${P.length?hechos/P.length*100:100}%"></i></div><small>${hechos} de ${P.length} expedientes completos</small>
      ${inc.length?`<ul>${[...inc].sort((a,b)=>b.creado-a.creado).slice(0,3).map(p=>`<li><button type="button" data-id="${p.id}"><b>${esc(nombreDe(p))}</b><em>${esc(p.hoy?'Alta hoy, '+p.hora:'Alta '+p.alta)}</em><span>${faltan(p).join(' · ')}</span></button></li>`).join('')}</ul>
      ${PUEDE.editar?'<button type="button" class="cv-go" id="cvSig">Completar el siguiente <kbd>C</kbd></button>':''}`:'<p class="cv-aldia">No hay expedientes pendientes.</p>'}`;
    const cuenta={};P.forEach(p=>p.alergias.forEach(a=>cuenta[a]=(cuenta[a]||0)+1));
    $('#cvAtn').innerHTML=Object.entries(cuenta).sort((a,b)=>b[1]-a[1]).slice(0,6).map(([a,k])=>`<li>${esc(a.charAt(0)+a.slice(1).toLowerCase())}<b class="t">${k}</b></li>`).join('')||'<li>Ninguna registrada</li>';
  }
  function pintarTodo(o={}){pintarCartera(o.nuevo);pintarLista(o.nuevo);pintarResumen(o.tick)}

  /* ---------- Selección con animación ---------- */
  function seleccionar(p){
    if(editando||!p||p===actual)return;
    const t=$('#cvTilt');
    const cambiar=()=>{actual=p;pintarFrente(p);pintarCartera();pintarLista();t.classList.remove('swap-out');t.classList.add('swap-in');setTimeout(()=>t.classList.remove('swap-in'),460)};
    if(reduce)return cambiar();
    t.classList.add('swap-out');setTimeout(cambiar,200);
  }

  /* ---------- Edición (reverso) ---------- */
  const CAMPOS=['nombres','paterno','materno','curp','fnac','genero','civil','nacionalidad','sangre','cel','tel','email','direccion','emNombre','emRel','emTel','aviso'];
  const PESTANA_DE={nombres:'id',paterno:'id',materno:'id',curp:'id',fnac:'id',genero:'id',cel:'ct',tel:'ct',email:'ct',emTel:'ct'};
  const DEL_SERVIDOR={primer_nombre:'nombres',apellido_paterno:'paterno',apellido_materno:'materno',rut:'curp',fecha_nacimiento:'fnac',genero:'genero',celular:'cel',telefono:'tel',email:'email',contacto_emerg_telefono:'emTel'};
  let erroresServidor={};

  function abrirEdicion(p,tab){
    if(editando||(!p.id&&!PUEDE.crear)||(p.id&&!PUEDE.editar))return;
    borrador=JSON.parse(JSON.stringify(p));erroresServidor={};
    $('.cv-glhost').innerHTML=guilloche((p.curp||'X')+'r','#0b5a54',.07);
    CAMPOS.forEach(k=>{const el=$(`#cvBack [data-k="${k}"]:not(.cv-fld)`);if(!el)return;if(el.type==='checkbox')el.checked=!!borrador[k];else el.value=borrador[k]||'';delete el.dataset.toc});
    $('#cvBkKicker').textContent=p.id?`Editando credencial · ${codigoDe(p)}`:'Emitiendo credencial nueva';
    $('#cvSaveTxt').textContent=p.id?'Guardar cambios':'Emitir credencial';
    pintarListas();irA(tab||'id');validarVivo();
    editando=true;root.classList.add('editing');
    setTimeout(()=>{const el=$('.cv-panel.on input:not([type=checkbox])')||$('.cv-panel.on input');el&&el.focus({preventScroll:true})},reduce?0:520);
  }
  function cerrarEdicion(){
    editando=false;root.classList.remove('editing');
    setTimeout(()=>{const b=$('#cvEditar');b&&b.focus({preventScroll:true})},reduce?0:600);
  }
  function irA(t){pestana=t;$$('.cv-tab').forEach(b=>{const on=b.dataset.tab===t;b.setAttribute('aria-selected',on);b.tabIndex=on?0:-1});$$('.cv-panel').forEach(p=>p.classList.toggle('on',p.dataset.panel===t))}
  function pintarListas(){
    $$('.cv-lst').forEach(box=>{const k=box.dataset.l;box.querySelector('[data-n]').textContent=borrador[k].length||'';
      box.querySelector('.cv-chips').innerHTML=borrador[k].map((x,i)=>`<span class="cv-chip">${esc(x)}<button type="button" data-i="${i}" aria-label="Quitar ${esc(x)}">×</button></span>`).join('')});
  }
  function errores(b){
    const e={};
    if(!String(b.nombres||'').trim())e.nombres='Escribe el nombre o nombres.';
    if(!String(b.paterno||'').trim())e.paterno='Escribe el apellido paterno.';
    if(b.curp&&!curpInfo(b.curp))e.curp=b.curp.length<18?`La CURP lleva 18 caracteres; tiene ${b.curp.length}.`:'La CURP no tiene un formato válido.';
    else if(b.curp&&P.some(p=>p.curp===b.curp&&p.id!==b.id))e.curp='Esta CURP ya pertenece a otro paciente.';
    if(b.fnac&&b.fnac>new Date().toISOString().slice(0,10))e.fnac='La fecha no puede ser posterior a hoy.';
    else if(b.fnac&&curpInfo(b.curp)&&curpInfo(b.curp).iso!==b.fnac)e.fnac='No coincide con la fecha de la CURP.';
    ['cel','tel','emTel'].forEach(k=>{if(b[k]&&b[k].length!==10)e[k]=`Debe tener 10 dígitos; tiene ${b[k].length}.`});
    if(b.email&&!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(b.email))e.email='Escribe un correo como nombre@dominio.com.';
    Object.entries(erroresServidor).forEach(([k,m])=>{if(!e[k])e[k]=m});
    return e;
  }
  function validarVivo(mostrarTodo){
    const b=borrador,e=errores(b);
    $$('.cv-fld[data-k]').forEach(f=>{const k=f.dataset.k,inp=f.querySelector('input,select');const ver=mostrarTodo||erroresServidor[k]||(inp&&inp.dataset.toc);const m=ver?e[k]:'';f.classList.toggle('err',!!m);if(inp)inp.setAttribute('aria-invalid',!!m);const msg=f.querySelector('.cv-msg');if(msg)msg.textContent=m||''});
    // CURP descifrada en vivo
    const c=String(b.curp||'').toUpperCase(),ci=curpInfo(c),lab=['Iniciales','Nacimiento','Sexo','Estado','Consonantes','Verificador'],rng=[[0,4],[4,10],[10,11],[11,13],[13,16],[16,18]];
    $('#cvDec').innerHTML=rng.map(([a,z],i)=>{const s=c.slice(a,z);let txt=lab[i];
      if(ci){if(i===1)txt=fechaCorta(ci.iso);if(i===2)txt=sexoTxt(ci.genero);if(i===3)txt=ci.estado}
      return `<div class="${ci?'ok':(c.length===18?'bad':'')}"><b>${esc(s)||'·'.repeat(z-a)}</b><span>${esc(txt)}</span></div>`}).join('');
    const n=$('#cvNote');n.className='cv-note';
    if(!c){n.textContent='Escribe la CURP y MediTrack descifra fecha, sexo y estado de nacimiento.'}
    else if(ci){const espe=inicialesEsperadas(b);if(espe.length===4&&espe!==c.slice(0,4)){n.className='cv-note warn';n.textContent=`Las iniciales deberían ser ${espe} según el nombre. Revisa el nombre o la CURP.`}else{n.className='cv-note ok';n.textContent=`CURP válida · ${edadDe(ci.iso)} años · nacimiento en ${ci.estado}.`}}
    else if(c.length<18){n.textContent=`Faltan ${18-c.length} caracteres.`}
    else{n.className='cv-note bad';n.textContent='Revisa la CURP: el formato o la fecha no son válidos.'}
    // Pestañas y pie
    const st=(ok,err)=>err?'err':ok?'ok':'no';
    const set=(id,s,num)=>{const d=$(id);d.className='cv-dot '+s;d.textContent=s==='ok'?'✓':s==='err'?'!':num};
    set('#cvDId',st(!!ci,e.nombres||e.paterno||e.curp||e.fnac),1);
    set('#cvDCt',st(!!(b.cel||b.tel),e.cel||e.tel||e.email||e.emTel),2);
    const nf=b.alergias.length+b.condiciones.length+b.meds.length;$('#cvDFm').className='cv-dot';$('#cvDFm').textContent=nf||3;
    set('#cvDAv',b.aviso?'ok':'no',4);
    $('#cvBkName').textContent=nombreDe(b).toUpperCase();
    const vis=Object.keys(e).filter(k=>mostrarTodo||erroresServidor[k]||($(`.cv-fld[data-k="${k}"] input, .cv-fld[data-k="${k}"] select`)||{}).dataset?.toc);
    const msg=$('#cvBkMsg'),f=faltan(b);
    if(!String(b.nombres).trim()||!String(b.paterno).trim()){msg.className='';msg.textContent='Para guardar: nombre y apellido paterno.'}
    else if(vis.length){msg.className='bad';msg.textContent=`Corrige ${vis.length} dato${vis.length>1?'s':''} marcado${vis.length>1?'s':''}.`}
    else if(b.curp&&b.curp.length<18){msg.className='';msg.textContent=`Completa la CURP: faltan ${18-b.curp.length} caracteres.`}
    else if(f.length){msg.className='warn';msg.textContent='Puedes guardar · quedará por completar: '+f.join(', ')+'.'}
    else{msg.className='ok';msg.textContent='Listo · expediente completo.'}
    return e;
  }
  function sacudir(){const f=$('#cvBack');f.classList.remove('shake');void f.offsetWidth;f.classList.add('shake')}
  function enfocarError(e){const k=Object.keys(e)[0];if(!k)return;irA(PESTANA_DE[k]||'id');sacudir();setTimeout(()=>{const el=$(`.cv-fld[data-k="${k}"] input, .cv-fld[data-k="${k}"] select`);el&&el.focus()},40)}

  async function guardar(){
    if(guardando)return;
    $$('#cvBack input,#cvBack select').forEach(i=>i.dataset.toc=1);
    ['alergias','condiciones','meds'].forEach(l=>{const inp=$(`.cv-lst[data-l="${l}"] input`);agregar(l,inp)});
    erroresServidor={};
    const e=validarVivo(true);if(Object.keys(e).length)return enfocarError(e);
    const b=borrador,esNuevo=!b.id;
    const cuerpo={primer_nombre:b.nombres,apellido_paterno:b.paterno,apellido_materno:b.materno,rut:b.curp,fecha_nacimiento:b.fnac,genero:b.genero,estado_civil:b.civil,nacionalidad:b.nacionalidad,grupo_sanguineo:b.sangre,celular:b.cel,telefono:b.tel,email:b.email,direccion:b.direccion,contacto_emerg_nombre:b.emNombre,contacto_emerg_relacion:b.emRel,contacto_emerg_telefono:b.emTel,acepta_aviso_privacidad:!!b.aviso,alergias:b.alergias,condiciones:b.condiciones,medicamentos:b.meds};
    guardando=true;const btn=$('#cvSave');btn.disabled=true;$('#cvSaveTxt').innerHTML='<span class="cv-spin" aria-hidden="true"></span> Guardando…';
    try{
      const res=await fetch(esNuevo?URL_PACIENTES:`${URL_PACIENTES}/${b.id}`,{method:esNuevo?'POST':'PUT',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':CSRF,'X-Requested-With':'XMLHttpRequest'},body:JSON.stringify(cuerpo)});
      const data=await res.json().catch(()=>({}));
      if(res.status===422){
        Object.entries(data.errors||{}).forEach(([k,m])=>{erroresServidor[DEL_SERVIDOR[k]||k]=m[0]});
        const ee=validarVivo(true);enfocarError(ee);return;
      }
      if(!res.ok)throw new Error(res.status===419?'Tu sesión expiró. Recarga la página e inténtalo de nuevo.':(data.message||'No se pudo guardar el expediente. Inténtalo de nuevo.'));
      const nuevo=data.paciente;let cambios=[];
      if(esNuevo){P.push(nuevo)}else{const i=P.findIndex(x=>x.id===nuevo.id);cambios=CAMPOS.concat(['alergias']).filter(k=>JSON.stringify(P[i][k])!==JSON.stringify(nuevo[k]));P[i]=nuevo}
      actual=nuevo;pintarFrente(actual,cambios);cerrarEdicion();
      const d=new Date(),hm=`${String(d.getHours()).padStart(2,'0')}:${String(d.getMinutes()).padStart(2,'0')}`;
      setTimeout(()=>{const s=$('#cvStampTxt');if(s){s.innerHTML=(esNuevo?'EMITIDA':'ACTUALIZADA')+'<br>'+hm;$('#cvStamp').classList.add('on')}pintarTodo({nuevo:esNuevo?nuevo.id:null,tick:esNuevo})},reduce?0:720);
      avisar(data.message||(esNuevo?'Paciente registrado.':'Expediente actualizado.'));
      $('#cvQ').value='';
    }catch(err){
      $('#cvBkMsg').className='bad';$('#cvBkMsg').textContent=err.message||'Sin conexión con el servidor. Revisa la red e inténtalo de nuevo.';sacudir();
    }finally{
      guardando=false;btn.disabled=false;$('#cvSaveTxt').textContent=borrador&&borrador.id?'Guardar cambios':'Emitir credencial';
    }
  }
  function nuevo(pref={}){
    if(editando||!PUEDE.crear)return;
    const draft=Object.assign({id:0,codigo:'',paterno:'',materno:'',nombres:'',curp:'',fnac:'',genero:'',civil:'',nacionalidad:'',sangre:'',cel:'',tel:'',email:'',direccion:'',emNombre:'',emRel:'',emTel:'',aviso:false,estado:'Activo',alergias:[],condiciones:[],meds:[],hoy:true,hora:'',alta:'',creado:0},pref);
    const ci=curpInfo(draft.curp);if(ci){draft.fnac=ci.iso;draft.genero=ci.genero}
    const t=$('#cvTilt');
    const ir=()=>{pintarFrente(Object.assign({},draft,{id:0,codigo:'Credencial nueva',paterno:draft.paterno||'PACIENTE',nombres:draft.nombres||'NUEVO',hoy:false,alta:'en proceso'}));t.classList.remove('swap-out');t.classList.add('swap-in');setTimeout(()=>{t.classList.remove('swap-in');abrirEdicion(draft)},reduce?0:380)};
    if(reduce)return ir();t.classList.add('swap-out');setTimeout(ir,200);
  }
  function agregar(l,inp){
    const v=String(inp.value||'').trim().toUpperCase().replace(/\s+/g,' ');
    if(v&&!borrador[l].includes(v))borrador[l].push(v);inp.value='';pintarListas();validarVivo();
  }
  function eliminar(){
    if(!actual||!PUEDE.eliminar||!window.Swal)return;
    const p=actual;
    Swal.fire({
      title:'¿Eliminar este expediente?',
      html:`<p style="font-size:14px">Se eliminará el expediente de <b>${esc(nombreDe(p))}</b> con sus alergias, condiciones y medicamentos. Esta acción no se puede deshacer.</p>`,
      icon:'warning',iconColor:'#c8102e',showCancelButton:true,reverseButtons:true,focusCancel:true,
      confirmButtonText:'Eliminar expediente',cancelButtonText:'Cancelar',confirmButtonColor:'#c8102e',cancelButtonColor:'#56625f',
      showLoaderOnConfirm:true,
      preConfirm:async()=>{try{const r=await fetch(`${URL_PACIENTES}/${p.id}`,{method:'DELETE',headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}});const d=await r.json().catch(()=>({}));
        if(!r.ok||d.success===false)throw new Error(r.status===419?'Tu sesión expiró. Recarga la página.':(d.message||'No se pudo eliminar el expediente.'));return d}catch(e){Swal.showValidationMessage(e.message)}},
      allowOutsideClick:()=>!Swal.isLoading()
    }).then(r=>{if(!r.isConfirmed)return;const i=P.findIndex(x=>x.id===p.id);P.splice(i,1);actual=P[Math.min(i,P.length-1)]||null;pintarFrente(actual);pintarTodo();avisar((r.value&&r.value.message)||'Expediente eliminado.')});
  }

  /* ---------- Eventos ---------- */
  $('#cvEditar')&&($('#cvEditar').onclick=()=>actual&&abrirEdicion(actual));
  $('#cvNuevo')&&($('#cvNuevo').onclick=e=>{e.preventDefault();nuevo()});
  $('#cvEliminar')&&($('#cvEliminar').onclick=eliminar);
  $('#cvImprimir').onclick=()=>window.print();
  $('#cvCancel').onclick=()=>{const eraNuevo=borrador&&!borrador.id;cerrarEdicion();if(eraNuevo)setTimeout(()=>pintarFrente(actual),reduce?0:700)};
  $('#cvBack').addEventListener('submit',e=>{e.preventDefault();guardar()});
  $$('.cv-tab').forEach(b=>{b.onclick=()=>irA(b.dataset.tab);b.onkeydown=e=>{if(!['ArrowRight','ArrowLeft'].includes(e.key))return;const tabs=$$('.cv-tab'),i=tabs.indexOf(b),n=tabs[(i+(e.key==='ArrowRight'?1:-1)+tabs.length)%tabs.length];irA(n.dataset.tab);n.focus()}});
  $('#cvBack').addEventListener('input',e=>{const k=e.target.dataset.k;if(!k||!borrador)return;let v=e.target.type==='checkbox'?e.target.checked:e.target.value;
    if(['cel','tel','emTel'].includes(k)){v=v.replace(/\D/g,'').slice(0,10);e.target.value=v}
    if(k==='curp'){v=v.toUpperCase().replace(/[^A-Z0-9]/g,'');e.target.value=v;if(v.length>=18)e.target.dataset.toc=1;const ci=curpInfo(v);if(ci){if(!borrador.fnac){borrador.fnac=ci.iso;$('#cvf-fnac').value=ci.iso}if(!borrador.genero){borrador.genero=ci.genero;$('#cvf-genero').value=ci.genero}}}
    delete erroresServidor[k];borrador[k]=v;validarVivo()});
  $('#cvBack').addEventListener('change',e=>{if(e.target.tagName==='SELECT'||e.target.type==='date'){const k=e.target.dataset.k;if(k){borrador[k]=e.target.value;e.target.dataset.toc=1;validarVivo()}}});
  $('#cvBack').addEventListener('focusout',e=>{if(e.target.dataset.k&&e.target.value){e.target.dataset.toc=1;validarVivo()}});
  $('#cvBack').addEventListener('keydown',e=>{const inp=e.target.closest('.cv-lst input');if(inp&&e.key==='Enter'){e.preventDefault();agregar(inp.closest('.cv-lst').dataset.l,inp)}else if(e.key==='Enter'&&e.target.tagName==='INPUT'&&e.target.type!=='checkbox'&&!(e.ctrlKey||e.metaKey)){e.preventDefault()}});
  $$('.cv-lst').forEach(box=>box.addEventListener('click',e=>{const b=e.target.closest('[data-i]');if(!b)return;borrador[box.dataset.l].splice(+b.dataset.i,1);pintarListas();validarVivo()}));
  $('#cvDeck').onclick=e=>{const b=e.target.closest('.cv-mini');b&&seleccionar(P.find(p=>p.id==b.dataset.id))};
  $('#cvRows').onclick=e=>{
    const r=e.target.closest('.cv-row');if(r){seleccionar(P.find(p=>p.id==r.dataset.id));if(!editando)$('#cvCardwrap').scrollIntoView({behavior:reduce?'auto':'smooth',block:'nearest'});return}
    if(e.target.closest('[data-mas]')){limite+=30;pintarLista();return}
    const a=e.target.closest('[data-alta]');if(!a)return;const q=$('#cvQ').value.trim().toUpperCase();
    if(a.dataset.alta==='curp')nuevo({curp:sinAcentos(q)});else{const w=q.split(/\s+/);nuevo({paterno:w[0]||'',materno:w[1]||'',nombres:w.slice(2).join(' ')})}
  };
  $('#cvCola').onclick=e=>{
    const b=e.target.closest('[data-id]'),sig=e.target.closest('#cvSig');
    const p=b?P.find(x=>x.id==b.dataset.id):sig?[...P].sort((a,c)=>c.creado-a.creado).find(x=>faltan(x).length):null;if(!p||!PUEDE.editar)return;
    const tab=!curpInfo(p.curp)?'id':!(p.cel||p.tel)?'ct':'av';
    if(p!==actual){seleccionar(p);setTimeout(()=>abrirEdicion(p,tab),reduce?0:520)}else abrirEdicion(p,tab);
  };
  $$('.cv [data-f]').forEach(b=>b.onclick=()=>{filtro=filtro===b.dataset.f&&b.closest('.cv-ind')?'todos':b.dataset.f;limite=30;$$('.cv [data-f]').forEach(x=>x.setAttribute('aria-pressed',x.dataset.f===filtro));pintarLista()});
  $('#cvQ').addEventListener('input',()=>{limite=30;pintarLista()});
  $('#cvQ').addEventListener('keydown',e=>{if(e.key!=='Enter')return;const l=filtrados();if(l.length===1)seleccionar(l[0]);else if(!l.length){const btn=$('[data-alta]');btn&&btn.click()}});
  document.addEventListener('keydown',e=>{
    const enCampo=/INPUT|SELECT|TEXTAREA/.test(e.target.tagName)||e.target.isContentEditable;
    if(editando){if(e.key==='Escape'){e.preventDefault();$('#cvCancel').click()}if(e.key==='Enter'&&(e.ctrlKey||e.metaKey)){e.preventDefault();guardar()}return}
    if(enCampo||e.ctrlKey||e.metaKey||e.altKey||document.querySelector('.swal2-popup:not(.swal2-toast)'))return;
    if(e.key==='/'){e.preventDefault();$('#cvQ').focus()}
    else if(e.key==='n'||e.key==='N'){e.preventDefault();nuevo()}
    else if((e.key==='e'||e.key==='E')&&actual){e.preventDefault();abrirEdicion(actual)}
    else if(e.key==='c'||e.key==='C'){const s=$('#cvSig');s&&s.click()}
  });
  // Inclinación y brillo que siguen al cursor (solo en reposo)
  const cw=$('#cvCardwrap'),tilt=$('#cvTilt');
  if(!reduce&&matchMedia('(pointer:fine)').matches){
    cw.addEventListener('pointermove',e=>{if(editando)return;const r=cw.getBoundingClientRect(),x=(e.clientX-r.left)/r.width,y=(e.clientY-r.top)/r.height;
      tilt.style.setProperty('--ry',(x-.5)*14+'deg');tilt.style.setProperty('--rx',(.5-y)*10+'deg');const s=$('.cv-shine');s&&s.style.setProperty('--sx',x*100+'%')});
    cw.addEventListener('pointerleave',()=>{tilt.style.removeProperty('--ry');tilt.style.removeProperty('--rx')});
  }

  if(!P.length&&!PUEDE.crear)$('#cvActs').style.display='none';
  pintarFrente(actual);pintarTodo();
  // Llegadas desde la búsqueda global (Ctrl+K): ?p=ID abre ese expediente, ?nuevo=1 emite una credencial
  if(URLP.has('p')||URLP.has('nuevo')){
    if(URLP.has('nuevo')&&PUEDE.crear)setTimeout(()=>nuevo(),reduce?0:250);
    history.replaceState(null,'',location.pathname);
  }
})();
</script>
@endverbatim