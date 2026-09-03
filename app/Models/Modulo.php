<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Modulo extends Model
{
    use HasFactory;

    protected $table = 'modulos';
    protected $fillable = ['nombre'];

    public function permisos()
    {
        return $this->hasMany(Permiso::class, 'modulo_id');
    }
}