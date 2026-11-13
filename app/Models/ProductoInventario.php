<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProductoInventario extends Model
{
    use HasFactory;

    /** Aviso de stock bajo o agotado cuando el estado cruza ese límite (App\Services\Notificador). */
    protected static function booted(): void
    {
        static::updated(fn (ProductoInventario $p) => \App\Services\Notificador::stockCambio($p));
    }

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

    // Columna auxiliar de ->conStock(): no se expone en el JSON (ya sale como stock_reservado)
    protected $hidden = ['stock_reservado_sql'];

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
    public function getStockReservadoAttribute(): int
    {
        // Si el producto se cargó con ->conStock(), el dato ya viene calculado en la misma consulta
        if (array_key_exists('stock_reservado_sql', $this->attributes)) {
            return (int) $this->attributes['stock_reservado_sql'];
        }

        // Respaldo: una consulta para este producto (modelos cargados sin ->conStock())
        return (int) DB::table('ticket_detalles')
            ->join('tickets_venta', 'ticket_detalles.ticket_id', '=', 'tickets_venta.id')
            ->where('ticket_detalles.producto_id', $this->id)
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->where('tickets_venta.status', 'pendiente')
                      ->where('tickets_venta.expires_at', '>', now('America/Mexico_City'));
                })
                ->orWhere('tickets_venta.status', 'pagado');
            })
            ->sum('ticket_detalles.cantidad');
    }

    /**
     * Piezas apartadas en SQL (misma regla que getStockReservadoAttribute).
     * Devuelve [expresión, bindings].
     */
    public static function sqlReservado(string $tabla = 'productos_inventario'): array
    {
        $sql = "COALESCE(("
             . "SELECT SUM(td.cantidad) FROM ticket_detalles td"
             . " INNER JOIN tickets_venta tv ON tv.id = td.ticket_id"
             . " WHERE td.producto_id = {$tabla}.id"
             . " AND ((tv.status = 'pendiente' AND tv.expires_at > ?) OR tv.status = 'pagado')"
             . "), 0)";

        return [$sql, [now('America/Mexico_City')->toDateTimeString()]];
    }

    /**
     * La misma fórmula que getStockDisponibleAttribute(), pero en SQL, para
     * usarla en consultas (DB::table, filtros, orden) sin una query por producto.
     * Devuelve [expresión, bindings]. La columna física 'stock_disponible' NO se usa.
     */
    public static function sqlDisponible(string $tabla = 'productos_inventario'): array
    {
        [$reservado, $bindings] = self::sqlReservado($tabla);

        return ["GREATEST(0, CAST(COALESCE({$tabla}.stock_actual, 0) AS SIGNED) - {$reservado})", $bindings];
    }

    /**
     * Carga el stock reservado en la MISMA consulta (subconsulta por fila).
     * Antes, cada producto hacía su propia consulta al convertirse a JSON o al leer
     * stock_disponible: 100 productos = 100+ consultas extra en POS, Inventario, Caja y Dashboard.
     *
     *   ProductoInventario::conStock()->get();
     *   TicketVenta::with(['detalles.producto' => ProductoInventario::cargarConStock()])
     *
     * No usar junto con lockForUpdate (bloquearía también filas de tickets).
     */
    public function scopeConStock($query)
    {
        $tabla = $this->getTable();
        [$sql, $bindings] = self::sqlReservado($tabla);

        if (is_null($query->getQuery()->columns)) {
            $query->select("{$tabla}.*");
        }

        return $query->selectRaw("{$sql} as stock_reservado_sql", $bindings);
    }

    /** Restricción para eager loading: ['detalles.producto' => ProductoInventario::cargarConStock()] */
    public static function cargarConStock(): \Closure
    {
        return fn ($query) => $query->conStock();
    }

    /**
     * Estado visual según el stock DISPONIBLE (físico menos lo apartado).
     * Es la única regla: la usan inventario, surtido y entregas del POS.
     */
    public function recalcularEstado(): self
    {
        $disponible = $this->stock_disponible;

        $this->estado = match (true) {
            $disponible <= 0                         => 'Agotado',
            $disponible <= (int) $this->stock_minimo => 'Bajo Stock',
            default                                  => 'Normal',
        };

        return $this;
    }

    /**
     * Stock disponible real para venta inmediata (Stock Físico - Piezas Comprometidas)
     */
    public function getStockDisponibleAttribute(): int
    {
        $fisico = (int) ($this->stock_actual ?? 0);
        return max(0, $fisico - $this->stock_reservado);
    }
}