@extends('layouts.master')

@section('content')

@if(session('success'))
<script>
    Swal.fire({
        icon: 'success',
        title: 'Proceso Registrado',
        text: '{{ session('success') }}',
        confirmButtonColor: '#3085d6'
    });
</script>
@endif

<style>
    .form-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 20px;
    }

    .form-card {
        background: #fff;
        border-radius: 8px;
        border: 1px solid #ddd;
        overflow: hidden;
    }

    .form-header {
        background: #f5f5f5;
        padding: 20px;
        text-align: center;
        border-bottom: 1px solid #ddd;
    }

    .form-header h1 {
        font-size: 24px;
        margin: 0;
        color: #333;
    }

    .form-header p {
        color: #666;
        margin-top: 8px;
        font-size: 14px;
    }

    .form-body {
        padding: 30px;
    }

    .form-section {
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }

    .form-section h3 {
        color: #555;
        margin-bottom: 15px;
        font-size: 18px;
        font-weight: 600;
    }

    .form-group {
        margin-bottom: 15px;
    }

    label {
        font-weight: 600;
        color: #333;
        display: block;
        margin-bottom: 5px;
        font-size: 13px;
    }

    input,
    select,
    textarea {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 14px;
        box-sizing: border-box;
    }

    input:focus,
    select:focus,
    textarea:focus {
        outline: none;
        border-color: #888;
    }

    .row {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }

    .btn-submit {
        background: #337ab7;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        font-size: 14px;
        cursor: pointer;
        width: 100%;
    }

    .btn-submit:hover {
        background: #286090;
    }

    .falta-group {
        display: flex;
        gap: 10px;
        align-items: flex-end;
    }

    .falta-group .form-group {
        flex: 1;
        margin-bottom: 0;
    }

    .btn-subir {
        background: #5cb85c;
        color: white;
        padding: 8px 15px;
        border: none;
        border-radius: 4px;
        font-size: 13px;
        cursor: pointer;
        height: 35px;
        white-space: nowrap;
    }

    .btn-subir:hover {
        background: #4cae4c;
    }
</style>

<div class="form-container">

    <div class="form-card">

        <div class="form-header">
            <h1>Registro de Proceso Disciplinario</h1>
            <p>Complete la información del proceso</p>
        </div>

        <div class="form-body">

            <form action="{{ route('abogado.registro.store') }}" method="POST" enctype="multipart/form-data">

                @csrf

                <div class="form-section">

                    <h3>Información del Conductor</h3>

                    <div class="row">

                        <div class="form-group">
                            <label>Nombre del Conductor</label>

                            <input type="text"
                                   name="nombre"
                                   placeholder="Escriba el nombre del conductor"
                                   required>
                        </div>

                        <div class="form-group">
                            <label>Cédula</label>

                            <input type="text"
                                   name="cedula"
                                   placeholder="Escriba la cédula">
                        </div>

                       <div class="form-group">

    <label>Modalidad o Cargo</label>

    <select name="modalidad" class="form-control">

        <option value="">Seleccione una opción</option>

        <option value="Premium">Premium</option>
        <option value="Dobleyo">Dobleyo</option>
        <option value="Platino Express">Platino Express</option>
        <option value="Platino Jet">Platino Jet</option>
        <option value="Administrativo">Administrativo</option>
        <option value="Call Center">Call Center</option>
        <option value="Asistente de Ventas">Asistente de Ventas</option>
        <option value="Monitoreo">Monitoreo</option>
        <option value="Urbano">Urbano</option>
        <option value="Aerovans">Aerovans</option>
        <option value="Encomiendas">Encomiendas</option>
        <option value="Mixtos">Mixtos</option>

    </select>

</div>
                        <div class="form-group">
                            <label>Placa del Vehículo</label>
                            <input type="text"
                                   name="placa"
                                   placeholder="Escriba la placa">
                        </div>
                        <div class="form-group">
                            <label>Ruta</label>
                            <input type="text"
                                   name="ruta"
                                   placeholder="Escriba la ruta">
                        </div>
                        <div class="form-group">
                            <label>Teléfono</label>
                            <input type="text"
                                   name="telefono"
                                   placeholder="Escriba el teléfono">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Información de la Falta</h3>
                    <div class="row">

                        <div class="form-group">
                            <label>Tipo de Falta</label>

                            <input type="text"
                                   name="tipo_falta"
                                   placeholder="Escriba el tipo de falta">
                        </div>

                        <div class="form-group">
                            <label>Fecha de la Falta</label>

                            <input type="date"
                                   name="fecha_falta">
                        </div>

                    </div>

                    <div class="falta-group">

                        <div class="form-group">

                            <label>Descripción de la Falta</label>

                            <textarea name="descripcion_falta"
                                      rows="3"
                                      placeholder="Escriba la descripción de la falta cometida"></textarea>

                        </div>

                        <button type="button"
                                class="btn-subir"
                                onclick="document.getElementById('documento_falta').click()">

                            📄 Subir documento

                        </button>

                        <input type="file"
                               id="documento_falta"
                               name="documento_falta"
                               style="display: none;">

                    </div>

                </div>

                <div class="form-section">

                    <h3>Proceso Disciplinario</h3>

                    <div class="form-group">

                        <label>Observaciones</label>

                        <textarea name="observacion"
                                  rows="3"
                                  placeholder="Escriba observaciones adicionales..."
                                  style="resize: vertical;"></textarea>

                    </div>

                    <div class="form-group">

                        <label>Descargos</label>

                        <textarea name="descargos"
                                  rows="3"
                                  placeholder="Escriba los descargos..."
                                  style="resize: vertical;"></textarea>

                    </div>

                    <div class="form-group">

                        <label>Decisión Final</label>

                        <textarea name="decision_final"
                                  rows="3"
                                  placeholder="Escriba la decisión final..."
                                  style="resize: vertical;"></textarea>

                    </div>

                </div>

                <button type="submit" class="btn-submit">
                    Guardar Proceso
                </button>

            </form>

        </div>

    </div>

</div>

@endsection