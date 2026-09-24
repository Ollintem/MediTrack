<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoInventario extends Model
{
    use HasFactory;

    protected $table = 'productos_inventario';

    protected $fillable = [
        'clinica_id',
        'categoria_id',
        'codigo',
        'nombre',
        'descripcion',
        'precio_compra',
        'precio_venta',
        'stock_actual',
        'stock_disponible',
        'stock_minimo',
        'stock_maximo',
        'estado',
    ];

    public function categoria()
    {
        return $this->belongsTo(CategoriaInventario::class, 'categoria_id');
    }
}