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
