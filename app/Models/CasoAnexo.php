<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CasoAnexo extends Model
{
    protected $table = 'caso_anexos';

    public const TIPO_ARCHIVO_PREVIO = 'archivo_previo';
    public const TIPO_FIRMA_GERENTE = 'firma_gerente';

    public const ESTADO_CARGADO = 'cargado';
    public const ESTADO_PENDIENTE_FIRMA = 'pendiente_firma';
    public const ESTADO_FIRMADO = 'firmado';

    public const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx'];
    public const ALLOWED_MIMES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-word.document.macroenabled.12',
    ];

    public const SCAN_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png'];
    public const SCAN_MIMES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
    ];

    protected $fillable = [
        'caso_id',
        'user_id',
        'tipo',
        'estado',
        'titulo',
        'descripcion',
        'nombre_original',
        'nombre_almacenado',
        'extension',
        'mime_type',
        'tamano',
        'ruta_segura',
        'ruta_firmada',
        'nombre_firmado',
        'extension_firmada',
        'mime_firmado',
        'tamano_firmado',
        'firmado_at',
    ];

    protected $casts = [
        'firmado_at' => 'datetime',
    ];

    public static function tipos(): array
    {
        return [
            self::TIPO_ARCHIVO_PREVIO => 'Archivo previo',
            self::TIPO_FIRMA_GERENTE => 'Terminación por justas causas',
        ];
    }

    public static function resolverTipo(string $tipo): array
    {
        if ($tipo === self::TIPO_FIRMA_GERENTE) {
            return [
                'tipo' => self::TIPO_FIRMA_GERENTE,
                'estado' => self::ESTADO_PENDIENTE_FIRMA,
                'titulo' => 'Terminación por justas causas',
            ];
        }

        return [
            'tipo' => self::TIPO_ARCHIVO_PREVIO,
            'estado' => self::ESTADO_CARGADO,
            'titulo' => null,
        ];
    }

    public function caso()
    {
        return $this->belongsTo(ProcesoDisciplinario::class, 'caso_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tamanoLegible(?int $bytes = null): string
    {
        $bytes = $bytes ?? (int) $this->tamano;
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 0) . ' KB';
        }
        return $bytes . ' B';
    }

    public function etiquetaTipo(): string
    {
        return self::tipos()[$this->tipo] ?? 'Archivo previo';
    }

    public function etiquetaEstado(): string
    {
        if ($this->estado === self::ESTADO_PENDIENTE_FIRMA) {
            return 'Pendiente de firma';
        }
        if ($this->estado === self::ESTADO_FIRMADO) {
            return 'Firmado';
        }
        return 'En archivo';
    }

    public function requiereFirma(): bool
    {
        return $this->tipo === self::TIPO_FIRMA_GERENTE
            && $this->estado === self::ESTADO_PENDIENTE_FIRMA;
    }

    public function rutaDescarga(string $version = 'original'): ?string
    {
        if ($version === 'firmado') {
            return $this->ruta_firmada ?: ($this->estado === self::ESTADO_FIRMADO ? $this->ruta_segura : null);
        }

        return $this->ruta_segura;
    }

    public function nombreDescarga(string $version = 'original'): string
    {
        if ($version === 'firmado') {
            return $this->nombre_firmado ?: $this->nombre_original;
        }

        return $this->nombre_original;
    }
}
