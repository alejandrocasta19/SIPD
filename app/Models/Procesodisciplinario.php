<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Support\Modalidades;

class ProcesoDisciplinario extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'disciplinario';

    protected $fillable = [

        // TIPO Y CONTENIDO DEL CASO OFICIAL
        'tipo_proceso',

        // DATOS DEL CONDUCTOR
        'nombre',
        'cedula',
        'placa',
        'ruta',
        'modalidad',
        'cargo',
        'telefono',

        // INFORMACIÓN DISCIPLINARIA
        'tipo_falta',
        'descripcion_falta',
        'datos_oficiales',
        'fecha_falta',

        // DOCUMENTOS Y OBSERVACIONES
        'documento_falta',
        'observacion',
        'descargos',
        'descargos_forma',
        'descargos_medio',
        'decision_final',

        // ESTADO DEL PROCESO
        'estado',

        // USUARIO QUE REGISTRÓ
        'user_id'
    ];

    protected $casts = [
        'fecha_falta' => 'date',
        'datos_oficiales' => 'array',
    ];

    public const DESCARGOS_MEDIOS = [
        'whatsapp' => 'WhatsApp',
        'correo' => 'Correo',
        'telefono' => 'Teléfono',
        'videollamada' => 'Videollamada',
        'otro' => 'Otro medio',
    ];

    public static function opcionesDescargosPresentacion(): array
    {
        $opciones = [
            'no_presento' => 'Descargos · No presentó',
            'presencial' => 'Descargos · Presencial',
        ];

        foreach (self::DESCARGOS_MEDIOS as $clave => $etiqueta) {
            $opciones['virtual_' . $clave] = 'Descargos · Virtual · ' . $etiqueta;
        }

        return $opciones;
    }

    public static function parseDescargosPresentacion(?string $value): array
    {
        $value = (string) $value;

        if ($value === '' || $value === 'presentado') {
            return [];
        }

        if ($value === 'presencial') {
            return ['descargos_forma' => 'presencial', 'descargos_medio' => null];
        }

        if ($value === 'virtual' || str_starts_with($value, 'virtual_')) {
            $medio = $value === 'virtual' ? null : substr($value, 8);
            if ($medio !== null && !array_key_exists($medio, self::DESCARGOS_MEDIOS)) {
                $medio = null;
            }

            return ['descargos_forma' => 'virtual', 'descargos_medio' => $medio];
        }

        return ['descargos_forma' => 'no_presento', 'descargos_medio' => null];
    }

    public function descargosPresentacionValue(): string
    {
        if ($this->descargos_forma === 'presencial') {
            return 'presencial';
        }

        if ($this->descargos_forma === 'virtual') {
            return ($this->descargos_medio && array_key_exists($this->descargos_medio, self::DESCARGOS_MEDIOS))
                ? 'virtual_' . $this->descargos_medio
                : 'virtual_whatsapp';
        }

        if ($this->descargos_forma === 'no_presento') {
            return 'no_presento';
        }

        return filled($this->descargos) ? 'presentado' : 'no_presento';
    }

    public function etiquetaTipoDescargos(): string
    {
        if ($this->descargosPresentacionValue() === 'presentado') {
            return 'Descargos · Presentado';
        }

        $opciones = self::opcionesDescargosPresentacion();

        return $opciones[$this->descargosPresentacionValue()] ?? 'Descargos · No presentó';
    }

    /**
     * RELACIÓN:
     * UN PROCESO PERTENECE A UN USUARIO
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * RELACIÓN: evidencias adjuntas al caso
     */
    public function evidencias()
    {
        return $this->hasMany(CasoEvidencia::class, 'caso_id')->latest();
    }

    public function anexos()
    {
        return $this->hasMany(CasoAnexo::class, 'caso_id')->latest();
    }

    /**
     * RELACIÓN: estados de cada subdocumento
     */
    public function documentoEstados()
    {
        return $this->hasMany(CasoDocumentoEstado::class, 'caso_id');
    }

    public function scopeConDocumentoDescargado($query)
    {
        return $query->whereHas('documentoEstados', function ($q) {
            $q->whereNotNull('descargado_en');
        });
    }

    public function scopeSinDocumentoDescargado($query)
    {
        return $query->whereDoesntHave('documentoEstados', function ($q) {
            $q->whereNotNull('descargado_en');
        });
    }

    /**
     * Pendiente y En proceso siguen el avance del documento.
     * Sancionado y Archivado siguen la decisión de la coordinadora.
     */
    public function scopeDondeEstadoVisible($query, string $estado)
    {
        if (in_array($estado, ['Sancionado', 'Archivado'], true)) {
            return $query->where('estado', $estado);
        }

        $query->whereNotIn('estado', ['Sancionado', 'Archivado']);

        if (in_array($estado, ['En proceso', 'En Proceso'], true)) {
            return $query->conDocumentoDescargado();
        }

        return $query->sinDocumentoDescargado();
    }

    /**
     * Obtiene (o crea) el estado del subdocumento indicado.
     */
    public function estadoDocumento(string $tipo): CasoDocumentoEstado
    {
        return $this->documentoEstados()
            ->firstOrCreate(
                ['tipo_documento' => $tipo],
                ['estado' => 'no_iniciado']
            );
    }

    public function varianteDelSlot(string $slot): string
    {
        $opciones = CasoDocumentoEstado::variantesDe($slot);
        $fallback = $opciones[0] ?? 'disciplinario';
        $saved = $this->datos_oficiales['slots'][$slot] ?? null;
        if (is_string($saved) && in_array($saved, $opciones, true)) {
            return $saved;
        }

        $estados = $this->relationLoaded('documentoEstados')
            ? $this->documentoEstados
            : $this->documentoEstados()->get();

        foreach ($opciones as $tipo) {
            $est = $estados->firstWhere('tipo_documento', $tipo);
            if ($est && $est->estado !== 'no_iniciado') {
                return $tipo;
            }
        }

        if ($slot === 'apertura' && in_array((string) $this->tipo_proceso, $opciones, true)) {
            return $this->tipo_proceso;
        }

        return $fallback;
    }

    public function guardarVarianteSlot(string $slot, string $tipo): void
    {
        $opciones = CasoDocumentoEstado::variantesDe($slot);
        abort_unless(in_array($tipo, $opciones, true), 404);

        $data = $this->datos_oficiales ?: [];
        $data['slots'][$slot] = $tipo;
        $this->update(['datos_oficiales' => $data]);
    }

    public function estadoDelSlot(string $slot): CasoDocumentoEstado
    {
        return $this->estadoDocumento($this->varianteDelSlot($slot));
    }

    /**
     * Número legible del expediente (ej. 2026-001)
     */
    public function numeroExpediente(): string
    {
        $anio = optional($this->created_at)->format('Y') ?: date('Y');
        return $anio . '-' . str_pad((string) $this->id, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Radicado del documento de apertura (ej. 2026-031-01).
     * Continúa la numeración real del expediente; no reinicia en 1.
     */
    public function numeroRadicado(): string
    {
        return $this->numeroExpediente() . '-01';
    }

    public static function siguienteId(): int
    {
        return max(1, (int) static::query()->max('id') + 1);
    }

    public static function fechaExpedicion(): string
    {
        return now()->format('d/m/Y');
    }

    public static function normalizarCedula(?string $cedula): string
    {
        return preg_replace('/\D+/', '', (string) $cedula) ?? '';
    }

    public static function formatearCedula(?string $cedula): string
    {
        $digits = self::normalizarCedula($cedula);
        if ($digits === '') {
            return '';
        }

        return preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $digits) ?? $digits;
    }

    public function scopePorCedula($query, string $cedula)
    {
        $digits = self::normalizarCedula($cedula);
        if ($digits === '') {
            return $query->whereRaw('0 = 1');
        }

        return $query->where(function ($inner) use ($digits) {
            $inner->where('cedula', $digits)
                ->orWhereRaw(
                    "REPLACE(REPLACE(REPLACE(REPLACE(cedula, '.', ''), ',', ''), '-', ''), ' ', '') = ?",
                    [$digits]
                );
        });
    }

    /**
     * Encabezado de apertura: trabajador, cargo, cédula, fecha y radicado.
     * Si el caso aún no existe, deja nombre/cargo/cédula vacíos y anticipa el siguiente radicado.
     */
    public static function datosEncabezadoDocumento(?self $proceso = null, ?User $firmante = null): array
    {
        $user = $firmante ?: auth()->user();
        $firmanteNombre = $user ? mb_strtoupper(trim((string) $user->name)) : 'KELLY JOHANNA RODRIGUEZ VARGAS';
        $firmanteCargo  = $user ? trim((string) ($user->cargo ?: '')) : 'Asesora Jurídica y de Seguros';

        if ($proceso && $proceso->exists) {
            return [
                'nombre'          => trim((string) $proceso->nombre),
                'cargo'           => Modalidades::etiquetaCaso($proceso->modalidad, $proceso->cargo),
                'cedula'          => trim((string) ($proceso->cedula ?: '')),
                'fecha'           => self::fechaExpedicion(),
                'radicado'        => $proceso->numeroRadicado(),
                'firmante_nombre' => $firmanteNombre,
                'firmante_cargo'  => $firmanteCargo,
            ];
        }

        $siguiente = self::siguienteId();

        return [
            'nombre'          => '',
            'cargo'           => '',
            'cedula'          => '',
            'fecha'           => self::fechaExpedicion(),
            'radicado'        => date('Y') . '-' . str_pad((string) $siguiente, 3, '0', STR_PAD_LEFT) . '-01',
            'firmante_nombre' => $firmanteNombre,
            'firmante_cargo'  => $firmanteCargo,
        ];
    }

    public function codigoProceso(): string
    {
        return 'PRO-' . str_pad((string) $this->id, 3, '0', STR_PAD_LEFT);
    }

    public function vencimientoPlazo()
    {
        $inicio = $this->created_at
            ? $this->created_at->copy()->startOfDay()
            : now()->startOfDay();

        return $inicio->copy()->addDays(5);
    }

    public function diasPlazo(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->vencimientoPlazo(), false);
    }

    public function semaforoPlazo(): string
    {
        $dias = $this->diasPlazo();
        if ($dias <= 0) {
            return 'vencido';
        }
        if ($dias <= 2) {
            return 'por_vencer';
        }

        return 'vigente';
    }

    public function siguienteAccion(): array
    {
        $abierto = in_array($this->estado, ['Pendiente', 'En Proceso'], true);
        $dias = $this->diasPlazo();

        if ($abierto && $dias <= 0) {
            return ['texto' => 'Plazo vencido', 'tono' => 'late'];
        }
        if ($abierto && $dias <= 2) {
            return [
                'texto' => $dias === 1 ? 'Vence mañana' : 'Vence en ' . $dias . ' días',
                'tono' => 'warn',
            ];
        }

        if ($this->estado === 'Pendiente') {
            if (!$this->tieneLosCuatroDocumentosGenerados()) {
                return ['texto' => 'Generar documentos', 'tono' => 'info'];
            }
            if (!filled($this->descargos)) {
                return ['texto' => 'Registrar descargos', 'tono' => 'warn'];
            }

            return ['texto' => 'Listo para enviar a proceso', 'tono' => 'ok'];
        }

        if ($this->estado === 'En Proceso') {
            if ($this->varianteDelSlot('resolucion') === 'terminacion') {
                $anexos = $this->relationLoaded('anexos') ? $this->anexos : $this->anexos()->get();
                $firmado = $anexos->contains(function ($anexo) {
                    return $anexo->tipoDocumento() === 'terminacion';
                });
                if (!$firmado) {
                    return ['texto' => 'Cargar terminación firmada', 'tono' => 'warn'];
                }
            }

            return ['texto' => 'Pendiente de veredicto', 'tono' => 'info'];
        }

        if ($this->estado === 'Sancionado') {
            return ['texto' => 'Resuelto · sanción', 'tono' => 'sanc'];
        }

        return ['texto' => 'Archivado', 'tono' => 'arch'];
    }

    public function etiquetaTipoProceso(): string
    {
        return match ($this->tipo_proceso) {
            'comprobacion' => 'Proceso de comprobación',
            'acta' => 'Acta de cargos y descargos',
            'sancion' => 'Sanción',
            'llamado' => 'Llamado de atención',
            'terminacion' => 'Terminación por justas causas',
            'archivo' => 'Decisión de archivo',
            default => 'Proceso disciplinario',
        };
    }

    public function listoParaVeredicto(): bool
    {
        return $this->puedeEnviarAProceso();
    }

    public function tieneAvanceDocumental(): bool
    {
        foreach (array_keys(CasoDocumentoEstado::SLOTS) as $slot) {
            $est = $this->estadoDelSlot($slot);
            if ($est->estado === 'en_diligenciamiento' || $est->estaGenerado()) {
                return true;
            }
        }

        return false;
    }

    public function faseFlujo(): string
    {
        if ($this->estado === 'Sancionado') {
            return 'sancionado';
        }
        if ($this->estado === 'Archivado') {
            return 'archivado';
        }
        if ($this->estado === 'En Proceso') {
            return 'en_revision';
        }
        if ($this->tieneAvanceDocumental()) {
            return 'en_proceso';
        }

        return 'pendiente';
    }

    /**
     * Texto y progreso del bloque "Estado y flujo" en el detalle.
     * Distingue elaborar documentos vs. revisión de la coordinadora.
     */
    public function resumenFlujo(): array
    {
        $fase = $this->faseFlujo();
        $pasosSlot = [];
        $generados = 0;
        $borradores = 0;
        $nombresListos = [];
        $slotActual = null;

        foreach (array_keys(CasoDocumentoEstado::SLOTS) as $slot) {
            $est = $this->estadoDelSlot($slot);
            $corto = CasoDocumentoEstado::SLOT_SHORT[$slot] ?? $slot;

            if ($est->estaGenerado()) {
                $pasoSlot = $est->estaDescargado() ? 'listo' : 'generado';
                $generados++;
                $nombresListos[] = $corto;
            } elseif ($est->estado === 'en_diligenciamiento') {
                $pasoSlot = 'borrador';
                $borradores++;
                if ($slotActual === null) {
                    $slotActual = $slot;
                }
            } else {
                $pasoSlot = 'pendiente';
                if ($slotActual === null) {
                    $slotActual = $slot;
                }
            }

            $pasosSlot[$slot] = $pasoSlot;
        }

        $siguienteCorto = $slotActual ? (CasoDocumentoEstado::SLOT_SHORT[$slotActual] ?? $slotActual) : null;
        $siguienteEsBorrador = $slotActual && ($pasosSlot[$slotActual] ?? null) === 'borrador';

        if ($fase === 'sancionado') {
            $paso = 'fallo';
            $titulo = 'Sancionado';
            $detalle = 'La coordinadora sancionó el expediente. El proceso está cerrado.';
            $chipMedio = 'En Proceso';
            $subPendiente = 'Documentos listos';
            $subMedio = 'Revisión completada';
            $subFallo = 'Quedó sancionado';
            $slotActual = null;
        } elseif ($fase === 'archivado') {
            $paso = 'fallo';
            $titulo = 'Archivado';
            $detalle = 'La coordinadora archivó el expediente. El proceso está cerrado.';
            $chipMedio = 'En Proceso';
            $subPendiente = 'Documentos listos';
            $subMedio = 'Revisión completada';
            $subFallo = 'Quedó archivado';
            $slotActual = null;
        } elseif ($fase === 'en_revision') {
            $paso = 'revision';
            $titulo = 'En Proceso';
            $detalle = 'Los 4 documentos están listos. Esperando el veredicto de la coordinadora.';
            $chipMedio = 'En Proceso';
            $subPendiente = 'Documentos listos';
            $subMedio = 'Pendiente de veredicto';
            $subFallo = 'Sancionado o archivado';
            $slotActual = null;
        } elseif ($generados === 0 && $borradores === 0) {
            $paso = 'pendiente';
            $titulo = 'Pendiente';
            $detalle = 'Todavía no hay documentos. Empiece por Apertura.';
            $chipMedio = 'En Proceso';
            $subPendiente = 'Sin documentos aún';
            $subMedio = 'Desde el primer documento hasta enviarlo a revisión';
            $subFallo = 'Sancionado o archivado';
            $slotActual = 'apertura';
        } else {
            $paso = 'elaboracion';
            $titulo = 'En Proceso';
            if ($this->puedeEnviarAProceso()) {
                $detalle = 'Los 4 documentos están listos. Envíe el caso a revisión.';
                $subMedio = '4 de 4 listos · Enviar a revisión';
                $slotActual = null;
            } else {
                $detalle = $generados . ' de 4 documentos listos';
                if ($nombresListos) {
                    $detalle .= ' (' . implode(', ', $nombresListos) . ')';
                }
                $detalle .= '.';
                if ($siguienteEsBorrador) {
                    $detalle .= ' Hay un borrador de ' . $siguienteCorto . '.';
                } elseif ($siguienteCorto) {
                    $detalle .= ' Siguiente: ' . $siguienteCorto . '.';
                }
                $subMedio = $generados . ' de 4 listos';
            }
            $chipMedio = 'En Proceso';
            $subPendiente = 'Ya hay documentos';
            $subFallo = 'Sancionado o archivado';
        }

        $pct = in_array($paso, ['revision', 'fallo'], true)
            ? 100
            : (int) round(($generados / 4) * 100);

        return [
            'fase' => $fase,
            'paso' => $paso,
            'titulo' => $titulo,
            'detalle' => $detalle,
            'generados' => $generados,
            'borradores' => $borradores,
            'total' => 4,
            'pct' => $pct,
            'slot_actual' => $slotActual,
            'slots' => $pasosSlot,
            'chip_medio' => $chipMedio,
            'sub_pendiente' => $subPendiente,
            'sub_medio' => $subMedio,
            'sub_fallo' => $subFallo,
        ];
    }

    public function tieneDocumentoDescargado(): bool
    {
        $estados = $this->relationLoaded('documentoEstados')
            ? $this->documentoEstados
            : $this->documentoEstados()->get();

        return $estados->contains(fn ($doc) => $doc->estaDescargado());
    }

    /**
     * Pendiente si todavía no se descarga ningún formato.
     * En proceso si hay al menos un documento descargado.
     * Sancionado o Archivado quedan como los dejó la coordinadora.
     */
    public function estadoVisible(): string
    {
        if (in_array($this->estado, ['Sancionado', 'Archivado'], true)) {
            return $this->estado;
        }

        return $this->tieneDocumentoDescargado() ? 'En proceso' : 'Pendiente';
    }

    public function cerradoPorCoordinadora(): bool
    {
        return in_array($this->estado, ['Sancionado', 'Archivado'], true);
    }

    public function soloLoManejaCoordinadora(?User $user = null): bool
    {
        $user = $user ?: auth()->user();

        return $this->cerradoPorCoordinadora() && (!$user || !$user->esCoordinadora());
    }

    public function redireccionSiBloqueado()
    {
        if (!$this->soloLoManejaCoordinadora()) {
            return null;
        }

        $mensaje = 'Los procesos sancionados o archivados solo los puede manejar la coordinadora.';
        if (request()->expectsJson()) {
            abort(403, $mensaje);
        }

        return redirect()->back()->with('error', $mensaje);
    }

    public function tieneDocumentoGenerado(): bool
    {
        $estados = $this->relationLoaded('documentoEstados')
            ? $this->documentoEstados
            : $this->documentoEstados()->get();

        return $estados->contains(fn ($doc) => $doc->estaGenerado());
    }

    public function tieneLosCuatroDocumentosGenerados(): bool
    {
        foreach (array_keys(CasoDocumentoEstado::SLOTS) as $slot) {
            if (!$this->estadoDelSlot($slot)->estaGenerado()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Pendiente + los 4 documentos generados → se puede enviar a En Proceso.
     */
    public function puedeEnviarAProceso(): bool
    {
        if ($this->estado !== 'Pendiente') {
            return false;
        }

        return $this->tieneLosCuatroDocumentosGenerados();
    }

    /**
     * Vista pública del trámite: etapas y línea de tiempo, sin datos reservados.
     */
    public function seguimientoPublico(): array
    {
        $docs = $this->relationLoaded('documentoEstados')
            ? $this->documentoEstados
            : $this->documentoEstados()->get();

        $docsByTipo = $docs->keyBy('tipo_documento');

        $aperturaTipo = $this->varianteDelSlot('apertura');
        $apertura = $docsByTipo->get($aperturaTipo);
        $aperturaGenerada = $apertura && in_array($apertura->estado, ['completo', 'generado'], true);

        $acta = $docsByTipo->get('acta');
        $actaHecha = $acta && in_array($acta->estado, ['completo', 'generado'], true);
        $descargosHechos = $actaHecha || filled($this->descargos);
        $finalizado = in_array($this->estado, ['Sancionado', 'Archivado'], true);
        $enTramite = $this->estado === 'En Proceso';

        $pasoActual = 2;
        if ($finalizado) {
            $pasoActual = 4;
        } elseif ($enTramite && $descargosHechos) {
            $pasoActual = 4;
        } elseif ($enTramite || $descargosHechos) {
            $pasoActual = 3;
        } elseif ($aperturaGenerada) {
            $pasoActual = 3;
        }

        $titulosEtapa = [
            1 => ['titulo' => 'Apertura', 'detalle' => 'Se abrió el expediente'],
            2 => ['titulo' => 'Notificación', 'detalle' => 'Documento de apertura del trámite'],
            3 => ['titulo' => 'Descargos', 'detalle' => 'Etapa de cargos y descargos'],
            4 => ['titulo' => 'Resolución', 'detalle' => 'Fallo o archivo del proceso'],
        ];

        $etapas = [];
        for ($i = 1; $i <= 4; $i++) {
            if ($finalizado || $i < $pasoActual) {
                $estadoEtapa = 'completada';
            } elseif ($i === $pasoActual) {
                $estadoEtapa = 'actual';
            } else {
                $estadoEtapa = 'pendiente';
            }

            $etapas[] = array_merge($titulosEtapa[$i], [
                'paso' => $i,
                'estado' => $estadoEtapa,
            ]);
        }

        $eventos = collect();

        $eventos->push([
            'orden' => optional($this->created_at)->timestamp ?? 0,
            'fecha' => $this->fechaPublica($this->created_at),
            'titulo' => 'Proceso abierto',
            'detalle' => 'Se registró el expediente ' . $this->numeroExpediente() . '.',
            'actual' => false,
        ]);

        foreach (CasoDocumentoEstado::TIPOS as $tipo) {
            $doc = $docsByTipo->get($tipo);
            if (!$doc) {
                continue;
            }

            $fechaDoc = $doc->generado_en ?: $doc->updated_at;
            $etiqueta = CasoDocumentoEstado::etiqueta($tipo);

            if (in_array($doc->estado, ['completo', 'generado'], true)) {
                $eventos->push([
                    'orden' => optional($fechaDoc)->timestamp ?? 1,
                    'fecha' => $this->fechaPublica($fechaDoc),
                    'titulo' => 'Trámite adelantado',
                    'detalle' => 'Se emitió el ' . $etiqueta . '.',
                    'actual' => false,
                ]);
            } elseif ($doc->estado === 'en_diligenciamiento') {
                $eventos->push([
                    'orden' => optional($doc->updated_at)->timestamp ?? 1,
                    'fecha' => $this->fechaPublica($doc->updated_at),
                    'titulo' => 'Trámite en elaboración',
                    'detalle' => 'Se está elaborando el ' . $etiqueta . '.',
                    'actual' => false,
                ]);
            }
        }

        if (filled($this->descargos) && !$actaHecha) {
            $eventos->push([
                'orden' => optional($this->updated_at)->timestamp ?? 2,
                'fecha' => $this->fechaPublica($this->updated_at),
                'titulo' => 'Etapa de descargos',
                'detalle' => 'Se registró la etapa de descargos del conductor.',
                'actual' => false,
            ]);
        }

        $nEvidencias = $this->evidencias_count
            ?? ($this->relationLoaded('evidencias') ? $this->evidencias->count() : $this->evidencias()->count());

        if ($nEvidencias > 0) {
            $fechaPrueba = $this->relationLoaded('evidencias')
                ? $this->evidencias->min('created_at')
                : $this->evidencias()->min('created_at');

            $eventos->push([
                'orden' => optional($fechaPrueba)->timestamp ?? 3,
                'fecha' => $this->fechaPublica($fechaPrueba),
                'titulo' => 'Recepción de pruebas',
                'detalle' => $nEvidencias === 1
                    ? 'Se incorporó un elemento de prueba al expediente.'
                    : 'Se incorporaron ' . $nEvidencias . ' elementos de prueba al expediente.',
                'actual' => false,
            ]);
        }

        $nAnexos = $this->anexos_count
            ?? ($this->relationLoaded('anexos') ? $this->anexos->count() : $this->anexos()->count());

        if ($nAnexos > 0) {
            $fechaAnexo = $this->relationLoaded('anexos')
                ? $this->anexos->min('created_at')
                : $this->anexos()->min('created_at');

            $eventos->push([
                'orden' => optional($fechaAnexo)->timestamp ?? 3,
                'fecha' => $this->fechaPublica($fechaAnexo),
                'titulo' => 'Documentación del expediente',
                'detalle' => $nAnexos === 1
                    ? 'Se incorporó un documento al expediente.'
                    : 'Se incorporaron ' . $nAnexos . ' documentos al expediente.',
                'actual' => false,
            ]);
        }

        if ($this->estado === 'Sancionado') {
            $eventos->push([
                'orden' => optional($this->updated_at)->timestamp ?? 4,
                'fecha' => $this->fechaPublica($this->updated_at),
                'titulo' => 'Proceso finalizado',
                'detalle' => 'Se emitió el fallo del proceso.',
                'actual' => false,
            ]);
        } elseif ($this->estado === 'Archivado') {
            $eventos->push([
                'orden' => optional($this->updated_at)->timestamp ?? 4,
                'fecha' => $this->fechaPublica($this->updated_at),
                'titulo' => 'Proceso archivado',
                'detalle' => 'El expediente fue archivado.',
                'actual' => false,
            ]);
        }

        $linea = $eventos->sortBy('orden')->values()->map(function (array $evento) {
            unset($evento['orden']);
            return $evento;
        })->all();

        if (!$finalizado) {
            $linea[] = [
                'fecha' => null,
                'titulo' => 'Trámite en curso',
                'detalle' => $this->mensajeEstadoPublico(),
                'actual' => true,
            ];
        }

        return [
            'codigo' => $this->codigoProceso(),
            'expediente' => $this->numeroExpediente(),
            'nombre' => $this->nombre,
            'tipo' => $this->etiquetaTipoProceso(),
            'estado' => $this->estado,
            'estado_clase' => $this->claseEstadoPublico(),
            'abierto_el' => $this->fechaPublica($this->created_at),
            'mensaje' => $this->mensajeEstadoPublico(),
            'etapas' => $etapas,
            'linea_tiempo' => $linea,
        ];
    }

    public function mensajeEstadoPublico(): string
    {
        return match ($this->estado) {
            'Pendiente' => 'El proceso está en trámite inicial. Aún se adelanta la apertura o la notificación.',
            'En Proceso' => 'El proceso está en trámite. Se adelantan los descargos o se estudia la decisión.',
            'Sancionado' => 'El proceso finalizó. Se emitió un fallo.',
            'Archivado' => 'El proceso finalizó. El expediente fue archivado.',
            default => 'El proceso se encuentra en trámite.',
        };
    }

    public function claseEstadoPublico(): string
    {
        return match ($this->estado) {
            'En Proceso' => 'en-proceso',
            'Sancionado' => 'sancionado',
            'Archivado' => 'archivado',
            default => 'pendiente',
        };
    }

    private function fechaPublica($date): ?array
    {
        if (!$date) {
            return null;
        }

        $carbon = $date instanceof \Carbon\Carbon
            ? $date
            : \Illuminate\Support\Carbon::parse($date);

        return [
            'texto' => $carbon->copy()->locale('es')->isoFormat('D [de] MMMM [de] YYYY'),
        ];
    }
}