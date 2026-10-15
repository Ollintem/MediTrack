{{-- Estilos del módulo de Pacientes («Credencial viva»). El CSS va sin directivas Blade. --}}
{{-- Las fuentes (Bricolage Grotesque, Geist, Geist Mono) las carga layouts/admin. --}}
@verbatim
<style>
/*
 * MediTrack · Módulo de Pacientes «Credencial viva»
 * Estilos del módulo. Todo va bajo .cv para no chocar con el resto del sistema.
 * Reglas de diseño: DESIGN.md
 */

.cv{
  --tinta:#0b1e1c;--texto:#2c3d3a;--media:#56625f;--linea:#dbe4e1;--linea2:#edf2f1;--niebla:#eef3f1;--papel:#fff;
  --cred:#0b5a54;--cred2:#073f3b;--foil:#a6efe2;--teal:#0e7c72;
  --rosa:#c8102e;--rosav:#fdecef;--ambar:#9a5800;--ambarv:#fdf3e1;--verde:#08734f;--verdev:#e5f4ee;
  --e:cubic-bezier(.2,.8,.2,1);--flip:cubic-bezier(.3,.7,.1,1);
  --disp:'Bricolage Grotesque',ui-sans-serif,system-ui,sans-serif;--ui:'Geist',ui-sans-serif,system-ui,sans-serif;--mono:'Geist Mono',ui-monospace,monospace;
  container-type:inline-size;font:450 14px/1.45 var(--ui);color:var(--texto);position:relative;
}
.cv *{box-sizing:border-box}
.cv ::selection{background:var(--foil);color:var(--tinta)}
.cv :focus-visible{outline:2px solid var(--teal);outline-offset:2px}
.cv button{cursor:pointer}
.cv .t{font-variant-numeric:tabular-nums}
.cv .sr{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap}
.cv kbd{font:600 11px var(--mono);border:1px solid var(--linea);border-radius:6px;padding:2px 6px;color:var(--media);background:transparent}

.cv-grid{display:grid;grid-template-columns:minmax(0,1fr);gap:16px 24px;align-content:start}
@container (min-width:1240px){.cv-grid{grid-template-columns:minmax(0,1fr) 320px}.cv-side{grid-column:2;grid-row:2/4}}
@container (max-width:1239px){.cv-side{order:5}}

/* Barra de búsqueda */
.cv-top{grid-column:1/-1;display:flex;align-items:center;gap:12px}
.cv-cmd{flex:1;min-width:0;max-width:760px;height:50px;border-radius:14px;background:var(--papel);border:1px solid var(--linea);box-shadow:0 12px 30px -22px rgba(11,30,28,.45);display:flex;align-items:center;gap:12px;padding:0 8px 0 16px}
.cv-cmd svg{width:18px;height:18px;flex:none}
@container (max-width:639px){.cv-cmd>kbd,.cv-dark kbd{display:none}.cv-cmd{padding-left:12px;gap:8px}.cv-cmd input::placeholder{color:transparent}.cv-n{font-size:84px}}
.cv-cmd input{flex:1;min-width:0;border:0;outline:0;background:none;font:450 15px var(--ui);color:var(--tinta)}
.cv-cmd input::placeholder{color:var(--media)}
.cv-dark{height:36px;border-radius:10px;background:var(--tinta);color:#fff;font:600 13.5px var(--ui);display:flex;align-items:center;gap:10px;padding:0 10px 0 14px;white-space:nowrap}
.cv-dark kbd{border-color:rgba(255,255,255,.25);color:#cfe}

/* Escenario */
.cv-stage{position:relative;display:grid;grid-template-columns:minmax(0,1fr);gap:20px;transition:grid-template-columns .4s var(--e),gap .4s var(--e)}
@container (min-width:760px){.cv-stage{grid-template-columns:220px minmax(0,1fr);gap:28px}}
.cv-stage:before{content:"";position:absolute;left:30%;top:-30px;width:70%;height:470px;background:radial-gradient(closest-side,rgba(14,124,114,.14),transparent);pointer-events:none}
.cv-cifra{padding-top:6px;overflow:hidden;transition:opacity .2s ease,transform .3s var(--e)}
.cv-cifra h1{font:600 15px var(--ui);color:var(--media)}
.cv-n{font:800 112px/.82 var(--disp);font-stretch:78%;letter-spacing:-.04em;color:var(--tinta);margin-top:14px;white-space:nowrap}
.cv-n.tick{animation:cvTick .5s var(--e)}
@keyframes cvTick{35%{transform:translateY(-10px);color:var(--teal)}}
.cv-n small{display:block;font:500 16px var(--ui);letter-spacing:0;color:var(--texto);margin-top:12px}
.cv-ind{margin-top:22px;border-top:1px solid var(--linea)}
.cv-ind button{width:100%;display:grid;grid-template-columns:1fr auto;align-items:center;padding:11px 0;border-bottom:1px solid var(--linea);text-align:left;font-weight:500;background:none}
.cv-ind button b{font:750 22px var(--disp);color:var(--tinta)}
.cv-ind button i{grid-column:1/-1;height:4px;border-radius:2px;background:var(--linea);margin-top:7px;position:relative;overflow:hidden}
.cv-ind button i:after{content:"";position:absolute;inset:0;width:var(--p);background:currentColor;border-radius:2px;transition:width .6s var(--e)}
.cv-ind .g i{color:var(--teal)}.cv-ind .w b,.cv-ind .w i{color:var(--ambar)}.cv-ind .r b,.cv-ind .r i{color:var(--rosa)}
.cv-ind button[aria-pressed=true]{background:linear-gradient(90deg,rgba(14,124,114,.08),transparent)}

.cv-col{min-width:0;position:relative}
.cv-cardrow{display:flex;flex-direction:column;gap:16px}
@container (min-width:760px){.cv-cardrow{flex-direction:row;gap:22px;align-items:flex-start}}
.cv-cardwrap{flex:none;width:100%;height:306px;perspective:1800px;position:relative;transition:width .4s var(--e),height .4s var(--e)}
@container (min-width:760px){.cv-cardwrap{width:min(500px,100%)}}
.cv-tilt{position:absolute;inset:0;transform:rotateX(var(--rx,3deg)) rotateY(var(--ry,-8deg)) rotateZ(-1.5deg);transform-style:preserve-3d;transition:transform .5s var(--e)}
.cv-inner{position:absolute;inset:0;transform-style:preserve-3d;transition:transform .45s var(--flip)}
.cv-face{position:absolute;inset:0;border-radius:24px;overflow:hidden;backface-visibility:hidden;-webkit-backface-visibility:hidden}

/* Frente */
.cv-front{background:var(--cred);color:#eafaf6;padding:22px 24px 0;box-shadow:0 1px 0 rgba(255,255,255,.2) inset,0 -1px 0 rgba(0,0,0,.25) inset,0 46px 70px -34px rgba(7,63,59,.85),0 12px 24px -12px rgba(7,63,59,.5);-webkit-print-color-adjust:exact;print-color-adjust:exact}
.cv .gl{position:absolute;inset:0;width:100%;height:100%;pointer-events:none}
.cv-shine{position:absolute;inset:0;pointer-events:none;background:linear-gradient(112deg,transparent calc(var(--sx,40%) - 12%),rgba(255,255,255,.14) var(--sx,40%),rgba(166,239,226,.08) calc(var(--sx,40%) + 6%),transparent calc(var(--sx,40%) + 14%))}
.cv-seal{position:absolute;right:20px;top:18px;width:74px;height:74px;color:var(--foil);opacity:.9}
.cv-seal svg{position:absolute;inset:0}.cv-seal b{position:absolute;inset:0;display:grid;place-items:center;font:800 15px var(--disp);color:#fff}
.cv-hd{position:relative;font-size:12.5px;color:#bfeee6}.cv-hd b{display:block;color:#fff;font-weight:600;font-size:13px}
.cv-nm{position:relative;margin-top:10px;font:780 31px/.95 var(--disp);font-stretch:78%;color:#fff;letter-spacing:-.005em;max-width:78%}
.cv-nm span{display:block;font-weight:520;color:#cdf5ee}
.cv-cu{position:relative;margin-top:11px;display:flex;gap:7px;font:600 15px var(--mono);letter-spacing:.06em;min-height:26px}
.cv-cu span{padding:3px 0;border-bottom:1px solid rgba(255,255,255,.35)}.cv-cu span.x{color:var(--foil);border-color:var(--foil)}
.cv-cu.no{font:600 13px var(--ui);letter-spacing:0;color:#ffd68c}
.cv-dt{position:relative;margin-top:10px;display:grid;grid-template-columns:1.5fr .7fr .95fr .55fr;gap:10px}
.cv-dt dt{font-size:11.5px;color:#a9e0d6}.cv-dt dd{margin:0;white-space:nowrap;font-weight:600;color:#fff;font-size:14px;overflow:hidden;text-overflow:ellipsis}
.cv-dt .gs dd{font:800 30px/1 var(--disp)}
.cv-sel{position:relative;display:flex;gap:5px;margin-top:9px}
.cv-sel i{font:700 10.5px var(--mono);font-style:normal;padding:4px 7px;border-radius:6px;background:rgba(255,255,255,.14)}
.cv-sel i.no{background:transparent;outline:1px dashed rgba(255,214,140,.85);outline-offset:-1px;color:#ffd68c}
.cv-band{position:absolute;left:0;right:0;bottom:0;height:40px;display:flex;align-items:center;gap:10px;padding:0 24px;font:650 14px var(--ui);color:#fff;background:var(--rosa);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.cv-band.ok{background:rgba(0,0,0,.18);color:#bfeee6;font-weight:550}
.cv-band svg{width:16px;flex:none}
.cv-vacia{position:relative;height:100%;display:flex;flex-direction:column;justify-content:center;gap:10px;padding-bottom:20px}
.cv-vacia b{font:780 30px/1 var(--disp);font-stretch:80%;color:#fff}.cv-vacia p{color:#bfeee6;max-width:32ch}
.cv .flash{animation:cvFlash 1.4s ease}
@keyframes cvFlash{0%,30%{color:var(--foil)}}
.cv-stamp{position:absolute;right:28px;bottom:58px;width:132px;height:132px;display:grid;place-items:center;pointer-events:none;opacity:0;color:#ffe3a3}
.cv-stamp svg{position:absolute;inset:0;opacity:.75}
.cv-stamp b{position:relative;font:800 15px/1.1 var(--disp);text-align:center;letter-spacing:.05em;color:#fff1cc;transform:rotate(-10deg);background:rgba(7,63,59,.82);padding:5px 9px;border-radius:6px;border:1.5px solid #ffe3a3}
.cv-stamp.on{animation:cvStamp 3.2s var(--e) forwards}
@keyframes cvStamp{0%{opacity:0;transform:scale(1.9) rotate(-26deg)}14%{opacity:1;transform:scale(.94) rotate(-8deg)}20%{transform:scale(1) rotate(-8deg)}80%{opacity:1}100%{opacity:0;transform:scale(1) rotate(-8deg)}}

/* Reverso: formulario */
.cv-back{transform:rotateY(180deg);background:#fbfdfc;border:1px solid #cfe1dd;box-shadow:0 46px 70px -34px rgba(7,63,59,.55),0 12px 24px -12px rgba(7,63,59,.25);display:grid;grid-template-rows:auto auto minmax(0,1fr) auto}
.cv-glhost{position:absolute;inset:0;pointer-events:none}
.cv-bkhd{position:relative;padding:18px 22px 10px}
.cv-bkhd p{font-size:12.5px;color:var(--media)}
.cv-bkhd h2{font:780 24px/1.05 var(--disp);font-stretch:80%;color:var(--tinta);margin-top:2px;min-height:25px}
.cv-bkhd h2:empty:before{content:"Paciente nuevo";color:#8f9e9b}
.cv-tabs{position:relative;display:flex;gap:4px;padding:0 18px;border-bottom:1px solid var(--linea);overflow-x:auto}
.cv-tab{position:relative;display:flex;align-items:center;gap:8px;padding:10px 10px 11px;font:600 13.5px var(--ui);color:var(--media);border:0;border-bottom:2px solid transparent;margin-bottom:-1px;background:none;white-space:nowrap}
.cv-tab[aria-selected=true]{color:var(--tinta);border-color:var(--teal)}
.cv-dot{width:18px;height:18px;border-radius:50%;display:grid;place-items:center;font:700 10px var(--ui);background:var(--linea2);color:var(--media)}
.cv-dot.ok{background:var(--verdev);color:var(--verde)}.cv-dot.no{background:var(--ambarv);color:var(--ambar)}.cv-dot.err{background:var(--rosav);color:var(--rosa)}
.cv-panels{position:relative;overflow:auto;padding:16px 22px}
.cv-panel{display:none}.cv-panel.on{display:block;animation:cvPin .2s var(--e)}
@keyframes cvPin{from{opacity:0;transform:translateY(6px)}}
.cv-g{display:grid;grid-template-columns:minmax(0,1fr);gap:12px}
@container (min-width:640px){.cv-g3{grid-template-columns:repeat(3,minmax(0,1fr))}.cv-g4{grid-template-columns:repeat(4,minmax(0,1fr))}.cv-g21{grid-template-columns:minmax(0,2fr) minmax(0,1fr)}}
.cv-g+.cv-g,.cv-g+.cv-dec+.cv-note+.cv-g{margin-top:12px}
.cv-fld label{display:block;font:600 13px var(--ui);color:var(--texto);margin-bottom:5px}
.cv-fld input,.cv-fld select{width:100%;height:40px;border-radius:10px;border:1px solid var(--linea);background:#fff;padding:0 12px;font:550 14px var(--ui);color:var(--tinta);outline:0;transition:border-color .15s,box-shadow .15s}
.cv-fld input.up{text-transform:uppercase}
.cv-fld input::placeholder{color:#7f8e8b;font-weight:450;text-transform:none}
.cv-fld input:focus,.cv-fld select:focus{border-color:var(--teal);box-shadow:0 0 0 3px rgba(14,124,114,.15)}
.cv-fld.err input,.cv-fld.err select{border-color:var(--rosa);background:#fff8f9}
.cv-msg{font-size:12px;font-weight:600;color:var(--rosa);margin-top:4px}
.cv-fld input.mono{font-family:var(--mono);letter-spacing:.06em}
.cv-req{color:var(--rosa)}
.cv-dec{margin-top:12px;display:flex;gap:6px;flex-wrap:wrap}
.cv-dec div{min-width:64px;border-radius:9px;padding:6px 9px;background:var(--linea2);transition:background .25s}
.cv-dec div b{display:block;font:650 14px var(--mono);letter-spacing:.06em;color:var(--tinta);min-height:20px}
.cv-dec div span{font-size:11.5px;color:var(--media)}
.cv-dec div.ok{background:#e3f1ee}.cv-dec div.ok b{color:var(--cred)}
.cv-dec div.bad{background:var(--rosav)}.cv-dec div.bad b{color:var(--rosa)}
.cv-note{margin-top:8px;font-size:12.5px;font-weight:600;color:var(--media);min-height:18px}
.cv-note.ok{color:var(--verde)}.cv-note.warn{color:var(--ambar)}.cv-note.bad{color:var(--rosa)}
.cv-sub{margin:14px 0 6px;font-weight:600;font-size:13px;color:var(--media)}
.cv-lists{display:grid;grid-template-columns:minmax(0,1fr);gap:12px}
@container (min-width:640px){.cv-lists{grid-template-columns:repeat(3,minmax(0,1fr))}}
.cv-lst{border:1px solid var(--linea);border-radius:12px;padding:10px;background:#fff}
.cv-lst h3{font:650 13px var(--ui);color:var(--tinta);display:flex;justify-content:space-between}
.cv-lst input{margin-top:8px;width:100%;height:34px;border-radius:8px;border:1px dashed var(--linea);padding:0 10px;font:500 13px var(--ui);outline:0;text-transform:uppercase}
.cv-lst input::placeholder{text-transform:none;color:#7f8e8b}
.cv-lst input:focus{border-style:solid;border-color:var(--teal)}
.cv-chips{display:flex;flex-wrap:wrap;gap:5px;margin-top:8px}
.cv-chip{display:inline-flex;align-items:center;gap:4px;padding:3px 4px 3px 9px;border-radius:99px;font:600 12px var(--ui);background:var(--linea2);color:var(--tinta)}
.cv-chip button{width:22px;height:22px;border-radius:50%;display:grid;place-items:center;font-size:14px;line-height:1;background:none;border:0}
.cv-chip button:hover{background:rgba(0,0,0,.08)}
.cv-lst.al .cv-chip{background:var(--rosav);color:var(--rosa)}
.cv-aviso{display:flex;gap:14px;align-items:flex-start;border:1px solid var(--linea);border-radius:14px;padding:16px;background:#fff;cursor:pointer}
.cv-aviso input{width:22px;height:22px;accent-color:var(--teal);margin-top:2px;flex:none}
.cv-aviso b{display:block;font-weight:650;color:var(--tinta)}
.cv-aviso span p{font-size:13px;color:var(--media);margin-top:3px}
.cv-avisotx{margin-top:12px;max-height:170px;overflow:auto;border:1px solid var(--linea);border-radius:12px;padding:14px 16px;background:#fff;font-size:13px;line-height:1.6;color:var(--texto)}
.cv-avisotx h4{font:650 12.5px var(--ui);color:var(--cred);margin:10px 0 2px}
.cv-avisotx p{max-width:70ch}
.cv-bkft{position:relative;display:flex;align-items:center;gap:10px;padding:12px 18px;border-top:1px solid var(--linea);background:rgba(238,243,241,.75);flex-wrap:wrap}
.cv-bkft p{margin-right:auto;font-size:13px;font-weight:600;color:var(--media)}
.cv-bkft p.ok{color:var(--verde)}.cv-bkft p.warn{color:var(--ambar)}.cv-bkft p.bad{color:var(--rosa)}
.cv-btn{height:40px;border-radius:11px;padding:0 14px;font:600 14px var(--ui);display:inline-flex;align-items:center;gap:8px;white-space:nowrap;border:1px solid transparent;text-decoration:none}
.cv-btn.ghost{border-color:var(--linea);background:#fff;color:var(--texto)}
.cv-btn.ghost:hover{background:var(--niebla)}
.cv-btn.primary{background:var(--cred);color:#fff}.cv-btn.primary:hover{background:var(--cred2)}
.cv-btn.primary kbd{border-color:rgba(255,255,255,.3);color:#cfe}
.cv-btn.danger{color:var(--rosa);background:#fff;border-color:var(--linea)}.cv-btn.danger:hover{background:var(--rosav);border-color:#f5c2cf}
.cv-btn[disabled]{opacity:.6;cursor:wait}
.cv-spin{width:14px;height:14px;border-radius:50%;border:2px solid rgba(255,255,255,.5);border-top-color:#fff;animation:cvSpin .7s linear infinite}
@keyframes cvSpin{to{transform:rotate(360deg)}}
.cv .shake{animation:cvShake .4s}
@keyframes cvShake{20%{transform:translateX(-6px)}40%{transform:translateX(5px)}60%{transform:translateX(-3px)}80%{transform:translateX(2px)}}

/* Acciones junto a la credencial */
.cv-acts{display:flex;flex-wrap:wrap;gap:8px;transition:opacity .18s ease,transform .3s var(--e)}
@container (min-width:760px){.cv-acts{flex:1;min-width:150px;flex-direction:column;padding-top:26px}.cv-acts .cv-btn{justify-content:space-between;width:100%;max-width:220px}}

/* Cartera de altas */
.cv-deck{display:flex;gap:8px;align-items:flex-end;margin-top:14px;padding-top:10px;min-height:76px;max-height:96px;overflow-x:auto;scrollbar-width:none;transition:opacity .18s ease,transform .3s var(--e),max-height .35s var(--e),margin .35s var(--e)}
.cv-deck>p{font:600 12.5px var(--ui);color:var(--media);margin-right:4px;align-self:center;white-space:nowrap}
.cv-mini{flex:none;position:relative;width:74px;height:56px;border-radius:10px;background:var(--cred);overflow:hidden;color:#fff;text-align:left;padding:7px 9px;border:0;box-shadow:0 10px 18px -12px rgba(7,63,59,.8);transition:transform .25s var(--e)}
.cv-mini:hover{transform:translateY(-3px)}
.cv-mini b{position:relative;display:block;font:780 16px/1 var(--disp)}
.cv-mini span{position:relative;font:500 11px var(--mono);color:#bfeee6}
.cv-mini .al{position:absolute;left:0;right:0;bottom:0;height:5px;background:var(--rosa)}
.cv-mini.inc{background:#5d6d69}
.cv-mini[aria-current=true]{outline:2px solid var(--tinta);outline-offset:3px;transform:translateY(-6px)}
.cv-mini.drop{animation:cvDrop .35s var(--e)}
@keyframes cvDrop{from{transform:translateY(-60px) rotate(-12deg) scale(1.2);opacity:0}}

/* Modo edición */
.cv.editing .cv-stage{grid-template-columns:minmax(0,1fr)}
@container (min-width:760px){.cv.editing .cv-stage{grid-template-columns:0px minmax(0,1fr);gap:0}}
.cv.editing .cv-cifra{opacity:0;transform:translateX(-30px);pointer-events:none}
.cv.editing .cv-cardwrap{width:100%;height:540px}
@container (max-width:639px){.cv.editing .cv-cifra{display:none}.cv.editing .cv-cardwrap{height:700px}}
.cv.editing .cv-tilt{transform:none}
.cv.editing .cv-inner{transform:rotateY(180deg)}
.cv.editing .cv-acts{opacity:0;pointer-events:none;position:absolute;right:0;transform:translateX(30px)}
@container (max-width:759px){.cv.editing .cv-acts{display:none}}
.cv.editing .cv-deck{opacity:0;transform:translateY(12px);pointer-events:none;max-height:0;min-height:0;margin-top:0;padding-top:0;overflow:hidden}
.cv-side,.cv-lista{transition:opacity .4s,filter .4s}
.cv.editing .cv-side,.cv.editing .cv-lista{opacity:.35;filter:saturate(.6);pointer-events:none}
.cv .swap-out{animation:cvOut .12s ease-in forwards}
.cv .swap-in{animation:cvIn .26s var(--e)}
@keyframes cvOut{to{opacity:0;transform:translateY(26px) rotate(-3deg) scale(.95)}}
@keyframes cvIn{from{opacity:0;transform:translateY(-18px) rotate(2deg) scale(.97)}}

/* Columna lateral */
.cv-side{display:grid;grid-template-columns:minmax(0,1fr);gap:14px;align-content:start}
@container (min-width:760px) and (max-width:1239px){.cv-side{grid-template-columns:1fr 1fr}}
.cv-box{background:var(--papel);border:1px solid var(--linea);border-radius:18px;box-shadow:0 18px 40px -34px rgba(11,30,28,.6)}
.cv-cola .h{padding:18px 20px 6px}.cv-cola h3,.cv-atn h3{font:600 13.5px var(--ui);color:var(--media)}
.cv-cola .h p{font:750 22px/1.15 var(--disp);color:var(--tinta);margin-top:4px}
.cv-prog{margin:12px 20px 4px;height:8px;border-radius:4px;background:var(--ambarv);overflow:hidden}
.cv-prog i{display:block;height:100%;width:var(--p,30%);background:var(--ambar);transition:width .6s var(--e)}
.cv-cola small{display:block;padding:0 20px 10px;font-size:12.5px;color:var(--media)}
.cv-cola ul{list-style:none;padding:0;margin:0}
.cv-cola li button{width:100%;display:grid;grid-template-columns:1fr auto;gap:2px 8px;padding:11px 20px;border:0;border-top:1px solid var(--linea2);text-align:left;background:none}
.cv-cola li button:hover{background:var(--niebla)}
.cv-cola li b{font:600 13.5px var(--ui);color:var(--tinta)}.cv-cola li em{font-style:normal;font-size:12px;color:var(--media)}
.cv-cola li span{grid-row:1/3;grid-column:2;align-self:center;white-space:nowrap;font:600 11px var(--mono);color:var(--ambar);background:var(--ambarv);padding:4px 7px;border-radius:6px}
.cv-go{margin:12px 16px 16px;width:calc(100% - 32px);height:44px;border-radius:12px;background:var(--tinta);color:#fff;display:flex;align-items:center;justify-content:space-between;padding:0 16px;font:600 14px var(--ui);border:0}
.cv-go kbd{border-color:rgba(255,255,255,.3);color:#cfe}
.cv-aldia{padding:6px 20px 18px;color:var(--verde);font-weight:600}
.cv-atn{padding:16px 20px}.cv-atn ul{list-style:none;padding:0;margin:8px 0 0}
.cv-atn li{display:flex;justify-content:space-between;padding:7px 0;font-size:13.5px;border-bottom:1px dashed var(--linea2)}
.cv-atn li b{color:var(--rosa);font-weight:650}

/* Lista */
.cv-lista{background:var(--papel);border:1px solid var(--linea);border-radius:18px;box-shadow:0 18px 40px -34px rgba(11,30,28,.6);overflow:hidden;min-width:0}
.cv-lh{display:flex;align-items:center;gap:8px;padding:13px 18px;border-bottom:1px solid var(--linea);flex-wrap:wrap}
.cv-lh h2{font:700 19px var(--disp);color:var(--tinta);margin-right:auto}
.cv-lh h2 small{font:500 13px var(--ui);color:var(--media);margin-left:6px}
.cv-lh button{height:32px;border-radius:99px;padding:0 13px;font:550 13px var(--ui);border:1px solid var(--linea);background:#fff;color:var(--texto)}
.cv-lh button[aria-pressed=true]{background:var(--tinta);color:#fff;border-color:var(--tinta)}
.cv-row{width:100%;display:grid;grid-template-columns:44px minmax(0,1fr) 112px;gap:14px;align-items:center;padding:10px 18px;border:0;border-bottom:1px solid var(--linea2);text-align:left;background:none}
@container (min-width:860px){.cv-row{grid-template-columns:44px minmax(0,1.2fr) minmax(0,1.1fr) minmax(0,.9fr) 132px;gap:16px}}
.cv-row:hover{background:#f6faf9}
.cv-row[aria-current=true]{background:linear-gradient(90deg,rgba(14,124,114,.08),transparent 70%);box-shadow:inset 3px 0 0 var(--teal)}
.cv-row.nuevo{animation:cvRow 1.6s ease}
@keyframes cvRow{0%{background:#d9f3ee}}
.cv-ro{position:relative;width:42px;height:42px;color:#9fd0c8}.cv-ro svg{position:absolute;inset:0}
.cv-ro b{position:absolute;inset:0;display:grid;place-items:center;font:700 12.5px var(--ui);color:var(--cred2)}
.cv-ro.alg{color:#f2a3b1}
.cv-rnm{display:block;font:650 14.5px/1.2 var(--ui);color:var(--tinta)}.cv-rnm span{font-weight:450;color:var(--texto)}
.cv-rmt{display:block;font-size:12.5px;color:var(--media)}
.cv-inact{display:inline-block;margin-left:6px;padding:0 6px;border-radius:5px;background:var(--linea2);font:600 11px var(--ui);color:var(--texto)}
.cv-rcu,.cv-ral{display:none}
@container (min-width:860px){.cv-rcu,.cv-ral{display:block}}
.cv-rcu span{font:600 12.5px var(--mono);letter-spacing:.04em;color:var(--tinta);margin-right:3px}
.cv-rcu .s1,.cv-rcu .s3{color:var(--teal)}.cv-rcu .s2{background:var(--cred);color:#fff;border-radius:4px;padding:0 4px}
.cv-rcu.no{font:600 12.5px var(--ui);color:var(--ambar)}
.cv-ral span{display:inline-block;margin:0 4px 2px 0;font:600 12px var(--ui);color:var(--rosa);background:var(--rosav);padding:3px 8px;border-radius:99px}
.cv-ral em{font-style:normal;color:var(--media)}
.cv-rse{display:flex;gap:4px}.cv-rse i{font:600 11px var(--mono);font-style:normal;padding:4px 7px;border-radius:6px}
.cv-rse i.ok{background:var(--verdev);color:var(--verde)}.cv-rse i.no{color:var(--ambar);outline:1px dashed #e0b56a;outline-offset:-1px}
.cv-empty{padding:28px 18px;text-align:center;color:var(--media)}
.cv-empty b{display:block;color:var(--tinta);font-size:15px;margin-bottom:10px}
.cv-mas{display:flex;justify-content:center;padding:14px}

@media (prefers-reduced-motion:reduce){
  .cv *,.cv *:before,.cv *:after{animation-duration:.01ms!important;transition-duration:.01ms!important}
  .cv-tilt{transform:none!important}
  .cv.editing .cv-inner{transform:none}.cv.editing .cv-front{visibility:hidden}.cv-back{transform:none;visibility:hidden}.cv.editing .cv-back{visibility:visible}
}
@media print{
  body *{visibility:hidden!important}
  .cv-front,.cv-front *{visibility:visible!important}
  .cv-front{position:fixed!important;left:12mm;top:12mm;width:500px;height:306px;transform:none!important;box-shadow:none}
  .cv-shine,.cv-stamp{display:none}
}
</style>
@endverbatim
