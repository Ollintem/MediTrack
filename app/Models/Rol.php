<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    use HasFactory;

    protected $table = 'roles';

    protected $fillable = [
        'nombre',
    ];

    /**
     * Relación con el modelo Permiso.
     */
    public function permisos()
    {
        return $this->hasMany(Permiso::class, 'rol_id');
    }
}