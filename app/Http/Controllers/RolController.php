<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Modulo;
use App\Models\Permiso;
use App\Models\Personal;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Throwable;

/**
 * Roles y permisos: qué puede ver, crear, editar y eliminar cada cargo en cada módulo.
 * Alta y edición en la misma vista (respuestas JSON).
 */
class RolController extends Controller
{
    private const ACCIONES = ['ver', 'crear', 'editar', 'eliminar'];

    public function index()
    {
        // El permiso "Roles,ver" lo revisa la ruta (middleware permiso)
        Modulo::sincronizar();

        $modulos = $this->modulos();
        $personas = $this->personasPorRol();
        $roles = Rol::with('permisos')->orderBy('nombre')->get()
            ->map(fn ($r) => $this->presentar($r, $modulos, $personas))->values();

        $user = auth()->user();
        $datosRoles = [
            'roles'   => $roles,
            'modulos' => $modulos->values(),
            'miRolId' => $user->rol_id ? (int) $user->rol_id : null,
            'soyTotal' => $user->tieneAccesoTotal(),
            'puede'   => [
                'crear'    => (bool) $user->tienePermiso('Roles', 'crear'),
                'editar'   => (bool) $user->tienePermiso('Roles', 'editar'),
                'eliminar' => (bool) $user->tienePermiso('Roles', 'eliminar'),
            ],
            'rutas'   => [
                'base'     => url('roles'),
                'personal' => Route::has('personal.index') ? route('personal.index') : null,
            ],
        ];

        return view('personal.roles', compact('datosRoles'));
    }

    public function store(Request $request)
    {
        // El permiso "Roles,crear" lo revisa la ruta (middleware permiso)
        $request->merge(['nombre' => trim(preg_replace('/\s+/', ' ', (string) $request->nombre))]);
        $request->validate(['nombre' => 'required|string|max:100|unique:roles,nombre'], $this->mensajes());

        if ($motivo = $this->escalada($request, null)) {
            return $this->negado($request, $motivo);
        }

        try {
            $rol = DB::transaction(function () use ($request) {
                $rol = Rol::create(['nombre' => $request->nombre]);
                $this->guardarPermisos($rol, (array) $request->input('permisos', []));

                return $rol;
            });
            $this->bitacora('Crear', "Se creó el rol {$rol->nombre}", $rol);
        } catch (Throwable $e) {
            report($e);
            return $this->fallo($request, 'No se pudo crear el rol. Intenta de nuevo.');
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'mensaje' => 'Rol creado correctamente.', 'rol' => $this->presentarUno($rol)], 201);
        }

        return redirect()->route('roles.index')->with('success', 'Rol y permisos creados correctamente.');
    }

    public function update(Request $request, $id)
    {
        // El permiso "Roles,editar" lo revisa la ruta (middleware permiso)
        $rol = Rol::findOrFail($id);
        $request->merge(['nombre' => trim(preg_replace('/\s+/', ' ', (string) $request->nombre))]);
        $request->validate(['nombre' => 'required|string|max:100|unique:roles,nombre,' . $rol->id], $this->mensajes());

        if ($motivo = $this->escalada($request, $rol)) {
            return $this->negado($request, $motivo);
        }

        try {
            DB::transaction(function () use ($request, $rol) {
                $rol->update(['nombre' => $request->nombre]);
                Permiso::where('rol_id', $rol->id)->delete();
                $this->guardarPermisos($rol, (array) $request->input('permisos', []));
            });
            $this->bitacora('Editar', "Se actualizaron los permisos del rol {$rol->nombre}", $rol);
        } catch (Throwable $e) {
            report($e);
            return $this->fallo($request, 'No se pudo guardar el rol. Intenta de nuevo.');
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'mensaje' => 'Permisos guardados.', 'rol' => $this->presentarUno($rol->fresh())]);
        }

        return redirect()->route('roles.index')->with('success', 'Rol y permisos actualizados correctamente.');
    }

    public function destroy($id)
    {
        // El permiso "Roles,eliminar" lo revisa la ruta (middleware permiso)
        $rol = Rol::find($id);
        if (!$rol) {
            return response()->json(['ok' => false, 'success' => false, 'message' => 'El rol no existe o ya fue eliminado.'], 404);
        }

        if ((int) auth()->user()->rol_id === (int) $rol->id) {
            return response()->json(['ok' => false, 'success' => false, 'message' => 'No puedes eliminar tu propio rol.'], 409);
        }

        if ($rol->esTotal() && !auth()->user()->tieneAccesoTotal()) {
            return response()->json(['ok' => false, 'success' => false, 'message' => 'Solo un administrador puede eliminar un rol con acceso total.'], 403);
        }

        try {
            $nombre = $rol->nombre;
            $usuarios = User::where('rol_id', $rol->id)->count();
            $rol->delete();
            Bitacora::registrar('Roles', 'Eliminar', "Se eliminó el rol {$nombre}", ['rol_id' => (int) $id, 'usuarios_sin_rol' => $usuarios]);

            return response()->json(['ok' => true, 'success' => true, 'message' => 'El rol se eliminó correctamente.']);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['ok' => false, 'success' => false, 'message' => 'No se pudo eliminar el rol.'], 500);
        }
    }

    // =====================================================================
    //  AYUDANTES
    // =====================================================================

    /** Módulos con su grupo, ícono y descripción, en el orden del menú. */
    private function modulos()
    {
        $info = [];
        $orden = 0;
        foreach (Modulo::CATALOGO as $grupo => $lista) {
            foreach ($lista as $nombre => [$icono, $desc]) {
                $info[mb_strtolower($nombre)] = ['grupo' => $grupo, 'icono' => $icono, 'desc' => $desc, 'orden' => $orden++];
            }
        }

        return Modulo::all()->map(function ($m) use ($info) {
            $i = $info[mb_strtolower(trim($m->nombre))] ?? ['grupo' => 'Otros', 'icono' => 'bi-app', 'desc' => 'Módulo registrado en la base de datos', 'orden' => 999];

            return [
                'id'     => $m->id,
                'nombre' => $m->nombre,
                'grupo'  => $i['grupo'],
                'icono'  => $i['icono'],
                'desc'   => $i['desc'],
                'orden'  => $i['orden'],
            ];
        })->sortBy([['orden', 'asc'], ['nombre', 'asc']])->values();
    }

    /** Quién tiene cada rol: nombre del personal (o de la cuenta) por rol_id. */
    private function personasPorRol()
    {
        $personal = Personal::whereNotNull('user_id')->pluck('id', 'user_id');
        $nombres = Personal::whereNotNull('user_id')->pluck('nombre_completo', 'user_id');

        return User::whereNotNull('rol_id')->orderBy('name')->get(['id', 'name', 'rol_id'])
            ->groupBy('rol_id')
            ->map(fn ($g) => $g->map(fn ($u) => [
                'nombre'      => (string) ($nombres[$u->id] ?? $u->name),
                'personal_id' => $personal[$u->id] ?? null,
            ])->values());
    }

    private function presentar(Rol $rol, $modulos, $personas): array
    {
        $permisos = [];
        foreach ($modulos as $m) {
            $p = $rol->permisos->firstWhere('modulo_id', $m['id']);
            $permisos[$m['id']] = [
                'ver'      => (bool) ($p->puede_ver ?? false),
                'crear'    => (bool) ($p->puede_crear ?? false),
                'editar'   => (bool) ($p->puede_editar ?? false),
                'eliminar' => (bool) ($p->puede_eliminar ?? false),
            ];
        }
        $gente = $personas[$rol->id] ?? collect();

        return [
            'id'       => $rol->id,
            'nombre'   => $rol->nombre,
            'permisos' => $permisos,
            'personas' => $gente,
            'usuarios' => $gente->count(),
            'total'    => $rol->esTotal(),
        ];
    }

    private function presentarUno(Rol $rol): array
    {
        return $this->presentar($rol->load('permisos'), $this->modulos(), $this->personasPorRol());
    }

    /** Guarda solo los módulos con algún permiso; crear, editar o eliminar implican ver. */
    private function guardarPermisos(Rol $rol, array $permisos): void
    {
        $validos = Modulo::pluck('id')->map(fn ($i) => (int) $i)->all();
        $si = fn ($v) => filter_var($v, FILTER_VALIDATE_BOOLEAN) || $v === '1' || $v === 1;

        foreach ($permisos as $moduloId => $acciones) {
            if (!in_array((int) $moduloId, $validos, true) || !is_array($acciones)) {
                continue;
            }
            $a = [];
            foreach (self::ACCIONES as $accion) {
                $a[$accion] = array_key_exists($accion, $acciones) && $si($acciones[$accion]);
            }
            if ($a['crear'] || $a['editar'] || $a['eliminar']) {
                $a['ver'] = true;
            }
            if (!in_array(true, $a, true)) {
                continue;
            }

            Permiso::create([
                'rol_id'         => $rol->id,
                'modulo_id'      => (int) $moduloId,
                'puede_ver'      => $a['ver'],
                'puede_crear'    => $a['crear'],
                'puede_editar'   => $a['editar'],
                'puede_eliminar' => $a['eliminar'],
            ]);
        }
    }

    /**
     * Evita que alguien sin acceso total se dé más poder del que tiene:
     *  - no puede crear ni renombrar un rol a un nombre con acceso total (Administrador, Admin...);
     *  - no puede modificar un rol con acceso total ni su propio rol;
     *  - no puede otorgar acciones que él mismo no tiene.
     * Devuelve el motivo del rechazo o null si puede continuar.
     */
    private function escalada(Request $request, ?Rol $rol): ?string
    {
        $user = auth()->user();
        if ($user->tieneAccesoTotal()) {
            return null;
        }

        if (Rol::nombreEsTotal($request->nombre)) {
            return 'Solo un administrador puede usar un nombre de rol con acceso total («' . $request->nombre . '»).';
        }

        if ($rol && $rol->esTotal()) {
            return 'Solo un administrador puede modificar un rol con acceso total.';
        }

        if ($rol && (int) $user->rol_id === (int) $rol->id) {
            return 'No puedes modificar los permisos de tu propio rol.';
        }

        $modulos = Modulo::pluck('nombre', 'id');
        $sinPermiso = [];
        foreach ((array) $request->input('permisos', []) as $moduloId => $acciones) {
            if (!isset($modulos[(int) $moduloId]) || !is_array($acciones)) {
                continue;
            }
            foreach (self::ACCIONES as $accion) {
                $pide = array_key_exists($accion, $acciones) && filter_var($acciones[$accion], FILTER_VALIDATE_BOOLEAN);
                if ($pide && !$user->tienePermiso($modulos[(int) $moduloId], $accion)) {
                    $sinPermiso[] = $modulos[(int) $moduloId] . ' (' . $accion . ')';
                }
            }
        }

        return $sinPermiso
            ? 'No puedes otorgar permisos que tú no tienes: ' . implode(', ', $sinPermiso) . '.'
            : null;
    }

    private function mensajes(): array
    {
        return [
            'nombre.required' => 'Escribe el nombre del rol.',
            'nombre.unique'   => 'Ya existe un rol con ese nombre.',
            'nombre.max'      => 'El nombre admite hasta 100 caracteres.',
        ];
    }

    private function bitacora(string $accion, string $texto, Rol $rol): void
    {
        try {
            Bitacora::registrar('Roles', $accion, $texto, [
                'rol_id'   => $rol->id,
                'modulos'  => Permiso::where('rol_id', $rol->id)->count(),
            ]);
        } catch (Throwable $e) {
            // La bitácora no debe impedir guardar
        }
    }

    private function negado(Request $request, string $mensaje)
    {
        return $request->expectsJson()
            ? response()->json(['ok' => false, 'success' => false, 'status' => 'error', 'message' => $mensaje], 403)
            : abort(403, $mensaje);
    }

    private function fallo(Request $request, string $mensaje)
    {
        return $request->expectsJson()
            ? response()->json(['ok' => false, 'success' => false, 'status' => 'error', 'message' => $mensaje], 500)
            : back()->withErrors(['error' => $mensaje]);
    }
}