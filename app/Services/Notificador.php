<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\Notificacion;
use App\Models\PeriodoNomina;
use App\Models\ProductoInventario;
use App\Models\TicketVenta;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Crea los avisos de la campana. Una sola regla de "a quién le toca":
 *  - Citas:      al médico de la cita (su cuenta de usuario).
 *  - Inventario: a quien puede editar Inventario.
 *  - Caja:       a quien entrega en el POS (POS, editar).
 *  - Nómina:     a quien puede editar Personal.
 * Nunca se avisa a quien hizo el cambio, ni a cuentas inactivas.
 * Un error al avisar nunca debe impedir guardar: se registra y se sigue.
 */
class Notificador
{
    private const DIAS = ['dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb'];
    private const MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

    // =====================================================================
    //  CITAS
    // =====================================================================

    public static function citaCreada(Cita $cita): void
    {
        self::seguro(function () use ($cita) {
            self::aMedico($cita->personal_id, 'Nueva cita',
                self::paciente($cita) . ' · ' . self::cuando($cita->fecha, $cita->hora), $cita);
        });
    }

    /** Se reprograma (fecha/hora), se cancela o se pasa a otro médico. Los demás cambios no avisan. */
    public static function citaActualizada(Cita $cita): void
    {
        self::seguro(function () use ($cita) {
            $antes = $cita->getOriginal();

            if ($cita->wasChanged('personal_id')) {
                self::aMedico($antes['personal_id'] ?? null, 'Cita reasignada',
                    self::paciente($cita) . ' · ' . self::cuando($cita->fecha, $cita->hora) . ' ya no está en tu agenda', $cita);
                self::aMedico($cita->personal_id, 'Nueva cita',
                    self::paciente($cita) . ' · ' . self::cuando($cita->fecha, $cita->hora), $cita);
                return;
            }

            if ($cita->wasChanged('estado') && $cita->estado === 'Cancelada') {
                self::aMedico($cita->personal_id, 'Cita cancelada',
                    self::paciente($cita) . ' · ' . self::cuando($cita->fecha, $cita->hora), $cita);
                return;
            }

            if ($cita->wasChanged(['fecha', 'hora']) && $cita->estado !== 'Cancelada') {
                self::aMedico($cita->personal_id, 'Cita reprogramada',
                    self::paciente($cita) . ': ' . self::cuando($antes['fecha'] ?? null, $antes['hora'] ?? null)
                    . ' → ' . self::cuando($cita->fecha, $cita->hora), $cita);
            }
        });
    }

    public static function citaEliminada(Cita $cita): void
    {
        self::seguro(function () use ($cita) {
            if ($cita->estado === 'Cancelada') {
                return;   // ya se avisó al cancelarla
            }
            self::aMedico($cita->personal_id, 'Cita eliminada',
                self::paciente($cita) . ' · ' . self::cuando($cita->fecha, $cita->hora), null);
        });
    }

    // =====================================================================
    //  INVENTARIO
    // =====================================================================

    /** Solo cuando el producto CRUZA hacia bajo stock o agotado (no en cada venta). */
    public static function stockCambio(ProductoInventario $p): void
    {
        self::seguro(function () use ($p) {
            if (!$p->wasChanged('estado') || !in_array($p->estado, ['Bajo Stock', 'Agotado'], true)) {
                return;
            }
            $antes = $p->getOriginal('estado');
            if ($antes === 'Agotado' && $p->estado === 'Bajo Stock') {
                return;   // se surtió un poco: mejoró, no es una alerta nueva
            }

            $titulo = $p->estado === 'Agotado' ? 'Producto agotado' : 'Stock bajo';
            $mensaje = $p->nombre . ($p->estado === 'Agotado'
                ? ' se quedó sin existencias disponibles'
                : ': quedan ' . $p->stock_disponible . ' (mínimo ' . (int) $p->stock_minimo . ')');

            self::aUsuarios(self::conPermiso('Inventario', 'editar'), 'Inventario', $titulo, $mensaje,
                route('inventario.index'), 'producto', $p->id);
        });
    }

    // =====================================================================
    //  CAJA → POS
    // =====================================================================

    public static function ticketCambio(TicketVenta $t): void
    {
        self::seguro(function () use ($t) {
            // getAttributes() = valor nuevo en BD (getRawOriginal aún es el anterior dentro del evento "updated")
            if (!$t->wasChanged('status') || ($t->getAttributes()['status'] ?? null) !== 'pagado') {
                return;
            }
            self::aUsuarios(self::conPermiso('POS', 'editar'), 'Caja', 'Ticket pagado',
                $t->codigo_ticket . ' ($' . number_format((float) $t->monto_total, 2) . ') está listo para entregar',
                route('pos.index'), 'ticket', $t->id);
        });
    }

    // =====================================================================
    //  NÓMINA (revisión diaria)
    // =====================================================================

    /** Periodos con recibos pendientes cuya fecha de pago es hoy o ya pasó. Un aviso por periodo y día. */
    public static function nominaPorPagar(?Carbon $hoy = null): int
    {
        $hoy = ($hoy ?? today())->copy()->startOfDay();
        $avisos = 0;

        $periodos = PeriodoNomina::whereIn('estado', ['Abierto', 'Cerrado'])
            ->whereNotNull('fecha_pago')
            ->whereDate('fecha_pago', '<=', $hoy)
            ->withCount(['recibos as pendientes' => fn ($q) => $q->where('estado', '!=', 'Pagado')])
            ->get()
            ->where('pendientes', '>', 0);

        $destinatarios = self::conPermiso('Personal', 'editar', false);

        foreach ($periodos as $p) {
            // No repetir el aviso del mismo periodo el mismo día
            $yaAvisado = Notificacion::where('referencia_tipo', 'periodo_nomina')->where('referencia_id', $p->id)
                ->whereDate('created_at', $hoy)->exists();
            if ($yaAvisado) {
                continue;
            }

            $vence = $p->fecha_pago->isSameDay($hoy) ? 'se pagan hoy' : 'debían pagarse el ' . $p->fecha_pago->format('d/m/Y');
            $nombre = ['SEMANAL' => 'Semana', 'QUINCENAL' => 'Quincena', 'MENSUAL' => 'Mes'][$p->tipo] ?? 'Pago especial';
            $avisos += self::aUsuarios($destinatarios, 'Nómina', 'Nómina por pagar',
                $nombre . ' ' . $p->fecha_inicio->format('d/m') . '–' . $p->fecha_fin->format('d/m') . ': '
                . $p->pendientes . ($p->pendientes === 1 ? ' recibo pendiente' : ' recibos pendientes') . ' ' . $vence,
                route('personal.nomina.show', $p), 'periodo_nomina', $p->id, false);
        }

        return $avisos;
    }

    /** Borra los avisos ya leídos con más de $dias días. */
    public static function limpiar(int $dias = 60): int
    {
        return Notificacion::where('leida', true)->where('created_at', '<', now()->subDays($dias))->delete();
    }

    // =====================================================================
    //  AYUDANTES
    // =====================================================================

    /** Cuentas activas con ese permiso (sin quien hizo el cambio, salvo que $excluirActor sea false). */
    public static function conPermiso(string $modulo, string $accion, bool $excluirActor = true): Collection
    {
        return User::with('rol.permisos.modulo', 'personal')->get()
            ->filter(fn (User $u) => !$u->estaInactivo() && $u->tienePermiso($modulo, $accion))
            ->when($excluirActor && auth()->id(), fn ($c) => $c->where('id', '!=', auth()->id()))
            ->values();
    }

    private static function aMedico(?int $personalId, string $titulo, string $mensaje, ?Cita $cita): void
    {
        if (!$personalId) {
            return;
        }
        $usuario = User::with('personal')->whereHas('personal', fn ($q) => $q->whereKey($personalId))->first();
        if (!$usuario || $usuario->estaInactivo() || (int) $usuario->id === (int) auth()->id()) {
            return;
        }

        $url = $cita ? route('citas.index', ['fecha' => substr((string) $cita->fecha, 0, 10)]) : route('citas.index');
        self::aUsuarios(collect([$usuario]), 'Citas', $titulo, $mensaje, $url, 'cita', $cita?->id);
    }

    /** Crea un aviso por usuario. Devuelve cuántos se crearon. */
    private static function aUsuarios(Collection $usuarios, string $tipo, string $titulo, string $mensaje,
                                      ?string $url, ?string $refTipo, ?int $refId, bool $excluirActor = true): int
    {
        $clinica = DB::table('clinicas')->value('id');
        if (!$clinica) {
            return 0;
        }

        $n = 0;
        foreach ($usuarios as $u) {
            if ($excluirActor && (int) $u->id === (int) auth()->id()) {
                continue;
            }
            Notificacion::create([
                'clinica_id' => $clinica, 'user_id' => $u->id, 'tipo' => $tipo,
                'titulo' => $titulo, 'mensaje' => mb_substr($mensaje, 0, 1000), 'leida' => false, 'fecha' => now(),
                'url' => $url, 'referencia_tipo' => $refTipo, 'referencia_id' => $refId,
            ]);
            $n++;
        }

        return $n;
    }

    private static function paciente(Cita $cita): string
    {
        return trim((string) optional($cita->paciente)->nombre) ?: 'Paciente';
    }

    /** "vie 14 nov 10:00" */
    private static function cuando($fecha, $hora): string
    {
        if (!$fecha) {
            return '';
        }
        $f = Carbon::parse(substr((string) $fecha, 0, 10));

        return self::DIAS[$f->dayOfWeek] . ' ' . $f->day . ' ' . self::MESES[$f->month - 1]
            . ($hora ? ' ' . substr((string) $hora, 0, 5) : '');
    }

    private static function seguro(callable $fn): void
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            report($e);   // avisar es secundario: nunca debe tumbar el guardado
        }
    }
}
