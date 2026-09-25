<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Acceso al sistema · SIPD</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            min-height: 100%;
            font-family: Inter, system-ui, -apple-system, sans-serif;
            color: #0f172a;
            background: #fafafa;
        }

        .split {
            display: grid;
            grid-template-columns: 38% 1fr;
            min-height: 100vh;
        }

        .hero {
            position: relative;
            overflow: hidden;
            background: #050910;
            color: #fff;
            padding: 44px 48px 36px;
            display: flex;
            flex-direction: column;
        }

        .hero-orb {
            position: absolute;
            top: -140px;
            right: -110px;
            width: 360px;
            height: 360px;
            border-radius: 50%;
            background: #0d1b2c;
            pointer-events: none;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
            z-index: 1;
        }

        .brand svg { display: block; color: #d6dee8; }

        .brand-name {
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0.08em;
            line-height: 1;
        }

        .brand-sub {
            font-size: 9px;
            letter-spacing: 0.18em;
            color: #8b97a8;
            margin-top: 5px;
            text-transform: uppercase;
        }

        .hero-copy {
            position: relative;
            z-index: 1;
            margin: auto 0;
            max-width: 460px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 999px;
            background: #101826;
            color: #c5d0dc;
            font-size: 10px;
            letter-spacing: 0.16em;
            font-weight: 600;
            margin-bottom: 22px;
        }

        .hero h1 {
            margin: 0 0 16px;
            font-size: 42px;
            line-height: 1.08;
            letter-spacing: -0.04em;
            font-weight: 600;
        }

        .hero-copy p {
            margin: 0;
            color: #8b97a8;
            font-size: 15px;
            line-height: 1.6;
            max-width: 420px;
        }

        .public-box {
            position: relative;
            z-index: 1;
            margin-top: auto;
            background: #101826;
            border-radius: 20px;
            padding: 20px 20px 18px;
        }

        .public-box small {
            display: block;
            color: #8b97a8;
            font-size: 10px;
            letter-spacing: .16em;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .public-box p {
            color: #e8eef4;
            font-size: 14px;
            line-height: 1.5;
            margin: 0 0 16px;
        }

        .btn-public {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            height: 44px;
            border: 0;
            border-radius: 999px;
            background: #7c3aed;
            color: #fff;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
        }

        .btn-public i {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #fff;
            display: inline-block;
        }

        .btn-public:hover { color: #fff; filter: brightness(1.06); }

        .panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 24px;
            background: #fafafa;
        }

        .panel-inner { width: 100%; max-width: 456px; }

        .panel h2 {
            margin: 0 0 8px;
            font-size: 36px;
            letter-spacing: -0.04em;
            font-weight: 700;
            color: #0b1220;
        }

        .panel-lead {
            margin: 0 0 32px;
            color: #64748b;
            font-size: 15px;
        }

        .field { margin-bottom: 18px; }

        label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #111827;
        }

        .req { color: #ef4444; }

        input[type="email"],
        input[type="password"],
        input[type="text"] {
            width: 100%;
            height: 48px;
            border: 1px solid #e6e8eb;
            border-radius: 14px;
            padding: 0 16px;
            font: inherit;
            font-size: 14px;
            outline: none;
            background: #fff;
            color: #0f172a;
        }

        input::placeholder { color: #c5cdd6; }

        input:focus { border-color: #86efac; }

        .password-wrap { position: relative; }

        .toggle-pass {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            border: 0;
            background: none;
            color: #94a3b8;
            font: inherit;
            font-size: 14px;
            cursor: pointer;
        }

        .row-between {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 6px 0 22px;
        }

        .remember {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #334155;
            font-size: 14px;
            font-weight: 400;
        }

        .remember input {
            width: 15px;
            height: 15px;
            accent-color: #22c55e;
        }

        .forgot {
            color: #94a3b8;
            font-size: 14px;
            text-decoration: none;
        }

        .btn-submit {
            width: 100%;
            height: 48px;
            border: 0;
            border-radius: 999px;
            background: #22c55e;
            color: #fff;
            font: inherit;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
        }

        .quick { margin-top: 30px; }

        .quick-title {
            font-size: 11px;
            letter-spacing: 0.12em;
            color: #94a3b8;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .quick-btn {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 0;
            border-radius: 14px;
            padding: 11px 14px;
            margin-bottom: 10px;
            cursor: pointer;
            text-align: left;
            font: inherit;
            background: transparent;
        }

        .quick-btn.coordinadora { background: #f0fdf4; }
        .quick-btn.rh { background: #eff6ff; }

        .avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: #fff;
            color: #64748b;
            flex-shrink: 0;
        }

        .quick-copy { flex: 1; min-width: 0; }
        .quick-copy strong {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #0f172a;
        }
        .quick-copy span {
            display: block;
            font-size: 12px;
            color: #94a3b8;
            margin-top: 1px;
            white-space: nowrap;
        }
        .quick-go {
            color: #94a3b8;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
        }

        .note {
            margin-top: 18px;
            color: #94a3b8;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .note::before {
            content: "";
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #86efac;
            flex-shrink: 0;
        }

        .error { color: #dc2626; font-size: 12px; margin-top: 6px; }

        @media (max-width: 960px) {
            .split { grid-template-columns: 1fr; }
            .hero { min-height: auto; padding: 28px 22px; }
            .hero h1 { font-size: 34px; }
            .panel { padding: 32px 20px 40px; }
        }
    </style>
</head>
<body>
    <div class="split">
        <aside class="hero">
            <div class="hero-orb"></div>

            <div class="brand">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                    <path d="M12 3l7.2 3.1v6.2c0 5.1-3.3 8.6-7.2 9.7-3.9-1.1-7.2-4.6-7.2-9.7V6.1L12 3z"></path>
                </svg>
                <div>
                    <div class="brand-name">SIPD</div>
                    <div class="brand-sub">Procesos disciplinarios</div>
                </div>
            </div>

            <div class="hero-copy">
                <div class="badge">PLATAFORMA INSTITUCIONAL</div>
                <h1>Gestión disciplinaria integrada</h1>
                <p>Sistema de Recursos Humanos para la administración y seguimiento de procesos disciplinarios.</p>
            </div>

            <div class="public-box">
                <small>ACCESO PÚBLICO</small>
                <p>¿Eres conductor y quieres consultar tu proceso disciplinario?</p>
                <a class="btn-public" href="{{ route('consulta.publica') }}"><i></i>Consultar mi caso</a>
            </div>
        </aside>

        <main class="panel">
            <div class="panel-inner">
                <h2>Acceso al sistema</h2>
                <p class="panel-lead">Ingresa las credenciales de tu cuenta institucional.</p>

                <form method="POST" action="{{ route('login') }}" id="login-form">
                    @csrf

                    <div class="field">
                        <label for="email">Correo electrónico <span class="req">*</span></label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="usuario@sipd.co" required autofocus>
                        @error('email') <div class="error">{{ $message }}</div> @enderror
                    </div>

                    <div class="field">
                        <label for="password">Contraseña <span class="req">*</span></label>
                        <div class="password-wrap">
                            <input id="password" type="password" name="password" required>
                            <button type="button" class="toggle-pass" id="toggle-pass">Ver</button>
                        </div>
                        @error('password') <div class="error">{{ $message }}</div> @enderror
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

                    <button type="button" class="quick-btn coordinadora" data-email="coordinadora@sipd.co" data-password="admin123">
                        <span class="avatar">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 20v-1a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v1"></path>
                                <circle cx="10" cy="7" r="3"></circle>
                                <path d="M16 11h5"></path>
                                <path d="M18.5 8.5v5"></path>
                            </svg>
                        </span>
                        <span class="quick-copy">
                            <strong>Coordinador/a de RH</strong>
                            <span>coordinadora@sipd.co</span>
                        </span>
                        <span class="quick-go">Entrar →</span>
                    </button>

                    <button type="button" class="quick-btn rh" data-email="rh@sipd.co" data-password="admin123">
                        <span class="avatar">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </span>
                        <span class="quick-copy">
                            <strong>Equipo de RH</strong>
                            <span>rh@sipd.co</span>
                        </span>
                        <span class="quick-go">Entrar →</span>
                    </button>
                </div>

                <div class="note">Acceso exclusivo para personal institucional de SIPD.</div>
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
