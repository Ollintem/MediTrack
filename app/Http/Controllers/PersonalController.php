<?php

namespace App\Http\Controllers;

use App\Models\Personal;
use App\Models\User;
use App\Models\Rol;
use App\Models\Modulo;
use App\Models\Bitacora;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PersonalController extends Controller
{
    /** Tipos de pago válidos para la nómina. */
    private const TIPOS_PAGO = ['SEMANAL', 'QUINCENAL', 'MENSUAL'];

    public function index()
    {
        $personal = Personal::with('usuario')->get();
        return view('personal.index', compact('personal'));
    }

    public function create()
    {
        $roles = Rol::all();
        $modulos = Modulo::all();
        return view('personal.create', compact('roles', 'modulos'));
    }

    public function store(Request $request)
    {
        $requiereAcceso = $request->boolean('requiere_acceso');
        $emailCompleto = null;

        // 1. Validaciones condicionales si requiere acceso al sistema
        if ($requiereAcceso) {
            $emailCompleto = strtolower(trim($request->username)) . '@meditrack.com';
            $request->merge(['email' => $emailCompleto]);

            $request->validate([
                'username' => 'required|string|alpha_dash|max:50',
                'email'    => 'required|email|unique:users,email',
                'password' => 'required|size:8|confirmed',
            ], [
                'username.required' => 'Ingresa un nombre de usuario para el correo.',
                'email.unique'      => 'Este usuario de correo ya está registrado.',
                'password.size'     => 'La contraseña debe tener exactamente 8 caracteres.',
            ]);
        }

        // 2. Validaciones generales de la ficha de personal (incluye datos de nómina)
        $request->validate([
            'nombre'         => 'required|string|max:80',
            'apellido'       => 'required|string|max:80',
            'rol_id'         => 'required|exists:roles,id',
            'rut'            => 'nullable|unique:personals,rut',
            'turno'          => 'required|in:Mañana,Tarde,Completo,Noche',
            'salario_diario' => 'nullable|numeric|min:0|max:999999',
            'tipo_pago'      => 'nullable|in:' . implode(',', self::TIPOS_PAGO),
        ], [
            'rol_id.required'        => 'Debes seleccionar un rol para el sistema.',
            'salario_diario.numeric' => 'El salario diario debe ser un número.',
            'salario_diario.min'     => 'El salario diario no puede ser negativo.',
            'tipo_pago.in'           => 'El tipo de pago debe ser semanal, quincenal o mensual.',
        ]);

        $rol = Rol::findOrFail($request->rol_id);
        $nombreRol = $rol->nombre;
        $nombreCompleto = trim($request->nombre . ' ' . $request->apellido);
        $datosNomina = $this->datosNomina($request);

        DB::transaction(function () use ($request, $emailCompleto, $nombreCompleto, $nombreRol, $requiereAcceso, $datosNomina) {
            $clinicaId = DB::table('clinicas')->value('id') ?? 1;
            $userId = null;

            // 3. Crear credenciales de usuario SOLO si el switch está encendido
            if ($requiereAcceso) {
                $usuario = User::create([
                    'name'       => $nombreCompleto,
                    'nombre'     => $request->nombre,
                    'apellido'   => $request->apellido,
                    'email'      => $emailCompleto,
                    'rol_id'     => $request->rol_id,
                    'password'   => Hash::make($request->password), // Encriptación explícita para evitar texto plano
                    'clinica_id' => $clinicaId,
                ]);
                $userId = $usuario->id;
            }

            // 4. Crear registro de personal
            $personal = Personal::create([
                'user_id'                => $userId,
                'clinica_id'             => $clinicaId,
                'nombre_completo'        => $nombreCompleto,
                'rut'                    => $request->rut,
                'telefono'               => $request->telefono,
                'numero_registro'        => $request->numero_registro,
                'especialidad_principal' => $nombreRol,
                'turno'                  => $request->turno,
                'estado'                 => 'Activo',
            ]);

            // 4.1 Datos de nómina (forceFill: se guardan aunque no estén en $fillable del modelo)
            $personal->forceFill($datosNomina)->save();

            // 5. Registrar evento en Bitácora
            Bitacora::registrar(
                'Personal',
                'Crear',
                "Se dio de alta al personal: {$nombreCompleto}" . ($requiereAcceso ? ' (Con acceso a sistema)' : ' (Sin acceso a sistema)'),
                [
                    'personal_id'     => $personal->id,
                    'user_id'         => $userId,
                    'correo'          => $emailCompleto,
                    'rol'             => $nombreRol,
                    'requiere_acceso' => $requiereAcceso,
                    'salario_diario'  => $datosNomina['salario_diario'],
                    'tipo_pago'       => $datosNomina['tipo_pago'],
                ]
            );
        });

        return redirect()->route('personal.index')->with('success', 'Personal registrado correctamente.');
    }

    public function edit($id)
    {
        $personal = Personal::with('usuario')->findOrFail($id);
        $roles = Rol::all();
        $modulos = Modulo::all();

        return view('personal.edit', compact('personal', 'roles', 'modulos'));
    }

    public function update(Request $request, $id)
    {
        $personal = Personal::findOrFail($id);
        $requiereAcceso = $request->boolean('requiere_acceso');
        $usuario = $personal->user_id ? User::find($personal->user_id) : null;

        // 1. Validaciones base (incluye datos de nómina)
        $request->validate([
            'nombre_completo' => 'required|string|max:150',
            'rol_id'          => 'required|exists:roles,id',
            'salario_diario'  => 'nullable|numeric|min:0|max:999999',
            'tipo_pago'       => 'nullable|in:' . implode(',', self::TIPOS_PAGO),
        ], [
            'rol_id.required'        => 'Debes seleccionar un rol para el sistema.',
            'salario_diario.numeric' => 'El salario diario debe ser un número.',
            'salario_diario.min'     => 'El salario diario no puede ser negativo.',
            'tipo_pago.in'           => 'El tipo de pago debe ser semanal, quincenal o mensual.',
        ]);

        $emailCompleto = null;

        // 2. Validar credenciales si tiene acceso activado
        if ($requiereAcceso) {
            $emailCompleto = strtolower(trim($request->username)) . '@meditrack.com';
            $request->merge(['email' => $emailCompleto]);

            $request->validate([
                'username' => 'required|string|alpha_dash|max:50',
                'email'    => 'required|email|unique:users,email,' . ($usuario ? $usuario->id : 'NULL'),
                'password' => 'nullable|size:8|confirmed',
            ], [
                'password.size' => 'La contraseña debe tener exactamente 8 caracteres.',
                'email.unique'  => 'Este usuario de correo ya está en uso.',
            ]);
        }

        $rol = Rol::findOrFail($request->rol_id);
        $nombreRol = $rol->nombre;
        $datosNomina = $this->datosNomina($request);

        // Para la bitácora: salario anterior
        $salarioAnterior = (float) ($personal->salario_diario ?? 0);

        DB::transaction(function () use ($request, $personal, $usuario, $emailCompleto, $nombreRol, $requiereAcceso, $datosNomina, $salarioAnterior) {
            $clinicaId = DB::table('clinicas')->value('id') ?? 1;

            if ($requiereAcceso) {
                if ($usuario) {
                    // Actualizar credenciales existentes
                    $usuario->name   = $request->nombre_completo;
                    $usuario->email  = $emailCompleto;
                    $usuario->rol_id = $request->rol_id;
                    if ($request->filled('password')) {
                        $usuario->password = Hash::make($request->password);
                    }
                    $usuario->save();
                } else {
                    // Crear nuevo usuario si antes no tenía acceso
                    $partesNombre = explode(' ', $request->nombre_completo, 2);
                    $nuevoUsuario = User::create([
                        'name'       => $request->nombre_completo,
                        'nombre'     => $partesNombre[0],
                        'apellido'   => $partesNombre[1] ?? '',
                        'email'      => $emailCompleto,
                        'rol_id'     => $request->rol_id,
                        'password'   => Hash::make($request->password ?? '12345678'),
                        'clinica_id' => $clinicaId,
                    ]);
                    $personal->user_id = $nuevoUsuario->id;
                }
            } else {
                // Si apagan el switch y tenía usuario previo, lo eliminamos y desvinculamos
                if ($usuario) {
                    $personal->user_id = null;
                    $personal->save();
                    $usuario->delete();
                }
            }

            // Actualizar datos del personal
            $personal->update([
                'nombre_completo'        => $request->nombre_completo,
                'telefono'               => $request->telefono,
                'especialidad_principal' => $nombreRol,
            ]);

            // Datos de nómina (forceFill: se guardan aunque no estén en $fillable del modelo)
            $personal->forceFill($datosNomina)->save();

            // Registrar en la bitácora
            Bitacora::registrar(
                'Personal',
                'Editar',
                "Se actualizó la información del personal: {$request->nombre_completo}",
                [
                    'personal_id'       => $personal->id,
                    'correo'            => $emailCompleto,
                    'rol'               => $nombreRol,
                    'requiere_acceso'   => $requiereAcceso,
                    'password_cambiado' => $request->filled('password'),
                    'salario_anterior'  => $salarioAnterior,
                    'salario_diario'    => $datosNomina['salario_diario'],
                    'tipo_pago'         => $datosNomina['tipo_pago'],
                ]
            );
        });

        return redirect()->route('personal.index')->with('success', 'Personal actualizado correctamente.');
    }

    public function destroy($id)
    {
        $personal = Personal::where('id', $id)->orWhere('user_id', $id)->first();

        if ($personal) {
            $userId = $personal->user_id;
            $personalId = $personal->id;
            $nombreEliminado = $personal->nombre_completo;

            DB::transaction(function () use ($personalId, $userId) {
                DB::table('personals')->where('id', $personalId)->delete();

                if ($userId) {
                    DB::table('users')->where('id', $userId)->delete();
                }
            });

            Bitacora::registrar(
                'Personal',
                'Eliminar',
                "Se eliminó del sistema al miembro del personal: {$nombreEliminado}",
                [
                    'personal_id' => $personalId,
                    'user_id'     => $userId,
                    'nombre'      => $nombreEliminado
                ]
            );

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'El miembro del personal ha sido eliminado correctamente.'
                ], 200);
            }

            return redirect()->route('personal.index')->with('success', 'Personal eliminado correctamente.');
        }

        if (request()->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró el registro a eliminar.'
            ], 404);
        }

        return redirect()->route('personal.index')->with('error', 'No se encontró el registro a eliminar.');
    }

    /** Salario diario y tipo de pago que vienen del bloque "Datos de nómina". */
    private function datosNomina(Request $request): array
    {
        $tipo = strtoupper((string) $request->input('tipo_pago', 'QUINCENAL'));

        return [
            'salario_diario' => round((float) ($request->input('salario_diario') ?: 0), 2),
            'tipo_pago'      => in_array($tipo, self::TIPOS_PAGO, true) ? $tipo : 'QUINCENAL',
        ];
    }
}