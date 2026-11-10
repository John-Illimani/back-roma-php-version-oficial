<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistorialEstadoInscripcion extends Model
{
    protected $table = 'historial_estado_inscripcion';
    protected $primaryKey = 'historial_id';
    public $timestamps = false;

    protected $fillable = [
        'inscripcion_id', 'estado_anterior', 'estado_nuevo', 
        'cambiado_por', 'motivo', 'fecha_cambio'
    ];

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class, 'inscripcion_id', 'inscripcion_id');
    }
}