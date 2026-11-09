<?php

namespace Tests\Concerns;

use App\Models\Cita;
use App\Models\Clinica;
use App\Models\Consultorio;
use App\Models\Modulo;
use App\Models\Paciente;
use App\Models\Permiso;
use App\Models\Personal;
use App\Models\ProductoInventario;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Atajos para crear datos mínimos en las pruebas.
 * No se depende del usuario con ID 1: en MySQL los IDs no se reinician entre pruebas,
 * así que el acceso total se da con el rol "Administrador".
 */
trait DatosDePrueba
{
    protected function clinica(): Clinica
    {
        return Clinica::first() ?? Clinica::create([
            'nombre'      => 'CLÍNICA DE PRUEBA',
            'rut_empresa' => 'PRUEBA' . Str::upper(Str::random(6)),
            'direccion'   => 'CALLE 1',
            'telefono'    => '5550000000',
        ]);
    }

    /** Rol con permisos por módulo: ['Citas' => ['ver', 'editar'], ...] */
    protected function rol(string $nombre, array $permisos = []): Rol
    {
        Modulo::sincronizar();
        $rol = Rol::create(['nombre' => $nombre . ' ' . Str::random(4)]);

        foreach ($permisos as $modulo => $acciones) {
            Permiso::create([
                'rol_id'         => $rol->id,
                'modulo_id'      => Modulo::where('nombre', $modulo)->value('id'),
                'puede_ver'      => in_array('ver', $acciones, true),
                'puede_crear'    => in_array('crear', $acciones, true),
                'puede_editar'   => in_array('editar', $acciones, true),
                'puede_eliminar' => in_array('eliminar', $acciones, true),
            ]);
        }

        return $rol;
    }

    protected function admin(): User
    {
        $rol = Rol::firstOrCreate(['nombre' => 'Administrador']);

        return $this->usuario($rol);
    }

    /** Usuario con ficha de personal. */
    protected function usuario(?Rol $rol = null, string $password = 'Clave1234', string $estado = 'Activo'): User
    {
        $user = User::create([
            'name'     => 'USUARIO PRUEBA',
            'email'    => Str::lower(Str::random(10)) . '@meditrack.com',
            'password' => Hash::make($password),
            'rol_id'   => $rol?->id,
        ]);

        Personal::create([
            'user_id'         => $user->id,
            'clinica_id'      => $this->clinica()->id,
            'nombre_completo' => 'PERSONAL PRUEBA',
            'turno'           => 'Completo',
            'estado'          => $estado,
        ]);

        return $user->fresh();
    }

    protected function paciente(): Paciente
    {
        return Paciente::create([
            'clinica_id'       => $this->clinica()->id,
            'primer_nombre'    => 'MARIA',
            'apellido_paterno' => 'CORTES',
            'apellido_materno' => 'MORENO',
        ]);
    }

    protected function consultorio(string $estado = 'Disponible'): Consultorio
    {
        return Consultorio::create([
            'clinica_id' => $this->clinica()->id,
            'nombre'     => 'SALA ' . Str::upper(Str::random(4)),
            'estado'     => $estado,
        ]);
    }

    protected function medico(): Personal
    {
        return Personal::create([
            'clinica_id'      => $this->clinica()->id,
            'nombre_completo' => 'DR. PRUEBA ' . Str::upper(Str::random(4)),
            'turno'           => 'Completo',
            'estado'          => 'Activo',
        ]);
    }

    protected function cita(array $datos = []): Cita
    {
        return Cita::create($datos + [
            'paciente_id'    => $this->paciente()->id,
            'personal_id'    => $this->medico()->id,
            'consultorio_id' => $this->consultorio()->id,
            'clinica_id'     => $this->clinica()->id,
            'fecha'          => '2031-01-10',
            'hora'           => '09:00:00',
            'duracion_min'   => 30,
            'motivo'         => 'PRUEBA',
            'tipo_consulta'  => 'Seguimiento',
            'estado'         => 'Confirmada',
        ]);
    }

    /** Datos de cita como los manda el formulario de Citas. */
    protected function formularioCita(Cita $cita, array $cambios = []): array
    {
        return $cambios + [
            'paciente_id'    => $cita->paciente_id,
            'personal_id'    => $cita->personal_id,
            'consultorio_id' => $cita->consultorio_id,
            'fecha'          => substr((string) $cita->fecha, 0, 10),
            'hora'           => substr((string) $cita->hora, 0, 5),
            'duracion_min'   => $cita->duracion_min,
            'motivo'         => $cita->motivo,
            'tipo_consulta'  => $cita->tipo_consulta,
            'estado'         => $cita->estado,
        ];
    }

    protected function producto(int $stock, int $minimo = 2): ProductoInventario
    {
        return ProductoInventario::create([
            'clinica_id'    => $this->clinica()->id,
            'codigo'        => (string) random_int(1000000000000, 9999999999999),
            'nombre'        => 'PARACETAMOL ' . Str::upper(Str::random(4)),
            'descripcion'   => '',
            'precio_compra' => 10,
            'precio_venta'  => 20,
            'stock_actual'  => $stock,
            'stock_minimo'  => $minimo,
            'estado'        => 'Normal',
        ]);
    }
}
