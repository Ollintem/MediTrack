<?php

namespace App\Http\Controllers;

use App\Models\Receta;
use App\Models\RecetaDetalle;
use App\Models\Paciente;
use App\Models\Personal;
use App\Models\SignosVitales;
use App\Models\Consulta;
use App\Models\Modulo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class RecetaController extends Controller
{
    public function index()
    {
        $recetas = Receta::with(['detalles', 'paciente', 'personal', 'signoVital'])
            ->orderBy('created_at', 'desc')
            ->get();
            
        $pacientes = Paciente::all();
        $modulos = Modulo::all();

        $doctores = Personal::whereHas('usuario.rol', function ($query) {
            $query->whereIn(DB::raw('LOWER(nombre)'), ['doctor', 'médico', 'medico', 'doc']);
        })->get();

        if ($doctores->isEmpty()) {
            $doctores = Personal::all();
        }

        return view('recetas.index', compact('recetas', 'pacientes', 'modulos', 'doctores'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'paciente_id' => 'required|exists:pacientes,id',
            'fecha_emision' => 'required|date',
            'personal_id' => 'nullable|exists:personals,id',
            'indicaciones_generales' => 'nullable|string',
            'pa_sistolica' => 'nullable|numeric',
            'pa_diastolica' => 'nullable|numeric',
            'frecuencia_cardiaca' => 'nullable|numeric',
            'frecuencia_respiratoria' => 'nullable|numeric',
            'temperatura' => 'nullable|numeric',
            'saturacion_oxigeno' => 'nullable|numeric',
            'peso_kg' => 'nullable|numeric',
            'talla_cm' => 'nullable|numeric',
            'glucosa_sangre' => 'nullable|numeric',
            'medicamentos' => 'required|array|min:1',
            'medicamentos.*.nombre' => 'required|string',
            'medicamentos.*.dosis' => 'nullable|string',
            'medicamentos.*.frecuencia' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request) {
            $user = auth()->user();

            if ($request->filled('personal_id')) {
                $personalId = $request->personal_id;
            } else {
                $personalId = $user->personal?->id 
                    ?? Personal::where('user_id', $user->id)->value('id');
            }

            // 1. Vincular la receta a la última consulta abierta del paciente si existe
            $consultaId = Consulta::where('paciente_id', $request->paciente_id)
                ->latest()
                ->value('id');

            // 2. Crear la Receta
$receta = Receta::create([
    'folio'                  => 'REC-' . strtoupper(uniqid()),
    'paciente_id'            => $request->paciente_id,
    'personal_id'            => $personalId,
    'fecha_emision'          => $request->fecha_emision, // Solo esta columna existe en la tabla
    'indicaciones_generales' => $request->indicaciones_generales,
]);

            // 3. Guardar Signos Vitales
            if ($request->filled('pa_sistolica') || $request->filled('frecuencia_cardiaca') || $request->filled('temperatura') || $request->filled('peso_kg')) {
                
                $imc = null;
                if ($request->filled('peso_kg') && $request->filled('talla_cm') && $request->talla_cm > 0) {
                    $tallaMetros = $request->talla_cm > 3 ? $request->talla_cm / 100 : $request->talla_cm;
                    $imc = round($request->peso_kg / ($tallaMetros * $tallaMetros), 2);
                }

                SignosVitales::create([
                    'consulta_id' => $consultaId, // Permite NULL sin arrojar error SQL 1364
                    'paciente_id' => $request->paciente_id,
                    'fecha_registro' => $request->fecha_emision,
                    'pa_sistolica' => $request->pa_sistolica,
                    'pa_diastolica' => $request->pa_diastolica,
                    'frecuencia_cardiaca' => $request->frecuencia_cardiaca,
                    'frecuencia_respiratoria' => $request->frecuencia_respiratoria,
                    'temperatura' => $request->temperatura,
                    'saturacion_oxigeno' => $request->saturacion_oxigeno,
                    'peso_kg' => $request->peso_kg,
                    'talla_cm' => $request->talla_cm,
                    'imc' => $imc,
                    'glucosa_sangre' => $request->glucosa_sangre,
                ]);
            }

            // 4. Registrar Medicamentos
            foreach ($request->medicamentos as $med) {
                if (!empty($med['nombre'])) {
                    RecetaDetalle::create([
                        'receta_id' => $receta->id,
                        'medicamento' => $med['nombre'],
                        'dosis' => $med['dosis'] ?? null,
                        'frecuencia' => $med['frecuencia'] ?? null,
                    ]);
                }
            }
        });

        return redirect()->route('recetas.index')->with('success', 'Receta médica registrada correctamente.');
    }

    public function pdf($id)
    {
        $receta = Receta::with(['detalles', 'paciente', 'personal', 'signoVital'])->findOrFail($id);
        $pdf = Pdf::loadView('recetas.pdf', compact('receta'));
        return $pdf->stream('Receta-N' . str_pad($receta->id, 5, '0', STR_PAD_LEFT) . '.pdf');
    }

    public function destroy($id)
    {
        $receta = Receta::findOrFail($id);
        $receta->detalles()->delete();
        $receta->delete();

        return response()->json([
            'success' => true,
            'message' => 'La receta ha sido eliminada del sistema.'
        ]);
    }
}