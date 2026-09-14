<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Rol;
use App\Models\Modulo;
use App\Models\Clinica;
use App\Models\Personal;
use App\Models\Paciente;
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
        // 1. Crear Clínica por defecto
        $clinica = Clinica::firstOrCreate(
            ['id' => 1],
            [
                'nombre'      => 'Clínica Principal MediTrack',
                'rut_empresa' => 'CLI-76543210-K',
                'direccion'   => 'Av. Principal #123, Ciudad Central',
                'telefono'    => '5550001122',
            ]
        );

        // 2. Crear Módulos del Sistema
        $modulos = ['Pacientes', 'Citas', 'Consultas', 'Recetas', 'Facturación', 'Reportes', 'Inventario', 'Usuarios', 'Personal'];
        foreach ($modulos as $mod) {
            Modulo::firstOrCreate(['nombre' => $mod]);
        }

        // 3. Crear Roles por defecto
        $rolDoctor = Rol::firstOrCreate(['nombre' => 'Doctor']);
        Rol::firstOrCreate(['nombre' => 'Administrador']);
        Rol::firstOrCreate(['nombre' => 'Enfermero']);
        Rol::firstOrCreate(['nombre' => 'Recepcionista']);

        // 4. Crear Super Administrador
        User::firstOrCreate(
            ['email' => 'admin@meditrack.com'],
            [
                'name'     => 'Administrador Principal',
                'password' => Hash::make('meditrack'),
                'rol_id'   => null,
            ]
        );

        // 5. Crear Usuario Doctor de prueba
        $usuarioDoctor = User::firstOrCreate(
            ['email' => 'doctor@meditrack.com'],
            [
                'name'     => 'Dr. Alejandro Morales',
                'password' => Hash::make('meditrack'),
                'rol_id'   => $rolDoctor->id,
            ]
        );

        // 6. Perfil de Personal
        Personal::firstOrCreate(
            ['user_id' => $usuarioDoctor->id],
            [
                'clinica_id'             => $clinica->id,
                'nombre_completo'        => 'Alejandro Morales',
                'rut'                    => 'MED-994821',
                'telefono'               => '5551234567',
                'numero_registro'        => 'CED-849201',
                'especialidad_principal' => 'Medicina General',
                'turno'                  => 'Mañana',
                'estado'                 => 'Activo',
            ]
        );

        // 7. Paciente ajustado EXACTAMENTE a las columnas de tu tabla 'pacientes'
        Paciente::firstOrCreate(
            ['id' => 1],
            [
                'clinica_id'       => $clinica->id,
                'codigo'           => 'PAC-001',
                'nombre_completo'  => 'María Cortés',
                'rut'              => 'PAC-10293',
                'fecha_nacimiento' => '1995-04-12',
                'genero'           => 'Femenino',
                'telefono'         => '5559876543',
                'estado'           => 'Activo',
            ]
        );
    }
}