<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Secretaria extends Model
{
    protected $table = 'secretarias';
    protected $primaryKey = 'secretaria_id';
    public $timestamps = false;

    protected $fillable = ['usuario_id', 'ventanilla_asignada', 'turno'];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id', 'usuario_id');
    }
}