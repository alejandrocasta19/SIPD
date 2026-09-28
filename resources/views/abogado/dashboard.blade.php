@extends('layouts.master')

@php
    $pageTitle = 'Inicio';
    $codigo = function ($proceso) {
        return 'PRO-' . str_pad($proceso->id, 3, '0', STR_PAD_LEFT);
    };
    $saludo = $user->role === 'coordinadora' ? 'Bienvenida' : 'Bienvenido';
@endphp

@section('styles')
<style>
    .hero {
        background: #0f3d2e;
        color: #fff;
        border-radius: 22px;
        padding: 28px 32px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        margin-bottom: 28px;
    }

    .hero-kicker {
        font-size: 12px;
        letter-spacing: .12em;
        color: #86efac;
        font-weight: 700;
        margin-bottom: 10px;
    }

    .hero h2 {
        margin: 0 0 10px;
        font-size: 32px;
        font-weight: 700;
        letter-spacing: -.03em;
    }

    .hero p {
        margin: 0;
        color: #d1fae5;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .hero-actions { display: flex; gap: 10px; flex-wrap: wrap; }

    .btn-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border-radius: 999px;
        padding: 11px 18px;
        font-size: 14px;
        font-weight: 700;
        text-decoration: none;
    }

    .btn-pill.green { background: #22c55e; color: #fff; }
    .btn-pill.dark { background: #14532d; color: #fff; }
    .btn-pill:hover { color: #fff; filter: brightness(1.06); }

    .section-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 0 0 14px;
    }

    .section-head h3 {
        margin: 0;
        font-size: 16px;
        font-weight: 700;
    }

    .section-head a {
        color: #16a34a;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
    }

    .pipeline {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 22px;
    }

    .pipe {
        background: #fff;
        border-radius: 18px;
        padding: 18px 18px 16px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
    }

    .pipe-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 18px;
    }

    .pipe-ico {
        width: 36px;
        height: 36px;
        border-radius: 12px;
        background: #f8fafc;
        display: grid;
        place-items: center;
        color: #64748b;
    }

    .pipe-num { font-size: 28px; font-weight: 700; }

    .pipe-title { font-weight: 600; margin-bottom: 10px; }

    .bar {
        height: 4px;
        background: #eef2f7;
        border-radius: 999px;
        overflow: hidden;
        margin-bottom: 8px;
    }

    .bar > span { display: block; height: 100%; border-radius: 999px; }
    .bar.yellow > span { background: #f59e0b; }
    .bar.blue > span { background: #3b82f6; }
    .bar.rose > span { background: #f43f5e; }
    .bar.gray > span { background: #94a3b8; }

    .pipe small { color: #94a3b8; font-size: 12px; }

    .grid {
        display: grid;
        grid-template-columns: minmax(0, 1.7fr) minmax(280px, .85fr);
        gap: 16px;
        align-items: start;
    }

    .card {
        background: #fff;
        border-radius: 18px;
        padding: 18px 18px 16px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
        margin-bottom: 16px;
    }

    .card.tint { background: #fffbeb; }

    .card-title {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 14px;
        font-weight: 700;
    }

    .chip {
        background: #fff1f2;
        color: #e11d48;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        padding: 4px 10px;
    }

    .alert {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        border-radius: 14px;
        padding: 14px 16px;
        margin-bottom: 10px;
    }

    .alert:last-child { margin-bottom: 0; }
    .alert b { display: block; font-size: 14px; }
    .alert span { color: #64748b; font-size: 13px; }
    .alert a { color: #334155; font-size: 13px; font-weight: 700; text-decoration: none; }
    .alert.pink { background: #fff1f2; }
    .alert.yellow { background: #fffbeb; }
    .alert.green { background: #f0fdf4; }

    .row-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid #f1f5f9;
        text-decoration: none;
        color: inherit;
    }

    .card.tint .row-item { border-bottom-color: #fde68a; }

    .row-item:last-child { border-bottom: 0; padding-bottom: 0; }

    .dot {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        font-size: 11px;
        font-weight: 700;
        flex-shrink: 0;
    }

    .dot.soft { background: #fff; border: 1px solid #e2e8f0; color: #94a3b8; }
    .dot.ok { background: #dcfce7; color: #166534; }

    .row-main { flex: 1; min-width: 0; }
    .row-main b { display: block; font-size: 14px; }
    .row-main small { color: #94a3b8; font-size: 12px; }
    .code { color: #94a3b8; font-size: 12px; margin-right: 6px; }

    .status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #64748b;
        font-size: 13px;
        white-space: nowrap;
    }

    .status i { font-size: 8px; }
    .status.pend i { color: #f59e0b; }
    .status.proc i { color: #3b82f6; }
    .status.sanc i { color: #f43f5e; }
    .status.arch i { color: #94a3b8; }

    .meta { color: #94a3b8; font-size: 13px; margin-right: 10px; white-space: nowrap; }

    .cta {
        display: block;
        text-align: center;
        background: #fbbf24;
        color: #0f172a;
        border-radius: 12px;
        padding: 12px;
        font-weight: 700;
        text-decoration: none;
        margin-top: 8px;
    }

    .cta:hover { color: #0f172a; }

    .stat {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        color: #475569;
        font-size: 14px;
    }

    .stat b { color: #0f172a; }

    .team {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 0;
    }

    .team .ava {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: #ecfdf5;
        color: #166534;
        display: grid;
        place-items: center;
        font-size: 11px;
        font-weight: 700;
    }

    .team b { display: block; font-size: 14px; }
    .team small { color: #94a3b8; font-size: 12px; }
    .team .n { margin-left: auto; font-size: 13px; color: #64748b; }

    .links a {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        color: #334155;
        text-decoration: none;
        font-size: 14px;
        border-bottom: 1px solid #f1f5f9;
    }

    .links a:last-child { border-bottom: 0; }
    .links a i.left { color: #22c55e; width: 18px; }
    .links a span { display: flex; align-items: center; gap: 10px; }

    .empty { color: #94a3b8; font-size: 13px; padding: 8px 0; }

    @media (max-width: 980px) {
        .pipeline, .grid { grid-template-columns: 1fr; }
        .hero { flex-direction: column; align-items: flex-start; }
    }
</style>
@endsection

@section('content')
    <section class="hero">
        <div>
            <div class="hero-kicker">PANEL DE GESTIÓN</div>
            <h2>{{ $saludo }}, {{ $user->name }} 👋</h2>
            <p>
                <i class="fas fa-exclamation-triangle"></i>
                @if($sinAbogado > 0)
                    {{ $sinAbogado }} proceso{{ $sinAbogado === 1 ? '' : 's' }} sin RH asignado — acción requerida.
                @else
                    No hay procesos sin RH asignado.
                @endif
            </p>
        </div>
        <div class="hero-actions">
            <a class="btn-pill green" href="{{ route('abogado.registro') }}">
                <i class="fas fa-plus"></i> Registrar proceso
            </a>
            @if($user->role === 'coordinadora')
                <a class="btn-pill dark" href="{{ route('coordinadora.abogados') }}">
                    <i class="fas fa-users"></i> Equipo
                </a>
            @else
                <a class="btn-pill dark" href="{{ route('abogado.consultarproceso') }}">
                    <i class="fas fa-folder-open"></i> Procesos
                </a>
            @endif
        </div>
    </section>

    <div class="section-head">
        <h3>Pipeline de procesos</h3>
        <a href="{{ route('abogado.consultarproceso') }}">Ver todos →</a>
    </div>

    <section class="pipeline">
        <article class="pipe">
            <div class="pipe-top">
                <div class="pipe-ico"><i class="far fa-hourglass"></i></div>
                <div class="pipe-num">{{ $pendientes }}</div>
            </div>
            <div class="pipe-title">Pendiente</div>
            <div class="bar yellow"><span style="width: {{ $pctPendiente }}%"></span></div>
            <small>{{ $pctPendiente }}% del total</small>
        </article>
        <article class="pipe">
            <div class="pipe-top">
                <div class="pipe-ico"><i class="fas fa-spinner"></i></div>
                <div class="pipe-num">{{ $enProceso }}</div>
            </div>
            <div class="pipe-title">En proceso</div>
            <div class="bar blue"><span style="width: {{ $pctProceso }}%"></span></div>
            <small>{{ $pctProceso }}% del total</small>
        </article>
        <article class="pipe">
            <div class="pipe-top">
                <div class="pipe-ico"><i class="far fa-times-circle"></i></div>
                <div class="pipe-num">{{ $sancionados }}</div>
            </div>
            <div class="pipe-title">Sancionado</div>
            <div class="bar rose"><span style="width: {{ $pctSancionado }}%"></span></div>
            <small>{{ $pctSancionado }}% del total</small>
        </article>
        <article class="pipe">
            <div class="pipe-top">
                <div class="pipe-ico"><i class="far fa-folder"></i></div>
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
                    <span><i class="far fa-bell"></i> Alertas del sistema</span>
                    <span class="chip">{{ $alertasActivas }} activa{{ $alertasActivas === 1 ? '' : 's' }}</span>
                </div>

                <div class="alert pink">
                    <div>
                        <b><i class="far fa-clock"></i> Vencimiento próximo</b>
                        <span>
                            @if($proximoVencer)
                                {{ $codigo($proximoVencer) }}
                                @if($diasVencer !== null && $diasVencer >= 0)
                                    vence en {{ $diasVencer }} día{{ $diasVencer === 1 ? '' : 's' }}
                                @else
                                    plazo vencido
                                @endif
                            @else
                                No hay vencimientos próximos
                            @endif
                        </span>
                    </div>
                    <a href="{{ route('abogado.plazos') }}">Revisar</a>
                </div>

                <div class="alert yellow">
                    <div>
                        <b><i class="far fa-user"></i> Sin RH</b>
                        <span>
                            @if($procesosSinAsignar->isNotEmpty())
                                {{ $procesosSinAsignar->map($codigo)->implode(', ') }} sin asignar
                            @else
                                Todos los procesos tienen responsable
                            @endif
                        </span>
                    </div>
                    <a href="{{ route('abogado.consultarproceso') }}">Revisar</a>
                </div>

                <div class="alert green">
                    <div>
                        <b><i class="far fa-file-alt"></i> Descargos pendientes</b>
                        <span>
                            @if($alertaDescargos)
                                {{ $codigo($alertaDescargos) }} espera respuesta del conductor
                            @else
                                No hay descargos pendientes
                            @endif
                        </span>
                    </div>
                    <a href="{{ route('abogado.consultarproceso') }}">Revisar</a>
                </div>
            </section>

            <section class="card tint">
                <div class="card-title">Procesos sin RH asignado</div>

                @forelse($procesosSinAsignar as $proceso)
                    <a class="row-item" href="{{ route('abogado.detalleproceso', $proceso->id) }}">
                        <span class="dot soft">{{ str_pad($proceso->id, 3, '0', STR_PAD_LEFT) }}</span>
                        <div class="row-main">
                            <b><span class="code">{{ $codigo($proceso) }}</span> {{ $proceso->nombre }}</b>
                            <small>{{ $proceso->tipo_falta ?: 'Sin tipo de falta' }}</small>
                        </div>
                        <span class="status pend"><i class="fas fa-circle"></i> {{ $proceso->estado }}</span>
                    </a>
                @empty
                    <div class="empty">No hay procesos sin RH.</div>
                @endforelse

                @if($user->role === 'coordinadora')
                    <a class="cta" href="{{ route('coordinadora.abogados') }}">Ir a gestión de RH →</a>
                @endif
            </section>

            <section class="card">
                <div class="card-title">
                    <span>Procesos recientes</span>
                    <a href="{{ route('abogado.consultarproceso') }}" style="color:#16a34a;font-size:14px;font-weight:600;text-decoration:none;">Ver todos →</a>
                </div>

                @forelse($recientes as $proceso)
                    <a class="row-item" href="{{ route('abogado.detalleproceso', $proceso->id) }}">
                        <span class="dot ok">{{ str_pad($proceso->id, 3, '0', STR_PAD_LEFT) }}</span>
                        <div class="row-main">
                            <b>{{ $proceso->nombre }}</b>
                            <small>{{ $proceso->tipo_falta ?: 'Sin tipo de falta' }}</small>
                        </div>
                        <span class="meta">{{ $proceso->user->name ?? 'Sin asignar' }}</span>
                        <span class="status
                            @if($proceso->estado == 'Pendiente') pend
                            @elseif($proceso->estado == 'En Proceso') proc
                            @elseif($proceso->estado == 'Sancionado') sanc
                            @else arch
                            @endif">
                            <i class="fas fa-circle"></i> {{ $proceso->estado }}
                        </span>
                    </a>
                @empty
                    <div class="empty">Aún no hay procesos registrados.</div>
                @endforelse
            </section>
        </div>

        <div>
            <section class="card">
                <div class="card-title">Resumen del sistema</div>
                <div class="stat"><span><i class="far fa-file-alt"></i> Total registrados</span><b>{{ $total }}</b></div>
                <div class="stat"><span><i class="fas fa-exclamation-triangle"></i> Sin RH</span><b>{{ $sinAbogado }}</b></div>
                <div class="stat"><span><i class="far fa-check-circle"></i> Tasa resolución</span><b>{{ $tasaResolucion }}%</b></div>
            </section>

            <section class="card">
                <div class="card-title">
                    <span>Carga del equipo</span>
                    @if($user->role === 'coordinadora')
                        <a href="{{ route('coordinadora.abogados') }}" style="color:#16a34a;font-size:13px;font-weight:600;text-decoration:none;">Gestionar</a>
                    @endif
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
                        <div class="n">{{ $abogado->procesos_count }} caso{{ $abogado->procesos_count === 1 ? '' : 's' }}</div>
                    </div>
                @empty
                    <div class="empty">No hay personal de RH registrado.</div>
                @endforelse
            </section>

            <section class="card">
                <div class="card-title">Accesos directos</div>
                <div class="links">
                    <a href="{{ route('abogado.estadistica') }}">
                        <span><i class="fas fa-chart-bar left"></i> Estadísticas del sistema</span>
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <a href="{{ route('abogado.plazos') }}">
                        <span><i class="far fa-clock left"></i> Plazos y vencimientos</span>
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <a href="{{ route('abogado.partes') }}">
                        <span><i class="fas fa-user-friends left"></i> Partes involucradas</span>
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <a href="{{ route('abogado.resoluciones') }}">
                        <span><i class="far fa-file-alt left"></i> Resoluciones emitidas</span>
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </section>
        </div>
    </div>
@endsection
