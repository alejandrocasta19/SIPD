@extends('layouts.master')

@section('content')

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

                                <div class="col-md-4">
                                    <strong>Placa del Vehículo:</strong>

                                    <input type="text"
                                           name="placa"
                                           value="{{ $proceso->placa }}"
                                           class="form-control campo-editable"
                                           readonly>
                                </div>

                                <div class="col-md-4">
                                    <strong>Ruta:</strong>

                                    <input type="text"
                                           name="ruta"
                                           value="{{ $proceso->ruta }}"
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

                                    @if($proceso->documento_falta)

                                        <p>
                                            <a href="{{ asset('storage/' . $proceso->documento_falta) }}"
                                               target="_blank"
                                               class="btn btn-sm btn-info">

                                                <i class="fas fa-file-pdf"></i> Ver Documento

                                            </a>
                                        </p>

                                    @else

                                        <p>No hay documento adjunto</p>

                                    @endif

                                    <input type="file"
                                           name="documento_falta" class="form-control campo-editable" disabled>
                                     </input>
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

    <strong>Estado del Proceso:</strong>

   <div id="estadoBotones" style="display:none; margin-top:10px;">

    <div class="d-flex gap-2 flex-wrap">

        <!-- PENDIENTE -->
        <label class="btn btn-warning">

            <input type="radio"
                   name="estado"
                   value="Pendiente"
                   {{ $proceso->estado == 'Pendiente' ? 'checked' : '' }}>

            Pendiente

        </label>

        <!-- EN PROCESO -->
        <label class="btn btn-primary">

            <input type="radio"
                   name="estado"
                   value="En Proceso"
                   {{ $proceso->estado == 'En Proceso' ? 'checked' : '' }}>

            En Proceso

        </label>

        <!-- SANCIONADO -->
        <label class="btn btn-success">

            <input type="radio"
                   name="estado"
                   value="Sancionado"
                   {{ $proceso->estado == 'Sancionado' ? 'checked' : '' }}>

            Sancionado

        </label>

        <!-- ARCHIVADO -->
        <label class="btn btn-secondary">

            <input type="radio"
                   name="estado"
                   value="Archivado"
                   {{ $proceso->estado == 'Archivado' ? 'checked' : '' }}>

            Archivado

        </label>

    </div>

</div>
    </div>

    <p id="estadoTexto"
       class="mt-2">

        {{ $proceso->estado }}

    </p>

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

    document.getElementById('estadoBotones').style.display = 'block';

    document.getElementById('estadoTexto').style.display = 'none';
}

</script>
@endsection