@extends('layouts.master')

@php
    $pageTitle = 'Procesos disciplinarios';
    $estadoActual = request('estado', 'todos');
    $codigo = function ($proceso) {
        return 'PRO-' . str_pad($proceso->id, 3, '0', STR_PAD_LEFT);
    };
@endphp

@section('page-header')
    <div class="proc-head">
        <div>
            <h1>Procesos disciplinarios</h1>
            <p>{{ $conteos['todos'] }} proceso{{ $conteos['todos'] === 1 ? '' : 's' }} registrado{{ $conteos['todos'] === 1 ? '' : 's' }} en el sistema.</p>
        </div>
        <a class="btn-add" href="{{ route('abogado.registro') }}">
            <i class="fas fa-plus"></i> Registrar proceso
        </a>
    </div>
@endsection

@section('styles')
<style>
    .proc-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 18px;
    }

    .proc-head h1 {
        margin: 0 0 4px;
        font-size: 28px;
        font-weight: 700;
        letter-spacing: -.03em;
    }

    .proc-head p {
        margin: 0;
        color: #94a3b8;
        font-size: 14px;
    }

    .btn-add {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #22c55e;
        color: #fff;
        border-radius: 999px;
        padding: 11px 18px;
        font-weight: 700;
        font-size: 14px;
        text-decoration: none;
        white-space: nowrap;
    }

    .btn-add:hover { color: #fff; filter: brightness(1.05); }

    .chips {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 16px;
    }

    .chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border-radius: 999px;
        padding: 7px 12px;
        background: #fff;
        color: #64748b;
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        box-shadow: 0 1px 2px rgba(15,23,42,.04);
    }

    .chip.active {
        background: #ecfdf5;
        color: #166534;
    }

    .chip i { font-size: 8px; }
    .chip .n { color: #94a3b8; font-weight: 500; }
    .chip.active .n { color: #166534; }
    .dot-pend { color: #f59e0b; }
    .dot-proc { color: #3b82f6; }
    .dot-sanc { color: #f43f5e; }
    .dot-arch { color: #94a3b8; }

    .toolbar {
        display: grid;
        grid-template-columns: 1fr 220px auto;
        gap: 12px;
        align-items: center;
        margin-bottom: 16px;
    }

    .search {
        position: relative;
    }

    .search i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
    }

    .search input,
    .toolbar select {
        width: 100%;
        height: 44px;
        border: 0;
        border-radius: 12px;
        background: #fff;
        padding: 0 14px 0 40px;
        font: inherit;
        font-size: 14px;
        box-shadow: 0 1px 2px rgba(15,23,42,.04);
        outline: none;
    }

    .toolbar select { padding-left: 14px; color: #475569; }

    .results {
        color: #94a3b8;
        font-size: 13px;
        white-space: nowrap;
    }

    .table-card {
        background: #fff;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(15,23,42,.04);
    }

    table.proc {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }

    table.proc th {
        text-align: left;
        padding: 14px 12px;
        color: #94a3b8;
        font-size: 11px;
        letter-spacing: .06em;
        font-weight: 700;
        border-bottom: 1px solid #f1f5f9;
        white-space: nowrap;
    }

    table.proc td {
        padding: 14px 12px;
        border-bottom: 1px solid #f8fafc;
        color: #334155;
        vertical-align: middle;
    }

    table.proc tr:last-child td { border-bottom: 0; }
    table.proc tbody tr:hover { background: #fafbfc; }

    .id {
        color: #16a34a;
        font-weight: 700;
        text-decoration: none;
    }

    .name { font-weight: 600; color: #0f172a; }

    .st {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-weight: 600;
        white-space: nowrap;
    }

    .st i { font-size: 8px; }

    .acts { display: flex; gap: 8px; }
    .acts a, .acts button {
        width: 30px;
        height: 30px;
        border: 0;
        background: #f8fafc;
        color: #64748b;
        border-radius: 8px;
        display: grid;
        place-items: center;
        text-decoration: none;
        cursor: pointer;
    }

    .acts a:hover, .acts button:hover { background: #ecfdf5; color: #166534; }
    .acts button.danger:hover { background: #fff1f2; color: #e11d48; }

    .empty {
        text-align: center;
        padding: 40px 16px;
        color: #94a3b8;
    }

    .pager {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px 16px;
        color: #94a3b8;
        font-size: 13px;
    }

    .pager-pages { display: flex; gap: 6px; align-items: center; }

    .pager a, .pager span.current {
        min-width: 30px;
        height: 30px;
        border-radius: 8px;
        display: grid;
        place-items: center;
        text-decoration: none;
        color: #64748b;
        background: #fff;
        border: 1px solid #e2e8f0;
    }

    .pager span.current {
        background: #22c55e;
        border-color: #22c55e;
        color: #fff;
        font-weight: 700;
    }

    @media (max-width: 900px) {
        .toolbar { grid-template-columns: 1fr; }
        .proc-head { flex-direction: column; }
        .table-card { overflow-x: auto; }
    }
</style>
@endsection

@section('content')
    <div class="chips">
        <a class="chip {{ $estadoActual === 'todos' ? 'active' : '' }}" href="{{ route('abogado.consultarproceso', array_merge(request()->except('estado', 'page'), ['estado' => 'todos'])) }}">
            Todos <span class="n">({{ $conteos['todos'] }})</span>
        </a>
        <a class="chip {{ $estadoActual === 'Pendiente' ? 'active' : '' }}" href="{{ route('abogado.consultarproceso', array_merge(request()->except('page'), ['estado' => 'Pendiente'])) }}">
            <i class="fas fa-circle dot-pend"></i> Pendiente <span class="n">({{ $conteos['Pendiente'] }})</span>
        </a>
        <a class="chip {{ $estadoActual === 'En Proceso' ? 'active' : '' }}" href="{{ route('abogado.consultarproceso', array_merge(request()->except('page'), ['estado' => 'En Proceso'])) }}">
            <i class="fas fa-circle dot-proc"></i> En proceso <span class="n">({{ $conteos['En Proceso'] }})</span>
        </a>
        <a class="chip {{ $estadoActual === 'Sancionado' ? 'active' : '' }}" href="{{ route('abogado.consultarproceso', array_merge(request()->except('page'), ['estado' => 'Sancionado'])) }}">
            <i class="fas fa-circle dot-sanc"></i> Sancionado <span class="n">({{ $conteos['Sancionado'] }})</span>
        </a>
        <a class="chip {{ $estadoActual === 'Archivado' ? 'active' : '' }}" href="{{ route('abogado.consultarproceso', array_merge(request()->except('page'), ['estado' => 'Archivado'])) }}">
            <i class="fas fa-circle dot-arch"></i> Archivado <span class="n">({{ $conteos['Archivado'] }})</span>
        </a>
    </div>

    <form class="toolbar" method="GET" action="{{ route('abogado.consultarproceso') }}">
        @if(request('estado'))
            <input type="hidden" name="estado" value="{{ request('estado') }}">
        @endif
        <div class="search">
            <i class="fas fa-search"></i>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar por conductor, cédula, placa o ID...">
        </div>
        <select name="modalidad" onchange="this.form.submit()">
            <option value="">Todas las modalidades</option>
            @foreach($modalidades as $modalidad)
                <option value="{{ $modalidad }}" {{ request('modalidad') === $modalidad ? 'selected' : '' }}>{{ $modalidad }}</option>
            @endforeach
        </select>
        <div class="results">{{ $procesos->total() }} resultado{{ $procesos->total() === 1 ? '' : 's' }}</div>
    </form>

    <div class="table-card">
        <table class="proc">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>CONDUCTOR</th>
                    <th>CÉDULA</th>
                    <th>PLACA</th>
                    <th>MODALIDAD</th>
                    <th>TIPO DE FALTA</th>
                    <th>FECHA FALTA</th>
                    <th>ESTADO</th>
                    <th>ACCIONES</th>
                </tr>
            </thead>
            <tbody>
                @forelse($procesos as $proceso)
                    <tr>
                        <td>
                            <a class="id" href="{{ route('abogado.detalleproceso', $proceso->id) }}">{{ $codigo($proceso) }}</a>
                        </td>
                        <td class="name">{{ $proceso->nombre }}</td>
                        <td>{{ $proceso->cedula ?: '—' }}</td>
                        <td>{{ $proceso->placa ?: '—' }}</td>
                        <td>{{ $proceso->modalidad ?: '—' }}</td>
                        <td>{{ $proceso->tipo_falta ?: '—' }}</td>
                        <td>{{ $proceso->fecha_falta ? \Carbon\Carbon::parse($proceso->fecha_falta)->format('Y-m-d') : '—' }}</td>
                        <td>
                            @if($proceso->estado == 'Pendiente')
                                <span class="st"><i class="fas fa-circle dot-pend"></i> Pendiente</span>
                            @elseif($proceso->estado == 'En Proceso')
                                <span class="st"><i class="fas fa-circle dot-proc"></i> En proceso</span>
                            @elseif($proceso->estado == 'Sancionado')
                                <span class="st"><i class="fas fa-circle dot-sanc"></i> Sancionado</span>
                            @else
                                <span class="st"><i class="fas fa-circle dot-arch"></i> Archivado</span>
                            @endif
                        </td>
                        <td>
                            <div class="acts">
                                <a href="{{ route('abogado.detalleproceso', $proceso->id) }}" title="Ver">
                                    <i class="far fa-eye"></i>
                                </a>
                                @if(auth()->user()->role == 'coordinadora')
                                    <form action="{{ route('abogado.eliminarproceso', $proceso->id) }}" method="POST"
                                          onsubmit="return confirm('¿Deseas eliminar este proceso disciplinario?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="danger" title="Eliminar">
                                            <i class="far fa-trash-alt"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="empty">No hay procesos disciplinarios registrados</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($procesos->total() > 0)
            <div class="pager">
                <div>
                    {{ $procesos->firstItem() }}-{{ $procesos->lastItem() }} de {{ $procesos->total() }}
                    · {{ $procesos->perPage() }} por página
                </div>
                <div class="pager-pages">
                    @if($procesos->onFirstPage())
                        <span class="current" style="background:#fff;color:#cbd5e1;border-color:#e2e8f0;">‹</span>
                    @else
                        <a href="{{ $procesos->previousPageUrl() }}">‹</a>
                    @endif

                    @foreach($procesos->getUrlRange(1, $procesos->lastPage()) as $page => $url)
                        @if($page == $procesos->currentPage())
                            <span class="current">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach

                    @if($procesos->hasMorePages())
                        <a href="{{ $procesos->nextPageUrl() }}">›</a>
                    @else
                        <span class="current" style="background:#fff;color:#cbd5e1;border-color:#e2e8f0;">›</span>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection
