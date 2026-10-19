<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('images/logo-sipd.png') }}" type="image/png">
    <title>@yield('title', 'Recuperar contraseña · SIPD Cootranshuila')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ rtrim(request()->root(), '/') }}/css/sipd-theme.css?v=58">
</head>
<body class="guest-focus-page">
    <main class="guest-focus-main">
        <a class="guest-focus-brand" href="{{ route('login') }}">
            <img src="{{ asset('images/logo-sipd.png') }}" alt="">
            <span><strong>SIPD</strong><small>Cootranshuila</small></span>
        </a>
        <section class="guest-focus-card">
            <div class="guest-focus-decoration" aria-hidden="true">
                <span class="guest-focus-icon"><i class="fas fa-lock"></i></span>
                <span class="guest-focus-orbit guest-focus-orbit-one"></span>
                <span class="guest-focus-orbit guest-focus-orbit-two"></span>
            </div>
            @yield('content')
        </section>
    </main>
    @yield('scripts')
</body>
</html>
