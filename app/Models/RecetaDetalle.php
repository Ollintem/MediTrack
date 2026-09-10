<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecetaDetalle extends Model
{
    use HasFactory;

    protected $table = 'receta_detalles';

    protected $fillable = [
        'receta_id',
        'medicamento',
        'dosis',
        'frecuencia',
        'duracion',
    ];

    public function receta()
    {
        return $this->belongsTo(Receta::class, 'receta_id');
    }
}