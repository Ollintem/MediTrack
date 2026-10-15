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

    /**
     * Módulos que el sistema revisa (middleware permiso:X y menú). Si alguno no existe en la tabla
     * `modulos`, se crea al abrir esta pantalla para que se le puedan dar permisos.
     */
    private const MODULOS = [
        'Gestión médica' => [
            'Pacientes'    => ['bi-people', 'Expedientes y credenciales'],
            'Citas'        => ['bi-calendar2-week', 'Agenda de la clínica'],
            'Consultas'    => ['bi-clipboard2-pulse', 'Atención del médico'],
            'Consultorios' => ['bi-hospital', 'Plano y estado de las salas'],
            'Recetas'      => ['bi-file-earmark-medical', 'Emisión e impresión'],
        ],
        'Farmacia y finanzas' => [
            'POS'          => ['bi-shop', 'Terminal punto de venta'],
            'Caja Central' => ['bi-cash-coin', 'Cobros, apertura y corte'],
            'Inventario'   => ['bi-box-seam', 'Existencias y despacho de farmacia'],
            'Proveedores'  => ['bi-truck', 'Compras y laboratorios'],
            'Facturación'  => ['bi-receipt', 'Facturas (próximamente)'],
        ],
        'Administración' => [
            'Personal'      => ['bi-person-badge', 'Plantilla, accesos y nómina'],
            'Roles'         => ['bi-shield-lock', 'Esta pantalla'],
            'Reportes'      => ['bi-bar-chart-line', 'Informes (próximamente)'],
            'Configuración' => ['bi-gear', 'Datos de la clínica y cuenta'],
            'Bitácora'      => ['bi-journal-text', 'Historial de movimientos'],
        ],
    ];

    /** Roles que el sistema deja pasar a todo sin revisar permisos (middleware y menú). */
    private const ROLES_TOTALES = ['administrador', 'super admin'];

    public function index()
    {
        if (!auth()->check() || !auth()->user()->tienePermiso('Roles', 'ver')) {
            abort(403, 'No tienes permisos para acceder al módulo de Roles.');
        }

        $this->sincronizarModulos();

        $modulos = $this->modulos();
        $personas = $this->personasPorRol();
        $roles = Rol::with('permisos')->orderBy('nombre')->get()
            ->map(fn ($r) => $this->presentar($r, $modulos, $personas))->values();

        $user = auth()->user();
        $datosRoles = [
            'roles'   => $roles,
            'modulos' => $modulos->values(),
            'miRolId' => $user->rol_id ? (int) $user->rol_id : null,
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
        if (!auth()->check() || !auth()->user()->tienePermiso('Roles', 'crear')) {
            return $this->negado($request, 'No tienes permisos para crear roles.');
        }

        $request->merge(['nombre' => trim(preg_replace('/\s+/', ' ', (string) $request->nombre))]);
        $request->validate(['nombre' => 'required|string|max:100|unique:roles,nombre'], $this->mensajes());

        try {
            $rol = DB::transaction(function () use ($request) {
                $rol = Rol::create(['nombre' => $request->nombre]);
                $this->guardarPermisos($rol, (array) $request->input('permisos', []));

                return $rol;
            });
            $this->bitacora('Crear', "Se creó el rol {$rol->nombre}", $rol);
        } catch (Throwable $e) {
            return $this->fallo($request, 'No se pudo crear el rol: ' . $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'mensaje' => 'Rol creado correctamente.', 'rol' => $this->presentarUno($rol)], 201);
        }

        return redirect()->route('roles.index')->with('success', 'Rol y permisos creados correctamente.');
    }

    public function update(Request $request, $id)
    {
        if (!auth()->check() || !auth()->user()->tienePermiso('Roles', 'editar')) {
            return $this->negado($request, 'No tienes permisos para editar roles.');
        }

        $rol = Rol::findOrFail($id);
        $request->merge(['nombre' => trim(preg_replace('/\s+/', ' ', (string) $request->nombre))]);
        $request->validate(['nombre' => 'required|string|max:100|unique:roles,nombre,' . $rol->id], $this->mensajes());

        try {
            DB::transaction(function () use ($request, $rol) {
                $rol->update(['nombre' => $request->nombre]);
                Permiso::where('rol_id', $rol->id)->delete();
                $this->guardarPermisos($rol, (array) $request->input('permisos', []));
            });
            $this->bitacora('Editar', "Se actualizaron los permisos del rol {$rol->nombre}", $rol);
        } catch (Throwable $e) {
            return $this->fallo($request, 'No se pudo guardar el rol: ' . $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'mensaje' => 'Permisos guardados.', 'rol' => $this->presentarUno($rol->fresh())]);
        }

        return redirect()->route('roles.index')->with('success', 'Rol y permisos actualizados correctamente.');
    }

    public function destroy($id)
    {
        if (!auth()->check() || !auth()->user()->tienePermiso('Roles', 'eliminar')) {
            return response()->json(['success' => false, 'message' => 'No tienes permisos para eliminar roles.'], 403);
        }

        try {
            $rol = Rol::findOrFail($id);

            if ((int) auth()->user()->rol_id === (int) $rol->id) {
                return response()->json(['success' => false, 'message' => 'No puedes eliminar tu propio rol.'], 422);
            }

            $nombre = $rol->nombre;
            $usuarios = User::where('rol_id', $rol->id)->count();
            $rol->delete();
            Bitacora::registrar('Roles', 'Eliminar', "Se eliminó el rol {$nombre}", ['rol_id' => (int) $id, 'usuarios_sin_rol' => $usuarios]);

            return response()->json(['success' => true, 'message' => 'El rol se eliminó correctamente.']);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error al eliminar: ' . $e->getMessage()], 422);
        }
    }

    // =====================================================================
    //  AYUDANTES
    // =====================================================================

    /** Crea los módulos que el sistema usa y aún no están en la tabla (sin duplicar por mayúsculas). */
    private function sincronizarModulos(): void
    {
        $existentes = Modulo::pluck('nombre')->map(fn ($n) => mb_strtolower(trim($n)))->all();

        foreach (self::MODULOS as $grupo) {
            foreach (array_keys($grupo) as $nombre) {
                if (!in_array(mb_strtolower($nombre), $existentes, true)) {
                    Modulo::create(['nombre' => $nombre]);
                }
            }
        }
    }

    /** Módulos con su grupo, ícono y descripción, en el orden del menú. */
    private function modulos()
    {
        $info = [];
        $orden = 0;
        foreach (self::MODULOS as $grupo => $lista) {
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
            'total'    => in_array(mb_strtolower(trim($rol->nombre)), self::ROLES_TOTALES, true),
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
            ? response()->json(['ok' => false, 'message' => $mensaje], 403)
            : abort(403, $mensaje);
    }

    private function fallo(Request $request, string $mensaje)
    {
        return $request->expectsJson()
            ? response()->json(['ok' => false, 'message' => $mensaje], 500)
            : back()->withErrors(['error' => $mensaje]);
    }
}