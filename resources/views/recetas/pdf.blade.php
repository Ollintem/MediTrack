<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Receta médica #{{ str_pad($receta->id, 5, '0', STR_PAD_LEFT) }}</title>
    {{-- PDF de la receta (DomPDF). Colores y jerarquía según DESIGN.md; solo tablas y CSS simple para que DomPDF lo respete. --}}
    <style>
        @page { margin: 0; }
        body { margin: 0; padding: 0 34px 60px; font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; color: #2c3d3a; }
        .banda { height: 8px; background: #0b5a54; margin: 0 -34px 22px; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; }
        .clinica { font-size: 18px; font-weight: bold; color: #0b5a54; }
        .clinica-d { margin-top: 3px; font-size: 9.5px; color: #56625f; line-height: 1.4; }
        .folio { text-align: right; }
        .folio span { display: block; font-size: 9px; color: #56625f; }
        .folio b { font-family: 'Courier', monospace; font-size: 16px; color: #0b1e1c; }
        .folio small { display: block; margin-top: 2px; font-size: 10px; color: #56625f; }
        .linea { border-bottom: 1px solid #dbe4e1; margin: 14px 0 12px; }
        .et { width: 82px; font-size: 9.5px; color: #56625f; padding: 3px 0; }
        .val { font-size: 11.5px; font-weight: bold; color: #0b1e1c; padding: 3px 0; }
        .val-n { font-size: 11px; color: #2c3d3a; padding: 3px 0; text-align: right; }
        .alergia { margin-top: 6px; padding: 5px 10px; background: #fdecef; color: #c8102e; font-weight: bold; font-size: 10.5px; border-radius: 10px; }
        .signos { margin-top: 10px; background: #eef3f1; border-radius: 8px; }
        .signos td { padding: 6px 8px; font-size: 9px; color: #56625f; }
        .signos b { display: block; font-family: 'Courier', monospace; font-size: 11.5px; color: #0b1e1c; }
        .dx { margin-top: 12px; }
        .dx span { font-size: 9.5px; color: #56625f; }
        .dx p { margin: 3px 0 0; font-size: 12px; font-weight: bold; color: #0b1e1c; text-transform: uppercase; }
        .rx { margin-top: 18px; }
        .rx-simbolo { width: 44px; font-family: 'DejaVu Sans', sans-serif; font-size: 32px; font-weight: bold; color: #0b5a54; line-height: 1; }
        .med { padding: 0 0 12px; }
        .med-n { width: 18px; font-family: 'Courier', monospace; font-size: 11px; color: #56625f; padding-top: 1px; }
        .med b { font-size: 12.5px; color: #0b1e1c; text-transform: uppercase; }
        .med p { margin: 2px 0 0; font-size: 11px; color: #2c3d3a; text-transform: uppercase; }
        .ind { margin-top: 8px; padding: 10px 12px; background: #eef3f1; border-radius: 8px; }
        .ind span { display: block; font-size: 9.5px; color: #56625f; margin-bottom: 3px; }
        .ind p { margin: 0; font-size: 11px; text-transform: uppercase; line-height: 1.45; }
        .firma { margin-top: 46px; text-align: center; }
        .firma-img { height: 48px; }
        .firma-img img { max-height: 46px; max-width: 180px; }
        .firma-l { width: 240px; margin: 0 auto 5px; border-top: 1px solid #2c3d3a; }
        .firma b { font-size: 12px; color: #0b1e1c; }
        .firma small { display: block; font-size: 9.5px; color: #56625f; margin-top: 2px; }
        .pie { position: fixed; bottom: 18px; left: 34px; right: 34px; padding-top: 6px; border-top: 1px solid #edf2f1; font-size: 8.5px; color: #7d8986; text-align: center; }
    </style>
</head>
<body>
    @php
        $personalReceta = $receta->personal ?? (auth()->user()->personal ?? null);
        $medico = $personalReceta->nombre_completo ?? (auth()->user()->name ?? 'Médico tratante');
        $cedula = $personalReceta->numero_registro ?? $personalReceta->rut ?? null;
        $pac = $receta->paciente;
        $edad = null;
        if ($pac && !empty($pac->fecha_nacimiento)) {
            try { $edad = \Carbon\Carbon::parse($pac->fecha_nacimiento)->age; } catch (\Throwable $e) {}
        }
        $alergias = $pac ? $pac->alergias->pluck('descripcion')->filter()->implode(', ') : '';
        $sv = $signos ?? $receta->signoVital;
        $fecha = \Carbon\Carbon::parse($receta->fecha_emision ?? $receta->created_at)->locale('es');
        $num = fn ($v) => $v === null ? null : rtrim(rtrim(number_format((float) $v, 1, '.', ''), '0'), '.');
    @endphp

    <div class="banda"></div>

    <table>
        <tr>
            <td>
                <div class="clinica">{{ $clinica->nombre ?? ($config['nombre_clinica'] ?? 'MediTrack') }}</div>
                <div class="clinica-d">
                    {{ $clinica->direccion ?? '' }}
                    @if(!empty($clinica->telefono)) <br>Tel. {{ $clinica->telefono }} @endif
                    @if(!empty($config['email']) || !empty($clinica->email)) · {{ $config['email'] ?? $clinica->email }} @endif
                    @if(!empty($clinica->rut_empresa)) · RFC {{ $clinica->rut_empresa }} @endif
                </div>
            </td>
            <td class="folio">
                <span>Receta médica</span>
                <b>#{{ str_pad($receta->id, 5, '0', STR_PAD_LEFT) }}</b>
                <small>{{ $fecha->translatedFormat('j \d\e F \d\e Y') }}</small>
            </td>
        </tr>
    </table>

    <div class="linea"></div>

    <table>
        <tr>
            <td class="et">Paciente</td>
            <td class="val">{{ $pac->nombre ?? 'Paciente' }}</td>
            <td class="val-n">{{ $edad !== null ? $edad . ' años' : '' }}{{ $pac && $pac->genero ? ' · ' . $pac->genero : '' }}</td>
        </tr>
        <tr>
            <td class="et">Médico</td>
            <td class="val" colspan="2">{{ $medico }}</td>
        </tr>
    </table>
    @if($alergias)
        <div class="alergia">ALERGIAS: {{ mb_strtoupper($alergias, 'UTF-8') }}</div>
    @endif

    @if($sv && ($sv->pa_sistolica || $sv->frecuencia_cardiaca || $sv->temperatura || $sv->peso_kg || $sv->saturacion_oxigeno))
        <table class="signos">
            <tr>
                @if($sv->pa_sistolica)<td>Presión<b>{{ $num($sv->pa_sistolica) }}/{{ $num($sv->pa_diastolica) ?? '—' }}</b></td>@endif
                @if($sv->frecuencia_cardiaca)<td>F. cardiaca<b>{{ $num($sv->frecuencia_cardiaca) }} lpm</b></td>@endif
                @if($sv->frecuencia_respiratoria)<td>F. respiratoria<b>{{ $num($sv->frecuencia_respiratoria) }} rpm</b></td>@endif
                @if($sv->temperatura)<td>Temperatura<b>{{ $num($sv->temperatura) }} °C</b></td>@endif
                @if($sv->saturacion_oxigeno)<td>SpO2<b>{{ $num($sv->saturacion_oxigeno) }} %</b></td>@endif
                @if($sv->peso_kg)<td>Peso<b>{{ $num($sv->peso_kg) }} kg</b></td>@endif
                @if($sv->talla_cm)<td>Talla<b>{{ $num($sv->talla_cm) }} cm</b></td>@endif
                @if($sv->imc)<td>IMC<b>{{ $num($sv->imc) }}</b></td>@endif
            </tr>
        </table>
    @endif

    @if($receta->diagnostico)
        <div class="dx"><span>Diagnóstico</span><p>{{ $receta->diagnostico }}</p></div>
    @endif

    <table class="rx">
        <tr>
            <td class="rx-simbolo">&#8478;</td>
            <td>
                <table>
                    @foreach($receta->detalles as $i => $det)
                        <tr>
                            <td class="med-n">{{ $i + 1 }}.</td>
                            <td class="med">
                                <b>{{ $det->medicamento }}</b>
                                <p>{{ collect([$det->dosis, $det->frecuencia, $det->duracion])->filter()->implode(' · ') ?: 'SIN INDICACIÓN DE DOSIS' }}</p>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </td>
        </tr>
    </table>

    @if($receta->indicaciones_generales)
        <div class="ind"><span>Indicaciones generales</span><p>{{ $receta->indicaciones_generales }}</p></div>
    @endif

    <div class="firma">
        <div class="firma-img">
            @if(!empty($personalReceta->firma))
                <img src="{{ public_path('storage/' . $personalReceta->firma) }}" alt="Firma">
            @endif
        </div>
        <div class="firma-l"></div>
        <b>{{ $medico }}</b>
        <small>{{ $cedula ? 'Cédula profesional ' . $cedula : 'Cédula profesional en trámite' }}</small>
        <small>Firma del médico tratante</small>
    </div>

    <div class="pie">Receta generada con {{ $clinica->nombre ?? 'MediTrack' }} · Folio #{{ str_pad($receta->id, 5, '0', STR_PAD_LEFT) }}</div>
</body>
</html>