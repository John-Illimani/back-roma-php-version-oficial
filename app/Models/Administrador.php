<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Administrador extends Model
{
    protected $table = 'administradores';
    protected $primaryKey = 'administrador_id';
    public $timestamps = false;

    protected $fillable = ['usuario_id'];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id', 'usuario_id');
    }
}