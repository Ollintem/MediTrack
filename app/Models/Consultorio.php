<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consultorio extends Model
{
    use HasFactory;

    protected $table = 'consultorios';
    protected $fillable = ['clinica_id', 'nombre', 'piso', 'estado', 'pos_x', 'pos_y'];
    protected $guarded = [];
}