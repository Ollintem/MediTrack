<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReciboNomina extends Model
{
    protected $table = 'recibos_nomina';

    public const METODOS = ['EFECTIVO', 'TRANSFERENCIA', 'CHEQUE'];

    protected $fillable = [
        'periodo_id', 'personal_id',
        'salario_diario', 'dias_trabajados', 'sueldo_base', 'horas_extra', 'bonos',
        'isr', 'imss', 'otras_deducciones',
        'total_percepciones', 'total_deducciones', 'neto', 'notas',
        'estado', 'fecha_pago', 'metodo_pago', 'referencia', 'pagado_por',
    ];

    protected $casts = [
        'salario_diario'     => 'float',
        'dias_trabajados'    => 'float',
        'sueldo_base'        => 'float',
        'horas_extra'        => 'float',
        'bonos'              => 'float',
        'isr'                => 'float',
        'imss'               => 'float',
        'otras_deducciones'  => 'float',
        'total_percepciones' => 'float',
        'total_deducciones'  => 'float',
        'neto'               => 'float',
        'fecha_pago'         => 'date',
    ];

    public function periodo()
    {
        return $this->belongsTo(PeriodoNomina::class, 'periodo_id');
    }

    public function personal()
    {
        return $this->belongsTo(Personal::class, 'personal_id');
    }

    public function estaPagado(): bool
    {
        return $this->estado === 'Pagado';
    }

    /** Recalcula sueldo base y totales a partir de los montos capturados. */
    public function recalcular(): self
    {
        $this->sueldo_base        = round($this->salario_diario * $this->dias_trabajados, 2);
        $this->total_percepciones = round($this->sueldo_base + $this->horas_extra + $this->bonos, 2);
        $this->total_deducciones  = round($this->isr + $this->imss + $this->otras_deducciones, 2);
        $this->neto               = round($this->total_percepciones - $this->total_deducciones, 2);

        return $this;
    }
}