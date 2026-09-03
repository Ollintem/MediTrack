<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Personal extends Model
{
    use HasFactory;

    protected $table = 'personals';

    protected $fillable = [
        'usuario_id',
        'clinica_id',
        'fecha_nacimiento',
        'fecha_ingreso',
        'rut',
        'nombre_completo',
        'genero',
        'telefono',
        'correo',
        'direccion',
        'rol_profesional',
        'numero_registro',
        'turno',
        'estado',
        'foto_url'
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function clinica()
    {
        return $this->belongsTo(Clinica::class, 'clinica_id');
    }

    public function especialidades()
    {
        return $this->belongsToMany(Especialidad::class, 'personal_especialidad', 'personal_id', 'especialidad_id');
    }
}