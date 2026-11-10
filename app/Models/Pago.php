<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $table = 'pagos';
    protected $primaryKey = 'pago_id';
    public $timestamps = false;

    protected $fillable = [
        'inscripcion_id',
        'postulante_id',
        'usuario_id',
        'medio_pago_id',
        'estado_pago_id',
        'recibo_nro',
        'concepto',
        'monto_abonado',
        'medio_pago',
        'referencia_bancaria_bcp',
        'fecha_pago',
        'estado_pago',
        'foto_comprobante',
        'verificacion',
    ];

    protected $casts = [
        'monto_abonado' => 'decimal:2',
        'fecha_pago' => 'datetime',
        'medio_pago_id' => 'integer',
        'estado_pago_id' => 'integer',
    ];

    /**
     * Incluye automáticamente la propiedad foto_comprobante_base64 en el JSON del modelo
     */
    protected $appends = ['foto_comprobante_base64'];

    /**
     * Convierte la foto binaria (bytea) a Base64 legible por React
     */
    public function getFotoComprobanteBase64Attribute(): ?string
{
    if (!$this->foto_comprobante) {
        return null;
    }

    if (str_starts_with($this->foto_comprobante, 'data:image')) {
        return $this->foto_comprobante;
    }

    return 'data:image/png;base64,' . $this->foto_comprobante;
}

    // Relación con la inscripción correspondiente
    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class, 'inscripcion_id', 'inscripcion_id');
    }

    // Relación directa con el postulante (fk_pagos_postulante)
    public function postulante()
    {
        return $this->belongsTo(Postulante::class, 'postulante_id', 'postulante_id');
    }

    // Relación con el usuario/secretaria que registró o está vinculado al pago
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id', 'usuario_id');
    }

    // Relación con el catálogo de medios de pago
    public function catalogoMedioPago()
    {
        return $this->belongsTo(CatalogoMedioPago::class, 'medio_pago_id', 'medio_pago_id');
    }

    // Relación con el catálogo de estados de pago
    public function catalogoEstadoPago()
    {
        return $this->belongsTo(CatalogoEstadoPago::class, 'estado_pago_id', 'estado_pago_id');
    }

    public function pagoQr()
    {
        return $this->hasOne(PagoQr::class, 'pago_id', 'pago_id');
    }

    public function certificaciones()
    {
        return $this->hasMany(BlockchainCertificacion::class, 'pago_id', 'pago_id');
    }
}