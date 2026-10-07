<?php

namespace App\Services\Chatbot;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Consultas a la base de datos que el chatbot puede ejecutar ("funciones").
 *
 * Reglas de seguridad:
 *  - Solo LECTURA. Ninguna función crea, edita ni elimina registros.
 *  - Cada función pertenece a un módulo y se valida con User::tienePermiso()
 *    (la misma regla que usa el middleware de permisos del sistema).
 *  - NUNCA se envía información clínica (diagnósticos, alergias, condiciones,
 *    medicamentos del paciente, recetas, signos vitales ni consultas).
 */
class HerramientasChatbot
{
    public function __construct(private User $user)
    {
    }

    /** Funciones que el usuario actual tiene permiso de usar. */
    public function definiciones(): array
    {
        $rangoFechas = [
            'fecha_inicio' => ['type' => 'string', 'description' => 'Fecha inicial YYYY-MM-DD'],
            'fecha_fin'    => ['type' => 'string', 'description' => 'Fecha final YYYY-MM-DD (inclusive)'],
        ];

        $todas = [
            [
                'modulo' => 'citas',
                'name' => 'consultar_citas',
                'description' => 'Lista las citas médicas de un rango de fechas con hora, paciente, médico, consultorio, tipo y estado. Se puede filtrar por médico, paciente o estado.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => $rangoFechas + [
                        'medico'   => ['type' => 'string', 'description' => 'Parte del nombre del médico'],
                        'paciente' => ['type' => 'string', 'description' => 'Parte del nombre o código del paciente'],
                        'estado'   => ['type' => 'string', 'enum' => ['Pendiente', 'Confirmada', 'En curso', 'Finalizada', 'Cancelada']],
                    ],
                    'required' => ['fecha_inicio', 'fecha_fin'],
                ],
            ],
            [
                'modulo' => 'citas',
                'name' => 'contar_citas',
                'description' => 'Cuenta citas en un rango de fechas agrupadas por estado, médico, tipo de consulta o día. Úsala para preguntas de "cuántas".',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => $rangoFechas + [
                        'agrupar_por' => ['type' => 'string', 'enum' => ['estado', 'medico', 'tipo_consulta', 'dia']],
                    ],
                    'required' => ['fecha_inicio', 'fecha_fin', 'agrupar_por'],
                ],
            ],
            [
                'modulo' => 'pacientes',
                'name' => 'buscar_pacientes',
                'description' => 'Busca pacientes por nombre, apellido o código. Devuelve solo datos administrativos (código, nombre, estado, médico tratante, fecha de registro). No incluye información clínica.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'texto'  => ['type' => 'string', 'description' => 'Nombre, apellido o código del paciente'],
                        'estado' => ['type' => 'string', 'enum' => ['Activo', 'Inactivo']],
                    ],
                    'required' => ['texto'],
                ],
            ],
            [
                'modulo' => 'pacientes',
                'name' => 'estadisticas_pacientes',
                'description' => 'Totales de pacientes: total, activos/inactivos, por género y registrados en un rango de fechas (opcional).',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => $rangoFechas,
                ],
            ],
            [
                'modulo' => 'personal',
                'name' => 'consultar_personal',
                'description' => 'Lista al personal de la clínica (médicos y demás) con especialidad, turno y estado.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'texto'        => ['type' => 'string', 'description' => 'Parte del nombre'],
                        'especialidad' => ['type' => 'string'],
                        'turno'        => ['type' => 'string', 'enum' => ['Mañana', 'Tarde', 'Completo', 'Noche']],
                        'estado'       => ['type' => 'string', 'enum' => ['Activo', 'Inactivo']],
                    ],
                ],
            ],
            [
                'modulo' => 'consultorios',
                'name' => 'consultar_consultorios',
                'description' => 'Lista los consultorios y su estado (Disponible, Ocupado, Mantenimiento).',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'estado' => ['type' => 'string', 'enum' => ['Disponible', 'Ocupado', 'Mantenimiento']],
                    ],
                ],
            ],
            [
                'modulo' => 'inventario',
                'name' => 'consultar_inventario',
                'description' => 'Busca medicamentos y productos del inventario con su stock disponible, stock mínimo y estado (Normal, Bajo stock, Agotado). Puede devolver solo los que tienen stock bajo o agotado.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'texto'           => ['type' => 'string', 'description' => 'Nombre o código del producto'],
                        'solo_bajo_stock' => ['type' => 'boolean', 'description' => 'true para traer solo productos con stock bajo o agotados'],
                    ],
                ],
            ],
            [
                'modulo' => 'caja',
                'name' => 'resumen_ventas',
                'description' => 'Resumen de ventas del punto de venta (tickets) y de los movimientos de caja (ingresos y egresos por método de pago) en un rango de fechas.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => $rangoFechas,
                    'required' => ['fecha_inicio', 'fecha_fin'],
                ],
            ],
        ];

        $permitidas = [];
        foreach ($todas as $funcion) {
            if ($this->puedeVer($funcion['modulo'])) {
                unset($funcion['modulo']);
                $permitidas[] = $funcion;
            }
        }

        return $permitidas;
    }

    public function ejecutar(string $nombre, array $args): array
    {
        $modulos = [
            'consultar_citas'        => 'citas',
            'contar_citas'           => 'citas',
            'buscar_pacientes'       => 'pacientes',
            'estadisticas_pacientes' => 'pacientes',
            'consultar_personal'     => 'personal',
            'consultar_consultorios' => 'consultorios',
            'consultar_inventario'   => 'inventario',
            'resumen_ventas'         => 'caja',
        ];

        if (!isset($modulos[$nombre])) {
            return ['error' => 'Función desconocida.'];
        }

        // Doble verificación: aunque el modelo pida una función, se revisa el permiso.
        if (!$this->puedeVer($modulos[$nombre])) {
            return ['error' => 'El usuario no tiene permiso para consultar este módulo.'];
        }

        try {
            return match ($nombre) {
                'consultar_citas'        => $this->consultarCitas($args),
                'contar_citas'           => $this->contarCitas($args),
                'buscar_pacientes'       => $this->buscarPacientes($args),
                'estadisticas_pacientes' => $this->estadisticasPacientes($args),
                'consultar_personal'     => $this->consultarPersonal($args),
                'consultar_consultorios' => $this->consultarConsultorios($args),
                'consultar_inventario'   => $this->consultarInventario($args),
                'resumen_ventas'         => $this->resumenVentas($args),
            };
        } catch (\Throwable $e) {
            report($e);
            return ['error' => 'No se pudo realizar la consulta.'];
        }
    }

    /* ---------------------------------------------------------------------
     | Citas
     * ------------------------------------------------------------------- */

    private function consultarCitas(array $a): array
    {
        $q = DB::table('citas')
            ->join('pacientes', 'pacientes.id', '=', 'citas.paciente_id')
            ->leftJoin('personals', 'personals.id', '=', 'citas.personal_id')
            ->leftJoin('consultorios', 'consultorios.id', '=', 'citas.consultorio_id');

        $this->filtrarFechas($q, 'citas.fecha', $a);

        $this->filtrarClinica($q, 'citas');

        if (!empty($a['estado'])) {
            $q->where('citas.estado', $a['estado']);
        }
        if (!empty($a['medico'])) {
            $q->where('personals.nombre_completo', 'like', '%' . $a['medico'] . '%');
        }
        if (!empty($a['paciente'])) {
            $this->buscarNombrePaciente($q, $a['paciente']);
        }

        $total = (clone $q)->count();

        $filas = $q->orderBy('citas.fecha')->orderBy('citas.hora')
            ->limit($this->limite())
            ->get([
                'citas.fecha', 'citas.hora', 'citas.duracion_min', 'citas.tipo_consulta', 'citas.estado', 'citas.motivo',
                'pacientes.codigo', 'pacientes.primer_nombre', 'pacientes.apellido_paterno', 'pacientes.apellido_materno',
                'personals.nombre_completo as medico', 'consultorios.nombre as consultorio',
            ])
            ->map(fn ($c) => [
                'fecha'         => substr((string) $c->fecha, 0, 10),
                'hora'          => substr((string) $c->hora, 0, 5),
                'duracion_min'  => $c->duracion_min,
                'paciente'      => $this->nombrePaciente($c),
                'medico'        => $c->medico,
                'consultorio'   => $c->consultorio,
                'tipo_consulta' => $c->tipo_consulta,
                'estado'        => $c->estado,
                'motivo'        => $c->motivo,
            ]);

        return ['total' => $total, 'mostrando' => $filas->count(), 'citas' => $filas->all()];
    }

    private function contarCitas(array $a): array
    {
        $columnas = [
            'estado'        => 'citas.estado',
            'medico'        => 'personals.nombre_completo',
            'tipo_consulta' => 'citas.tipo_consulta',
            'dia'           => 'citas.fecha',
        ];
        $agrupar = isset($columnas[$a['agrupar_por'] ?? '']) ? $a['agrupar_por'] : 'estado';
        $col = $columnas[$agrupar];

        $q = DB::table('citas')
            ->leftJoin('personals', 'personals.id', '=', 'citas.personal_id');

        $this->filtrarFechas($q, 'citas.fecha', $a);

        $this->filtrarClinica($q, 'citas');

        $grupos = $q->groupBy($col)
            ->orderBy($col)
            ->get([DB::raw("$col as grupo"), DB::raw('COUNT(*) as total')]);

        return ['total' => (int) $grupos->sum('total'), "por_$agrupar" => $grupos->all()];
    }

    /* ---------------------------------------------------------------------
     | Pacientes
     * ------------------------------------------------------------------- */

    private function buscarPacientes(array $a): array
    {
        $q = DB::table('pacientes')
            ->leftJoin('personals', 'personals.id', '=', 'pacientes.medico_tratante_id');

        $this->buscarNombrePaciente($q, $a['texto'] ?? '');
        $this->filtrarClinica($q, 'pacientes');

        if (!empty($a['estado'])) {
            $q->where('pacientes.estado', $a['estado']);
        }

        $filas = $q->orderBy('pacientes.apellido_paterno')->orderBy('pacientes.primer_nombre')
            ->limit($this->limite())
            ->get([
                'pacientes.codigo', 'pacientes.primer_nombre', 'pacientes.apellido_paterno', 'pacientes.apellido_materno',
                'pacientes.estado', 'personals.nombre_completo as medico_tratante', 'pacientes.created_at',
            ])
            ->map(fn ($p) => [
                'codigo'          => $p->codigo,
                'paciente'        => $this->nombrePaciente($p),
                'estado'          => $p->estado,
                'medico_tratante' => $p->medico_tratante,
                'fecha_registro'  => substr((string) $p->created_at, 0, 10),
            ]);

        return ['encontrados' => $filas->count(), 'pacientes' => $filas->all()];
    }

    private function estadisticasPacientes(array $a): array
    {
        $base = DB::table('pacientes');
        $this->filtrarClinica($base, 'pacientes');

        $resultado = [
            'total'      => (clone $base)->count(),
            'por_estado' => (clone $base)->groupBy('estado')->get(['estado', DB::raw('COUNT(*) as total')])->all(),
            'por_genero' => (clone $base)->groupBy('genero')->get(['genero', DB::raw('COUNT(*) as total')])->all(),
        ];

        if (!empty($a['fecha_inicio']) && !empty($a['fecha_fin'])) {
            [$inicio, $fin] = $this->rango($a);
            $resultado['registrados_en_rango'] = (clone $base)
                ->whereBetween('created_at', ["$inicio 00:00:00", "$fin 23:59:59"])
                ->count();
        }

        return $resultado;
    }

    /* ---------------------------------------------------------------------
     | Personal y consultorios
     * ------------------------------------------------------------------- */

    private function consultarPersonal(array $a): array
    {
        $q = DB::table('personals')
            ->leftJoin('personal_especialidad', 'personal_especialidad.personal_id', '=', 'personals.id')
            ->leftJoin('especialidades', 'especialidades.id', '=', 'personal_especialidad.especialidad_id');

        $this->filtrarClinica($q, 'personals');

        if (!empty($a['texto'])) {
            $q->where('personals.nombre_completo', 'like', '%' . $a['texto'] . '%');
        }
        if (!empty($a['especialidad'])) {
            $q->where(function ($w) use ($a) {
                $w->where('especialidades.nombre', 'like', '%' . $a['especialidad'] . '%')
                  ->orWhere('personals.especialidad_principal', 'like', '%' . $a['especialidad'] . '%');
            });
        }
        if (!empty($a['turno'])) {
            $q->where('personals.turno', $a['turno']);
        }
        if (!empty($a['estado'])) {
            $q->where('personals.estado', $a['estado']);
        }

        $filas = $q->orderBy('personals.nombre_completo')
            ->limit($this->limite() * 3)
            ->get(['personals.id', 'personals.nombre_completo', 'personals.especialidad_principal',
                   'personals.turno', 'personals.estado', 'especialidades.nombre as especialidad'])
            ->groupBy('id')
            ->map(function ($grupo) {
                $p = $grupo->first();
                return [
                    'nombre'         => $p->nombre_completo,
                    'especialidades' => $grupo->pluck('especialidad')->push($p->especialidad_principal)->filter()->unique()->values()->all(),
                    'turno'          => $p->turno,
                    'estado'         => $p->estado,
                ];
            })
            ->take($this->limite())
            ->values();

        return ['encontrados' => $filas->count(), 'personal' => $filas->all()];
    }

    private function consultarConsultorios(array $a): array
    {
        $q = DB::table('consultorios');
        $this->filtrarClinica($q, 'consultorios');

        if (!empty($a['estado'])) {
            $q->where('estado', $a['estado']);
        }

        $filas = $q->orderBy('nombre')->limit($this->limite())->get(['nombre', 'piso', 'estado']);

        return [
            'total'         => $filas->count(),
            'por_estado'    => $filas->countBy('estado')->all(),
            'consultorios'  => $filas->all(),
        ];
    }

    /* ---------------------------------------------------------------------
     | Inventario (igual que InventarioController: se usa stock_disponible)
     * ------------------------------------------------------------------- */

    private function consultarInventario(array $a): array
    {
        $q = DB::table('productos_inventario')
            ->leftJoin('categorias_inventario', 'categorias_inventario.id', '=', 'productos_inventario.categoria_id');

        $this->filtrarClinica($q, 'productos_inventario');

        if (!empty($a['texto'])) {
            $q->where(function ($w) use ($a) {
                $w->where('productos_inventario.nombre', 'like', '%' . $a['texto'] . '%')
                  ->orWhere('productos_inventario.codigo', 'like', '%' . $a['texto'] . '%');
            });
        }
        if (!empty($a['solo_bajo_stock'])) {
            $q->whereColumn('productos_inventario.stock_disponible', '<=', 'productos_inventario.stock_minimo');
        }

        $filas = $q->orderBy('productos_inventario.stock_disponible')
            ->orderBy('productos_inventario.nombre')
            ->limit($this->limite())
            ->get([
                'productos_inventario.codigo', 'productos_inventario.nombre', 'categorias_inventario.nombre as categoria',
                'productos_inventario.stock_disponible', 'productos_inventario.stock_minimo', 'productos_inventario.precio_venta',
            ])
            ->map(fn ($p) => [
                'codigo'           => $p->codigo,
                'nombre'           => $p->nombre,
                'categoria'        => $p->categoria,
                'stock_disponible' => (int) $p->stock_disponible,
                'stock_minimo'     => (int) $p->stock_minimo,
                'precio_venta'     => (float) $p->precio_venta,
                'estado'           => $this->estadoStock((int) $p->stock_disponible, (int) $p->stock_minimo),
            ]);

        // Totales del inventario completo (mismas métricas que las tarjetas del módulo Inventario)
        $todos = DB::table('productos_inventario');
        $this->filtrarClinica($todos, 'productos_inventario');
        $todos = $todos->get(['stock_disponible', 'stock_minimo']);

        return [
            'resumen_inventario' => [
                'total_productos' => $todos->count(),
                'stock_bajo'      => $todos->filter(fn ($p) => $p->stock_disponible > 0 && $p->stock_disponible <= $p->stock_minimo)->count(),
                'agotados'        => $todos->filter(fn ($p) => $p->stock_disponible <= 0)->count(),
            ],
            'encontrados' => $filas->count(),
            'productos'   => $filas->all(),
        ];
    }

    private function estadoStock(int $disponible, int $minimo): string
    {
        if ($disponible <= 0) {
            return 'Agotado';
        }

        return $disponible <= $minimo ? 'Bajo stock' : 'Normal';
    }

    /* ---------------------------------------------------------------------
     | Ventas y caja
     * ------------------------------------------------------------------- */

    private function resumenVentas(array $a): array
    {
        [$inicio, $fin] = $this->rango($a);
        $desde = "$inicio 00:00:00";
        $hasta = "$fin 23:59:59";

        $tickets = DB::table('tickets_venta')
            ->whereBetween('created_at', [$desde, $hasta])
            ->groupBy('status')
            ->get(['status', DB::raw('COUNT(*) as cantidad'), DB::raw('SUM(monto_total) as monto')]);

        $caja = DB::table('movimientos_caja')
            ->whereBetween('created_at', [$desde, $hasta])
            ->groupBy('tipo', 'metodo_pago')
            ->get(['tipo', 'metodo_pago', DB::raw('COUNT(*) as movimientos'), DB::raw('SUM(monto) as monto')]);

        $ingresos = (float) $caja->where('tipo', 'Ingreso')->sum('monto');
        $egresos  = (float) $caja->where('tipo', 'Egreso')->sum('monto');

        return [
            'periodo'           => "$inicio a $fin",
            'tickets_por_estado' => $tickets->all(),
            'caja' => [
                'total_ingresos'   => round($ingresos, 2),
                'total_egresos'    => round($egresos, 2),
                'balance'          => round($ingresos - $egresos, 2),
                'detalle'          => $caja->all(),
            ],
        ];
    }

    /* ---------------------------------------------------------------------
     | Utilidades
     * ------------------------------------------------------------------- */

    /** Usa la misma regla de permisos que el resto del sistema (User::tienePermiso). */
    public function puedeVer(string $clave): bool
    {
        $modulo = config("chatbot.modulos.$clave");
        if (!$modulo) {
            return false;
        }

        if (method_exists($this->user, 'tienePermiso')) {
            return (bool) $this->user->tienePermiso($modulo, 'ver');
        }

        return $this->user->isSuperAdmin();
    }

    /** Igual que el resto del sistema: whereDate, porque la fecha puede guardarse con hora */
    private function filtrarFechas(Builder $q, string $columna, array $a): void
    {
        [$inicio, $fin] = $this->rango($a);
        $q->whereDate($columna, '>=', $inicio)->whereDate($columna, '<=', $fin);
    }

    /** Solo filtra por clínica si el usuario tiene una asignada (el Super Admin ve todo). */
    private function filtrarClinica(Builder $q, string $tabla): void
    {
        if (!$this->user->isSuperAdmin() && $this->user->clinica_id) {
            $q->where("$tabla.clinica_id", $this->user->clinica_id);
        }
    }

    /** Cada palabra debe aparecer en el nombre, en algún apellido o en el código. */
    private function buscarNombrePaciente(Builder $q, string $texto): void
    {
        foreach (preg_split('/\s+/', trim($texto), -1, PREG_SPLIT_NO_EMPTY) as $palabra) {
            $q->where(function ($w) use ($palabra) {
                $w->where('pacientes.primer_nombre', 'like', "%$palabra%")
                  ->orWhere('pacientes.apellido_paterno', 'like', "%$palabra%")
                  ->orWhere('pacientes.apellido_materno', 'like', "%$palabra%")
                  ->orWhere('pacientes.codigo', 'like', "%$palabra%");
            });
        }
    }

    private function nombrePaciente(object $fila): ?string
    {
        if (config('chatbot.ocultar_nombres_pacientes')) {
            return $fila->codigo ? 'Paciente ' . $fila->codigo : 'Paciente';
        }

        return trim("{$fila->primer_nombre} {$fila->apellido_paterno} {$fila->apellido_materno}") ?: null;
    }

    private function rango(array $a): array
    {
        $inicio = $this->fecha($a['fecha_inicio'] ?? null);
        $fin = $this->fecha($a['fecha_fin'] ?? ($a['fecha_inicio'] ?? null));

        return $inicio <= $fin ? [$inicio, $fin] : [$fin, $inicio];
    }

    private function fecha(?string $valor): string
    {
        try {
            return \Carbon\Carbon::parse($valor ?? 'today', 'America/Mexico_City')->toDateString();
        } catch (\Throwable) {
            return now('America/Mexico_City')->toDateString();
        }
    }

    private function limite(): int
    {
        return (int) config('chatbot.max_resultados', 50);
    }
}