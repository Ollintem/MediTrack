<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientoInventario extends Model
{
    use HasFactory;

    protected $table = 'movimientos_inventario';

    protected $fillable = [
        'producto_id',
        'personal_id',
        'cantidad',
        'tipo',
        'motivo'
    ];

    public function producto()
    {
        return $this->belongsTo(ProductoInventario::class, 'producto_id');
    }

    public function personal()
    {
        return $this->belongsTo(User::class, 'personal_id');
    }
}