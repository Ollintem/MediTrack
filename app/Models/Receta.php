<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receta extends Model
{
    use HasFactory;

    protected $table = 'recetas';

    protected $fillable = [
        'folio',
        'paciente_id',
        'personal_id',
        'fecha_emision',
        'diagnostico',
        'indicaciones_generales',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function personal()
    {
        return $this->belongsTo(Personal::class, 'personal_id');
    }

    public function detalles()
    {
        return $this->hasMany(RecetaDetalle::class, 'receta_id');
    }

    public function signoVital()
    {
        return $this->hasOne(SignosVitales::class, 'paciente_id', 'paciente_id')
                    ->latestOfMany('created_at');
    }
}