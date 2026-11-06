<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consulta extends Model
{
    use HasFactory;

    protected $table = 'consultas';

    /** Columnas reales de la tabla `consultas` (antes listaba fecha_consulta, motivo, diagnostico y observaciones, que no existen). */
    protected $fillable = [
        'cita_id',
        'paciente_id',
        'personal_id',
        'fecha',
        'exploracion_fisica',
        'cabeza_cuello',
        'torax',
        'abdomen',
        'extremidades',
        'piel_faneras',
        'neurologico',
        'notas_generales',
        'estado',
    ];

    protected $casts = [
        'fecha' => 'datetime',
    ];

    public function cita()
    {
        return $this->belongsTo(Cita::class, 'cita_id');
    }

    public function signosVitales()
    {
        return $this->hasMany(SignosVitales::class, 'consulta_id');
    }

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function personal()
    {
        return $this->belongsTo(Personal::class, 'personal_id');
    }
}