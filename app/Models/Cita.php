<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cita extends Model
{
    use HasFactory;

    protected $table = 'citas';

    protected $fillable = [
        'paciente_id', 'personal_id', 'consultorio_id', 'clinica_id', 
        'fecha', 'hora', 'duracion_min', 'motivo', 'tipo_consulta', 'estado'
    ];

    public function paciente() { return $this->belongsTo(Paciente::class); }
    public function medico() { return $this->belongsTo(Personal::class, 'personal_id'); }
    public function consultorio() { return $this->belongsTo(Consultorio::class); }
    public function clinica() { return $this->belongsTo(Clinica::class); }
}