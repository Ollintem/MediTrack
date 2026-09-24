<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clinica extends Model
{
    use HasFactory;

    protected $table = 'clinicas';

    protected $fillable = [
        'nombre',
        'rut_empresa',
        'direccion',
        'telefono',
        'confirmacion_auto_citas',
        'recordatorio_pago_email',
        'email',
        'estado',
    ];

    public function personals()
    {
        return $this->hasMany(Personal::class, 'clinica_id');
    }
}