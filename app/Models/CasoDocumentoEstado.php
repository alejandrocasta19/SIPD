<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CasoDocumentoEstado extends Model
{
    protected $table = 'caso_documento_estados';

    protected $fillable = [
        'caso_id',
        'tipo_documento',
        'estado',
        'generado_en',
    ];

    protected $casts = [
        'generado_en' => 'datetime',
    ];

    public const TIPOS = ['disciplinario', 'comprobacion', 'acta'];

    public const ESTADOS = [
        'no_iniciado'       => 'No iniciado',
        'en_diligenciamiento' => 'En diligenciamiento',
        'completo'          => 'Completo',
        'generado'          => 'Generado',
    ];

    public function caso()
    {
        return $this->belongsTo(ProcesoDisciplinario::class, 'caso_id');
    }

    public function etiquetaEstado(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }

    public function badgeClass(): string
    {
        return match ($this->estado) {
            'no_iniciado'         => 'badge-secondary',
            'en_diligenciamiento' => 'badge-warning',
            'completo'            => 'badge-info',
            'generado'            => 'badge-success',
            default               => 'badge-secondary',
        };
    }
}
