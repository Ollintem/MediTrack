<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Rol;
use App\Models\Modulo;
use App\Models\Clinica;
use App\Models\Personal;
use App\Models\Paciente;
use App\Models\CategoriaInventario;
use App\Models\ProductoInventario;
use App\Models\MovimientoInventario;
use App\Models\TicketVenta;
use App\Models\TicketDetalle;
use App\Models\MovimientoCaja;
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
        $adminUser = User::firstOrCreate(
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
        $personal = Personal::firstOrCreate(
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

        // 7. Paciente de prueba
        $paciente = Paciente::firstOrCreate(
            ['codigo' => 'PAC-001'],
            [
                'clinica_id'              => $clinica->id,
                'codigo'                  => 'PAC-001',
                'primer_nombre'           => 'MARIA',
                'apellido_paterno'        => 'CORTES',
                'apellido_materno'        => 'MORENO',
                'rut'                     => 'COMM950412HDFRRN01',
                'fecha_nacimiento'        => '1995-04-12',
                'genero'                  => 'Femenino',
                'estado_civil'            => 'Soltero/a',
                'nacionalidad'            => 'Mexicana',
                'grupo_sanguineo'         => 'O+',
                'telefono'                => '5559876543',
                'celular'                 => '5512345678',
                'email'                   => 'maria.cortes@email.com',
                'direccion'               => 'AV. SIEMPRE VIVA 123',
                'contacto_emerg_nombre'   => 'JUAN CORTÉS',
                'contacto_emerg_relacion' => 'Padre',
                'contacto_emerg_telefono' => '5551112233',
                'acepta_aviso_privacidad' => true,
                'estado'                  => 'Activo',
            ]
        );

        // =========================================================
        // 8. CATEGORÍAS DE INVENTARIO
        // =========================================================
        $catAnalgesicos = CategoriaInventario::firstOrCreate(['nombre' => 'ANALGÉSICOS Y ANTIPIRÉTICOS']);
        $catAntibioticos = CategoriaInventario::firstOrCreate(['nombre' => 'ANTIBIÓTICOS']);
        $catGastricos   = CategoriaInventario::firstOrCreate(['nombre' => 'GASTROENTEROLOGÍA']);
        $catInsumos     = CategoriaInventario::firstOrCreate(['nombre' => 'INSUMOS MÉDICOS']);

        // =========================================================
        // 9. PRODUCTOS DE INVENTARIO (MEDICAMENTOS CON CÓDIGO EAN-13)
        // =========================================================
        $p1 = ProductoInventario::firstOrCreate(
            ['codigo' => '7501058618210'],
            [
                'clinica_id'    => $clinica->id,
                'categoria_id'  => $catAnalgesicos->id,
                'nombre'        => 'PARACETAMOL 500MG (CAJA C/10 TABLETAS)',
                'descripcion'   => 'PARACETAMOL ALIVIO DE FIEBRE Y DOLOR',
                'precio_compra' => 15.00,
                'precio_venta'  => 35.00,
                'stock_disponible'  => 45,
                'stock_minimo'  => 10,
                'estado'        => 'Normal',
            ]
        );

        $p2 = ProductoInventario::firstOrCreate(
            ['codigo' => '7501234567890'],
            [
                'clinica_id'    => $clinica->id,
                'categoria_id'  => $catAntibioticos->id,
                'nombre'        => 'AMOXICILINA 500MG (CAJA C/12 CÁPSULAS)',
                'descripcion'   => 'AMOXICILINA BACTERICIDA DE AMPLIO ESPECTRO',
                'precio_compra' => 45.00,
                'precio_venta'  => 98.00,
                'stock_disponible'  => 20,
                'stock_minimo'  => 5,
                'estado'        => 'Normal',
            ]
        );

        $p3 = ProductoInventario::firstOrCreate(
            ['codigo' => '7509876543210'],
            [
                'clinica_id'    => $clinica->id,
                'categoria_id'  => $catGastricos->id,
                'nombre'        => 'OMEPRAZOL 20MG (CAJA C/14 CÁPSULAS)',
                'descripcion'   => 'OMEPRAZOL INHIBIDOR DE LA BOMBA DE PROTONES',
                'precio_compra' => 28.00,
                'precio_venta'  => 65.00,
                'stock_disponible'  => 8,
                'stock_minimo'  => 10,
                'estado'        => 'Bajo Stock',
            ]
        );

        $p4 = ProductoInventario::firstOrCreate(
            ['codigo' => '7501112223334'],
            [
                'clinica_id'    => $clinica->id,
                'categoria_id'  => $catAnalgesicos->id,
                'nombre'        => 'IBUPROFENO 400MG (CAJA C/10 TABLETAS)',
                'descripcion'   => 'IBUPROFENO ANTIINFLAMATORIO Y ANALGÉSICO',
                'precio_compra' => 20.00,
                'precio_venta'  => 48.00,
                'stock_disponible'  => 30,
                'stock_minimo'  => 8,
                'estado'        => 'Normal',
            ]
        );

        $p5 = ProductoInventario::firstOrCreate(
            ['codigo' => '7504445556667'],
            [
                'clinica_id'    => $clinica->id,
                'categoria_id'  => $catInsumos->id,
                'nombre'        => 'JERINGA DESECHABLE 5ML C/AGUJA 21G (PIEZA)',
                'descripcion'   => 'JERINGA ESTÉRIL DE UN SOLO USO',
                'precio_compra' => 2.50,
                'precio_venta'  => 7.50,
                'stock_disponible'  => 100,
                'stock_minimo'  => 25,
                'estado'        => 'Normal',
            ]
        );

        // Registros de auditoría inicial de entrada de mercancía
        foreach ([$p1, $p2, $p3, $p4, $p5] as $prod) {
            MovimientoInventario::firstOrCreate(
                ['producto_id' => $prod->id, 'tipo' => 'Entrada', 'motivo' => 'Inventario Inicial'],
                [
                    'personal_id' => $personal->id,
                    'cantidad'    => $prod->stock_actual,
                ]
            );
        }

        // =========================================================
        // 10. TICKETS DE PRUEBA (POS / TERMINAL DE VENTA Y CAJA)
        // =========================================================

        // A. Ticket 1: Estado PENDIENTE (En reserva temporal de 25 minutos para Caja)
        $ticketPendiente = TicketVenta::firstOrCreate(
            ['codigo_ticket' => 'TCK-849201'],
            [
                'paciente_id' => $paciente->id,
                'user_id'     => $usuarioDoctor->id,
                'monto_total' => 133.00,
                'status'      => 'pendiente',
                'expires_at'  => now()->addMinutes(25),
            ]
        );

        TicketDetalle::firstOrCreate(
            ['ticket_id' => $ticketPendiente->id, 'producto_id' => $p1->id],
            [
                'cantidad'        => 1,
                'precio_unitario' => 35.00,
                'subtotal'        => 35.00,
            ]
        );

        TicketDetalle::firstOrCreate(
            ['ticket_id' => $ticketPendiente->id, 'producto_id' => $p2->id],
            [
                'cantidad'        => 1,
                'precio_unitario' => 98.00,
                'subtotal'        => 98.00,
            ]
        );

        // B. Ticket 2: Estado PAGADO (Cobrado en Caja Central, listo para despacho en Farmacia)
        $ticketPagado = TicketVenta::firstOrCreate(
            ['codigo_ticket' => 'TCK-102938'],
            [
                'paciente_id' => null,
                'user_id'     => $adminUser->id,
                'monto_total' => 65.00,
                'status'      => 'pagado',
                'expires_at'  => now()->subMinutes(10),
            ]
        );

        TicketDetalle::firstOrCreate(
            ['ticket_id' => $ticketPagado->id, 'producto_id' => $p3->id],
            [
                'cantidad'        => 1,
                'precio_unitario' => 65.00,
                'subtotal'        => 65.00,
            ]
        );

        // Registra el ingreso económico en caja para el ticket pagado
        MovimientoCaja::firstOrCreate(
            ['ticket_id' => $ticketPagado->id],
            [
                'user_id'     => $adminUser->id,
                'tipo'        => 'Ingreso',
                'concepto'    => 'PAGO DE TICKET DE FARMACIA TCK-102938',
                'monto'       => 65.00,
                'metodo_pago' => 'Efectivo',
            ]
        );
    }
}