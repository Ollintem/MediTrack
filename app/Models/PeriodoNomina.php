<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodoNomina extends Model
{
    protected $table = 'periodos_nomina';

    /** SEMANAL, QUINCENAL y MENSUAL son periodos normales; ESPECIAL es un pago fuera de calendario (adelanto, finiquito, bono…). */
    public const TIPOS = ['SEMANAL', 'QUINCENAL', 'MENSUAL', 'ESPECIAL'];

    protected $fillable = ['tipo', 'fecha_inicio', 'fecha_fin', 'fecha_pago', 'estado', 'notas', 'creado_por'];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
        'fecha_pago'   => 'date',
    ];

    public function recibos()
    {
        return $this->hasMany(ReciboNomina::class, 'periodo_id');
    }

    public function estaAbierto(): bool
    {
        return $this->estado === 'Abierto';
    }

    /** Días naturales que abarca el periodo. */
    public function dias(): int
    {
        return $this->fecha_inicio->diffInDays($this->fecha_fin) + 1;
    }

    /**
     * Ajusta el estado según los pagos individuales:
     * todos pagados → Pagado; si se revierte un pago → vuelve a Abierto.
     * "Cerrado" (bloqueado por el usuario) se respeta mientras haya pendientes.
     */
    public function sincronizarEstado(): self
    {
        $total   = $this->recibos()->count();
        $pagados = $this->recibos()->where('estado', 'Pagado')->count();

        if ($total > 0 && $pagados === $total) {
            $this->estado = 'Pagado';
            $this->fecha_pago = $this->recibos()->max('fecha_pago') ?: now()->toDateString();
        } elseif ($this->estado === 'Pagado') {
            $this->estado = 'Abierto';
        }

        $this->save();

        return $this;
    }
}