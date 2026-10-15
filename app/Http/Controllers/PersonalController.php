<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Cita;
use App\Models\Personal;
use App\Models\Rol;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

/**
 * Personal de la clínica: plantilla con gafete, alta y edición en la misma vista (respuestas JSON),
 * acceso al sistema y datos de nómina. La nómina en sí vive en NominaController.
 */
class PersonalController extends Controller
{
    /** Tipos de pago válidos para la nómina. */
    private const TIPOS_PAGO = ['SEMANAL', 'QUINCENAL', 'MENSUAL'];

    private const TURNOS = ['Mañana', 'Tarde', 'Completo', 'Noche'];

    private const DOMINIO = 'meditrack.com';

    public function index()
    {
        $hoy = Carbon::today();

        // Citas de hoy y consultas atendidas en el mes, por persona (una consulta para cada cosa)
        $citasHoy = Cita::whereDate('fecha', $hoy)->where('estado', '!=', 'Cancelada')
            ->selectRaw('personal_id, count(*) as n')->groupBy('personal_id')->pluck('n', 'personal_id');
        $atendidasMes = Cita::where('estado', 'Finalizada')
            ->whereDate('fecha', '>=', $hoy->copy()->startOfMonth())->whereDate('fecha', '<=', $hoy)
            ->selectRaw('personal_id, count(*) as n')->groupBy('personal_id')->pluck('n', 'personal_id');

        $lista = Personal::with('usuario.rol')->get()
            ->map(fn ($p) => $this->presentar($p, $citasHoy, $atendidasMes))
            ->sortBy('nombre', SORT_NATURAL | SORT_FLAG_CASE)->values();

        $user = auth()->user();

        $datosPersonal = [
            'personal' => $lista,
            'roles'    => Rol::orderBy('nombre')->get(['id', 'nombre'])->map(fn ($r) => ['id' => (string) $r->id, 'nombre' => $r->nombre])->values(),
            'dominio'  => self::DOMINIO,
            'clinica'  => (string) (DB::table('clinicas')->value('nombre') ?? 'MediTrack'),
            'puede'    => [
                'crear'    => $user ? (bool) $user->tienePermiso('Personal', 'crear') : false,
                'editar'   => $user ? (bool) $user->tienePermiso('Personal', 'editar') : false,
                'eliminar' => $user ? (bool) $user->tienePermiso('Personal', 'eliminar') : false,
                'roles'    => $user ? (bool) $user->tienePermiso('Roles', 'ver') : false,
            ],
            'rutas'    => [
                'base'   => url('personal'),
                'roles'  => Route::has('roles.index') ? route('roles.index') : null,
                'nomina' => Route::has('personal.nomina.index') ? route('personal.nomina.index') : null,
            ],
        ];

        return view('personal.index', compact('datosPersonal'));
    }

    /** El alta se hace en la misma plantilla: se abre el gafete en modo captura. */
    public function create()
    {
        return redirect()->route('personal.index', ['nuevo' => 1]);
    }

    /** La edición también: se abre el gafete de esa persona volteado. */
    public function edit($id)
    {
        $personal = Personal::findOrFail($id);

        return redirect()->route('personal.index', ['p' => $personal->id, 'editar' => 1]);
    }

    public function store(Request $request)
    {
        $requiereAcceso = $request->boolean('requiere_acceso');
        $emailCompleto = null;

        // 1. Credenciales (solo si tendrá acceso al sistema)
        if ($requiereAcceso) {
            $emailCompleto = strtolower(trim((string) $request->username)) . '@' . self::DOMINIO;
            $request->merge(['email' => $emailCompleto]);

            $request->validate([
                'username' => 'required|string|alpha_dash|min:3|max:50',
                'email'    => 'required|email|unique:users,email',
                'password' => 'required|size:8|confirmed',
            ], $this->mensajes());
        }

        // 2. Ficha del personal y datos de nómina
        $request->validate($this->reglas(), $this->mensajes());

        $rol = Rol::findOrFail($request->rol_id);
        $nombreCompleto = $this->nombreCompleto($request);
        $datosNomina = $this->datosNomina($request);
        $personalId = null;

        DB::transaction(function () use ($request, $emailCompleto, $nombreCompleto, $rol, $requiereAcceso, $datosNomina, &$personalId) {
            $clinicaId = DB::table('clinicas')->value('id') ?? 1;
            $userId = null;

            if ($requiereAcceso) {
                $usuario = User::create([
                    'name'     => $nombreCompleto,
                    'email'    => $emailCompleto,
                    'rol_id'   => $rol->id,
                    'password' => Hash::make($request->password),
                ]);
                $userId = $usuario->id;
            }

            $personal = Personal::create([
                'user_id'                => $userId,
                'clinica_id'             => $clinicaId,
                'nombre_completo'        => $nombreCompleto,
                'rut'                    => $this->cedula($request),
                'telefono'               => $request->telefono ?: null,
                'especialidad_principal' => $this->especialidad($request, $rol),
                'turno'                  => $request->turno,
                'estado'                 => $request->input('estado', 'Activo'),
            ]);
            $personal->forceFill(['direccion' => $this->texto($request->direccion, 255)] + $datosNomina)->save();
            $personalId = $personal->id;

            Bitacora::registrar('Personal', 'Crear',
                "Se dio de alta al personal: {$nombreCompleto}" . ($requiereAcceso ? ' (Con acceso a sistema)' : ' (Sin acceso a sistema)'),
                [
                    'personal_id'     => $personal->id,
                    'user_id'         => $userId,
                    'correo'          => $emailCompleto,
                    'rol'             => $rol->nombre,
                    'requiere_acceso' => $requiereAcceso,
                    'salario_diario'  => $datosNomina['salario_diario'],
                    'tipo_pago'       => $datosNomina['tipo_pago'],
                ]
            );
        });

        if ($request->expectsJson()) {
            return response()->json([
                'ok'       => true,
                'mensaje'  => 'Personal registrado correctamente.',
                'personal' => $this->presentar(Personal::with('usuario.rol')->find($personalId)),
            ], 201);
        }

        return redirect()->route('personal.index', ['p' => $personalId])->with('success', 'Personal registrado correctamente.');
    }

    public function update(Request $request, $id)
    {
        $personal = Personal::findOrFail($id);
        $requiereAcceso = $request->boolean('requiere_acceso');
        $usuario = $personal->user_id ? User::find($personal->user_id) : null;
        $emailCompleto = null;

        // 1. Credenciales: contraseña obligatoria solo si antes no tenía cuenta
        if ($requiereAcceso) {
            $emailCompleto = strtolower(trim((string) $request->username)) . '@' . self::DOMINIO;
            $request->merge(['email' => $emailCompleto]);

            $request->validate([
                'username' => 'required|string|alpha_dash|min:3|max:50',
                'email'    => 'required|email|unique:users,email,' . ($usuario ? $usuario->id : 'NULL'),
                'password' => ($usuario ? 'nullable' : 'required') . '|size:8|confirmed',
            ], $this->mensajes());
        }

        $request->validate($this->reglas($personal->id), $this->mensajes());

        $rol = Rol::findOrFail($request->rol_id);
        $nombreCompleto = $this->nombreCompleto($request);
        $datosNomina = $this->datosNomina($request);
        $salarioAnterior = (float) ($personal->salario_diario ?? 0);

        DB::transaction(function () use ($request, $personal, $usuario, $emailCompleto, $nombreCompleto, $rol, $requiereAcceso, $datosNomina, $salarioAnterior) {

            if ($requiereAcceso) {
                if ($usuario) {
                    $usuario->name   = $nombreCompleto;
                    $usuario->email  = $emailCompleto;
                    $usuario->rol_id = $rol->id;
                    if ($request->filled('password')) {
                        $usuario->password = Hash::make($request->password);
                    }
                    $usuario->save();
                } else {
                    $nuevo = User::create([
                        'name'     => $nombreCompleto,
                        'email'    => $emailCompleto,
                        'rol_id'   => $rol->id,
                        'password' => Hash::make($request->password),
                    ]);
                    $personal->user_id = $nuevo->id;
                }
            } elseif ($usuario) {
                // Se quitó el acceso: se borra su cuenta y queda solo el expediente del personal
                $personal->user_id = null;
                $personal->save();
                $usuario->delete();
            }

            $personal->update([
                'nombre_completo'        => $nombreCompleto,
                'rut'                    => $this->cedula($request),
                'telefono'               => $request->telefono ?: null,
                'especialidad_principal' => $this->especialidad($request, $rol),
                'turno'                  => $request->turno,
                'estado'                 => $request->input('estado', $personal->estado ?: 'Activo'),
            ]);
            $personal->forceFill(['direccion' => $this->texto($request->direccion, 255)] + $datosNomina)->save();

            Bitacora::registrar('Personal', 'Editar',
                "Se actualizó la información del personal: {$nombreCompleto}",
                [
                    'personal_id'       => $personal->id,
                    'correo'            => $emailCompleto,
                    'rol'               => $rol->nombre,
                    'requiere_acceso'   => $requiereAcceso,
                    'password_cambiado' => $request->filled('password'),
                    'salario_anterior'  => $salarioAnterior,
                    'salario_diario'    => $datosNomina['salario_diario'],
                    'tipo_pago'         => $datosNomina['tipo_pago'],
                ]
            );
        });

        if ($request->expectsJson()) {
            return response()->json([
                'ok'       => true,
                'mensaje'  => 'Personal actualizado correctamente.',
                'personal' => $this->presentar(Personal::with('usuario.rol')->find($personal->id)),
            ]);
        }

        return redirect()->route('personal.index', ['p' => $personal->id])->with('success', 'Personal actualizado correctamente.');
    }

    public function destroy($id)
    {
        $personal = Personal::where('id', $id)->orWhere('user_id', $id)->first();

        if ($personal) {
            $userId = $personal->user_id;
            $personalId = $personal->id;
            $nombreEliminado = $personal->nombre_completo;

            if ($userId && (int) $userId === (int) auth()->id()) {
                $mensaje = 'No puedes eliminar tu propia cuenta.';

                return request()->expectsJson()
                    ? response()->json(['success' => false, 'message' => $mensaje], 422)
                    : redirect()->route('personal.index')->with('error', $mensaje);
            }

            DB::transaction(function () use ($personalId, $userId) {
                DB::table('personals')->where('id', $personalId)->delete();

                if ($userId) {
                    DB::table('users')->where('id', $userId)->delete();
                }
            });

            Bitacora::registrar('Personal', 'Eliminar',
                "Se eliminó del sistema al miembro del personal: {$nombreEliminado}",
                ['personal_id' => $personalId, 'user_id' => $userId, 'nombre' => $nombreEliminado]
            );

            if (request()->expectsJson()) {
                return response()->json(['success' => true, 'message' => 'El miembro del personal ha sido eliminado correctamente.']);
            }

            return redirect()->route('personal.index')->with('success', 'Personal eliminado correctamente.');
        }

        if (request()->expectsJson()) {
            return response()->json(['success' => false, 'message' => 'No se encontró el registro a eliminar.'], 404);
        }

        return redirect()->route('personal.index')->with('error', 'No se encontró el registro a eliminar.');
    }

    // =====================================================================
    //  AYUDANTES
    // =====================================================================

    /** Datos de una persona como los usa la vista (gafete y lista). */
    private function presentar(Personal $p, $citasHoy = null, $atendidasMes = null): array
    {
        $usuario = $p->usuario;
        $rol = $usuario ? $usuario->rol : null;
        $rolNombre = $rol ? $rol->nombre : null;
        $nombre = trim((string) $p->nombre_completo) ?: 'Sin nombre';
        [$pila, $apellido] = $this->separarNombre($nombre, $usuario);
        $especialidad = trim((string) $p->especialidad_principal);
        $email = $usuario ? (string) $usuario->email : null;

        return [
            'id'           => $p->id,
            'nombre'       => $nombre,
            'pila'         => $pila,
            'apellido'     => $apellido,
            'rol_id'       => $rol ? (string) $rol->id : '',
            'rol'          => $rolNombre,
            // Antes se guardaba el nombre del rol como especialidad; en ese caso no se repite
            'especialidad' => $especialidad !== '' && mb_strtolower($especialidad) !== mb_strtolower((string) $rolNombre) ? $especialidad : null,
            'cedula'       => $p->rut ?: ($p->numero_registro ?: null),
            'telefono'     => $p->telefono ?: null,
            'direccion'    => $p->direccion ?: null,
            'turno'        => in_array($p->turno, self::TURNOS, true) ? $p->turno : 'Completo',
            'estado'       => $p->estado ?: 'Activo',
            'activo'       => mb_strtolower((string) ($p->estado ?: 'Activo')) === 'activo',
            'acceso'       => (bool) $usuario,
            'email'        => $email ?: null,
            'username'     => $email && str_ends_with($email, '@' . self::DOMINIO) ? substr($email, 0, -strlen('@' . self::DOMINIO)) : ($email ? strstr($email, '@', true) : ''),
            'salario'      => (float) ($p->salario_diario ?? 0),
            'tipo_pago'    => in_array($p->tipo_pago, self::TIPOS_PAGO, true) ? $p->tipo_pago : 'QUINCENAL',
            'folio'        => 'MT-' . str_pad((string) ($p->user_id ?: $p->id), 4, '0', STR_PAD_LEFT),
            'desde'        => $p->created_at ? $p->created_at->locale('es')->translatedFormat('M Y') : null,
            'soyYo'        => $usuario && (int) $usuario->id === (int) auth()->id(),
            'citasHoy'     => $citasHoy ? (int) ($citasHoy[$p->id] ?? 0) : 0,
            'atendidasMes' => $atendidasMes ? (int) ($atendidasMes[$p->id] ?? 0) : 0,
            'nomina'       => Route::has('personal.nomina.empleado') ? route('personal.nomina.empleado', $p->id) : null,
        ];
    }

    /** Separa nombre(s) y apellidos para el formulario: los dos últimos términos son apellidos. */
    private function separarNombre(string $nombre, $usuario): array
    {
        $pila = trim((string) data_get($usuario, 'nombre'));
        $apellido = trim((string) data_get($usuario, 'apellido'));
        if ($pila !== '' && mb_strtoupper(trim($pila . ' ' . $apellido)) === mb_strtoupper($nombre)) {
            return [$pila, $apellido];
        }

        $partes = preg_split('/\s+/', $nombre) ?: [];
        // Quita títulos al inicio (DR., DRA., ENF., LIC.) del cálculo, pero los deja en el nombre
        $titulo = [];
        while ($partes && preg_match('/^(DR|DRA|ENF|LIC|ING|MTRO|MTRA)\.?$/i', $partes[0])) {
            $titulo[] = array_shift($partes);
        }
        $n = count($partes);
        $corte = $n >= 3 ? $n - 2 : max(1, $n - 1);

        return [
            trim(implode(' ', array_merge($titulo, array_slice($partes, 0, $corte)))),
            trim(implode(' ', array_slice($partes, $corte))),
        ];
    }

    private function reglas(?int $ignorarId = null): array
    {
        return [
            'nombre'         => 'required|string|max:80',
            'apellido'       => 'nullable|string|max:80',
            'rol_id'         => 'required|exists:roles,id',
            'rut'            => 'nullable|string|max:20|unique:personals,rut' . ($ignorarId ? ',' . $ignorarId : ''),
            'telefono'       => 'nullable|digits:10',
            'especialidad'   => 'nullable|string|max:100',
            'direccion'      => 'nullable|string|max:255',
            'turno'          => 'required|in:' . implode(',', self::TURNOS),
            'estado'         => 'nullable|in:Activo,Inactivo',
            'salario_diario' => 'nullable|numeric|min:0|max:999999',
            'tipo_pago'      => 'nullable|in:' . implode(',', self::TIPOS_PAGO),
        ];
    }

    private function mensajes(): array
    {
        return [
            'nombre.required'        => 'Escribe el nombre.',
            'rol_id.required'        => 'Elige el cargo o rol.',
            'rol_id.exists'          => 'Ese rol ya no existe.',
            'rut.unique'             => 'Esa cédula ya está registrada en otra persona.',
            'rut.max'                => 'La cédula admite hasta 20 caracteres.',
            'telefono.digits'        => 'El teléfono debe tener 10 dígitos.',
            'turno.required'         => 'Elige el turno.',
            'turno.in'               => 'Elige un turno válido.',
            'salario_diario.numeric' => 'El salario diario debe ser un número.',
            'salario_diario.min'     => 'El salario diario no puede ser negativo.',
            'tipo_pago.in'           => 'El tipo de pago debe ser semanal, quincenal o mensual.',
            'username.required'      => 'Escribe el usuario para iniciar sesión.',
            'username.alpha_dash'    => 'El usuario solo admite letras, números, guion y guion bajo.',
            'username.min'           => 'El usuario debe tener al menos 3 caracteres.',
            'email.unique'           => 'Ese usuario ya está en uso.',
            'password.required'      => 'Escribe una contraseña de 8 caracteres.',
            'password.size'          => 'La contraseña debe tener exactamente 8 caracteres.',
            'password.confirmed'     => 'Las contraseñas no coinciden.',
        ];
    }

    private function nombreCompleto(Request $request): string
    {
        $nombre = trim(preg_replace('/\s+/', ' ', $request->nombre . ' ' . $request->apellido));

        return mb_strtoupper($nombre, 'UTF-8');
    }

    private function cedula(Request $request): ?string
    {
        $cedula = strtoupper(preg_replace('/\s+/', '', (string) $request->rut));

        return $cedula !== '' ? $cedula : null;
    }

    /** La especialidad capturada; si no hay, el nombre del rol (como se guardaba antes). */
    private function especialidad(Request $request, Rol $rol): string
    {
        $esp = $this->texto($request->especialidad, 100);

        return $esp ?: $rol->nombre;
    }

    private function texto($valor, int $max): ?string
    {
        $t = trim(preg_replace('/\s+/', ' ', (string) $valor));

        return $t !== '' ? mb_substr(mb_strtoupper($t, 'UTF-8'), 0, $max) : null;
    }

    /** Salario diario y tipo de pago del bloque "Datos de nómina". */
    private function datosNomina(Request $request): array
    {
        $tipo = strtoupper((string) $request->input('tipo_pago', 'QUINCENAL'));

        return [
            'salario_diario' => round((float) ($request->input('salario_diario') ?: 0), 2),
            'tipo_pago'      => in_array($tipo, self::TIPOS_PAGO, true) ? $tipo : 'QUINCENAL',
        ];
    }
}