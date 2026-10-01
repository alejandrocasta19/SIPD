@extends('layouts.master')

@php
    $pageTitle = 'Veredictos';
    $codigo = function ($proceso) {
        return 'PRO-' . str_pad($proceso->id, 3, '0', STR_PAD_LEFT);
    };
@endphp

@section('page-header')
    <div class="proc-head">
        <div>
            <h1>Veredictos</h1>
            <p>Solo tú cierras el expediente. Sancionar o archivar es definitivo.</p>
        </div>
        <div class="proc-head-side">
            <span class="stat-chip">{{ $procesos->total() }} pendiente{{ $procesos->total() === 1 ? '' : 's' }}</span>
            <a class="btn-alt" href="{{ route('abogado.consultarproceso') }}"><i class="fas fa-search"></i> Todos los procesos</a>
        </div>
    </div>
@endsection

@section('styles')
<style>
    .ver-card { background:#fff; border:1px solid #e2e8f0; border-radius:18px; box-shadow:0 1px 2px rgba(15,23,42,.04); overflow:hidden; }
    .ver-row { display:grid; grid-template-columns: 88px minmax(0,1.4fr) minmax(0,1fr) auto; gap:16px; align-items:center; padding:18px 20px; border-bottom:1px solid #f1f5f9; }
    .ver-row:last-child { border-bottom:0; }
    .ver-code { font-weight:800; color:var(--cth-green); text-decoration:none; }
    .ver-main b { display:block; color:#0f172a; font-size:15px; }
    .ver-main small { color:#64748b; font-size:12px; }
    .ver-rh { color:#475569; font-size:13px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .ver-acts { display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end; }
    .ver-acts button, .ver-acts a {
        border:0; border-radius:10px; padding:8px 14px; font-weight:700; font-size:12px; cursor:pointer; text-decoration:none;
    }
    .ver-acts .ghost { background:#f1f5f9; color:#334155; }
    .ver-acts .sanc { background:#e11d48; color:#fff; }
    .ver-acts .arch { background:#475569; color:#fff; }
    .empty { text-align:center; color:#94a3b8; padding:48px 16px; }
    .pager { display:flex; justify-content:space-between; padding:14px 20px; color:#64748b; font-size:13px; }
    @media (max-width: 900px) { .ver-row { grid-template-columns: 1fr; } .ver-acts { justify-content:flex-start; } }
</style>
@endsection

@section('content')
    <div class="ver-card">
        @forelse($procesos as $proceso)
            <div class="ver-row">
                <a class="ver-code" href="{{ route('abogado.detalleproceso', $proceso->id) }}">{{ $codigo($proceso) }}</a>
                <div class="ver-main">
                    <b>{{ $proceso->nombre }}</b>
                    <small>{{ $proceso->tipo_falta ?: 'Sin tipificar' }} · En Proceso</small>
                </div>
                <div class="ver-rh">{{ $proceso->user->name ?? 'Sin RH asignado' }}</div>
                <div class="ver-acts">
                    <a class="ghost" href="{{ route('abogado.detalleproceso', $proceso->id) }}">Ver expediente</a>
                    <form action="{{ route('abogado.actualizarestado', $proceso->id) }}" method="POST"
                          data-confirm="Esto cerrará el proceso de forma permanente."
                          data-confirm-title="Sancionar proceso"
                          data-confirm-ok="Sancionar"
                          data-confirm-danger="1"
                          data-confirm-icon="warning">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="estado" value="Sancionado">
                        <button type="submit" class="sanc">Sancionar</button>
                    </form>
                    <form action="{{ route('abogado.actualizarestado', $proceso->id) }}" method="POST"
                          data-confirm="El expediente quedará archivado."
                          data-confirm-title="Archivar proceso"
                          data-confirm-ok="Archivar"
                          data-confirm-icon="question">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="estado" value="Archivado">
                        <button type="submit" class="arch">Archivar</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="empty">No hay casos esperando veredicto.</div>
        @endforelse

        @include('partials.paginacion', ['paginador' => $procesos])
    </div>
@endsection
