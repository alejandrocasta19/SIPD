<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('images/Favicon2.png') }}" type="image/x-icon">
    <title>SIPD · {{ $pageTitle ?? 'Inicio' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('AdminLTE-3.2.0/plugins/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('AdminLTE-3.2.0/dist/css/adminlte.min.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        html, body {
            min-height: 100%;
            margin: 0 !important;
            padding: 0 !important;
            font-family: Inter, system-ui, -apple-system, sans-serif !important;
            background: #f4f6fa !important;
            color: #0f172a;
        }

        .sipd-app {
            display: grid;
            grid-template-columns: 236px 1fr;
            min-height: 100vh;
        }

        .sipd-sidebar {
            background: #0f172a;
            color: #f1f5f9;
            padding: 24px 14px 16px;
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 0;
            height: 100vh;
            box-sizing: border-box;
        }

        .sipd-sidebar-nav {
            flex: 1;
            overflow-y: auto;
            padding-right: 4px;
        }

        .sipd-sidebar-nav::-webkit-scrollbar {
            width: 4px;
        }
        .sipd-sidebar-nav::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,.1);
            border-radius: 4px;
        }

        .sipd-sidebar-nav::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,.2);
        }


        .sipd-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 4px 8px 22px;
            text-decoration: none;
            color: #fff;
        }

        .sipd-brand-mark {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: #15803d; /* Elegant deep green */
            color: #fff;
            display: grid;
            place-items: center;
            font-weight: 800;
            font-size: 18px;
            box-shadow: 0 4px 12px rgba(21, 128, 61, 0.3);
        }

        .sipd-brand strong {
            display: block;
            font-size: 15px;
            letter-spacing: .04em;
        }

        .sipd-brand small {
            display: block;
            color: #94a3b8;
            font-size: 9px;
            letter-spacing: .12em;
        }

        .sipd-nav-label {
            color: #64748b;
            font-size: 11px;
            letter-spacing: .12em;
            font-weight: 700;
            padding: 10px 12px 6px;
        }

        .sipd-nav {
            display: grid;
            gap: 4px;
        }

        .sipd-nav a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 8px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 500;
            transition: all 0.2s ease;
            position: relative;
        }

        .sipd-nav a i { width: 18px; text-align: center; opacity: 0.8; transition: transform 0.2s; }

        .sipd-nav a:hover { 
            background: rgba(255,255,255,.03); 
            color: #f8fafc; 
        }

        .sipd-nav a:hover i {
            opacity: 1;
            transform: scale(1.1);
        }

        .sipd-nav a.active {
            background: rgba(255,255,255,.05);
            color: #fff;
            font-weight: 600;
            border-left: 4px solid #16a34a;
            border-radius: 0 8px 8px 0;
            padding-left: 10px;
        }
        
        .sipd-nav a.active i {
            color: #4ade80;
            opacity: 1;
        }

        .sipd-side-user {
            margin-top: auto;
            padding-top: 16px;
            border-top: 1px solid rgba(255,255,255,.08);
        }

        .sipd-side-user .who {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 6px 12px;
        }

        .sipd-side-user .ava {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, #16a34a, #15803d);
            display: grid;
            place-items: center;
            font-size: 12px;
            font-weight: 700;
            color: #fff;
            box-shadow: 0 2px 8px rgba(22, 163, 74, 0.2);
        }

        .sipd-side-user b,
        .sipd-side-user small {
            display: block;
            color: #ffffff !important;
        }

        .sipd-side-user b { font-size: 13px; }

        .sipd-side-user small {
            font-size: 11px;
            text-transform: capitalize;
            opacity: .9;
        }

        .sipd-logout {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 13px;
            padding: 6px;
        }

        .sipd-logout:hover { color: #fff; }

        .sipd-main { min-width: 0; }

        .sipd-top {
            height: 72px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
        }

        .sipd-crumb {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #94a3b8;
            font-size: 13px;
        }

        .sipd-crumb a { color: #94a3b8; text-decoration: none; }
        .sipd-crumb strong { color: #0f172a; font-weight: 600; }

        .sipd-top-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sipd-bell,
        .sipd-user {
            display: flex;
            align-items: center;
            gap: 10px;
            color: inherit;
            text-decoration: none;
            background: #fff;
            border-radius: 999px;
            box-shadow: 0 2px 4px rgba(15,23,42,.04);
            border: 1px solid #f1f5f9;
            transition: all 0.2s ease;
        }
        
        .sipd-bell:hover, .sipd-user:hover {
            box-shadow: 0 4px 6px rgba(15,23,42,.06);
            border-color: #e2e8f0;
        }

        .sipd-bell {
            width: 42px;
            height: 42px;
            justify-content: center;
            color: #64748b;
            position: relative;
        }

        .sipd-bell:hover { color: #0f172a; }

        .sipd-bell .badge-dot {
            position: absolute;
            top: 8px;
            right: 8px;
            min-width: 16px;
            height: 16px;
            padding: 0 4px;
            border-radius: 999px;
            background: #ef4444;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            display: grid;
            place-items: center;
        }

        .notif-menu {
            width: 320px;
            padding: 10px 8px;
            border: 0;
            border-radius: 16px;
            box-shadow: 0 12px 40px rgba(15,23,42,.12);
            margin-top: 8px;
        }

        .notif-menu h4 {
            margin: 0;
            padding: 8px 10px 10px;
            font-size: 14px;
            font-weight: 700;
        }

        .notif-menu a {
            display: block;
            padding: 10px 12px;
            border-radius: 10px;
            color: #334155;
            text-decoration: none;
            font-size: 13px;
        }

        .notif-menu a:hover { background: #f8fafc; }
        .notif-menu a b { display: block; font-size: 13px; }
        .notif-menu a span { color: #94a3b8; font-size: 12px; }
        .notif-empty { padding: 12px; color: #94a3b8; font-size: 13px; }

        .sipd-user .ava {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #16a34a;
            color: #fff;
            display: grid;
            place-items: center;
            font-size: 12px;
            font-weight: 700;
        }

        .sipd-user {
            padding: 4px 12px 4px 4px;
        }

        .sipd-user b { display: block; font-size: 14px; font-weight: 600; }
        .sipd-user small { display: block; color: #94a3b8; font-size: 12px; text-transform: capitalize; }
        .sipd-user .chev { color: #94a3b8; font-size: 10px; margin-left: 2px; }

        .sipd-user.dropdown-toggle::after,
        .sipd-bell.dropdown-toggle::after { display: none; }

        .user-menu {
            width: 280px;
            padding: 12px 8px 8px;
            border: 0;
            border-radius: 12px;
            box-shadow: 0 16px 48px rgba(15,23,42,.12);
            margin-top: 8px;
        }

        .user-menu-head {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 8px 10px 14px;
        }

        .user-menu-head .ava {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #16a34a;
            color: #fff;
            display: grid;
            place-items: center;
            font-size: 13px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .user-menu-head b {
            display: block;
            font-size: 14px;
            color: #0f172a;
        }

        .user-menu-head span,
        .user-menu-head small {
            display: block;
            color: #94a3b8;
            font-size: 12px;
        }

        .user-menu-head small { text-transform: capitalize; }

        .user-menu .dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            border-radius: 10px;
            padding: 10px 12px;
            color: #334155;
            font-size: 14px;
        }

        .user-menu .dropdown-item i { width: 16px; color: #94a3b8; }
        .user-menu .dropdown-item:hover { background: #f8fafc; }
        .user-menu .dropdown-item.danger { color: #dc2626; }
        .user-menu .dropdown-item.danger i { color: #dc2626; }
        .user-menu .dropdown-divider { margin: 4px 8px; }

        .sipd-wrap {
            padding: 0 28px 48px;
        }

        .sipd-page-title {
            margin: 0 0 24px;
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -.02em;
            color: #0f172a;
        }

        .dropdown-menu { font-size: 14px; }

        .pwd-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .45);
            z-index: 80;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .pwd-modal.open { display: flex; }

        .pwd-box {
            width: 100%;
            max-width: 460px;
            background: #fff;
            border-radius: 20px;
            padding: 24px 24px 20px;
            box-shadow: 0 24px 60px rgba(15,23,42,.2);
        }

        .pwd-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 18px;
        }

        .pwd-top h3 {
            margin: 0 0 6px;
            font-size: 22px;
            font-weight: 700;
        }

        .pwd-top p {
            margin: 0;
            color: #94a3b8;
            font-size: 14px;
        }

        .pwd-close {
            border: 0;
            background: none;
            color: #94a3b8;
            font-size: 20px;
            cursor: pointer;
            line-height: 1;
        }

        .pwd-box label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
        }

        .pwd-field {
            position: relative;
            margin-bottom: 14px;
        }

        .pwd-field input {
            width: 100%;
            height: 44px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 0 64px 0 14px;
            font: inherit;
            font-size: 14px;
            outline: none;
        }

        .pwd-field input:focus { border-color: #22c55e; }

        .pwd-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            border: 0;
            background: none;
            color: #94a3b8;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .pwd-error {
            color: #dc2626;
            font-size: 12px;
            margin: -8px 0 12px;
        }

        .pwd-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 18px;
        }

        .pwd-cancel,
        .pwd-save {
            border: 0;
            border-radius: 999px;
            padding: 11px 18px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
        }

        .pwd-cancel { background: #f1f5f9; color: #475569; }
        .pwd-save { background: #16a34a; color: #fff; transition: background 0.2s; }
        .pwd-save:hover { background: #15803d; }

        .pf-box {
            width: 100%;
            max-width: 560px;
            background: #fff;
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 24px 60px rgba(15,23,42,.2);
        }

        .pf-hero {
            background: #16a34a;
            color: #fff;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .pf-hero-user {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .pf-hero .ava {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #fff;
            color: #16a34a;
            display: grid;
            place-items: center;
            font-weight: 800;
        }

        .pf-hero b { display: block; font-size: 16px; }
        .pf-hero small { display: block; opacity: .9; font-size: 13px; }

        .pf-hero .pwd-close { color: #fff; }

        .pf-body { padding: 20px 22px 18px; }

        .pf-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px 14px;
            margin-bottom: 14px;
        }

        .pf-grid label {
            display: block;
            font-size: 11px;
            letter-spacing: .04em;
            color: #94a3b8;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .pf-grid input {
            width: 100%;
            height: 42px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 0 12px;
            font: inherit;
            font-size: 14px;
            background: #fff;
        }

        .pf-grid input[readonly] {
            background: #f8fafc;
            color: #334155;
        }

        .pf-status {
            background: #f0fdf4;
            color: #166534;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 13px;
            margin-bottom: 16px;
        }

        @media (max-width: 900px) {
            .sipd-app { grid-template-columns: 1fr; }
            .sipd-sidebar { position: relative; height: auto; }
        }
    </style>
    @yield('styles')
</head>
<body>
    @php
        $pageTitle = $pageTitle ?? (request()->routeIs('abogado.dashboard') ? 'Inicio' : 'SIPD');
        $nameParts  = collect(preg_split('/\s+/', trim(auth()->user()->name)))->filter()->values();
        $iniciales  = $nameParts->take(2)->map(fn($p) => strtoupper(substr($p, 0, 1)))->implode('');
        // Primer nombre + primer apellido (o solo el primer nombre si hay uno)
        $nombreCorto = $nameParts->get(0, '') . ($nameParts->get(2) ? ' ' . $nameParts->get(2) : ($nameParts->get(1) ? ' ' . $nameParts->get(1) : ''));
        $isManager = in_array(auth()->user()->role, ['admin', 'coordinadora'], true);
        $visibleProcesses = \App\Models\ProcesoDisciplinario::query();
        if (!$isManager) {
            $visibleProcesses->where('user_id', auth()->id());
        }

        $notifVencidos = (clone $visibleProcesses)->whereIn('estado', ['Pendiente', 'En Proceso'])
            ->where('created_at', '<', now()->subDays(15))
            ->count();
        $notifSinRh = (clone $visibleProcesses)->whereNull('user_id')->count();
        $notifDescargos = (clone $visibleProcesses)->whereIn('estado', ['Pendiente', 'En Proceso'])
            ->where(function ($query) {
                $query->whereNull('descargos')->orWhere('descargos', '');
            })
            ->count();
        $notifTotal = $notifVencidos + ($notifSinRh > 0 ? 1 : 0) + ($notifDescargos > 0 ? 1 : 0);
    @endphp

    <div class="sipd-app">
        <aside class="sipd-sidebar">
            <a class="sipd-brand" href="{{ route('abogado.dashboard') }}">
                <div class="sipd-brand-mark">S</div>
                <div>
                    <strong>SIPD</strong>
                    <small>PROCESOS DISCIPLINARIOS</small>
                </div>
            </a>

            <div class="sipd-sidebar-nav">
                <div class="sipd-nav-label">PRINCIPAL</div>
                <nav class="sipd-nav">
                    <a href="{{ route('abogado.dashboard') }}" class="{{ request()->routeIs('abogado.dashboard') ? 'active' : '' }}">
                        <i class="fas fa-home"></i> Inicio
                    </a>
                    <a href="{{ route('abogado.registro') }}" class="{{ request()->routeIs('abogado.registro') ? 'active' : '' }}">
                        <i class="fas fa-plus-circle"></i> Nuevo Proceso
                    </a>
                    @if($isManager)
                        <a href="{{ route('abogado.consultarproceso') }}" class="{{ request()->routeIs('abogado.consultarproceso', 'abogado.detalleproceso') && !request()->routeIs('documentos.*') ? 'active' : '' }}">
                            <i class="fas fa-search"></i> Buscar Procesos
                        </a>
                    @else
                        <a href="{{ route('abogado.mis-casos') }}" class="{{ request()->routeIs('abogado.mis-casos', 'abogado.detalleproceso') && !request()->routeIs('documentos.*') ? 'active' : '' }}">
                            <i class="fas fa-search"></i> Mis Casos
                        </a>
                    @endif
                    <a href="{{ route('abogado.reincidencias') }}" class="{{ request()->routeIs('abogado.reincidencias') ? 'active' : '' }}">
                        <i class="fas fa-history"></i> Reincidencias
                    </a>
                </nav>

                <div class="sipd-nav-label">MÓDULOS DE CONTROL</div>
                <nav class="sipd-nav">
                    <a href="{{ route('abogado.plazos') }}" class="{{ request()->routeIs('abogado.plazos') ? 'active' : '' }}">
                        <i class="far fa-clock"></i> Plazos y términos
                    </a>
                    <a href="{{ route('abogado.partes') }}" class="{{ request()->routeIs('abogado.partes') ? 'active' : '' }}">
                        <i class="fas fa-user-friends"></i> Partes involucradas
                    </a>
                    <a href="{{ route('abogado.resoluciones') }}" class="{{ request()->routeIs('abogado.resoluciones') ? 'active' : '' }}">
                        <i class="far fa-file-alt"></i> Resoluciones
                    </a>
                </nav>

                <div class="sipd-nav-label">GESTIÓN DOCUMENTAL</div>
                <nav class="sipd-nav">
                    <a href="{{ route('documentos.hub') }}"
                       class="{{ request()->routeIs('documentos.*') ? 'active' : '' }}"
                       title="Central de Documentos Oficiales">
                        <i class="fas fa-file-signature"></i> Autos y Actas
                    </a>
                    @if(request()->routeIs('documentos.*') && request()->route('id'))
                        @php $docCasoId = request()->route('id'); @endphp
                        <a href="{{ route('documentos.edit', [$docCasoId, 'disciplinario']) }}"
                           class="{{ request()->routeIs('documentos.edit') && request()->route('tipo') === 'disciplinario' ? 'active' : '' }}"
                           style="padding-left:28px;font-size:13px;">
                            <i class="fas fa-balance-scale"></i> Apertura Disciplinarios
                        </a>
                        <a href="{{ route('documentos.edit', [$docCasoId, 'comprobacion']) }}"
                           class="{{ request()->routeIs('documentos.edit') && request()->route('tipo') === 'comprobacion' ? 'active' : '' }}"
                           style="padding-left:28px;font-size:13px;">
                            <i class="fas fa-search"></i> Apertura Comprobación
                        </a>
                        <a href="{{ route('documentos.edit', [$docCasoId, 'acta']) }}"
                           class="{{ request()->routeIs('documentos.edit') && request()->route('tipo') === 'acta' ? 'active' : '' }}"
                           style="padding-left:28px;font-size:13px;">
                            <i class="fas fa-gavel"></i> Acta Cargos y Descargos
                        </a>
                    @endif
                </nav>

                <div class="sipd-nav-label">REPORTES</div>
                <nav class="sipd-nav">
                    <a href="{{ route('abogado.reportes') }}" class="{{ request()->routeIs('abogado.estadistica', 'abogado.reportes') ? 'active' : '' }}">
                        <i class="fas fa-chart-bar"></i> Estadísticas / Reportes
                    </a>
                    @if($isManager)
                        <a href="{{ route('coordinadora.abogados') }}" class="{{ request()->routeIs('coordinadora.abogados') ? 'active' : '' }}">
                            <i class="fas fa-user-tie"></i> Gestión de RH
                        </a>
                    @endif
                </nav>
            </div>


            <div class="sipd-side-user">
                <div class="who">
                    <span class="ava">{{ $iniciales }}</span>
                    <div>
                        <b>{{ $nombreCorto }}</b>
                        <small>{{ auth()->user()->cargo ?: ucfirst(auth()->user()->role) }}</small>
                    </div>
                </div>
                <a class="sipd-logout" href="{{ route('logout') }}"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="fas fa-sign-out-alt"></i> Cerrar sesión
                </a>
            </div>
        </aside>

        <div class="sipd-main">
            <header class="sipd-top">
                <div class="sipd-crumb">
                    <a href="{{ route('abogado.dashboard') }}">SIPD</a>
                    <span>/</span>
                    <strong>{{ $pageTitle }}</strong>
                </div>

                <div class="sipd-top-actions">
                    <div class="dropdown">
                        <a class="sipd-bell dropdown-toggle" href="#" data-toggle="dropdown" title="Notificaciones">
                            <i class="far fa-bell"></i>
                            @if($notifTotal > 0)
                                <span class="badge-dot">{{ $notifTotal }}</span>
                            @endif
                        </a>
                        <div class="dropdown-menu dropdown-menu-right notif-menu">
                            <h4>Notificaciones</h4>
                            @if($notifVencidos > 0)
                                <a href="{{ route('abogado.plazos', ['estado' => 'vencido']) }}">
                                    <b>Plazos vencidos</b>
                                    <span>{{ $notifVencidos }} proceso{{ $notifVencidos === 1 ? '' : 's' }} superó el término de 15 días</span>
                                </a>
                            @endif
                            @if($notifSinRh > 0)
                                <a href="{{ route('abogado.consultarproceso') }}">
                                    <b>Sin RH asignado</b>
                                    <span>{{ $notifSinRh }} proceso{{ $notifSinRh === 1 ? '' : 's' }} sin responsable</span>
                                </a>
                            @endif
                            @if($notifDescargos > 0)
                                <a href="{{ route('abogado.consultarproceso') }}">
                                    <b>Descargos pendientes</b>
                                    <span>{{ $notifDescargos }} expediente{{ $notifDescargos === 1 ? '' : 's' }} esperan respuesta</span>
                                </a>
                            @endif
                            @if($notifTotal === 0)
                                <div class="notif-empty">No hay notificaciones nuevas</div>
                            @endif
                        </div>
                    </div>

                    <div class="dropdown">
                    <a class="sipd-user dropdown-toggle" href="#" data-toggle="dropdown">
                        <span class="ava">{{ $iniciales }}</span>
                        <span>
                            <b>{{ $nombreCorto }}</b>
                            <small>{{ auth()->user()->cargo ?: ucfirst(auth()->user()->role) }}</small>
                        </span>
                        <i class="fas fa-chevron-down chev"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right user-menu">
                        <div class="user-menu-head">
                            <span class="ava">{{ $iniciales }}</span>
                            <div>
                                <b>{{ auth()->user()->name }}</b>
                                <span>{{ auth()->user()->email }}</span>
                                <small>{{ auth()->user()->cargo ?: ucfirst(auth()->user()->role) }}</small>
                            </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="#" id="open-profile-modal">
                            <i class="far fa-user"></i> Mi perfil
                        </a>
                        <a class="dropdown-item" href="#" id="open-password-modal">
                            <i class="fas fa-lock"></i> Cambiar contraseña
                        </a>
                        <a class="dropdown-item danger" href="{{ route('logout') }}"
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="fas fa-sign-out-alt"></i> Cerrar sesión
                        </a>
                    </div>
                </div>
                </div>
            </header>

            <div class="sipd-wrap">
                @hasSection('page-header')
                    @yield('page-header')
                @else
                    <h1 class="sipd-page-title">{{ $pageTitle }}</h1>
                @endif
                @yield('content')
            </div>
        </div>
    </div>

    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>

    @php $authUser = auth()->user(); @endphp
    <div class="pwd-modal {{ session('open_profile') || $errors->has('name') || $errors->has('email') ? 'open' : '' }}" id="profile-modal">
        <div class="pf-box">
            <div class="pf-hero">
                <div class="pf-hero-user">
                    <span class="ava">{{ $iniciales }}</span>
                    <div>
                        <b>{{ $authUser->name }}</b>
                        <small>{{ $authUser->cargo ?: ucfirst($authUser->role) }}</small>
                    </div>
                </div>
                <button type="button" class="pwd-close" id="close-profile-modal">&times;</button>
            </div>

            <div class="pf-body">
                <form method="POST" action="{{ route('perfil.update') }}" id="profile-form">
                    @csrf
                    @method('PUT')

                    <div class="pf-grid">
                        <div>
                            <label>NOMBRE COMPLETO</label>
                            <input type="text" name="name" value="{{ old('name', $authUser->name) }}" required readonly>
                            @error('name') <div class="pwd-error">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label>CORREO ELECTRÓNICO</label>
                            <input type="email" name="email" value="{{ old('email', $authUser->email) }}" required readonly>
                            @error('email') <div class="pwd-error">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label>TELÉFONO</label>
                            <input type="text" name="telefono" value="{{ old('telefono', $authUser->telefono) }}" readonly>
                        </div>
                        <div>
                            <label>CÉDULA</label>
                            <input type="text" name="cedula" value="{{ old('cedula', $authUser->cedula) }}" readonly>
                        </div>
                        <div>
                            <label>CARGO</label>
                            <input type="text" name="cargo" value="{{ old('cargo', $authUser->cargo) }}" readonly>
                        </div>
                        <div>
                            <label>FECHA DE INGRESO</label>
                            <input type="date" name="fecha_ingreso" value="{{ old('fecha_ingreso', optional($authUser->fecha_ingreso)->format('Y-m-d') ?: optional($authUser->created_at)->format('Y-m-d')) }}" readonly>
                        </div>
                    </div>

                    <div class="pf-status">
                        Rol asignado: {{ ucfirst($authUser->role) }} · Acceso activo
                    </div>

                    <div class="pwd-actions">
                        <button type="button" class="pwd-cancel" id="cancel-profile-modal">Cerrar</button>
                        <button type="button" class="pwd-save" id="edit-profile-btn">Editar perfil</button>
                        <button type="submit" class="pwd-save" id="save-profile-btn" style="display:none;">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="pwd-modal {{ session('open_password') || $errors->has('current_password') || $errors->has('password') ? 'open' : '' }}" id="password-modal">
        <div class="pwd-box">
            <div class="pwd-top">
                <div>
                    <h3>Cambiar contraseña</h3>
                    <p>Elige una contraseña segura para tu cuenta.</p>
                </div>
                <button type="button" class="pwd-close" id="close-password-modal">&times;</button>
            </div>

            <form method="POST" action="{{ route('perfil.password.update') }}">
                @csrf
                @method('PUT')

                <label>Contraseña actual</label>
                <div class="pwd-field">
                    <input type="password" name="current_password" id="current_password" required>
                    <button type="button" class="pwd-toggle" data-target="current_password">Ver</button>
                </div>
                @error('current_password')
                    <div class="pwd-error">{{ $message }}</div>
                @enderror

                <label>Nueva contraseña</label>
                <div class="pwd-field">
                    <input type="password" name="password" id="new_password" required>
                    <button type="button" class="pwd-toggle" data-target="new_password">Ver</button>
                </div>
                @error('password')
                    <div class="pwd-error">{{ $message }}</div>
                @enderror

                <label>Confirmar nueva contraseña</label>
                <div class="pwd-field">
                    <input type="password" name="password_confirmation" id="password_confirmation" required>
                    <button type="button" class="pwd-toggle" data-target="password_confirmation">Ver</button>
                </div>

                <div class="pwd-actions">
                    <button type="button" class="pwd-cancel" id="cancel-password-modal">Cancelar</button>
                    <button type="submit" class="pwd-save">Actualizar contraseña</button>
                </div>
            </form>
        </div>
    </div>

    @if(session('profile_success'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: @json(session('profile_success')),
                confirmButtonColor: '#16a34a'
            });
        });
    </script>
    @endif

    @if(session('password_success'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: @json(session('password_success')),
                confirmButtonColor: '#16a34a'
            });
        });
    </script>
    @endif

    <script src="{{ asset('AdminLTE-3.2.0/plugins/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('AdminLTE-3.2.0/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>
        (function () {
            var modal = document.getElementById('password-modal');
            var openBtn = document.getElementById('open-password-modal');
            var closeBtn = document.getElementById('close-password-modal');
            var cancelBtn = document.getElementById('cancel-password-modal');

            function openModal(e) {
                if (e) e.preventDefault();
                modal.classList.add('open');
            }

            function closeModal() {
                modal.classList.remove('open');
            }

            if (openBtn) openBtn.addEventListener('click', openModal);
            if (closeBtn) closeBtn.addEventListener('click', closeModal);
            if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

            modal.addEventListener('click', function (e) {
                if (e.target === modal) closeModal();
            });

            var profileModal = document.getElementById('profile-modal');
            var openProfile = document.getElementById('open-profile-modal');
            var closeProfile = document.getElementById('close-profile-modal');
            var cancelProfile = document.getElementById('cancel-profile-modal');
            var editProfile = document.getElementById('edit-profile-btn');
            var saveProfile = document.getElementById('save-profile-btn');
            var profileInputs = document.querySelectorAll('#profile-form input');

            function openProfileModal(e) {
                if (e) e.preventDefault();
                profileModal.classList.add('open');
            }

            function closeProfileModal() {
                profileModal.classList.remove('open');
            }

            function enableProfileEdit() {
                profileInputs.forEach(function (input) { input.readOnly = false; });
                editProfile.style.display = 'none';
                saveProfile.style.display = 'inline-flex';
            }

            if (openProfile) openProfile.addEventListener('click', openProfileModal);
            if (closeProfile) closeProfile.addEventListener('click', closeProfileModal);
            if (cancelProfile) cancelProfile.addEventListener('click', closeProfileModal);
            if (editProfile) editProfile.addEventListener('click', enableProfileEdit);

            profileModal.addEventListener('click', function (e) {
                if (e.target === profileModal) closeProfileModal();
            });

            @if(session('profile_edit'))
            enableProfileEdit();
            @endif

            document.querySelectorAll('.pwd-toggle').forEach(function (button) {
                button.addEventListener('click', function () {
                    var input = document.getElementById(button.getAttribute('data-target'));
                    var visible = input.type === 'text';
                    input.type = visible ? 'password' : 'text';
                    button.textContent = visible ? 'Ver' : 'Ocultar';
                });
            });
        })();
    </script>
    @yield('scripts')
</body>
</html>
