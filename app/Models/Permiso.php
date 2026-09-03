<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permiso extends Model
{
    use HasFactory;

    protected $table = 'permisos';
    protected $fillable = [
        'rol_id',
        'modulo_id',
        'puede_ver',
        'puede_crear',
        'puede_editar',
        'puede_eliminar'
    ];

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    public function modulo()
    {
        return $this->belongsTo(Modulo::class, 'modulo_id');
    }
}