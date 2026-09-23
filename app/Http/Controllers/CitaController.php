<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Paciente;
use App\Models\Consultorio;
use App\Models\Personal;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CitaController extends Controller
{
    public function index()
{
    $pacientes = Paciente::all() ?? collect();
    $medicos = Personal::all() ?? collect();
    $consultorios = \App\Models\Consultorio::all() ?? collect(); // <--- Añadido

    return view('citas.index', compact('pacientes', 'medicos', 'consultorios'));
}

    public function getEventos()
    {
        $citas = Cita::all();
        
        $eventos = $citas->map(function($cita) {
            $start = $cita->fecha . ' ' . ($cita->hora ?? '09:00:00');
            $duracion = $cita->duracion_min ?? 30;
            $end = Carbon::parse($start)->addMinutes($duracion)->format('Y-m-d H:i:s');
            
            $color = match($cita->estado ?? 'Pendiente') {
                'Confirmada' => '#10b981',
                'En curso'   => '#0ea5e9',
                'Finalizada' => '#64748b',
                'Cancelada'  => '#f43f5e',
                default      => '#0d9488', // Teal por defecto
            };

            return [
                'id' => $cita->id,
                'title' => $cita->motivo ?? 'Cita Médica',
                'start' => $start,
                'end' => $end,
                'color' => $color,
                'extendedProps' => [
                    'paciente_id' => $cita->paciente_id,
                    'personal_id' => $cita->personal_id,
                    'fecha' => $cita->fecha,
                    'hora' => $cita->hora,
                    'duracion_min' => $duracion,
                    'tipo_consulta' => $cita->tipo_consulta ?? 'Primera Vez',
                    'estado' => $cita->estado ?? 'Pendiente',
                    'motivo' => $cita->motivo
                ]
            ];
        });

        return response()->json($eventos);
    }

    public function store(Request $request)
{
    // Limpiamos los campos opcionales si vienen vacíos para evitar errores de validación de enteros
    $request->merge([
        'consultorio_id' => $request->consultorio_id ? $request->consultorio_id : 1,
        'clinica_id'     => $request->clinica_id ? $request->clinica_id : 1,
    ]);

    $data = $request->validate([
        'paciente_id'    => 'required',
        'personal_id'    => 'required',
        'consultorio_id' => 'nullable|integer',
        'clinica_id'     => 'nullable|integer',
        'fecha'          => 'required|date',
        'hora'           => 'required',
        'duracion_min'   => 'required|integer',
        'motivo'         => 'required|string|max:255',
        'tipo_consulta'  => 'required|string',
        'estado'         => 'required|string',
    ]);

    Cita::create($data);
    
    return response()->json(['status' => 'success']);
}

    public function update(Request $request, Cita $cita)
    {
        if ($request->has('dragged')) {
            $date = Carbon::parse($request->start);
            $cita->update([
                'fecha' => $date->format('Y-m-d'),
                'hora' => $date->format('H:i:s')
            ]);
        } else {
            $cita->update($request->all());
        }
        
        return response()->json(['status' => 'success']);
    }

    public function destroy(Cita $cita)
    {
        $cita->delete();
        return response()->json(['status' => 'success']);
    }
}