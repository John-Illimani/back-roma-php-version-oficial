<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CertificadoEmitido extends Model
{
    use HasFactory;

    // Nombre exacto de la tabla en PostgreSQL
    protected $table = 'certificados_emitidos';

    // Clave primaria
    protected $primaryKey = 'certificado_id';

    // Desactivar timestamps por defecto si solo usamos fecha_emision
    public $timestamps = false;

    // Campos asignables masivamente
    protected $fillable = [
        'postulante_id',
        'hash_blockchain',
        'ci',
        'tx_hash',
        'fecha_emision',
    ];

    // Casteo de tipos de datos
    protected $casts = [
        'postulante_id' => 'integer',
        'fecha_emision' => 'datetime',
    ];

    /**
     * Relación con el Postulante (si existe el modelo Postulante)
     */
    public function postulante()
    {
        return $this->belongsTo(Postulante::class, 'postulante_id', 'postulante_id');
    }
}