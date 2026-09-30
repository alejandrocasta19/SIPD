<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CasoEvidencia extends Model
{
    protected $table = 'caso_evidencias';

    protected $fillable = [
        'caso_id',
        'user_id',
        'nombre_original',
        'nombre_almacenado',
        'extension',
        'mime_type',
        'tamano',
        'descripcion',
        'ruta_segura',
    ];

    /**
     * Extensiones y MIME types permitidos.
     * La validación ocurre en el controlador; este mapa sirve de referencia canónica.
     */
    public const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png'];
    public const ALLOWED_MIMES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
    ];

    public function caso()
    {
        return $this->belongsTo(ProcesoDisciplinario::class, 'caso_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** ¿El archivo es una imagen? */
    public function esImagen(): bool
    {
        return in_array($this->extension, ['jpg', 'jpeg', 'png'], true);
    }

    /** Tamaño legible */
    public function tamanoLegible(): string
    {
        $bytes = $this->tamano;
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 0) . ' KB';
        }
        return $bytes . ' B';
    }
}
