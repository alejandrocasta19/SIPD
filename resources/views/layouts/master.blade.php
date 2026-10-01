<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('images/logo-sipd.svg') }}" type="image/svg+xml">
    <title>SIPD · {{ $pageTitle ?? 'Inicio' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ rtrim(request()->root(), '/') }}/AdminLTE-3.2.0/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="{{ rtrim(request()->root(), '/') }}/AdminLTE-3.2.0/dist/css/adminlte.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="stylesheet" href="{{ rtrim(request()->root(), '/') }}/css/sipd-theme.css?v=34">
    @yield('styles')
</head>
<body class="theme-cootranshuila">
    @php
        $me = auth()->user();
        $me->loadMissing('permisos');
        $pageTitle = $pageTitle ?? (request()->routeIs('abogado.dashboard') ? 'Inicio' : 'SIPD');
        $nameParts  = collect(preg_split('/\s+/', trim($me->name)))->filter()->values();
        $iniciales  = $nameParts->take(2)->map(fn($p) => strtoupper(substr($p, 0, 1)))->implode('');
        $nombreCorto = $nameParts->get(0, '') . ($nameParts->get(2) ? ' ' . $nameParts->get(2) : ($nameParts->get(1) ? ' ' . $nameParts->get(1) : ''));
        $isManager = $me->esCoordinadora();
        $etiquetaRol = $me->etiquetaEquipo();
        $visibleProcesses = \App\Models\ProcesoDisciplinario::query();
        if (!$isManager) {
            $visibleProcesses->where('user_id', $me->id);
        }

        $notifVeredictos = $isManager ? (clone $visibleProcesses)->where('estado', 'En Proceso')->count() : 0;
        $bandeja = \App\Models\Aviso::noLeidosPara($me)->take(8);
        $bandejaCount = \App\Models\Aviso::where('user_id', $me->id)->whereNull('leida_at')->count();
        $alertasSistema = \App\Support\AlertasSistema::visibles($me);
        $notifSolicitudes = $isManager
            ? \App\Models\Aviso::where('user_id', $me->id)->where('tipo', 'solicitud')->whereNull('leida_at')->count()
            : 0;
        $notifTotal = $bandejaCount + count($alertasSistema);
        $solicitablesPermiso = \App\Support\RhPermisos::solicitables();
        $duracionesHoras = \App\Support\RhPermisos::duracionesHoras();
        $puedeEditarPerfil = $me->puede('editar_perfil');
    @endphp

    <div class="sipd-app">
        <aside class="sipd-sidebar">
            <a class="sipd-brand" href="{{ route('abogado.dashboard') }}">
                <img class="sipd-brand-logo" src="{{ asset('images/logo-sipd.svg') }}" alt="SIPD">
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
                    @if($isManager)
                        <a href="{{ route('abogado.consultarproceso') }}" class="{{ request()->routeIs('abogado.consultarproceso', 'abogado.detalleproceso') && !request()->routeIs('documentos.*') && !request()->routeIs('coordinadora.veredictos') ? 'active' : '' }}">
                            <i class="fas fa-search"></i> Buscar procesos
                        </a>
                        <a href="{{ route('coordinadora.veredictos') }}" class="{{ request()->routeIs('coordinadora.veredictos') ? 'active' : '' }}">
                            <i class="fas fa-gavel"></i> Veredictos
                            @if($notifVeredictos > 0)
                                <span class="sipd-nav-count">{{ $notifVeredictos }}</span>
                            @endif
                        </a>
                        <a href="{{ route('coordinadora.solicitudes') }}" class="{{ request()->routeIs('coordinadora.solicitudes') ? 'active' : '' }}">
                            <i class="fas fa-key"></i> Solicitudes
                            @if($notifSolicitudes > 0)
                                <span class="sipd-nav-count">{{ $notifSolicitudes }}</span>
                            @endif
                        </a>
                        <a href="{{ route('coordinadora.abogados') }}" class="{{ request()->routeIs('coordinadora.abogados') ? 'active' : '' }}">
                            <i class="fas fa-user-tie"></i> Equipo y permisos
                        </a>
                    @else
                        @if($me->puede('registrar_casos'))
                            <a href="{{ route('abogado.registro') }}" class="{{ request()->routeIs('abogado.registro') ? 'active' : '' }}">
                                <i class="fas fa-plus-circle"></i> Nuevo Proceso
                            </a>
                        @endif
                        @if($me->puede('ver_casos'))
                            <a href="{{ route('abogado.mis-casos') }}" class="{{ request()->routeIs('abogado.mis-casos', 'abogado.detalleproceso') && !request()->routeIs('documentos.*') ? 'active' : '' }}">
                                <i class="fas fa-search"></i> Mis Casos
                            </a>
                        @endif
                    @endif
                    @if($me->puede('ver_reincidencias'))
                    <a href="{{ route('abogado.reincidencias') }}" class="{{ request()->routeIs('abogado.reincidencias') ? 'active' : '' }}">
                        <i class="fas fa-history"></i> Reincidencias
                    </a>
                    @endif
                </nav>

                <div class="sipd-nav-label">MÓDULOS DE CONTROL</div>
                <nav class="sipd-nav">
                    @if($me->puede('ver_plazos'))
                    <a href="{{ route('abogado.plazos') }}" class="{{ request()->routeIs('abogado.plazos') ? 'active' : '' }}">
                        <i class="far fa-clock"></i> Plazos y términos
                    </a>
                    @endif
                    @if($me->puede('ver_anexos'))
                    <a href="{{ route('abogado.anexos') }}" class="{{ request()->routeIs('abogado.anexos') ? 'active' : '' }}">
                        <i class="fas fa-file-upload"></i> Anexos escaneados
                    </a>
                    @endif
                    @if($me->puede('ver_resoluciones'))
                    <a href="{{ route('abogado.resoluciones') }}" class="{{ request()->routeIs('abogado.resoluciones') ? 'active' : '' }}">
                        <i class="far fa-file-alt"></i> Resoluciones
                    </a>
                    @endif
                    @if($isManager && $me->puede('registrar_casos'))
                        <a href="{{ route('abogado.registro') }}" class="{{ request()->routeIs('abogado.registro') ? 'active' : '' }}">
                            <i class="fas fa-plus-circle"></i> Nuevo proceso
                        </a>
                    @endif
                </nav>

                @if($me->puede('ver_documentos'))
                <div class="sipd-nav-label">GESTIÓN DOCUMENTAL</div>
                <nav class="sipd-nav">
                    <a href="{{ route('documentos.hub') }}"
                       class="{{ request()->routeIs('documentos.*') ? 'active' : '' }}"
                       title="Central de Documentos Oficiales">
                        <i class="fas fa-file-signature"></i> Autos y Actas
                    </a>
                    @if(request()->routeIs('documentos.*') && request()->route('id'))
                        @php
                            $docCasoId = request()->route('id');
                            $docCaso = \App\Models\ProcesoDisciplinario::find($docCasoId);
                            $tipoActual = request()->route('tipo');
                        @endphp
                        @foreach(\App\Models\CasoDocumentoEstado::SLOTS as $slot => $variantes)
                            @php $tipoSlot = $docCaso ? $docCaso->varianteDelSlot($slot) : $variantes[0]; @endphp
                            <a href="{{ route('documentos.edit', [$docCasoId, $tipoSlot]) }}"
                               class="{{ request()->routeIs('documentos.edit') && in_array($tipoActual, $variantes, true) ? 'active' : '' }}"
                               style="padding-left:28px;font-size:13px;">
                                <i class="fas {{ \App\Models\CasoDocumentoEstado::SLOT_ICONS[$slot] }}"></i>
                                {{ \App\Models\CasoDocumentoEstado::SLOT_LABELS[$slot] }}
                            </a>
                        @endforeach
                    @endif
                </nav>
                @endif

                @if($me->puede('ver_reportes'))
                <div class="sipd-nav-label">REPORTES</div>
                <nav class="sipd-nav">
                    <a href="{{ route('abogado.reportes') }}" class="{{ request()->routeIs('abogado.estadistica', 'abogado.reportes') ? 'active' : '' }}">
                        <i class="fas fa-chart-bar"></i> Estadísticas / Reportes
                    </a>
                </nav>
                @endif
            </div>


            <div class="sipd-side-user">
                <a class="sipd-logout" href="{{ route('logout') }}"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="fas fa-sign-out-alt"></i> Cerrar sesión
                </a>
            </div>
        </aside>

        <div class="sipd-main">
            <button type="button" class="sipd-nav-scrim" id="sipd-nav-scrim" aria-label="Cerrar menú"></button>
            <header class="sipd-top">
                <div class="sipd-crumb d-flex align-items-center gap-3">
                    <button type="button" class="sipd-nav-toggle" id="sipd-nav-toggle" aria-label="Abrir menú">
                        <i class="fas fa-bars"></i>
                    </button>
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
                    @if(!$isManager)
                        <button type="button" class="btn-alt sipd-req-btn" id="open-permiso-modal">
                            <i class="fas fa-key"></i> Solicitar permiso
                        </button>
                    @else
                        <a class="btn-alt sipd-req-btn" href="{{ route('coordinadora.notificar') }}">
                            <i class="far fa-paper-plane"></i> Avisar al equipo
                        </a>
                    @endif
                    <div class="dropdown">
                        <a class="sipd-bell dropdown-toggle" href="#" data-toggle="dropdown" title="Notificaciones">
                            <i class="far fa-bell"></i>
                            @if($notifTotal > 0)
                                <span class="badge-dot">{{ $notifTotal }}</span>
                            @endif
                        </a>
                        <div class="dropdown-menu dropdown-menu-right notif-menu">
                            <div class="notif-menu-head">
                                <h4>Notificaciones</h4>
                                <span class="notif-menu-count {{ $notifTotal === 0 ? 'is-zero' : '' }}">{{ $notifTotal }}</span>
                            </div>
                            @if($bandeja->isNotEmpty())
                                @foreach($bandeja as $aviso)
                                    <a href="{{ route('notificaciones.leer', $aviso->id) }}" class="{{ $aviso->esDirectiva() ? 'is-coord' : '' }}">
                                        <span class="notif-ico {{ $aviso->tonoIcono() }}">
                                            <i class="fas {{ $aviso->icono() }}"></i>
                                        </span>
                                        <span>
                                            <b>
                                                @if($aviso->esDirectiva())
                                                    <em class="notif-tag">Aviso de coordinación</em>
                                                @endif
                                                {{ $aviso->titulo }}
                                            </b>
                                            <span>{{ \Illuminate\Support\Str::limit($aviso->cuerpo ?: $aviso->motivo, 90) }}</span>
                                        </span>
                                    </a>
                                @endforeach
                            @endif
                            @foreach($alertasSistema as $alerta)
                                <div class="notif-item">
                                    <a href="{{ $alerta['href'] }}">
                                        <span class="notif-ico {{ $alerta['tono'] }}">
                                            <i class="{{ $alerta['icono'] }}"></i>
                                        </span>
                                        <span>
                                            <b>{{ $alerta['titulo'] }}</b>
                                            <span>{{ $alerta['detalle'] }}</span>
                                        </span>
                                    </a>
                                    <form method="POST" action="{{ route('notificaciones.alertas.silenciar', $alerta['tipo']) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="notif-mute" title="Quitar esta alerta">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                            @if($notifTotal === 0)
                                <div class="notif-empty">
                                    <i class="far fa-bell-slash"></i>
                                    No hay notificaciones nuevas
                                </div>
                            @endif
                            <a class="notif-footer" href="{{ route('notificaciones.index') }}">Ver bandeja</a>
                        </div>
                    </div>

                    <div class="dropdown sipd-user-drop">
                        <a class="sipd-user dropdown-toggle" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="{{ auth()->user()->name }}">
                            <span class="ava">{{ $iniciales }}</span>
                            <span class="sipd-user-meta">
                                <b>{{ $nombreCorto }}</b>
                                <small>{{ $etiquetaRol }}</small>
                            </span>
                            <i class="fas fa-chevron-down chev"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right user-menu">
                            <div class="user-menu-head">
                                <span class="ava">{{ $iniciales }}</span>
                                <div class="user-menu-meta">
                                    <b title="{{ auth()->user()->name }}">{{ auth()->user()->name }}</b>
                                    <span class="mail" title="{{ auth()->user()->email }}">{{ auth()->user()->email }}</span>
                                    <small>{{ $etiquetaRol }}</small>
                                </div>
                            </div>
                            <div class="user-menu-actions">
                                <a class="dropdown-item" href="#" id="open-profile-modal">
                                    <span class="umi"><i class="far fa-user"></i></span>
                                    Mi perfil
                                </a>
                                <a class="dropdown-item" href="#" id="open-password-modal" @if(!$puedeEditarPerfil) data-need-perfil="1" @endif>
                                    <span class="umi"><i class="fas fa-lock"></i></span>
                                    Cambiar contraseña
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item danger" href="{{ route('logout') }}"
                                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <span class="umi"><i class="fas fa-sign-out-alt"></i></span>
                                    Cerrar sesión
                                </a>
                            </div>
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

    @if(!$isManager)
    <div class="sipd-dialog-bg" id="modalSolicitarPermiso">
        <div class="sipd-dialog">
            <h3>Solicitar permiso</h3>
            <p class="sipd-dialog-lead">La coordinadora recibe qué vas a hacer, por qué y por cuántas horas.</p>
            <form method="POST" action="{{ route('permisos.solicitar') }}" id="formSolicitarPermiso">
                @csrf
                <label>Módulo</label>
                <select name="permiso" id="sol-permiso" required>
                    @foreach($solicitablesPermiso as $clave => $etiqueta)
                        <option value="{{ $clave }}">{{ $etiqueta }}</option>
                    @endforeach
                </select>
                <label>Qué vas a hacer</label>
                <textarea name="que_hara" rows="3" required maxlength="1000" placeholder="Describe la acción concreta."></textarea>
                <label>Por qué lo necesitas</label>
                <textarea name="motivo" rows="3" required maxlength="1000" placeholder="Explica el motivo."></textarea>
                <label>Tiempo</label>
                <select name="duracion" id="sol-duracion" required>
                    @foreach($duracionesHoras as $valor => $texto)
                        <option value="{{ $valor }}">{{ $texto }}</option>
                    @endforeach
                </select>
                <div id="sol-custom" class="is-hidden">
                    <label>Horas personalizadas</label>
                    <input type="number" name="horas_custom" min="1" max="168" placeholder="Ej. 8">
                </div>
                <div class="sipd-dialog-actions">
                    <button type="button" class="btn-ghost" id="cerrar-permiso-modal">Cancelar</button>
                    <button type="submit" class="btn-ok">Enviar solicitud</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    @php $authUser = auth()->user(); @endphp
    <div class="pwd-modal {{ session('open_profile') || $errors->has('name') || $errors->has('email') ? 'open' : '' }}" id="profile-modal">
        <div class="pf-box">
            <div class="pf-hero">
                <div class="pf-hero-user">
                    <span class="ava">{{ $iniciales }}</span>
                    <div>
                        <b>{{ $authUser->name }}</b>
                        <small>{{ $etiquetaRol }}</small>
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
                        @if($puedeEditarPerfil)
                            <button type="button" class="pwd-save" id="edit-profile-btn">Editar perfil</button>
                            <button type="submit" class="pwd-save" id="save-profile-btn" style="display:none;">Guardar cambios</button>
                        @else
                            <button type="button" class="pwd-save" id="pedir-perfil-btn">Solicitar permiso para editar</button>
                        @endif
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

                @if($puedeEditarPerfil)
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
                @else
                <p class="pwd-error" style="margin:0 0 16px;">Cambiar la contraseña entra en el permiso de editar perfil. Pídeselo a la coordinadora.</p>
                <div class="pwd-actions">
                    <button type="button" class="pwd-cancel" id="cancel-password-modal">Cancelar</button>
                    <button type="button" class="pwd-save" id="pedir-password-btn">Solicitar permiso</button>
                </div>
                @endif
            </form>
        </div>
    </div>

    @php
        $sipdFlashes = [];
        $okText = session('success') ?: session('profile_success') ?: session('password_success') ?: session('status');
        if ($okText) {
            $sipdFlashes[] = ['icon' => 'success', 'title' => 'Listo', 'text' => $okText];
        }
        if (session('error')) {
            $sipdFlashes[] = ['icon' => 'error', 'title' => 'No se pudo completar', 'text' => session('error')];
        }
        if (session('warning')) {
            $sipdFlashes[] = ['icon' => 'warning', 'title' => 'Atención', 'text' => session('warning')];
        }
        if (session('info')) {
            $sipdFlashes[] = ['icon' => 'info', 'title' => 'Aviso', 'text' => session('info')];
        }
    @endphp
    <script>window.SIPD_FLASH = @json($sipdFlashes);</script>
    @if(session('clear_nuevo_draft'))
    <script>
        (function () {
            try {
                localStorage.removeItem('sipd_nuevo_proceso');
                Object.keys(localStorage).forEach(function (k) {
                    if (k.indexOf('sipd_nuevo_') === 0) localStorage.removeItem(k);
                });
            } catch (e) {}
        })();
    </script>
    @endif
    <script src="{{ rtrim(request()->root(), '/') }}/js/sipd-feedback.js?v=8"></script>

    <script src="{{ rtrim(request()->root(), '/') }}/AdminLTE-3.2.0/plugins/jquery/jquery.min.js"></script>
    <script src="{{ rtrim(request()->root(), '/') }}/AdminLTE-3.2.0/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            var app = document.querySelector('.sipd-app');
            var toggle = document.getElementById('sipd-nav-toggle');
            var scrim = document.getElementById('sipd-nav-scrim');

            function setNav(open) {
                if (!app) return;
                app.classList.toggle('is-nav-open', open);
                document.body.style.overflow = open ? 'hidden' : '';
                if (toggle) toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            }

            if (toggle) {
                toggle.addEventListener('click', function () {
                    setNav(!app.classList.contains('is-nav-open'));
                });
            }
            if (scrim) {
                scrim.addEventListener('click', function () { setNav(false); });
            }
            window.addEventListener('resize', function () {
                if (window.innerWidth > 900) setNav(false);
            });

            var modal = document.getElementById('password-modal');
            var openBtn = document.getElementById('open-password-modal');
            var closeBtn = document.getElementById('close-password-modal');
            var cancelBtn = document.getElementById('cancel-password-modal');

            function openModal(e) {
                if (e) e.preventDefault();
                if (openBtn && openBtn.getAttribute('data-need-perfil')) {
                    var ta = document.querySelector('#formSolicitarPermiso textarea[name="que_hara"]');
                    if (ta && !ta.value) ta.value = 'Cambiar la contraseña de mi cuenta.';
                    abrirPermisoModal('editar_perfil');
                    return;
                }
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
            var pedirPerfil = document.getElementById('pedir-perfil-btn');
            var profileInputs = document.querySelectorAll('#profile-form input');

            function openProfileModal(e) {
                if (e) e.preventDefault();
                profileModal.classList.add('open');
            }

            function closeProfileModal() {
                profileModal.classList.remove('open');
            }

            function enableProfileEdit() {
                if (!editProfile || !saveProfile) return;
                profileInputs.forEach(function (input) { input.readOnly = false; });
                editProfile.style.display = 'none';
                saveProfile.style.display = 'inline-flex';
            }

            if (openProfile) openProfile.addEventListener('click', openProfileModal);
            if (closeProfile) closeProfile.addEventListener('click', closeProfileModal);
            if (cancelProfile) cancelProfile.addEventListener('click', closeProfileModal);
            if (editProfile) editProfile.addEventListener('click', enableProfileEdit);

            var permisoModal = document.getElementById('modalSolicitarPermiso');
            var openPermiso = document.getElementById('open-permiso-modal');
            var closePermiso = document.getElementById('cerrar-permiso-modal');
            function abrirPermisoModal(permiso) {
                if (!permisoModal) return;
                if (permiso) {
                    var sel = document.getElementById('sol-permiso');
                    if (sel) sel.value = permiso;
                }
                permisoModal.style.display = 'flex';
            }
            function cerrarPermisoModal() {
                if (permisoModal) permisoModal.style.display = 'none';
            }
            if (openPermiso) openPermiso.addEventListener('click', function () { abrirPermisoModal(); });
            if (closePermiso) closePermiso.addEventListener('click', cerrarPermisoModal);
            if (permisoModal) {
                permisoModal.addEventListener('click', function (e) {
                    if (e.target === permisoModal) cerrarPermisoModal();
                });
            }
            if (pedirPerfil) {
                pedirPerfil.addEventListener('click', function () {
                    closeProfileModal();
                    abrirPermisoModal('editar_perfil');
                });
            }
            var pedirPassword = document.getElementById('pedir-password-btn');
            if (pedirPassword) {
                pedirPassword.addEventListener('click', function () {
                    closeModal();
                    var ta = document.querySelector('#formSolicitarPermiso textarea[name="que_hara"]');
                    if (ta && !ta.value) ta.value = 'Cambiar la contraseña de mi cuenta.';
                    abrirPermisoModal('editar_perfil');
                });
            }
            var solDuracion = document.getElementById('sol-duracion');
            var solCustom = document.getElementById('sol-custom');
            if (solDuracion && solCustom) {
                solDuracion.addEventListener('change', function () {
                    solCustom.classList.toggle('is-hidden', this.value !== 'custom');
                });
            }
            window.SIPD_abrirPermiso = abrirPermisoModal;
            document.addEventListener('click', function (e) {
                var need = e.target.closest('[data-need-permiso]');
                if (!need) return;
                e.preventDefault();
                var clave = need.getAttribute('data-need-permiso');
                var que = need.getAttribute('data-que') || '';
                var abrir = function () {
                    abrirPermisoModal(clave);
                    if (que) {
                        var ta = document.querySelector('#formSolicitarPermiso textarea[name="que_hara"]');
                        if (ta && !ta.value) ta.value = que;
                    }
                };
                if (window.SIPD && typeof window.SIPD.confirm === 'function') {
                    window.SIPD.confirm({
                        title: 'Se necesita permiso',
                        text: 'La coordinadora debe otorgarte permiso para borrar notificaciones.',
                        confirmText: 'Solicitar ahora'
                    }).then(function (ok) {
                        if (!ok) return;
                        if (window.Swal) window.Swal.close();
                        window.setTimeout(abrir, 80);
                    });
                    return;
                }
                abrir();
            });

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

            document.querySelectorAll('.sipd-pager-size').forEach(function (form) {
                var choice = form.querySelector('.sipd-pager-choice');
                var input = form.querySelector('input[name="per_page"]');
                var go = form.querySelector('.sipd-pager-go');
                if (!choice || !input) return;
                function sync(submitPreset) {
                    var custom = choice.value === 'custom';
                    input.classList.toggle('is-hidden', !custom);
                    if (go) go.classList.toggle('is-hidden', !custom);
                    if (!custom) {
                        input.value = choice.value;
                        if (submitPreset) form.submit();
                    } else if (submitPreset) {
                        input.focus();
                        input.select();
                    }
                }
                choice.addEventListener('change', function () { sync(true); });
            });
        })();
    </script>
    @yield('scripts')
</body>
</html>
