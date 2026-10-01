@extends('layouts.master')

@php
    $pageTitle = 'Anexos escaneados';
@endphp

@section('page-header')
    <div class="proc-head">
        <div>
            <h1>Anexos escaneados</h1>
            <p>Elige el formato oficial. El archivo queda en ese espacio del expediente. La terminación entra ya firmada por gerencia.</p>
        </div>
        <div class="proc-head-side">
            <span class="stat-chip">{{ $conteos['todos'] }} total</span>
            @if($conteos['pendiente'] > 0)
                <span class="stat-chip yellow">{{ $conteos['pendiente'] }} Pendiente firma</span>
            @endif
            @if($conteos['firmado'] > 0)
                <span class="stat-chip green">{{ $conteos['firmado'] }} Firmados</span>
            @endif
        </div>
    </div>
@endsection

@section('styles')
<style>
    .anexo-upload {
        background: #fff;
        border-radius: 16px;
        padding: 18px;
        margin-bottom: 16px;
        box-shadow: 0 1px 2px rgba(15,23,42,.04);
        border: 1px solid var(--cth-border);
    }
    .anexo-upload h3 { margin: 0 0 4px; font-size: 15px; }
    .anexo-upload > p { margin: 0 0 14px; color: #64748b; font-size: 13px; }
    .anexo-grid { display: grid; grid-template-columns: minmax(0,1.4fr) minmax(0,1.2fr) auto; gap: 10px; align-items: end; }
    .anexo-upload label.field { display: block; font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #64748b; margin-bottom: 6px; }
    .anexo-upload select {
        width: 100%; height: 42px; border: 1px solid var(--cth-border); border-radius: 10px;
        padding: 0 10px; font: inherit; background: #fff;
    }
    .anexo-cargar {
        height: 42px; border: 0; border-radius: 10px; padding: 0 18px; font-weight: 700;
        background: var(--cth-green); color: #fff; cursor: pointer; white-space: nowrap;
        display: inline-flex; align-items: center; gap: 8px;
    }
    .hint { font-size: 12px; color: #64748b; margin: 10px 0 0; }
    .sr-file { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0,0,0,0); }

    .toolbar { margin-bottom: 16px; }
    .search { position: relative; }
    .search i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
    .search input { width: 100%; height: 44px; border: 0; border-radius: 12px; background: #fff; padding: 0 14px 0 40px; font: inherit; font-size: 14px; box-shadow: 0 1px 2px rgba(15,23,42,.04); outline: none; }

    .table-card { background: #fff; border-radius: 18px; overflow: hidden; box-shadow: 0 1px 2px rgba(15,23,42,.04); }
    table.proc { width: 100%; border-collapse: collapse; font-size: 13px; }
    table.proc th { text-align: left; padding: 14px 12px; color: #94a3b8; font-size: 11px; letter-spacing: .06em; font-weight: 700; border: 1px solid var(--cth-border); white-space: nowrap; text-transform: uppercase; }
    table.proc td { padding: 14px 12px; border: 1px solid var(--cth-border); color: #334155; vertical-align: middle; }
    table.proc tbody tr:hover { background: #fafbfc; }
    .id { color: var(--cth-green); font-weight: 700; text-decoration: none; }
    .name { font-weight: 600; color: #0f172a; }
    .hub-sub { display: block; color: #64748b; font-size: 12px; margin-top: 3px; }
    .empty { text-align: center; padding: 40px 16px; color: #94a3b8; }
    .acts { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; justify-content: flex-end; }
    .acts form { margin: 0; }
    .acts .action-btn {
        background: #f1f5f9; color: #334155; border: 1px solid var(--cth-border);
        padding: 8px 14px; border-radius: 999px; font-size: 12px; font-weight: 700;
        text-decoration: none; display: inline-flex; align-items: center; gap: 6px; cursor: pointer;
        white-space: nowrap; line-height: 1;
    }
    .acts .action-btn:hover { background: #e2e8f0; color: #0f172a; }
    .acts .action-btn.download {
        background: var(--cth-green);
        color: #fff;
        border-color: transparent;
        padding: 8px 16px;
        box-shadow: 0 1px 2px rgba(0, 104, 55, .22);
    }
    .acts .action-btn.download i { font-size: 12px; }
    .acts .action-btn.download:hover { filter: brightness(1.08); color: #fff; }
    .acts .action-btn.danger { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }
    .acts .action-btn.veredicto { background: #2563eb; color: #fff; border-color: #1d4ed8; }
    table.proc th:last-child,
    table.proc td:last-child { width: 1%; white-space: nowrap; }
    #modalEditarAnexo .sipd-dialog { max-width: 480px; }

    .pager { display: flex; justify-content: space-between; align-items: center; padding: 14px 16px; color: #94a3b8; font-size: 13px; border-top: 1px solid var(--cth-line); }

    @media (max-width: 640px) {
        .anexo-grid { grid-template-columns: 1fr; }
        .table-card { overflow-x: auto; }
    }
</style>
@endsection

@section('content')
<form class="anexo-upload" id="anexo-upload-form" action="{{ route('abogado.anexos.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <h3>Cargar documento</h3>
    <p>Selecciona el documento. Terminación: PDF, JPG o PNG firmado. Los demás: Word o PDF de un caso anterior.</p>

    <div class="anexo-grid">
        <div>
            <label class="field" for="anexo-caso">Proceso</label>
            <select id="anexo-caso" name="caso_id" required>
                <option value="">Selecciona un proceso…</option>
                @foreach($casos as $caso)
                    <option value="{{ $caso->id }}" @if((string) old('caso_id', $casoSeleccionado ?? '') === (string) $caso->id) selected @endif>
                        PRO-{{ str_pad($caso->id, 3, '0', STR_PAD_LEFT) }} · {{ $caso->nombre }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="field" for="anexo-tipo">Documento</label>
            <select id="anexo-tipo" name="tipo" required>
                <option value="">Selecciona el documento…</option>
                @foreach(\App\Models\CasoAnexo::tipos() as $valor => $etiqueta)
                    <option value="{{ $valor }}" @if(old('tipo') === $valor) selected @endif>{{ $etiqueta }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <input class="sr-file" id="anexo-file" type="file" name="archivo" accept=".pdf,.doc,.docx" required>
            <button type="button" class="anexo-cargar" id="anexo-cargar">
                <i class="fas fa-upload"></i> Cargar
            </button>
        </div>
    </div>
    <p class="hint" id="anexo-hint"></p>
</form>

<div class="chips">
    <a class="chip {{ $filtro === 'todos' ? 'active' : '' }}" href="{{ route('abogado.anexos', array_filter(['per_page' => request('per_page'), 'caso' => request('caso')])) }}">
        Todos <span class="n">({{ $conteos['todos'] }})</span>
    </a>
    @foreach(\App\Models\CasoDocumentoEstado::SLOT_LABELS as $slot => $etiqueta)
        <a class="chip {{ $filtro === $slot ? 'active' : '' }}" href="{{ route('abogado.anexos', array_filter(['filtro' => $slot, 'per_page' => request('per_page'), 'caso' => request('caso')])) }}">
            {{ $etiqueta }} <span class="n">({{ $conteos[$slot] ?? 0 }})</span>
        </a>
    @endforeach
    @if(($conteos['existente'] ?? 0) > 0 || $filtro === 'existente')
    <a class="chip {{ $filtro === 'existente' ? 'active' : '' }}" href="{{ route('abogado.anexos', array_filter(['filtro' => 'existente', 'per_page' => request('per_page'), 'caso' => request('caso')])) }}">
        Archivo previo <span class="n">({{ $conteos['existente'] }})</span>
    </a>
    @endif
    @if(($conteos['pendiente'] ?? 0) > 0 || $filtro === 'pendiente')
    <a class="chip {{ $filtro === 'pendiente' ? 'active' : '' }}" href="{{ route('abogado.anexos', array_filter(['filtro' => 'pendiente', 'per_page' => request('per_page'), 'caso' => request('caso')])) }}">
        <i class="fas fa-circle" style="color:#d97706;"></i> Pendiente firma <span class="n">({{ $conteos['pendiente'] }})</span>
    </a>
    @endif
</div>

<form class="toolbar" method="GET" action="{{ route('abogado.anexos') }}">
    @if(request('filtro'))
        <input type="hidden" name="filtro" value="{{ request('filtro') }}">
    @endif
    <div class="search">
        <i class="fas fa-search"></i>
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por proceso, conductor o nombre de archivo...">
    </div>
</form>

<div class="table-card">
    <table class="proc">
        <thead>
            <tr>
                <th>Proceso</th>
                <th>Documento</th>
                <th>Lugar</th>
                <th>Estado</th>
                <th>Cargado</th>
                <th style="text-align:right;">Acciones</th>
            </tr>
        </thead>
        <tbody>
        @forelse($anexos as $anexo)
            @php
                $estadoDot = [
                    'cargado' => 'dot-arch',
                    'pendiente_firma' => 'dot-pend',
                    'firmado' => 'dot-green',
                ][$anexo->estado] ?? 'dot-arch';
            @endphp
            <tr>
                <td>
                    <a class="id" href="{{ route('abogado.detalleproceso', $anexo->caso_id) }}">PRO-{{ str_pad($anexo->caso_id, 3, '0', STR_PAD_LEFT) }}</a>
                    <span class="hub-sub">{{ $anexo->caso->nombre ?? '—' }}</span>
                </td>
                <td>
                    <span class="name">{{ $anexo->nombreVisible() }}</span>
                    <span class="hub-sub">
                        {{ strtoupper($anexo->extensionVigente()) }} · {{ $anexo->tamanoVigente() }}
                        @if($anexo->nombre_original && $anexo->nombre_original !== $anexo->nombreVisible())
                            · {{ $anexo->nombre_original }}
                        @endif
                    </span>
                </td>
                <td>{{ $anexo->etiquetaLugar() }}</td>
                <td>
                    <span class="st"><i class="fas fa-circle {{ $estadoDot }}"></i> {{ $anexo->etiquetaEstado() }}</span>
                    @if($anexo->firmado_at)
                        <span class="hub-sub">{{ $anexo->firmado_at->format('d/m/Y') }}</span>
                    @endif
                </td>
                <td>
                    {{ optional($anexo->created_at)->format('d/m/Y') }}
                    <span class="hub-sub">{{ $anexo->user->name ?? '—' }}</span>
                </td>
                <td>
                    @php
                        $esDuenioCaso = (int) optional($anexo->caso)->user_id === (int) auth()->id();
                        $puedeEditar = auth()->user()->puede('editar_anexos');
                        $puedeDescargar = auth()->user()->puede('descargar_anexos');
                        $puedeBorrarEste = auth()->user()->esCoordinadora()
                            || (auth()->user()->puede('eliminar_anexos') && ($anexo->user_id === auth()->id() || $esDuenioCaso));
                    @endphp
                    <div class="acts">
                        @if($puedeEditar)
                            <button type="button" class="action-btn anexo-editar"
                                data-action="{{ route('abogado.anexos.update', $anexo->id) }}"
                                data-tipo="{{ $anexo->tipoDocumento() }}"
                                data-titulo="{{ $anexo->nombreVisible() }}">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                        @else
                            <button type="button" class="action-btn" onclick="SIPD_abrirPermiso('editar_anexos')">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                        @endif
                        @if($anexo->requiereFirma() && auth()->user()->puede('subir_anexos'))
                            <form action="{{ route('abogado.anexos.firmar', $anexo->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <input class="sr-file" id="firmado-{{ $anexo->id }}" type="file" name="archivo" accept=".pdf,.jpg,.jpeg,.png" required onchange="this.form.submit()">
                                <label class="action-btn veredicto" for="firmado-{{ $anexo->id }}">
                                    <i class="fas fa-file-signature"></i> Cargar firmado
                                </label>
                            </form>
                        @endif
                        @if($puedeBorrarEste)
                            <form action="{{ route('abogado.anexos.destroy', $anexo->id) }}" method="POST"
                                  data-confirm="Se eliminará el anexo del expediente."
                                  data-confirm-title="Eliminar anexo"
                                  data-confirm-ok="Eliminar"
                                  data-confirm-danger="1"
                                  data-confirm-icon="warning">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="action-btn danger">
                                    <i class="far fa-trash-alt"></i> Eliminar
                                </button>
                            </form>
                        @else
                            <button type="button" class="action-btn danger" onclick="SIPD_abrirPermiso('eliminar_anexos')">
                                <i class="far fa-trash-alt"></i> Eliminar
                            </button>
                        @endif
                        @if($puedeDescargar)
                        <a class="action-btn download" href="{{ route('abogado.anexos.download', $anexo->id) }}" title="Descargar archivo">
                            <i class="fas fa-download"></i>
                            <span>Descargar</span>
                        </a>
                        @else
                        <button type="button" class="action-btn download" onclick="SIPD_abrirPermiso('descargar_anexos')">
                            <i class="fas fa-download"></i>
                            <span>Descargar</span>
                        </button>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td class="empty" colspan="6">Aún no hay anexos. Elige el proceso, el tipo y pulsa Cargar.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
    @include('partials.paginacion', ['paginador' => $anexos])
</div>

@if(auth()->user()->puede('editar_anexos'))
<div class="sipd-dialog-bg" id="modalEditarAnexo">
    <div class="sipd-dialog">
        <h3>Editar anexo</h3>
        <p class="sipd-dialog-lead" id="edit-anexo-lead">Cambia el documento o reemplaza el archivo.</p>
        <form id="formEditarAnexo" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <label for="edit-anexo-tipo">Documento</label>
            <select id="edit-anexo-tipo" name="tipo" required>
                @foreach(\App\Models\CasoAnexo::tipos() as $valor => $etiqueta)
                    <option value="{{ $valor }}">{{ $etiqueta }}</option>
                @endforeach
            </select>
            <label for="edit-anexo-file">Reemplazar archivo (opcional)</label>
            <input id="edit-anexo-file" type="file" name="archivo" accept=".pdf,.doc,.docx">
            <p class="hint" id="edit-anexo-hint"></p>
            <div class="sipd-dialog-actions">
                <button type="button" class="btn-ghost" id="edit-anexo-cerrar">Cancelar</button>
                <button type="submit" class="btn-ok">Guardar</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@section('scripts')
<script>
    (function () {
        var hints = {
            disciplinario: 'Word o PDF de 1.1 GA-FT-045 Apertura disciplinaria. Queda en 1. Apertura.',
            comprobacion: 'Word o PDF de 1.1 GA-FT-045 Apertura de comprobación. Queda en 1. Apertura.',
            acta: 'Word o PDF del acta de cargos y descargos. Queda en 2. Acta.',
            sancion: 'Word o PDF de la sanción. Queda en 3. Sanción / llamado / terminación.',
            llamado: 'Word o PDF del llamado de atención. Queda en 3. Sanción / llamado / terminación.',
            terminacion: 'PDF, JPG o PNG de la terminación ya firmada por gerencia. Queda en 3. Sanción / llamado / terminación.',
            archivo: 'Word o PDF de la decisión de archivo. Queda en 4. Decisión de archivo.'
        };
        var acceptOf = function (tipo) {
            return tipo === 'terminacion' ? '.pdf,.jpg,.jpeg,.png' : '.pdf,.doc,.docx';
        };

        var form = document.getElementById('anexo-upload-form');
        if (form) {
            var hint = document.getElementById('anexo-hint');
            var tipo = document.getElementById('anexo-tipo');
            var caso = document.getElementById('anexo-caso');
            var file = document.getElementById('anexo-file');
            var cargar = document.getElementById('anexo-cargar');
            function syncHint() {
                hint.textContent = hints[tipo.value] || 'Selecciona el formato oficial para que el archivo quede en ese espacio.';
                file.accept = acceptOf(tipo.value);
            }
            tipo.addEventListener('change', syncHint);
            syncHint();
            cargar.addEventListener('click', function () {
                if (!caso.value || !tipo.value) {
                    if (window.SIPD && SIPD.toast) {
                        SIPD.toast({ icon: 'warning', text: 'Selecciona el proceso y el documento.' });
                    }
                    return;
                }
                file.click();
            });
            file.addEventListener('change', function () {
                if (file.files && file.files.length) {
                    form.submit();
                }
            });
        }

        var modal = document.getElementById('modalEditarAnexo');
        var formEdit = document.getElementById('formEditarAnexo');
        if (!modal || !formEdit) return;
        var tipoEdit = document.getElementById('edit-anexo-tipo');
        var fileEdit = document.getElementById('edit-anexo-file');
        var hintEdit = document.getElementById('edit-anexo-hint');
        var leadEdit = document.getElementById('edit-anexo-lead');

        function syncEditHint() {
            hintEdit.textContent = hints[tipoEdit.value] || 'Si no eliges archivo, se conserva el actual.';
            fileEdit.accept = acceptOf(tipoEdit.value);
        }

        function cerrarEdit() {
            modal.style.display = 'none';
            fileEdit.value = '';
        }

        function abrirEdit(btn) {
            formEdit.action = btn.getAttribute('data-action');
            tipoEdit.value = btn.getAttribute('data-tipo') || '';
            leadEdit.textContent = btn.getAttribute('data-titulo') || 'Cambia el documento o reemplaza el archivo.';
            fileEdit.value = '';
            syncEditHint();
            modal.style.display = 'flex';
        }

        tipoEdit.addEventListener('change', syncEditHint);
        document.querySelectorAll('.anexo-editar').forEach(function (btn) {
            btn.addEventListener('click', function () { abrirEdit(this); });
        });
        document.getElementById('edit-anexo-cerrar').addEventListener('click', cerrarEdit);
        modal.addEventListener('click', function (e) {
            if (e.target === modal) cerrarEdit();
        });
    })();
</script>
@endsection
