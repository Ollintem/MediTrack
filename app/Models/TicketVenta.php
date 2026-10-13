<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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
        'monto_total' => 'decimal:2',
    ];

    // Asegura que las propiedades dinámicas calculadas se incluyan en las respuestas JSON
    protected $appends = ['status_real', 'esta_expirado'];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES ELOQUENT
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | ACCESORES Y PROPIEDADES DINÁMICAS
    |--------------------------------------------------------------------------
    */

    /**
     * Evalúa dinámicamente si el ticket superó la ventana de 25 minutos.
     * Retorna 'expirado' en tiempo real cuando status es 'pendiente' pero ya venció la hora.
     */
    public function getStatusAttribute($value)
    {
        if ($value === 'pendiente' && $this->expires_at && Carbon::now('America/Mexico_City')->greaterThan($this->expires_at)) {
            return 'expirado';
        }
        return $value;
    }

    /**
     * Devuelve el estado directo calculado sin alterar la propiedad original de la BD.
     */
    public function getStatusRealAttribute()
    {
        return $this->status;
    }

    /**
     * Indica mediante verdadero/falso si la reserva de stock del ticket venció.
     */
    public function getEstaExpiradoAttribute()
    {
        if (!$this->expires_at) {
            return false;
        }
        return Carbon::now('America/Mexico_City')->greaterThan($this->expires_at);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES DE BÚSQUEDA
    |--------------------------------------------------------------------------
    */

    /**
     * Scope para consultar únicamente tickets 'pendiente' que siguen vigentes (< 25 min).
     */
    public function scopeVigentes($query)
    {
        return $query->where('status', 'pendiente')
                     ->where('expires_at', '>', Carbon::now('America/Mexico_City'));
    }

    /**
     * Scope para consultar tickets que han vencido sus 25 minutos.
     */
    public function scopeExpirados($query)
    {
        return $query->where('status', 'pendiente')
                     ->where('expires_at', '<=', Carbon::now('America/Mexico_City'));
    }
}