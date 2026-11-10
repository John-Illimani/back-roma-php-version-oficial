<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QrInstitucional extends Model
{
    use HasFactory;

    protected $table = 'qr_institucional';
    protected $primaryKey = 'qr_id';
    public $timestamps = false;

    protected $fillable = [
        'qr_foto',
        'titular',
        'activo',
        'numero_cuenta',
        'banco',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Hace que 'qr_foto_base64' se incluya automáticamente al convertir el modelo a JSON o Array
     */
    protected $appends = [
        'qr_foto_base64',
    ];

    /**
     * Oculta la columna cruda para no duplicar datos en la serialización JSON
     */
    protected $hidden = [
        'qr_foto',
    ];

    /**
     * Accesor para convertir el campo qr_foto (text, ya en base64) al formato data-uri
     */
    public function getQrFotoBase64Attribute(): ?string
    {
        if (!$this->qr_foto) {
            return null;
        }

        // Si ya viene formateado con la cabecera data:image
        if (str_starts_with($this->qr_foto, 'data:image')) {
            return $this->qr_foto;
        }

        return 'data:image/png;base64,' . $this->qr_foto;
    }
}