<?php

namespace App\Services\Chatbot;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Consultas a la base de datos que el chatbot puede ejecutar ("tools").
 *
 * Reglas de seguridad:
 *  - Solo LECTURA. Ninguna herramienta crea, edita ni elimina registros.
 *  - Cada herramienta pertenece a un módulo y se valida contra la tabla `permisos`
 *    (puede_ver) igual que el middleware CheckPermission.
 *  - Todo se filtra por la clínica del usuario (excepto el Super Admin, ID 1).
 *  - NUNCA se envía información clínica (diagnósticos, alergias, condiciones,
 *    medicamentos, recetas, signos vitales, consultas ni archivos clínicos).
 */
class HerramientasChatbot
{
    public function __construct(private User $user)
    {
    }

    /** Herramientas que el usuario actual tiene permiso de usar. */
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
                'description' => 'Lista citas médicas en un rango de fechas. Se puede filtrar por médico, paciente o estado.',
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
                'description' => 'Cuenta citas en un rango de fechas, agrupadas por estado, médico, tipo de consulta o día. Úsala para preguntas de "cuántas".',
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
                'description' => 'Busca pacientes por nombre o código. Devuelve solo datos administrativos (código, estado, médico tratante, aseguradora, fecha de registro). No incluye información clínica.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'texto'  => ['type' => 'string', 'description' => 'Nombre o código del paciente'],
                        'estado' => ['type' => 'string', 'enum' => ['Activo', 'Inactivo']],
                    ],
                    'required' => ['texto'],
                ],
            ],
            [
                'modulo' => 'pacientes',
                'name' => 'estadisticas_pacientes',
                'description' => 'Totales de pacientes: registrados en un rango de fechas, activos/inactivos, por aseguradora y por género.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => $rangoFechas,
                    'required' => [],
                ],
            ],
            [
                'modulo' => 'personal',
                'name' => 'consultar_personal',
                'description' => 'Lista al personal de la clínica (médicos y demás). Filtros por nombre, especialidad, turno y estado.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'texto'        => ['type' => 'string', 'description' => 'Parte del nombre'],
                        'especialidad' => ['type' => 'string'],
                        'turno'        => ['type' => 'string', 'enum' => ['Mañana', 'Tarde', 'Completo', 'Noche']],
                        'estado'       => ['type' => 'string', 'enum' => ['Activo', 'Inactivo']],
                    ],
                    'required' => [],
                ],
            ],
            [
                'modulo' => 'consultorios',
                'name' => 'consultar_consultorios',
                'description' => 'Lista consultorios y su estado (Disponible, Ocupado, Mantenimiento).',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'estado' => ['type' => 'string', 'enum' => ['Disponible', 'Ocupado', 'Mantenimiento']],
                    ],
                    'required' => [],
                ],
            ],
            [
                'modulo' => 'inventario',
                'name' => 'consultar_inventario',
                'description' => 'Busca productos del inventario con su stock. Puede devolver solo los productos con stock bajo, crítico o agotado.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'texto'           => ['type' => 'string', 'description' => 'Nombre o código del producto'],
                        'solo_bajo_stock' => ['type' => 'boolean'],
                    ],
                    'required' => [],
                ],
            ],
            [
                'modulo' => 'facturacion',
                'name' => 'resumen_facturacion',
                'description' => 'Resumen de facturas en un rango de fechas: cantidad y monto total por estado (Pendiente, Pagada, Vencida, Cancelada).',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => $rangoFechas,
                    'required' => ['fecha_inicio', 'fecha_fin'],
                ],
            ],
        ];

        $permitidas = [];
        foreach ($todas as $herramienta) {
            if ($this->puedeVer($herramienta['modulo'])) {
                unset($herramienta['modulo']);
                $permitidas[] = $herramienta;
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
            'resumen_facturacion'    => 'facturacion',
        ];

        if (!isset($modulos[$nombre])) {
            return ['error' => 'Herramienta desconocida.'];
        }

        // Doble verificación: aunque el modelo pida una herramienta, se revisa el permiso.
        if (!$this->puedeVer($modulos[$nombre])) {
            return ['error' => 'El usuario no tiene permiso para consultar este módulo.'];
        }

        if (!$this->user->isSuperAdmin() && !$this->user->clinica_id) {
            return ['error' => 'El usuario no tiene una clínica asignada.'];
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
                'resumen_facturacion'    => $this->resumenFacturacion($args),
            };
        } catch (\Throwable $e) {
            report($e);
            return ['error' => 'No se pudo realizar la consulta.'];
        }
    }

    private function consultarCitas(array $a): array
    {
        $q = DB::table('citas')
            ->join('pacientes', 'pacientes.id', '=', 'citas.paciente_id')
            ->join('personals', 'personals.id', '=', 'citas.personal_id')
            ->leftJoin('consultorios', 'consultorios.id', '=', 'citas.consultorio_id')
            ->whereBetween('citas.fecha', [$this->fecha($a['fecha_inicio'] ?? null), $this->fecha($a['fecha_fin'] ?? null)]);

        $this->filtrarClinica($q, 'citas');

        if (!empty($a['estado'])) {
            $q->where('citas.estado', $a['estado']);
        }
        if (!empty($a['medico'])) {
            $q->where('personals.nombre_completo', 'like', '%' . $a['medico'] . '%');
        }
        if (!empty($a['paciente'])) {
            $q->where(function ($w) use ($a) {
                $w->where('pacientes.nombre_completo', 'like', '%' . $a['paciente'] . '%')
                  ->orWhere('pacientes.codigo', 'like', '%' . $a['paciente'] . '%');
            });
        }

        $total = (clone $q)->count();

        $filas = $q->orderBy('citas.fecha')->orderBy('citas.hora')
            ->limit($this->limite())
            ->get([
                'citas.fecha', 'citas.hora', 'citas.duracion_min', 'citas.tipo_consulta', 'citas.estado',
                'pacientes.codigo as paciente_codigo', 'pacientes.nombre_completo as paciente',
                'personals.nombre_completo as medico', 'consultorios.nombre as consultorio',
            ])
            ->map(fn ($c) => $this->anonimizar((array) $c));

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
        $col = $columnas[$a['agrupar_por'] ?? 'estado'] ?? 'citas.estado';

        $q = DB::table('citas')
            ->join('personals', 'personals.id', '=', 'citas.personal_id')
            ->whereBetween('citas.fecha', [$this->fecha($a['fecha_inicio'] ?? null), $this->fecha($a['fecha_fin'] ?? null)]);

        $this->filtrarClinica($q, 'citas');

        $grupos = $q->groupBy($col)
            ->orderBy($col)
            ->get([DB::raw("$col as grupo"), DB::raw('COUNT(*) as total')]);

        return ['total' => $grupos->sum('total'), 'por_' . ($a['agrupar_por'] ?? 'estado') => $grupos->all()];
    }

    private function buscarPacientes(array $a): array
    {
        $texto = trim($a['texto'] ?? '');

        $q = DB::table('pacientes')
            ->leftJoin('personals', 'personals.id', '=', 'pacientes.medico_tratante_id')
            ->where(function ($w) use ($texto) {
                $w->where('pacientes.nombre_completo', 'like', "%$texto%")
                  ->orWhere('pacientes.codigo', 'like', "%$texto%");
            });

        $this->filtrarClinica($q, 'pacientes');

        if (!empty($a['estado'])) {
            $q->where('pacientes.estado', $a['estado']);
        }

        $filas = $q->orderBy('pacientes.nombre_completo')
            ->limit($this->limite())
            ->get([
                'pacientes.codigo as paciente_codigo', 'pacientes.nombre_completo as paciente',
                'pacientes.estado', 'pacientes.aseguradora', 'personals.nombre_completo as medico_tratante',
                'pacientes.created_at as fecha_registro',
            ])
            ->map(fn ($p) => $this->anonimizar((array) $p));

        return ['encontrados' => $filas->count(), 'pacientes' => $filas->all()];
    }

    private function estadisticasPacientes(array $a): array
    {
        $base = DB::table('pacientes');
        $this->filtrarClinica($base, 'pacientes');

        $resultado = [
            'total'           => (clone $base)->count(),
            'por_estado'      => (clone $base)->groupBy('estado')->get(['estado', DB::raw('COUNT(*) as total')])->all(),
            'por_genero'      => (clone $base)->groupBy('genero')->get(['genero', DB::raw('COUNT(*) as total')])->all(),
            'por_aseguradora' => (clone $base)->groupBy('aseguradora')->orderByDesc('total')->limit(10)
                ->get(['aseguradora', DB::raw('COUNT(*) as total')])->all(),
        ];

        if (!empty($a['fecha_inicio']) && !empty($a['fecha_fin'])) {
            $resultado['registrados_en_rango'] = (clone $base)
                ->whereBetween('created_at', [$this->fecha($a['fecha_inicio']) . ' 00:00:00', $this->fecha($a['fecha_fin']) . ' 23:59:59'])
                ->count();
        }

        return $resultado;
    }

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

        $filas = $q->limit($this->limite() * 3)
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

        return ['consultorios' => $q->orderBy('nombre')->limit($this->limite())->get(['nombre', 'piso', 'estado'])->all()];
    }

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
            $q->where(function ($w) {
                $w->whereIn('productos_inventario.estado', ['Bajo Stock', 'Critico', 'Agotado'])
                  ->orWhereColumn('productos_inventario.stock_actual', '<=', 'productos_inventario.stock_minimo');
            });
        }

        $filas = $q->orderBy('productos_inventario.stock_actual')
            ->limit($this->limite())
            ->get(['productos_inventario.codigo', 'productos_inventario.nombre', 'categorias_inventario.nombre as categoria',
                   'productos_inventario.stock_actual', 'productos_inventario.stock_minimo', 'productos_inventario.estado']);

        return ['encontrados' => $filas->count(), 'productos' => $filas->all()];
    }

    private function resumenFacturacion(array $a): array
    {
        $q = DB::table('facturas')
            ->whereBetween('fecha_emision', [$this->fecha($a['fecha_inicio'] ?? null), $this->fecha($a['fecha_fin'] ?? null)]);

        $this->filtrarClinica($q, 'facturas');

        $grupos = $q->groupBy('estado')
            ->get(['estado', DB::raw('COUNT(*) as cantidad'), DB::raw('SUM(monto_total) as monto')]);

        return [
            'cantidad_total' => $grupos->sum('cantidad'),
            'monto_total'    => round((float) $grupos->sum('monto'), 2),
            'por_estado'     => $grupos->all(),
        ];
    }

    /** Misma lógica que CheckPermission: Super Admin todo, los demás según `permisos`. */
    public function puedeVer(string $clave): bool
    {
        if ($this->user->isSuperAdmin()) {
            return true;
        }

        $modulo = config("chatbot.modulos.$clave");
        if (!$modulo || !$this->user->rol_id) {
            return false;
        }

        return DB::table('permisos')
            ->join('modulos', 'modulos.id', '=', 'permisos.modulo_id')
            ->where('permisos.rol_id', $this->user->rol_id)
            ->where('modulos.nombre', $modulo)
            ->where('permisos.puede_ver', true)
            ->exists();
    }

    private function filtrarClinica(Builder $q, string $tabla): void
    {
        if (!$this->user->isSuperAdmin()) {
            $q->where("$tabla.clinica_id", $this->user->clinica_id);
        }
    }

    private function anonimizar(array $fila): array
    {
        if (config('chatbot.ocultar_nombres_pacientes')) {
            unset($fila['paciente']);
        }
        return $fila;
    }

    private function fecha(?string $valor): string
    {
        try {
            return \Carbon\Carbon::parse($valor ?? 'today')->toDateString();
        } catch (\Throwable) {
            return now()->toDateString();
        }
    }

    private function limite(): int
    {
        return (int) config('chatbot.max_resultados', 50);
    }
}