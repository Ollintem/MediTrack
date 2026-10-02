<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketDetalle extends Model
{
    use HasFactory;

    protected $table = 'ticket_detalles';

    protected $fillable = [
        'ticket_id',
        'producto_id',
        'cantidad',
        'precio_unitario',
        'subtotal'
    ];

    public function ticket()
    {
        return $this->belongsTo(TicketVenta::class, 'ticket_id');
    }

    public function producto()
    {
        return $this->belongsTo(ProductoInventario::class, 'producto_id');
    }
}