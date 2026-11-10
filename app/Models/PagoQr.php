<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoQr extends Model
{
    protected $table = 'pagos_qr';
    protected $primaryKey = 'pago_qr_id';
    public $timestamps = false;

    protected $fillable = [
        'pago_id', 'codigo_qr', 'referencia_qr', 'monto', 
        'moneda', 'fecha_generacion', 'fecha_expiracion', 
        'estado', 'respuesta_qr'
    ];

    protected $casts = [
        'respuesta_qr' => 'array',
    ];

    public function pago()
    {
        return $this->belongsTo(Pago::class, 'pago_id', 'pago_id');
    }
}