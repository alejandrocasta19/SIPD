@extends('layouts.guest')

@section('title', 'Recuperar contraseña · SIPD Cootranshuila')

@section('content')
    <h2>Recuperar contraseña</h2>
    <p class="guest-lead">Ingresa el correo de tu cuenta institucional. Te enviaremos un enlace para restablecerla.</p>

    @if(session('status'))
        <div class="sipd-alert sipd-alert-success" role="status">
            Te enviamos el enlace al correo si esa cuenta existe en el sistema.
        </div>
    @endif

    @if($errors->any())
        <div class="sipd-alert sipd-alert-error" role="alert">
            <div>
                <strong>No se pudo enviar el enlace</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" id="forgot-form">
        @csrf
        <div class="guest-field">
            <label for="email">Correo electrónico <span class="req">*</span></label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="usuario@cootranshuila.com" autocomplete="username" required autofocus class="{{ $errors->has('email') ? 'is-invalid' : '' }}">
        </div>
        <button type="submit" class="btn-submit" id="forgot-submit">Enviar enlace</button>
    </form>

    <a class="guest-back" href="{{ route('login') }}">← Volver al acceso</a>
    <div class="guest-note">Acceso exclusivo para personal institucional de Cootranshuila.</div>
@endsection

@section('scripts')
<script>
    (function () {
        var form = document.getElementById('forgot-form');
        var submit = document.getElementById('forgot-submit');
        if (!form || !submit) return;
        form.addEventListener('submit', function () {
            submit.disabled = true;
            submit.textContent = 'Enviando…';
        });
    })();
</script>
@endsection
