<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    use HasFactory;

    protected $table = 'roles';

    protected $fillable = [
        'nombre',
    ];

    /**
     * Nombres de rol (en minúsculas) con acceso total al sistema sin revisar permisos.
     * Es la ÚNICA lista que usan el middleware, el menú, User::tienePermiso() y la pantalla de Roles.
     */
    public const ROLES_TOTALES = ['administrador', 'admin', 'super admin', 'superadmin', 'superadministrador', 'super administrador'];

    /** Normaliza un nombre de rol para compararlo (minúsculas, sin espacios dobles ni a los lados). */
    public static function normalizar(?string $nombre): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $nombre)));
    }

    /** ¿Ese nombre corresponde a un rol con acceso total? */
    public static function nombreEsTotal(?string $nombre): bool
    {
        return in_array(self::normalizar($nombre), self::ROLES_TOTALES, true);
    }

    /** ¿Este rol tiene acceso total? */
    public function esTotal(): bool
    {
        return self::nombreEsTotal($this->nombre);
    }

    /**
     * Relación con el modelo Permiso.
     */
    public function permisos()
    {
        return $this->hasMany(Permiso::class, 'rol_id');
    }
}
