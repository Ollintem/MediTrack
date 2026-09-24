<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
        'stock_minimo',
        'stock_maximo',
        'estado'
    ];

    public function categoria()
    {
        return $this->belongsTo(CategoriaInventario::class, 'categoria_id');
    }

    // Accesor para obtener la cantidad reservada en tickets activos (< 25 min)
    public function getStockReservadoAttribute()
    {
        return DB::table('ticket_detalles')
            ->join('tickets_venta', 'ticket_detalles.ticket_id', '=', 'tickets_venta.id')
            ->where('ticket_detalles.producto_id', $this->id)
            ->where('tickets_venta.status', 'pendiente')
            ->where('tickets_venta.expires_at', '>', now())
            ->sum('ticket_detalles.cantidad');
    }

    // Accesor para el Stock Disponible (número en NEGRITAS)
    public function getStockDisponibleAttribute()
    {
        return max(0, $this->stock_actual - $this->stock_reservado);
    }
}