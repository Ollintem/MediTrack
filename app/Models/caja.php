<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Caja extends Model
{
    use HasFactory;

    protected $table = 'cajas';

    protected $fillable = [
        'user_id',
        'monto_inicial',
        'monto_final',
        'total_efectivo',
        'total_tarjeta',
        'total_transferencia',
        'total_ventas',
        'status',
        'observaciones',
        'observaciones_cierre',
        'fecha_apertura',
        'fecha_cierre',
    ];

    protected $casts = [
        'fecha_apertura'      => 'datetime',
        'fecha_cierre'        => 'datetime',
        'monto_inicial'       => 'float',
        'monto_final'         => 'float',
        'total_efectivo'      => 'float',
        'total_tarjeta'       => 'float',
        'total_transferencia' => 'float',
        'total_ventas'        => 'float',
    ];

    // Relación con el Cajero / Operador
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relación con los movimientos registrados durante esta sesión de caja
    public function movimientos()
    {
        return $this->hasMany(MovimientoCaja::class, 'caja_id');
    }

    /**
     * Calcula los subtotales del turno divididos por método de pago
     */
    public function obtenerTotales()
    {
        $ingresos = $this->movimientos()->where('tipo', 'Ingreso')->get();
        $egresos = $this->movimientos()->where('tipo', 'Egreso')->where('metodo_pago', 'Efectivo')->sum('monto');

        $efectivo = $ingresos->where('metodo_pago', 'Efectivo')->sum('monto') - $egresos;
        $tarjeta = $ingresos->where('metodo_pago', 'Tarjeta')->sum('monto');
        $transferencia = $ingresos->where('metodo_pago', 'Transferencia')->sum('monto');

        return [
            'efectivo'          => $efectivo,
            'tarjeta'           => $tarjeta,
            'transferencia'     => $transferencia,
            'total_digital'     => $tarjeta + $transferencia,
            'efectivo_esperado' => $this->monto_inicial + $efectivo,
            'total_general'     => $efectivo + $tarjeta + $transferencia,
        ];
    }
}