<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Paciente;
use App\Models\Consultorio;
use App\Models\Personal;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CitaController extends Controller
{
    private const ESTADOS = ['Pendiente', 'Confirmada', 'En curso', 'Finalizada', 'Cancelada'];

    /** Reglas compartidas por store y update. */
    private function reglas(): array
    {
        return [
            'paciente_id'    => 'required|integer',
            'personal_id'    => 'required|integer',
            'consultorio_id' => 'nullable|integer',
            'fecha'          => 'required|date',
            'hora'           => ['required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'duracion_min'   => 'required|integer|min:5|max:480',
            'motivo'         => 'required|string|max:255',
            'tipo_consulta'  => 'required|string|max:50',
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
        ];
    }

    public function index()
    {
        $pacientes = Paciente::all() ?? collect();
        $medicos = Personal::all() ?? collect();
        $consultorios = Consultorio::all() ?? collect();

        // Obtenemos las citas reales con sus relaciones para mostrarlas en el calendario
        $citas = Cita::with(['paciente', 'personal', 'consultorio'])->get();

        return view('citas.index', compact('pacientes', 'medicos', 'consultorios', 'citas'));
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
        // Asignamos la clínica por defecto (1) y un consultorio por defecto si no se selecciona
        $request->merge([
            'consultorio_id' => $request->consultorio_id ? $request->consultorio_id : 1,
            'clinica_id'     => 1,
        ]);

        $data = $request->validate($this->reglas(), $this->mensajes());
        $data['clinica_id'] = 1;

        if ($mensaje = $this->choque($data)) {
            return $this->errorHorario($request, $mensaje);
        }

        Cita::create($data);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['status' => 'success', 'message' => 'Cita agendada correctamente']);
        }

        return redirect()->route('citas.index')->with('success', 'Cita registrada correctamente.');
    }

    public function update(Request $request, Cita $cita)
    {
        // Arrastre de una cita desde un calendario (solo cambia fecha y hora)
        if ($request->has('dragged')) {
            $date = Carbon::parse($request->start);
            $cita->update([
                'fecha' => $date->format('Y-m-d'),
                'hora' => $date->format('H:i:s')
            ]);

            return response()->json(['status' => 'success']);
        }

        // Edición desde el modal: se valida y se actualiza ESTA cita (nunca se crea una nueva)
        $data = $request->validate($this->reglas(), $this->mensajes());
        $data['consultorio_id'] = $data['consultorio_id'] ?? $cita->consultorio_id;

        if ($mensaje = $this->choque($data, $cita->id)) {
            return $this->errorHorario($request, $mensaje);
        }

        $cita->update($data);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['status' => 'success', 'message' => 'Cita actualizada correctamente']);
        }

        return redirect()->route('citas.index')->with('success', 'Cita actualizada correctamente.');
    }

    public function destroy(Cita $cita)
    {
        try {
            $cita->delete();
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