<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Catálogo de especialidades médicas (tabla `especialidades`).
 * Se relaciona con el personal mediante la tabla pivote `personal_especialidad`.
 */
class Especialidad extends Model
{
    use HasFactory;

    protected $table = 'especialidades';

    protected $fillable = ['nombre'];

    public function personal()
    {
        return $this->belongsToMany(Personal::class, 'personal_especialidad', 'especialidad_id', 'personal_id')
            ->withTimestamps();
    }
}
