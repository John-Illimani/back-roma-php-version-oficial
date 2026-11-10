<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Materia extends Model
{
    protected $table = 'materias';
    protected $primaryKey = 'materia_id';
    public $timestamps = false;

    protected $fillable = [
        'curso_id', 'docente_id', 'codigo', 'nombre', 
        'horas_academicas', 'precio_individual', 'cupo_maximo', 
        'aula', 'horario_inicio', 'horario_fin', 'dias', 
        'modalidad', 'enlace_virtual'
    ];

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'curso_id', 'curso_id');
    }

    public function docente()
    {
        return $this->belongsTo(Docente::class, 'docente_id', 'docente_id');
    }
}