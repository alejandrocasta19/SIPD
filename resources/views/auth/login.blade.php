<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Acceso al sistema · SIPD Cootranshuila</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ rtrim(request()->root(), '/') }}/css/sipd-theme.css?v=6">
</head>
<body>
    <div class="guest-split">
        <aside class="guest-hero">
            <a class="guest-brand" href="{{ route('login') }}">
                <span class="guest-logo">
                    <img src="{{ rtrim(request()->root(), '/') }}/images/logo-cootranshuila-claro.png" alt="Cootranshuila">
                </span>
                <span class="guest-brand-text">
                    <strong>SIPD</strong>
                    <small>Sistema de procesos disciplinarios</small>
                </span>
            </a>

            <div class="guest-copy">
                <div class="guest-badge">PLATAFORMA INSTITUCIONAL</div>
                <h1>Gestión disciplinaria de Cootranshuila</h1>
                <p>Sistema de Recursos Humanos para la administración y seguimiento de procesos disciplinarios.</p>
            </div>

            <div class="guest-public">
                <small>ACCESO PÚBLICO</small>
                <p>¿Eres conductor y quieres consultar tu proceso disciplinario?</p>
                <a class="btn-public" href="{{ route('consulta.publica') }}">Consultar mi caso</a>
            </div>
        </aside>

        <main class="guest-panel">
            <div class="guest-panel-inner">
                <h2>Acceso al sistema</h2>
                <p class="guest-lead">Ingresa las credenciales de tu cuenta institucional.</p>

                <form method="POST" action="{{ route('login') }}" id="login-form">
                    @csrf

                    <div class="guest-field">
                        <label for="email">Correo electrónico <span class="req">*</span></label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="usuario@cootranshuila.com" required autofocus>
                        @error('email') <div class="guest-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="guest-field">
                        <label for="password">Contraseña <span class="req">*</span></label>
                        <div class="password-wrap">
                            <input id="password" type="password" name="password" required>
                            <button type="button" class="toggle-pass" id="toggle-pass">Ver</button>
                        </div>
                        @error('password') <div class="guest-error">{{ $message }}</div> @enderror
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

                    <button type="submit" class="btn-submit">Ingresar al sistema</button>
                </form>

                <div class="quick">
                    <div class="quick-title">ACCESO RÁPIDO DE DEMOSTRACIÓN</div>

                    <button type="button" class="quick-btn" data-email="kelly.johanna.rodriguez@pendiente.local" data-password="admin123">
                        <span class="avatar">KJ</span>
                        <span class="quick-copy">
                            <strong>KELLY JOHANNA RODRIGUEZ VARGAS</strong>
                            <span>Asesora Jurídica</span>
                        </span>
                        <span class="quick-go">Entrar →</span>
                    </button>

                    <button type="button" class="quick-btn" data-email="coordinadora@sipd.co" data-password="admin123">
                        <span class="avatar">RH</span>
                        <span class="quick-copy">
                            <strong>Coordinador/a de RH</strong>
                            <span>coordinadora@sipd.co</span>
                        </span>
                        <span class="quick-go">Entrar →</span>
                    </button>

                    <button type="button" class="quick-btn" data-email="rh@sipd.co" data-password="admin123">
                        <span class="avatar">EQ</span>
                        <span class="quick-copy">
                            <strong>Equipo de RH</strong>
                            <span>rh@sipd.co</span>
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

        toggle.addEventListener('click', function () {
            const visible = password.type === 'text';
            password.type = visible ? 'password' : 'text';
            toggle.textContent = visible ? 'Ver' : 'Ocultar';
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
