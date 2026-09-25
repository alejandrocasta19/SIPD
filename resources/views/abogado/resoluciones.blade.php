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
    <div class="res-head">
        <div>
            <h1>Resoluciones</h1>
            <p>Archivo de resoluciones emitidas en procesos disciplinarios.</p>
        </div>
    </div>
@endsection

@section('styles')
<style>
    .res-head { margin-bottom: 20px; }
    .res-head h1 {
        margin: 0 0 4px;
        font-size: 28px;
        font-weight: 700;
        letter-spacing: -.03em;
    }
    .res-head p { margin: 0; color: #94a3b8; font-size: 14px; }

    .kpis {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 18px;
    }

    .kpi {
        background: #fff;
        border-radius: 16px;
        padding: 18px 20px 16px;
        box-shadow: 0 1px 2px rgba(15,23,42,.04);
        border-top: 3px solid #e2e8f0;
    }

    .kpi.sancionatoria { border-top-color: #22c55e; }
    .kpi.absolutoria { border-top-color: #14b8a6; }
    .kpi.archivo { border-top-color: #94a3b8; }
    .kpi.nulidad { border-top-color: #f59e0b; }

    .kpi strong {
        display: block;
        font-size: 32px;
        line-height: 1;
        margin-bottom: 8px;
    }

    .kpi span { color: #64748b; font-size: 14px; }

    .chips {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 16px;
    }

    .chip {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 7px 14px;
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

    .list { display: grid; gap: 10px; }

    .row {
        display: grid;
        grid-template-columns: 110px 1fr auto auto 28px;
        gap: 16px;
        align-items: center;
        background: #fff;
        border-radius: 16px;
        padding: 16px 18px;
        box-shadow: 0 1px 2px rgba(15,23,42,.04);
        text-decoration: none;
        color: inherit;
    }

    .row:hover { box-shadow: 0 8px 20px rgba(15,23,42,.06); }

    .num {
        color: #16a34a;
        font-weight: 700;
        font-size: 13px;
    }

    .num small {
        display: block;
        color: #94a3b8;
        font-weight: 500;
        margin-bottom: 2px;
    }

    .who b {
        display: block;
        font-size: 15px;
        color: #0f172a;
    }

    .who span {
        color: #94a3b8;
        font-size: 13px;
    }

    .badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        font-weight: 600;
        white-space: nowrap;
    }

    .badge i { font-size: 8px; }
    .badge.sancionatoria { color: #dc2626; }
    .badge.absolutoria { color: #16a34a; }
    .badge.archivo { color: #64748b; }
    .badge.nulidad { color: #d97706; }

    .sign {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        font-weight: 600;
        white-space: nowrap;
    }

    .sign.ok { color: #16a34a; }
    .sign.wait { color: #d97706; }

    .chev { color: #cbd5e1; }

    .empty {
        text-align: center;
        padding: 40px 16px;
        color: #94a3b8;
        background: #fff;
        border-radius: 16px;
    }

    @media (max-width: 900px) {
        .kpis { grid-template-columns: 1fr 1fr; }
        .row { grid-template-columns: 1fr; gap: 8px; }
    }
</style>
@endsection

@section('content')
    <section class="kpis">
        <article class="kpi sancionatoria">
            <strong>{{ $conteos['sancionatoria'] }}</strong>
            <span>Sancionatoria</span>
        </article>
        <article class="kpi absolutoria">
            <strong>{{ $conteos['absolutoria'] }}</strong>
            <span>Absolutoria</span>
        </article>
        <article class="kpi archivo">
            <strong>{{ $conteos['archivo'] }}</strong>
            <span>Archivo</span>
        </article>
        <article class="kpi nulidad">
            <strong>{{ $conteos['nulidad'] }}</strong>
            <span>Nulidad</span>
        </article>
    </section>

    <div class="chips">
        <a class="chip {{ $filtro === 'todos' ? 'active' : '' }}" href="{{ route('abogado.resoluciones') }}">Todos</a>
        <a class="chip {{ $filtro === 'sancionatoria' ? 'active' : '' }}" href="{{ route('abogado.resoluciones', ['tipo' => 'sancionatoria']) }}">Sancionatoria</a>
        <a class="chip {{ $filtro === 'absolutoria' ? 'active' : '' }}" href="{{ route('abogado.resoluciones', ['tipo' => 'absolutoria']) }}">Absolutoria</a>
        <a class="chip {{ $filtro === 'archivo' ? 'active' : '' }}" href="{{ route('abogado.resoluciones', ['tipo' => 'archivo']) }}">Archivo</a>
        <a class="chip {{ $filtro === 'nulidad' ? 'active' : '' }}" href="{{ route('abogado.resoluciones', ['tipo' => 'nulidad']) }}">Nulidad</a>
    </div>

    <div class="list">
        @forelse($resoluciones as $resolucion)
            <a class="row" href="{{ route('abogado.detalleproceso', $resolucion->proceso_id) }}">
                <div class="num">
                    <small>N°</small>
                    {{ $resolucion->numero }}
                </div>
                <div class="who">
                    <b>{{ $resolucion->nombre }}</b>
                    <span>
                        PRO-{{ str_pad($resolucion->proceso_id, 3, '0', STR_PAD_LEFT) }}
                        · {{ $resolucion->abogado }}
                        · Exp. {{ $resolucion->expediente }}
                    </span>
                </div>
                <span class="badge {{ $resolucion->tipo }}">
                    <i class="fas fa-circle"></i> {{ $tipos[$resolucion->tipo] }}
                </span>
                @if($resolucion->firmada)
                    <span class="sign ok"><i class="fas fa-check"></i> Firmada</span>
                @else
                    <span class="sign wait">Pendiente</span>
                @endif
                <i class="fas fa-chevron-down chev"></i>
            </a>
        @empty
            <div class="empty">No hay resoluciones registradas</div>
        @endforelse
    </div>
@endsection
