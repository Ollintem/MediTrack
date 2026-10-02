<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    use HasFactory;

    protected $table = 'proveedores';

    protected $fillable = [
        'razon_social',
        'nombre_comercial',
        'rut_rfc',
        'categoria',
        'contacto_nombre',
        'telefono',
        'email',
        'direccion',
        'dias_credito',
        'banco',
        'cuenta_bancaria',
        'estado',
        'notas',
    ];

    /**
     * Scope para filtrar solo proveedores activos.
     */
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }
}