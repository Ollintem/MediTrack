<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bitacora extends Model
{
    use HasFactory;

    protected $table = 'bitacoras';

    protected $fillable = [
        'user_id',
        'usuario_nombre',
        'modulo',
        'accion',
        'descripcion',
        'detalles',
        'ip_address',
    ];

    /**
     * Casteo de atributos. Convierte automáticamente el campo JSON a array de PHP.
     */
    protected $casts = [
        'detalles' => 'array',
    ];

    /**
     * Relación con el modelo User.
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Método estático para registrar eventos en la bitácora desde cualquier controlador.
     *
     * @param string $modulo      Ej: 'Personal', 'Roles', 'Pacientes'
     * @param string $accion      Ej: 'Crear', 'Editar', 'Eliminar'
     * @param string $descripcion Descripción legible de la acción realizada
     * @param array|null $detalles Arreglo opcional con datos clave antes/después
     */
    public static function registrar(string $modulo, string $accion, string $descripcion, ?array $detalles = null): self
    {
        $user = auth()->user();

        // Intenta obtener el nombre completo o email del usuario autenticado
        $nombreUsuario = 'Sistema / Invitado';
        if ($user) {
            $nombreUsuario = $user->nombre_completo 
                ?? trim(($user->nombre ?? '') . ' ' . ($user->apellido ?? '')) 
                ?: $user->name 
                ?: $user->email;
        }

        return self::create([
            'user_id'        => $user ? $user->id : null,
            'usuario_nombre' => $nombreUsuario,
            'modulo'         => $modulo,
            'accion'         => $accion,
            'descripcion'    => $descripcion,
            'detalles'       => $detalles,
            'ip_address'     => request()->ip(),
        ]);
    }
}