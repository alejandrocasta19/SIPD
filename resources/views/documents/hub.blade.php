@extends('layouts.master')

@php $pageTitle = 'Generar documentos'; @endphp

@section('styles')
<style>
    .hub-toolbar { display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:16px; }
    .hub-toolbar input { height:40px; flex:1 1 220px; min-width:0; max-width:420px; border: 1px solid var(--cth-border); border-radius:8px; padding:0 12px; }
    .hub-btn { border:0; border-radius:8px; padding:10px 16px; font-weight:700; cursor:pointer; display:inline-flex; gap:7px; align-items:center; background:var(--cth-green); color:#fff; }

    .hub-table { background:#fff; border: 1px solid var(--cth-border); border-radius:12px; overflow-x:auto; }
    .hub-table table { width:100%; table-layout:fixed; border-collapse:collapse; font-size:13px; }
    .hub-table col.col-proc { width:14%; }
    .hub-table col.col-cond { width:16%; }
    .hub-table col.col-doc { width:13%; }
    .hub-table col.col-act { width:16%; }
    .hub-table th, .hub-table td { padding:11px 8px; border:1px solid var(--cth-border); text-align:left; vertical-align:middle; }
    .hub-table td { overflow:hidden; }
    .hub-table th { color:#64748b; font-size:10px; letter-spacing:.02em; text-transform:uppercase; background:#f8fafc; line-height:1.3; }
    .hub-table th.center, .hub-table td.center { text-align:center; }
    .hub-table a.proc-lnk { color:var(--cth-green); font-weight:700; text-decoration:none; font-size:14px; }
    .hub-sub { display:block; color:#64748b; font-size:12px; margin-top:4px; overflow:hidden; text-overflow:ellipsis; }
    .hub-table .st { max-width:100%; padding:3px 8px; font-size:11px; }
    .empty-hub { text-align:center; padding:42px; color:#94a3b8; }
    .hub-pager { display:flex; justify-content:space-between; padding:14px; color:#64748b; }

    .doc-badge {
        display: inline-flex; align-items: center; justify-content: center;
        padding: 4px 8px; border-radius: 999px; font-size: 10px; font-weight: 700;
        min-width: 84px; max-width: 100%; text-transform: uppercase; letter-spacing: 0.04em;
        cursor: pointer; transition: transform 0.15s ease, box-shadow 0.15s ease;
        white-space: normal; line-height: 1.25; text-align: center;
    }
    .doc-badge:hover { transform: translateY(-1px); box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .db-pendiente { background: #fef2f2; color: #b91c1c; border: 1px solid #fca5a5; }
    .db-en_diligenciamiento { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
    .db-generado { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
    .db-descargado { background: #f0fdf4; color: var(--cth-green-text); border: 1px solid #bbf7d0; }
    .hub-doc { display:flex; flex-direction:column; align-items:center; gap:6px; }
    .hub-dl { display:flex; gap:6px; flex-wrap:wrap; }
    .hub-dl a {
        display:inline-flex; align-items:center; gap:5px;
        padding:7px 12px; border-radius:8px; font-size:12px; font-weight:700;
        text-decoration:none; color:#fff;
    }
    .hub-dl-word { background:#1d4ed8; }
    .hub-dl-pdf { background:#e24b3a; }
    .dl-item {
        display:flex; align-items:center; justify-content:space-between; gap:12px;
        padding:12px 0; border-bottom:1px solid var(--cth-border);
    }
    .dl-item:last-child { border-bottom:0; }
    .dl-item b { display:block; color:#0f172a; font-size:14px; }
    .dl-item small { display:block; margin-top:3px; color:#64748b; font-size:11px; font-weight:700; letter-spacing:.04em; text-transform:uppercase; }
    #modalDescargas .sipd-dialog { max-width: 520px; }

    .hub-actions {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 6px;
    }
    .hub-actions form { margin: 0; display: block; }
    .action-btn {
        background: #f1f5f9; color: #334155; border: 1px solid var(--cth-border);
        padding: 7px 8px; border-radius: 8px; font-size: 11px; font-weight: 700;
        text-decoration: none; display: inline-flex; align-items: center; justify-content: center;
        gap: 5px; white-space: nowrap; line-height: 1.2; cursor: pointer; width: 100%;
        box-sizing: border-box;
    }
    .action-btn:hover { background: #e2e8f0; color: #0f172a; }
    .action-btn.veredicto {
        background: #3b82f6; color: #fff; border-color: #2563eb;
        white-space: normal;
    }
    .action-btn.veredicto:hover { background: #2563eb; color: #fff; }
</style>
@endsection

@section('page-header')
    <div class="proc-head">
        <div>
            <h1>Generar documentos</h1>
            <p>Nuevo proceso solo deja un borrador. Aquí se genera y se descarga cada formato. Enviar aparece cuando los 4 están generados.</p>
        </div>
    </div>
@endsection

@section('content')
<form class="hub-toolbar" method="GET" action="{{ route('documentos.hub') }}">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por conductor, cédula, placa o N° proceso...">
    <button class="hub-btn" type="submit"><i class="fas fa-search"></i> Buscar</button>
</form>

<div class="hub-table">
    <table>
        <colgroup>
            <col class="col-proc">
            <col class="col-cond">
            <col class="col-doc">
            <col class="col-doc">
            <col class="col-doc">
            <col class="col-doc">
            <col class="col-act">
        </colgroup>
        <thead>
            <tr>
                <th>Proceso</th>
                <th>Conductor / cédula</th>
                <th class="center">1. Apertura</th>
                <th class="center">2. Acta</th>
                <th class="center">3. Sanción / llamado / terminación</th>
                <th class="center">4. Decisión de archivo</th>
                <th class="center">Gestionar</th>
            </tr>
        </thead>
        <tbody>
        @forelse($casos as $caso)
            @php
                $estadoDot = [
                    'Pendiente' => 'dot-pend',
                    'En Proceso' => 'dot-proc',
                    'Sancionado' => 'dot-sanc',
                    'Archivado' => 'dot-arch',
                ][$caso->estado] ?? 'dot-arch';
            @endphp
            <tr>
                <td>
                    <a class="proc-lnk" href="{{ route('abogado.detalleproceso', ['id' => $caso->id, 'from' => 'documentos']) }}">PRO-{{ str_pad($caso->id, 3, '0', STR_PAD_LEFT) }}</a>
                    <span class="hub-sub">
                        <span class="st"><i class="fas fa-circle {{ $estadoDot }}"></i> {{ $caso->estado }}</span>
                        @if(($caso->anexos_count ?? 0) > 0)
                            · {{ $caso->anexos_count }} anexo{{ $caso->anexos_count === 1 ? '' : 's' }}
                        @endif
                    </span>
                </td>
                <td>
                    <strong>{{ $caso->nombre }}</strong>
                    <span class="hub-sub">{{ $caso->cedula ?: 'Sin cédula' }}</span>
                </td>
                @foreach(\App\Models\CasoDocumentoEstado::SLOTS as $slot => $variantes)
                    @php
                        $tipoSlot = $caso->varianteDelSlot($slot);
                        $estSlot = $caso->estadoDocumento($tipoSlot);
                    @endphp
                    <td class="center">
                        <div class="hub-doc">
                            <a href="{{ route('documentos.edit', [$caso->id, $tipoSlot]) }}" title="{{ \App\Models\CasoDocumentoEstado::etiqueta($tipoSlot) }}" style="text-decoration:none;">
                                <span class="doc-badge {{ $estSlot->claseHub() }}">{{ $estSlot->etiquetaHub() }}</span>
                            </a>
                        </div>
                    </td>
                @endforeach
                <td>
                    <div class="hub-actions">
                        @if($caso->listoParaVeredicto() && !auth()->user()->esCoordinadora())
                            <form action="{{ route('abogado.solicitar_veredicto', $caso->id) }}" method="POST"
                                  data-confirm="El caso pasará a En Proceso y quedará pendiente de veredicto."
                                  data-confirm-title="Enviar caso"
                                  data-confirm-ok="Enviar"
                                  data-confirm-icon="question">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="action-btn veredicto" title="Enviar a En Proceso">
                                    <i class="fas fa-paper-plane"></i> Enviar
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('abogado.detalleproceso', ['id' => $caso->id, 'from' => 'documentos']) }}" class="action-btn" title="Editar datos del proceso">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                        @if(auth()->user()->puede('descargar_documentos'))
                            @php
                                $descargas = [];
                                foreach (\App\Models\CasoDocumentoEstado::SLOTS as $slot => $variantes) {
                                    $tipoSlot = $caso->varianteDelSlot($slot);
                                    $estSlot = $caso->estadoDocumento($tipoSlot);
                                    $descargas[] = [
                                        'label' => \App\Models\CasoDocumentoEstado::SLOT_LABELS[$slot],
                                        'estado' => $estSlot->etiquetaHub(),
                                        'word' => route('documentos.download', [$caso->id, $tipoSlot]),
                                        'pdf' => route('documentos.download', [$caso->id, $tipoSlot]) . '?format=pdf',
                                    ];
                                }
                            @endphp
                            <button type="button" class="action-btn hub-open-dl"
                                data-proc="PRO-{{ str_pad($caso->id, 3, '0', STR_PAD_LEFT) }}"
                                data-nombre="{{ $caso->nombre }}"
                                data-items='@json($descargas)'>
                                <i class="fas fa-download"></i> Descargar
                            </button>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr><td class="empty-hub" colspan="7">No tienes casos registrados o no hay coincidencias de búsqueda.</td></tr>
        @endforelse
        </tbody>
    </table>
    @include('partials.paginacion', ['paginador' => $casos])
</div>

@if(auth()->user()->puede('descargar_documentos'))
<div class="sipd-dialog-bg" id="modalDescargas">
    <div class="sipd-dialog">
        <h3>Descargar · <span id="dlProc"></span></h3>
        <p class="sipd-dialog-lead" id="dlNombre"></p>
        <div id="dlList"></div>
        <div class="sipd-dialog-actions">
            <button type="button" class="btn-ghost" id="dlCerrar">Cerrar</button>
        </div>
    </div>
</div>
@endif
@endsection

@section('scripts')
@if(auth()->user()->puede('descargar_documentos'))
<script>
(function () {
    var modal = document.getElementById('modalDescargas');
    var list = document.getElementById('dlList');
    if (!modal || !list) return;

    function cerrar() {
        modal.style.display = 'none';
        list.innerHTML = '';
    }

    function esc(value) {
        return String(value || '').replace(/[&<>"']/g, function (c) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
        });
    }

    function abrir(btn) {
        var items = [];
        try { items = JSON.parse(btn.getAttribute('data-items') || '[]'); } catch (e) { items = []; }
        document.getElementById('dlProc').textContent = btn.getAttribute('data-proc') || '';
        document.getElementById('dlNombre').textContent = btn.getAttribute('data-nombre') || '';
        list.innerHTML = items.map(function (item) {
            return '<div class="dl-item">' +
                '<div><b>' + esc(item.label) + '</b><small>' + esc(item.estado) + '</small></div>' +
                '<div class="hub-dl">' +
                    '<a class="hub-dl-word" href="' + esc(item.word) + '" title="Descargar Word"><i class="fas fa-file-word"></i> Word</a>' +
                    '<a class="hub-dl-pdf" href="' + esc(item.pdf) + '" title="Descargar PDF"><i class="fas fa-file-pdf"></i> PDF</a>' +
                '</div>' +
            '</div>';
        }).join('');
        modal.style.display = 'flex';
    }

    document.querySelectorAll('.hub-open-dl').forEach(function (btn) {
        btn.addEventListener('click', function () { abrir(this); });
    });
    document.getElementById('dlCerrar').addEventListener('click', cerrar);
    modal.addEventListener('click', function (e) {
        if (e.target === modal) cerrar();
    });
})();
</script>
@endif
@endsection
