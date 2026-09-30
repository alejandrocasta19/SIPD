@extends('layouts.master')

@php
    $pageTitle = 'Partes involucradas';
    $roles = [
        'investigado' => 'Investigado',
        'quejoso' => 'Quejoso',
        'testigo' => 'Testigo',
        'apoderado' => 'Apoderado',
    ];
@endphp

@section('page-header')
    <div class="proc-head">
        <div>
            <h1>Partes involucradas</h1>
            <p>Registro de todas las personas vinculadas a procesos disciplinarios.</p>
        </div>
    </div>
    <div class="page-banner">
        <div class="page-banner-left">
            <div class="page-banner-title">Partes Procesales</div>
            <div class="page-banner-sub">Consulta los investigados, quejosos, testigos y apoderados de cada proceso.</div>
        </div>
        <div class="page-banner-right">
            <span class="pb-badge">{{ $conteos['todos'] }} total</span>
            @if($conteos['investigado'] > 0)<span class="pb-badge red">{{ $conteos['investigado'] }} Investigados</span>@endif
            @if($conteos['testigo'] > 0)<span class="pb-badge">{{ $conteos['testigo'] }} Testigos</span>@endif
        </div>
    </div>
@endsection

@section('styles')
<style>


    .chips { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
    .chip { display: inline-flex; align-items: center; gap: 8px; border-radius: 999px; padding: 7px 12px; background: #fff; color: #64748b; text-decoration: none; font-size: 13px; font-weight: 600; box-shadow: 0 1px 2px rgba(15,23,42,.04); }
    .chip.active { background: #ecfdf5; color: var(--cth-green-text); }
    .chip i { font-size: 8px; }
    .chip .n { color: #94a3b8; font-weight: 500; }
    .chip.active .n { color: var(--cth-green-text); }

    .dot-pend { color: #f59e0b; }
    .dot-proc { color: #3b82f6; }
    .dot-sanc { color: #f43f5e; }
    .dot-arch { color: #94a3b8; }
    .dot-green { color: var(--cth-green-bright); }
    .dot-purple { color: #8b5cf6; }

    .toolbar { display: grid; grid-template-columns: 1fr auto; gap: 12px; align-items: center; margin-bottom: 16px; }
    .search { position: relative; }
    .search i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
    .search input { width: 100%; height: 44px; border: 0; border-radius: 12px; background: #fff; padding: 0 14px 0 40px; font: inherit; font-size: 14px; box-shadow: 0 1px 2px rgba(15,23,42,.04); outline: none; }

    .table-card { background: #fff; border-radius: 18px; overflow: hidden; box-shadow: 0 1px 2px rgba(15,23,42,.04); }
    table.proc { width: 100%; border-collapse: collapse; font-size: 13px; }
    table.proc th { text-align: left; padding: 14px 12px; color: #94a3b8; font-size: 11px; letter-spacing: .06em; font-weight: 700; border-bottom: 1px solid #f1f5f9; white-space: nowrap; text-transform: uppercase; }
    table.proc td { padding: 14px 12px; border-bottom: 1px solid #f8fafc; color: #334155; vertical-align: middle; }
    table.proc tr:last-child td { border-bottom: 0; }
    table.proc tbody tr:hover { background: #fafbfc; }

    .id { color: var(--cth-green); font-weight: 700; text-decoration: none; }
    .name { font-weight: 600; color: #0f172a; }
    .st { display: inline-flex; align-items: center; gap: 6px; font-weight: 600; white-space: nowrap; }
    .st i { font-size: 8px; }

    .empty { text-align: center; padding: 40px 16px; color: #94a3b8; }
    
    .pager { display: flex; justify-content: space-between; align-items: center; padding: 14px 16px; color: #94a3b8; font-size: 13px; border-top: 1px solid #e2e8f0; }
    .pager-pages { display: flex; gap: 6px; align-items: center; }
    .pager a, .pager span.current { min-width: 30px; height: 30px; border-radius: 8px; display: grid; place-items: center; text-decoration: none; color: #64748b; background: #fff; border: 1px solid #e2e8f0; }
    .pager span.current { background: var(--cth-green-bright); border-color: var(--cth-green-bright); color: #fff; font-weight: 700; }
    
    @media (max-width: 900px) { .toolbar { grid-template-columns: 1fr; } .proc-head { flex-direction: column; } .table-card { overflow-x: auto; } }
</style>
@endsection

@section('content')
    <div class="chips">
        <a class="chip {{ $filtro === 'todos' ? 'active' : '' }}" href="{{ route('abogado.partes', request()->except('rol')) }}">
            Todos <span class="n">({{ $conteos['todos'] }})</span>
        </a>
        <a class="chip {{ $filtro === 'investigado' ? 'active' : '' }}" href="{{ route('abogado.partes', array_merge(request()->all(), ['rol' => 'investigado'])) }}">
            <i class="fas fa-circle dot-sanc"></i> Investigado <span class="n">({{ $conteos['investigado'] }})</span>
        </a>
        <a class="chip {{ $filtro === 'quejoso' ? 'active' : '' }}" href="{{ route('abogado.partes', array_merge(request()->all(), ['rol' => 'quejoso'])) }}">
            <i class="fas fa-circle dot-pend"></i> Quejoso <span class="n">({{ $conteos['quejoso'] }})</span>
        </a>
        <a class="chip {{ $filtro === 'testigo' ? 'active' : '' }}" href="{{ route('abogado.partes', array_merge(request()->all(), ['rol' => 'testigo'])) }}">
            <i class="fas fa-circle dot-proc"></i> Testigo <span class="n">({{ $conteos['testigo'] }})</span>
        </a>
        <a class="chip {{ $filtro === 'apoderado' ? 'active' : '' }}" href="{{ route('abogado.partes', array_merge(request()->all(), ['rol' => 'apoderado'])) }}">
            <i class="fas fa-circle dot-purple"></i> Apoderado <span class="n">({{ $conteos['apoderado'] }})</span>
        </a>
    </div>

    <form class="toolbar" method="GET" action="{{ route('abogado.partes') }}">
        @if(request('rol'))
            <input type="hidden" name="rol" value="{{ request('rol') }}">
        @endif
        <div class="search">
            <i class="fas fa-search"></i>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre o proceso...">
        </div>
    </form>

    <div class="table-card">
        <table class="proc">
            <thead>
                <tr>
                    <th>NOMBRE</th>
                    <th>CÉDULA</th>
                    <th>ROL</th>
                    <th>TELÉFONO</th>
                    <th>PROCESO VINCULADO</th>
                    <th>VINCULACIÓN</th>
                </tr>
            </thead>
            <tbody>
                @forelse($partes as $parte)
                    <tr>
                        <td class="name">{{ $parte->nombre }}</td>
                        <td>{{ $parte->cedula ?: '—' }}</td>
                        <td>
                            @if($parte->rol === 'investigado')
                                <span class="st"><i class="fas fa-circle dot-sanc"></i> {{ $roles[$parte->rol] }}</span>
                            @elseif($parte->rol === 'quejoso')
                                <span class="st"><i class="fas fa-circle dot-pend"></i> {{ $roles[$parte->rol] }}</span>
                            @elseif($parte->rol === 'testigo')
                                <span class="st"><i class="fas fa-circle dot-proc"></i> {{ $roles[$parte->rol] }}</span>
                            @else
                                <span class="st"><i class="fas fa-circle dot-purple"></i> {{ $roles[$parte->rol] }}</span>
                            @endif
                        </td>
                        <td>{{ $parte->telefono ?: '—' }}</td>
                        <td>
                            <a class="name" style="text-decoration:none;" href="{{ route('abogado.detalleproceso', $parte->proceso_id) }}">PRO-{{ str_pad($parte->proceso_id, 3, '0', STR_PAD_LEFT) }}</a>
                        </td>
                        <td>{{ optional($parte->vinculacion)->format('Y-m-d') ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty">No hay partes involucradas registradas</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($partes->total() > 0)
            <div class="pager">
                <div>
                    {{ $partes->firstItem() }}-{{ $partes->lastItem() }} de {{ $partes->total() }} · {{ $partes->perPage() }} por página
                </div>
                <div class="pager-pages">
                    @if($partes->onFirstPage())
                        <span class="current" style="background:#fff;color:#cbd5e1;border-color:#e2e8f0;">‹</span>
                    @else
                        <a href="{{ $partes->previousPageUrl() }}">‹</a>
                    @endif
                    @foreach($partes->getUrlRange(1, $partes->lastPage()) as $p => $url)
                        @if($p == $partes->currentPage())
                            <span class="current">{{ $p }}</span>
                        @else
                            <a href="{{ $url }}">{{ $p }}</a>
                        @endif
                    @endforeach
                    @if($partes->hasMorePages())
                        <a href="{{ $partes->nextPageUrl() }}">›</a>
                    @else
                        <span class="current" style="background:#fff;color:#cbd5e1;border-color:#e2e8f0;">›</span>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection
