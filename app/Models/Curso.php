<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Curso extends Model
{
    protected $table = 'cursos';
    protected $primaryKey = 'curso_id';
    public $timestamps = false;

    protected $fillable = [
        'facultad_id', 'docente_id', 'codigo', 'nombre', 
        'cupo_maximo', 'precio_mensual', 'horario_referencial', 
        'dias', 'modalidad', 'aula', 'estado', 'descripcion'
    ];

    public function facultad()
    {
        return $this->belongsTo(Facultad::class, 'facultad_id', 'facultad_id');
    }

    public function docente()
    {
        return $this->belongsTo(Docente::class, 'docente_id', 'docente_id');
    }

    public function materias()
    {
        return $this->hasMany(Materia::class, 'curso_id', 'curso_id');
    }

    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class, 'curso_id', 'curso_id');
    }
}