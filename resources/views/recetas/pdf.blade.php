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
            font-size: 22px;
            font-weight: bold;
            color: #0d9488;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .subtitle {
            font-size: 10px;
            color: #64748b;
            font-weight: bold;
        }
        .info-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 10px 12px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .info-box table {
            width: 100%;
        }
        .info-box td {
            padding: 3px 0;
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
            background-color: #f1f5f9;
            border-left: 3px solid #0d9488;
            padding: 8px 12px;
            margin-bottom: 25px;
            font-style: italic;
            color: #334155;
        }
        
        .firma-container {
            margin-top: 40px;
            width: 100%;
            text-align: center;
        }
        .firma-box {
            display: inline-block;
            width: 280px;
            text-align: center;
        }
        .firma-espacio {
            height: 55px;
            margin-bottom: 5px;
        }
        .firma-espacio img {
            max-height: 50px;
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
        $medicoImpresion = $receta->personal->nombre_completo ?? auth()->user()->personal->nombre_completo ?? auth()->user()->nombre_completo;
    @endphp

    <!-- Encabezado -->
    <div class="header">
        <table>
            <tr>
                <td>
                    <div class="title">MediTrack</div>
                    <div class="subtitle">Clínica Médica & Atención Especializada</div>
                </td>
                <td style="text-align: right;">
                    <strong style="color: #0d9488; font-size: 13px;">N° Receta: #{{ str_pad($receta->id, 5, '0', STR_PAD_LEFT) }}</strong><br>
                    <span style="color: #64748b;">Fecha: {{ \Carbon\Carbon::parse($receta->fecha_emision ?? $receta->fecha)->format('d/m/Y') }}</span>
                </td>
            </tr>
        </table>
    </div>

    <!-- Datos del Paciente -->
    <div class="info-box">
        <table>
            <tr>
                <td><strong>Paciente:</strong> {{ $receta->paciente->nombre_completo ?? trim(($receta->paciente->nombre ?? 'N/A') . ' ' . ($receta->paciente->apellido ?? '')) }}</td>
                <td><strong>Edad:</strong> {{ $receta->paciente->edad ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td colspan="2">
                    <strong>Atendido Por:</strong> Dr. {{ $medicoImpresion }}
                </td>
            </tr>
        </table>
    </div>

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
            @if(isset($receta->personal->firma) && $receta->personal->firma)
                <img src="{{ public_path('storage/' . $receta->personal->firma) }}" alt="Firma Médica">
            @endif
        </div>
        <div class="firma-linea"></div>
        <div class="medico-nombre">
            Dr. {{ $medicoImpresion }}
        </div>
        <div class="medico-cedula">
            Cédula Profesional: {{ $receta->personal->rut ?? auth()->user()->personal->rut ?? 'CÉD. PROF. EN TÁMITE' }}
        </div>
        <div style="font-size: 9px; color: #94a3b8; margin-top: 2px;">Firma y Sello del Médico Tratante</div>
    </div>
</div>

    <div class="footer-note">
        Este documento es una representación impresa de una receta médica generada mediante el sistema MediTrack.
    </div>

</body>
</html>