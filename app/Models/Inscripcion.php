<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inscripcion extends Model
{
    protected $table = 'inscripciones';
    protected $primaryKey = 'inscripcion_id';
    public $timestamps = false;

    protected $fillable = [
        'postulante_id',
        'curso_id',
        'pasarela_id',
        'turno_id',
        'estado_inscripcion_id',
        'codigo_inscripcion',
        'fecha_solicitud',
        'estado',
        'observaciones',
        'fecha_aprobacion',
        'fecha_cancelacion',
        'revisado_por',
        'turno'
    ];

    protected $casts = [
        'fecha_solicitud' => 'datetime',
        'fecha_aprobacion' => 'datetime',
        'fecha_cancelacion' => 'datetime',
        'turno_id' => 'integer',
        'estado_inscripcion_id' => 'integer',
    ];

    public function postulante()
    {
        return $this->belongsTo(Postulante::class, 'postulante_id', 'postulante_id');
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'curso_id', 'curso_id');
    }

    public function pasarela()
    {
        return $this->belongsTo(PasarelaBcp::class, 'pasarela_id', 'pasarela_id');
    }

    public function revisor()
    {
        return $this->belongsTo(Usuario::class, 'revisado_por', 'usuario_id');
    }

    // Relación con el catálogo de turnos
    public function catalogoTurno()
    {
        return $this->belongsTo(CatalogoTurno::class, 'turno_id', 'turno_id');
    }

    // Relación con el catálogo de estados de inscripción
    public function catalogoEstadoInscripcion()
    {
        return $this->belongsTo(CatalogoEstadoInscripcion::class, 'estado_inscripcion_id', 'estado_inscripcion_id');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'inscripcion_id', 'inscripcion_id');
    }

    public function historiales()
    {
        return $this->hasMany(HistorialEstadoInscripcion::class, 'inscripcion_id', 'inscripcion_id');
    }

    public function certificaciones()
    {
        return $this->hasMany(BlockchainCertificacion::class, 'inscripcion_id', 'inscripcion_id');
    }

    public function diplomas()
    {
        return $this->hasMany(DiplomaCulminacion::class, 'inscripcion_id', 'inscripcion_id');
    }
}