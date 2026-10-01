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

                        <a href="{{ route('abogado.consultarproceso') }}"
                           class="btn btn-secondary">

                            <i class="fas fa-arrow-left"></i> Volver a la lista

                        </a>

                        @if(auth()->user()->puede('editar_casos'))
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
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="p-3 bg-white rounded border shadow-sm">
                                <small class="text-uppercase font-weight-bold text-muted d-block mb-2"><i class="fas fa-route" style="color:var(--cth-green);"></i> Estado y Flujo del Proceso:</small>
                                <div class="sipd-flujo d-flex align-items-center justify-content-between text-center position-relative">
                                    @php
                                        $e = $proceso->estado;
                                        $fase1 = in_array($e, ['Pendiente', 'En Proceso', 'Sancionado', 'Archivado']);
                                        $fase2 = in_array($e, ['En Proceso', 'Sancionado', 'Archivado']);
                                        $fase3 = in_array($e, ['Sancionado', 'Archivado']);
                                        $fase3Cls = $e === 'Archivado' ? 'archivado' : 'sancionado';
                                    @endphp
                                    <div class="flex-fill">
                                        <span class="sipd-estado sipd-estado--pendiente {{ $fase1 && !$fase2 ? 'is-on' : '' }}"><i class="fas fa-check-circle"></i> 1. Apertura / Notificación</span>
                                        <small class="d-block text-muted">Auto Disciplinario o Comprobación</small>
                                    </div>
                                    <i class="fas fa-chevron-right text-muted mx-2"></i>
                                    <div class="flex-fill">
                                        <span class="sipd-estado sipd-estado--proceso {{ $fase2 && !$fase3 ? 'is-on' : '' }}"><i class="{{ $fase2 ? 'fas fa-spinner' : 'far fa-circle' }}"></i> 2. Descargos / Pruebas</span>
                                        <small class="d-block text-muted">Acta de Cargos y Descargos</small>
                                    </div>
                                    <i class="fas fa-chevron-right text-muted mx-2"></i>
                                    <div class="flex-fill">
                                        <span class="sipd-estado sipd-estado--{{ $fase3Cls }} {{ $fase3 ? 'is-on' : '' }}"><i class="fas fa-gavel"></i> 3. Fallo / {{ $e == 'Archivado' ? 'Archivo' : 'Sanción' }}</span>
                                        <small class="d-block text-muted">Resolución Final</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                        <h5 class="mb-0 font-weight-bold text-dark">
                            <i class="fas fa-file-signature text-success"></i> Documentos Oficiales del Caso
                        </h5>
                        <a class="btn btn-sm btn-success" href="{{ route('documentos.index', $proceso->id) }}">
                            <i class="fas fa-folder-open"></i> Ver todos los documentos y evidencias
                        </a>
                    </div>
                    <p class="text-muted small mb-3">
                        Solo se muestra el formato que se usó en cada espacio.
                    </p>

                    <div class="row justify-content-center">
                        @php
                            $slotCards = [
                                'apertura' => ['bg' => '#f8f9fa', 'iconBg' => 'rgba(0, 123, 255, 0.1)', 'icon' => 'fas fa-balance-scale text-primary'],
                                'acta' => ['bg' => '#faf5ff', 'iconBg' => 'rgba(168, 85, 247, 0.1)', 'icon' => 'fas fa-gavel', 'iconStyle' => 'color:#9333ea;'],
                                'resolucion' => ['bg' => '#fef2f2', 'iconBg' => 'rgba(185, 28, 28, 0.1)', 'icon' => 'fas fa-stamp text-danger'],
                                'archivo' => ['bg' => '#f8fafc', 'iconBg' => 'rgba(71, 85, 105, 0.12)', 'icon' => 'fas fa-archive text-secondary'],
                            ];
                        @endphp
                        @foreach(\App\Models\CasoDocumentoEstado::SLOTS as $slot => $variantes)
                            @php
                                $tipoSlot = $proceso->varianteDelSlot($slot);
                                $card = $slotCards[$slot];
                            @endphp
                            <div class="col-md-3 mb-3">
                                <div class="card h-100 border shadow-sm" style="background: linear-gradient(145deg, #ffffff, {{ $card['bg'] }}); border-radius: 12px; overflow: hidden;">
                                    <div class="card-body p-4 text-center">
                                        <div class="mb-3">
                                            <div style="width: 60px; height: 60px; background: {{ $card['iconBg'] }}; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;">
                                                <i class="{{ $card['icon'] }} fa-2x" @if(!empty($card['iconStyle'])) style="{{ $card['iconStyle'] }}" @endif></i>
                                            </div>
                                        </div>
                                        <h6 class="font-weight-bold mb-1" style="font-size:13px;">{{ \App\Models\CasoDocumentoEstado::SLOT_LABELS[$slot] }}</h6>
                                        <p class="text-muted small mb-3">{{ \App\Models\CasoDocumentoEstado::etiqueta($tipoSlot) }}</p>
                                        @if($tipoSlot === 'terminacion')
                                            <p class="text-muted small mb-3">Imprimir para firma del gerente y subir el escaneo en Anexos.</p>
                                        @endif
                                        <div class="d-flex justify-content-center gap-2">
                                            <a href="{{ route('documentos.download', [$proceso->id, $tipoSlot]) }}" class="btn btn-sm px-3 rounded-pill shadow-sm btn-docx">
                                                <i class="fas fa-file-word mr-1"></i> DOCX
                                            </a>
                                            <a href="{{ route('documentos.download', [$proceso->id, $tipoSlot]) }}?format=pdf" class="btn btn-sm btn-danger px-3 rounded-pill shadow-sm">
                                                <i class="fas fa-file-pdf mr-1"></i> PDF
                                            </a>
                                        </div>
                                        @php $anexosSlot = $proceso->anexos->filter(fn ($anexo) => $anexo->slot() === $slot); @endphp
                                        @if($anexosSlot->isNotEmpty())
                                            <div class="text-left mt-3">
                                                @foreach($anexosSlot as $anexo)
                                                    <div class="small mb-1">
                                                        <a href="{{ route('abogado.anexos.download', $anexo->id) }}">{{ $anexo->nombreVisible() }}</a>
                                                        @if($anexo->nombre_original && $anexo->nombre_original !== $anexo->nombreVisible())
                                                            <span class="d-block text-muted">{{ $anexo->nombre_original }}</span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                
                <div class="card-body">
                    
                    <!-- Información del Conductor -->
                    <div class="info-box">

                        <div class="info-header">
                            <h4><i class="fas fa-user"></i>Información del Conductor</h4>
                        </div>

                        <div class="info-content">

                            <div class="row">

                                <div class="col-md-4">
                                    <strong>Nombre del Conductor:</strong>

                                    <input type="text"
                                           name="nombre"
                                           value="{{ $proceso->nombre }}"
                                           class="form-control campo-editable"
                                           readonly>
                                </div>

                                <div class="col-md-4">
                                    <strong>Cédula:</strong>

                                    <input type="text"
                                           name="cedula"
                                           value="{{ $proceso->cedula }}"
                                           class="form-control campo-editable"
                                           readonly>
                                </div>

                                <div class="col-md-3">
                                    <strong>Teléfono:</strong>

                                    <input type="text"
                                           name="telefono"
                                           value="{{ $proceso->telefono }}"
                                           class="form-control campo-editable"
                                           readonly>
                                </div>

                                <div class="col-md-4">
                                    <strong>Modalidad o Cargo:</strong>

                                    <input type="text"
                                           name="modalidad"
                                           value="{{ $proceso->modalidad }}"
                                           class="form-control campo-editable"
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
                                        <div style="max-height:100px;overflow-y:auto;margin-bottom:8px;">
                                            @foreach($proceso->evidencias as $evidencia)
                                            <p class="mb-1" style="font-size:13px;">
                                                <a href="{{ route('documentos.evidencias.download', [$proceso->id, $evidencia->id]) }}" class="text-info">
                                                    <i class="fas fa-paperclip"></i> {{ $evidencia->nombre_original }}
                                                </a>
                                            </p>
                                            @endforeach
                                        </div>
                                    @elseif($proceso->documento_falta)
                                        <p>
                                            <a href="{{ route('abogado.documento-falta', $proceso->id) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-file-pdf"></i> Ver Documento Principal
                                            </a>
                                        </p>
                                    @else
                                        <p>No hay documento adjunto</p>
                                    @endif

                                    <input type="file" name="documento_falta[]" multiple class="form-control campo-editable" disabled>
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

                                <div class="col-md-4">

                                    <strong>Observaciones:</strong>

                                    <textarea name="observacion"
                                              class="form-control campo-editable" rows="3"
                                              readonly>{{ $proceso->observacion }}</textarea>
                                </div>
                                <div class="col-md-4">
                                    <strong>Descargos:</strong>
                                    <select name="descargos_presentacion"
                                            class="form-control campo-editable"
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
                                              readonly>{{ $proceso->descargos }}</textarea>
                                </div>
                                <div class="col-md-5">
                                    <strong>Decisión Final:</strong>
                                    <textarea name="decision_final"
                                              class="form-control campo-editable decision-text" rows="3"
                                              readonly>{{ $proceso->decision_final }}</textarea>
                                </div>
                                <div class="col-md-5">
                                    <strong>Estado del proceso:</strong>
                                    <p class="text-muted small mb-2">
                                        Pendiente si aún no hay documento generado. Enviar lo pasa a En Proceso. El veredicto lo cierra como Sancionado o Archivado.
                                    </p>
                                    <div class="d-flex gap-2 flex-wrap align-items-center">
                                        <span class="sipd-estado sipd-estado--pendiente {{ $proceso->estado == 'Pendiente' ? 'is-on' : '' }}">
                                            Pendiente
                                        </span>
                                        @if($proceso->puedeEnviarAProceso() && !auth()->user()->esCoordinadora())
                                            <button type="submit"
                                                    form="form-enviar-proceso"
                                                    class="sipd-estado sipd-estado--proceso"
                                                    data-confirm="El caso pasará a En Proceso y quedará pendiente de veredicto."
                                                    data-confirm-title="Enviar caso"
                                                    data-confirm-ok="Enviar"
                                                    data-confirm-icon="question">
                                                Enviar
                                            </button>
                                        @else
                                            <span class="sipd-estado sipd-estado--proceso {{ $proceso->estado == 'En Proceso' ? 'is-on' : '' }}">
                                                En Proceso
                                            </span>
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
                                            @if($proceso->estado === 'En Proceso')
                                                <small class="text-muted d-block w-100 mt-1">Pendiente de veredicto de la coordinadora.</small>
                                            @endif
                                        @endif
                                    </div>
                                    @if(auth()->user()->esCoordinadora())
                                        <div class="mt-3 d-flex gap-2 align-items-center flex-wrap">
                                            <label class="mb-0 small text-muted font-weight-bold" for="asignar-rh">Responsable RH</label>
                                            <select id="asignar-rh" name="user_id" form="form-asignar-rh" class="form-control form-control-sm" style="max-width:260px">
                                                <option value="">Sin asignar</option>
                                                @foreach($equipoRh as $rh)
                                                    <option value="{{ $rh->id }}" {{ (int) $proceso->user_id === (int) $rh->id ? 'selected' : '' }}>{{ $rh->name }}</option>
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
                <div class="card-footer">
                    <div class="text-center">
                        <button class="btn btn-success">
                            <i class="fas fa-print"></i> Imprimir Detalle
                        </button>
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
    .btn-docx {
        background: #2563eb !important;
        border: 1px solid #2563eb !important;
        color: #fff !important;
    }
    .btn-docx:hover {
        background: #1d4ed8 !important;
        border-color: #1d4ed8 !important;
        color: #fff !important;
    }
    .info-box {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 8px;
        margin-bottom: 25px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .info-header {
        background: #f8f9fa;
        padding: 12px 20px;
        border-bottom: 2px solid #007bff;
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
        border-bottom: 1px solid #f0f0f0;
    }

    .campo-editable {
        width: 100%;
        border: 1px solid #ddd;
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

function habilitarEdicion() {
    let campos = document.querySelectorAll('.campo-editable');
    campos.forEach(campo => {
        campo.removeAttribute('readonly');
        campo.removeAttribute('disabled');
    });
    document.getElementById('btnGuardar').style.display = 'inline-block';
}

@if(session('autodownload'))
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        var iframe = document.createElement('iframe');
        iframe.style.display = 'none';
        iframe.src = "{{ route('documentos.download', [$proceso->id, session('autodownload')]) }}";
        document.body.appendChild(iframe);
    }, 400);
});
@endif
</script>
@endsection