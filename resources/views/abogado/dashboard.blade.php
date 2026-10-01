@extends('layouts.master')

@php
    $pageTitle = 'Inicio';
    $saludo = $saludo ?? 'Bienvenido';
    $atencion = $atencion ?? collect();
    $anexosTotal = $anexosTotal ?? 0;
    $plazosVencidos = $plazosVencidos ?? 0;
    $plazosPorVencer = $plazosPorVencer ?? 0;
    $descargosPendientes = $descargosPendientes ?? 0;
    $abiertos = $abiertos ?? ($pendientes + $enProceso);
    $estadoClass = function ($estado) {
        return match ($estado) {
            'Pendiente' => 'pend',
            'En Proceso' => 'proc',
            'Sancionado' => 'sanc',
            default => 'arch',
        };
    };
    $dotClass = function ($tono) {
        return match ($tono) {
            'late' => 'late',
            'warn' => 'warn',
            default => '',
        };
    };
@endphp

@section('page-header')
    <div class="proc-head">
        <div>
            <h1>{{ $saludo }}, {{ $user->primerNombre() }}</h1>
            <p>
                @if($abiertos > 0)
                    {{ $abiertos }} proceso{{ $abiertos === 1 ? '' : 's' }} abierto{{ $abiertos === 1 ? '' : 's' }}
                    @if(($plazosVencidos + $plazosPorVencer) > 0)
                        · {{ $plazosVencidos + $plazosPorVencer }} con plazo en riesgo
                    @endif
                    @if($anexosTotal > 0)
                        · {{ $anexosTotal }} anexo{{ $anexosTotal === 1 ? '' : 's' }}
                    @endif
                @else
                    Panel de gestión de procesos disciplinarios.
                @endif
            </p>
        </div>
        <div class="proc-head-side">
            <span class="stat-chip">{{ $total }} total</span>
            @if($abiertos > 0)<span class="stat-chip">{{ $abiertos }} abiertos</span>@endif
            <span class="stat-chip green">{{ $tasaResolucion }}% resolución</span>
            @if($plazosVencidos > 0)<span class="stat-chip red">{{ $plazosVencidos }} vencidos</span>@endif
            @if(auth()->user()->puede('registrar_casos'))
            <a class="btn-add" href="{{ route('abogado.registro') }}"><i class="fas fa-plus"></i> Registrar proceso</a>
            @endif
            <a class="btn-alt" href="{{ route('abogado.mis-casos') }}"><i class="fas fa-folder-open"></i> Mis casos</a>
        </div>
    </div>
@endsection

@section('content')
    <section class="dash-pipeline">
        <a class="dash-pipe" href="{{ route('abogado.mis-casos', ['estado' => 'Pendiente']) }}">
            <div class="dash-pipe-top">
                <div class="dash-pipe-ico y"><i class="far fa-hourglass"></i></div>
                <div class="dash-pipe-num">{{ $pendientes }}</div>
            </div>
            <div class="dash-pipe-title">Pendiente</div>
            <div class="dash-bar yellow"><span style="width: {{ $pctPendiente }}%"></span></div>
            <small>{{ $pctPendiente }}% del total</small>
        </a>
        <a class="dash-pipe" href="{{ route('abogado.mis-casos', ['estado' => 'En Proceso']) }}">
            <div class="dash-pipe-top">
                <div class="dash-pipe-ico b"><i class="fas fa-spinner"></i></div>
                <div class="dash-pipe-num">{{ $enProceso }}</div>
            </div>
            <div class="dash-pipe-title">En proceso</div>
            <div class="dash-bar blue"><span style="width: {{ $pctProceso }}%"></span></div>
            <small>{{ $pctProceso }}% del total</small>
        </a>
        <a class="dash-pipe" href="{{ route('abogado.mis-casos', ['estado' => 'Sancionado']) }}">
            <div class="dash-pipe-top">
                <div class="dash-pipe-ico r"><i class="far fa-times-circle"></i></div>
                <div class="dash-pipe-num">{{ $sancionados }}</div>
            </div>
            <div class="dash-pipe-title">Sancionado</div>
            <div class="dash-bar rose"><span style="width: {{ $pctSancionado }}%"></span></div>
            <small>{{ $pctSancionado }}% del total</small>
        </a>
        <a class="dash-pipe" href="{{ route('abogado.mis-casos', ['estado' => 'Archivado']) }}">
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
                    <span>Requieren atención</span>
                    @if($atencion->isNotEmpty())
                        <span class="dash-chip">{{ $atencion->count() }}</span>
                    @endif
                    <a href="{{ route('abogado.plazos') }}">Plazos &rarr;</a>
                </div>

                @forelse($atencion as $proceso)
                    @php $paso = $proceso->siguienteAccion(); @endphp
                    <a class="dash-row" href="{{ route('abogado.detalleproceso', $proceso->id) }}">
                        <span class="dash-dot {{ $dotClass($paso['tono']) }}">{{ str_pad($proceso->id, 3, '0', STR_PAD_LEFT) }}</span>
                        <div class="dash-main">
                            <b>{{ $proceso->nombre }}</b>
                            <small>{{ $proceso->codigoProceso() }} · {{ $proceso->tipo_falta ?: 'Sin tipificar' }}</small>
                        </div>
                        <span class="dash-status {{ $paso['tono'] }}">{{ $paso['texto'] }}</span>
                    </a>
                @empty
                    <div class="dash-ok">Nada urgente. Los plazos de tus casos abiertos están en término.</div>
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
                    <a href="{{ route('abogado.mis-casos', ['estado' => 'Pendiente']) }}">
                        <span>Descargos</span>
                        <b>{{ $descargosPendientes }}</b>
                    </a>
                    <a href="{{ route('abogado.anexos') }}">
                        <span>Anexos</span>
                        <b>{{ $anexosTotal }}</b>
                    </a>
                </div>
                <div class="dash-links">
                    <a href="{{ route('documentos.hub') }}">
                        <span><i class="fas fa-file-signature left"></i> Autos y Actas</span>
                        <i class="fas fa-chevron-right right"></i>
                    </a>
                    <a href="{{ route('abogado.anexos') }}">
                        <span><i class="fas fa-file-upload left"></i> Anexos escaneados</span>
                        @if($anexosTotal > 0)<em class="count">{{ $anexosTotal }}</em>@endif
                        <i class="fas fa-chevron-right right"></i>
                    </a>
                    <a href="{{ route('abogado.plazos') }}">
                        <span><i class="far fa-clock left"></i> Plazos y términos</span>
                        @if(($plazosVencidos + $plazosPorVencer) > 0)<em class="count">{{ $plazosVencidos + $plazosPorVencer }}</em>@endif
                        <i class="fas fa-chevron-right right"></i>
                    </a>
                    <a href="{{ route('abogado.resoluciones') }}">
                        <span><i class="far fa-file-alt left"></i> Resoluciones</span>
                        <i class="fas fa-chevron-right right"></i>
                    </a>
                    <a href="{{ route('abogado.reportes') }}">
                        <span><i class="fas fa-chart-bar left"></i> Estadísticas / Reportes</span>
                        <i class="fas fa-chevron-right right"></i>
                    </a>
                </div>
            </section>
        </div>
    </div>

    <section class="dash-card dash-card-wide">
        <div class="dash-card-title">
            <span>Procesos recientes</span>
            <a href="{{ route('abogado.mis-casos') }}">Ver tabla &rarr;</a>
        </div>
        <div class="dash-recent">
            @forelse($recientes as $proceso)
                @php $paso = $proceso->siguienteAccion(); @endphp
                <a class="dash-row" href="{{ route('abogado.detalleproceso', $proceso->id) }}">
                    <span class="dash-dot">{{ str_pad($proceso->id, 3, '0', STR_PAD_LEFT) }}</span>
                    <div class="dash-main">
                        <b>{{ $proceso->nombre }}</b>
                        <small>{{ $paso['texto'] }}</small>
                    </div>
                    <span class="dash-status {{ $estadoClass($proceso->estado) }}">
                        <i class="fas fa-circle" style="font-size:6px;"></i> {{ $proceso->estado }}
                    </span>
                </a>
            @empty
                <div class="dash-empty">Aún no hay procesos registrados.</div>
            @endforelse
        </div>
    </section>
@endsection
