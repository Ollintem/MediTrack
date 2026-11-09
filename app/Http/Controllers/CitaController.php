<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Paciente;
use App\Models\Consultorio;
use App\Models\Personal;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CitaController extends Controller
{
    private const ESTADOS = ['Pendiente', 'Confirmada', 'En curso', 'Finalizada', 'Cancelada'];

    /** Reglas compartidas por store y update. */
    private function reglas(): array
    {
        return [
            'paciente_id'    => 'required|integer|exists:pacientes,id',
            'personal_id'    => 'required|integer|exists:personals,id',
            'consultorio_id' => 'nullable|integer|exists:consultorios,id',
            'fecha'          => 'required|date',
            'hora'           => ['required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'duracion_min'   => 'required|integer|min:5|max:480',
            'motivo'         => 'required|string|max:255',
            'tipo_consulta'  => 'required|in:Primera Vez,Seguimiento,Urgencia',
            'estado'         => 'required|in:' . implode(',', self::ESTADOS),
        ];
    }

    private function mensajes(): array
    {
        return [
            'paciente_id.required' => 'Selecciona un paciente.',
            'personal_id.required' => 'Selecciona un médico.',
            'fecha.required'       => 'Indica la fecha de la cita.',
            'hora.required'        => 'Indica la hora de la cita.',
            'hora.regex'           => 'La hora debe tener el formato HH:MM.',
            'motivo.required'      => 'Escribe el motivo de la consulta.',
            'estado.in'            => 'El estado de la cita no es válido.',
            'paciente_id.exists'    => 'El paciente seleccionado ya no existe.',
            'personal_id.exists'    => 'El médico seleccionado ya no existe.',
            'consultorio_id.exists' => 'El consultorio seleccionado ya no existe.',
            'tipo_consulta.required' => 'Selecciona el tipo de consulta.',
            'tipo_consulta.in'      => 'El tipo de consulta no es válido.',
        ];
    }

    /** Meses que se cargan alrededor de la fecha que se está viendo (antes se cargaban TODAS las citas). */
    private const MESES_ANTES = 3;
    private const MESES_DESPUES = 12;

    public function index(Request $request)
    {
        // Ventana de citas alrededor de la fecha de la URL (?fecha=AAAA-MM-DD, o el formato anterior ?mes=&anio=).
        // Si en la vista se navega fuera de esta ventana, la página se recarga centrada en la nueva fecha.
        $ancla = Carbon::today();
        try {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->query('fecha'))) {
                $ancla = Carbon::parse($request->query('fecha'));
            } elseif ($request->filled('mes') && $request->filled('anio')) {
                $ancla = Carbon::create((int) $request->query('anio'), (int) $request->query('mes'), 1);
            }
        } catch (\Throwable $e) {
            $ancla = Carbon::today();
        }
        $desde = $ancla->copy()->startOfMonth()->subMonthsNoOverflow(self::MESES_ANTES);
        $hasta = $ancla->copy()->startOfMonth()->addMonthsNoOverflow(self::MESES_DESPUES)->endOfMonth();

        $pacientes    = Paciente::with('alergias')->get();
        $medicos      = Personal::with('usuario.rol')->where('estado', '!=', 'Inactivo')->orderBy('nombre_completo')->get();

        // Solo el personal con rol de médico atiende citas (enfermería, recepción, etc. no aparecen).
        // Si nadie tiene ese rol todavía, se muestra a todo el personal para no dejar la agenda vacía.
        $esMedico   = fn ($m) => in_array(mb_strtolower(trim((string) optional(optional($m->usuario)->rol)->nombre)), ['doctor', 'doctora', 'médico', 'medico', 'médica', 'medica'], true);
        $hayMedicos = $medicos->contains($esMedico);
        $consultorios = Consultorio::orderBy('piso')->orderBy('nombre')->get();
        $citas        = Cita::with(['paciente.alergias'])
            ->whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hasta)
            ->get();

        // Forma que consume la vista (tablero del día, mes, pase y formulario)
        $datosCitas = [
            // Rango cargado: la vista recarga la página si se navega fuera de él
            'rango'        => ['desde' => $desde->toDateString(), 'hasta' => $hasta->toDateString()],
            'citas'        => $citas->map(fn ($c) => $this->presentar($c))->values(),
            'pacientes'    => $pacientes->map(fn ($p) => [
                'id'      => $p->id,
                'nombre'  => $p->nombre ?: 'Paciente',
                'curp'    => $p->rut,
                'alergia' => $p->alergias->pluck('descripcion')->filter()->first(),
            ])->sortBy('nombre', SORT_NATURAL | SORT_FLAG_CASE)->values(),
            'medicos'      => $medicos->map(fn ($m) => [
                'id'           => $m->id,
                'nombre'       => $m->nombre_completo,
                'especialidad' => $m->especialidad_principal,
                'esMedico'     => $hayMedicos ? $esMedico($m) : true,
            ])->values(),
            'consultorios' => $consultorios->map(fn ($c) => [
                'id' => $c->id, 'nombre' => $c->nombre, 'piso' => (string) ($c->piso ?? ''), 'estado' => $c->estado,
            ])->values(),
        ];

        return view('citas.index', compact('pacientes', 'medicos', 'consultorios', 'citas', 'datosCitas'));
    }

    public function create()
    {
        return redirect()->route('citas.index');
    }

    public function getEventos()
    {
        $citas = Cita::with(['paciente', 'personal'])->get();

        $eventos = $citas->map(function ($cita) {
            $start = $cita->fecha . ' ' . ($cita->hora ?? '09:00:00');
            $duracion = $cita->duracion_min ?? 30;
            $end = Carbon::parse($start)->addMinutes($duracion)->format('Y-m-d H:i:s');

            $color = match ($cita->estado ?? 'Pendiente') {
                'Confirmada' => '#10b981',
                'En curso'   => '#0ea5e9',
                'Finalizada' => '#64748b',
                'Cancelada'  => '#f43f5e',
                default      => '#0d9488', // Teal por defecto
            };

            return [
                'id' => $cita->id,
                'title' => ($cita->paciente->nombre ?? 'Paciente') . ' - ' . ($cita->motivo ?? 'Cita'),
                'start' => $start,
                'end' => $end,
                'color' => $color,
                'extendedProps' => [
                    'paciente_id' => $cita->paciente_id,
                    'personal_id' => $cita->personal_id,
                    'consultorio_id' => $cita->consultorio_id,
                    'fecha' => $cita->fecha,
                    'hora' => $cita->hora,
                    'duracion_min' => $duracion,
                    'tipo_consulta' => $cita->tipo_consulta ?? 'Primera Vez',
                    'estado' => $cita->estado ?? 'Pendiente',
                    'motivo' => $cita->motivo
                ]
            ];
        });

        return response()->json($eventos);
    }

    public function store(Request $request)
    {
        // Clínica por defecto (1) y, si no se eligió consultorio, el primero que exista
        $request->merge([
            'consultorio_id' => $request->consultorio_id ?: Consultorio::orderBy('id')->value('id'),
            'clinica_id'     => 1,
        ]);

        $data = $request->validate($this->reglas(), $this->mensajes());
        $data['clinica_id'] = 1;

        // La columna consultorio_id es obligatoria: sin consultorios registrados no se puede agendar
        if (empty($data['consultorio_id'])) {
            return $this->errorHorario($request, 'Registra al menos un consultorio antes de agendar citas.');
        }

        if ($mensaje = $this->choque($data)) {
            return $this->errorHorario($request, $mensaje);
        }

        $cita = Cita::create($data);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Cita agendada correctamente',
                'cita'    => $this->presentar($cita->fresh(['paciente.alergias'])),
            ], 201);
        }

        return redirect()->route('citas.index')->with('success', 'Cita registrada correctamente.');
    }

    public function update(Request $request, Cita $cita)
    {
        // Arrastre de una cita desde un calendario (solo cambia fecha y hora)
        if ($request->has('dragged')) {
            $request->validate(
                ['start' => 'required|date'],
                ['start.required' => 'Falta la nueva fecha de la cita.', 'start.date' => 'La nueva fecha no es válida.']
            );

            $date = Carbon::parse($request->start);
            $nuevo = [
                'fecha'          => $date->format('Y-m-d'),
                'hora'           => $date->format('H:i:s'),
                'duracion_min'   => (int) ($cita->duracion_min ?? 30),
                'personal_id'    => $cita->personal_id,
                'consultorio_id' => $cita->consultorio_id,
                'estado'         => $cita->estado,
            ];

            // Mover la cita también debe respetar la agenda del médico y del consultorio
            if ($mensaje = $this->choque($nuevo, $cita->id)) {
                return response()->json(['status' => 'error', 'message' => $mensaje], 422);
            }

            $cita->update(['fecha' => $nuevo['fecha'], 'hora' => $nuevo['hora']]);

            return response()->json(['status' => 'success']);
        }

        // Edición desde el modal: se valida y se actualiza ESTA cita (nunca se crea una nueva)
        $data = $request->validate($this->reglas(), $this->mensajes());
        $data['consultorio_id'] = $data['consultorio_id'] ?? $cita->consultorio_id;

        if ($mensaje = $this->choque($data, $cita->id)) {
            return $this->errorHorario($request, $mensaje);
        }

        $estadoAntes = $cita->estado;
        $consultorioAntes = $cita->consultorio_id;

        DB::transaction(function () use ($cita, $data, $estadoAntes, $consultorioAntes) {
            $cita->update($data);
            $this->sincronizarConsultorios($cita, $estadoAntes, $consultorioAntes);
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Cita actualizada correctamente',
                'cita'    => $this->presentar($cita->fresh(['paciente.alergias'])),
            ]);
        }

        return redirect()->route('citas.index')->with('success', 'Cita actualizada correctamente.');
    }

    public function destroy(Cita $cita)
    {
        try {
            DB::transaction(function () use ($cita) {
                $consultorio = $cita->estado === 'En curso' ? $cita->consultorio : null;
                $cita->delete();
                // Si se borra una cita que estaba en consulta, su consultorio no debe quedar "Ocupado"
                $consultorio?->liberarSiLibre($cita->id);
            });
        } catch (QueryException $e) {
            // Por ejemplo, si la cita ya tiene una consulta o receta asociada
            $mensaje = 'No se puede eliminar: la cita tiene registros asociados (consulta o receta). Puedes marcarla como Cancelada.';

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json(['status' => 'error', 'message' => $mensaje], 409);
            }

            return redirect()->route('citas.index')->with('error', $mensaje);
        }

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['status' => 'success', 'message' => 'Cita eliminada correctamente']);
        }

        return redirect()->route('citas.index')->with('success', 'Cita eliminada correctamente.');
    }

    /**
     * Mantiene el estado de los consultorios cuando la cita se edita desde este módulo
     * (antes solo lo hacía Consultas, y un consultorio podía quedar "Ocupado" para siempre):
     *  - deja de estar "En curso" o cambia de consultorio → se libera el consultorio anterior;
     *  - pasa a "En curso" → se ocupa su consultorio (salvo mantenimiento).
     */
    private function sincronizarConsultorios(Cita $cita, ?string $estadoAntes, ?int $consultorioAntes): void
    {
        $enCursoAhora = $cita->estado === 'En curso';

        if ($estadoAntes === 'En curso' && (!$enCursoAhora || (int) $consultorioAntes !== (int) $cita->consultorio_id)) {
            Consultorio::find($consultorioAntes)?->liberarSiLibre($cita->id);
        }

        if ($enCursoAhora) {
            $cita->consultorio()->first()?->ocupar();
        }
    }

    /** Datos de una cita para la vista. */
    private function presentar(Cita $c): array
    {
        return [
            'id'             => $c->id,
            'fecha'          => Carbon::parse($c->fecha)->format('Y-m-d'),
            'hora'           => Carbon::parse($c->hora)->format('H:i'),
            'duracion_min'   => (int) ($c->duracion_min ?? 30),
            'estado'         => $c->estado ?? 'Pendiente',
            'motivo'         => $c->motivo ?? '',
            'tipo_consulta'  => $c->tipo_consulta ?? 'Primera Vez',
            'paciente_id'    => $c->paciente_id,
            'personal_id'    => $c->personal_id,
            'consultorio_id' => $c->consultorio_id,
            'paciente'       => $c->paciente?->nombre ?: 'Paciente',
            'alergia'        => $c->paciente?->alergias->pluck('descripcion')->filter()->first(),
        ];
    }

    /** Respuesta de error cuando el horario se traslapa. */
    private function errorHorario(Request $request, string $mensaje)
    {
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['status' => 'error', 'message' => $mensaje], 422);
        }

        return back()->withErrors(['horario' => $mensaje])->withInput();
    }

    /**
     * Revisa si el médico o el consultorio ya tienen otra cita que se traslape.
     * Devuelve el mensaje de error, o null si el horario está libre.
     */
    private function choque(array $d, ?int $ignorarId = null): ?string
    {
        // Una cita cancelada no ocupa horario
        if (($d['estado'] ?? '') === 'Cancelada') {
            return null;
        }

        $inicio = Carbon::parse($d['fecha'] . ' ' . substr($d['hora'], 0, 5));
        $fin = $inicio->copy()->addMinutes((int) $d['duracion_min']);

        $candidatas = Cita::whereDate('fecha', $d['fecha'])
            ->where('estado', '!=', 'Cancelada')
            ->when($ignorarId, fn ($q) => $q->where('id', '!=', $ignorarId))
            ->where(function ($q) use ($d) {
                $q->where('personal_id', $d['personal_id']);
                if (!empty($d['consultorio_id'])) {
                    $q->orWhere('consultorio_id', $d['consultorio_id']);
                }
            })
            ->get();

        foreach ($candidatas as $c) {
            $ci = Carbon::parse(Carbon::parse($c->fecha)->format('Y-m-d') . ' ' . Carbon::parse($c->hora)->format('H:i'));
            $cf = $ci->copy()->addMinutes((int) ($c->duracion_min ?? 30));

            if ($inicio < $cf && $fin > $ci) {
                $rango = $ci->format('H:i') . ' – ' . $cf->format('H:i');

                return $c->personal_id == $d['personal_id']
                    ? "El médico ya tiene una cita en ese horario ($rango)."
                    : "El consultorio ya está ocupado en ese horario ($rango).";
            }
        }

        return null;
    }
}