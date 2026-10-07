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

    // Asegura que estas propiedades calculadas se incluyan en las respuestas JSON
    protected $appends = ['stock_reservado', 'stock_disponible'];

    // Guarda el cálculo para no repetir la consulta en el mismo request
    protected $stockReservadoCache = null;

    public function categoria()
    {
        return $this->belongsTo(CategoriaInventario::class, 'categoria_id');
    }

    /**
     * Calcula la cantidad de piezas 'flotantes' reservadas en tickets 'pendiente' vigentes (< 25 min)
     */
    public function getStockReservadoAttribute()
    {
        if ($this->stockReservadoCache === null) {
            $this->stockReservadoCache = (int) DB::table('ticket_detalles')
                ->join('tickets_venta', 'ticket_detalles.ticket_id', '=', 'tickets_venta.id')
                ->where('ticket_detalles.producto_id', $this->id)
                ->where('tickets_venta.status', 'pendiente')
                ->where('tickets_venta.expires_at', '>', now())
                ->sum('ticket_detalles.cantidad');
        }

        return $this->stockReservadoCache;
    }

    /**
     * Calcula el stock disponible vendible para la Terminal POS
     */
    public function getStockDisponibleAttribute()
    {
        $actual = $this->stock_actual ?? 0;
        return max(0, $actual - $this->stock_reservado);
    }
}