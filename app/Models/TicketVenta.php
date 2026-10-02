<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketVenta extends Model
{
    use HasFactory;

    protected $table = 'tickets_venta';

    protected $fillable = [
        'codigo_ticket',
        'paciente_id',
        'user_id',
        'monto_total',
        'status', // 'pendiente', 'pagado', 'entregado', 'expirado', 'cancelado'
        'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    // Relaciones
    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function vendedor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function detalles()
    {
        return $this->hasMany(TicketDetalle::class, 'ticket_id');
    }

    // Scope para evaluar si el ticket está expirado o activo
    public function scopeVigentes($query)
    {
        return $query->where('status', 'pendiente')
                     ->where('expires_at', '>', now());
    }
}