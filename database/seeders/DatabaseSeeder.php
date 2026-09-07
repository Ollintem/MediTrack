<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Rol;
use App\Models\Modulo;
use App\Models\Clinica;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Clinica::firstOrCreate(
        ['id' => 1],
        ['nombre' => 'Clínica Principal MediTrack', 'estado' => 'Activo']
        );

        $modulos = ['Pacientes', 'Citas', 'Facturación', 'Reportes', 'Inventario', 'Usuarios', 'Personal'];
        foreach ($modulos as $mod) {
         Modulo::firstOrCreate(['nombre' => $mod]);
        }

        $rolesDefault = ['Administrador', 'Doctor', 'Enfermero', 'Recepcionista'];
        foreach ($rolesDefault as $rolNombre) {
        Rol::firstOrCreate(['nombre' => $rolNombre]);
        }
        // 1. Garantizar la creación inmutable del Super Admin
        User::firstOrCreate(
            ['id' => 1], // Condición de búsqueda única
            [
                'nombre'     => 'Administrador',
                'apellido'   => 'Principal',
                'email'      => 'admin@meditrack.com',
                'password'   => Hash::make('meditrack'),
                'estado'     => 'Activo',
                'clinica_id' => null, // Acceso global
                'rol_id'     => null,
            ]
        );
    }
}
