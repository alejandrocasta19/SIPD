@extends('layouts.master')

@php
    $pageTitle = 'Inicio';
    $codigo = function ($proceso) {
        return 'PRO-' . str_pad($proceso->id, 3, '0', STR_PAD_LEFT);
    };
    $saludo = 'Bienvenido';
@endphp

@section('page-header')
    <div class="proc-head">
        <div>
            <h1>{{ $saludo }}, {{ $user->name }}</h1>
            <p>
                @if($sinAbogado > 0)
                    {{ $sinAbogado }} proceso{{ $sinAbogado === 1 ? '' : 's' }} sin RH asignado.
                @else
                    Panel de gestión de procesos disciplinarios.
                @endif
            </p>
        </div>
        <div class="proc-head-side">
            <span class="stat-chip">{{ $total }} total</span>
            <span class="stat-chip green">{{ $tasaResolucion }}% resolución</span>
            @if($sinAbogado > 0)<span class="stat-chip yellow">{{ $sinAbogado }} sin RH</span>@endif
            @if(auth()->user()->puede('registrar_casos'))
            <a class="btn-add" href="{{ route('abogado.registro') }}"><i class="fas fa-plus"></i> Registrar proceso</a>
            @endif
            <a class="btn-alt" href="{{ route('abogado.mis-casos') }}"><i class="fas fa-folder-open"></i> Mis casos</a>
        </div>
    </div>
@endsection

@section('styles')
<style>
    .section-head { display: flex; justify-content: space-between; align-items: center; margin: 0 0 16px; }
    .section-head h3 { margin: 0; font-size: 16px; font-weight: 700; color: #0f172a; }
    .section-head a { color: var(--cth-green); font-size: 13px; font-weight: 600; text-decoration: none; }

    .pipeline { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin-bottom: 32px; }
    .pipe { background: #fff; border: 1px solid #e2e8f0; border-radius: 18px; padding: 20px; box-shadow: 0 1px 2px rgba(15,23,42,.04); }
    .pipe-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
    
    .pipe-ico { 
        width: 36px; height: 36px; border-radius: 10px; display: grid; place-items: center; font-size: 14px; 
    }
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
    
    .chip { background: #fff1f2; color: #e11d48; border-radius: 8px; font-size: 11px; font-weight: 700; padding: 4px 10px; }

    .alert { display: flex; justify-content: space-between; align-items: center; gap: 12px; border-radius: 12px; padding: 12px 16px; margin-bottom: 12px; }
    .alert:last-child { margin-bottom: 0; }
    .alert b { display: block; font-size: 13px; color: #0f172a; font-weight: 600; margin-bottom: 2px; }
    .alert span { color: #475569; font-size: 12px; }
    .alert a { color: var(--cth-green); font-size: 12px; font-weight: 700; text-decoration: none; }
    
    .alert.pink { background: #fff1f2; }
    .alert.yellow { background: #fffbeb; }
    .alert.green { background: #f0fdf4; }

    .row-item { display: flex; align-items: center; gap: 14px; padding: 14px 0; border-bottom: 1px solid #f1f5f9; text-decoration: none; transition: background .2s; }
    .row-item:hover { background: #fafbfc; }
    .row-item:last-child { border-bottom: 0; }
    
    .dot { width: 36px; height: 36px; border-radius: 10px; display: grid; place-items: center; font-size: 12px; font-weight: 700; flex-shrink: 0; }
    .dot.soft { background: #f8fafc; border: 1px solid #e2e8f0; color: #64748b; }
    .dot.ok { background: #f0fdf4; color: var(--cth-green-text); border: 1px solid #bbf7d0; }
    
    .row-main { flex: 1; min-width: 0; }
    .row-main b { display: block; font-size: 14px; font-weight: 600; color: #0f172a; margin-bottom: 2px; }
    .row-main b .code { color: var(--cth-green); font-size: 11px; margin-right: 6px; }
    .row-main small { color: #64748b; font-size: 12px; }
    
    .status { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 8px; white-space: nowrap; }
    .status.pend { background: #fffbeb; color: #d97706; }
    .status.proc { background: #eff6ff; color: #2563eb; }
    .status.sanc { background: #fff1f2; color: #e11d48; }
    .status.arch { background: #f8fafc; color: #475569; }
    
    .meta { color: #94a3b8; font-size: 12px; margin-right: 12px; }

    .stat { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; color: #475569; font-size: 13px; border-bottom: 1px solid #f1f5f9; }
    .stat:last-child { border-bottom: 0; }
    .stat b { color: #0f172a; }

    .team { display: flex; align-items: center; gap: 10px; padding: 12px 0; border-bottom: 1px solid #f1f5f9; }
    .team:last-child { border-bottom: 0; }
    .team .ava { width: 32px; height: 32px; border-radius: 50%; background: #f0fdf4; color: var(--cth-green-text); display: grid; place-items: center; font-size: 11px; font-weight: 700; border: 1px solid #bbf7d0; }
    .team b { display: block; font-size: 13px; color: #0f172a; }
    .team small { color: #64748b; font-size: 11px; }
    .team .n { margin-left: auto; font-size: 12px; color: #64748b; font-weight: 600; }

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

    <div class="section-head">
        <h3>Pipeline de procesos</h3>
        <a href="{{ route('abogado.mis-casos') }}">Ver todos &rarr;</a>
    </div>

    <section class="pipeline">
        <article class="pipe">
            <div class="pipe-top">
                <div class="pipe-ico y"><i class="far fa-hourglass"></i></div>
                <div class="pipe-num">{{ $pendientes }}</div>
            </div>
            <div class="pipe-title">Pendiente</div>
            <div class="bar yellow"><span style="width: {{ $pctPendiente }}%"></span></div>
            <small>{{ $pctPendiente }}% del total</small>
        </article>
        <article class="pipe">
            <div class="pipe-top">
                <div class="pipe-ico b"><i class="fas fa-spinner"></i></div>
                <div class="pipe-num">{{ $enProceso }}</div>
            </div>
            <div class="pipe-title">En proceso</div>
            <div class="bar blue"><span style="width: {{ $pctProceso }}%"></span></div>
            <small>{{ $pctProceso }}% del total</small>
        </article>
        <article class="pipe">
            <div class="pipe-top">
                <div class="pipe-ico r"><i class="far fa-times-circle"></i></div>
                <div class="pipe-num">{{ $sancionados }}</div>
            </div>
            <div class="pipe-title">Sancionado</div>
            <div class="bar rose"><span style="width: {{ $pctSancionado }}%"></span></div>
            <small>{{ $pctSancionado }}% del total</small>
        </article>
        <article class="pipe">
            <div class="pipe-top">
                <div class="pipe-ico g"><i class="far fa-folder"></i></div>
                <div class="pipe-num">{{ $archivados }}</div>
            </div>
            <div class="pipe-title">Archivado</div>
            <div class="bar gray"><span style="width: {{ $pctArchivado }}%"></span></div>
            <small>{{ $pctArchivado }}% del total</small>
        </article>
    </section>

    <div class="grid">
        <div>
            <section class="card">
                <div class="card-title">
                    <span><i class="far fa-bell" style="color:#94a3b8;margin-right:6px;"></i> Alertas del sistema</span>
                    @if($alertasActivas > 0)
                        <span class="chip">{{ $alertasActivas }} ACTIVA{{ $alertasActivas === 1 ? '' : 'S' }}</span>
                    @endif
                </div>

                <div class="alert pink">
                    <div>
                        <b><i class="far fa-clock"></i> Vencimiento próximo</b>
                        <span>
                            @if($proximoVencer)
                                {{ $codigo($proximoVencer) }}
                                @if($diasVencer !== null && $diasVencer > 0)
                                    vence en {{ $diasVencer }} día{{ $diasVencer === 1 ? '' : 's' }}
                                @else
                                    plazo vencido
                                @endif
                            @else
                                Ninguno
                            @endif
                        </span>
                    </div>
                    <a href="{{ route('abogado.plazos') }}">Ver &rarr;</a>
                </div>

                <div class="alert yellow">
                    <div>
                        <b><i class="far fa-user"></i> Sin responsable (RH)</b>
                        <span>
                            @if($procesosSinAsignar->isNotEmpty())
                                {{ $procesosSinAsignar->map($codigo)->implode(', ') }}
                            @else
                                Todos asignados
                            @endif
                        </span>
                    </div>
                    <a href="{{ route('abogado.consultarproceso') }}">Ver &rarr;</a>
                </div>

                <div class="alert green">
                    <div>
                        <b><i class="far fa-file-alt"></i> Descargos pendientes</b>
                        <span>
                            @if($alertaDescargos)
                                {{ $codigo($alertaDescargos) }}
                            @else
                                Todo al día
                            @endif
                        </span>
                    </div>
                    <a href="{{ route('abogado.consultarproceso') }}">Ver &rarr;</a>
                </div>
            </section>

            <section class="card">
                <div class="card-title">
                    <span>Procesos recientes</span>
                    <a href="{{ route('abogado.mis-casos') }}">Ver tabla &rarr;</a>
                </div>

                @forelse($recientes as $proceso)
                    <a class="row-item" href="{{ route('abogado.detalleproceso', $proceso->id) }}">
                        <span class="dot ok">{{ str_pad($proceso->id, 3, '0', STR_PAD_LEFT) }}</span>
                        <div class="row-main">
                            <b>{{ $proceso->nombre }}</b>
                            <small>{{ $proceso->tipo_falta ?: 'Pendiente tipificar' }}</small>
                        </div>
                        <span class="meta">{{ $proceso->user->name ?? '—' }}</span>
                        <span class="status
                            @if($proceso->estado == 'Pendiente') pend
                            @elseif($proceso->estado == 'En Proceso') proc
                            @elseif($proceso->estado == 'Sancionado') sanc
                            @else arch
                            @endif">
                            <i class="fas fa-circle" style="font-size:6px;"></i> {{ $proceso->estado }}
                        </span>
                    </a>
                @empty
                    <div class="empty">Aún no hay procesos registrados.</div>
                @endforelse
            </section>
        </div>

        <div>
            <section class="card">
                <div class="card-title">Resumen</div>
                <div class="stat"><span><i class="far fa-file-alt"></i> Total registrados</span><b>{{ $total }}</b></div>
                <div class="stat"><span><i class="fas fa-exclamation-triangle"></i> Sin asignar</span><b>{{ $sinAbogado }}</b></div>
                <div class="stat"><span><i class="far fa-check-circle"></i> Resolución</span><b>{{ $tasaResolucion }}%</b></div>
            </section>

            <section class="card">
                <div class="card-title">
                    <span>Equipo</span>
                </div>
                @forelse($cargaAbogados as $abogado)
                    @php
                        $ini = collect(preg_split('/\s+/', trim($abogado->name)))->filter()->take(2)->map(function ($p) { return strtoupper(substr($p, 0, 1)); })->implode('');
                    @endphp
                    <div class="team">
                        <span class="ava">{{ $ini }}</span>
                        <div>
                            <b>{{ $abogado->name }}</b>
                            <small>{{ $abogado->cargo ?: 'RH' }}</small>
                        </div>
                        <div class="n">{{ $abogado->procesos_count }}</div>
                    </div>
                @empty
                    <div class="empty">Sin personal de RH.</div>
                @endforelse
            </section>

            <section class="card">
                <div class="card-title">Mapeo de accesos</div>
                <div class="links">
                    <a href="{{ route('abogado.reportes') }}">
                        <span><i class="fas fa-chart-bar left"></i> Estadísticas integrales</span>
                        <i class="fas fa-chevron-right right"></i>
                    </a>
                    <a href="{{ route('abogado.plazos') }}">
                        <span><i class="far fa-clock left"></i> Plazos procesales</span>
                        <i class="fas fa-chevron-right right"></i>
                    </a>
                    <a href="{{ route('abogado.anexos') }}">
                        <span><i class="fas fa-file-upload left"></i> Anexos escaneados</span>
                        <i class="fas fa-chevron-right right"></i>
                    </a>
                    <a href="{{ route('abogado.resoluciones') }}">
                        <span><i class="far fa-file-alt left"></i> Archivo de resoluciones</span>
                        <i class="fas fa-chevron-right right"></i>
                    </a>
                </div>
            </section>
        </div>
    </div>
@endsection
