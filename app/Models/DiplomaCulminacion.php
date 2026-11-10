<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiplomaCulminacion extends Model
{
    protected $table = 'diplomas_culminacion';
    protected $primaryKey = 'diploma_id';
    public $timestamps = false;

    protected $fillable = [
        'cert_id', 'inscripcion_id', 'fecha_conclusion', 
        'promedio_final', 'calificacion_cualitativa', 
        'total_horas_academicas', 'emitido_por', 'estado_emision'
    ];

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class, 'inscripcion_id', 'inscripcion_id');
    }

    public function certificacion()
    {
        return $this->belongsTo(BlockchainCertificacion::class, 'cert_id', 'cert_id');
    }
}