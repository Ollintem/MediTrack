<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Detecta si cambió algo en un módulo para que la pantalla abierta se actualice sola.
 *
 * Por cada tabla del módulo se toma COUNT(*) y MAX(updated_at): si alguien agrega, edita o borra
 * un registro, la "firma" cambia. Son consultas mínimas (no traen los datos).
 */
class Cambios
{
    /**
     * Módulo de pantalla => [permiso que se revisa, tablas que lo afectan].
     * La pantalla se recarga sola cuando cambian; si estás capturando algo, espera a que termines.
     * Cada pantalla puede decir que está ocupada con window.mtOcupado (p. ej. el POS con productos en el carrito).
     */
    public const MODULOS = [
        'citas'        => ['Citas',        ['citas', 'pacientes', 'consultorios', 'personals']],
        'consultas'    => ['Consultas',    ['citas', 'consultas', 'signos_vitales']],
        'pacientes'    => ['Pacientes',    ['pacientes', 'paciente_alergias', 'paciente_condiciones', 'paciente_medicamentos']],
        'recetas'      => ['Recetas',      ['recetas', 'receta_detalles']],
        'pos'          => ['POS',          ['tickets_venta', 'ticket_detalles', 'productos_inventario']],   // espera a que el carrito esté vacío
        'caja'         => ['Caja Central', ['tickets_venta', 'movimientos_caja']],
        'inventario'   => ['Inventario',   ['productos_inventario', 'categorias_inventario', 'movimientos_inventario', 'tickets_venta']],
        'proveedores'  => ['Proveedores',  ['proveedores']],
        'nomina'       => ['Personal',     ['periodos_nomina', 'recibos_nomina', 'recibo_extras', 'personals']],
        'personal'     => ['Personal',     ['personals', 'users', 'roles']],
        'reportes'     => ['Reportes',     ['movimientos_caja', 'movimientos_inventario', 'recibos_nomina']],
        'bitacora'     => ['Bitácora',     ['bitacoras']],
    ];

    /**
     * Nombre de ruta => módulo. El orden importa: "personal.nomina.*" va antes que "personal.*".
     * El Dashboard y Consultorios no están: ya se actualizan solos con su propio mecanismo.
     */
    public const RUTAS = [
        'personal.nomina.*' => 'nomina',
        'nomina.*'          => 'nomina',
        'personal.*'        => 'personal',
        'citas.*'           => 'citas',
        'consultas.*'       => 'consultas',
        'pacientes.*'       => 'pacientes',
        'recetas.*'         => 'recetas',
        'pos.*'             => 'pos',
        'caja.*'            => 'caja',
        'inventario.*'      => 'inventario',
        'proveedores.*'     => 'proveedores',
        'reportes.*'        => 'reportes',
        'bitacora.*'        => 'bitacora',
    ];

    /** Módulo de la página actual (o null si no se vigila). */
    public static function moduloDeRuta(?string $ruta): ?string
    {
        if (!$ruta) {
            return null;
        }
        foreach (self::RUTAS as $patron => $modulo) {
            if (\Illuminate\Support\Str::is($patron, $ruta)) {
                return $modulo;
            }
        }

        return null;
    }

    /** Firma del módulo: cambia cuando cambia cualquier registro de sus tablas. */
    public static function firma(string $modulo): string
    {
        $partes = [];
        foreach (self::MODULOS[$modulo][1] as $tabla) {
            $fila = DB::table($tabla)->selectRaw('COUNT(*) as n, MAX(updated_at) as u')->first();
            $partes[] = $tabla . ':' . $fila->n . ':' . $fila->u;
        }

        return md5(implode('|', $partes));
    }
}
