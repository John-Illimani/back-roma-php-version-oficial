<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockchainCertificacion extends Model
{
    protected $table = 'blockchain_certificaciones';
    protected $primaryKey = 'cert_id';
    public $timestamps = false;

    protected $fillable = [
        'bloque_id', 'pago_id', 'inscripcion_id', 'diploma_id', 
        'tipo_documento', 'hash_transaccion', 'firma_notarial_root', 'payload_qr'
    ];

    public function bloque()
    {
        return $this->belongsTo(BlockchainBloque::class, 'bloque_id', 'bloque_id');
    }

    public function pago()
    {
        return $this->belongsTo(Pago::class, 'pago_id', 'pago_id');
    }

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class, 'inscripcion_id', 'inscripcion_id');
    }

    public function diploma()
    {
        return $this->hasOne(DiplomaCulminacion::class, 'cert_id', 'cert_id');
    }
}