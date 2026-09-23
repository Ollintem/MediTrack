<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use App\Models\Cita;
use App\Models\Consulta;
use Illuminate\Http\Request;

class BusquedaController extends Controller
{
    public function buscar(Request $request)
    {
        $query = trim($request->input('q', ''));
        $resultados = [];

        // Si la búsqueda tiene menos de 2 caracteres, retornamos un array vacío
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $user = auth()->user();

        // 1. Búsqueda en PACIENTES (Solo si tiene permiso)
        if ($user->tienePermiso('Pacientes', 'ver') || $user->tienePermiso('Pacientes', 'consultar')) {
            $pacientes = Paciente::where('primer_nombre', 'LIKE', "%{$query}%")
                ->orWhere('apellido_paterno', 'LIKE', "%{$query}%")
                ->orWhere('rut', 'LIKE', "%{$query}%")
                ->limit(5)
                ->get();

            foreach ($pacientes as $paciente) {
                $resultados[] = [
                    'categoria' => 'Pacientes',
                    'icono'     => 'bi-person-fill',
                    'titulo'    => "{$paciente->primer_nombre} {$paciente->apellido_paterno}",
                    'subtitulo' => "CURP/RUT: {$paciente->rut}",
                    'url'       => route('pacientes.index', ['search' => $paciente->rut]),
                ];
            }
        }

        // 2. Búsqueda en CITAS (Solo si tiene permiso)
        if ($user->tienePermiso('Citas', 'ver') || $user->tienePermiso('Citas', 'consultar')) {
            $citas = Cita::with('paciente')
                ->whereHas('paciente', function ($q) use ($query) {
                    $q->where('primer_nombre', 'LIKE', "%{$query}%")
                      ->orWhere('apellido_paterno', 'LIKE', "%{$query}%");
                })
                ->orWhere('motivo', 'LIKE', "%{$query}%")
                ->limit(5)
                ->get();

            foreach ($citas as $cita) {
                $resultados[] = [
                    'categoria' => 'Citas',
                    'icono'     => 'bi-calendar-event-fill',
                    'titulo'    => "Cita: " . ($cita->paciente->primer_nombre ?? 'Paciente'),
                    'subtitulo' => "Fecha: {$cita->fecha} - Motivo: {$cita->motivo}",
                    'url'       => route('citas.index', ['cita_id' => $cita->id]),
                ];
            }
        }

        // 3. Búsqueda en CONSULTAS / HISTORIAL (Solo si tiene permiso)
        if ($user->tienePermiso('Consultas', 'ver')) {
            $consultas = Consulta::where('diagnostico', 'LIKE', "%{$query}%")
                ->limit(5)
                ->get();

            foreach ($consultas as $consulta) {
                $resultados[] = [
                    'categoria' => 'Consultas',
                    'icono'     => 'bi-journal-medical',
                    'titulo'    => "Consulta #{$consulta->id}",
                    'subtitulo' => "Diag: " . substr($consulta->diagnostico, 0, 35) . "...",
                    'url'       => route('consultas.index', ['id' => $consulta->id]),
                ];
            }
        }

        return response()->json($resultados);
    }
}