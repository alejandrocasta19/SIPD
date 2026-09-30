@extends('layouts.master')

@php
    $pageTitle = 'Coordinación de RH';
    $codigo = function ($proceso) {
        return 'PRO-' . str_pad($proceso->id, 3, '0', STR_PAD_LEFT);
    };
@endphp

@section('page-header')
    <div class="proc-head">
        <div>
            <h1>Coordinación de RH</h1>
            <p>
                @if($enProceso > 0 || $sinAbogado > 0)
                    {{ $enProceso }} pendiente{{ $enProceso === 1 ? '' : 's' }} de veredicto
                    @if($sinAbogado > 0)
                        · {{ $sinAbogado }} sin responsable
                    @endif
                @else
                    Supervisión de expedientes, equipo y veredictos.
                @endif
            </p>
        </div>
        <div class="proc-head-side">
            <span class="stat-chip">{{ $total }} total</span>
            <span class="stat-chip green">{{ $tasaResolucion }}% resolución</span>
            @if($enProceso > 0)<span class="stat-chip">{{ $enProceso }} veredicto</span>@endif
            @if($sinAbogado > 0)<span class="stat-chip yellow">{{ $sinAbogado }} sin RH</span>@endif
            <a class="btn-add" href="{{ route('coordinadora.veredictos') }}"><i class="fas fa-gavel"></i> Veredictos</a>
            <a class="btn-alt" href="{{ route('coordinadora.solicitudes') }}"><i class="fas fa-key"></i> Solicitudes</a>
            <a class="btn-alt" href="{{ route('coordinadora.notificar') }}"><i class="far fa-paper-plane"></i> Avisar</a>
        </div>
    </div>
@endsection

@section('styles')
<style>
    .section-head { display: flex; justify-content: space-between; align-items: center; margin: 0 0 16px; }
    .section-head h3 { margin: 0; font-size: 16px; font-weight: 700; color: #0f172a; }
    .section-head a { color: var(--cth-green); font-size: 13px; font-weight: 600; text-decoration: none; }

    .pipeline { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin-bottom: 32px; }
    .pipe { background: #fff; border: 1px solid #e2e8f0; border-radius: 18px; padding: 20px; box-shadow: 0 1px 2px rgba(15,23,42,.04); text-decoration: none; color: inherit; display: block; }
    .pipe:hover { border-color: #b7d0c0; }
    .pipe-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }

    .pipe-ico { width: 36px; height: 36px; border-radius: 10px; display: grid; place-items: center; font-size: 14px; }
    .pipe-ico.y { background: #fef3c7; color: #d97706; }
    .pipe-ico.b { background: #eff6ff; color: #2563eb; }
    .pipe-ico.r { background: #fff1f2; color: #e11d48; }
    .pipe-ico.g { background: #f8fafc; color: #64748b; }

    .pipe-num { font-size: 24px; font-weight: 700; color: #0f172a; }
    .pipe-title { font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 10px; }

    .bar { height: 4px; background: #f1f5f9; border-radius: 999px; overflow: hidden; margin-bottom: 8px; }
    .bar > span { display: block; height: 100%; border-radius: 999px; }
    .bar.yellow > span { background: #f59e0b; }
    .bar.blue > span { background: #3b82f6; }
    .bar.rose > span { background: #e11d48; }
    .bar.gray > span { background: #94a3b8; }
    .pipe small { color: #94a3b8; font-size: 11px; font-weight: 600; }

    .grid { display: grid; grid-template-columns: minmax(0, 1.6fr) minmax(0, 1fr); gap: 24px; align-items: start; }

    .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 18px; padding: 24px; box-shadow: 0 1px 2px rgba(15,23,42,.04); margin-bottom: 24px; }
    .card-title { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; font-weight: 700; font-size: 16px; color: #0f172a; }
    .card-title a { color: var(--cth-green); font-size: 13px; font-weight: 600; text-decoration: none; }

    .chip { background: #eff6ff; color: #2563eb; border-radius: 8px; font-size: 11px; font-weight: 700; padding: 4px 10px; }

    .row-item { display: flex; align-items: center; gap: 14px; padding: 14px 0; border-bottom: 1px solid #f1f5f9; text-decoration: none; }
    .row-item:last-child { border-bottom: 0; }
    .row-stack { display: flex; flex-direction: column; gap: 8px; padding: 14px 0; border-bottom: 1px solid #f1f5f9; }
    .row-stack:last-child { border-bottom: 0; }

    .dot { width: 36px; height: 36px; border-radius: 10px; display: grid; place-items: center; font-size: 12px; font-weight: 700; flex-shrink: 0; }
    .dot.ok { background: #f0fdf4; color: var(--cth-green-text); border: 1px solid #bbf7d0; }
    .dot.blue { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
    .dot.warn { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }

    .row-main { flex: 1; min-width: 0; }
    .row-main b { display: block; font-size: 14px; font-weight: 600; color: #0f172a; margin-bottom: 2px; }
    .row-main small { color: #64748b; font-size: 12px; }

    .status { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 8px; white-space: nowrap; }
    .status.pend { background: #fffbeb; color: #d97706; }
    .status.proc { background: #eff6ff; color: #2563eb; }
    .status.sanc { background: #fff1f2; color: #e11d48; }
    .status.arch { background: #f8fafc; color: #475569; }

    .meta { color: #94a3b8; font-size: 12px; margin-right: 12px; }

    .assign { display: flex; gap: 8px; align-items: center; }
    .assign select {
        height: 36px; border: 1px solid #e2e8f0; border-radius: 10px; padding: 0 10px;
        font: inherit; font-size: 13px; min-width: 0; flex: 1; background: #fff;
    }
    .assign button {
        height: 36px; border: 0; border-radius: 10px; background: var(--cth-green); color: #fff;
        font-weight: 700; padding: 0 14px; cursor: pointer; font-size: 12px; white-space: nowrap;
    }

    .team { display: flex; align-items: center; gap: 10px; padding: 12px 0; border-bottom: 1px solid #f1f5f9; }
    .team:last-child { border-bottom: 0; }
    .team .ava { width: 32px; height: 32px; border-radius: 50%; background: #f0fdf4; color: var(--cth-green-text); display: grid; place-items: center; font-size: 11px; font-weight: 700; border: 1px solid #bbf7d0; }
    .team b { display: block; font-size: 13px; color: #0f172a; }
    .team small { color: #64748b; font-size: 11px; }
    .team .n { margin-left: auto; text-align: right; font-size: 12px; color: #64748b; font-weight: 600; }
    .team .n em { display: block; font-style: normal; color: #2563eb; font-size: 11px; }

    .links a { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; color: #334155; text-decoration: none; font-size: 13px; border-bottom: 1px solid #f1f5f9; }
    .links a:last-child { border-bottom: 0; }
    .links a span { display: flex; align-items: center; gap: 10px; }
    .links a i.left { color: var(--cth-green); width: 14px; }
    .links a i.right { color: #cbd5e1; font-size: 11px; }
    .empty { color: #94a3b8; font-size: 12px; padding: 8px 0; text-align: center; }

    @media (max-width: 1100px) { .pipeline { grid-template-columns: 1fr 1fr; } .grid { grid-template-columns: 1fr; } }
    @media (max-width: 640px) { .pipeline { grid-template-columns: 1fr; } }
</style>
@endsection

@section('content')
    @php $solicitudesPendientes = $solicitudesPendientes ?? collect(); @endphp
    @if($solicitudesPendientes->count() > 0)
        <div class="verdict-banner" style="background:#fff7ed;border-color:#fed7aa;margin-bottom:24px;">
            <div>
                <b style="color:#9a3412;">{{ $solicitudesPendientes->count() }} solicitud{{ $solicitudesPendientes->count() === 1 ? '' : 'es' }} de permiso</b>
                <span style="color:#c2410c;">{{ $solicitudesPendientes->first()->user->name }} pide {{ $solicitudesPendientes->first()->etiquetaPermiso() }}.</span>
            </div>
            <a href="{{ route('coordinadora.solicitudes') }}" style="background:#c2410c;color:#fff;border-radius:10px;padding:10px 16px;font-weight:700;text-decoration:none;">Revisar</a>
        </div>
    @endif

    <div class="section-head">
        <h3>Estado de los expedientes</h3>
        <a href="{{ route('abogado.consultarproceso') }}">Ver todos &rarr;</a>
    </div>

    <section class="pipeline">
        <a class="pipe" href="{{ route('abogado.consultarproceso', ['estado' => 'Pendiente']) }}">
            <div class="pipe-top">
                <div class="pipe-ico y"><i class="far fa-hourglass"></i></div>
                <div class="pipe-num">{{ $pendientes }}</div>
            </div>
            <div class="pipe-title">Pendiente</div>
            <div class="bar yellow"><span style="width: {{ $pctPendiente }}%"></span></div>
            <small>{{ $pctPendiente }}% del total</small>
        </a>
        <a class="pipe" href="{{ route('abogado.consultarproceso', ['estado' => 'En Proceso']) }}">
            <div class="pipe-top">
                <div class="pipe-ico b"><i class="fas fa-gavel"></i></div>
                <div class="pipe-num">{{ $enProceso }}</div>
            </div>
            <div class="pipe-title">Pendiente de veredicto</div>
            <div class="bar blue"><span style="width: {{ $pctProceso }}%"></span></div>
            <small>{{ $pctProceso }}% del total</small>
        </a>
        <a class="pipe" href="{{ route('abogado.consultarproceso', ['estado' => 'Sancionado']) }}">
            <div class="pipe-top">
                <div class="pipe-ico r"><i class="far fa-times-circle"></i></div>
                <div class="pipe-num">{{ $sancionados }}</div>
            </div>
            <div class="pipe-title">Sancionado</div>
            <div class="bar rose"><span style="width: {{ $pctSancionado }}%"></span></div>
            <small>{{ $pctSancionado }}% del total</small>
        </a>
        <a class="pipe" href="{{ route('abogado.consultarproceso', ['estado' => 'Archivado']) }}">
            <div class="pipe-top">
                <div class="pipe-ico g"><i class="far fa-folder"></i></div>
                <div class="pipe-num">{{ $archivados }}</div>
            </div>
            <div class="pipe-title">Archivado</div>
            <div class="bar gray"><span style="width: {{ $pctArchivado }}%"></span></div>
            <small>{{ $pctArchivado }}% del total</small>
        </a>
    </section>

    <div class="grid">
        <div>
            <section class="card">
                <div class="card-title">
                    <span>Pendientes de veredicto</span>
                    @if($enProceso > 0)
                        <span class="chip">{{ $enProceso }}</span>
                    @endif
                    <a href="{{ route('coordinadora.veredictos') }}">Ver cola &rarr;</a>
                </div>

                @forelse($pendientesVeredicto as $proceso)
                    <a class="row-item" href="{{ route('abogado.detalleproceso', $proceso->id) }}">
                        <span class="dot blue">{{ str_pad($proceso->id, 3, '0', STR_PAD_LEFT) }}</span>
                        <div class="row-main">
                            <b>{{ $proceso->nombre }}</b>
                            <small>{{ $proceso->tipo_falta ?: 'Sin tipificar' }} · {{ $proceso->user->name ?? 'Sin RH' }}</small>
                        </div>
                        <span class="status proc">
                            <i class="fas fa-circle" style="font-size:6px;"></i> En Proceso
                        </span>
                    </a>
                @empty
                    <div class="empty">No hay expedientes esperando veredicto.</div>
                @endforelse
            </section>

            <section class="card">
                <div class="card-title">
                    <span>Sin responsable de RH</span>
                    @if($sinAbogado > 0)
                        <span class="chip" style="background:#fffbeb;color:#d97706;">{{ $sinAbogado }}</span>
                    @endif
                </div>

                @forelse($procesosSinAsignar as $proceso)
                    <div class="row-stack">
                        <div class="row-item" style="padding:0;border:0;">
                            <span class="dot warn">{{ str_pad($proceso->id, 3, '0', STR_PAD_LEFT) }}</span>
                            <div class="row-main">
                                <b>{{ $proceso->nombre }}</b>
                                <small>{{ $proceso->tipo_falta ?: 'Sin tipificar' }} · {{ $proceso->estado }}</small>
                            </div>
                            <a class="meta" href="{{ route('abogado.detalleproceso', $proceso->id) }}">Ver</a>
                        </div>
                        <form class="assign" method="POST" action="{{ route('coordinadora.asignar', $proceso->id) }}">
                            @csrf
                            @method('PUT')
                            <select name="user_id" required>
                                <option value="">Asignar a…</option>
                                @foreach($equipoRh as $rh)
                                    <option value="{{ $rh->id }}">{{ $rh->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit">Asignar</button>
                        </form>
                    </div>
                @empty
                    <div class="empty">Todos los expedientes tienen responsable.</div>
                @endforelse
            </section>
        </div>

        <div>
            <section class="card">
                <div class="card-title">
                    <span>Carga del equipo</span>
                    <a href="{{ route('coordinadora.abogados') }}">Permisos &rarr;</a>
                </div>
                @forelse($cargaAbogados as $abogado)
                    @php
                        $ini = collect(preg_split('/\s+/', trim($abogado->name)))->filter()->take(2)->map(function ($p) { return strtoupper(substr($p, 0, 1)); })->implode('');
                    @endphp
                    <div class="team">
                        <span class="ava">{{ $ini }}</span>
                        <div>
                            <b>{{ $abogado->name }}</b>
                            <small>
                                {{ $abogado->cargo ?: 'Equipo de RH' }}
                                @php
                                    $vigentes = ($abogado->permisos ?? collect())->filter(function ($p) { return $p->estaVigente(); });
                                    $temps = $vigentes->filter(function ($p) { return $p->esTemporal(); })->count();
                                @endphp
                                · {{ $vigentes->count() }} permiso{{ $vigentes->count() === 1 ? '' : 's' }}
                                @if($temps > 0) · {{ $temps }} temporal{{ $temps === 1 ? '' : 'es' }} @endif
                            </small>
                        </div>
                        <div class="n">
                            {{ $abogado->procesos_count }} caso{{ $abogado->procesos_count === 1 ? '' : 's' }}
                            <em>{{ $abogado->procesos_abiertos_count }} abierto{{ $abogado->procesos_abiertos_count === 1 ? '' : 's' }}</em>
                        </div>
                    </div>
                @empty
                    <div class="empty">Sin personal de RH.</div>
                @endforelse
            </section>

            <section class="card">
                <div class="card-title">Accesos de coordinación</div>
                <div class="links">
                    <a href="{{ route('coordinadora.solicitudes') }}">
                        <span><i class="fas fa-key left"></i> Solicitudes de permiso</span>
                        <i class="fas fa-chevron-right right"></i>
                    </a>
                    <a href="{{ route('coordinadora.notificar') }}">
                        <span><i class="far fa-paper-plane left"></i> Avisar al equipo</span>
                        <i class="fas fa-chevron-right right"></i>
                    </a>
                    <a href="{{ route('abogado.consultarproceso') }}">
                        <span><i class="fas fa-search left"></i> Todos los procesos</span>
                        <i class="fas fa-chevron-right right"></i>
                    </a>
                    <a href="{{ route('abogado.reportes') }}">
                        <span><i class="fas fa-chart-bar left"></i> Estadísticas integrales</span>
                        <i class="fas fa-chevron-right right"></i>
                    </a>
                    <a href="{{ route('abogado.plazos') }}">
                        <span><i class="far fa-clock left"></i> Plazos procesales</span>
                        <i class="fas fa-chevron-right right"></i>
                    </a>
                    <a href="{{ route('abogado.reincidencias') }}">
                        <span><i class="fas fa-history left"></i> Reincidencias</span>
                        <i class="fas fa-chevron-right right"></i>
                    </a>
                </div>
            </section>
        </div>
    </div>
@endsection
