@extends('layouts.master')

@section('content')

@if(session('error'))
    <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> {{ session('error') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-warning">
        <strong>Información pendiente:</strong>
        <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
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
                    <h3 class="card-title">Detalle del Proceso Disciplinario</h3>

                    <div class="card-tools">

                        <a href="{{ route('abogado.consultarproceso') }}"
                           class="btn btn-secondary">

                            <i class="fas fa-arrow-left"></i> Volver a la lista

                        </a>

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

                    </div>
                </div>

                <div class="card-body border-bottom bg-light">
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="p-3 bg-white rounded border shadow-sm">
                                <small class="text-uppercase font-weight-bold text-muted d-block mb-2"><i class="fas fa-route text-primary"></i> Estado y Flujo del Proceso:</small>
                                <div class="d-flex align-items-center justify-content-between text-center position-relative">
                                    @php
                                        $e = $proceso->estado;
                                        $fase1 = in_array($e, ['Pendiente', 'En Proceso', 'Sancionado', 'Archivado']);
                                        $fase2 = in_array($e, ['En Proceso', 'Sancionado', 'Archivado']);
                                        $fase3 = in_array($e, ['Sancionado', 'Archivado']);
                                    @endphp
                                    <div class="flex-fill">
                                        <span class="badge badge-{{ $fase1 ? 'success' : 'secondary' }} p-2 mb-1"><i class="fas fa-check-circle"></i> 1. Apertura / Notificación</span>
                                        <small class="d-block text-muted">Auto Disciplinario o Comprobación</small>
                                    </div>
                                    <i class="fas fa-chevron-right text-muted mx-2"></i>
                                    <div class="flex-fill">
                                        <span class="badge badge-{{ $fase2 ? 'primary' : 'secondary' }} p-2 mb-1"><i class="{{ $fase2 ? 'fas fa-spinner fa-spin' : 'far fa-circle' }}"></i> 2. Descargos / Pruebas</span>
                                        <small class="d-block text-muted">Acta de Cargos y Descargos</small>
                                    </div>
                                    <i class="fas fa-chevron-right text-muted mx-2"></i>
                                    <div class="flex-fill">
                                        <span class="badge badge-{{ $fase3 ? ($e == 'Sancionado' ? 'danger' : 'dark') : 'secondary' }} p-2 mb-1"><i class="fas fa-gavel"></i> 3. Fallo / {{ $e == 'Archivado' ? 'Archivo' : 'Sanción' }}</span>
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
                        Los 3 documentos oficiales de este expediente utilizan el formato de plantillas institucionales de Cootranshuila.
                    </p>

                    <div class="row justify-content-center">
                        @if($proceso->tipo_proceso !== 'comprobacion')
                        {{-- 1. Apertura Disciplinaria --}}
                        <div class="col-md-4 mb-3">
                            <div class="card h-100 border shadow-sm" style="background: linear-gradient(145deg, #ffffff, #f8f9fa); border-radius: 12px; overflow: hidden;">
                                <div class="card-body p-4 text-center">
                                    <div class="mb-3">
                                        <div style="width: 60px; height: 60px; background: rgba(0, 123, 255, 0.1); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-balance-scale text-primary fa-2x"></i>
                                        </div>
                                    </div>
                                    <h6 class="font-weight-bold mb-2" style="font-size:14px;">1.1 GA-FT-045 Apertura Proceso Disciplinarios</h6>
                                    <p class="text-muted small mb-3">Documento principal inicial</p>
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="{{ route('documentos.download', [$proceso->id, 'disciplinario']) }}" class="btn btn-sm btn-primary px-3 rounded-pill shadow-sm">
                                            <i class="fas fa-file-word mr-1"></i> DOCX
                                        </a>
                                        <a href="{{ route('documentos.download', [$proceso->id, 'disciplinario']) }}?format=pdf" class="btn btn-sm btn-danger px-3 rounded-pill shadow-sm">
                                            <i class="fas fa-file-pdf mr-1"></i> PDF
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($proceso->tipo_proceso !== 'disciplinario')
                        {{-- 2. Apertura Comprobación --}}
                        <div class="col-md-4 mb-3">
                            <div class="card h-100 border shadow-sm" style="background: linear-gradient(145deg, #ffffff, #fefce8); border-radius: 12px; overflow: hidden;">
                                <div class="card-body p-4 text-center">
                                    <div class="mb-3">
                                        <div style="width: 60px; height: 60px; background: rgba(234, 179, 8, 0.15); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-search text-warning fa-2x"></i>
                                        </div>
                                    </div>
                                    <h6 class="font-weight-bold mb-2" style="font-size:14px;">1.1 GA-FT-045 Apertura Proceso Comprobación</h6>
                                    <p class="text-muted small mb-3">Ruta de comprobación</p>
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="{{ route('documentos.download', [$proceso->id, 'comprobacion']) }}" class="btn btn-sm btn-primary px-3 rounded-pill shadow-sm">
                                            <i class="fas fa-file-word mr-1"></i> DOCX
                                        </a>
                                        <a href="{{ route('documentos.download', [$proceso->id, 'comprobacion']) }}?format=pdf" class="btn btn-sm btn-danger px-3 rounded-pill shadow-sm">
                                            <i class="fas fa-file-pdf mr-1"></i> PDF
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($proceso->estado !== 'Pendiente' || $proceso->tipo_proceso === 'acta')
                        {{-- 3. Acta Cargos y Descargos --}}
                        <div class="col-md-4 mb-3">
                            <div class="card h-100 border shadow-sm" style="background: linear-gradient(145deg, #ffffff, #faf5ff); border-radius: 12px; overflow: hidden;">
                                <div class="card-body p-4 text-center">
                                    <div class="mb-3">
                                        <div style="width: 60px; height: 60px; background: rgba(168, 85, 247, 0.1); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-gavel fa-2x" style="color:#9333ea;"></i>
                                        </div>
                                    </div>
                                    <h6 class="font-weight-bold mb-2" style="font-size:14px;">2. Acta de cargos y descargos (grabación)</h6>
                                    <p class="text-muted small mb-3">Diligencia de descargos</p>
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="{{ route('documentos.download', [$proceso->id, 'acta']) }}" class="btn btn-sm btn-primary px-3 rounded-pill shadow-sm">
                                            <i class="fas fa-file-word mr-1"></i> DOCX
                                        </a>
                                        <a href="{{ route('documentos.download', [$proceso->id, 'acta']) }}?format=pdf" class="btn btn-sm btn-danger px-3 rounded-pill shadow-sm">
                                            <i class="fas fa-file-pdf mr-1"></i> PDF
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
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
                                    <textarea name="descargos"
                                              class="form-control campo-editable"
                                              rows="3"
                                              readonly>{{ $proceso->descargos }}</textarea>
                                </div>
                                <div class="col-md-5">
                                    <strong>Decisión Final:</strong>
                                    <textarea name="decision_final"
                                              class="form-control campo-editable decision-text"rows="3"
                                              readonly>{{ $proceso->decision_final }}</textarea>
</div>
                                    <!-- Estado del Proceso -->
<div class="col-md-5">
    <strong>Cambiar Estado Rápidamente:</strong>
    <p class="text-muted small mb-2">Selecciona un estado para actualizar el proceso inmediatamente.</p>
    <form action="{{ route('abogado.actualizarestado', $proceso->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="d-flex gap-2 flex-wrap">
            <button type="submit" name="estado" value="Pendiente" class="btn btn-sm {{ $proceso->estado == 'Pendiente' ? 'btn-warning text-white' : 'btn-outline-warning text-dark' }} font-weight-bold shadow-sm">
                Pendiente
            </button>
            <button type="submit" name="estado" value="En Proceso" class="btn btn-sm {{ $proceso->estado == 'En Proceso' ? 'btn-primary' : 'btn-outline-primary' }} font-weight-bold shadow-sm">
                En Proceso
            </button>
            <button type="submit" name="estado" value="Sancionado" class="btn btn-sm {{ $proceso->estado == 'Sancionado' ? 'btn-danger' : 'btn-outline-danger' }} font-weight-bold shadow-sm" onclick="return confirm('¿Seguro que deseas sancionar? Esto cerrará el proceso permanentemente y no podrás editarlo después.');">
                Sancionado
            </button>
            <button type="submit" name="estado" value="Archivado" class="btn btn-sm {{ $proceso->estado == 'Archivado' ? 'btn-secondary' : 'btn-outline-secondary' }} font-weight-bold shadow-sm">
                Archivado
            </button>
        </div>
    </form>
</div>
                                </div>
                            </div>
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
<style>
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

    .campo-editable[readonly] {
        background: transparent;
        border: none;
        padding-left: 0;
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
        var a = document.createElement('a');
        a.href = "{{ route('documentos.download', [$proceso->id, session('autodownload')]) }}";
        a.target = "_blank";
        document.body.appendChild(a);
        a.click();
    }, 1000); // 1 segundo de retraso para que el toast cargue
});
@endif
</script>
@endsection