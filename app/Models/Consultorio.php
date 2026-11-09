<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consultorio extends Model
{
    use HasFactory;

    protected $table = 'consultorios';
    protected $fillable = ['clinica_id', 'nombre', 'piso', 'estado', 'pos_x', 'pos_y'];

    /** Lo marca "Ocupado" (salvo que esté en mantenimiento). */
    public function ocupar(): void
    {
        if (!in_array($this->estado, ['Mantenimiento', 'Ocupado'], true)) {
            $this->forceFill(['estado' => 'Ocupado'])->save();
        }
    }

    /**
     * Lo regresa a "Disponible" si estaba "Ocupado" y ya no queda ninguna cita "En curso" en él.
     * $ignorarCitaId: la cita que se está cerrando, cancelando o borrando (aún puede figurar en curso).
     */
    public function liberarSiLibre(?int $ignorarCitaId = null): void
    {
        if ($this->estado !== 'Ocupado') {
            return;
        }

        $hayOtra = Cita::where('consultorio_id', $this->id)
            ->where('estado', 'En curso')
            ->when($ignorarCitaId, fn ($q) => $q->where('id', '!=', $ignorarCitaId))
            ->exists();

        if (!$hayOtra) {
            $this->forceFill(['estado' => 'Disponible'])->save();
        }
    }
}