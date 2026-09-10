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
        'clinica_id',
        'rol_id',
        'nombre',
        'email',
        'password',
        'estado',
        'ultimo_login',
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
            'ultimo_login' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Eventos y comportamiento del modelo.
     */
    protected static function booted(): void
    {
        // Bloquea la eliminación del Super Admin inicial (ID 1)
        static::deleting(function ($user) {
            if ($user->id === 1) {
                throw new Exception("El usuario Super Admin inicial (ID 1) no puede ser eliminado.");
            }
        });
    }

    /**
     * Determina si el usuario tiene permiso para realizar una acción en un módulo.
     */
    public function tienePermiso($nombreModulo, $accion = 'ver')
{
    // 1. EL SUPER ADMIN INICIAL (ID 1) TIENE ACCESO TOTAL SIEMPRE
    if ($this->id === 1) {
        return true;
    }

    // Cargar la relación si aún no está en memoria
    if (!$this->relationLoaded('rol')) {
        $this->load('rol.permisos.modulo');
    }

    if (!$this->rol) {
        return false;
    }

    // 2. SOLO ADMINISTRADORES GLOBALES TIENEN ACCESO TOTAL AUTOMÁTICO
    $nombreRol = strtolower(trim($this->rol->nombre));
    if (in_array($nombreRol, ['administrador', 'admin', 'super admin', 'superadministrador'])) {
        return true;
    }

    // 3. EVALUACIÓN DE REGISTROS EN BD PARA TODOS LOS DEMÁS ROLES
    $permisos = $this->rol->permisos;
    if (!$permisos) {
        return false;
    }

    $permiso = $permisos->first(function ($p) use ($nombreModulo) {
        return $p->modulo && strtolower(trim($p->modulo->nombre)) === strtolower(trim($nombreModulo));
    });

    if (!$permiso) {
        return false;
    }

    switch (strtolower($accion)) {
        case 'ver':
            return (bool) $permiso->puede_ver;
        case 'crear':
            return (bool) $permiso->puede_crear;
        case 'editar':
            return (bool) $permiso->puede_editar;
        case 'eliminar':
            return (bool) $permiso->puede_eliminar;
        default:
            return false;
    }
}
    /**
     * Determina si el usuario es el Super Admin del sistema.
     */
    public function isSuperAdmin(): bool
    {
        return $this->id === 1;
    }

    public function clinica()
    {
        return $this->belongsTo(Clinica::class, 'clinica_id');
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