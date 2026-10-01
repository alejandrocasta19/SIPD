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
        'descargado_en',
    ];

    protected $casts = [
        'generado_en' => 'datetime',
        'descargado_en' => 'datetime',
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

    public const SLOT_SHORT = [
        'apertura' => 'Apertura',
        'acta' => 'Acta de cargos',
        'resolucion' => 'Sanción',
        'archivo' => 'Archivo',
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
        'no_iniciado'       => 'Pendiente',
        'en_diligenciamiento' => 'Borrador',
        'completo'          => 'Generada, no descargada',
        'generado'          => 'Generada',
    ];

    public function caso()
    {
        return $this->belongsTo(ProcesoDisciplinario::class, 'caso_id');
    }

    public function estaGenerado(): bool
    {
        return in_array($this->estado, ['completo', 'generado'], true);
    }

    public function estaDescargado(): bool
    {
        return $this->descargado_en !== null;
    }

    public function etiquetaHub(): string
    {
        if ($this->estaDescargado()) {
            return 'Generada y descargada';
        }
        if ($this->estaGenerado()) {
            return 'Generada, no descargada';
        }
        if ($this->estado === 'en_diligenciamiento') {
            return 'Borrador';
        }

        return 'Pendiente';
    }

    public function claseHub(): string
    {
        if ($this->estaDescargado()) {
            return 'db-descargado';
        }
        if ($this->estaGenerado()) {
            return 'db-generado';
        }
        if ($this->estado === 'en_diligenciamiento') {
            return 'db-en_diligenciamiento';
        }

        return 'db-pendiente';
    }

    public function tonoTarjeta(): string
    {
        if ($this->estaDescargado()) {
            return 'ok';
        }
        if ($this->estado === 'en_diligenciamiento' || $this->estaGenerado()) {
            return 'borrador';
        }

        return 'pendiente';
    }

    public function etiquetaEstado(): string
    {
        return $this->etiquetaHub();
    }

    public function badgeClass(): string
    {
        return match (true) {
            $this->estaDescargado() => 'badge-success',
            $this->estaGenerado() => 'badge-info',
            $this->estado === 'en_diligenciamiento' => 'badge-warning',
            default => 'badge-secondary',
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
