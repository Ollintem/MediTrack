<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pago extraordinario de un recibo de nómina (horas extra con su concepto).
 * monto = horas × cuota; la cuota es la declarada en la ficha del empleado al capturarlo.
 */
class ReciboExtra extends Model
{
    protected $table = 'recibo_extras';

    protected $fillable = ['recibo_id', 'concepto', 'horas', 'cuota', 'monto'];

    protected $casts = [
        'horas' => 'float',
        'cuota' => 'float',
        'monto' => 'float',
    ];

    public function recibo()
    {
        return $this->belongsTo(ReciboNomina::class, 'recibo_id');
    }
}
