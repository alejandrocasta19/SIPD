@extends('layouts.master')

@php
    $pageTitle = 'Resoluciones';
    $tipos = [
        'sancionatoria' => 'Sancionatoria',
        'absolutoria' => 'Absolutoria',
        'archivo' => 'Archivo',
        'nulidad' => 'Nulidad',
    ];
@endphp

@section('page-header')
    <div class="proc-head">
        <div>
            <h1>Resoluciones</h1>
            <p>Archivo de resoluciones emitidas en procesos disciplinarios.</p>
        </div>
    </div>
    <div class="page-banner">
        <div class="page-banner-left">
            <div class="page-banner-title">Archivo de Resoluciones</div>
            <div class="page-banner-sub">Consulta las resoluciones sancionatorias, absolutorias y de archivo emitidas.</div>
        </div>
        <div class="page-banner-right">
            <span class="pb-badge">{{ $conteos['todos'] }} total</span>
            @if($conteos['sancionatoria'] > 0)<span class="pb-badge red">{{ $conteos['sancionatoria'] }} Sancionatorias</span>@endif
            @if($conteos['absolutoria'] > 0)<span class="pb-badge green">{{ $conteos['absolutoria'] }} Absolutorias</span>@endif
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
    
    @media (max-width: 900px) { .proc-head { flex-direction: column; } .table-card { overflow-x: auto; } }
</style>
@endsection

@section('content')
    <div class="chips">
        <a class="chip {{ $filtro === 'todos' ? 'active' : '' }}" href="{{ route('abogado.resoluciones') }}">
            Todos <span class="n">({{ $conteos['todos'] }})</span>
        </a>
        <a class="chip {{ $filtro === 'sancionatoria' ? 'active' : '' }}" href="{{ route('abogado.resoluciones', ['tipo' => 'sancionatoria']) }}">
            <i class="fas fa-circle dot-sanc"></i> Sancionatoria <span class="n">({{ $conteos['sancionatoria'] }})</span>
        </a>
        <a class="chip {{ $filtro === 'absolutoria' ? 'active' : '' }}" href="{{ route('abogado.resoluciones', ['tipo' => 'absolutoria']) }}">
            <i class="fas fa-circle dot-green"></i> Absolutoria <span class="n">({{ $conteos['absolutoria'] }})</span>
        </a>
        <a class="chip {{ $filtro === 'archivo' ? 'active' : '' }}" href="{{ route('abogado.resoluciones', ['tipo' => 'archivo']) }}">
            <i class="fas fa-circle dot-arch"></i> Archivo <span class="n">({{ $conteos['archivo'] }})</span>
        </a>
        <a class="chip {{ $filtro === 'nulidad' ? 'active' : '' }}" href="{{ route('abogado.resoluciones', ['tipo' => 'nulidad']) }}">
            <i class="fas fa-circle dot-pend"></i> Nulidad <span class="n">({{ $conteos['nulidad'] }})</span>
        </a>
    </div>

    <div class="table-card">
        <table class="proc">
            <thead>
                <tr>
                    <th>N° RESOLUCIÓN</th>
                    <th>PROCESO</th>
                    <th>CONDUCTOR / AFECTADO</th>
                    <th>TIPO</th>
                    <th>ESTADO DE FIRMA</th>
                    <th>FECHA EXPEDICIÓN</th>
                </tr>
            </thead>
            <tbody>
                @forelse($resoluciones as $resolucion)
                    <tr>
                        <td>
                            <a class="id" href="{{ route('abogado.detalleproceso', $resolucion->proceso_id) }}">{{ $resolucion->numero }}</a>
                        </td>
                        <td>
                            <a class="name" style="text-decoration:none;" href="{{ route('abogado.detalleproceso', $resolucion->proceso_id) }}">PRO-{{ str_pad($resolucion->proceso_id, 3, '0', STR_PAD_LEFT) }}</a>
                        </td>
                        <td class="name">{{ $resolucion->nombre }}</td>
                        <td>
                            @if($resolucion->tipo === 'sancionatoria')
                                <span class="st"><i class="fas fa-circle dot-sanc"></i> {{ $tipos[$resolucion->tipo] }}</span>
                            @elseif($resolucion->tipo === 'absolutoria')
                                <span class="st"><i class="fas fa-circle dot-green"></i> {{ $tipos[$resolucion->tipo] }}</span>
                            @elseif($resolucion->tipo === 'nulidad')
                                <span class="st"><i class="fas fa-circle dot-pend"></i> {{ $tipos[$resolucion->tipo] }}</span>
                            @else
                                <span class="st"><i class="fas fa-circle dot-arch"></i> {{ $tipos[$resolucion->tipo] }}</span>
                            @endif
                        </td>
                        <td>
                            @if($resolucion->firmada)
                                <span style="color:var(--cth-green);font-weight:600;"><i class="fas fa-check"></i> Firmada</span>
                            @else
                                <span style="color:#d97706;font-weight:600;">Pendiente firma</span>
                            @endif
                        </td>
                        <td>{{ $resolucion->expediente }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty">No hay resoluciones registradas</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($resoluciones->total() > 0)
            <div class="pager">
                <div>
                    {{ $resoluciones->firstItem() }}-{{ $resoluciones->lastItem() }} de {{ $resoluciones->total() }} · {{ $resoluciones->perPage() }} por página
                </div>
                <div class="pager-pages">
                    @if($resoluciones->onFirstPage())
                        <span class="current" style="background:#fff;color:#cbd5e1;border-color:#e2e8f0;">‹</span>
                    @else
                        <a href="{{ $resoluciones->previousPageUrl() }}">‹</a>
                    @endif
                    @foreach($resoluciones->getUrlRange(1, $resoluciones->lastPage()) as $p => $url)
                        @if($p == $resoluciones->currentPage())
                            <span class="current">{{ $p }}</span>
                        @else
                            <a href="{{ $url }}">{{ $p }}</a>
                        @endif
                    @endforeach
                    @if($resoluciones->hasMorePages())
                        <a href="{{ $resoluciones->nextPageUrl() }}">›</a>
                    @else
                        <span class="current" style="background:#fff;color:#cbd5e1;border-color:#e2e8f0;">›</span>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection
