<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Consulta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ConsultaController extends Controller
{
    public function index()
    {
        return view('consultas.index');
    }

    // Endpoint JSON que alimenta el calendario de forma personalizada por doctor
    // Endpoint JSON que alimenta el calendario de forma segura
    public function getCitasAsignadas(Request $request)
    {
        $user = Auth::user();
        
        // Si es Super Admin (ID 1), ve todas las citas; si no, solo las suyas
        if ($user->id === 1) {
            $citas = Cita::with(['paciente'])->get();
        } else {
            $personalId = $user->personal_id ?? $user->id; 
            $citas = Cita::where('personal_id', $personalId)->with(['paciente'])->get();
        }

        $eventos = $citas->map(function($cita) {
            $start = $cita->fecha . ' ' . ($cita->hora ?? '09:00:00');
            $duracion = $cita->duracion_min ?? 30;
            $end = Carbon::parse($start)->addMinutes($duracion)->format('Y-m-d H:i:s');
            
            $color = match($cita->estado) {
                'Confirmada' => '#10b981',
                'En curso'   => '#0ea5e9',
                'Finalizada' => '#64748b',
                default      => '#0d9488',
            };

            return [
                'id' => $cita->id,
                'title' => 'Cita # ' . $cita->id . ' | Paciente: ' . ($cita->paciente->nombre_completo ?? 'General'),
                'start' => $start,
                'end' => $end,
                'color' => $color,
                'extendedProps' => [
                    'cita_id'         => $cita->id,
                    'paciente_id'     => $cita->paciente_id,
                    'paciente_nombre' => $cita->paciente->nombre_completo ?? 'Sin nombre',
                    'personal_id'     => $cita->personal_id,
                    'motivo'          => $cita->motivo,
                    'estado_cita'     => $cita->estado
                ]
            ];
        });

        return response()->json($eventos);
    }
    // Guardar la consulta médica y conectar con recetas
    public function store(Request $request)
    {
        $user = Auth::user();
        $personalId = $user->personal_id ?? $user->id;

        $data = $request->validate([
            'cita_id'           => 'required|exists:citas,id',
            'paciente_id'       => 'required|exists:pacientes,id',
            'exploracion_fisica'=> 'nullable|string',
            'cabeza_cuello'     => 'nullable|string',
            'torax'             => 'nullable|string',
            'abdomen'           => 'nullable|string',
            'extremidades'      => 'nullable|string',
            'piel_faneras'      => 'nullable|string',
            'neurologico'       => 'nullable|string',
            'notas_generales'   => 'nullable|string',
        ]);

        $data['personal_id'] = $personalId;
        $data['fecha'] = now()->toDateString();
        $data['estado'] = 'Atendida';

        // Creamos el registro clínico basado exactamente en tu tabla de consultas
        $consulta = Consulta::create($data);

        // Actualizamos el estado de la cita a Finalizada
        Cita::where('id', $request->cita_id)->update(['estado' => 'Finalizada']);

        // Retornamos respuesta indicando éxito y enviando el ID para enlazar con Recetas
        return response()->json([
            'status' => 'success', 
            'message' => 'Consulta registrada con éxito',
            'consulta_id' => $consulta->id,
            'redireccion_receta' => route('recetas.create', ['consulta_id' => $consulta->id, 'paciente_id' => $consulta->paciente_id])
        ]);
    }
}