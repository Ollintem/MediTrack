<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PacienteMedicamento extends Model
{
    protected $table = 'paciente_medicamentos';
    protected $fillable = ['paciente_id', 'nombre', 'dosis'];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }
}