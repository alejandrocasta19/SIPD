<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('images/logo-sipd.png') }}" type="image/png">
    <title>@yield('title', 'SIPD Cootranshuila')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ rtrim(request()->root(), '/') }}/css/sipd-theme.css?v=45">
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
                        <img class="guest-sipd-mark" src="{{ rtrim(request()->root(), '/') }}/images/logo-sipd.png" alt="">
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
                <p>¿Quieres consultar el estado de tu proceso?</p>
                <a class="btn-public" href="{{ route('consulta.publica') }}">Consultar mi caso</a>
            </div>
        </aside>

        <main class="guest-panel">
            <div class="guest-panel-inner">
                @yield('content')
            </div>
        </main>
    </div>
    @yield('scripts')
</body>
</html>
