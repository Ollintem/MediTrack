<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Consulta;
use App\Models\Personal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Módulo de consultas del médico.
 *
 *  - Un médico solo ve las citas asignadas a él. El super admin ve todas.
 *  - Iniciar una consulta pone la cita "En curso" y el consultorio "Ocupado".
 *  - Finalizarla (o liberarla) devuelve el consultorio a "Disponible".
 *
 * Revisa los AJUSTES de abajo: dependen de cómo relaciona tu sistema a los usuarios con el personal.
 */
class ConsultaController extends Controller
{
    // ------------------------------------------------------------------ AJUSTES
    /** Roles (en minúsculas) que ven las citas de todos los médicos. */
    private const ROLES_ADMIN = ['super admin', 'superadmin', 'super administrador', 'administrador', 'admin'];

    /** Si tu super admin es siempre el usuario con este id, se reconoce aunque el rol no coincida. null = desactivar. */
    private const SUPERADMIN_ID = 1;

    /** El administrador también recibe los recordatorios de todas las citas (útil para probarlos). */
    private const ADMIN_RECIBE_RECORDATORIOS = true;
    // ----------------------------------------------------------------------------

    private const PENDIENTES = ['Pendiente', 'Confirmada'];

    private const COLORES = [
        'Pendiente'  => '#f59e0b',
        'Confirmada' => '#10b981',
        'En curso'   => '#0ea5e9',
        'Finalizada' => '#94a3b8',
        'Cancelada'  => '#f43f5e',
    ];

    private ?Personal $personalCache = null;
    private bool $personalCargado = false;

    // ================================================================= USUARIO
    private function esAdmin(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        if (self::SUPERADMIN_ID !== null && (int) $user->id === self::SUPERADMIN_ID) {
            return true;
        }

        foreach (['rol.nombre', 'role.nombre', 'rol', 'role', 'tipo', 'perfil', 'cargo'] as $campo) {
            $v = data_get($user, $campo);
            if (is_string($v) && $v !== '' && in_array(mb_strtolower(trim($v)), self::ROLES_ADMIN, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Registro de Personal (médico) que corresponde al usuario que inició sesión.
     * Ajusta esta función si tu relación usuario ↔ personal es distinta.
     */
    private function personalActual(): ?Personal
    {
        if ($this->personalCargado) {
            return $this->personalCache;
        }
        $this->personalCargado = true;

        $user = auth()->user();
        if (!$user) {
            return null;
        }

        try {
            // 1) La tabla users tiene personal_id
            if (!empty($user->personal_id)) {
                return $this->personalCache = Personal::find($user->personal_id);
            }
            // 2) El modelo User tiene una relación personal()
            if (method_exists($user, 'personal') && $user->personal instanceof Personal) {
                return $this->personalCache = $user->personal;
            }
            // 3) La tabla personal tiene user_id
            return $this->personalCache = Personal::where('user_id', $user->id)->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function nombre($m): string
    {
        return (string) (data_get($m, 'nombre_completo') ?: trim(
            data_get($m, 'nombre', '') . ' ' . (data_get($m, 'apellidos') ?? data_get($m, 'apellido_paterno') ?? '')
        ));
    }

    /** Citas que el usuario actual tiene permitido ver. */
    private function citasVisibles(?int $personalFiltro = null)
    {
        $q = Cita::with(['paciente', 'personal', 'consultorio']);

        if ($this->esAdmin()) {
            return $personalFiltro ? $q->where('personal_id', $personalFiltro) : $q;
        }

        $personal = $this->personalActual();

        return $personal ? $q->where('personal_id', $personal->id) : $q->whereRaw('1 = 0');
    }

    private function puedeVer(Cita $cita): bool
    {
        if ($this->esAdmin()) {
            return true;
        }
        $personal = $this->personalActual();

        return $personal && (int) $cita->personal_id === (int) $personal->id;
    }

    // ================================================================= FORMATO
    /** Datos de una cita en el formato que usa la vista, el calendario y los recordatorios. */
    private function dto(Cita $c): array
    {
        $inicio = Carbon::parse(Carbon::parse($c->fecha)->format('Y-m-d') . ' ' . Carbon::parse($c->hora)->format('H:i:s'));
        $fin = $inicio->copy()->addMinutes((int) ($c->duracion_min ?? 30));

        return [
            'cita_id'          => $c->id,
            'paciente_id'      => $c->paciente_id,
            'paciente_nombre'  => $this->nombre($c->paciente) ?: 'Paciente',
            'personal_id'      => $c->personal_id,
            'personal_nombre'  => $this->nombre($c->personal) ?: '—',
            'consultorio_id'   => $c->consultorio_id,
            'consultorio_nombre' => data_get($c, 'consultorio.nombre'),
            'consultorio_piso' => data_get($c, 'consultorio.piso'),
            'motivo'           => $c->motivo ?? '',
            'tipo_consulta'    => $c->tipo_consulta ?? 'Primera Vez',
            'estado'           => $c->estado ?? 'Pendiente',
            'fecha'            => $inicio->format('Y-m-d'),
            'hora'             => $inicio->format('H:i'),
            'hora_fin'         => $fin->format('H:i'),
            'duracion_min'     => (int) ($c->duracion_min ?? 30),
            'inicio_iso'       => $inicio->format('Y-m-d\TH:i:s'),
            'fin_iso'          => $fin->format('Y-m-d\TH:i:s'),
            // minutos que faltan para la cita (negativo = ya pasó la hora)
            'faltan_min'       => (int) ceil(($inicio->timestamp - now()->timestamp) / 60),
        ];
    }

    private function respuestaError(string $mensaje, int $codigo = 422)
    {
        return response()->json(['status' => 'error', 'message' => $mensaje], $codigo);
    }

    // ================================================================= CONSULTORIO
    private function ocuparConsultorio(Cita $cita): void
    {
        $cons = $cita->consultorio;
        if ($cons && $cons->estado !== 'Mantenimiento' && $cons->estado !== 'Ocupado') {
            $cons->forceFill(['estado' => 'Ocupado'])->save();
        }
    }

    private function liberarConsultorio(Cita $cita): void
    {
        $cons = $cita->consultorio;
        if (!$cons || $cons->estado !== 'Ocupado') {
            return;
        }

        // Solo se libera si no hay otra consulta en curso en el mismo consultorio
        $hayOtra = Cita::where('consultorio_id', $cons->id)
            ->where('estado', 'En curso')
            ->where('id', '!=', $cita->id)
            ->exists();

        if (!$hayOtra) {
            $cons->forceFill(['estado' => 'Disponible'])->save();
        }
    }

    private function recetaUrl(?Consulta $consulta): ?string
    {
        if (!$consulta) {
            return null;
        }
        // Tu módulo de recetas crea las recetas desde su pantalla principal (modal), no desde una página "create":
        // se envía a recetas.index con la consulta, para que esa pantalla pueda abrir la receta con los datos.
        return Route::has('recetas.index')
            ? route('recetas.index', ['consulta_id' => $consulta->id])
            : null;
    }

    // ================================================================= PÁGINAS Y DATOS
    public function index()
    {
        $esAdmin = $this->esAdmin();
        $personal = $this->personalActual();

        $doctores = $esAdmin
            ? Personal::all()
                ->map(fn ($m) => ['id' => $m->id, 'nombre' => $this->nombre($m)])
                ->sortBy('nombre', SORT_NATURAL | SORT_FLAG_CASE)->values()
            : collect();

        return view('consultas.index', [
            'esAdmin'      => $esAdmin,
            'doctores'     => $doctores,
            'nombreDoctor' => $personal ? $this->nombre($personal) : null,
        ]);
    }

    /** Eventos del calendario (FullCalendar). */
    public function citas(Request $request)
    {
        $q = $this->citasVisibles($request->integer('personal_id') ?: null);

        if ($request->filled('start')) {
            $q->whereDate('fecha', '>=', Carbon::parse($request->start)->toDateString());
        }
        if ($request->filled('end')) {
            $q->whereDate('fecha', '<=', Carbon::parse($request->end)->toDateString());
        }

        $eventos = $q->orderBy('fecha')->orderBy('hora')->get()->map(function ($c) {
            $d = $this->dto($c);

            return [
                'id'            => $c->id,
                'title'         => $d['paciente_nombre'],
                'start'         => $d['inicio_iso'],
                'end'           => $d['fin_iso'],
                'color'         => self::COLORES[$d['estado']] ?? '#0d9488',
                'classNames'    => $d['estado'] === 'Cancelada' ? ['fc-cancelada'] : [],
                'extendedProps' => $d,
            ];
        });

        return response()->json($eventos);
    }

    /**
     * Resumen del día, próximas citas y consulta en curso.
     * Lo usan el panel del médico y los recordatorios (?recordatorios=1).
     */
    public function agenda(Request $request)
    {
        $ahora = now();

        if ($request->boolean('recordatorios')) {
            // Recordatorios: solo las citas del propio médico
            $personal = $this->personalActual();
            $q = Cita::with(['paciente', 'personal', 'consultorio']);

            if ($personal) {
                $q->where('personal_id', $personal->id);
            } elseif (!($this->esAdmin() && self::ADMIN_RECIBE_RECORDATORIOS)) {
                $q->whereRaw('1 = 0');
            }
        } else {
            $q = $this->citasVisibles($request->integer('personal_id') ?: null);
        }

        $hoy = $ahora->toDateString();
        $citasHoy = (clone $q)->whereDate('fecha', $hoy)->get();

        $proximas = (clone $q)
            ->whereIn('estado', ['Pendiente', 'Confirmada', 'En curso'])
            ->whereDate('fecha', '>=', $hoy)
            ->whereDate('fecha', '<=', $ahora->copy()->addDays(7)->toDateString())
            ->orderBy('fecha')->orderBy('hora')
            ->limit(30)->get()
            ->map(fn ($c) => $this->dto($c))->values();

        $enCurso = (clone $q)->where('estado', 'En curso')->orderByDesc('updated_at')->first();

        return response()->json([
            'ahora'    => $ahora->format('Y-m-d\TH:i:s'),
            'es_admin' => $this->esAdmin(),
            'resumen'  => [
                'hoy'         => $citasHoy->where('estado', '!=', 'Cancelada')->count(),
                'por_atender' => $citasHoy->whereIn('estado', self::PENDIENTES)->count(),
                'atendidas'   => $citasHoy->where('estado', 'Finalizada')->count(),
                'en_curso'    => $citasHoy->where('estado', 'En curso')->count(),
            ],
            'proxima'  => $proximas->first(fn ($c) => in_array($c['estado'], self::PENDIENTES, true) && $c['faltan_min'] > 0),
            'proximas' => $proximas,
            'en_curso' => $enCurso ? $this->dto($enCurso) : null,
        ]);
    }

    /** Datos para atender una cita: consulta guardada (borrador), historial y datos del paciente. */
    public function detalle(Cita $cita)
    {
        abort_unless($this->puedeVer($cita), 403);

        $cita->load(['paciente', 'personal', 'consultorio']);
        $consulta = Consulta::where('cita_id', $cita->id)->first();

        $historial = Consulta::where('paciente_id', $cita->paciente_id)
            ->where('cita_id', '!=', $cita->id)
            ->orderByDesc('id')->limit(5)->get()
            ->map(fn ($c) => [
                'id'      => $c->id,
                'fecha'   => $c->fecha ? Carbon::parse($c->fecha)->format('Y-m-d') : null,
                'resumen' => Str::limit((string) ($c->notas_generales ?? ''), 140),
            ])->values();

        $p = $cita->paciente;
        $edad = null;
        if ($nac = data_get($p, 'fecha_nacimiento')) {
            try {
                $edad = Carbon::parse($nac)->age;
            } catch (\Throwable $e) {
            }
        }

        // Momento en que se inició la consulta (para el cronómetro)
        $inicio = $consulta?->created_at ?? $cita->updated_at;
        $segundos = ($cita->estado === 'En curso' && $inicio)
            ? max(0, now()->timestamp - Carbon::parse($inicio)->timestamp)
            : 0;

        return response()->json([
            'cita'      => $this->dto($cita),
            'paciente'  => [
                'nombre'   => $this->nombre($p),
                'edad'     => $edad ?? data_get($p, 'edad'),
                'sexo'     => data_get($p, 'sexo'),
                'telefono' => data_get($p, 'telefono'),
                'alergias' => data_get($p, 'alergias'),
            ],
            'consulta'  => $consulta ? collect($consulta->toArray())->only([
                'exploracion_fisica', 'cabeza_cuello', 'torax', 'abdomen',
                'extremidades', 'piel_faneras', 'neurologico', 'notas_generales',
            ]) : null,
            'historial' => $historial,
            'segundos'  => $segundos,
            'receta_url' => $this->recetaUrl($consulta),
        ]);
    }

    // ================================================================= ACCIONES
    /** Inicia la consulta: la cita pasa a "En curso" y el consultorio a "Ocupado". */
    public function iniciar(Cita $cita)
    {
        abort_unless($this->puedeVer($cita), 403);

        if (in_array($cita->estado, ['Finalizada', 'Cancelada'], true)) {
            return $this->respuestaError('Esta cita ya está ' . mb_strtolower($cita->estado) . '.');
        }

        if ($cita->estado !== 'En curso') {
            $otra = Cita::with('paciente')
                ->where('personal_id', $cita->personal_id)
                ->where('estado', 'En curso')
                ->where('id', '!=', $cita->id)->first();

            if ($otra) {
                return $this->respuestaError(
                    'Ya estás atendiendo a ' . ($this->nombre($otra->paciente) ?: 'otro paciente') . '. Finaliza esa consulta antes de iniciar otra.',
                    409
                );
            }

            $cons = $cita->consultorio;
            if ($cons && $cons->estado === 'Mantenimiento') {
                return $this->respuestaError('El consultorio ' . $cons->nombre . ' está en mantenimiento.', 409);
            }
            if ($cons && Cita::where('consultorio_id', $cons->id)->where('estado', 'En curso')->where('id', '!=', $cita->id)->exists()) {
                return $this->respuestaError('El consultorio ' . $cons->nombre . ' ya está en uso por otra consulta.', 409);
            }
        }

        DB::transaction(function () use ($cita) {
            $cita->forceFill(['estado' => 'En curso'])->save();
            $this->ocuparConsultorio($cita);
        });

        $nombreCons = data_get($cita, 'consultorio.nombre');

        return response()->json([
            'status'  => 'success',
            'message' => 'Consulta iniciada.' . ($nombreCons ? " El consultorio $nombreCons figura como ocupado." : ''),
        ]);
    }

    /** Cancela la atención en curso sin finalizar: libera el consultorio y la cita vuelve a "Confirmada". */
    public function liberar(Cita $cita)
    {
        abort_unless($this->puedeVer($cita), 403);

        if ($cita->estado !== 'En curso') {
            return $this->respuestaError('La cita no está en curso.');
        }

        DB::transaction(function () use ($cita) {
            $cita->forceFill(['estado' => 'Confirmada'])->save();
            $this->liberarConsultorio($cita);
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Consultorio liberado. La cita volvió a Confirmada.',
        ]);
    }

    /** Guarda la consulta como borrador (finalizar = false) o la finaliza (finalizar = true). */
    public function store(Request $request)
    {
        $data = $request->validate([
            'cita_id'            => 'required|integer',
            'finalizar'          => 'nullable|boolean',
            'exploracion_fisica' => 'nullable|string|max:5000',
            'cabeza_cuello'      => 'nullable|string|max:2000',
            'torax'              => 'nullable|string|max:2000',
            'abdomen'            => 'nullable|string|max:2000',
            'extremidades'       => 'nullable|string|max:2000',
            'piel_faneras'       => 'nullable|string|max:2000',
            'neurologico'        => 'nullable|string|max:2000',
            'notas_generales'    => 'nullable|string|max:5000',
        ]);

        $finalizar = $request->boolean('finalizar', true);

        if ($finalizar && blank($data['notas_generales'] ?? null)) {
            return $this->respuestaError('Escribe las notas generales / diagnóstico para finalizar la consulta.');
        }

        $cita = Cita::with('consultorio')->findOrFail($data['cita_id']);
        abort_unless($this->puedeVer($cita), 403);

        if (in_array($cita->estado, ['Finalizada', 'Cancelada'], true)) {
            return $this->respuestaError('Esta cita ya está ' . mb_strtolower($cita->estado) . '.');
        }

        $campos = ['exploracion_fisica', 'cabeza_cuello', 'torax', 'abdomen', 'extremidades', 'piel_faneras', 'neurologico', 'notas_generales'];
        $valores = [];
        foreach ($campos as $campo) {
            $valores[$campo] = $data[$campo] ?? '';   // '' para columnas NOT NULL
        }

        $consulta = DB::transaction(function () use ($cita, $valores, $finalizar) {
            $consulta = Consulta::firstOrNew(['cita_id' => $cita->id]);
            if (!$consulta->exists) {
                $consulta->fecha = now()->toDateString();
            }

            $consulta->forceFill($valores + [
                'paciente_id' => $cita->paciente_id,
                'personal_id' => $cita->personal_id,
                'estado'      => $finalizar ? 'Finalizada' : 'En curso',
            ])->save();

            if ($finalizar) {
                $cita->forceFill(['estado' => 'Finalizada'])->save();
                $this->liberarConsultorio($cita);
            } elseif ($cita->estado !== 'En curso') {
                $cita->forceFill(['estado' => 'En curso'])->save();
                $this->ocuparConsultorio($cita);
            }

            return $consulta;
        });

        return response()->json([
            'status'             => 'success',
            'finalizada'         => $finalizar,
            'consulta_id'        => $consulta->id,
            'message'            => $finalizar ? 'Consulta finalizada correctamente.' : 'Borrador guardado.',
            'redireccion_receta' => $finalizar ? $this->recetaUrl($consulta) : null,
        ]);
    }
}