@extends('layouts.master')

@php
    $isMisCasos = $isMisCasos ?? false;
    $pageTitle = $isMisCasos ? 'Mis casos' : 'Todos los procesos';
    $estadoActual = request('estado', 'todos');
    $codigo = function ($proceso) {
        return 'PRO-' . str_pad($proceso->id, 3, '0', STR_PAD_LEFT);
    };
    $esCoordinadora = auth()->user()->esCoordinadora();
@endphp

@section('page-header')
    <div class="proc-head">
        <div>
            <h1>{{ $pageTitle }}</h1>
            <p>
                @if($isMisCasos)
                    {{ $conteos['todos'] }} caso{{ $conteos['todos'] === 1 ? '' : 's' }} asignado{{ $conteos['todos'] === 1 ? '' : 's' }} a tu cuenta.
                @else
                    {{ $conteos['todos'] }} expediente{{ $conteos['todos'] === 1 ? '' : 's' }} en el sistema.
                @endif
            </p>
        </div>
        <div class="proc-head-side">
            <span class="stat-chip">{{ $conteos['todos'] }} total</span>
            @if($conteos['Pendiente'] > 0)<span class="stat-chip yellow">{{ $conteos['Pendiente'] }} Pendientes</span>@endif
            @if($conteos['Sancionado'] > 0)<span class="stat-chip red">{{ $conteos['Sancionado'] }} Sancionados</span>@endif
        @if(auth()->user()->puede('registrar_casos'))
            <a class="btn-add" href="{{ route('abogado.registro') }}"><i class="fas fa-plus"></i> Registrar proceso</a>
        @endif
        </div>
    </div>
@endsection

@section('styles')
<style>
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
        color: var(--cth-green-text);
    }

    .chip i { font-size: 8px; }
    .chip .n { color: #94a3b8; font-weight: 500; }
    .chip.active .n { color: var(--cth-green-text); }
    .dot-pend { color: #f59e0b; }
    .dot-proc { color: #3b82f6; }
    .dot-sanc { color: #f43f5e; }
    .dot-arch { color: #94a3b8; }

    .toolbar {
        display: grid;
        grid-template-columns: minmax(0,1fr) minmax(140px,220px) auto;
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
        border: 1px solid var(--cth-border);
        white-space: nowrap;
    }

    table.proc td {
        padding: 14px 12px;
        border: 1px solid var(--cth-border);
        color: #334155;
        vertical-align: middle;
    }

    table.proc tbody tr:hover { background: #fafbfc; }

    .id {
        color: var(--cth-green);
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

    table.proc th.col-acts,
    table.proc td.col-acts { text-align: center; }
    .acts { display: flex; gap: 8px; justify-content: center; }
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

    .acts a:hover, .acts button:hover { background: #ecfdf5; color: var(--cth-green-text); }
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
        border-top: 1px solid var(--cth-line);
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
        border: 1px solid var(--cth-border);
    }

    .pager span.current {
        background: var(--cth-green-bright);
        border-color: var(--cth-green-bright);
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
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar por conductor, cédula, placa o tipo de falta...">
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
                    @if($esCoordinadora)
                        <th>RH</th>
                    @endif
                    <th>ESTADO</th>
                    <th class="col-acts">ACCIONES</th>
                </tr>
            </thead>
            <tbody>
                @forelse($procesos as $proceso)
                    <tr>
                        <td>
                            <a class="id" href="{{ route('abogado.detalleproceso', ['id' => $proceso->id, 'from' => $fromDetalle ?? 'procesos']) }}">{{ $codigo($proceso) }}</a>
                            @if(($proceso->anexos_count ?? 0) > 0)
                                <span style="display:block;color:#64748b;font-size:12px;margin-top:3px;">{{ $proceso->anexos_count }} anexo{{ $proceso->anexos_count === 1 ? '' : 's' }}</span>
                            @endif
                        </td>
                        <td class="name">{{ $proceso->nombre }}</td>
                        <td>{{ $proceso->cedula ?: '—' }}</td>
                        <td>{{ $proceso->placa ?: '—' }}</td>
                        <td>{{ $proceso->modalidad ?: '—' }}</td>
                        <td>{{ $proceso->tipo_falta ?: '—' }}</td>
                        <td>{{ $proceso->fecha_falta ? \Carbon\Carbon::parse($proceso->fecha_falta)->format('Y-m-d') : '—' }}</td>
                        @if($esCoordinadora)
                            <td>{{ $proceso->user->name ?? 'Sin asignar' }}</td>
                        @endif
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
                        <td class="col-acts">
                            <div class="acts">
                                <a href="{{ route('abogado.detalleproceso', ['id' => $proceso->id, 'from' => $fromDetalle ?? 'procesos']) }}" title="Ver proceso">
                                    <i class="far fa-eye"></i>
                                </a>
                                @if(auth()->user()->puede('eliminar_casos'))
                                <form action="{{ route('abogado.eliminarproceso', $proceso->id) }}" method="POST"
                                      data-confirm="Esta acción eliminará el proceso disciplinario de forma permanente."
                                      data-confirm-title="Eliminar proceso"
                                      data-confirm-ok="Eliminar"
                                      data-confirm-danger="1"
                                      data-confirm-icon="warning">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="danger" title="Eliminar proceso">
                                        <i class="far fa-trash-alt"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $esCoordinadora ? 10 : 9 }}" class="empty">No hay procesos disciplinarios registrados</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @include('partials.paginacion', ['paginador' => $procesos])
    </div>
@endsection
