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
    <link rel="stylesheet" href="{{ rtrim(request()->root(), '/') }}/AdminLTE-3.2.0/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="{{ rtrim(request()->root(), '/') }}/AdminLTE-3.2.0/dist/css/adminlte.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="stylesheet" href="{{ rtrim(request()->root(), '/') }}/css/sipd-theme.css?v=2">
    @yield('styles')
</head>
<body class="theme-cootranshuila">
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
                <div class="sipd-brand-mark">C</div>
                <div>
                    <strong>SIPD</strong>
                    <small>COOTRANSHUILA</small>
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
                <a class="sipd-logout" href="{{ route('logout') }}"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="fas fa-sign-out-alt"></i> Cerrar sesión
                </a>
            </div>
        </aside>

        <div class="sipd-main">
            <header class="sipd-top">
                <div class="sipd-crumb d-flex align-items-center gap-3">
                    <a href="{{ route('abogado.dashboard') }}">Cootranshuila</a>
                    <span>/</span>
                    <strong>{{ $pageTitle }}</strong>
                </div>

                <!-- Buscador Global en Header -->
                <form class="sipd-search" action="{{ route('abogado.consultarproceso') }}" method="GET">
                    <div class="sipd-search-inner">
                        <i class="fas fa-search"></i>
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar por conductor, cédula, placa o N° proceso...">
                    </div>
                </form>

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
                confirmButtonColor: '#006837'
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
                confirmButtonColor: '#006837'
            });
        });
    </script>
    @endif

    <script src="{{ rtrim(request()->root(), '/') }}/AdminLTE-3.2.0/plugins/jquery/jquery.min.js"></script>
    <script src="{{ rtrim(request()->root(), '/') }}/AdminLTE-3.2.0/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
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
