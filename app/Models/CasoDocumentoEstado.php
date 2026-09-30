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

    public const TIPOS = [
        'disciplinario',
        'comprobacion',
        'acta',
        'sancion',
        'llamado',
        'terminacion',
        'archivo',
    ];

    public const SLOTS = [
        'apertura' => ['disciplinario', 'comprobacion'],
        'acta' => ['acta'],
        'resolucion' => ['sancion', 'llamado', 'terminacion'],
        'archivo' => ['archivo'],
    ];

    public const SLOT_LABELS = [
        'apertura' => '1. Apertura',
        'acta' => '2. Acta de cargos y descargos',
        'resolucion' => '3. Sanción / llamado / terminación',
        'archivo' => '4. Decisión de archivo',
    ];

    public const SLOT_ICONS = [
        'apertura' => 'fa-balance-scale',
        'acta' => 'fa-gavel',
        'resolucion' => 'fa-stamp',
        'archivo' => 'fa-archive',
    ];

    public const ETIQUETAS = [
        'disciplinario' => '1.1 GA-FT-045 Apertura disciplinaria',
        'comprobacion' => '1.1 GA-FT-045 Apertura de comprobación',
        'acta' => '2. Acta de cargos y descargos (grabación)',
        'sancion' => '3. Sanción',
        'llamado' => '4. Llamado de atención',
        'terminacion' => '5. Terminación por justas causas',
        'archivo' => 'Decisión de archivo',
    ];

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

    public static function slotDe(string $tipo): ?string
    {
        foreach (self::SLOTS as $slot => $tipos) {
            if (in_array($tipo, $tipos, true)) {
                return $slot;
            }
        }

        return null;
    }

    public static function etiqueta(string $tipo): string
    {
        return self::ETIQUETAS[$tipo] ?? ucfirst($tipo);
    }

    public static function variantesDe(string $slot): array
    {
        return self::SLOTS[$slot] ?? [];
    }

    public static function slotTieneOpciones(string $slot): bool
    {
        return count(self::variantesDe($slot)) > 1;
    }
}
