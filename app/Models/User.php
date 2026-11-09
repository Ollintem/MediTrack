<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Exception;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Los atributos que se pueden asignar de forma masiva.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'rol_id',
    ];

    /**
     * Los atributos que deben ocultarse en la serialización.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Conversión de tipos de atributos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
        ];
    }

    /**
     * Eventos y comportamiento del modelo.
     */
    protected static function booted(): void
    {
        // Bloquea la eliminación del Super Admin inicial (ID 1)
        static::deleting(function ($user) {
            if ((int) $user->id === 1) {
                throw new Exception("El usuario Super Admin inicial (ID 1) no puede ser eliminado.");
            }
        });
    }

    /**
     * Obtener el nombre completo del médico desde la tabla 'personals' o 'users'.
     */
    public function getNombreCompletoAttribute(): string
    {
        // 1. Obtener directamente de la tabla 'personals' (columna nombre_completo)
        if ($this->personal && !empty($this->personal->nombre_completo)) {
            return $this->personal->nombre_completo;
        }

        // 2. Si no tiene registro en 'personals', tomar de la tabla 'users'
        $nombreUser = $this->attributes['name'] ?? '';

        return !empty($nombreUser) ? $nombreUser : 'Médico en Sesión';
    }

    /** Acciones que se pueden revisar en cada módulo. */
    public const ACCIONES = ['ver', 'crear', 'editar', 'eliminar'];

    /**
     * Determina si el usuario tiene permiso para realizar una acción en un módulo.
     * Es la regla única del sistema: la usan el middleware de rutas, el menú, las vistas y los controladores.
     */
    public function tienePermiso($nombreModulo, $accion = 'ver'): bool
    {
        // 1. Acceso total: Super Admin inicial (ID 1) o rol con acceso total
        if ($this->tieneAccesoTotal()) {
            return true;
        }

        $accion = mb_strtolower(trim((string) $accion));
        if (!in_array($accion, self::ACCIONES, true) || !$this->rol) {
            return false;
        }

        // 2. Registro del módulo en la tabla de permisos del rol
        $this->rol->loadMissing('permisos.modulo');
        $modulo = Rol::normalizar($nombreModulo);
        $permiso = $this->rol->permisos->first(
            fn ($p) => $p->modulo && Rol::normalizar($p->modulo->nombre) === $modulo
        );

        return $permiso ? (bool) $permiso->{'puede_' . $accion} : false;
    }

    /**
     * Acceso total: el Super Admin inicial (ID 1) o un rol de la lista Rol::ROLES_TOTALES.
     */
    public function tieneAccesoTotal(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (!$this->relationLoaded('rol')) {
            $this->load('rol.permisos.modulo');
        }

        return (bool) ($this->rol && $this->rol->esTotal());
    }

    /**
     * Determina si el usuario es el Super Admin del sistema.
     */
    public function isSuperAdmin(): bool
    {
        return (int) $this->id === 1;
    }

    /**
     * ¿Su ficha de personal está marcada como "Inactivo"? Entonces ya no puede usar el sistema.
     * El Super Admin inicial (ID 1) nunca se considera inactivo, para no dejar el sistema sin acceso.
     */
    public function estaInactivo(): bool
    {
        if ($this->isSuperAdmin()) {
            return false;
        }

        return mb_strtolower(trim((string) optional($this->personal)->estado)) === 'inactivo';
    }

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    public function personal()
    {
        return $this->hasOne(Personal::class, 'user_id');
    }

    public function notificaciones()
    {
        return $this->hasMany(Notificacion::class, 'user_id');
    }
}