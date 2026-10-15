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

    // Incluir automáticamente las propiedades calculadas en JSON (AJAX / Alpine.js)
    protected $appends = ['stock_reservado', 'stock_disponible'];

    /**
     * Relación con CategoriaInventario
     */
    public function categoria()
    {
        return $this->belongsTo(CategoriaInventario::class, 'categoria_id');
    }

    /**
     * Calcula las piezas apartadas o comprometidas:
     * - Tickets 'pendiente' dentro de la ventana de 25 minutos.
     * - Tickets 'pagado' en espera de ser surtidos/entregados.
     */
    public function getStockReservadoAttribute()
    {
        return DB::table('ticket_detalles')
            ->join('tickets_venta', 'ticket_detalles.ticket_id', '=', 'tickets_venta.id')
            ->where('ticket_detalles.producto_id', $this->id)
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->where('tickets_venta.status', 'pendiente')
                      ->where('tickets_venta.expires_at', '>', now('America/Mexico_City'));
                })
                ->orWhere('tickets_venta.status', 'pagado');
            })
            ->sum('ticket_detalles.cantidad') ?? 0;
    }

    /**
     * Stock disponible real para venta inmediata (Stock Físico - Piezas Comprometidas)
     */
    public function getStockDisponibleAttribute()
    {
        $fisico = $this->stock_actual ?? 0;
        return max(0, $fisico - $this->stock_reservado);
    }
}