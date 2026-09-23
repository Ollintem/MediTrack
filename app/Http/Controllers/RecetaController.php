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
            'diagnostico' => 'required|string',
            'medicamentos' => 'required|array|min:1',
        ]);

        $personalId = $request->personal_id ?? (auth()->user()->personal->id ?? null);

        // CONVERTIR A MAYÚSCULAS LOS CAMPOS INGRESADOS POR EL USUARIO
        $receta = Receta::create([
            'paciente_id'            => $request->paciente_id,
            'personal_id'            => $personalId,
            'fecha_emision'          => now(),
            'diagnostico'            => mb_strtoupper($request->diagnostico, 'UTF-8'),
            'indicaciones_generales' => $request->indicaciones_generales ? mb_strtoupper($request->indicaciones_generales, 'UTF-8') : null,
        ]);

        // GUARDAR CADA MEDICAMENTO EN MAYÚSCULAS
        if (isset($request->medicamentos[0]) && is_array($request->medicamentos[0])) {
            foreach ($request->medicamentos as $med) {
                if (!empty($med['nombre'])) {
                    $receta->detalles()->create([
                        'medicamento' => mb_strtoupper($med['nombre'], 'UTF-8'),
                        'dosis'       => !empty($med['dosis']) ? mb_strtoupper($med['dosis'], 'UTF-8') : null,
                        'frecuencia'  => !empty($med['frecuencia']) ? mb_strtoupper($med['frecuencia'], 'UTF-8') : null,
                        'duracion'    => !empty($med['duracion']) ? mb_strtoupper($med['duracion'], 'UTF-8') : null,
                    ]);
                }
            }
        } else {
            foreach ($request->medicamentos as $index => $nombreMed) {
                if (!empty($nombreMed)) {
                    $receta->detalles()->create([
                        'medicamento' => mb_strtoupper($nombreMed, 'UTF-8'),
                        'dosis'       => !empty($request->dosis[$index]) ? mb_strtoupper($request->dosis[$index], 'UTF-8') : null,
                        'frecuencia'  => !empty($request->frecuencias[$index]) ? mb_strtoupper($request->frecuencias[$index], 'UTF-8') : null,
                        'duracion'    => !empty($request->duraciones[$index]) ? mb_strtoupper($request->duraciones[$index], 'UTF-8') : null,
                    ]);
                }
            }
        }

        return redirect()->route('recetas.index')->with('success', 'Receta médica registrada correctamente.');
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