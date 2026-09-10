<?php

namespace App\Http\Controllers;

use App\Models\Receta;
use App\Models\RecetaDetalle;
use App\Models\Paciente;
use App\Models\Personal;
use App\Models\Modulo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class RecetaController extends Controller
{
    public function index()
    {
        $recetas = Receta::with(['detalles', 'paciente', 'personal'])
            ->orderBy('created_at', 'desc')
            ->get();
            
        $pacientes = Paciente::all();
        $modulos = Modulo::all();

        return view('recetas.index', compact('recetas', 'pacientes', 'modulos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'paciente_id' => 'required|exists:pacientes,id',
            'fecha_emision' => 'required|date',
            'indicaciones_generales' => 'nullable|string',
            'medicamentos' => 'required|array|min:1',
            'medicamentos.*.nombre' => 'required|string',
            'medicamentos.*.dosis' => 'nullable|string',
            'medicamentos.*.frecuencia' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request) {
            // Se valida la existencia del personal en la tabla 'personals'
            $personalId = auth()->user()->personal_id ?? null;

            if ($personalId && !Personal::where('id', $personalId)->exists()) {
                $personalId = null;
            }

            // Si es nulo, tomar el primer personal disponible como respaldo
            if (!$personalId) {
                $personalId = Personal::first()?->id;
            }

            $receta = Receta::create([
                'paciente_id' => $request->paciente_id,
                'personal_id' => $personalId,
                'fecha' => $request->fecha_emision,
                'fecha_emision' => $request->fecha_emision,
                'indicaciones_generales' => $request->indicaciones_generales,
            ]);

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

        return redirect()->route('recetas.index')->with('success', 'Receta médica generada correctamente.');
    }

    public function pdf($id)
    {
        $receta = Receta::with(['detalles', 'paciente', 'personal'])->findOrFail($id);

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