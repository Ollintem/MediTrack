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