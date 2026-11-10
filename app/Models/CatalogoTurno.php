<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogoTurno extends Model
{
    protected $table = 'catalogo_turnos';
    protected $primaryKey = 'turno_id';
    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'nombre',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class, 'turno_id', 'turno_id');
    }
}