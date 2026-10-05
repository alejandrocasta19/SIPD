@extends('layouts.master')

@php
    $pageTitle = 'Coordinación de RH';
    $saludo = $saludo ?? 'Bienvenida';
    $solicitudesPendientes = $solicitudesPendientes ?? collect();
    $anexosTotal = $anexosTotal ?? 0;
    $plazosVencidos = $plazosVencidos ?? 0;
    $plazosPorVencer = $plazosPorVencer ?? 0;
    $abiertos = $abiertos ?? ($pendientes + $enProceso);
    $veredictos = $veredictos ?? 0;
    $nSolicitudes = $solicitudesPendientes->count();
@endphp

@section('page-header')
    <div class="proc-head">
        <div>
            <h1>Coordinación de RH</h1>
            <p>
                {{ $saludo }}, {{ $user->primerNombre() }}.
                @if($enProceso > 0)
                    {{ $enProceso }} pendiente{{ $enProceso === 1 ? '' : 's' }} de veredicto.
                @else
                    Supervisión de expedientes, equipo y veredictos.
                @endif
            </p>
        </div>
        <div class="proc-head-side">
            <span class="stat-chip">{{ $total }} total</span>
            <span class="stat-chip green">{{ $tasaResolucion }}% resolución</span>
            @if($enProceso > 0)<span class="stat-chip">{{ $enProceso }} veredicto</span>@endif
            @if($plazosVencidos > 0)<span class="stat-chip red">{{ $plazosVencidos }} vencidos</span>@endif
        </div>
    </div>
@endsection

@section('content')
    @if($nSolicitudes > 0)
        <div class="verdict-banner">
            <div>
                <b>{{ $nSolicitudes }} solicitud{{ $nSolicitudes === 1 ? '' : 'es' }} de permiso</b>
                <span>{{ $solicitudesPendientes->pluck('user.name')->join(', ') }} pide{{ $nSolicitudes > 1 ? 'n' : '' }} {{ $nSolicitudes === 1 ? $solicitudesPendientes->first()->etiquetaPermiso() : 'permisos pendientes de respuesta' }}.</span>
            </div>
            <a href="{{ route('coordinadora.solicitudes') }}">Revisar solicitudes</a>
        </div>
    @endif

    <section class="dash-pipeline">
        <a class="dash-pipe" href="{{ route('abogado.consultarproceso', ['estado' => 'Pendiente']) }}">
            <div class="dash-pipe-top">
                <div class="dash-pipe-ico y"><i class="far fa-hourglass"></i></div>
                <div class="dash-pipe-num">{{ $pendientes }}</div>
            </div>
            <div class="dash-pipe-title">Pendiente</div>
            <div class="dash-bar yellow"><span style="width: {{ $pctPendiente }}%"></span></div>
            <small>{{ $pctPendiente }}% del total</small>
        </a>
        <a class="dash-pipe" href="{{ route('abogado.consultarproceso', ['estado' => 'En Proceso']) }}">
            <div class="dash-pipe-top">
                <div class="dash-pipe-ico b"><i class="fas fa-gavel"></i></div>
                <div class="dash-pipe-num">{{ $enProceso }}</div>
            </div>
            <div class="dash-pipe-title">Pendiente de veredicto</div>
            <div class="dash-bar blue"><span style="width: {{ $pctProceso }}%"></span></div>
            <small>{{ $pctProceso }}% del total</small>
        </a>
        <a class="dash-pipe" href="{{ route('abogado.consultarproceso', ['estado' => 'Sancionado']) }}">
            <div class="dash-pipe-top">
                <div class="dash-pipe-ico r"><i class="far fa-times-circle"></i></div>
                <div class="dash-pipe-num">{{ $sancionados }}</div>
            </div>
            <div class="dash-pipe-title">Sancionado</div>
            <div class="dash-bar rose"><span style="width: {{ $pctSancionado }}%"></span></div>
            <small>{{ $pctSancionado }}% del total</small>
        </a>
        <a class="dash-pipe" href="{{ route('abogado.consultarproceso', ['estado' => 'Archivado']) }}">
            <div class="dash-pipe-top">
                <div class="dash-pipe-ico g"><i class="far fa-folder"></i></div>
                <div class="dash-pipe-num">{{ $archivados }}</div>
            </div>
            <div class="dash-pipe-title">Archivado</div>
            <div class="dash-bar gray"><span style="width: {{ $pctArchivado }}%"></span></div>
            <small>{{ $pctArchivado }}% del total</small>
        </a>
    </section>

    <div class="dash-grid">
        <div class="dash-col">
            <section class="dash-card">
                <div class="dash-card-title">
                    <span>Pendientes de veredicto</span>
                    @if($enProceso > 0)
                        <span class="dash-chip blue">{{ $enProceso }}</span>
                    @endif
                    <a href="{{ route('coordinadora.veredictos') }}">Ver cola &rarr;</a>
                </div>

                @forelse($pendientesVeredicto as $proceso)
                    <a class="dash-row" href="{{ route('abogado.detalleproceso', $proceso->id) }}">
                        <span class="dash-dot blue">{{ str_pad($proceso->id, 3, '0', STR_PAD_LEFT) }}</span>
                        <div class="dash-main">
                            <b>{{ $proceso->nombre }}</b>
                            <small>{{ \App\Support\Modalidades::etiquetaCaso($proceso->modalidad, $proceso->cargo) }} · {{ $proceso->tipo_falta ?: 'Sin tipificar' }} · {{ $proceso->user->name ?? 'Sin RH' }}</small>
                        </div>
                        <span class="dash-status proc">
                            <i class="fas fa-circle" style="font-size:6px;"></i> En Proceso
                        </span>
                    </a>
                @empty
                    <div class="dash-empty">No hay expedientes esperando veredicto.</div>
                @endforelse
            </section>
        </div>

        <div class="dash-col">
            <section class="dash-card">
                <div class="dash-card-title">Hoy</div>
                <div class="dash-hoy">
                    <a href="{{ route('abogado.plazos', ['estado' => 'vencido']) }}">
                        <span>Plazos vencidos</span>
                        <b class="{{ $plazosVencidos > 0 ? 'n-late' : '' }}">{{ $plazosVencidos }}</b>
                    </a>
                    <a href="{{ route('abogado.plazos', ['estado' => 'por_vencer']) }}">
                        <span>Por vencer</span>
                        <b class="{{ $plazosPorVencer > 0 ? 'n-warn' : '' }}">{{ $plazosPorVencer }}</b>
                    </a>
                    <a href="{{ route('coordinadora.veredictos') }}">
                        <span>Veredictos</span>
                        <b>{{ $enProceso }}</b>
                    </a>
                    <a href="{{ route('abogado.anexos') }}">
                        <span>Anexos</span>
                        <b>{{ $anexosTotal }}</b>
                    </a>
                </div>
                <div class="dash-links">
                    <a href="{{ route('coordinadora.solicitudes') }}">
                        <span><i class="fas fa-key left"></i> Solicitudes de permiso</span>
                        @if($nSolicitudes > 0)<em class="count">{{ $nSolicitudes }}</em>@endif
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
                        @if(($plazosVencidos + $plazosPorVencer) > 0)<em class="count">{{ $plazosVencidos + $plazosPorVencer }}</em>@endif
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

    <section class="dash-card dash-card-wide">
        <div class="dash-card-title">
            <span>Carga del equipo</span>
            <a href="{{ route('coordinadora.abogados') }}">Permisos &rarr;</a>
        </div>
        <div class="dash-team-grid">
            @forelse($cargaAbogados as $abogado)
                <div class="dash-team {{ $abogado->estaActivo() ? '' : 'is-inactive' }}">
                    <span class="dash-ava">{{ $abogado->inicialesCortas() }}</span>
                    <div>
                        <b>{{ $abogado->name }}</b>
                        <small>
                            {{ $abogado->cargo ?: 'Equipo de RH' }}
                            @if(!$abogado->estaActivo())
                                · Inactivo
                            @else
                                @php
                                    $vigentes = ($abogado->permisos ?? collect())->filter(function ($p) { return $p->estaVigente(); });
                                    $temps = $vigentes->filter(function ($p) { return $p->esTemporal(); })->count();
                                @endphp
                                · {{ $vigentes->count() }} permiso{{ $vigentes->count() === 1 ? '' : 's' }}
                                @if($temps > 0) · {{ $temps }} temporal{{ $temps === 1 ? '' : 'es' }} @endif
                            @endif
                        </small>
                    </div>
                    <div class="n">
                        {{ $abogado->procesos_count }} caso{{ $abogado->procesos_count === 1 ? '' : 's' }}
                        <em>{{ $abogado->procesos_abiertos_count }} abierto{{ $abogado->procesos_abiertos_count === 1 ? '' : 's' }}</em>
                    </div>
                </div>
            @empty
                <div class="dash-empty">Sin personal de RH.</div>
            @endforelse
        </div>
    </section>
@endsection
