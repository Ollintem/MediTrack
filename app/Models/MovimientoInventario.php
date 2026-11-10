<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientoInventario extends Model
{
    use HasFactory;

    protected $table = 'movimientos_inventario';

    /** Valores válidos de la columna enum `tipo`. */
    public const TIPOS = ['Entrada', 'Salida', 'Ajuste'];

    protected $fillable = [
        'producto_id',
        'personal_id',
        'user_id',
        'ticket_id',
        'cantidad',
        'costo_unitario',
        'tipo',
        'motivo',
        'referencia',
        'lote',
    ];

    public function producto()
    {
        return $this->belongsTo(ProductoInventario::class, 'producto_id');
    }

    /** La llave foránea personal_id apunta a la tabla `personals` (antes se relacionaba por error con User). */
    public function personal()
    {
        return $this->belongsTo(Personal::class, 'personal_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function ticket()
    {
        return $this->belongsTo(TicketVenta::class, 'ticket_id');
    }

    /** Motivo con que se registra la existencia inicial al dar de alta un producto (no es una compra del periodo). */
    public const MOTIVO_INICIAL = 'INVENTARIO INICIAL';

    protected $casts = [
        'costo_unitario' => 'float',
    ];

    /**
     * Registra un movimiento con el usuario en sesión (y su ficha de personal, si tiene).
     * Guarda el precio de compra vigente como costo unitario (para Reportes).
     */
    public static function registrar(ProductoInventario $producto, string $tipo, int $cantidad, array $extra = []): self
    {
        $user = auth()->user();

        return self::create($extra + [
            'producto_id'    => $producto->id,
            'tipo'           => $tipo,
            'cantidad'       => $cantidad,
            'costo_unitario' => (float) $producto->precio_compra,
            'user_id'     => $user?->id,
            'personal_id' => $user?->personal?->id,
        ]);
    }
}
