<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="/images/logo-sipd.png?v=colibri" type="image/png">
    <title>@yield('title', 'SIPD Cootranshuila')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<<<<<<< HEAD
    <link rel="stylesheet" href="{{ rtrim(request()->root(), '/') }}/css/sipd-theme.css?v=50">
=======
    <link rel="stylesheet" href="/css/sipd-theme.css?v=51">
    <link rel="stylesheet" href="/css/sipd-halloween.css?v=2">
>>>>>>> origin/main
</head>
<body>
    @include('partials.halloween', ['hwMode' => 'full'])
    <div class="guest-split">
        <aside class="guest-hero">
            <a class="guest-brand" href="{{ route('login') }}">
                <span class="guest-logo">
                    <img src="/images/logo-cootranshuila.png?v=marca2" alt="Cootranshuila">
                </span>
                <span class="guest-brand-text">
                    <span class="guest-sipd-row">
                        <img class="guest-sipd-mark" src="/images/logo-sipd.png?v=colibri" alt="">
                        <strong>SIPD</strong>
                    </span>
                    <small>Sistema de procesos disciplinarios</small>
                </span>
            </a>

            <div class="guest-copy">
                <div class="guest-pills">
                    <div class="guest-badge">PLATAFORMA INSTITUCIONAL</div>
                    <span class="hw-season">HALLOWEEN</span>
                </div>
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
