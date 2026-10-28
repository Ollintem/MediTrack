<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Personal extends Model
{
    use HasFactory;

    protected $table = 'personals';

    protected $fillable = [
        'user_id',
        'clinica_id',
        'fecha_nacimiento',
        'fecha_ingreso',
        'rut',
        'nombre_completo',
        'genero',
        'telefono',
        'numero_registro',
        'direccion',
        'rut_profesional',
        'especialidad_principal',
        'turno',
        'estado',
        'foto_url',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'fecha_ingreso'    => 'date',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Alias para compatibilidad con llamadas que busquen ->user
    public function user()
    {
        return $this->usuario();
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