<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Notificaciones internas por usuario (tabla `notificaciones`).
 */
class Notificacion extends Model
{
    use HasFactory;

    protected $table = 'notificaciones';

    /** Valores válidos de la columna enum `tipo`. */
    public const TIPOS = ['Citas', 'Inventario', 'Facturación', 'Sistema'];

    protected $fillable = [
        'clinica_id',
        'user_id',
        'titulo',
        'mensaje',
        'leida',
        'fecha',
        'referencia_id',
        'tipo',
        'referencia_tipo',
    ];

    protected $casts = [
        'leida' => 'boolean',
        'fecha' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function clinica()
    {
        return $this->belongsTo(Clinica::class, 'clinica_id');
    }

    public function scopeNoLeidas($query)
    {
        return $query->where('leida', false);
    }
}
