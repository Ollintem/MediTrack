<?php

namespace App\Http\Controllers;

use App\Models\Receta;
use App\Models\RecetaDetalle;
use App\Models\Paciente;
use App\Models\Personal;
use App\Models\Modulo;
use App\Models\Consulta;
use App\Models\Clinica;       // Modelo de Clinica importado
use App\Models\Configuracion;  // Modelo de Configuracion importado
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Barryvdh\DomPDF\Facade\Pdf;

class RecetaController extends Controller
{
    public function index(Request $request)
    {
        // Si se llega desde una consulta finalizada (/recetas?consulta_id=ID), se prepara la receta con sus datos
        $prefill = $this->prefillDesdeConsulta($request);

        // Si esa consulta ya tiene receta, no se abre otra vez el formulario (evita duplicados al recargar)
        if ($prefill && Schema::hasColumn('recetas', 'consulta_id')) {
            $existente = Receta::where('consulta_id', $prefill['consulta_id'])->first();
            if ($existente) {
                return redirect()->route('recetas.index')
                    ->with('success', 'Esta consulta ya tiene la receta #' . str_pad($existente->id, 5, '0', STR_PAD_LEFT) . '.');
            }
        }

        $recetas = Receta::with(['detalles', 'paciente', 'personal'])
            ->orderBy('created_at', 'desc')
            ->get();

        $pacientes = Paciente::all();
        $modulos = Modulo::all();

        // Carga de datos de la clínica y configuraciones globales
        $clinica = Clinica::first();
        $config = Configuracion::pluck('valor', 'clave')->toArray();

        $doctores = Personal::whereHas('usuario.rol', function ($query) {
            $query->whereIn(DB::raw('LOWER(nombre)'), ['doctor', 'médico', 'medico', 'doc']);
        })->get();

        if ($doctores->isEmpty()) {
            $doctores = Personal::all();
        }

        return view('recetas.index', compact('recetas', 'pacientes', 'modulos', 'doctores', 'clinica', 'config', 'prefill'));
    }

    /**
     * Las recetas se crean desde un modal en la pantalla principal; no existe una página "create".
     * Por compatibilidad, cualquier enlace viejo o guardado a /recetas/create lleva a esa pantalla
     * conservando los parámetros (por ejemplo ?consulta_id=5).
     */
    public function create(Request $request)
    {
        return redirect()->route('recetas.index', $request->query());
    }

    /**
     * Datos de una consulta para prellenar la receta: paciente, médico, diagnóstico (notas generales)
     * y un resumen de la exploración para tenerlo a la vista mientras se prescribe.
     */
    private function prefillDesdeConsulta(Request $request): ?array
    {
        if (!$request->filled('consulta_id')) {
            return null;
        }

        $consulta = Consulta::find($request->consulta_id);
        if (!$consulta) {
            return null;
        }

        // Un médico solo puede preparar recetas de sus propias consultas (el administrador, de todas)
        $user = auth()->user();
        $personalUsuario = $user->personal->id ?? null;
        $esAdmin = (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())
            || in_array(strtolower($user->rol->nombre ?? ''), ['administrador', 'admin', 'super admin'], true);

        if (!$esAdmin && $personalUsuario && (int) $consulta->personal_id !== (int) $personalUsuario) {
            return null;
        }

        $paciente = Paciente::find($consulta->paciente_id);
        $doctor = Personal::find($consulta->personal_id);

        $nombre = fn ($m) => $m
            ? ($m->nombre_completo ?? trim(($m->nombre ?? '') . ' ' . ($m->apellido ?? '')))
            : null;

        $etiquetas = [
            'exploracion_fisica' => 'Exploración física',
            'cabeza_cuello'      => 'Cabeza y cuello',
            'torax'              => 'Tórax',
            'abdomen'            => 'Abdomen',
            'extremidades'       => 'Extremidades',
            'piel_faneras'       => 'Piel y faneras',
            'neurologico'        => 'Neurológico',
        ];

        $resumen = [];
        foreach ($etiquetas as $campo => $etiqueta) {
            $texto = trim((string) ($consulta->{$campo} ?? ''));
            if ($texto !== '') {
                $resumen[] = ['etiqueta' => $etiqueta, 'texto' => $texto];
            }
        }

        return [
            'consulta_id'     => $consulta->id,
            'paciente_id'     => $consulta->paciente_id,
            'paciente_nombre' => $nombre($paciente) ?: 'Paciente',
            'personal_id'     => $consulta->personal_id,
            'personal_nombre' => $doctor ? 'Dr. ' . $nombre($doctor) : null,
            'diagnostico'     => trim((string) ($consulta->notas_generales ?? '')),
            'fecha'           => $consulta->fecha ? Carbon::parse($consulta->fecha)->format('d/m/Y') : null,
            'resumen'         => $resumen,
        ];
    }

    public function store(Request $request)
    {
        $request->validate([
            'paciente_id' => 'required|exists:pacientes,id',
            'diagnostico' => 'required|string',
            'medicamentos' => 'required|array|min:1',
            'consulta_id' => 'nullable|integer|exists:consultas,id',
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

        // Enlaza la receta con la consulta de la que viene (requiere la columna consulta_id en recetas)
        if ($request->filled('consulta_id') && Schema::hasColumn('recetas', 'consulta_id')) {
            $receta->forceFill(['consulta_id' => $request->consulta_id])->save();
        }

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

        // Obtener la información de la clínica y configuraciones globales
        $clinica = Clinica::first();
        $config = Configuracion::pluck('valor', 'clave')->toArray();

        // Enviar $clinica y $config a la vista de PDF
        $pdf = Pdf::loadView('recetas.pdf', compact('receta', 'clinica', 'config'));
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