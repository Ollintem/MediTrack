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
        'nombre_completo',
        'rut',
        'telefono',
        'numero_registro',
        'especialidad_principal',
        'turno',
        'estado',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
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