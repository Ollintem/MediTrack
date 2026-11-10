<?php

namespace App\Models;

use Carbon\CarbonInterface;
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

    /** Días que se pagan por periodo completo: la quincena siempre vale 15 días y el mes 30. */
    public const DIAS_FIJOS = ['QUINCENAL' => 15, 'MENSUAL' => 30];

    /** Días naturales que abarca el periodo. */
    public function dias(): int
    {
        return (int) $this->fecha_inicio->diffInDays($this->fecha_fin) + 1;
    }

    /** Días que se pagan en este periodo (ver diasAPagar). */
    public function diasPagables(): int
    {
        return self::diasAPagar($this->tipo, $this->fecha_inicio, $this->fecha_fin);
    }

    /**
     * Días que se pagan según el tipo de periodo:
     *  - QUINCENAL: 15 fijos (sin importar si la quincena tiene 13, 15 o 16 días de calendario).
     *  - MENSUAL:   30 fijos (sin importar si el mes tiene 28, 30 o 31 días).
     *  - SEMANAL:   los días del rango (7 en una semana completa).
     *  - ESPECIAL:  0 (el monto se captura a mano).
     * Si el rango es más corto que una quincena o un mes completo (por ejemplo, alguien que entró
     * a mitad de quincena), se pagan los días reales del rango, sin pasar de 15 o 30.
     * La misma regla está en resources/views/personal/nomina/index.blade.php (diasAPagar) para la vista previa.
     */
    public static function diasAPagar(string $tipo, CarbonInterface $inicio, CarbonInterface $fin): int
    {
        $tipo = strtoupper($tipo);
        $calendario = (int) $inicio->copy()->startOfDay()->diffInDays($fin->copy()->startOfDay()) + 1;

        if ($tipo === 'ESPECIAL') {
            return 0;
        }

        if (!isset(self::DIAS_FIJOS[$tipo])) {
            return $calendario;
        }

        $mismoMes = $inicio->isSameMonth($fin);
        $completo = $tipo === 'QUINCENAL'
            ? $mismoMes && (($inicio->day === 1 && $fin->day === 15) || ($inicio->day === 16 && $fin->isLastOfMonth()))
            : $mismoMes && $inicio->day === 1 && $fin->isLastOfMonth();

        return $completo ? self::DIAS_FIJOS[$tipo] : min($calendario, self::DIAS_FIJOS[$tipo]);
    }

    /** ¿Tiene al menos un recibo pagado? Entonces no se puede eliminar sin revertir antes. */
    public function tienePagos(): bool
    {
        return $this->recibos()->where('estado', 'Pagado')->exists();
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