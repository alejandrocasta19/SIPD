@extends('layouts.master')

@php
    $pageTitle = 'Anexos escaneados';
@endphp

@section('page-header')
    <div class="proc-head">
        <div>
            <h1>Anexos escaneados</h1>
            <p>Carga Word o PDF al expediente. Solo la terminación por justas causas pasa por firma del gerente.</p>
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
    .acts { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; justify-content: flex-end; }
    .acts form { margin: 0; }
    .acts .action-btn {
        background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1;
        padding: 6px 10px; border-radius: 8px; font-size: 12px; font-weight: 700;
        text-decoration: none; display: inline-flex; align-items: center; gap: 5px; cursor: pointer;
    }
    .acts .action-btn.danger { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }
    .acts .action-btn.veredicto { background: #2563eb; color: #fff; border-color: #1d4ed8; }

    .pager { display: flex; justify-content: space-between; align-items: center; padding: 14px 16px; color: #94a3b8; font-size: 13px; border-top: 1px solid #e2e8f0; }

    @media (max-width: 900px) {
        .anexo-grid { grid-template-columns: 1fr; }
        .table-card { overflow-x: auto; }
    }
</style>
@endsection

@section('content')
<form class="anexo-upload" id="anexo-upload-form" action="{{ route('abogado.anexos.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <h3>Cargar documento</h3>
    <p>Word o PDF. La firma del gerente aplica únicamente a la terminación por justas causas.</p>

    <div class="anexo-grid">
        <div>
            <label class="field" for="anexo-caso">Proceso</label>
            <select id="anexo-caso" name="caso_id" required>
                <option value="">Selecciona un proceso…</option>
                @foreach($casos as $caso)
                    <option value="{{ $caso->id }}" @if((string) old('caso_id') === (string) $caso->id) selected @endif>
                        PRO-{{ str_pad($caso->id, 3, '0', STR_PAD_LEFT) }} · {{ $caso->nombre }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="field" for="anexo-tipo">Tipo</label>
            <select id="anexo-tipo" name="tipo" required>
                @foreach(\App\Models\CasoAnexo::tipos() as $valor => $etiqueta)
                    <option value="{{ $valor }}" @if(old('tipo', 'archivo_previo') === $valor) selected @endif>{{ $etiqueta }}</option>
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
    <a class="chip {{ $filtro === 'todos' ? 'active' : '' }}" href="{{ route('abogado.anexos') }}">
        Todos <span class="n">({{ $conteos['todos'] }})</span>
    </a>
    <a class="chip {{ $filtro === 'existente' ? 'active' : '' }}" href="{{ route('abogado.anexos', ['filtro' => 'existente']) }}">
        <i class="fas fa-circle" style="color:#64748b;"></i> Archivo previo <span class="n">({{ $conteos['existente'] }})</span>
    </a>
    <a class="chip {{ $filtro === 'pendiente' ? 'active' : '' }}" href="{{ route('abogado.anexos', ['filtro' => 'pendiente']) }}">
        <i class="fas fa-circle" style="color:#d97706;"></i> Pendiente firma <span class="n">({{ $conteos['pendiente'] }})</span>
    </a>
    <a class="chip {{ $filtro === 'firmado' ? 'active' : '' }}" href="{{ route('abogado.anexos', ['filtro' => 'firmado']) }}">
        <i class="fas fa-circle" style="color:var(--cth-green-bright);"></i> Firmados <span class="n">({{ $conteos['firmado'] }})</span>
    </a>
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
                <th>Tipo</th>
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
                    <span class="name">{{ $anexo->titulo ?: $anexo->nombre_original }}</span>
                    <span class="hub-sub">
                        {{ strtoupper($anexo->extension) }} · {{ $anexo->tamanoLegible() }}
                        @if($anexo->ruta_firmada && $anexo->ruta_firmada !== $anexo->ruta_segura)
                            · firmado {{ strtoupper($anexo->extension_firmada ?: '') }}
                        @endif
                    </span>
                </td>
                <td>{{ $anexo->etiquetaTipo() }}</td>
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
                    <div class="acts">
                        @if($anexo->requiereFirma())
                            <a class="action-btn" href="{{ route('abogado.anexos.download', $anexo->id) }}">
                                <i class="fas fa-print"></i> Imprimir
                            </a>
                            <form action="{{ route('abogado.anexos.firmar', $anexo->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <input class="sr-file" id="firmado-{{ $anexo->id }}" type="file" name="archivo" accept=".pdf,.jpg,.jpeg,.png" required onchange="this.form.submit()">
                                <label class="action-btn veredicto" for="firmado-{{ $anexo->id }}">
                                    <i class="fas fa-file-signature"></i> Cargar firmado
                                </label>
                            </form>
                        @else
                            @if($anexo->tipo === 'firma_gerente' && $anexo->ruta_firmada && $anexo->ruta_firmada !== $anexo->ruta_segura)
                                <a class="action-btn" href="{{ route('abogado.anexos.download', $anexo->id) }}">
                                    <i class="fas fa-print"></i> Original
                                </a>
                                <a class="action-btn veredicto" href="{{ route('abogado.anexos.download', ['id' => $anexo->id, 'v' => 'firmado']) }}">
                                    <i class="fas fa-download"></i> Firmado
                                </a>
                            @else
                                <a class="action-btn" href="{{ route('abogado.anexos.download', $anexo->id) }}">
                                    <i class="fas fa-download"></i> Descargar
                                </a>
                            @endif
                        @endif
                        @if(in_array(auth()->user()->role, ['admin','coordinadora'], true) || $anexo->user_id === auth()->id())
                            <form action="{{ route('abogado.anexos.destroy', $anexo->id) }}" method="POST"
                                  data-confirm="Se eliminará el anexo y, si existe, también el escaneo firmado."
                                  data-confirm-title="Eliminar anexo"
                                  data-confirm-ok="Eliminar"
                                  data-confirm-danger="1"
                                  data-confirm-icon="warning">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="action-btn danger" title="Eliminar"><i class="far fa-trash-alt"></i></button>
                            </form>
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
    @if($anexos->hasPages())
        <div class="pager">
            <div>{{ $anexos->firstItem() }}-{{ $anexos->lastItem() }} de {{ $anexos->total() }}</div>
            <div>{{ $anexos->links() }}</div>
        </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
    (function () {
        var form = document.getElementById('anexo-upload-form');
        if (!form) return;
        var hint = document.getElementById('anexo-hint');
        var tipo = document.getElementById('anexo-tipo');
        var caso = document.getElementById('anexo-caso');
        var file = document.getElementById('anexo-file');
        var cargar = document.getElementById('anexo-cargar');
        var hints = {
            archivo_previo: 'Word o PDF que ya tienes. Queda archivado en el expediente.',
            firma_gerente: 'Word o PDF de la terminación por justas causas. Imprimes, el gerente firma y cargas el escaneo en la fila.'
        };
        function syncHint() {
            hint.textContent = hints[tipo.value] || hints.archivo_previo;
        }
        tipo.addEventListener('change', syncHint);
        syncHint();
        cargar.addEventListener('click', function () {
            if (!caso.value || !tipo.value) {
                if (window.SIPD && SIPD.toast) {
                    SIPD.toast({ icon: 'warning', text: 'Selecciona el proceso y el tipo.' });
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
    })();
</script>
@endsection
