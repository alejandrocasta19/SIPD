@extends('layouts.master')

@php $pageTitle = 'Notificaciones'; @endphp

@section('page-header')
    <div class="proc-head">
        <div>
            <h1>Bandeja de notificaciones</h1>
            <p>Los avisos de coordinación llegan aparte de las alertas del sistema.</p>
        </div>
        @if($avisos->total() > 0 || count($alertasSistema) > 0)
            <div class="proc-head-side">
                @if(count($alertasSistema) > 0)
                    <form method="POST" action="{{ route('notificaciones.alertas.silenciar-todas') }}"
                          data-confirm="Se quitarán las alertas del sistema que ves ahora. Si entra un caso nuevo, volverán a aparecer."
                          data-confirm-title="Quitar alertas de ahora"
                          data-confirm-ok="Quitar">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-alt">
                            <i class="far fa-bell-slash"></i> Quitar alertas de ahora
                        </button>
                    </form>
                @endif
                @if($avisos->total() > 0)
                    @if($puedeBorrar)
                        <form method="POST" action="{{ route('notificaciones.leidas') }}"
                              data-confirm="Se borrarán las notificaciones que ya leíste."
                              data-confirm-title="Eliminar leídas"
                              data-confirm-ok="Eliminar"
                              data-confirm-danger="1">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-alt">
                                <i class="far fa-trash-alt"></i> Eliminar leídas
                            </button>
                        </form>
                    @else
                        <button type="button" class="btn-alt"
                                data-need-permiso="eliminar_notificaciones"
                                data-que="Borrar notificaciones de la bandeja que ya no necesito.">
                            <i class="far fa-trash-alt"></i> Solicitar borrar
                        </button>
                    @endif
                @endif
            </div>
        @endif
    </div>
@endsection

@section('styles')
<style>
    .proc-head { margin-bottom: 28px; }
    .proc-head-side { display:flex; flex-wrap:wrap; gap:10px; align-items:center; }
    
    .av-list { display: grid; gap: 14px; }
    
    .av-item {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 12px;
        align-items: stretch;
        background: #fff;
        border-radius: 20px;
        border: 1px solid var(--cth-border);
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(15,23,42,.02);
        transition: all 0.2s ease;
        animation: enterSlide 0.4s ease-out backwards;
    }
    .av-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 24px rgba(0,36,18,.06);
        border-color: #cbd5e1;
    }
    
    @keyframes enterSlide {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .av-item:nth-child(2) { animation-delay: 0.05s; }
    .av-item:nth-child(3) { animation-delay: 0.1s; }
    .av-item:nth-child(4) { animation-delay: 0.15s; }
    .av-item:nth-child(5) { animation-delay: 0.2s; }
    
    .av-item.is-unread {
        border-left: 4px solid #94a3b8;
        box-shadow: 0 4px 12px rgba(15,23,42,.04);
    }

    /* 🔴 Veredicto — rojo urgente */
    .av-item.av-danger, .av-danger.is-unread { border-left-color: #f43f5e !important; background: linear-gradient(145deg, #fff1f2 0%, #fff 100%); }
    .av-item.av-danger:hover { box-shadow: 0 12px 24px rgba(244,63,94,.10); }
    /* 🟡 Solicitud de permiso — ámbar */
    .av-item.av-warn, .av-warn.is-unread { border-left-color: #f59e0b !important; background: linear-gradient(145deg, #fffbeb 0%, #fff 100%); }
    .av-item.av-warn:hover { box-shadow: 0 12px 24px rgba(245,158,11,.10); }
    /* 🟢 Permiso respondido — verde */
    .av-item.av-ok, .av-ok.is-unread { border-left-color: #22c55e !important; background: linear-gradient(145deg, #f0fdf4 0%, #fff 100%); }
    .av-item.av-ok:hover { box-shadow: 0 12px 24px rgba(34,197,94,.10); }
    /* 🟣 Aviso coordinación — violeta */
    .av-item.av-coord, .av-coord.is-unread { border-left-color: #7c3aed !important; background: linear-gradient(145deg, #f5f3ff 0%, #fff 100%); }
    .av-item.av-coord:hover { box-shadow: 0 12px 24px rgba(124,58,237,.10); }
    /* 🔵 Sistema — azul */
    .av-item.av-sys, .av-sys.is-unread { border-left-color: #3b82f6 !important; background: linear-gradient(145deg, #eff6ff 0%, #fff 100%); }
    .av-item.av-sys:hover { box-shadow: 0 12px 24px rgba(59,130,246,.10); }
    .av-item.av-info, .av-info.is-unread { border-left-color: #3b82f6 !important; background: linear-gradient(145deg, #eff6ff 0%, #fff 100%); }
    .av-item.av-info:hover { box-shadow: 0 12px 24px rgba(59,130,246,.10); }
    
    .av-main {
        display: grid;
        grid-template-columns: 48px 1fr;
        gap: 16px;
        align-items: start;
        padding: 20px 20px;
        text-decoration: none;
        color: inherit;
        min-width: 0;
    }
    .av-main b { display: block; color: #0f172a; font-size: 15px; letter-spacing: -0.01em; margin-bottom: 2px; }
    .av-meta { color: #64748b; font-size: 13px; line-height: 1.4; display: flex; flex-wrap: wrap; gap: 4px; align-items: center; }
    
    .av-motivo {
        margin-top: 10px;
        font-size: 13px;
        color: #334155;
        background: #f8fafc;
        border-radius: 12px;
        padding: 12px 14px;
        border: 1px solid #e2e8f0;
    }
    .av-item.is-coord .av-motivo { background: #fff7ed; color: #9a3412; border-color: #ffedd5; }
    
    .dot {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: grid;
        place-items: center;
        background: #eff6ff;
        color: #2563eb;
        font-size: 18px;
        transition: transform 0.2s;
    }
    .av-item:hover .dot { transform: scale(1.08); }
    /* 🔴 Veredicto */
    .dot.danger { background: #fff1f2; color: #be123c; }
    /* 🟡 Solicitud */
    .dot.warn   { background: #fffbeb; color: #b45309; }
    /* 🟢 Permiso */
    .dot.ok     { background: #f0fdf4; color: #15803d; }
    /* 🟣 Coordinación */
    .dot.coord  { background: #f5f3ff; color: #6d28d9; }
    /* 🔵 Sistema */
    .dot.info   { background: #eff6ff; color: #1d4ed8; }
    
    .av-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .06em;
        text-transform: uppercase;
        border-radius: 999px;
        padding: 4px 10px;
        margin-bottom: 8px;
        background: #e2e8f0;
        color: #475569;
    }
    .av-chip i { font-size: 9px; }
    .av-chip.danger { background: #fff1f2; color: #be123c; }
    .av-chip.warn   { background: #fffbeb; color: #b45309; }
    .av-chip.ok     { background: #f0fdf4; color: #15803d; }
    .av-chip.coord  { background: #f5f3ff; color: #6d28d9; }
    
    .av-side {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        justify-content: space-between;
        padding: 18px 20px 18px 0;
        gap: 12px;
    }
    .av-state { font-size: 12px; font-weight: 700; color: #94a3b8; }
    .av-item.is-unread .av-state { color: #15803d; background: #dcfce7; padding: 2px 8px; border-radius: 999px; }
    
    .av-del {
        border: 0;
        background: #f1f5f9;
        color: #64748b;
        width: 38px;
        height: 38px;
        border-radius: 12px;
        cursor: pointer;
        display: grid;
        place-items: center;
        transition: all 0.2s;
    }
    .av-del:hover { background: #fee2e2; color: #be123c; transform: scale(1.05); }
    
    .empty-state { text-align: center; padding: 60px 20px; background: #fff; border-radius: 20px; box-shadow: var(--cth-shadow); animation: enterSlide 0.4s ease-out; }
    .empty-state i { color: #cbd5e1; font-size: 48px; margin-bottom: 16px; display: block; }
    .empty-state b { display: block; font-size: 16px; color: #334155; margin-bottom: 6px; }
    .empty-state p { margin: 0; color: #64748b; font-size: 14px; }
    
    @media (max-width: 700px) {
        .av-item { grid-template-columns: 1fr; }
        .av-side { flex-direction: row; padding: 0 20px 20px; align-items: center; }
        .av-main { padding-bottom: 14px; }
    }
    .av-chip.sys { background: #eff6ff; color: #1d4ed8; }
    .av-sys { border-left-color: #3b82f6; }

    .av-new-pulse {
        display: inline-block;
        width: 8px; height: 8px;
        border-radius: 50%;
        background: var(--cth-green);
        box-shadow: 0 0 0 3px rgba(13,122,79,.15);
        animation: newPulse 2s ease-in-out infinite;
    }
    @keyframes newPulse {
        0%,100% { box-shadow: 0 0 0 3px rgba(13,122,79,.15); }
        50% { box-shadow: 0 0 0 6px rgba(13,122,79,.08); }
    }

    .av-sep { color: #cbd5e1; margin: 0 2px; }
    .av-proc-ref {
        font-weight: 700;
        color: var(--cth-green-text);
        font-size: 12px;
    }
</style>
@endsection

@section('content')
    <div class="av-list">

        {{-- ── Alertas del sistema ─────────────────── --}}
        @foreach($alertasSistema as $alerta)
            <article class="av-item is-unread av-sys">
                <a class="av-main" href="{{ $alerta['href'] }}">
                    <span class="dot {{ $alerta['tono'] }}">
                        <i class="{{ $alerta['icono'] }}"></i>
                    </span>
                    <div>
                        <span class="av-chip sys">
                            <i class="fas fa-bolt"></i> Alerta del sistema
                        </span>
                        <b>{{ $alerta['titulo'] }}</b>
                        <div class="av-meta">
                            <i class="fas fa-info-circle" style="color:#3b82f6;font-size:11px;"></i>
                            {{ $alerta['detalle'] }}
                            <span class="av-sep">·</span>
                            <em style="color:#94a3b8;font-style:normal;">Se quita de tu campana, no borra el expediente.</em>
                        </div>
                    </div>
                </a>
                <div class="av-side">
                    <small class="av-state">Activa</small>
                    <form method="POST" action="{{ route('notificaciones.alertas.silenciar', $alerta['tipo']) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="av-del" title="Quitar alerta">
                            <i class="fas fa-bell-slash"></i>
                        </button>
                    </form>
                </div>
            </article>
        @endforeach

        {{-- ── Avisos de coordinación y equipo ────── --}}
        @forelse($avisos as $aviso)
            <article class="av-item av-{{ $aviso->tonoIcono() }} {{ $aviso->leida_at ? '' : 'is-unread' }}">
                <a class="av-main" href="{{ route('notificaciones.leer', $aviso->id) }}">
                    <span class="dot {{ $aviso->tonoIcono() }}">
                        <i class="fas {{ $aviso->icono() }}"></i>
                    </span>
                    <div>
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:6px;">
                            <span class="av-chip {{ $aviso->tonoIcono() }}">
                                <i class="fas {{ $aviso->icono() }}"></i>
                                {{ $aviso->etiquetaTipo() }}
                            </span>
                            @if(!$aviso->leida_at)
                                <span class="av-new-pulse"></span>
                            @endif
                        </div>
                        <b>{{ $aviso->titulo }}</b>
                        <div class="av-meta">
                            <i class="fas fa-user-circle" style="font-size:11px;"></i>
                            {{ $aviso->remitente->name ?? 'SIPD' }}
                            <span class="av-sep">·</span>
                            <time title="{{ $aviso->created_at->format('d/m/Y H:i') }}">
                                {{ $aviso->created_at->diffForHumans() }}
                            </time>
                            @if($aviso->proceso)
                                <span class="av-sep">·</span>
                                <span class="av-proc-ref">PRO-{{ str_pad($aviso->proceso->id, 3, '0', STR_PAD_LEFT) }}</span>
                            @endif
                        </div>
                        @if($aviso->motivo)
                            <div class="av-motivo">
                                <i class="fas fa-quote-left" style="font-size:10px;opacity:.5;margin-right:6px;"></i>{{ $aviso->motivo }}
                            </div>
                        @endif
                        @if($aviso->cuerpo && $aviso->cuerpo !== $aviso->motivo)
                            <div class="av-meta" style="margin-top:8px;">{{ $aviso->cuerpo }}</div>
                        @endif
                    </div>
                </a>
                <div class="av-side">
                    @if($aviso->leida_at)
                        <small class="av-state">Leída</small>
                    @else
                        <small class="av-state">Nueva</small>
                    @endif
                    @if($puedeBorrar)
                        <form method="POST" action="{{ route('notificaciones.destroy', $aviso->id) }}"
                              data-confirm="Esta notificación se quitará de tu bandeja permanentemente."
                              data-confirm-title="Eliminar notificación"
                              data-confirm-ok="Eliminar"
                              data-confirm-danger="1">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="av-del" title="Eliminar">
                                <i class="far fa-trash-alt"></i>
                            </button>
                        </form>
                    @else
                        <button type="button" class="av-del" title="Solicitar permiso para borrar"
                                data-need-permiso="eliminar_notificaciones"
                                data-que="Borrar notificaciones de la bandeja que ya no necesito.">
                            <i class="far fa-trash-alt"></i>
                        </button>
                    @endif
                </div>
            </article>
        @empty
            @if(count($alertasSistema) === 0)
                <div class="empty-state">
                    <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#f0fdf4,#dcfce7);display:grid;place-items:center;margin:0 auto 20px;font-size:28px;color:#22c55e;border:2px solid #bbf7d0;">
                        <i class="far fa-bell"></i>
                    </div>
                    <b>Tu bandeja está al día</b>
                    <p>No tienes notificaciones nuevas ni avisos pendientes.</p>
                </div>
            @endif
        @endforelse
    </div>
    @include('partials.paginacion', ['paginador' => $avisos])
@endsection
