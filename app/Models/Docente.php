<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Docente extends Model
{
    protected $table = 'docentes';
    protected $primaryKey = 'docente_id';
    public $timestamps = false;

    protected $fillable = [
        'nombre', 'apellido', 'ci', 'celular', 
        'email', 'especialidad', 'grado_academico', 'activo'
    ];

    public function cursos()
    {
        return $this->hasMany(Curso::class, 'docente_id', 'docente_id');
    }

    public function materias()
    {
        return $this->hasMany(Materia::class, 'docente_id', 'docente_id');
    }
}