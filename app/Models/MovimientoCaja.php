<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientoCaja extends Model
{
    use HasFactory;

    protected $table = 'movimientos_caja';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'tipo',        // 'Ingreso', 'Egreso'
        'concepto',
        'monto',
        'metodo_pago', // 'Efectivo', 'Tarjeta', 'Transferencia'
    ];

    /** Conceptos con que CajaController registra el fondo inicial y el corte (no son ventas). */
    public const CONCEPTO_APERTURA = 'APERTURA DE CAJA CENTRAL - FONDO INICIAL';
    public const PREFIJO_CIERRE    = 'CIERRE DE CAJA CENTRAL';

    /** Solo dinero de operaciones: excluye el fondo de apertura y el corte de cierre. */
    public function scopeOperaciones($query)
    {
        return $query->where('concepto', '!=', self::CONCEPTO_APERTURA)
                     ->where('concepto', 'not like', self::PREFIJO_CIERRE . '%');
    }

    // Relación con el ticket de venta (opcional)
    public function ticket()
    {
        return $this->belongsTo(TicketVenta::class, 'ticket_id');
    }

    // Relación con el cajero / usuario que registró el movimiento
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
