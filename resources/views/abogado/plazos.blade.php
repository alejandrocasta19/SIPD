@extends('layouts.master')

@php
    $pageTitle = 'Plazos y términos';
    $codigoPlazo = function ($id) {
        return 'PLZ-' . str_pad($id, 3, '0', STR_PAD_LEFT);
    };
    $codigoProceso = function ($id) {
        return 'PRO-' . str_pad($id, 3, '0', STR_PAD_LEFT);
    };
@endphp

@section('page-header')
    <div class="plz-head">
        <div>
            <h1>Plazos y términos</h1>
            <p>Control de vencimientos y términos procesales por proceso disciplinario.</p>
        </div>
    </div>
@endsection

@section('styles')
<style>
    .plz-head { margin-bottom: 20px; }
    .plz-head h1 {
        margin: 0 0 4px;
        font-size: 28px;
        font-weight: 700;
        letter-spacing: -.03em;
    }
    .plz-head p { margin: 0; color: #94a3b8; font-size: 14px; }

    .kpis {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
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

    .kpi.vigente { border-top-color: #22c55e; }
    .kpi.vencer { border-top-color: #f59e0b; }
    .kpi.vencido { border-top-color: #ef4444; }

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

    .table-card {
        background: #fff;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(15,23,42,.04);
    }

    table.plz {
        width: 100%;
        border-collapse: collapse;
    }

    table.plz th {
        text-align: left;
        padding: 14px 16px;
        color: #94a3b8;
        font-size: 11px;
        letter-spacing: .06em;
        font-weight: 700;
        border-bottom: 1px solid #f1f5f9;
    }

    table.plz td {
        padding: 14px 16px;
        border-bottom: 1px solid #f8fafc;
        color: #334155;
        font-size: 14px;
    }

    table.plz tr:last-child td { border-bottom: 0; }

    .id, .pro {
        color: #16a34a;
        font-weight: 700;
        text-decoration: none;
    }

    .name { font-weight: 600; color: #0f172a; }

    .dias.late { color: #ef4444; font-weight: 600; }

    .st {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-weight: 600;
        white-space: nowrap;
    }

    .st i { font-size: 8px; }
    .st.vigente { color: #16a34a; }
    .st.vencer { color: #d97706; }
    .st.vencido { color: #dc2626; }

    .empty {
        text-align: center;
        padding: 40px 16px;
        color: #94a3b8;
    }

    @media (max-width: 900px) {
        .kpis { grid-template-columns: 1fr; }
        .table-card { overflow-x: auto; }
    }
</style>
@endsection

@section('content')
    <section class="kpis">
        <article class="kpi vigente">
            <strong>{{ $conteos['vigente'] }}</strong>
            <span>Vigente</span>
        </article>
        <article class="kpi vencer">
            <strong>{{ $conteos['por_vencer'] }}</strong>
            <span>Por vencer</span>
        </article>
        <article class="kpi vencido">
            <strong>{{ $conteos['vencido'] }}</strong>
            <span>Vencido</span>
        </article>
    </section>

    <div class="chips">
        <a class="chip {{ $filtro === 'todos' ? 'active' : '' }}" href="{{ route('abogado.plazos') }}">Todos</a>
        <a class="chip {{ $filtro === 'vigente' ? 'active' : '' }}" href="{{ route('abogado.plazos', ['estado' => 'vigente']) }}">Vigente</a>
        <a class="chip {{ $filtro === 'por_vencer' ? 'active' : '' }}" href="{{ route('abogado.plazos', ['estado' => 'por_vencer']) }}">Por vencer</a>
        <a class="chip {{ $filtro === 'vencido' ? 'active' : '' }}" href="{{ route('abogado.plazos', ['estado' => 'vencido']) }}">Vencido</a>
    </div>

    <div class="table-card">
        <table class="plz">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>PROCESO</th>
                    <th>CONDUCTOR</th>
                    <th>TIPO DE PLAZO</th>
                    <th>VENCIMIENTO</th>
                    <th>DÍAS RESTANTES</th>
                    <th>ESTADO</th>
                </tr>
            </thead>
            <tbody>
                @forelse($plazos as $plazo)
                    <tr>
                        <td>
                            <a class="id" href="{{ route('abogado.detalleproceso', $plazo->proceso_id) }}">{{ $codigoPlazo($plazo->id) }}</a>
                        </td>
                        <td>
                            <a class="pro" href="{{ route('abogado.detalleproceso', $plazo->proceso_id) }}">{{ $codigoProceso($plazo->proceso_id) }}</a>
                        </td>
                        <td class="name">{{ $plazo->conductor }}</td>
                        <td>{{ $plazo->tipo }}</td>
                        <td>{{ $plazo->vencimiento->format('Y-m-d') }}</td>
                        <td class="{{ $plazo->dias < 0 ? 'dias late' : '' }}">
                            @if($plazo->dias < 0)
                                {{ abs($plazo->dias) }} días vencido
                            @else
                                {{ $plazo->dias }} días
                            @endif
                        </td>
                        <td>
                            @if($plazo->semaforo === 'vigente')
                                <span class="st vigente"><i class="fas fa-circle"></i> Vigente</span>
                            @elseif($plazo->semaforo === 'por_vencer')
                                <span class="st vencer"><i class="fas fa-circle"></i> Por vencer</span>
                            @else
                                <span class="st vencido"><i class="fas fa-circle"></i> Vencido</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty">No hay plazos registrados</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
