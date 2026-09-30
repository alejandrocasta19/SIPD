<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'SIPD') }} · Cootranshuila</title>
    <script src="{{ asset('js/app.js') }}" defer></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="{{ rtrim(request()->root(), '/') }}/css/app.css" rel="stylesheet">
    <link href="{{ rtrim(request()->root(), '/') }}/css/sipd-theme.css?v=2" rel="stylesheet">
</head>
<body>
    <div id="app" class="guest-auth-wrap">
        <header class="guest-auth-top">
            <a href="{{ route('login') }}">SIPD · Cootranshuila</a>
            <a href="{{ route('login') }}" style="font-weight:500;font-size:13px;opacity:.85;">Volver al acceso</a>
        </header>

        <main class="py-4">
            <div class="container">
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
