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
    .hub-table th { color:#64748b; font-size:10px; letter-spacing:.02em; text-transform:uppercase; background:#f8fafc; border-bottom:2px solid #e2e8f0; line-height:1.3; }
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
    .hub-doc { display:flex; flex-direction:column; align-items:center; gap:4px; }
    .hub-variante { font-size:10px; color:#64748b; font-style:italic; text-align:center; line-height:1.2; max-width:100%; }
    .hub-variante strong { color:#334155; font-style:normal; }
    .hub-worker-name { display:block; font-weight:700; color:#0f172a; font-size:14px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .hub-worker-cedula { display:block; color:#64748b; font-size:11px; margin-top:1px; }
    .hub-worker-area { display:block; color:#475569; font-size:11px; margin-top:2px; font-weight:500; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .hub-dl { display:flex; gap:6px; flex-wrap:wrap; }
    .hub-dl a {
        display:inline-flex; align-items:center; gap:5px;
        padding:7px 12px; border-radius:8px; font-size:12px; font-weight:700;
        text-decoration:none; color:#fff;
    }
    .hub-dl-word { background:#1d4ed8; }
    .hub-dl-pdf { background:#e24b3a; }
    .hub-dl-permission {
        display:inline-flex; align-items:center; gap:5px;
        padding:7px 10px; border:0; border-radius:8px; font-size:12px; font-weight:700;
        color:#5b21b6; background:#ede9fe; cursor:pointer;
    }
    .hub-dl-permission:hover { background:#ddd6fe; }
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
        flex-direction: row;
        flex-wrap: wrap;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }
    .hub-actions form { margin: 0; display: block; }
    .action-btn {
        width: 32px;
        height: 32px;
        border: 0;
        background: #f1f5f9;
        color: #64748b;
        border-radius: 8px;
        display: grid;
        place-items: center;
        text-decoration: none;
        cursor: pointer;
        font-size: 13px;
        padding: 0;
        flex-shrink: 0;
        box-sizing: border-box;
        transition: all 0.15s ease;
    }
    .action-btn:focus-visible { outline: 2px solid currentColor; outline-offset: 2px; }
    .action-btn.btn-view:hover { background: #dcfce7; color: #15803d; }
    .action-btn.btn-edit:hover { background: #dbeafe; color: #1d4ed8; }
    .action-btn.btn-delete:hover { background: #fee2e2; color: #b91c1c; }
    .action-btn.btn-down:hover { background: #ddd6fe; color: #5b21b6; }
    .action-btn.veredicto:hover { background: #ccfbf1; color: #0f766e; }
</style>
@endsection

@section('page-header')
    <div class="proc-head">
        <div>
            <h1>Generar documentos</h1>
            <p>Nuevo proceso solo deja un borrador. Aquí se genera y se descarga cada formato. Enviar aparece cuando los 4 están generados y descargados.</p>
        </div>
    </div>
@endsection

@section('content')
<form class="hub-toolbar" method="GET" action="{{ route('documentos.hub') }}">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por trabajador, cédula, placa o N° proceso...">
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
                <th>Trabajador / cédula</th>
                <th class="center">1. Apertura</th>
                <th class="center">2. Acta</th>
                <th class="center">3. Sanción / llamado / terminación</th>
                <th class="center">4. Decisión de archivo</th>
                <th class="center">Acciones</th>
            </tr>
        </thead>
        <tbody>
        @forelse($casos as $caso)
            @php
                $estadoVisible = $caso->estadoVisible();
                $puedeEnviar = $caso->puedeEnviarAProceso() && !auth()->user()->esCoordinadora();
                $estadoDot = [
                    'Pendiente' => 'dot-pend',
                    'En proceso' => 'dot-proc',
                    'Sancionado' => 'dot-sanc',
                    'Archivado' => 'dot-arch',
                ][$estadoVisible] ?? 'dot-arch';
            @endphp
            <tr>
                <td>
                    <a class="proc-lnk" href="{{ route('abogado.detalleproceso', ['id' => $caso->id, 'from' => 'documentos']) }}">PRO-{{ str_pad($caso->id, 3, '0', STR_PAD_LEFT) }}</a>
                    <span class="hub-sub">
                        <span class="st hub-process-state"><i class="fas fa-circle {{ $estadoDot }}"></i> {{ $estadoVisible }}</span>
                        @if(($caso->anexos_count ?? 0) > 0)
                            · {{ $caso->anexos_count }} anexo{{ $caso->anexos_count === 1 ? '' : 's' }}
                        @endif
                    </span>
                </td>
                <td>
                    <span class="hub-worker-name" title="{{ $caso->nombre }}">{{ $caso->nombre }}</span>
                    <span class="hub-worker-cedula"><i class="fas fa-id-card" style="font-size:9px;opacity:.6"></i> {{ $caso->cedula ? number_format((float)preg_replace('/\D/', '', $caso->cedula), 0, '', '.') : 'Sin cédula' }}</span>
                    <span class="hub-worker-area" title="{{ \App\Support\Modalidades::etiquetaCaso($caso->modalidad, $caso->cargo) }}"><i class="fas fa-briefcase" style="font-size:9px;opacity:.5"></i> {{ \App\Support\Modalidades::etiquetaCaso($caso->modalidad, $caso->cargo) ?: '—' }}</span>
                </td>
                @foreach(\App\Models\CasoDocumentoEstado::SLOTS as $slot => $variantes)
                    @php
                        $tipoSlot = $caso->varianteDelSlot($slot);
                        $estSlot  = $caso->estadoDocumento($tipoSlot);
                        $tieneOpciones = count($variantes) > 1;
                        // Etiqueta corta de la variante elegida
                        $varianteLabels = [
                            'disciplinario' => 'Disciplinario',
                            'comprobacion'  => 'Comprobación',
                            'acta'          => 'Acta c&d',
                            'sancion'       => 'Sanción',
                            'llamado'       => 'Llamado de atención',
                            'terminacion'   => 'Terminación',
                            'archivo'       => 'Archivo',
                        ];
                        $varianteLabel = $varianteLabels[$tipoSlot] ?? ucfirst($tipoSlot);
                    @endphp
                    <td class="center" data-document-slot="{{ $slot }}">
                        <div class="hub-doc">
                            <a href="{{ route('documentos.edit', [$caso->id, $tipoSlot]) }}" title="{{ \App\Models\CasoDocumentoEstado::etiqueta($tipoSlot) }}" style="text-decoration:none;">
                                <span class="doc-badge {{ $estSlot->claseHub() }}">{{ $estSlot->etiquetaHub() }}</span>
                            </a>
                            @if($tieneOpciones && $estSlot->estado !== 'no_iniciado')
                                <span class="hub-variante"><strong>{{ $varianteLabel }}</strong></span>
                            @endif
                        </div>
                    </td>
                @endforeach
                <td>
                    <div class="hub-actions">
                        @if($caso->estado === 'Pendiente' && !auth()->user()->esCoordinadora())
                            <form action="{{ route('abogado.solicitar_veredicto', $caso->id) }}" method="POST"
                                  class="hub-send-form {{ $puedeEnviar ? '' : 'd-none' }}"
                                  data-confirm="El caso pasará a En Proceso y quedará pendiente de veredicto."
                                  data-confirm-title="Enviar caso"
                                  data-confirm-ok="Enviar"
                                  data-confirm-icon="question">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="action-btn veredicto" title="Enviar a En Proceso">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('abogado.detalleproceso', ['id' => $caso->id, 'from' => 'documentos']) }}" class="action-btn btn-view" title="Ver proceso">
                            <i class="fas fa-eye"></i>
                        </a>
                        @if(!$caso->soloLoManejaCoordinadora())
                            @if(auth()->user()->puede('editar_casos'))
                                <a href="{{ route('abogado.detalleproceso', ['id' => $caso->id, 'from' => 'documentos', 'editar' => 1]) }}" class="action-btn btn-edit" title="Editar datos del proceso">
                                    <i class="fas fa-edit"></i>
                                </a>
                            @else
                                <button type="button" class="action-btn btn-edit" title="Solicitar permiso para editar" aria-label="Solicitar permiso para editar"
                                        onclick="window.SIPD_abrirPermiso && SIPD_abrirPermiso('editar_casos')">
                                    <i class="fas fa-key"></i>
                                </button>
                            @endif
                            @if(auth()->user()->puede('eliminar_casos'))
                                <form action="{{ route('abogado.eliminarproceso', $caso->id) }}" method="POST"
                                      data-confirm="Esta acción eliminará el proceso disciplinario de forma permanente y no se puede deshacer."
                                      data-confirm-title="Eliminar proceso"
                                      data-confirm-ok="Eliminar"
                                      data-confirm-danger="1"
                                      data-confirm-icon="warning">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="action-btn btn-delete" title="Eliminar proceso" aria-label="Eliminar proceso">
                                        <i class="far fa-trash-alt"></i>
                                    </button>
                                </form>
                            @else
                                <button type="button" class="action-btn btn-delete" title="Solicitar permiso para eliminar" aria-label="Solicitar permiso para eliminar"
                                        onclick="window.SIPD_abrirPermiso && SIPD_abrirPermiso('eliminar_casos')">
                                    <i class="fas fa-key"></i>
                                </button>
                            @endif
                        @endif
                        @php
                            $puedeGral = auth()->user()->puede('descargar_documentos');
                            $descargas = [];
                            $tieneDocumentoGenerado = false;
                            foreach (\App\Models\CasoDocumentoEstado::SLOTS as $slot => $variantes) {
                                $tipoSlot = $caso->varianteDelSlot($slot);
                                $estSlot = $caso->estadoDocumento($tipoSlot);
                                
                                $estaGenerado = $estSlot->estaGenerado();
                                $fueDescargado = $estSlot->estaDescargado();
                                $puedeDescargar = $puedeGral || !$fueDescargado;
                                if ($estaGenerado) {
                                    $tieneDocumentoGenerado = true;
                                }

                                if (!$estaGenerado) {
                                    $actionState = 'faltante';
                                } elseif ($puedeDescargar) {
                                    $actionState = 'listo';
                                } else {
                                    $actionState = 'bloqueado';
                                }

                                $descargas[] = [
                                    'label' => \App\Models\CasoDocumentoEstado::SLOT_LABELS[$slot],
                                    'slot' => $slot,
                                    'estado' => $estSlot->etiquetaHub(),
                                    'actionState' => $actionState,
                                    'requestText' => 'Solicito autorización para volver a descargar ' . \App\Models\CasoDocumentoEstado::SLOT_LABELS[$slot]
                                        . ' del proceso PRO-' . str_pad($caso->id, 3, '0', STR_PAD_LEFT) . '.',
                                    'requiresRepeatPermission' => !$puedeGral,
                                    'generado' => $estaGenerado,
                                    'descargado' => $fueDescargado,
                                    'badgeClass' => $estSlot->claseHub(),
                                    'word' => $actionState === 'listo' ? route('documentos.download', [$caso->id, $tipoSlot]) : null,
                                    'pdf' => $actionState === 'listo' ? route('documentos.download', [$caso->id, $tipoSlot]) . '?format=pdf' : null,
                                ];
                            }
                        @endphp
                        @if($tieneDocumentoGenerado)
                            <button type="button" class="action-btn btn-down hub-open-dl"
                                data-proc="PRO-{{ str_pad($caso->id, 3, '0', STR_PAD_LEFT) }}"
                                data-nombre="{{ $caso->nombre }}"
                                data-items='@json($descargas)'
                                title="Descargar documentos">
                                <i class="fas fa-download"></i>
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

<div class="sipd-dialog-bg" id="modalDescargas">
    <div class="sipd-dialog">
        <h3>Descargar · <span id="dlProc"></span></h3>
        <p class="sipd-dialog-lead" id="dlNombre"></p>
        <p class="sipd-dialog-lead" id="dlMessage" role="status" aria-live="polite" style="display:none;"></p>
        <div id="dlList"></div>
        <div class="sipd-dialog-actions">
            <button type="button" class="btn-ghost" id="dlCerrar">Cerrar</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
    var modal = document.getElementById('modalDescargas');
    var list = document.getElementById('dlList');
    var message = document.getElementById('dlMessage');
    if (!modal || !list) return;
    var activeItems = [];
    var activeButton = null;

    function cerrar() {
        modal.style.display = 'none';
        list.innerHTML = '';
        if (message) {
            message.textContent = '';
            message.style.display = 'none';
        }
    }

    function esc(value) {
        return String(value || '').replace(/[&<>"']/g, function (c) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
        });
    }

    function renderItems() {
        list.innerHTML = activeItems.map(function (item, index) {
            var actions = '';
            if (item.actionState === 'listo') {
                actions = '<a class="hub-dl-word hub-official-download" data-item-index="' + index + '" href="' + esc(item.word) + '" title="Descargar Word"><i class="fas fa-file-word"></i> Word</a><a class="hub-dl-pdf hub-official-download" data-item-index="' + index + '" href="' + esc(item.pdf) + '" title="Descargar PDF"><i class="fas fa-file-pdf"></i> PDF</a>';
            } else if (item.actionState === 'bloqueado') {
                actions = '<button type="button" class="hub-dl-permission hub-request-download-permission" data-request-text="' + esc(item.requestText || '') + '" title="Solicitar permiso para volver a descargar este documento"><i class="fas fa-key"></i> Solicitar permiso</button>';
            } else {
                actions = '<span style="color:var(--cth-gray-text);font-size:12px;" title="Primero debes diligenciar y generar el documento."><i class="fas fa-file-signature"></i> Falta generar</span>';
            }

            return '<div class="dl-item">' +
                '<div><b>' + esc(item.label) + '</b><small>' + esc(item.estado) + '</small></div>' +
                '<div class="hub-dl">' + actions + '</div>' +
            '</div>';
        }).join('');
    }

    function abrir(btn) {
        activeButton = btn;
        activeItems = [];
        try { activeItems = JSON.parse(btn.getAttribute('data-items') || '[]'); } catch (e) { activeItems = []; }
        document.getElementById('dlProc').textContent = btn.getAttribute('data-proc') || '';
        document.getElementById('dlNombre').textContent = btn.getAttribute('data-nombre') || '';
        if (message) {
            message.textContent = '';
            message.style.display = 'none';
        }
        renderItems();
        modal.style.display = 'flex';
    }

    function downloadFilename(response) {
        var disposition = response.headers.get('Content-Disposition') || '';
        var encoded = disposition.match(/filename\*=UTF-8''([^;]+)/i);
        var plain = disposition.match(/filename="?([^";]+)"?/i);
        var value = encoded ? encoded[1] : (plain ? plain[1] : 'documento');
        try { return decodeURIComponent(value); } catch (e) { return value; }
    }

    function saveBlob(blob, filename) {
        var url = URL.createObjectURL(blob);
        var link = document.createElement('a');
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
    }

    document.querySelectorAll('.hub-open-dl').forEach(function (btn) {
        btn.addEventListener('click', function () { abrir(this); });
    });
    list.addEventListener('click', function (e) {
        var download = e.target.closest('.hub-official-download');
        if (download) {
            e.preventDefault();
            if (download.getAttribute('aria-disabled') === 'true') return;
            download.setAttribute('aria-disabled', 'true');
            var itemIndex = Number(download.getAttribute('data-item-index'));
            var requestedItems = activeItems;
            var requestedItem = requestedItems[itemIndex];
            var requestedButton = activeButton;
            fetch(download.href, { credentials: 'same-origin' })
                .then(function (response) {
                    if (!response.ok || response.headers.get('X-SIPD-Download-Registered') !== '1') {
                        throw new Error('No fue posible completar la descarga. Verifica tu permiso e inténtalo de nuevo.');
                    }
                    return response.blob().then(function (blob) {
                        saveBlob(blob, downloadFilename(response));
                        return response;
                    });
                })
                .then(function () {
                    if (!requestedItem) return;
                    requestedItem.estado = 'Generada y descargada';
                    requestedItem.generado = true;
                    requestedItem.descargado = true;
                    requestedItem.badgeClass = 'db-descargado';
                    if (requestedItem.requiresRepeatPermission) requestedItem.actionState = 'bloqueado';
                    if (requestedButton) requestedButton.setAttribute('data-items', JSON.stringify(requestedItems));
                    if (requestedButton) {
                        var row = requestedButton.closest('tr');
                        var cell = row && row.querySelector('td[data-document-slot="' + requestedItem.slot + '"]');
                        var badge = cell && cell.querySelector('.doc-badge');
                        if (badge) {
                            badge.className = 'doc-badge ' + requestedItem.badgeClass;
                            badge.textContent = requestedItem.estado;
                        }
                        var todosDescargados = requestedItems.length === 4 && requestedItems.every(function (documento) {
                            return documento.generado && documento.descargado;
                        });
                        var sendForm = row && row.querySelector('.hub-send-form');
                        if (sendForm && todosDescargados) sendForm.classList.remove('d-none');
                    }
                    if (activeButton === requestedButton) renderItems();
                })
                .catch(function (error) {
                    if (message) {
                        message.textContent = error.message || 'No fue posible completar la descarga.';
                        message.style.display = 'block';
                    }
                    download.removeAttribute('aria-disabled');
                });
            return;
        }

        var button = e.target.closest('.hub-request-download-permission');
        if (!button || !window.SIPD_abrirPermiso) return;
        window.SIPD_abrirPermiso('descargar_documentos', button.getAttribute('data-request-text') || '');
    });
    document.getElementById('dlCerrar').addEventListener('click', cerrar);
    modal.addEventListener('click', function (e) {
        if (e.target === modal) cerrar();
    });
})();
</script>
@endsection
