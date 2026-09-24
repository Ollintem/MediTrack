<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Receta Médica #{{ str_pad($receta->id, 5, '0', STR_PAD_LEFT) }}</title>
    <style>
        @page {
            margin: 25px;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #1e293b;
            margin: 0;
            padding: 10px;
            font-size: 11px;
        }
        .header {
            border-bottom: 2px solid #0d9488;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }
        .header table {
            width: 100%;
        }
        .title {
            font-size: 20px;
            font-weight: bold;
            color: #0d9488;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .subtitle {
            font-size: 9px;
            color: #64748b;
            font-weight: normal;
            margin-top: 2px;
        }
        .info-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 10px 12px;
            border-radius: 8px;
            margin-bottom: 12px;
        }
        .info-box table {
            width: 100%;
        }
        .info-box td {
            padding: 3px 0;
        }
        .vitals-box {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 8px 10px;
            margin-bottom: 15px;
            font-size: 10px;
        }
        .vitals-table {
            width: 100%;
            border-collapse: collapse;
        }
        .vitals-table td {
            padding: 3px 6px;
        }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #0d9488;
            text-transform: uppercase;
            border-bottom: 1px solid #0d9488;
            padding-bottom: 3px;
            margin-bottom: 8px;
            margin-top: 15px;
        }
        .medicamentos-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .medicamentos-table th {
            background-color: #0d9488;
            color: white;
            text-align: left;
            padding: 6px 8px;
            font-size: 10px;
            text-transform: uppercase;
        }
        .medicamentos-table td {
            border-bottom: 1px solid #e2e8f0;
            padding: 8px;
        }
        .indicaciones-box {
            background-color: #f8fafc;
            border-left: 3px solid #0d9488;
            padding: 8px 12px;
            margin-bottom: 20px;
            font-style: italic;
            color: #334155;
        }
        .firma-container {
            margin-top: 35px;
            width: 100%;
            text-align: center;
        }
        .firma-box {
            display: inline-block;
            width: 280px;
            text-align: center;
        }
        .firma-espacio {
            height: 50px;
            margin-bottom: 5px;
        }
        .firma-espacio img {
            max-height: 48px;
            max-width: 180px;
        }
        .firma-linea {
            border-top: 1.5px solid #475569;
            width: 100%;
            margin-bottom: 5px;
        }
        .medico-nombre {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
        }
        .medico-cedula {
            font-size: 10px;
            color: #64748b;
        }
        .footer-note {
            position: fixed;
            bottom: 10px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #94a3b8;
            border-top: 1px solid #f1f5f9;
            padding-top: 5px;
        }
    </style>
</head>
<body>

    @php
        $personalReceta = $receta->personal ?? auth()->user()->personal ?? null;
        $medicoImpresion = $personalReceta->nombre_completo ?? auth()->user()->nombre_completo;
        
        $cedulaImpresion = $personalReceta->rut 
            ?? $personalReceta->numero_registro 
            ?? $personalReceta->cedula 
            ?? auth()->user()->personal->rut 
            ?? auth()->user()->personal->numero_registro 
            ?? null;

        $pac = $receta->paciente;
        $edadPaciente = 'N/A';

        if ($pac) {
            if (!empty($pac->edad)) {
                $edadPaciente = $pac->edad . ' años';
            } elseif (!empty($pac->fecha_nacimiento)) {
                $edadPaciente = \Carbon\Carbon::parse($pac->fecha_nacimiento)->age . ' años';
            }
        }

        $sv = $receta->signoVital;
    @endphp

    <!-- Encabezado con datos dinámicos de la Clínica -->
    <div class="header">
        <table>
            <tr>
                <td>
                    <div class="title">{{ $clinica->nombre ?? 'MEDITRACK' }}</div>
                    <div class="subtitle">
                        {{ $clinica->direccion ?? 'Dirección no registrada' }}
                        @if(!empty($clinica->telefono))
                            <br>TEL: {{ $clinica->telefono }}
                        @endif
                        @if(!empty($config['email']) || !empty($clinica->email))
                            | EMAIL: {{ $config['email'] ?? $clinica->email }}
                        @endif
                        @if(!empty($clinica->rut_empresa))
                            | RUT/RFC: {{ $clinica->rut_empresa }}
                        @endif
                    </div>
                </td>
                <td style="text-align: right; vertical-align: top;">
                    <strong style="color: #0d9488; font-size: 13px;">N° Receta: #{{ str_pad($receta->id, 5, '0', STR_PAD_LEFT) }}</strong><br>
                    <span style="color: #64748b;">Fecha: {{ \Carbon\Carbon::parse($receta->fecha_emision ?? $receta->fecha)->format('d/m/Y') }}</span>
                </td>
            </tr>
        </table>
    </div>

    <!-- Datos del Paciente y Médico -->
    <div class="info-box">
        <table>
            <tr>
                <td><strong>Paciente:</strong> {{ $pac->nombre_completo ?? trim(($pac->nombre ?? 'N/A') . ' ' . ($pac->apellido ?? '')) }}</td>
                <td style="text-align: right;"><strong>Edad:</strong> {{ $edadPaciente }}</td>
            </tr>
            <tr>
                <td colspan="2">
                    <strong>Atendido Por:</strong> Dr. {{ $medicoImpresion }}
                </td>
            </tr>
        </table>
    </div>

    <!-- Bloque de Signos Vitales -->
    @if($sv && ($sv->pa_sistolica || $sv->frecuencia_cardiaca || $sv->temperatura || $sv->peso_kg || $sv->talla_cm || $sv->saturacion_oxigeno))
        <div class="vitals-box">
            <strong style="color: #0d9488; text-transform: uppercase; font-size: 9px; display: block; margin-bottom: 4px;">Signos Vitales del Paciente:</strong>
            <table class="vitals-table">
                <tr>
                    @if($sv->pa_sistolica && $sv->pa_diastolica)
                        <td><strong>T/A:</strong> {{ $sv->pa_sistolica }}/{{ $sv->pa_diastolica }} mmHg</td>
                    @elseif($sv->pa_sistolica)
                        <td><strong>P.A.:</strong> {{ $sv->pa_sistolica }} mmHg</td>
                    @endif

                    @if($sv->frecuencia_cardiaca)
                        <td><strong>F.C.:</strong> {{ $sv->frecuencia_cardiaca }} bpm</td>
                    @endif

                    @if($sv->frecuencia_respiratoria)
                        <td><strong>F.R.:</strong> {{ $sv->frecuencia_respiratoria }} rpm</td>
                    @endif

                    @if($sv->temperatura)
                        <td><strong>Temp:</strong> {{ $sv->temperatura }} °C</td>
                    @endif

                    @if($sv->peso_kg)
                        <td><strong>Peso:</strong> {{ $sv->peso_kg }} kg</td>
                    @endif

                    @if($sv->talla_cm)
                        <td><strong>Talla:</strong> {{ $sv->talla_cm }} cm</td>
                    @endif

                    @if($sv->imc)
                        <td><strong>IMC:</strong> {{ $sv->imc }}</td>
                    @endif

                    @if($sv->saturacion_oxigeno)
                        <td><strong>SpO2:</strong> {{ $sv->saturacion_oxigeno }} %</td>
                    @endif
                </tr>
            </table>
        </div>
    @endif

    <!-- Prescripción Médica -->
    <div class="section-title">Medicamentos Prescritos</div>
    <table class="medicamentos-table">
        <thead>
            <tr>
                <th style="width: 40%;">Medicamento</th>
                <th style="width: 30%;">Dosis</th>
                <th style="width: 30%;">Frecuencia / Duración</th>
            </tr>
        </thead>
        <tbody>
            @foreach($receta->detalles as $det)
                <tr>
                    <td><strong>{{ $det->medicamento }}</strong></td>
                    <td>{{ $det->dosis ?? 'N/A' }}</td>
                    <td>{{ $det->frecuencia ?? 'N/A' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Indicaciones Generales -->
    @if($receta->indicaciones_generales)
        <div class="section-title">Indicaciones Adicionales</div>
        <div class="indicaciones-box">
            {{ $receta->indicaciones_generales }}
        </div>
    @endif

    <!-- ÁREA DE FIRMA DEL MÉDICO -->
    <div class="firma-container">
        <div class="firma-box">
            <div class="firma-espacio">
                @if(isset($personalReceta->firma) && $personalReceta->firma)
                    <img src="{{ public_path('storage/' . $personalReceta->firma) }}" alt="Firma Médica">
                @endif
            </div>
            <div class="firma-linea"></div>
            <div class="medico-nombre">
                Dr. {{ $medicoImpresion }}
            </div>
            <div class="medico-cedula">
                Cédula Profesional / RUT: {{ !empty($cedulaImpresion) ? $cedulaImpresion : 'CÉD. PROF. EN TÁMITE' }}
            </div>
            <div style="font-size: 9px; color: #94a3b8; margin-top: 2px;">Firma y Sello del Médico Tratante</div>
        </div>
    </div>

    <div class="footer-note">
        Este documento es una representación impresa de una receta médica generada mediante el sistema {{ $clinica->nombre ?? 'MediTrack' }}.
    </div>

</body>
</html>