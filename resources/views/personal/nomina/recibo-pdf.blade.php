@php
    $p = $recibo->periodo;
    $m = fn ($v) => '$' . number_format((float) $v, 2);
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo de nómina #{{ $recibo->id }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1e293b; margin: 0; padding: 28px; }
        .cabecera { border-bottom: 3px solid #0d9488; padding-bottom: 12px; margin-bottom: 16px; }
        .cabecera table { width: 100%; table-layout: fixed; }
        .cabecera td { vertical-align: top; word-wrap: break-word; }
        .marca { font-size: 18px; font-weight: bold; color: #0f766e; }
        .sub { color: #64748b; font-size: 10px; }
        .titulo { text-align: right; font-size: 14px; font-weight: bold; }
        .datos { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .datos td { padding: 6px 8px; border: 1px solid #e2e8f0; }
        .datos .et { background: #f8fafc; color: #64748b; font-weight: bold; width: 22%; font-size: 10px; text-transform: uppercase; }
        .cols { width: 100%; border-collapse: separate; border-spacing: 10px 0; margin: 0 -10px; }
        .cols > tbody > tr > td { vertical-align: top; width: 50%; }
        .tabla { width: 100%; border-collapse: collapse; }
        .tabla th { text-align: left; padding: 7px 8px; font-size: 10px; text-transform: uppercase; color: #fff; }
        .tabla td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; }
        .tabla .num { text-align: right; }
        .tabla tfoot td { font-weight: bold; border-top: 2px solid #cbd5e1; border-bottom: none; }
        .perc th { background: #059669; }
        .ded th  { background: #e11d48; }
        .neto { margin-top: 18px; padding: 14px; background: #0f766e; color: #fff; text-align: right; font-size: 16px; font-weight: bold; border-radius: 6px; }
        .neto span { font-size: 10px; font-weight: normal; text-transform: uppercase; display: block; opacity: .8; }
        .notas { margin-top: 12px; padding: 8px 10px; background: #f8fafc; border: 1px solid #e2e8f0; font-size: 10px; }
        .firmas { width: 100%; margin-top: 60px; }
        .firmas td { width: 50%; text-align: center; padding: 0 30px; }
        .linea { border-top: 1px solid #334155; padding-top: 5px; font-size: 10px; color: #475569; }
        .pie { margin-top: 30px; text-align: center; color: #94a3b8; font-size: 9px; }
        .imprimir { position: fixed; top: 12px; right: 12px; padding: 8px 14px; background: #0d9488; color: #fff; border: 0; border-radius: 8px; font-weight: bold; cursor: pointer; }
        @media print { .imprimir { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    @if (!empty($imprimible))
        <button class="imprimir" onclick="window.print()">Imprimir</button>
    @endif

    <div class="cabecera">
        <table>
            <tr>
                <td>
                    <div class="marca">{{ $datosClinica['nombre'] ?? 'MediTrack' }}</div>
                    @if (!empty($datosClinica['direccion']))<div class="sub">{{ $datosClinica['direccion'] }}</div>@endif
                    <div class="sub">
                        {{ collect([
                            !empty($datosClinica['rfc']) ? 'RFC: ' . $datosClinica['rfc'] : null,
                            !empty($datosClinica['telefono']) ? 'Tel. ' . $datosClinica['telefono'] : null,
                        ])->filter()->implode('  ·  ') }}
                    </div>
                    <div class="sub" style="margin-top:4px;font-weight:bold;color:#0f766e;text-transform:uppercase;letter-spacing:.5px">Recibo de pago de nómina</div>
                </td>
                <td class="titulo">
                    Recibo #{{ str_pad($recibo->id, 6, '0', STR_PAD_LEFT) }}
                    <div class="sub">Emitido el {{ now()->format('d/m/Y') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="datos">
        <tr>
            <td class="et">Empleado</td><td>{{ $empleado ?: 'Empleado #' . $recibo->personal_id }}</td>
            <td class="et">Periodo</td><td>{{ ucfirst(strtolower($p->tipo)) }}{{ $p->tipo === 'ESPECIAL' && $p->notas ? ' · ' . $p->notas : '' }}</td>
        </tr>
        <tr>
            <td class="et">Del</td><td>{{ $p->fecha_inicio->format('d/m/Y') }} al {{ $p->fecha_fin->format('d/m/Y') }}</td>
            <td class="et">Estado</td><td>{!! ($recibo->estado ?? '') === 'Pagado' ? '<b style="color:#059669">PAGADO</b> el ' . optional($recibo->fecha_pago)->format('d/m/Y') . ($recibo->metodo_pago ? ' · ' . ucfirst(strtolower($recibo->metodo_pago)) : '') . ($recibo->referencia ? ' · Ref. ' . e($recibo->referencia) : '') : '<b style="color:#d97706">PENDIENTE</b>' !!}</td>
        </tr>
        <tr>
            <td class="et">Salario diario</td><td>{{ $m($recibo->salario_diario) }}</td>
            <td class="et">Días pagados</td><td>{{ rtrim(rtrim(number_format($recibo->dias_trabajados, 2), '0'), '.') }}</td>
        </tr>
    </table>

    <table class="cols">
        <tr>
            <td>
                <table class="tabla perc">
                    <thead><tr><th>Percepciones</th><th class="num">Importe</th></tr></thead>
                    <tbody>
                        <tr><td>Sueldo</td><td class="num">{{ $m($recibo->sueldo_base) }}</td></tr>
                        <tr><td>Horas extra</td><td class="num">{{ $m($recibo->horas_extra) }}</td></tr>
                        <tr><td>Bonos</td><td class="num">{{ $m($recibo->bonos) }}</td></tr>
                    </tbody>
                    <tfoot><tr><td>Total</td><td class="num">{{ $m($recibo->total_percepciones) }}</td></tr></tfoot>
                </table>
            </td>
            <td>
                <table class="tabla ded">
                    <thead><tr><th>Deducciones</th><th class="num">Importe</th></tr></thead>
                    <tbody>
                        <tr><td>ISR</td><td class="num">{{ $m($recibo->isr) }}</td></tr>
                        <tr><td>IMSS</td><td class="num">{{ $m($recibo->imss) }}</td></tr>
                        <tr><td>Otras</td><td class="num">{{ $m($recibo->otras_deducciones) }}</td></tr>
                    </tbody>
                    <tfoot><tr><td>Total</td><td class="num">{{ $m($recibo->total_deducciones) }}</td></tr></tfoot>
                </table>
            </td>
        </tr>
    </table>

    <div class="neto">
        <span>Neto a pagar</span>
        {{ $m($recibo->neto) }}
    </div>

    @if ($recibo->notas)
        <div class="notas"><b>Notas:</b> {{ $recibo->notas }}</div>
    @endif

    <table class="firmas">
        <tr>
            <td><div class="linea">Firma del empleado</div></td>
            <td><div class="linea">Autorizó</div></td>
        </tr>
    </table>

    <div class="pie">Documento generado por MediTrack. Este recibo no sustituye al CFDI de nómina.</div>
</body>
</html>