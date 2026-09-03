<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Crear el Super Admin inicial (Garantiza que obtenga el ID 1)
        User::create([
            'nombre' => 'Administrador',
            'apellido' => 'Principal',
            'email' => 'admin@meditrack.com',
            'password' => Hash::make('meditrack'), // <-- Aquí defines la contraseña encriptada
            'estado' => 'Activo',
            'clinica_id' => null, // El ID 1 tiene acceso global
            'rol_id' => null,
        ]);
    }
}
