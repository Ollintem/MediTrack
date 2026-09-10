<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PacienteCondicion extends Model
{
    protected $table = 'paciente_condiciones';

    protected $fillable = [
        'paciente_id',
        'nombre',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }
}