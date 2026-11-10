<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogoEstadoPago extends Model
{
    protected $table = 'catalogo_estados_pago';
    protected $primaryKey = 'estado_pago_id';
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

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'estado_pago_id', 'estado_pago_id');
    }
}