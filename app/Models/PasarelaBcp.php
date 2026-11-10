<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasarelaBcp extends Model
{
    protected $table = 'pasarela_bcp';
    protected $primaryKey = 'pasarela_id';
    public $timestamps = false;

    protected $fillable = ['nombre', 'codigo_banco', 'url_api', 'ambiente', 'estado'];

    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class, 'pasarela_id', 'pasarela_id');
    }
}