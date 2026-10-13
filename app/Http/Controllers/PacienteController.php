<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class PacienteController extends Controller
{
    /**
     * Muestra el módulo de pacientes (credencial viva).
     */
    public function index()
    {
        $pacientes = Paciente::with(['alergias', 'condiciones', 'medicamentos'])
            ->latest()
            ->get();

        $pacientesJs = $pacientes->map(fn (Paciente $p) => $this->presentar($p))->values();

        return view('pacientes.index', compact('pacientes', 'pacientesJs'));
    }

    /**
     * El alta se hace desde la credencial del listado.
     */
    public function create()
    {
        return redirect()->route('pacientes.index');
    }

    /**
     * Guarda un nuevo paciente y sus antecedentes clínicos.
     */
    public function store(Request $request)
    {
        $this->validar($request);

        $paciente = Paciente::create(array_merge($this->datosBase($request), [
            'codigo'             => 'PAC-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT),
            'clinica_id'         => auth()->user()->clinica_id ?? 1,
            'medico_tratante_id' => auth()->user()->personal?->id ?? null,
            'estado'             => 'Activo',
        ]));

        $this->sincronizarAntecedentes($paciente, $request);

        if ($request->wantsJson()) {
            return response()->json([
                'message'  => 'Paciente registrado correctamente.',
                'paciente' => $this->presentar($paciente->fresh(['alergias', 'condiciones', 'medicamentos'])),
            ], 201);
        }

        return redirect()->route('pacientes.index')
            ->with('success', 'Paciente registrado correctamente en el sistema.');
    }

    /**
     * Devuelve el expediente (JSON para peticiones asíncronas).
     */
    public function edit(Paciente $paciente)
    {
        $paciente->load(['alergias', 'condiciones', 'medicamentos']);

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json($this->presentar($paciente));
        }

        return redirect()->route('pacientes.index');
    }

    /**
     * Actualiza la información personal y médica del paciente.
     */
    public function update(Request $request, Paciente $paciente)
    {
        $this->validar($request, $paciente);

        $paciente->update($this->datosBase($request));
        $this->sincronizarAntecedentes($paciente, $request, true);

        if ($request->wantsJson()) {
            return response()->json([
                'message'  => 'Expediente actualizado.',
                'paciente' => $this->presentar($paciente->fresh(['alergias', 'condiciones', 'medicamentos'])),
            ]);
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
            'message' => 'El paciente y sus antecedentes fueron eliminados.',
        ]);
    }

    /**
     * Muestra el documento oficial del Aviso de Privacidad.
     */
    public function avisoPrivacidad()
    {
        return view('pacientes.modalAvisoPrivacidad');
    }

    // ------------------------------------------------------------------
    // Auxiliares
    // ------------------------------------------------------------------

    private function validar(Request $request, ?Paciente $paciente = null): void
    {
        $curpUnica = Rule::unique('pacientes', 'rut');
        if ($paciente) {
            $curpUnica = $curpUnica->ignore($paciente->id);
        }

        $request->validate([
            'primer_nombre'           => 'required|string|max:80',
            'apellido_paterno'        => 'required|string|max:80',
            'apellido_materno'        => 'nullable|string|max:80',
            'rut'                     => ['nullable', 'string', 'size:18', 'regex:/^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]\d$/i', $curpUnica],
            'telefono'                => 'nullable|digits:10',
            'celular'                 => 'nullable|digits:10',
            'contacto_emerg_telefono' => 'nullable|digits:10',
            'email'                   => 'nullable|email|max:150',
            'fecha_nacimiento'        => 'nullable|date|before_or_equal:today',
            'genero'                  => ['nullable', Rule::in(['Masculino', 'Femenino', 'Otro'])],
            'estado_civil'            => 'nullable|string|max:30',
            'nacionalidad'            => 'nullable|string|max:50',
            'grupo_sanguineo'         => 'nullable|string|max:10',
            'direccion'               => 'nullable|string|max:255',
            'contacto_emerg_nombre'   => 'nullable|string|max:150',
            'contacto_emerg_relacion' => 'nullable|string|max:50',
            'acepta_aviso_privacidad' => 'nullable|boolean',
            'alergias'                => 'nullable|array',
            'alergias.*'              => 'nullable|string|max:120',
            'condiciones'             => 'nullable|array',
            'condiciones.*'           => 'nullable|string|max:120',
            'medicamentos'            => 'nullable|array',
            'medicamentos.*'          => 'nullable|string|max:120',
        ], [
            'rut.unique'                     => 'Esta CURP ya está registrada con otro paciente.',
            'rut.size'                       => 'La CURP debe tener 18 caracteres.',
            'rut.regex'                      => 'La CURP no tiene un formato válido.',
            'telefono.digits'                => 'El teléfono debe tener exactamente 10 dígitos.',
            'celular.digits'                 => 'El celular debe tener exactamente 10 dígitos.',
            'contacto_emerg_telefono.digits' => 'El teléfono de emergencia debe tener exactamente 10 dígitos.',
            'email.email'                    => 'Escribe un correo con el formato nombre@dominio.com.',
            'fecha_nacimiento.before_or_equal' => 'La fecha de nacimiento no puede ser posterior a hoy.',
        ]);
    }

    /** Datos del paciente normalizados: captura en MAYÚSCULAS (excepto correo). */
    private function datosBase(Request $request): array
    {
        $mayus = fn ($v) => filled($v) ? mb_strtoupper(trim($v), 'UTF-8') : null;

        return [
            'primer_nombre'           => $mayus($request->primer_nombre),
            'apellido_paterno'        => $mayus($request->apellido_paterno),
            'apellido_materno'        => $mayus($request->apellido_materno),
            'rut'                     => $mayus($request->rut),
            'fecha_nacimiento'        => $request->fecha_nacimiento ?: null,
            'genero'                  => $request->genero ?: null,
            'estado_civil'            => $mayus($request->estado_civil),
            'nacionalidad'            => $mayus($request->nacionalidad),
            'grupo_sanguineo'         => $request->grupo_sanguineo ?: null,
            'telefono'                => $request->telefono ?: null,
            'celular'                 => $request->celular ?: null,
            'email'                   => filled($request->email) ? trim($request->email) : null,
            'direccion'               => $mayus($request->direccion),
            'contacto_emerg_nombre'   => $mayus($request->contacto_emerg_nombre),
            'contacto_emerg_relacion' => $mayus($request->contacto_emerg_relacion),
            'contacto_emerg_telefono' => $request->contacto_emerg_telefono ?: null,
            'acepta_aviso_privacidad' => $request->boolean('acepta_aviso_privacidad') ? 1 : 0,
        ];
    }

    private function sincronizarAntecedentes(Paciente $paciente, Request $request, bool $reemplazar = false): void
    {
        $listas = [
            'alergias'     => ['alergias', 'descripcion'],
            'condiciones'  => ['condiciones', 'descripcion'],
            'medicamentos' => ['medicamentos', 'nombre'],
        ];

        foreach ($listas as $campo => [$relacion, $columna]) {
            if ($reemplazar) {
                $paciente->{$relacion}()->delete();
            }
            collect((array) $request->input($campo, []))
                ->map(fn ($v) => mb_strtoupper(trim((string) $v), 'UTF-8'))
                ->filter()
                ->unique()
                ->each(fn ($v) => $paciente->{$relacion}()->create([$columna => $v]));
        }
    }

    /** Forma que consume la vista (credencial, lista y formulario). */
    private function presentar(Paciente $p): array
    {
        $creado = $p->created_at ? Carbon::parse($p->created_at) : null;
        $fnac = null;
        if (!empty($p->fecha_nacimiento)) {
            try {
                $fnac = Carbon::parse($p->fecha_nacimiento)->format('Y-m-d');
            } catch (\Throwable $e) {
            }
        }

        return [
            'id'           => $p->id,
            'codigo'       => $p->codigo,
            'paterno'      => $p->apellido_paterno ?? '',
            'materno'      => $p->apellido_materno ?? '',
            'nombres'      => $p->primer_nombre ?? '',
            'curp'         => $p->rut ?? '',
            'fnac'         => $fnac ?? '',
            'genero'       => $p->genero ?? '',
            'civil'        => $p->estado_civil ?? '',
            'nacionalidad' => $p->nacionalidad ?? '',
            'sangre'       => $p->grupo_sanguineo ?? '',
            'cel'          => $p->celular ?? '',
            'tel'          => $p->telefono ?? '',
            'email'        => $p->email ?? '',
            'direccion'    => $p->direccion ?? '',
            'emNombre'     => $p->contacto_emerg_nombre ?? '',
            'emRel'        => $p->contacto_emerg_relacion ?? '',
            'emTel'        => $p->contacto_emerg_telefono ?? '',
            'aviso'        => (bool) $p->acepta_aviso_privacidad,
            'estado'       => $p->estado ?: 'Activo',
            'alergias'     => $p->alergias->pluck('descripcion')->filter()->values(),
            'condiciones'  => $p->condiciones->pluck('descripcion')->filter()->values(),
            'meds'         => $p->medicamentos->pluck('nombre')->filter()->values(),
            'hoy'          => $creado?->isToday() ?? false,
            'hora'         => $creado?->format('H:i') ?? '',
            'alta'         => $creado ? $creado->locale('es')->translatedFormat('j M Y') : '',
            'creado'       => $creado?->timestamp ?? 0,
        ];
    }
}