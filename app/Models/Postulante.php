<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Postulante extends Model
{
    protected $table = 'postulantes';
    protected $primaryKey = 'postulante_id';
    public $timestamps = false;

    protected $fillable = [
        'usuario_id', 'codigo_postulante', 'paralelo_id', 
        'fecha_nacimiento', 'direccion', 'tutor_nombre', 
        'tutor_celular', 'observaciones_secretaria'
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id', 'usuario_id');
    }

    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class, 'postulante_id', 'postulante_id');
    }
}