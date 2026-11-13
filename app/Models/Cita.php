<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cita extends Model
{
    use HasFactory;

    /** Avisos al médico: nueva cita, reprogramada, cancelada, reasignada o eliminada (App\Services\Notificador). */
    protected static function booted(): void
    {
        static::created(fn (Cita $c) => \App\Services\Notificador::citaCreada($c));
        static::updated(fn (Cita $c) => \App\Services\Notificador::citaActualizada($c));
        static::deleted(fn (Cita $c) => \App\Services\Notificador::citaEliminada($c));
    }

    protected $table = 'citas';

    protected $fillable = [
        'paciente_id', 'personal_id', 'consultorio_id', 'clinica_id', 
        'fecha', 'hora', 'duracion_min', 'motivo', 'tipo_consulta', 'estado'
    ];

    public function paciente() 
    { 
        return $this->belongsTo(Paciente::class); 
    }

    // Definimos ambas para que responda tanto a ->medico() como a ->personal()
    public function medico() 
    { 
        return $this->belongsTo(Personal::class, 'personal_id'); 
    }

    public function personal() 
    { 
        return $this->belongsTo(Personal::class, 'personal_id'); 
    }

    public function consultorio() 
    { 
        return $this->belongsTo(Consultorio::class); 
    }

    public function clinica() 
    { 
        return $this->belongsTo(Clinica::class); 
    }
}