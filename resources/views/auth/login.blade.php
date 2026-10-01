<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('images/logo-sipd.svg') }}" type="image/svg+xml">
    <title>Acceso al sistema · SIPD Cootranshuila</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ rtrim(request()->root(), '/') }}/css/sipd-theme.css?v=22">
</head>
<body>
    <div class="guest-split">
        <aside class="guest-hero">
            <a class="guest-brand" href="{{ route('login') }}">
                <span class="guest-logo">
                    <img src="{{ rtrim(request()->root(), '/') }}/images/logo-cootranshuila-claro.png" alt="Cootranshuila">
                </span>
                <span class="guest-brand-text">
                    <span class="guest-sipd-row">
                        <img class="guest-sipd-mark" src="{{ rtrim(request()->root(), '/') }}/images/logo-sipd.svg" alt="">
                        <strong>SIPD</strong>
                    </span>
                    <small>Sistema de procesos disciplinarios</small>
                </span>
            </a>

            <div class="guest-copy">
                <div class="guest-badge">PLATAFORMA INSTITUCIONAL</div>
                <h1>Gestión disciplinaria de Cootranshuila</h1>
                <p>Sistema de Recursos Humanos para la administración y seguimiento de procesos disciplinarios.</p>
                <ul class="guest-points">
                    <li>Expedientes y documentos oficiales en un solo lugar</li>
                    <li>Seguimiento de plazos, descargos y veredicto</li>
                    <li>Consulta pública del trámite, sin datos reservados</li>
                </ul>
            </div>

            <div class="guest-public">
                <small>ACCESO PÚBLICO</small>
                <p>¿Eres conductor y quieres consultar el estado de tu proceso?</p>
                <a class="btn-public" href="{{ route('consulta.publica') }}">Consultar mi caso</a>
            </div>
        </aside>

        <main class="guest-panel">
            <div class="guest-panel-inner">
                <h2>Acceso al sistema</h2>
                <p class="guest-lead">Ingresa las credenciales de tu cuenta institucional.</p>

                @if($errors->any())
                    <div class="sipd-alert sipd-alert-error" role="alert">
                        <div>
                            <strong>No se pudo iniciar sesión</strong>
                            <ul>
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" id="login-form">
                    @csrf

                    <div class="guest-field">
                        <label for="email">Correo electrónico <span class="req">*</span></label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="usuario@cootranshuila.com" autocomplete="username" required autofocus class="{{ $errors->has('email') ? 'is-invalid' : '' }}">
                    </div>

                    <div class="guest-field">
                        <label for="password">Contraseña <span class="req">*</span></label>
                        <div class="password-wrap">
                            <input id="password" type="password" name="password" autocomplete="current-password" required class="{{ $errors->has('password') ? 'is-invalid' : '' }}">
                            <button type="button" class="toggle-pass" id="toggle-pass" aria-label="Mostrar contraseña">Ver</button>
                        </div>
                    </div>

                    <div class="row-between">
                        <label class="remember">
                            <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                            Recordar sesión
                        </label>
                        @if (Route::has('password.request'))
                            <a class="forgot" href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
                        @endif
                    </div>

                    <button type="submit" class="btn-submit" id="login-submit">Ingresar al sistema</button>
                </form>

                <div class="quick">
                    <div class="quick-title">Acceso rápido</div>
                    <button type="button" class="quick-btn" data-email="kelly.johanna.rodriguez@pendiente.local" data-password="admin123">
                        <span class="avatar">KJ</span>
                        <span class="quick-copy">
                            <strong>Equipo de RH</strong>
                            <span>KELLY JOHANNA RODRIGUEZ VARGAS</span>
                        </span>
                        <span class="quick-go">Entrar →</span>
                    </button>
                    <button type="button" class="quick-btn" data-email="coordinadora@sipd.co" data-password="admin123">
                        <span class="avatar">CR</span>
                        <span class="quick-copy">
                            <strong>Coordinadora de RH</strong>
                            <span>coordinadora@sipd.co</span>
                        </span>
                        <span class="quick-go">Entrar →</span>
                    </button>
                </div>

                <div class="guest-note">Acceso exclusivo para personal institucional de Cootranshuila.</div>
            </div>
        </main>
    </div>

    <script>
        const toggle = document.getElementById('toggle-pass');
        const password = document.getElementById('password');
        const email = document.getElementById('email');
        const form = document.getElementById('login-form');
        const submit = document.getElementById('login-submit');

        toggle.addEventListener('click', function () {
            const visible = password.type === 'text';
            password.type = visible ? 'password' : 'text';
            toggle.textContent = visible ? 'Ver' : 'Ocultar';
            toggle.setAttribute('aria-label', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
        });

        form.addEventListener('submit', function () {
            submit.disabled = true;
            submit.textContent = 'Ingresando…';
        });

        document.querySelectorAll('.quick-btn').forEach(function (button) {
            button.addEventListener('click', function () {
                email.value = button.dataset.email || '';
                password.value = button.dataset.password || '';
                form.submit();
            });
        });
    </script>
</body>
</html>
