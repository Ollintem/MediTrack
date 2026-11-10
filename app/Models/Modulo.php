<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Modulo extends Model
{
    use HasFactory;

    protected $table = 'modulos';
    protected $fillable = ['nombre'];

    /**
     * Catálogo único de módulos con permisos (los que revisan las rutas "permiso:X" y el menú).
     * Grupo => [Nombre => [ícono, descripción]]. Lo usan la pantalla de Roles y el seeder.
     */
    public const CATALOGO = [
        'Gestión médica' => [
            'Pacientes'    => ['bi-people', 'Expedientes y credenciales'],
            'Citas'        => ['bi-calendar2-week', 'Agenda de la clínica'],
            'Consultas'    => ['bi-clipboard2-pulse', 'Atención del médico'],
            'Consultorios' => ['bi-hospital', 'Plano y estado de las salas'],
            'Recetas'      => ['bi-file-earmark-medical', 'Emisión e impresión'],
        ],
        'Farmacia y finanzas' => [
            'POS'          => ['bi-shop', 'Venta y entrega de pedidos'],
            'Caja Central' => ['bi-cash-coin', 'Cobros, apertura y corte'],
            'Inventario'   => ['bi-box-seam', 'Existencias, lotes y surtido'],
            'Proveedores'  => ['bi-truck', 'Compras y laboratorios'],
        ],
        'Administración' => [
            'Personal'      => ['bi-person-badge', 'Plantilla, accesos y nómina'],
            'Roles'         => ['bi-shield-lock', 'Esta pantalla'],
            'Reportes'      => ['bi-bar-chart-line', 'Ingresos, egresos y utilidad'],
            'Configuración' => ['bi-gear', 'Datos de la clínica y cuenta'],
            'Bitácora'      => ['bi-journal-text', 'Historial de movimientos'],
        ],
    ];

    /** Nombres del catálogo en orden de menú. */
    public static function nombres(): array
    {
        return array_merge(...array_map('array_keys', array_values(self::CATALOGO)));
    }

    /**
     * Crea los módulos del catálogo que falten (sin duplicar por mayúsculas/acentos escritos igual).
     * Nunca borra módulos: borrar uno eliminaría en cascada sus permisos en todos los roles.
     */
    public static function sincronizar(): void
    {
        $existentes = self::pluck('nombre')->map(fn ($n) => Rol::normalizar($n))->all();

        foreach (self::nombres() as $nombre) {
            if (!in_array(Rol::normalizar($nombre), $existentes, true)) {
                self::create(['nombre' => $nombre]);
            }
        }
    }

    public function permisos()
    {
        return $this->hasMany(Permiso::class, 'modulo_id');
    }
}
