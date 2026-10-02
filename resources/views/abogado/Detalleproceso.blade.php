@extends('layouts.master')

@section('page-header')
    <div class="proc-head">
        <div>
            <h1>Detalle del proceso</h1>
            <p>{{ $proceso->nombre }} · CC {{ $proceso->cedula ?: '—' }}</p>
        </div>
    </div>
@endsection

@section('content')

@if($errors->any())
    <div class="sipd-alert sipd-alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        <div>
            <strong>Información pendiente</strong>
            <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    </div>
@endif

<form action="{{ route('abogado.actualizarproceso', $proceso->id) }}"
      method="POST"
      enctype="multipart/form-data">

    @csrf
    @method('PUT')

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            
            <div class="card">

                <div class="card-header">
                    <div class="card-tools">

                        <a class="btn btn-secondary btn-volver-lista" href="{{ $volverA }}"><i class="fas fa-arrow-left"></i> Volver a la lista</a>

                        @if($proceso->soloLoManejaCoordinadora())
                        <span class="btn btn-outline-secondary disabled" style="pointer-events:none;">
                            <i class="fas fa-lock"></i> Solo la coordinadora puede manejarlo
                        </span>
                        @elseif(auth()->user()->puede('editar_casos'))
                        <button type="button"
                                class="btn btn-primary"
                                onclick="habilitarEdicion()">
                            <i class="fas fa-edit"></i> Editar Proceso
                        </button>
                        <button type="submit"
                                id="btnGuardar"
                                class="btn btn-success"
                                style="display:none;">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                        @else
                        <button type="button" class="btn btn-outline-secondary" onclick="window.SIPD_abrirPermiso && SIPD_abrirPermiso('editar_casos')">
                            <i class="fas fa-key"></i> Pedir permiso para editar
                        </button>
                        @endif

                    </div>
                </div>

                <div class="card-body border-bottom bg-light">
                    @php
                        $fase = $proceso->faseFlujo();
                        $flujo = $proceso->resumenFlujo();
                        $fasePendienteOn = $flujo['paso'] === 'pendiente';
                        $faseProcesoOn = in_array($flujo['paso'], ['elaboracion', 'revision'], true);
                        $faseFalloOn = $flujo['paso'] === 'fallo';
                        $fase3Cls = $fase === 'archivado' ? 'archivado' : 'sancionado';
                        $falloLabel = match ($fase) {
                            'archivado' => 'Archivado',
                            'sancionado' => 'Sancionado',
                            default => 'Fallo',
                        };
                        $estadoHint = $flujo['detalle'];
                    @endphp
                    <div class="p-3 bg-white rounded border shadow-sm">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                            <small class="text-uppercase font-weight-bold text-muted mb-0">
                                <i class="fas fa-route" style="color:var(--cth-green);"></i> Estado y flujo del proceso
                            </small>
                            <a class="btn btn-sm btn-success" href="{{ route('documentos.hub') }}">
                                <i class="fas fa-file-signature"></i> Generar documentos
                            </a>
                        </div>

                        <div class="sipd-flujo-now">
                            <div class="sipd-flujo-now__copy">
                                <span class="sipd-flujo-now__kicker">Ahora</span>
                                <strong class="sipd-flujo-now__title">{{ $flujo['titulo'] }}</strong>
                                <span class="sipd-flujo-now__detail">{{ $flujo['detalle'] }}</span>
                            </div>
                            <div class="sipd-flujo-now__meter">
                                <span class="sipd-flujo-now__count">{{ $flujo['generados'] }} de {{ $flujo['total'] }} documentos</span>
                                <div class="sipd-flujo-now__bar" role="progressbar" aria-valuenow="{{ $flujo['pct'] }}" aria-valuemin="0" aria-valuemax="100">
                                    <span style="width: {{ $flujo['pct'] }}%"></span>
                                </div>
                            </div>
                        </div>

                        <div class="sipd-flujo d-flex align-items-center justify-content-between text-center mb-4">
                            <div class="flex-fill sipd-flujo-step {{ $fasePendienteOn ? 'is-current' : ($faseProcesoOn || $faseFalloOn ? 'is-done' : '') }}">
                                <span class="sipd-estado sipd-estado--pendiente {{ $fasePendienteOn ? 'is-on' : '' }}">
                                    <i class="fas {{ $fasePendienteOn ? 'fa-hourglass-half' : 'fa-check' }}"></i> Pendiente
                                </span>
                                <small class="d-block text-muted">{{ $flujo['sub_pendiente'] }}</small>
                            </div>
                            <i class="fas fa-chevron-right text-muted mx-2"></i>
                            <div class="flex-fill sipd-flujo-step {{ $faseProcesoOn ? 'is-current' : ($faseFalloOn ? 'is-done' : 'is-wait') }}">
                                <span class="sipd-estado sipd-estado--proceso {{ $faseProcesoOn ? 'is-on' : '' }}">
                                    <i class="fas {{ $flujo['paso'] === 'revision' ? 'fa-user-check' : ($faseFalloOn ? 'fa-check' : 'fa-pen') }}"></i>
                                    En Proceso
                                </span>
                                <small class="d-block text-muted">{{ $flujo['sub_medio'] }}</small>
                                @if($proceso->puedeEnviarAProceso() && !auth()->user()->esCoordinadora())
                                    <button type="submit"
                                            form="form-enviar-proceso"
                                            class="sipd-estado sipd-estado--proceso mt-2"
                                            data-confirm="El caso pasará a En Proceso y quedará pendiente de veredicto."
                                            data-confirm-title="Enviar a revisión"
                                            data-confirm-ok="Enviar"
                                            data-confirm-icon="question">
                                        Enviar a revisión
                                    </button>
                                @endif
                            </div>
                            <i class="fas fa-chevron-right text-muted mx-2"></i>
                            <div class="flex-fill sipd-flujo-step {{ $faseFalloOn ? 'is-current' : 'is-wait' }}">
                                <span class="sipd-estado sipd-estado--{{ $fase3Cls }} {{ $faseFalloOn ? 'is-on' : '' }}">
                                    <i class="fas fa-gavel"></i> {{ $falloLabel }}
                                </span>
                                <small class="d-block text-muted">{{ $flujo['sub_fallo'] }}</small>
                            </div>
                        </div>

                        <div class="row">
                            @foreach(\App\Models\CasoDocumentoEstado::SLOTS as $slot => $variantes)
                                @php
                                    $tipoSlot = $proceso->varianteDelSlot($slot);
                                    $estSlot = $proceso->estadoDelSlot($slot);
                                    $tono = $estSlot->tonoTarjeta();
                                    $esActual = $flujo['slot_actual'] === $slot;
                                    $anexosSlot = $proceso->anexos->filter(fn ($anexo) => $anexo->slot() === $slot);
                                @endphp
                                <div class="col-md-3 mb-3">
                                    <a href="{{ route('documentos.edit', [$proceso->id, $tipoSlot]) }}" class="flujo-doc-card flujo-doc--{{ $tono }} {{ $esActual ? 'is-actual' : '' }}">
                                        <div class="card h-100 border shadow-sm">
                                            <div class="card-body p-3 text-center">
                                                @if($esActual)
                                                    <span class="flujo-doc-now">{{ ($flujo['slots'][$slot] ?? '') === 'borrador' ? 'En curso' : 'Siguiente' }}</span>
                                                @endif
                                                <div class="flujo-icon mb-3">
                                                    <i class="fas {{ \App\Models\CasoDocumentoEstado::SLOT_ICONS[$slot] }}"></i>
                                                </div>
                                                <h6 class="font-weight-bold mb-1" style="font-size:13px;">{{ \App\Models\CasoDocumentoEstado::SLOT_LABELS[$slot] }}</h6>
                                                <p class="small mb-2" style="color:inherit;opacity:.8;">{{ \App\Models\CasoDocumentoEstado::etiqueta($tipoSlot) }}</p>
                                                <span class="flujo-doc-tag">{{ $estSlot->etiquetaHub() }}</span>
                                                @if($tipoSlot === 'terminacion')
                                                    <p class="small mt-2 mb-0" style="opacity:.75;">Imprimir para firma del gerente y subir el escaneo en Anexos.</p>
                                                @endif
                                            </div>
                                        </div>
                                    </a>
                                    @if($anexosSlot->isNotEmpty())
                                        <div class="mt-2">
                                            @foreach($anexosSlot as $anexo)
                                                <div class="small mb-1">
                                                    @if(auth()->user()->puede('descargar_anexos'))
                                                        <a href="{{ route('abogado.anexos.download', $anexo->id) }}">{{ $anexo->nombreVisible() }}</a>
                                                    @else
                                                        {{ $anexo->nombreVisible() }}
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    
                    <!-- Información del trabajador -->
                    <div class="info-box">

                        <div class="info-header">
                            <h4><i class="fas fa-user"></i>Información del trabajador</h4>
                        </div>

                        <div class="info-content">

                            <div class="row">

                                <div class="col-md-3">
                                    <strong>Nombre del trabajador:</strong>

                                    <input type="text"
                                           name="nombre"
                                           id="inp-nombre"
                                           value="{{ $proceso->nombre }}"
                                           class="form-control campo-editable"
                                           title="Solo letras"
                                           required
                                           readonly>
                                </div>

                                <div class="col-md-3">
                                    <strong>Cédula:</strong>

                                    <input type="text"
                                           name="cedula"
                                           id="inp-cedula"
                                           value="{{ $proceso->cedula }}"
                                           class="form-control campo-editable"
                                           inputmode="numeric"
                                           pattern="[0-9]+"
                                           title="Solo números"
                                           required
                                           readonly>
                                </div>

                                <div class="col-md-3">
                                    <strong>Teléfono:</strong>

                                    <input type="text"
                                           name="telefono"
                                           id="inp-telefono"
                                           value="{{ $proceso->telefono }}"
                                           class="form-control campo-editable"
                                           inputmode="numeric"
                                           pattern="[0-9]*"
                                           title="Solo números"
                                           readonly>
                                </div>

                                <div class="col-md-3">
                                    <strong>Modalidad o Cargo:</strong>

                                    <input type="text"
                                           name="modalidad"
                                           id="inp-modalidad"
                                           value="{{ $proceso->modalidad }}"
                                           class="form-control campo-editable"
                                           readonly>
                                </div>

                                @php
                                    $placaAplica = \App\Support\Modalidades::usaPlaca($proceso->modalidad);
                                    $cargoAplica = filled($proceso->cargo) || $proceso->modalidad === 'Administrativos' || \App\Support\Modalidades::pideCargo($proceso->modalidad);
                                @endphp
                                <div class="col-md-3" id="cargo-field" @unless($cargoAplica) hidden @endunless>
                                    <strong>Cargo:</strong>
                                    @if($proceso->modalidad === 'Administrativos')
                                        <select name="cargo" id="inp-cargo" class="form-control campo-editable" disabled>
                                            @foreach(\App\Support\Modalidades::areasAdministrativas() as $area)
                                                <option value="{{ $area }}" {{ $proceso->cargo === $area ? 'selected' : '' }}>{{ $area }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <input type="text"
                                               name="cargo"
                                               id="inp-cargo"
                                               value="{{ $proceso->cargo }}"
                                               class="form-control campo-editable"
                                               title="Solo letras"
                                               @if($cargoAplica) required @endif
                                               @unless($cargoAplica) disabled @endunless
                                               readonly>
                                    @endif
                                </div>
                                <div class="col-md-3" id="placa-field" @unless($placaAplica) hidden @endunless>
                                    <strong>Placa del vehículo:</strong>

                                    <input type="text"
                                           name="placa"
                                           id="inp-placa"
                                           value="{{ $proceso->placa }}"
                                           class="form-control campo-editable"
                                           placeholder="Ej: GRK206"
                                           @unless($placaAplica) disabled @endunless
                                           readonly>
                                </div>

                            </div>

                        </div>

                    </div>
                    
                    <!-- Información de la Falta -->
                    <div class="info-box">

                        <div class="info-header">
                            <h4><i class="fas fa-exclamation-triangle"></i> Información de la Falta</h4>
                        </div>

                        <div class="info-content">

                            <div class="row">

                                <div class="col-md-6">

                                    <strong>Tipo de Falta:</strong>

                                    <input type="text"
                                           name="tipo_falta"
                                           value="{{ $proceso->tipo_falta }}"
                                           class="form-control campo-editable"
                                           readonly>

                                </div>

                                <div class="col-md-6">

                                    <strong>Fecha de la Falta:</strong>

                                    <input type="date"
                                           name="fecha_falta"
                                           value="{{ $proceso->fecha_falta }}"
                                           class="form-control campo-editable"
                                           readonly>

                                </div>

                            </div>

                            <div class="row">

                                <div class="col-md-6">

                                    <strong>Descripción de la Falta:</strong>

                                    <textarea name="descripcion_falta"
                                              class="form-control campo-editable"
                                              rows="3"
                                              readonly>{{ $proceso->descripcion_falta }}</textarea>

                                </div>

                                <div class="col-md-6">

                                    <strong>Documento de la Falta:</strong>

                                    @if($proceso->evidencias && $proceso->evidencias->count() > 0)
                                        <div class="detalle-docs">
                                            @foreach($proceso->evidencias as $evidencia)
                                            <p class="detalle-doc-line">
                                                <a href="{{ route('documentos.evidencias.download', [$proceso->id, $evidencia->id]) }}">
                                                    <i class="fas fa-paperclip"></i> {{ $evidencia->nombre_original }}
                                                </a>
                                            </p>
                                            @endforeach
                                        </div>
                                    @elseif($proceso->documento_falta)
                                        <p class="detalle-doc-line">
                                            <a href="{{ route('abogado.documento-falta', $proceso->id) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-file-pdf"></i> Ver Documento Principal
                                            </a>
                                        </p>
                                    @else
                                        <p class="detalle-empty">No hay documento adjunto</p>
                                    @endif

                                    <input type="file" name="documento_falta[]" multiple class="form-control campo-editable detalle-file" disabled>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Proceso Disciplinario -->
                    <div class="info-box">

                        <div class="info-header">
                            <h4><i class="fas fa-gavel"></i> Proceso Disciplinario</h4>
                        </div>

                        <div class="info-content">

                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Observaciones:</strong>
                                    <textarea name="observacion"
                                              class="form-control campo-editable" rows="4"
                                              readonly>{{ $proceso->observacion }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <strong>Descargos:</strong>
                                    <select name="descargos_presentacion"
                                            class="form-control campo-editable detalle-select"
                                            disabled>
                                        @if($proceso->descargosPresentacionValue() === 'presentado')
                                            <option value="presentado" selected>Presentado (sin medio)</option>
                                        @endif
                                        @foreach(\App\Models\ProcesoDisciplinario::opcionesDescargosPresentacion() as $valor => $etiqueta)
                                            <option value="{{ $valor }}" {{ $proceso->descargosPresentacionValue() === $valor ? 'selected' : '' }}>{{ $etiqueta }}</option>
                                        @endforeach
                                    </select>
                                    <textarea name="descargos"
                                              class="form-control campo-editable"
                                              rows="3"
                                              placeholder="Notas de descargos"
                                              readonly>{{ $proceso->descargos }}</textarea>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Decisión Final:</strong>
                                    <textarea name="decision_final"
                                              class="form-control campo-editable decision-text" rows="4"
                                              readonly>{{ $proceso->decision_final }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <strong>Estado del proceso:</strong>
                                    <div class="detalle-estado">
                                        <p>{{ $estadoHint }}</p>
                                        <div class="detalle-estado-chips">
                                            <span class="sipd-estado sipd-estado--pendiente {{ $fasePendienteOn ? 'is-on' : '' }}">
                                                Pendiente
                                            </span>
                                            <span class="sipd-estado sipd-estado--proceso {{ $faseProcesoOn ? 'is-on' : '' }}">
                                                En Proceso
                                            </span>
                                            @if($proceso->puedeEnviarAProceso() && !auth()->user()->esCoordinadora())
                                                <button type="submit"
                                                        form="form-enviar-proceso"
                                                        class="btn btn-sm btn-success"
                                                        data-confirm="El caso pasará a revisión de la coordinadora."
                                                        data-confirm-title="Enviar a revisión"
                                                        data-confirm-ok="Enviar"
                                                        data-confirm-icon="question">
                                                    Enviar a revisión
                                                </button>
                                            @endif
                                            @if($proceso->estado === 'En Proceso' && auth()->user()->esCoordinadora())
                                                <button type="submit"
                                                        form="form-estado-veredicto"
                                                        name="estado"
                                                        value="Sancionado"
                                                        class="sipd-estado sipd-estado--sancionado"
                                                        data-confirm="Esto cerrará el proceso de forma permanente y ya no se podrá editar."
                                                        data-confirm-title="Sancionar proceso"
                                                        data-confirm-ok="Sancionar"
                                                        data-confirm-danger="1"
                                                        data-confirm-icon="warning">
                                                    Sancionado
                                                </button>
                                                <button type="submit"
                                                        form="form-estado-veredicto"
                                                        name="estado"
                                                        value="Archivado"
                                                        class="sipd-estado sipd-estado--archivado"
                                                        data-confirm="El expediente quedará archivado."
                                                        data-confirm-title="Archivar proceso"
                                                        data-confirm-ok="Archivar"
                                                        data-confirm-icon="question">
                                                    Archivado
                                                </button>
                                            @else
                                                <span class="sipd-estado sipd-estado--sancionado {{ $proceso->estado == 'Sancionado' ? 'is-on' : '' }}">
                                                    Sancionado
                                                </span>
                                                <span class="sipd-estado sipd-estado--archivado {{ $proceso->estado == 'Archivado' ? 'is-on' : '' }}">
                                                    Archivado
                                                </span>
                                            @endif
                                        </div>
                                        @if(auth()->user()->esCoordinadora())
                                            <div class="detalle-asignar">
                                                <label class="mb-0" for="asignar-rh">Responsable RH</label>
                                                <select id="asignar-rh" name="user_id" form="form-asignar-rh" class="form-control form-control-sm" required>
                                                    @foreach($equipoRh as $rh)
                                                        <option value="{{ $rh->id }}" {{ (int) $proceso->user_id === (int) $rh->id ? 'selected' : '' }}>
                                                            {{ $rh->nombreCorto() }}{{ $rh->estaActivo() ? '' : ' (inactivo)' }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" form="form-asignar-rh" class="btn btn-sm btn-success">Asignar</button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="info-box">
                        <div class="info-header">
                            <h4><i class="fas fa-file-upload"></i> Anexos del expediente</h4>
                        </div>
                        <div class="info-content">
                            @include('partials.anexos-expediente', [
                                'anexos' => $proceso->anexos,
                                'casoId' => $proceso->id,
                            ])
                        </div>
                    </div>

                    <!-- Información Adicional -->
                    <div class="info-box">
                        <div class="info-header">
                            <h4><i class="fas fa-info-circle"></i> Información Adicional</h4>
                        </div>
                        <div class="info-content">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Fecha de Registro:</strong>
                                    <p>{{ $proceso->created_at }}</p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Última Actualización:</strong>
                                    <p>{{ $proceso->updated_at }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</form>
@if($proceso->puedeEnviarAProceso() && !auth()->user()->esCoordinadora())
<form id="form-enviar-proceso" action="{{ route('abogado.solicitar_veredicto', $proceso->id) }}" method="POST" class="d-none">
    @csrf
    @method('PUT')
</form>
@endif
@if($proceso->estado === 'En Proceso' && auth()->user()->esCoordinadora())
<form id="form-estado-veredicto" action="{{ route('abogado.actualizarestado', $proceso->id) }}" method="POST" class="d-none">
    @csrf
    @method('PUT')
</form>
@endif
@if(auth()->user()->esCoordinadora())
<form id="form-asignar-rh" action="{{ route('coordinadora.asignar', $proceso->id) }}" method="POST" class="d-none">
    @csrf
    @method('PUT')
</form>
@endif
<style>
    .flujo-doc-card {
        display: block;
        height: 100%;
        text-decoration: none;
        color: inherit;
    }
    .flujo-doc-card .card {
        border-radius: 12px;
        transition: transform .15s ease, box-shadow .15s ease;
        position: relative;
    }
    .flujo-doc-card .card-body { padding-top: 28px; }
    .flujo-doc-card:hover { text-decoration: none; color: inherit; }
    .flujo-doc-card:hover .card {
        transform: translateY(-2px);
        box-shadow: 0 8px 18px rgba(15,23,42,.1);
    }
    .flujo-icon {
        width: 56px;
        height: 56px;
        margin: 0 auto;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
    }
    .flujo-doc-tag {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .03em;
        text-transform: uppercase;
    }
    .flujo-doc--pendiente .card { background: #f8fafc; border-color: #cbd5e1; color: #64748b; }
    .flujo-doc--pendiente .flujo-icon { background: #e2e8f0; color: #64748b; }
    .flujo-doc--pendiente .flujo-doc-tag { background: #e2e8f0; color: #475569; }
    .flujo-doc--borrador .card { background: #fffbeb; border-color: #fde68a; color: #92400e; }
    .flujo-doc--borrador .flujo-icon { background: #fef3c7; color: #b45309; }
    .flujo-doc--borrador .flujo-doc-tag { background: #fde68a; color: #92400e; }
    .flujo-doc--ok .card { background: #f0fdf4; border-color: #86efac; color: #166534; }
    .flujo-doc--ok .flujo-icon { background: #dcfce7; color: #15803d; }
    .flujo-doc--ok .flujo-doc-tag { background: #bbf7d0; color: #166534; }
    .flujo-doc-now {
        display: block;
        position: absolute;
        top: 8px;
        left: 0;
        right: 0;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: #2563eb;
    }
    .flujo-doc-card.is-actual .card {
        box-shadow: 0 0 0 2px #3b82f6, 0 8px 16px rgba(37, 99, 235, .12);
    }
    .flujo-doc-card.is-actual .flujo-doc-now { color: #1d4ed8; }

    .info-box {
        display: flex;
        flex-direction: column;
        width: 100%;
        min-height: 0;
        padding: 0;
        background: #fff;
        border: 1px solid var(--cth-border);
        border-radius: 12px;
        margin-bottom: 25px;
        overflow: visible;
        align-items: stretch;
        box-shadow: var(--cth-shadow);
    }
    
    .info-header {
        background: #f6f8f9;
        padding: 12px 20px;
        border-bottom: 1px solid var(--cth-line);
    }
    
    .info-header h4 {
        margin: 0;
        color: #333;
        font-size: 18px;
    }
    
    .info-header h4 i {
        color: #007bff;
        margin-right: 10px;
    }
    
    .info-content {
        padding: 20px;
        flex: 1;
        min-width: 0;
    }
    
    .info-content strong {
        display: block;
        color: #555;
        margin-bottom: 5px;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .info-content p {
        margin: 0 0 15px 0;
        color: #333;
        font-size: 15px;
        line-height: 1.4;
        padding: 5px 0;
        border-bottom: 1px solid var(--cth-line);
    }

    .campo-editable {
        width: 100%;
        border: 1px solid var(--cth-border);
        border-radius: 4px;
        padding: 8px;
        margin-bottom: 15px;
        background: #fff;
    }

    .campo-editable[readonly],
    .campo-editable:disabled {
        background: transparent;
        border: none;
        padding-left: 0;
        appearance: none;
        -webkit-appearance: none;
    }
    
    .decision-text {
        background: #f8f9fa;
        padding: 10px;
        border-radius: 4px;
        border-left: 4px solid #28a745;
    }

    .detalle-empty {
        border: none !important;
        padding: 0 !important;
        margin: 0 0 8px !important;
        color: #64748b;
        font-size: 14px;
    }
    .detalle-doc-line {
        border: none !important;
        padding: 0 0 6px !important;
        margin: 0 !important;
        font-size: 13px;
    }
    .detalle-docs { max-height: 110px; overflow-y: auto; margin-bottom: 8px; }
    .detalle-file[disabled] { display: none; }
    .detalle-select { margin-bottom: 8px; }
    .detalle-estado {
        padding: 12px 14px;
        border: 1px solid var(--cth-border);
        border-radius: 12px;
        background: #f8fafc;
    }
    .detalle-estado p {
        border: none !important;
        padding: 0 !important;
        margin: 0 0 10px !important;
        color: #64748b;
        font-size: 13px;
        line-height: 1.4;
    }
    .detalle-estado-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
    }
    .detalle-asignar {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid var(--cth-line);
    }
    .detalle-asignar label {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: #64748b;
    }
    .detalle-asignar select { max-width: 260px; }
    
    @media print {

        .card-tools,
        .card-footer,
        .btn {

            display: none !important;
        }

        .info-box {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        body {
            padding: 20px;
        }
    }

</style>

<script>

var MODALIDADES_PLACA = @json(\App\Support\Modalidades::conPlaca());

function syncPlacaDetalle() {
    var mod = document.getElementById('inp-modalidad');
    var box = document.getElementById('placa-field');
    var input = document.getElementById('inp-placa');
    var aplica = MODALIDADES_PLACA.indexOf(((mod && mod.value) || '').trim()) !== -1;
    if (box) box.hidden = !aplica;
    if (input) input.disabled = !aplica;
}

function habilitarEdicion() {
    let campos = document.querySelectorAll('.campo-editable');
    campos.forEach(campo => {
        campo.removeAttribute('readonly');
        campo.removeAttribute('disabled');
    });
    syncPlacaDetalle();
    document.getElementById('btnGuardar').style.display = 'inline-block';
}

document.addEventListener('DOMContentLoaded', function () {
    var mod = document.getElementById('inp-modalidad');
    if (mod) mod.addEventListener('input', syncPlacaDetalle);
    syncPlacaDetalle();
});
</script>
@endsection