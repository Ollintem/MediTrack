<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Consultorio;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class ConsultorioController extends Controller
{
    /** Reglas de validación compartidas por store y update. */
    private function reglas(): array
    {
        return [
            'clinica_id' => 'nullable|integer|exists:clinicas,id',
            'nombre'     => 'required|string|max:80',
            'piso'       => 'nullable|string|max:20',
            'estado'     => 'required|in:Disponible,Ocupado,Mantenimiento',
        ];
    }

    private function mensajes(): array
    {
        return [
            'clinica_id.exists' => 'La clínica indicada no existe. Crea primero una clínica con id 1.',
            'nombre.required'   => 'El nombre del consultorio es obligatorio.',
            'nombre.max'        => 'El nombre no puede superar los 80 caracteres.',
            'estado.required'   => 'Selecciona un estado.',
            'estado.in'         => 'El estado debe ser Disponible, Ocupado o Mantenimiento.',
        ];
    }

    public function index()
    {
        // get() trae TODAS las columnas, incluidas pos_x y pos_y
        $consultorios = Consultorio::orderBy('id')->get();

        return view('consultorios.index', compact('consultorios'));
    }

    public function store(Request $request)
    {
        $datos = $request->validate($this->reglas(), $this->mensajes());
        $datos['clinica_id'] = $datos['clinica_id'] ?? 1;

        Consultorio::create($datos);

        return response()->json(['message' => 'Consultorio registrado correctamente']);
    }

    public function update(Request $request, $id)
    {
        $datos = $request->validate($this->reglas(), $this->mensajes());
        $datos['clinica_id'] = $datos['clinica_id'] ?? 1;

        Consultorio::findOrFail($id)->update($datos);

        return response()->json(['message' => 'Consultorio actualizado correctamente']);
    }

    public function destroy($id)
    {
        try {
            Consultorio::findOrFail($id)->delete();
        } catch (QueryException $e) {
            // Por ejemplo, si el consultorio tiene citas asociadas
            return response()->json([
                'message' => 'No se puede eliminar: el consultorio tiene registros asociados (por ejemplo, citas).'
            ], 409);
        }

        return response()->json(['message' => 'Consultorio eliminado correctamente']);
    }

    /** Guarda la posición del consultorio en el plano 3D. */
    public function actualizarPosicion(Request $request, $id)
    {
        $datos = $request->validate([
            'pos_x' => 'required|integer|min:0|max:5000',
            'pos_y' => 'required|integer|min:0|max:5000',
        ]);

        $consultorio = Consultorio::findOrFail($id);

        // forceFill guarda aunque pos_x / pos_y no estén en $fillable
        $consultorio->forceFill($datos)->save();

        // Se devuelve lo guardado para que la vista pueda confirmarlo
        return response()->json([
            'message' => 'Posición guardada',
            'pos_x'   => (int) $consultorio->pos_x,
            'pos_y'   => (int) $consultorio->pos_y,
        ]);
    }

    /**
     * Estado actual de cada consultorio (la vista lo consulta cada pocos segundos).
     * Si está "Ocupado" por una consulta en curso, indica qué médico lo usa.
     */
    public function estados()
    {
        $enCurso = Cita::with('personal')
            ->where('estado', 'En curso')
            ->get()
            ->keyBy('consultorio_id');

        $lista = Consultorio::all()->map(function ($c) use ($enCurso) {
            $cita = $enCurso->get($c->id);
            $medico = $cita
                ? (data_get($cita, 'personal.nombre_completo') ?: data_get($cita, 'personal.nombre'))
                : null;

            return [
                'id'          => $c->id,
                'estado'      => $c->estado,
                'ocupado_por' => $c->estado === 'Ocupado' ? $medico : null,
            ];
        })->values();

        return response()->json($lista);
    }
}