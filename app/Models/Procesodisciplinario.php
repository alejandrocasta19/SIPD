<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class ProcesoDisciplinario extends Model
{
    use HasFactory;

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

    /**
     * RELACIÓN: estados de cada subdocumento
     */
    public function documentoEstados()
    {
        return $this->hasMany(CasoDocumentoEstado::class, 'caso_id');
    }

    /**
     * Obtiene (o crea) el estado del subdocumento indicado.
     * @param string $tipo disciplinario|comprobacion|acta
     */
    public function estadoDocumento(string $tipo): CasoDocumentoEstado
    {
        return $this->documentoEstados()
            ->firstOrCreate(
                ['tipo_documento' => $tipo],
                ['estado' => 'no_iniciado']
            );
    }

    /**
     * Número legible del expediente (ej. 2026-001)
     */
    public function numeroExpediente(): string
    {
        $anio = optional($this->created_at)->format('Y') ?: date('Y');
        return $anio . '-' . str_pad($this->id, 3, '0', STR_PAD_LEFT);
    }

    public function codigoProceso(): string
    {
        return 'PRO-' . str_pad((string) $this->id, 3, '0', STR_PAD_LEFT);
    }

    public function etiquetaTipoProceso(): string
    {
        return match ($this->tipo_proceso) {
            'comprobacion' => 'Proceso de comprobación',
            'acta' => 'Acta de cargos y descargos',
            default => 'Proceso disciplinario',
        };
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

        $aperturaGenerada = collect(['disciplinario', 'comprobacion'])->contains(
            function (string $tipo) use ($docsByTipo) {
                $doc = $docsByTipo->get($tipo);
                return $doc && in_array($doc->estado, ['completo', 'generado'], true);
            }
        );

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

        foreach (['disciplinario', 'comprobacion', 'acta'] as $tipo) {
            $doc = $docsByTipo->get($tipo);
            if (!$doc) {
                continue;
            }

            $fechaDoc = $doc->generado_en ?: $doc->updated_at;
            $etiqueta = match ($tipo) {
                'comprobacion' => 'auto de apertura del proceso de comprobación',
                'acta' => 'acta de cargos y descargos',
                default => 'auto de apertura del proceso disciplinario',
            };

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