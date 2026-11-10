<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogoMedioPago extends Model
{
    protected $table = 'catalogo_medios_pago';
    protected $primaryKey = 'medio_pago_id';
    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'nombre',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'medio_pago_id', 'medio_pago_id');
    }
}