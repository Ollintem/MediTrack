<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

        // Métrica 1: Estatus Operativo
        $totalPacientes = $pacientes->count();
        $pacientesActivos = $pacientes->where('estado', 'Activo')->count();

        // Métrica 2: Cumplimiento Normativo / Datos Incompletos
        $pendientesNormativa = $pacientes->filter(function ($paciente) {
            $sinRut = empty($paciente->rut);
            $sinAviso = !$paciente->acepta_aviso_privacidad;
            $sinContacto = empty($paciente->telefono) && empty($paciente->celular);

            return $sinRut || $sinAviso || $sinContacto;
        })->count();

        // Configuración para el Modal de Aviso de Privacidad
        $config = \App\Models\Configuracion::pluck('valor', 'clave')->toArray();

        return view('pacientes.index', compact(
            'pacientes',
            'totalPacientes',
            'pacientesActivos',
            'pendientesNormativa',
            'config'
        ));
    }

    /**
     * Muestra el formulario de creación.
     */
    public function create()
    {
        $config = \App\Models\Configuracion::pluck('valor', 'clave')->toArray();

        return view('pacientes.create', compact('config'));
    }

    /**
     * Guarda un nuevo paciente y procesa sus antecedentes clínicos.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'primer_nombre'           => 'required|string|max:80',
            'apellido_paterno'        => 'required|string|max:80',
            'apellido_materno'        => 'nullable|string|max:80',
            'rut'                     => 'nullable|string|max:20|unique:pacientes,rut',
            'telefono'                => 'nullable|digits:10',
            'celular'                 => 'nullable|digits:10',
            'contacto_emerg_telefono' => 'nullable|digits:10',
            'email'                   => 'nullable|email|max:150',
            'fecha_nacimiento'        => 'nullable|date',
            'genero'                  => 'nullable|string',
            'estado_civil'            => 'nullable|string|max:30',
            'nacionalidad'            => 'nullable|string|max:50',
            'grupo_sanguineo'         => 'nullable|string|max:10',
            'direccion'               => 'nullable|string|max:255',
            'contacto_emerg_nombre'   => 'nullable|string|max:150',
            'contacto_emerg_relacion' => 'nullable|string|max:50',
            'acepta_aviso_privacidad' => 'nullable|boolean',
        ], [
            'rut.unique'                       => 'La CURP ingresada ya se encuentra registrada con otro paciente.',
            'telefono.digits'                  => 'El teléfono principal debe contener exactamente 10 dígitos.',
            'celular.digits'                   => 'El celular debe contener exactamente 10 dígitos.',
            'contacto_emerg_telefono.digits'   => 'El teléfono de emergencia debe contener exactamente 10 dígitos.',
        ]);

        $codigoGenerado = 'PAC-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);

        // Inserción de los datos principales del paciente con nombres divididos
        $paciente = Paciente::create([
            'codigo'                  => $codigoGenerado,
            'primer_nombre'           => mb_strtoupper(trim($request->primer_nombre), 'UTF-8'),
            'apellido_paterno'        => mb_strtoupper(trim($request->apellido_paterno), 'UTF-8'),
            'apellido_materno'        => $request->apellido_materno ? mb_strtoupper(trim($request->apellido_materno), 'UTF-8') : null,
            'rut'                     => $request->rut ? strtoupper(trim($request->rut)) : null,
            'fecha_nacimiento'        => $request->fecha_nacimiento,
            'genero'                  => $request->genero,
            'estado_civil'            => $request->estado_civil,
            'nacionalidad'            => $request->nacionalidad ? mb_strtoupper(trim($request->nacionalidad), 'UTF-8') : null,
            'grupo_sanguineo'         => $request->grupo_sanguineo,
            'telefono'                => $request->telefono,
            'celular'                 => $request->celular,
            'email'                   => $request->email,
            'direccion'               => $request->direccion ? mb_strtoupper(trim($request->direccion), 'UTF-8') : null,
            'contacto_emerg_nombre'   => $request->contacto_emerg_nombre ? mb_strtoupper(trim($request->contacto_emerg_nombre), 'UTF-8') : null,
            'contacto_emerg_relacion' => $request->contacto_emerg_relacion ? mb_strtoupper(trim($request->contacto_emerg_relacion), 'UTF-8') : null,
            'contacto_emerg_telefono' => $request->contacto_emerg_telefono,
            'acepta_aviso_privacidad' => $request->boolean('acepta_aviso_privacidad') ? 1 : 0,
            'clinica_id'              => auth()->user()->clinica_id ?? 1,
            'medico_tratante_id'      => auth()->user()->personal?->id ?? null,
            'estado'                  => 'Activo',
        ]);

        // Guardar Alergias
        if ($request->has('alergias') && is_array($request->alergias)) {
            foreach ($request->alergias as $alergia) {
                if (!empty(trim($alergia))) {
                    $paciente->alergias()->create([
                        'descripcion' => mb_strtoupper(trim($alergia), 'UTF-8')
                    ]);
                }
            }
        }

        // Guardar Condiciones
        if ($request->has('condiciones') && is_array($request->condiciones)) {
            foreach ($request->condiciones as $condicion) {
                if (!empty(trim($condicion))) {
                    $paciente->condiciones()->create([
                        'descripcion' => mb_strtoupper(trim($condicion), 'UTF-8')
                    ]);
                }
            }
        }

        // Guardar Medicamentos
        if ($request->has('medicamentos') && is_array($request->medicamentos)) {
            foreach ($request->medicamentos as $medicamento) {
                if (!empty(trim($medicamento))) {
                    $paciente->medicamentos()->create([
                        'nombre' => mb_strtoupper(trim($medicamento), 'UTF-8')
                    ]);
                }
            }
        }

        return redirect()->route('pacientes.index')
            ->with('success', 'Paciente registrado correctamente en el sistema.');
    }

    /**
     * Muestra el formulario para editar un expediente o retorna sus datos en JSON.
     */
    public function edit(Paciente $paciente)
    {
        $paciente->load(['alergias', 'condiciones', 'medicamentos']);

        // Si la petición solicita JSON (fetch desde el modal), devolver los datos frescos
        if (request()->wantsJson() || request()->ajax()) {
            return response()->json($paciente);
        }

        return view('pacientes.edit', compact('paciente'));
    }

    /**
     * Actualiza la información personal y médica del paciente.
     */
    public function update(Request $request, Paciente $paciente)
    {
        $validated = $request->validate([
            'primer_nombre'           => 'required|string|max:80',
            'apellido_paterno'        => 'required|string|max:80',
            'apellido_materno'        => 'nullable|string|max:80',
            'rut'                     => ['nullable', 'string', 'max:20', Rule::unique('pacientes', 'rut')->ignore($paciente->id)],
            'telefono'                => 'nullable|digits:10',
            'celular'                 => 'nullable|digits:10',
            'contacto_emerg_telefono' => 'nullable|digits:10',
            'email'                   => 'nullable|email|max:150',
            'fecha_nacimiento'        => 'nullable|date',
            'genero'                  => 'nullable|string',
            'estado_civil'            => 'nullable|string|max:30',
            'nacionalidad'            => 'nullable|string|max:50',
            'grupo_sanguineo'         => 'nullable|string|max:10',
            'direccion'               => 'nullable|string|max:255',
            'contacto_emerg_nombre'   => 'nullable|string|max:150',
            'contacto_emerg_relacion' => 'nullable|string|max:50',
            'acepta_aviso_privacidad' => 'nullable|boolean',
        ], [
            'rut.unique'                       => 'La CURP ingresada ya se encuentra registrada con otro paciente.',
            'telefono.digits'                  => 'El teléfono principal debe contener exactamente 10 dígitos.',
            'celular.digits'                   => 'El celular debe contener exactamente 10 dígitos.',
            'contacto_emerg_telefono.digits'   => 'El teléfono de emergencia debe contener exactamente 10 dígitos.',
        ]);

        // 1. Actualizar datos base
        $paciente->update([
            'primer_nombre'           => mb_strtoupper(trim($request->primer_nombre), 'UTF-8'),
            'apellido_paterno'        => mb_strtoupper(trim($request->apellido_paterno), 'UTF-8'),
            'apellido_materno'        => $request->apellido_materno ? mb_strtoupper(trim($request->apellido_materno), 'UTF-8') : null,
            'rut'                     => $request->rut ? strtoupper(trim($request->rut)) : null,
            'fecha_nacimiento'        => $request->fecha_nacimiento,
            'genero'                  => $request->genero,
            'estado_civil'            => $request->estado_civil,
            'nacionalidad'            => $request->nacionalidad ? mb_strtoupper(trim($request->nacionalidad), 'UTF-8') : null,
            'grupo_sanguineo'         => $request->grupo_sanguineo,
            'telefono'                => $request->telefono,
            'celular'                 => $request->celular,
            'email'                   => $request->email,
            'direccion'               => $request->direccion ? mb_strtoupper(trim($request->direccion), 'UTF-8') : null,
            'contacto_emerg_nombre'   => $request->contacto_emerg_nombre ? mb_strtoupper(trim($request->contacto_emerg_nombre), 'UTF-8') : null,
            'contacto_emerg_relacion' => $request->contacto_emerg_relacion ? mb_strtoupper(trim($request->contacto_emerg_relacion), 'UTF-8') : null,
            'contacto_emerg_telefono' => $request->contacto_emerg_telefono,
            'acepta_aviso_privacidad' => $request->boolean('acepta_aviso_privacidad') ? 1 : 0,
        ]);

        // 2. Sincronizar Alergias
        $paciente->alergias()->delete();
        if ($request->has('alergias') && is_array($request->alergias)) {
            foreach ($request->alergias as $alergia) {
                if (!empty(trim($alergia))) {
                    $paciente->alergias()->create([
                        'descripcion' => mb_strtoupper(trim($alergia), 'UTF-8')
                    ]);
                }
            }
        }

        // 3. Sincronizar Condiciones
        $paciente->condiciones()->delete();
        if ($request->has('condiciones') && is_array($request->condiciones)) {
            foreach ($request->condiciones as $condicion) {
                if (!empty(trim($condicion))) {
                    $paciente->condiciones()->create([
                        'descripcion' => mb_strtoupper(trim($condicion), 'UTF-8')
                    ]);
                }
            }
        }

        // 4. Sincronizar Medicamentos
        $paciente->medicamentos()->delete();
        if ($request->has('medicamentos') && is_array($request->medicamentos)) {
            foreach ($request->medicamentos as $medicamento) {
                if (!empty(trim($medicamento))) {
                    $paciente->medicamentos()->create([
                        'nombre' => mb_strtoupper(trim($medicamento), 'UTF-8')
                    ]);
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
    /**
     * Muestra el documento oficial del Aviso de Privacidad.
     */
    public function avisoPrivacidad()
    {
        return view('pacientes.modalAvisoPrivacidad');
    }
}