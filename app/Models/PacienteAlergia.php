<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PacienteAlergia extends Model
{
    protected $table = 'paciente_alergias';
    protected $fillable = ['paciente_id', 'descripcion'];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }
}