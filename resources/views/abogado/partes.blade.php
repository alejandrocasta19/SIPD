@extends('layouts.master')

@php
    $pageTitle = 'Partes involucradas';
    $roles = [
        'investigado' => 'Investigado',
        'quejoso' => 'Quejoso',
        'testigo' => 'Testigo',
        'apoderado' => 'Apoderado',
    ];
    $iconos = [
        'investigado' => 'fas fa-exclamation-triangle',
        'quejoso' => 'far fa-user',
        'testigo' => 'far fa-eye',
        'apoderado' => 'fas fa-briefcase',
    ];
@endphp

@section('page-header')
    <div class="par-head">
        <div>
            <h1>Partes involucradas</h1>
            <p>Registro de todas las personas vinculadas a procesos disciplinarios.</p>
        </div>
    </div>
@endsection

@section('styles')
<style>
    .par-head { margin-bottom: 18px; }
    .par-head h1 {
        margin: 0 0 4px;
        font-size: 28px;
        font-weight: 700;
        letter-spacing: -.03em;
    }
    .par-head p { margin: 0; color: #94a3b8; font-size: 14px; }

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

    .chip .n { color: #94a3b8; font-weight: 500; }
    .chip.active .n { color: #166534; }

    .search {
        position: relative;
        max-width: 360px;
        margin-bottom: 18px;
    }

    .search i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
    }

    .search input {
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

    .cards {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
    }

    .card {
        background: #fff;
        border-radius: 18px;
        padding: 18px 18px 16px;
        box-shadow: 0 1px 2px rgba(15,23,42,.04);
        text-decoration: none;
        color: inherit;
        display: block;
    }

    .card:hover { box-shadow: 0 8px 20px rgba(15,23,42,.06); }

    .card-top {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 14px;
    }

    .ico {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        flex-shrink: 0;
        font-size: 13px;
    }

    .ico.investigado { background: #f1f5f9; color: #475569; }
    .ico.quejoso { background: #fff7ed; color: #c2410c; }
    .ico.testigo { background: #eff6ff; color: #1d4ed8; }
    .ico.apoderado { background: #f5f3ff; color: #7c3aed; }

    .card-top b {
        display: block;
        font-size: 15px;
        color: #0f172a;
    }

    .rol {
        display: block;
        font-size: 13px;
        color: #94a3b8;
        margin-top: 2px;
    }

    .cc {
        color: #94a3b8;
        font-size: 12px;
        margin: 2px 0 12px;
    }

    .meta {
        display: grid;
        grid-template-columns: 92px 1fr;
        gap: 6px 8px;
        font-size: 13px;
    }

    .meta dt { color: #94a3b8; }
    .meta dd { margin: 0; color: #334155; font-weight: 500; }
    .meta a { color: #16a34a; font-weight: 700; text-decoration: none; }

    .empty {
        grid-column: 1 / -1;
        text-align: center;
        padding: 40px 16px;
        color: #94a3b8;
        background: #fff;
        border-radius: 18px;
    }

    @media (max-width: 1100px) { .cards { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 700px) { .cards { grid-template-columns: 1fr; } }
</style>
@endsection

@section('content')
    <div class="chips">
        <a class="chip {{ $filtro === 'todos' ? 'active' : '' }}" href="{{ route('abogado.partes', request()->except('rol')) }}">
            Todos <span class="n">({{ $conteos['todos'] }})</span>
        </a>
        <a class="chip {{ $filtro === 'investigado' ? 'active' : '' }}" href="{{ route('abogado.partes', array_merge(request()->all(), ['rol' => 'investigado'])) }}">
            <i class="fas fa-exclamation-triangle"></i> Investigado <span class="n">({{ $conteos['investigado'] }})</span>
        </a>
        <a class="chip {{ $filtro === 'quejoso' ? 'active' : '' }}" href="{{ route('abogado.partes', array_merge(request()->all(), ['rol' => 'quejoso'])) }}">
            <i class="far fa-user"></i> Quejoso <span class="n">({{ $conteos['quejoso'] }})</span>
        </a>
        <a class="chip {{ $filtro === 'testigo' ? 'active' : '' }}" href="{{ route('abogado.partes', array_merge(request()->all(), ['rol' => 'testigo'])) }}">
            <i class="far fa-eye"></i> Testigo <span class="n">({{ $conteos['testigo'] }})</span>
        </a>
        <a class="chip {{ $filtro === 'apoderado' ? 'active' : '' }}" href="{{ route('abogado.partes', array_merge(request()->all(), ['rol' => 'apoderado'])) }}">
            <i class="fas fa-briefcase"></i> Apoderado <span class="n">({{ $conteos['apoderado'] }})</span>
        </a>
    </div>

    <form class="search" method="GET" action="{{ route('abogado.partes') }}">
        @if(request('rol'))
            <input type="hidden" name="rol" value="{{ request('rol') }}">
        @endif
        <i class="fas fa-search"></i>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre o proceso...">
    </form>

    <div class="cards">
        @forelse($partes as $parte)
            <a class="card" href="{{ route('abogado.detalleproceso', $parte->proceso_id) }}">
                <div class="card-top">
                    <span class="ico {{ $parte->rol }}">
                        <i class="{{ $iconos[$parte->rol] }}"></i>
                    </span>
                    <div>
                        <b>{{ $parte->nombre }}</b>
                        <span class="rol">{{ $roles[$parte->rol] }}</span>
                    </div>
                </div>
                <div class="cc">CC {{ $parte->cedula ?: '—' }}</div>
                <dl class="meta">
                    <dt>Proceso</dt>
                    <dd>PRO-{{ str_pad($parte->proceso_id, 3, '0', STR_PAD_LEFT) }}</dd>
                    <dt>Teléfono</dt>
                    <dd>{{ $parte->telefono ?: '—' }}</dd>
                    <dt>Correo</dt>
                    <dd>{{ $parte->correo ?: '—' }}</dd>
                    <dt>Vinculación</dt>
                    <dd>{{ optional($parte->vinculacion)->format('Y-m-d') ?: '—' }}</dd>
                </dl>
            </a>
        @empty
            <div class="empty">No hay partes involucradas registradas</div>
        @endforelse
    </div>
@endsection
