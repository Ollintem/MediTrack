<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Avisos internos por usuario (campana del encabezado). Los crea App\Services\Notificador.
 */
class Notificacion extends Model
{
    use HasFactory;

    protected $table = 'notificaciones';

    /** Tipos de aviso (definen el ícono y color en la campana). */
    public const TIPOS = ['Citas', 'Inventario', 'Caja', 'Nómina', 'Sistema'];

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
        'url',
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

    /** Forma que consume la campana. */
    public function paraVista(): array
    {
        return [
            'id'      => $this->id,
            'tipo'    => $this->tipo,
            'titulo'  => $this->titulo,
            'mensaje' => $this->mensaje,
            'leida'   => (bool) $this->leida,
            'fecha'   => optional($this->fecha ?? $this->created_at)->toIso8601String(),
            'url'     => $this->url,
        ];
    }
}
