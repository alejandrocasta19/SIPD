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
        <div class="proc-head-side">
            <span class="stat-chip">{{ $conteos['todos'] }} total</span>
            @if($conteos['sancionatoria'] > 0)<span class="stat-chip red">{{ $conteos['sancionatoria'] }} Sancionatorias</span>@endif
            @if($conteos['absolutoria'] > 0)<span class="stat-chip green">{{ $conteos['absolutoria'] }} Absolutorias</span>@endif
        </div>
    </div>
@endsection

@section('styles')
<style>
    .dot-pend { color: #f59e0b; }
    .dot-proc { color: #3b82f6; }
    .dot-sanc { color: #f43f5e; }
    .dot-arch { color: #94a3b8; }
    .dot-green { color: var(--cth-green-bright); }
    .dot-purple { color: #8b5cf6; }

    .table-card { background: #fff; border-radius: 18px; overflow-x: auto; box-shadow: 0 1px 2px rgba(15,23,42,.04); }
    table.proc { width: 100%; border-collapse: collapse; font-size: 13px; }
    table.proc th { text-align: left; padding: 14px 12px; color: #94a3b8; font-size: 11px; letter-spacing: .06em; font-weight: 700; border: 1px solid var(--cth-border); white-space: nowrap; text-transform: uppercase; }
    table.proc td { padding: 14px 12px; border: 1px solid var(--cth-border); color: #334155; vertical-align: middle; }
    table.proc tbody tr:hover { background: #fafbfc; }

    .id { color: var(--cth-green); font-weight: 700; text-decoration: none; }
    .name { font-weight: 600; color: #0f172a; }
    .st { display: inline-flex; align-items: center; gap: 6px; font-weight: 600; white-space: nowrap; }
    .st i { font-size: 8px; }

    .acts { display: flex; gap: 8px; justify-content: center; align-items: center; }
    .acts a,
    .acts button {
        width: 30px;
        height: 30px;
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
    }
    .acts a:hover,
    .acts button:hover { background: #e2e8f0; color: #0f172a; }
    .acts a.btn-view:hover { background: #ecfdf5; color: var(--cth-green-text); }
    .acts button.danger:hover { background: #fff1f2; color: #e11d48; }
    .acts button.lock-del { color: #e11d48; background: #fff1f2; }
    table.proc th.col-acts,
    table.proc td.col-acts { text-align: center; width: 1%; white-space: nowrap; }

    .empty { text-align: center; padding: 40px 16px; color: #94a3b8; }
    
    .pager { display: flex; justify-content: space-between; align-items: center; padding: 14px 16px; color: #94a3b8; font-size: 13px; border-top: 1px solid var(--cth-line); }
    .pager-pages { display: flex; gap: 6px; align-items: center; }
    .pager a, .pager span.current { min-width: 30px; height: 30px; border-radius: 8px; display: grid; place-items: center; text-decoration: none; color: #64748b; background: #fff; border: 1px solid var(--cth-border); }
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
                    <th>PROCESO</th>
                    <th>CONDUCTOR / AFECTADO</th>
                    <th>MODALIDAD</th>
                    <th>TIPO</th>
                    <th>ESTADO DE FIRMA</th>
                    <th>FECHA EXPEDICIÓN</th>
                    <th class="col-acts">ACCIONES</th>
                </tr>
            </thead>
            <tbody>
                @forelse($resoluciones as $resolucion)
                    <tr>
                        <td>
                            <a class="id" style="text-decoration:none;" href="{{ route('abogado.detalleproceso', $resolucion->proceso_id) }}">PRO-{{ str_pad($resolucion->proceso_id, 3, '0', STR_PAD_LEFT) }}</a>
                        </td>
                        <td class="name">{{ $resolucion->nombre }}</td>
                        <td>{{ \App\Support\Modalidades::etiquetaCaso($resolucion->modalidad, $resolucion->cargo) }}</td>
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
                                <span class="st"><i class="fas fa-circle dot-green"></i> Firmada</span>
                            @else
                                <span class="st"><i class="fas fa-circle dot-pend"></i> Pendiente firma</span>
                            @endif
                        </td>
                        <td>{{ $resolucion->expediente }}</td>
                        <td class="col-acts">
                            <div class="acts">
                                <a href="{{ route('abogado.detalleproceso', $resolucion->proceso_id) }}" class="btn-view" title="Ver Expediente"><i class="far fa-eye"></i></a>
                                
                                @if(!$resolucion->solo_coordinadora)
                                    @if(auth()->user()->puede('eliminar_casos'))
                                    <form action="{{ route('abogado.eliminarproceso', $resolucion->proceso_id) }}" method="POST"
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
                                    @else
                                    <button type="button"
                                            class="danger lock-del"
                                            title="Solicitar permiso para eliminar"
                                            onclick="window.SIPD_abrirPermiso && SIPD_abrirPermiso('eliminar_casos')">
                                        <i class="fas fa-key"></i>
                                    </button>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty">No hay resoluciones registradas</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @include('partials.paginacion', ['paginador' => $resoluciones])
    </div>
@endsection
