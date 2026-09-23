<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\PacienteAlergia;
use App\Models\PacienteCondicion;
use App\Models\PacienteMedicamento;

class Paciente extends Model
{
    protected $fillable = [
        'clinica_id',
        'medico_tratante_id',
        'codigo',
        'primer_nombre',
        'apellido_paterno',
        'apellido_materno',
        'rut',
        'fecha_nacimiento',
        'genero',
        'estado_civil',
        'nacionalidad',
        'grupo_sanguineo',
        'telefono',
        'celular',
        'email',
        'direccion',
        'contacto_emerg_nombre',
        'contacto_emerg_relacion',
        'contacto_emerg_telefono',
        'aseguradora',
        'numero_poliza',
        'vigencia_poliza',
        'acepta_aviso_privacidad',
        'estado',
        'foto_url',
    ];

    protected $appends = ['nombre'];

    public function getNombreAttribute(): string
    {
        return trim("{$this->primer_nombre} {$this->apellido_paterno} {$this->apellido_materno}");
    }

    // RELACIONES APUNTANDO A TUS MODELOS EXACTOS
    public function alergias()
    {
        return $this->hasMany(PacienteAlergia::class);
    }

    public function condiciones()
    {
        return $this->hasMany(PacienteCondicion::class);
    }

    public function medicamentos()
    {
        return $this->hasMany(PacienteMedicamento::class);
    }
}