<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use Illuminate\Http\Request;

class BusquedaController extends Controller
{
    public function buscar(Request $request)
    {
        $query = trim(mb_strtolower($request->input('q', '')));

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $user = auth()->user();

        // Helper para verificar permisos de acceso a los módulos
        $tienePermiso = function ($modulo) use ($user) {
            if (!$user) return false;
            if (method_exists($user, 'tienePermiso')) {
                return $user->tienePermiso($modulo, 'ver');
            }
            if (method_exists($user, 'hasPermission')) {
                return $user->hasPermission($modulo);
            }
            if (method_exists($user, 'can')) {
                return $user->can($modulo);
            }
            return true; // Acceso total para administrador / testing
        };

        // Definición de todos los módulos del Sidebar
        $modulos = [
            [
                'nombre' => 'Dashboard',
                'keywords' => ['dashboard', 'inicio', 'panel', 'resumen', 'principal', 'estadisticas'],
                'categoria' => 'General',
                'icono' => 'bi-speedometer2',
                'route' => 'home',
                'permiso' => null,
            ],
            [
                'nombre' => 'Pacientes',
                'keywords' => ['pacientes', 'paciente', 'expedientes', 'fichas', 'historias clinicas', 'directorio'],
                'categoria' => 'Gestión Médica',
                'icono' => 'bi-people-fill',
                'route' => 'pacientes.index',
                'permiso' => 'Pacientes',
            ],
            [
                'nombre' => 'Citas',
                'keywords' => ['citas', 'cita', 'agenda', 'calendario', 'turnos', 'agendar'],
                'categoria' => 'Gestión Médica',
                'icono' => 'bi-calendar-event-fill',
                'route' => 'citas.index',
                'permiso' => 'Citas',
            ],
            [
                'nombre' => 'Consultas',
                'keywords' => ['consultas', 'consulta', 'medica', 'diagnostico', 'atencion', 'recetas'],
                'categoria' => 'Gestión Médica',
                'icono' => 'bi-journal-medical',
                'route' => 'consultas.index',
                'permiso' => 'Consultas',
            ],
            [
                'nombre' => 'Consultorios',
                'keywords' => ['consultorios', 'consultorio', 'salas', 'espacios', 'pisos'],
                'categoria' => 'Gestión Médica',
                'icono' => 'bi-building-fill',
                'route' => 'consultorios.index',
                'permiso' => 'Consultorios',
            ],
            [
                'nombre' => 'Personal / Usuarios',
                'keywords' => ['personal', 'usuarios', 'doctores', 'medicos', 'enfermeros', 'equipo', 'empleados'],
                'categoria' => 'Administración',
                'icono' => 'bi-person-badge-fill',
                'route' => 'personal.index',
                'permiso' => 'Personal',
            ],
            [
                'nombre' => 'Facturación y Finanzas',
                'keywords' => ['facturacion', 'facturas', 'pagos', 'finanzas', 'cobros', 'recibos'],
                'categoria' => 'Farmacia y Finanzas',
                'icono' => 'bi-credit-card-fill',
                'route' => 'facturacion.index',
                'permiso' => 'Facturacion',
            ],
            [
                'nombre' => 'Configuración',
                'keywords' => ['configuracion', 'ajustes', 'roles', 'permisos', 'sistema', 'clinica'],
                'categoria' => 'Sistema',
                'icono' => 'bi-gear-fill',
                'route' => 'configuracion.index',
                'permiso' => 'Configuracion',
            ],
        ];

        $resultados = [];

        foreach ($modulos as $mod) {
            // Verificar si la ruta existe en Laravel
            if (!\Route::has($mod['route'])) {
                continue;
            }

            // Filtrar por permisos del usuario
            if ($mod['permiso'] && !$tienePermiso($mod['permiso'])) {
                continue;
            }

            // Buscar coincidencia en el nombre o en las palabras clave (keywords)
            $coincide = false;
            foreach ($mod['keywords'] as $kw) {
                if (str_contains($kw, $query)) {
                    $coincide = true;
                    break;
                }
            }

            if ($coincide) {
                $resultados[] = [
                    'categoria' => $mod['categoria'],
                    'icono'     => $mod['icono'],
                    'titulo'    => $mod['nombre'],
                    'subtitulo' => 'Ir al módulo de ' . $mod['nombre'],
                    'url'       => route($mod['route']),
                ];
            }
        }

        // Pacientes: nombre, apellidos, CURP, código o celular (cada palabra debe coincidir)
        if (\Route::has('pacientes.index') && $tienePermiso('Pacientes')) {
            $palabras = collect(preg_split('/\s+/', mb_strtoupper(trim($request->input('q', '')), 'UTF-8')))
                ->filter()
                ->take(4);

            $pacientes = Paciente::query()
                ->with('alergias')
                ->where(function ($consulta) use ($palabras) {
                    foreach ($palabras as $palabra) {
                        $consulta->where(function ($w) use ($palabra) {
                            foreach (['primer_nombre', 'apellido_paterno', 'apellido_materno', 'rut', 'codigo', 'celular'] as $columna) {
                                $w->orWhere($columna, 'like', '%' . $palabra . '%');
                            }
                        });
                    }
                })
                ->orderBy('apellido_paterno')
                ->limit(6)
                ->get();

            foreach ($pacientes as $p) {
                $apellidos = trim($p->apellido_paterno . ' ' . $p->apellido_materno);
                $alergias  = $p->alergias->pluck('descripcion')->filter()->values();

                $resultados[] = [
                    'categoria' => 'Pacientes',
                    'icono'     => 'bi-person-vcard',
                    'titulo'    => trim($apellidos . ', ' . $p->primer_nombre, ', '),
                    'subtitulo' => collect([$p->rut ?: 'SIN CURP', $p->codigo])->filter()->implode(' · '),
                    'alergia'   => $alergias->isNotEmpty() ? 'Alergia: ' . $alergias->first() . ($alergias->count() > 1 ? ' +' . ($alergias->count() - 1) : '') : null,
                    'semilla'   => $p->rut ?: ($p->codigo ?: (string) $p->id),
                    'url'       => route('pacientes.index', ['p' => $p->id]),
                ];
            }
        }

        return response()->json($resultados);
    }
}