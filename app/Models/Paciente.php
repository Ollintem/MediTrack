<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paciente extends Model
{
    protected $table = 'pacientes';

    protected $fillable = [
        'clinica_id',
        'medico_tratante_id',
        'codigo',
        'nombre_completo',
        'rut',
        'fecha_nacimiento',
        'genero',
        'telefono',
        'email',
        'estado'
    ];

    public function getEdadCalculadaAttribute()
{
    if (!empty($this->edad)) {
        return $this->edad . ' años';
    }

    if (!empty($this->fecha_nacimiento)) {
        return \Carbon\Carbon::parse($this->fecha_nacimiento)->age . ' años';
    }

    return 'N/A';
}

    // Accessor para que $paciente->nombre devuelva $paciente->nombre_completo en las vistas
    public function getNombreAttribute()
    {
        return $this->nombre_completo;
    }

    public function alergias()
    {
        return $this->hasMany(PacienteAlergia::class, 'paciente_id');
    }

    public function condiciones()
    {
        return $this->hasMany(PacienteCondicion::class, 'paciente_id');
    }

    public function medicamentos()
    {
        return $this->hasMany(PacienteMedicamento::class, 'paciente_id');
    }
}