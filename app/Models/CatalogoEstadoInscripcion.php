<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogoEstadoInscripcion extends Model
{
    protected $table = 'catalogo_estados_inscripcion';
    protected $primaryKey = 'estado_inscripcion_id';
    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class, 'estado_inscripcion_id', 'estado_inscripcion_id');
    }
}