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
    <div class="proc-head">
        <div>
            <h1>Plazos y términos</h1>
            <p>Control de vencimientos y términos procesales.</p>
        </div>
    </div>
    <div class="page-banner">
        <div class="page-banner-left">
            <div class="page-banner-title">Control de Plazos Procesales</div>
            <div class="page-banner-sub">Monitorea los vencimientos de cada expediente disciplinario activo.</div>
        </div>
        <div class="page-banner-right">
            <span class="pb-badge">{{ $conteos['todos'] }} total</span>
            <span class="pb-badge green">{{ $conteos['vigente'] }} Vigentes</span>
            @if($conteos['por_vencer'] > 0)<span class="pb-badge yellow">{{ $conteos['por_vencer'] }} Por vencer</span>@endif
            @if($conteos['vencido'] > 0)<span class="pb-badge red">{{ $conteos['vencido'] }} Vencidos</span>@endif
        </div>
    </div>
@endsection

@section('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&family=Inter:wght@400;600;700&display=swap');

:root {
    --font-main: 'Inter', sans-serif;
    --font-head: 'Outfit', sans-serif;
    --p-primary: #3b82f6;
    --p-dark: #0f172a;
    --p-surface: #fff;
    --c-muted: #64748b;
    --radius: 20px;
    --p-shadow: 0 10px 40px -10px rgba(15, 23, 42, 0.08);
    --p-hover: 0 20px 40px -10px rgba(15, 23, 42, 0.12);
    --t-smooth: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}



.kpis { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 20px; margin-bottom: 24px; }
.kpi {
    background: var(--p-surface); border-radius: var(--radius); padding: 24px; box-shadow: var(--p-shadow); border-top: 4px solid #e2e8f0; transition: var(--t-smooth); animation: fadeInUp 0.6s ease-out backwards;
}
.kpi:nth-child(2) { animation-delay: 0.1s; }
.kpi:nth-child(3) { animation-delay: 0.2s; }
.kpi:hover { transform: translateY(-5px); box-shadow: var(--p-hover); }

.kpi.vigente { border-top-color: var(--cth-green-bright); background: linear-gradient(180deg, #fff, #f0fdf4 150%); }
.kpi.vencer { border-top-color: #f59e0b; background: linear-gradient(180deg, #fff, #fffbeb 150%); }
.kpi.vencido { border-top-color: #ef4444; background: linear-gradient(180deg, #fff, #fef2f2 150%); }

.kpi strong { display: block; font-family: var(--font-head); font-size: 36px; line-height: 1; margin-bottom: 8px; color: var(--p-dark); }
.kpi span { font-weight: 600; color: #64748b; font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em; }

.chips { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 24px; animation: fadeInUp 0.7s ease-out backwards; }
.chip {
    display: inline-flex; align-items: center; border-radius: 999px; padding: 10px 20px; background: #fff; color: #475569; text-decoration: none; font-size: 14px; font-weight: 600; box-shadow: var(--p-shadow); transition: var(--t-smooth); border: 1px solid #e2e8f0;
}
.chip:hover { border-color: #cbd5e1; color: var(--p-dark); transform: translateY(-2px); box-shadow: var(--p-hover); }
.chip.active { background: linear-gradient(135deg, #f0fdf4, #dcfce7); color: var(--cth-green); border-color: #bbf7d0; box-shadow: 0 4px 15px rgba(34,197,94,0.15); }

.table-card { background: #fff; border-radius: var(--radius); overflow: hidden; box-shadow: var(--p-shadow); animation: fadeInUp 0.8s ease-out backwards; }

table.plz { width: 100%; border-collapse: separate; border-spacing: 0; }
table.plz th { font-family: var(--font-head); text-align: left; padding: 16px 24px; color: #64748b; font-size: 12px; letter-spacing: 0.1em; font-weight: 700; text-transform: uppercase; border-bottom: 2px solid #f1f5f9; background: #f8fafc; }
table.plz td { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: var(--p-dark); font-size: 14px; transition: var(--t-smooth); }
table.plz tr:hover td { background: #f8fafc; }
table.plz tr:last-child td { border-bottom: 0; }

.id { font-family: var(--font-head); font-weight: 700; text-decoration: none; color: #64748b; background: #f1f5f9; padding: 4px 10px; border-radius: 8px; font-size: 12px; transition: var(--t-smooth); }
.id:hover { background: #e2e8f0; color: var(--p-dark); }
.pro { color: var(--p-primary); font-weight: 700; text-decoration: none; font-family: var(--font-head); }
.pro:hover { text-decoration: underline; }

.name { font-weight: 600; color: var(--p-dark); }
.dias.late { color: #ef4444; font-weight: 700; background: #fef2f2; padding: 4px 8px; border-radius: 6px; }

.st { display: inline-flex; align-items: center; gap: 8px; font-weight: 600; font-size: 13px; padding: 6px 12px; border-radius: 12px; white-space: nowrap; }
.st.vigente { background: #f0fdf4; color: var(--cth-green); }
.st.vencer { background: #fffbeb; color: #d97706; }
.st.vencido { background: #fef2f2; color: #dc2626; }

.empty { text-align: center; padding: 40px 16px; color: #94a3b8; }
.pager { display: flex; justify-content: space-between; align-items: center; padding: 14px 24px; color: #94a3b8; font-size: 13px; }
.pager-pages { display: flex; gap: 6px; align-items: center; }
.pager a, .pager span.current { min-width: 30px; height: 30px; border-radius: 8px; display: grid; place-items: center; text-decoration: none; color: #64748b; background: #fff; border: 1px solid #e2e8f0; }
.pager span.current { background: var(--p-primary); border-color: var(--p-primary); color: #fff; font-weight: 700; }
@media (max-width: 900px) { .kpis { grid-template-columns: 1fr; } .table-card { overflow-x: auto; } }
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
        @if($plazos->total() > 0)
            <div class="pager">
                <div>
                    {{ $plazos->firstItem() }}-{{ $plazos->lastItem() }} de {{ $plazos->total() }} · {{ $plazos->perPage() }} por página
                </div>
                <div class="pager-pages">
                    @if($plazos->onFirstPage())
                        <span class="current" style="background:#fff;color:#cbd5e1;border-color:#e2e8f0;">‹</span>
                    @else
                        <a href="{{ $plazos->previousPageUrl() }}">‹</a>
                    @endif
                    @foreach($plazos->getUrlRange(1, $plazos->lastPage()) as $p => $url)
                        @if($p == $plazos->currentPage())
                            <span class="current">{{ $p }}</span>
                        @else
                            <a href="{{ $url }}">{{ $p }}</a>
                        @endif
                    @endforeach
                    @if($plazos->hasMorePages())
                        <a href="{{ $plazos->nextPageUrl() }}">›</a>
                    @else
                        <span class="current" style="background:#fff;color:#cbd5e1;border-color:#e2e8f0;">›</span>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection
