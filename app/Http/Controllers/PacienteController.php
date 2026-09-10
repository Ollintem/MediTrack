<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use Illuminate\Http\Request;

class PacienteController extends Controller
{
    /**
     * Muestra la lista de pacientes con sus antecedentes médicos cargados.
     */
    public function index()
    {
        $pacientes = Paciente::with(['alergias', 'condiciones', 'medicamentos'])
            ->latest()
            ->get();

        return view('pacientes.index', compact('pacientes'));
    }

    /**
     * Muestra el formulario de creación.
     */
    public function create()
    {
        return view('pacientes.create');
    }

    /**
     * Guarda un nuevo paciente y procesa sus antecedentes clínicos.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre'           => 'required|string|max:255',
            'telefono'         => 'nullable|string|max:20',
            'email'            => 'nullable|email|max:255',
            'fecha_nacimiento' => 'nullable|date',
        ]);

        // Generar un código único simple para el expediente (ej. PAC-84920)
        $codigoGenerado = 'PAC-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);

        // 1. Inserción de los datos principales del paciente
        $paciente = Paciente::create([
            'codigo'                  => $codigoGenerado,
            'nombre_completo'         => $request->nombre,
            'rut'                     => $request->rut,
            'fecha_nacimiento'        => $request->fecha_nacimiento,
            'genero'                  => $request->genero,
            'estado_civil'            => $request->estado_civil,
            'nacionalidad'            => $request->nacionalidad,
            'grupo_sanguineo'         => $request->grupo_sanguineo,
            'telefono'                => $request->telefono,
            'celular'                 => $request->celular,
            'email'                   => $request->email,
            'direccion'               => $request->direccion,
            'contacto_emerg_nombre'   => $request->contacto_emerg_nombre,
            'contacto_emerg_relacion' => $request->contacto_emerg_relacion,
            'contacto_emerg_telefono' => $request->contacto_emerg_telefono,
            'acepta_aviso_privacidad' => $request->boolean('acepta_aviso_privacidad') ? 1 : 0,
            'clinica_id'              => auth()->user()->clinica_id ?? 1,
            'medico_tratante_id'      => auth()->user()->personal?->id ?? null,
            'estado'                  => 'Activo',
        ]);

        // 2. Guardar Alergias
        if ($request->has('alergias') && is_array($request->alergias)) {
            foreach ($request->alergias as $alergia) {
                if (!empty(trim($alergia))) {
                    $paciente->alergias()->create(['nombre' => trim($alergia)]);
                }
            }
        }

        // 3. Guardar Condiciones
        if ($request->has('condiciones') && is_array($request->condiciones)) {
            foreach ($request->condiciones as $condicion) {
                if (!empty(trim($condicion))) {
                    $paciente->condiciones()->create(['nombre' => trim($condicion)]);
                }
            }
        }

        // 4. Guardar Medicamentos
        if ($request->has('medicamentos') && is_array($request->medicamentos)) {
            foreach ($request->medicamentos as $medicamento) {
                if (!empty(trim($medicamento))) {
                    $paciente->medicamentos()->create(['nombre' => trim($medicamento)]);
                }
            }
        }

        return redirect()->route('pacientes.index')
            ->with('success', 'Paciente registrado correctamente en el sistema.');
    }

    /**
     * Muestra el formulario para editar un expediente existente.
     */
    public function edit(Paciente $paciente)
    {
        $paciente->load(['alergias', 'condiciones', 'medicamentos']);
        return view('pacientes.edit', compact('paciente'));
    }

    /**
     * Actualiza la información personal y médica del paciente.
     */
    public function update(Request $request, Paciente $paciente)
    {
        $request->validate([
            'nombre'           => 'required|string|max:255',
            'telefono'         => 'nullable|string|max:20',
            'email'            => 'nullable|email|max:255',
            'fecha_nacimiento' => 'nullable|date',
        ]);

        // 1. Actualizar datos base
        $paciente->update([
            'nombre_completo'  => $request->nombre,
            'telefono'         => $request->telefono,
            'email'            => $request->email,
            'fecha_nacimiento' => $request->fecha_nacimiento,
        ]);

        // 2. Sincronizar Alergias
        if ($request->has('alergias')) {
            $paciente->alergias()->delete();
            foreach ($request->alergias as $alergia) {
                if (!empty(trim($alergia))) {
                    $paciente->alergias()->create(['nombre' => trim($alergia)]);
                }
            }
        }

        // 3. Sincronizar Condiciones
        if ($request->has('condiciones')) {
            $paciente->condiciones()->delete();
            foreach ($request->condiciones as $condicion) {
                if (!empty(trim($condicion))) {
                    $paciente->condiciones()->create(['nombre' => trim($condicion)]);
                }
            }
        }

        // 4. Sincronizar Medicamentos
        if ($request->has('medicamentos')) {
            $paciente->medicamentos()->delete();
            foreach ($request->medicamentos as $medicamento) {
                if (!empty(trim($medicamento))) {
                    $paciente->medicamentos()->create(['nombre' => trim($medicamento)]);
                }
            }
        }

        return redirect()->route('pacientes.index')
            ->with('success', 'Expediente del paciente actualizado con éxito.');
    }

    /**
     * Elimina el registro del paciente y sus relaciones asociadas.
     */
    public function destroy(Paciente $paciente)
    {
        $paciente->delete();

        return response()->json([
            'success' => true,
            'message' => 'El paciente y sus antecedentes fueron eliminados.'
        ]);
    }
}