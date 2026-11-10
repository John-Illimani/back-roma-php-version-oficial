<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockchainBloque extends Model
{
    protected $table = 'blockchain_bloques';
    protected $primaryKey = 'bloque_id';
    public $timestamps = false;

    protected $fillable = [
        'bloque_nro', 'hash_bloque_actual', 'hash_bloque_previo', 
        'red_blockchain', 'smart_contract', 'fecha_sellado'
    ];

    public function certificaciones()
    {
        return $this->hasMany(BlockchainCertificacion::class, 'bloque_id', 'bloque_id');
    }
}